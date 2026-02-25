import gzip
import json
import re
import sys
import time
from datetime import datetime
from urllib.parse import urlparse, urldefrag
from xml.etree import ElementTree

import mysql.connector
import requests
from lxml import html

# === CONFIG ===

SITE_HOST = "jensenestate.es"
SITEMAP_INDEX_URL = "https://jensenestate.es/sitemap-index.xml"

DB_CONFIG = {
    "host": "localhost",
    "user": "firstlisting_user",
    "password": "girafferharlangehalse",
    "database": "test2firstlisting",
    "charset": "utf8mb4",
    "use_unicode": True,
}

HEADERS = {"User-Agent": "FirstListingBot/4.0 (+school MVP; allowed by Gitte, respectful crawl)"}
REQUEST_DELAY = 1.5  # Seconds to wait between fetching listing pages
TIMEOUT = 15         # Max seconds to wait for a response
DEFAULT_MAX_LISTINGS = 50


# === HELPERS ===

def clean_text(text):
    """Remove extra whitespace from a string."""
    if not text:
        return ""
    return re.sub(r"\s+", " ", text).strip()


def is_listing_url(url):
    """Return True if the URL is a single property listing (not a catalog page)."""
    return re.match(r"^https://jensenestate\.es/es/propiedad/\d+(?:/[^?#]*)?$", url) is not None


def normalize_url(url):
    """Remove URL fragments (#) and trailing slashes."""
    url = urldefrag(url)[0].strip()
    if url.endswith("/") and len(url) > len("https://jensenestate.es/"):
        url = url[:-1]
    return url


def extract_text_and_jsonld(html_bytes):
    """
    Parse raw HTML and extract:
    - Plain text (all visible text joined into one string)
    - JSON-LD structured data (used by search engines, often contains property details)
    """
    tree = html.fromstring(html_bytes)
    text_raw = clean_text(" ".join(tree.itertext()))

    jsonld_items = []
    for script in tree.xpath("//script[@type='application/ld+json']/text()"):
        try:
            data = json.loads(script)
        except json.JSONDecodeError:
            continue
        if isinstance(data, dict) and "@graph" in data:
            jsonld_items.extend(data["@graph"])
        elif isinstance(data, list):
            jsonld_items.extend(data)
        elif isinstance(data, dict):
            jsonld_items.append(data)

    jsonld_raw = json.dumps(jsonld_items, ensure_ascii=False) if jsonld_items else None
    return text_raw, jsonld_raw


def discover_listing_urls(max_listings):
    """
    Discover all listing URLs from the site's XML sitemap.
    The sitemap is published by the site daily and lists all properties.

    Steps:
    1. Fetch the sitemap index to find the actual sitemap file(s)
    2. Download and parse each sitemap file
    3. Return all URLs that match the listing URL pattern
    """
    # Step 1: fetch the sitemap index
    print(f"Fetching sitemap index: {SITEMAP_INDEX_URL}")
    response = requests.get(SITEMAP_INDEX_URL, headers=HEADERS, timeout=TIMEOUT)
    response.raise_for_status()

    ns = {"sm": "http://www.sitemaps.org/schemas/sitemap/0.9"}
    index = ElementTree.fromstring(response.content)
    sitemap_urls = [loc.text for loc in index.findall(".//sm:loc", ns)]

    if not sitemap_urls:
        print("No sitemaps found in index.")
        return []

    # Step 2: download and parse each sitemap file
    listing_urls = []
    for sitemap_url in sitemap_urls:
        print(f"Fetching sitemap: {sitemap_url}")
        r = requests.get(sitemap_url, headers=HEADERS, timeout=TIMEOUT)
        r.raise_for_status()

        # Decompress if the file is gzipped (.gz)
        content = gzip.decompress(r.content) if sitemap_url.endswith(".gz") else r.content

        sitemap = ElementTree.fromstring(content)
        for loc in sitemap.findall(".//sm:loc", ns):
            url = normalize_url(loc.text.strip())
            if is_listing_url(url):
                listing_urls.append(url)
                if len(listing_urls) >= max_listings:
                    print(f"Reached max listings ({max_listings}).")
                    return listing_urls

    print(f"Discovery done. Found {len(listing_urls)} listing URLs.")
    return listing_urls


def read_max_listings():
    """Read --max-listings=N from command line arguments."""
    for arg in sys.argv[1:]:
        if arg.startswith("--max-listings="):
            try:
                return max(1, int(arg.split("=", 1)[1]))
            except ValueError:
                pass
    return DEFAULT_MAX_LISTINGS


# === DATABASE ===

def url_exists(cur, url):
    """Return the existing row ID if this URL is already in the database."""
    cur.execute("SELECT id FROM raw_pages WHERE url = %s LIMIT 1", (url,))
    row = cur.fetchone()
    return row[0] if row else None


def touch_last_seen(cur, url):
    """Update the fetched_at timestamp for an existing listing."""
    cur.execute("UPDATE raw_pages SET fetched_at = NOW() WHERE url = %s", (url,))


def insert_listing(cur, url, domain, http_status, content_type, html_raw, text_raw, jsonld_raw):
    """Insert a new listing into raw_pages."""
    now = datetime.now()
    cur.execute(
        """
        INSERT INTO raw_pages (
            url, domain, first_seen_at, fetched_at,
            http_status, content_type, html_raw, text_raw, jsonld_raw
        )
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
        """,
        (url, domain, now, now, http_status, content_type, html_raw, text_raw, jsonld_raw),
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

        print(f"\nStarting crawl: {len(listing_urls)} URLs (max {max_listings})\n")

        new_count = 0
        seen_count = 0

        for url in listing_urls:
            # Skip if already in the database, just update the timestamp
            if url_exists(cur, url):
                touch_last_seen(cur, url)
                conn.commit()
                seen_count += 1
                print(f"[SEEN]     {url}")
                continue

            # Fetch the listing page
            print(f"[FETCHING] {url}")
            try:
                response = requests.get(url, headers=HEADERS, timeout=TIMEOUT)
            except requests.RequestException:
                print(f"[FAILED]   {url}")
                continue

            text_raw, jsonld_raw = extract_text_and_jsonld(response.content)

            try:
                insert_listing(
                    cur,
                    url=url,
                    domain=urlparse(url).netloc,
                    http_status=response.status_code,
                    content_type=response.headers.get("Content-Type", ""),
                    html_raw=response.text,
                    text_raw=text_raw,
                    jsonld_raw=jsonld_raw,
                )
                conn.commit()
                new_count += 1
                print(f"[INSERTED] {url}")
            except mysql.connector.IntegrityError:
                # The URL already exists in the database (duplicate).
                # This can happen if the URL was truncated in the DB and
                # url_exists() didn't catch it. We just skip it and move on.
                conn.rollback()
                print(f"[SKIPPED]  {url} (duplicate in DB)")

            time.sleep(REQUEST_DELAY)

        print(f"\nDone. New: {new_count} | Already seen: {seen_count}")

    finally:
        cur.close()
        conn.close()


if __name__ == "__main__":
    run()
