import requests
import mysql.connector
import time
import re
import json
from urllib.parse import urlparse
from lxml import html
from datetime import datetime

# === CONFIG ===

# Vi crawler stadig kun ÉN konkret listing-side
START_URLS = [
    "https://mediter.com/es/propiedad/2939/apartment/reventa/espana/alicante/guardamar-del-segura/urbanizaciones/"
]

DB_CONFIG = {
    "host": "localhost",
    "user": "firstlisting_admin",
    "password": "zf4B84BHrlW9jvl4",
    "database": "firstlisting_v1",
    "charset": "utf8mb4",
    "use_unicode": True
}

HEADERS = {
    "User-Agent": "FirstListingBot/1.0 (+analysis; no crawling abuse)"
}

REQUEST_DELAY = 3  # pause så vi ikke belaster serveren


# === HELPERS ===

def normalize_number(text):
    """
    Finder første tal i en tekst og normaliserer tusindtals-separatorer.
    Returnerer int eller None.
    """
    if not text:
        return None
    match = re.search(r'(\d[\d\.,]*)', text)
    if not match:
        return None
    raw = match.group(1)

    if '.' in raw and ',' in raw:
        raw = raw.replace('.', '').replace(',', '')
        return int(raw) if raw.isdigit() else None

    if ',' in raw and '.' not in raw:
        parts = raw.split(',')
        if len(parts[-1]) in (1, 2):
            try:
                return int(float(raw.replace(',', '.')))
            except ValueError:
                return None
        raw = raw.replace(',', '')
        return int(raw) if raw.isdigit() else None

    if '.' in raw and ',' not in raw:
        parts = raw.split('.')
        if len(parts[-1]) in (1, 2):
            try:
                return int(float(raw))
            except ValueError:
                return None
        raw = raw.replace('.', '')
        return int(raw) if raw.isdigit() else None

    return int(raw) if raw.isdigit() else None


def extract_sqm(text):
    """
    Bruges specifikt til m².
    Finder KUN tallet der hører sammen med 'm²'
    fx: '240 m² parcela 120 m² vivienda' → 240
    """
    if not text:
        return None
    match = re.search(r'(\d+)\s*m²', text)
    return int(match.group(1)) if match else None


def to_int(value):
    if value is None:
        return None
    if isinstance(value, (int, float)):
        return int(value)
    return normalize_number(str(value))


def find_first_text(tree, xpaths):
    for xp in xpaths:
        text = tree.xpath(f"string({xp})").strip()
        if text:
            return text
    return None


def extract_jsonld(tree):
    scripts = tree.xpath("//script[@type='application/ld+json']/text()")
    items = []
    for script in scripts:
        try:
            data = json.loads(script)
        except json.JSONDecodeError:
            continue

        if isinstance(data, dict) and "@graph" in data:
            items.extend(data.get("@graph", []))
        elif isinstance(data, list):
            items.extend(data)
        elif isinstance(data, dict):
            items.append(data)

    for item in items:
        if not isinstance(item, dict):
            continue

        offers = item.get("offers")
        if isinstance(offers, list) and offers:
            offers = offers[0]

        price = None
        currency = None
        if isinstance(offers, dict):
            price = offers.get("price") or offers.get("lowPrice")
            currency = offers.get("priceCurrency")
            if price is None and isinstance(offers.get("priceSpecification"), dict):
                price = offers["priceSpecification"].get("price")

        floor_size = item.get("floorSize") or item.get("floorArea") or item.get("area")
        sqm = None
        if isinstance(floor_size, dict):
            sqm = floor_size.get("value")
        elif floor_size is not None:
            sqm = floor_size

        rooms = item.get("numberOfRooms")

        address = item.get("address")
        area_text = None
        if isinstance(address, dict):
            locality = address.get("addressLocality")
            region = address.get("addressRegion")
            if locality or region:
                area_text = " ".join([part for part in [locality, region] if part])
        elif isinstance(address, str):
            area_text = address

        return {
            "title": item.get("name"),
            "description": item.get("description"),
            "price": to_int(price),
            "currency": currency,
            "sqm": to_int(sqm),
            "rooms": to_int(rooms),
            "area_text": area_text
        }

    return {}


def extract_meta(tree):
    title = find_first_text(tree, [
        "//meta[@property='og:title']/@content",
        "//meta[@name='title']/@content",
        "//title"
    ])

    description = find_first_text(tree, [
        "//meta[@property='og:description']/@content",
        "//meta[translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='description']/@content"
    ])

    price_text = find_first_text(tree, [
        "//meta[@property='product:price:amount']/@content",
        "//meta[@property='og:price:amount']/@content",
        "//meta[@itemprop='price']/@content"
    ])

    currency = find_first_text(tree, [
        "//meta[@property='product:price:currency']/@content",
        "//meta[@property='og:price:currency']/@content",
        "//meta[@itemprop='priceCurrency']/@content"
    ])

    return {
        "title": title,
        "description": description,
        "price": to_int(price_text),
        "currency": currency
    }


