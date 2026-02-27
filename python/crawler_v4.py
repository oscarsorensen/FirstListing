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


#  gonna move database pw to more safe location later.
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


# We clean text mainly to reduce token count when sending to AI later.
def clean_text(text):
    if not text:
        return ""
    return re.sub(r"\s+", " ", text).strip()

# Checks that we are actually on a property page, not a catalog page with multiple listings.
def is_listing_url(url):
    return re.match(r"^https://jensenestate\.es/es/propiedad/\d+(?:/[^?#]*)?$", url) is not None


# Makes the URL consistent (removes # fragments and trailing slashes) so it's easier to store and compare.
def normalize_url(url):
    url = urldefrag(url)[0].strip()
    if url.endswith("/") and len(url) > len("https://jensenestate.es/"):
        url = url[:-1]
    return url

# Parses raw HTML and extracts two things:
# 1. Plain text — all visible text on the page joined into one string
# 2. JSON-LD — structured data embedded by the site for search engines, often contains property details
def extract_text_and_jsonld(html_bytes):
    tree = html.fromstring(html_bytes)
    text_raw = clean_text(" ".join(tree.itertext())) #calling function

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


# Discovers all listing URLs from the site's XML sitemap.
# The sitemap is published by the site daily and lists all properties.
# Steps: fetch the sitemap index → download each sitemap file → return URLs that match the listing pattern.
def discover_listing_urls(max_listings):
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
            url = normalize_url(loc.text.strip()) #calling function
            if is_listing_url(url): #calling function
                listing_urls.append(url)
                if len(listing_urls) >= max_listings:
                    print(f"Reached max listings ({max_listings}).")
                    return listing_urls

    print(f"Discovery done. Found {len(listing_urls)} listing URLs.")
    return listing_urls


# Reads the --max-listings=N argument from the command line, so we can limit how many listings to crawl.
#This is just for me to have a count of how many listings there are. Only relevant for the presentation,
#becuase generally i will be crawling all listings.
def read_max_listings():
    for arg in sys.argv[1:]:
        if arg.startswith("--max-listings="):
            try:
                return max(1, int(arg.split("=", 1)[1]))
            except ValueError:
                pass
    return DEFAULT_MAX_LISTINGS


# Reads the --url=https://... argument from the command line.
# When set, the crawler skips sitemap discovery and just fetches that one URL.
# Returns the URL string, or None if the argument was not provided.
def read_single_url():
    for arg in sys.argv[1:]:
        if arg.startswith("--url="):
            return arg.split("=", 1)[1].strip()
    return None


# === DATABASE ===

# Checks if a URL already exists in the database. Returns the row ID if found, so we can update the timestamp instead of inserting a duplicate.
def url_exists(cur, url):
    cur.execute("SELECT id FROM raw_pages WHERE url = %s LIMIT 1", (url,))
    row = cur.fetchone()
    return row[0] if row else None

# Updates the fetched_at timestamp for a listing we've already seen (so we know when it was last checked).
def touch_last_seen(cur, url):
    cur.execute("UPDATE raw_pages SET fetched_at = NOW() WHERE url = %s", (url,))


# Inserts a new listing into the raw_pages table (only called when the URL is not already in the database).
def insert_listing(cur, url, domain, http_status, content_type, html_raw, text_raw, jsonld_raw):
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
    # Single-URL mode: crawl one specific page instead of the full sitemap.
    # Called from user.php when a user pastes a URL into the search form.
    # Prints "RAW_PAGE_ID:N" to stdout so PHP can capture the database row ID.
    single_url = read_single_url()
    if single_url:
        url = normalize_url(single_url)
        conn = mysql.connector.connect(**DB_CONFIG)
        cur = conn.cursor()
        try:
            # If we already have this URL in the database, just update the timestamp
            existing_id = url_exists(cur, url)
            if existing_id:
                touch_last_seen(cur, url)
                conn.commit()
                print(f"[SEEN]      {url}")
                # PHP reads this line to get the row ID
                print(f"RAW_PAGE_ID:{existing_id}")
                return

            # Fetch the page
            print(f"[FETCHING]  {url}")
            try:
                response = requests.get(url, headers=HEADERS, timeout=TIMEOUT)
            except requests.RequestException as e:
                print(f"[FAILED]    {url}: {e}", file=sys.stderr)
                sys.exit(1)

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
                # cur.lastrowid gives the auto-increment ID of the row we just inserted
                new_id = cur.lastrowid
                print(f"[INSERTED]  {url}")
                print(f"RAW_PAGE_ID:{new_id}")
            except mysql.connector.IntegrityError:
                # Race condition: URL was inserted between our check and insert.
                # Roll back and try to find the existing ID.
                conn.rollback()
                cur.execute("SELECT id FROM raw_pages WHERE url = %s LIMIT 1", (url,))
                row = cur.fetchone()
                if row:
                    print(f"[SKIPPED]   {url} (duplicate in DB)")
                    print(f"RAW_PAGE_ID:{row[0]}")
                else:
                    print(f"[FAILED]    {url} (integrity error, no ID found)", file=sys.stderr)
                    sys.exit(1)
        finally:
            cur.close()
            conn.close()
        return

    # Normal sitemap mode (unchanged from before)
    max_listings = read_max_listings()
    conn = mysql.connector.connect(**DB_CONFIG)
    cur = conn.cursor()

    try:
        listing_urls = discover_listing_urls(max_listings) #calling function (s)
        if not listing_urls:
            print("No listing URLs found.")
            return

        print(f"\nStarting crawl: {len(listing_urls)} URLs (max {max_listings})\n")

        new_count = 0
        seen_count = 0

        for url in listing_urls:
            # Skip if already in the database, just update the timestamp
            if url_exists(cur, url): #calling function
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

            try: #calling function
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
                # This might not be the best fix, but it prevents the crawler from crashing on duplicates. Yes i had problems with this.
                conn.rollback()
                print(f"[SKIPPED]  {url} (duplicate in DB)")

            time.sleep(REQUEST_DELAY)

        print(f"\nDone. New: {new_count} | Already seen: {seen_count}")

    finally:
        cur.close()
        conn.close()


if __name__ == "__main__":
    run()
