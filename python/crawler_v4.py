import json
import re
import time
from datetime import datetime
from urllib.parse import urlparse

import mysql.connector
import requests
from lxml import html

# === CONFIG ===

# Replace with your target URLs
START_URLS = [
    "https://jensenestate.es/es/propiedad/1015/adosado-en-san-pedro-del-pinatar/"
]

DB_CONFIG = {
    "host": "localhost",
    "user": "firstlisting_user",
    "password": "girafferharlangehalse",
    "database": "test2firstlisting",
    "charset": "utf8mb4",
    "use_unicode": True,
}

HEADERS = {
    "User-Agent": "FirstListingBot/4.0 (+school MVP; respectful crawl)"
}

REQUEST_DELAY = 3


# === HELPERS ===

def clean_text(text):
    if not text:
        return ""
    return re.sub(r"\s+", " ", text).strip()


def extract_text_and_jsonld(html_bytes):
    tree = html.fromstring(html_bytes)

    # text_raw
    text_raw = clean_text(" ".join(tree.itertext()))

    # jsonld_raw: store as JSON list of objects
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


def upsert_raw_page(cur, data):
    cur.execute(
        """
        INSERT INTO raw_pages (
            url, domain, first_seen_at, fetched_at,
            http_status, content_type, html_raw, text_raw, jsonld_raw
        )
        VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)
        ON DUPLICATE KEY UPDATE
            domain = VALUES(domain),
            first_seen_at = IF(first_seen_at IS NULL, VALUES(fetched_at), first_seen_at),
            fetched_at = VALUES(fetched_at),
            http_status = VALUES(http_status),
            content_type = VALUES(content_type),
            html_raw = VALUES(html_raw),
            text_raw = VALUES(text_raw),
            jsonld_raw = VALUES(jsonld_raw)
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
    conn = mysql.connector.connect(**DB_CONFIG)
    cur = conn.cursor()

    try:
        for url in START_URLS:
            print("Fetching:", url)
            time.sleep(REQUEST_DELAY)

            response = requests.get(url, headers=HEADERS, timeout=15)
            status = response.status_code
            content_type = response.headers.get("Content-Type", "")

            text_raw, jsonld_raw = extract_text_and_jsonld(response.content)

            parsed = urlparse(url)
            data = {
                "url": url,
                "domain": parsed.netloc,
                "first_seen_at": datetime.now(),
                "fetched_at": datetime.now(),
                "http_status": status,
                "content_type": content_type,
                "html_raw": response.text,
                "text_raw": text_raw,
                "jsonld_raw": jsonld_raw,
            }

            upsert_raw_page(cur, data)
            conn.commit()
            print("UPSERTED:", url)
    finally:
        cur.close()
        conn.close()


if __name__ == "__main__":
    run()