def extract_fallback(tree):
    price_text = find_first_text(tree, [
        "//*[contains(translate(@class,'PRICE','price'),'price') or contains(translate(@id,'PRICE','price'),'price')][contains(.,'€')]",
        "//*[contains(text(),'€')][1]"
    ])

    sqm_text = find_first_text(tree, [
        "//*[contains(translate(@class,'AREA','area'),'area') or contains(translate(@class,'SUPERFICIE','superficie'),'superficie') or contains(translate(@class,'SQM','sqm'),'sqm')][contains(.,'m²') or contains(.,'m2')]",
        "//*[contains(text(),'m²') or contains(text(),'m2')][1]"
    ])

    rooms_text = find_first_text(tree, [
        "//*[contains(translate(@class,'BED','bed'),'bed') or contains(translate(@class,'HAB','hab'),'hab') or contains(translate(@class,'ROOM','room'),'room')][1]",
        "//*[contains(translate(text(),'BEDROOM','bedroom'),'bed') or contains(translate(text(),'HABITACIONES','habitaciones'),'hab') or contains(translate(text(),'ROOMS','rooms'),'room')][1]"
    ])

    area_text = find_first_text(tree, [
        "//*[contains(translate(@class,'LOCATION','location'),'location') or contains(translate(@class,'AREA','area'),'area') or contains(translate(@class,'ADDRESS','address'),'address')]",
        "//*[contains(translate(@id,'LOCATION','location'),'location') or contains(translate(@id,'AREA','area'),'area') or contains(translate(@id,'ADDRESS','address'),'address')]"
    ])

    return {
        "price": to_int(price_text),
        "sqm": extract_sqm(sqm_text) if sqm_text else None,
        "rooms": to_int(rooms_text),
        "area_text": area_text
    }


# === DATA EXTRACTION ===

def extract_basic_fields(page_url, response):
    tree = html.fromstring(response.content)

    jsonld = extract_jsonld(tree)
    meta = extract_meta(tree)
    fallback = extract_fallback(tree)

    title = jsonld.get("title") or meta.get("title")
    description = jsonld.get("description") or meta.get("description")

    price = jsonld.get("price") or meta.get("price") or fallback.get("price")
    currency = jsonld.get("currency") or meta.get("currency") or "EUR"

    sqm = jsonld.get("sqm") or fallback.get("sqm")
    rooms = jsonld.get("rooms") or fallback.get("rooms")
    area_text = jsonld.get("area_text") or fallback.get("area_text")

    parsed = urlparse(page_url)

    return {
        "url": page_url,
        "domain": parsed.netloc,
        "source_type": "agent",

        "title": title,
        "description": description,
        "price": price,
        "currency": currency,
        "sqm": sqm,
        "rooms": rooms,
        "area_text": area_text
    }


# === DATABASE UPSERT ===

def upsert_listing(data, conn):
    cur = conn.cursor(dictionary=True)

    # Samme URL = samme annonce
    cur.execute("SELECT id FROM listings WHERE url = %s", (data["url"],))
    existing = cur.fetchone()

    now = datetime.now()

    if existing:
        # Eksisterende annonce → opdater
        cur.execute("""
            UPDATE listings SET
                title = %s,
                description = %s,
                price = %s,
                currency = %s,
                sqm = %s,
                rooms = %s,
                area_text = %s,
                last_seen_at = %s
            WHERE url = %s
        """, (
            data["title"],
            data["description"],
            data["price"],
            data["currency"],
            data["sqm"],
            data["rooms"],
            data["area_text"],
            now,
            data["url"]
        ))
        print("UPDATED:", data["url"])
    else:
        # Ny annonce → indsæt
        cur.execute("""
            INSERT INTO listings (
                url, domain, source_type,
                title, description,
                price, currency, sqm, rooms, area_text,
                first_seen_at, last_seen_at
            )
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
        """, (
            data["url"],
            data["domain"],
            data["source_type"],
            data["title"],
            data["description"],
            data["price"],
            data["currency"],
            data["sqm"],
            data["rooms"],
            data["area_text"],
            now,
            now
        ))
        print("INSERTED:", data["url"])

    conn.commit()
    cur.close()


# === MAIN ===

def run():
    conn = mysql.connector.connect(**DB_CONFIG)

    try:
        for url in START_URLS:
            print("Fetching:", url)
            time.sleep(REQUEST_DELAY)

            response = requests.get(url, headers=HEADERS, timeout=10)
            response.raise_for_status()

            data = extract_basic_fields(url, response)
            upsert_listing(data, conn)

    finally:
        conn.close()


if __name__ == "__main__":
    run()
