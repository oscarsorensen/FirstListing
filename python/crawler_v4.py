import json
import re
import sys
import time
from datetime import datetime
from urllib.parse import urlparse, urldefrag

import mysql.connector
import requests
from lxml import html

# === CONFIG ===

SITE_ROOT = "https://jensenestate.es/"
SITE_HOST = "jensenestate.es"
LISTING_INDEX_URL = "https://jensenestate.es/es/propiedades/"

DB_CONFIG = {
    "host": "localhost",
    "user": "firstlisting_user",
    "password": "girafferharlangehalse",
    "database": "test2firstlisting",
    "charset": "utf8mb4",
    "use_unicode": True,
}

HEADERS = {"User-Agent": "FirstListingBot/4.0 (+school MVP; allowed by Gitte, respectful crawl)"}
REQUEST_DELAY = 1.5
TIMEOUT = 15
DEFAULT_MAX_LISTINGS = 50
PAGINATION_STEP = 12
MAX_CATALOG_PAGES = 40


# === HELPERS ===

def clean_text(text):
    if not text:
        return ""
    return re.sub(r"\s+", " ", text).strip()


def is_listing_url(url):
    # Kun spanske listing-URLs: /es/propiedad/<tal>[/slug]
    return re.match(r"^https://jensenestate\.es/es/propiedad/\d+(?:/[^?#]*)?$", url) is not None


def normalize_url(url):
    url = urldefrag(url)[0].strip()
    if url.endswith("/") and len(url) > len("https://jensenestate.es/"):
        url = url[:-1]
    return url


def extract_text_and_jsonld(html_bytes):
    tree = html.fromstring(html_bytes)
    text_raw = clean_text(" ".join(tree.itertext()))

    jsonld_items = []
    for scr in tree.xpath("//script[@type='application/ld+json']/text()"):
        try:
            data = json.loads(scr)
        except json.JSONDecodeError:
            continue
        if isinstance(data, dict) and "@graph" in data:
            jsonld_items.extend(data.get("@graph", []))
        elif isinstance(data, list):
            jsonld_items.extend(data)
        elif isinstance(data, dict):
            jsonld_items.append(data)

    jsonld_raw = json.dumps(jsonld_items, ensure_ascii=False) if jsonld_items else None
    return text_raw, jsonld_raw


def catalog_url_for_offset(offset):
    # Første side er uden query-string
    if offset <= 1:
        return LISTING_INDEX_URL
    return f"{LISTING_INDEX_URL}?p={offset}"


def discover_listing_urls(max_listings):
    # Crawl katalogsider med kendt pagination-mønster: p=1,13,25,37...
    listing_urls = set()
    offsets = [1 + (i * PAGINATION_STEP) for i in range(MAX_CATALOG_PAGES)]
    pages_without_new = 0

    for index, offset in enumerate(offsets, start=1):
        page_url = normalize_url(catalog_url_for_offset(offset))
        try:
            r = requests.get(page_url, headers=HEADERS, timeout=TIMEOUT)
        except requests.RequestException:
            continue
        if r.status_code != 200:
            continue

        try:
            tree = html.fromstring(r.content)
        except Exception:
            continue

        before = len(listing_urls)
        for href in tree.xpath("//a/@href"):
            href = normalize_url(href if href.startswith("http") else ("https://jensenestate.es" + href))
            parsed = urlparse(href)
            if parsed.scheme not in ("http", "https"):
                continue
            if parsed.netloc not in (SITE_HOST, f"www.{SITE_HOST}"):
                continue
            if is_listing_url(href):
                listing_urls.add(href)
                if len(listing_urls) >= max_listings:
                    print(f"Reached max listings ({max_listings}) during discovery.")
                    return sorted(list(listing_urls))[:max_listings]

        added = len(listing_urls) - before
        if added == 0:
            pages_without_new += 1
        else:
            pages_without_new = 0

        print(f"[catalog {index}/{MAX_CATALOG_PAGES}] {page_url} | +{added} | listings so far: {len(listing_urls)}")

        # Stop hvis flere sider i træk ikke giver nye listings
        if pages_without_new >= 3:
            print("Stopping discovery: 3 catalog pages in a row with no new listing links.")
            break

        time.sleep(0.2)

    print(f"Found {len(listing_urls)} listing URLs from /es/propiedades crawl.")
    return sorted(list(listing_urls))[:max_listings]


def read_max_listings():
    max_listings = DEFAULT_MAX_LISTINGS
    for arg in sys.argv[1:]:
        if arg.startswith("--max-listings="):
            try:
                max_listings = max(1, int(arg.split("=", 1)[1]))
            except ValueError:
                pass
    return max_listings


def url_exists(cur, url):
    cur.execute("SELECT id FROM raw_pages WHERE url = %s LIMIT 1", (url,))
    row = cur.fetchone()
    return row[0] if row else None


def touch_last_seen(cur, url):
    cur.execute("UPDATE raw_pages SET fetched_at = NOW() WHERE url = %s", (url,))


def insert_new_listing(cur, data):
    cur.execute(
        """
        INSERT INTO raw_pages (
            url, domain, first_seen_at, fetched_at,
            http_status, content_type, html_raw, text_raw, jsonld_raw
        )
        VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)
        """,
        (
            data["url"],
            data["domain"],
            data["first_seen_at"],
            data["fetched_at"],
            data["http_status"],
            data["content_type"],
            data["html_raw"],
            data["text_raw"],
            data["jsonld_raw"],
        ),
    )


# === MAIN ===

def run():
    max_listings = read_max_listings()
    conn = mysql.connector.connect(**DB_CONFIG)
    cur = conn.cursor()

    try:
        listing_urls = discover_listing_urls(max_listings)
        if not listing_urls:
            print("No listing URLs found.")
            return

        print(f"Found {len(listing_urls)} listing URLs (max {max_listings})")

        new_count = 0
        seen_count = 0

        for url in listing_urls:
            existing_id = url_exists(cur, url)
            if existing_id:
                touch_last_seen(cur, url)
                conn.commit()
                seen_count += 1
                print(f"SEEN (updated last seen): {url}")
                continue

            print(f"NEW (fetching): {url}")
            try:
                response = requests.get(url, headers=HEADERS, timeout=TIMEOUT)
            except requests.RequestException:
                print(f"FAILED fetch: {url}")
                continue

            text_raw, jsonld_raw = extract_text_and_jsonld(response.content)
            parsed = urlparse(url)
            now = datetime.now()

            data = {
                "url": url,
                "domain": parsed.netloc,
                "first_seen_at": now,
                "fetched_at": now,
                "http_status": response.status_code,
                "content_type": response.headers.get("Content-Type", ""),
                "html_raw": response.text,
                "text_raw": text_raw,
                "jsonld_raw": jsonld_raw,
            }

            insert_new_listing(cur, data)
            conn.commit()
            new_count += 1
            print(f"INSERTED: {url}")
            time.sleep(REQUEST_DELAY)

        print(f"Done. New: {new_count}, Already seen: {seen_count}")
    finally:
        cur.close()
        conn.close()


if __name__ == "__main__":
    run()
