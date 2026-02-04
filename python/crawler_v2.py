import requests
import mysql.connector
import time
import re
import json
from urllib.parse import urlparse
from lxml import html
from datetime import datetime

# === CONFIG ===

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
    "User-Agent": "FirstListingBot/2.0 (+analysis; no crawling abuse)"
}

REQUEST_DELAY = 3


# === HELPERS ===

def normalize_number(text):
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


def to_int(value):
    if value is None:
        return None
    if isinstance(value, (int, float)):
        return int(value)
    return normalize_number(str(value))


def valid_price(value):
    if value is None:
        return None
    if value < 1000 or value > 100000000:
        return None
    return value


def valid_sqm(value):
    if value is None:
        return None
    if value < 10 or value > 2000:
        return None
    return value


def valid_rooms(value):
    if value is None:
        return None
    if value < 0 or value > 20:
        return None
    return value


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
            "price": valid_price(to_int(price)),
            "currency": currency,
            "sqm": valid_sqm(to_int(sqm)),
            "rooms": valid_rooms(to_int(rooms)),
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
        "price": valid_price(to_int(price_text)),
        "currency": currency
    }


def pick_best_number(candidates, validator):
    for text in candidates:
        value = validator(to_int(text))
        if value is not None:
            return value
    return None


def extract_labeled_numbers(tree):
    price_keywords = [
        "price", "precio", "prix", "preis", "cost", "€", "eur"
    ]
    sqm_keywords = [
        "sqm", "m²", "m2", "superficie", "superfície", "area", "surface"
    ]
    rooms_keywords = [
        "rooms", "room", "bedroom", "bedrooms", "beds", "dormitorio",
        "dormitorios", "habitacion", "habitaciones", "hab", "cuartos"
    ]
    parking_keywords = [
        "parking", "garage", "garaje", "carport"
    ]

    candidates = {"price": [], "sqm": [], "rooms": []}

    elements = tree.xpath("//*[normalize-space(text())]")
    for el in elements:
        text = " ".join(el.itertext()).strip()
        if not text:
            continue
        lower = text.lower()

        has_number = re.search(r"\\d", text) is not None

        if any(k in lower for k in price_keywords):
            if has_number:
                candidates["price"].append(text)
            else:
                nxt = el.getnext()
                if nxt is not None:
                    nxt_text = " ".join(nxt.itertext()).strip()
                    if re.search(r"\\d", nxt_text):
                        candidates["price"].append(nxt_text)

        if any(k in lower for k in sqm_keywords):
            if has_number:
                candidates["sqm"].append(text)
            else:
                nxt = el.getnext()
                if nxt is not None:
                    nxt_text = " ".join(nxt.itertext()).strip()
                    if re.search(r"\\d", nxt_text):
                        candidates["sqm"].append(nxt_text)

        if any(k in lower for k in rooms_keywords) and not any(k in lower for k in parking_keywords):
            if has_number:
                candidates["rooms"].append(text)
            else:
                nxt = el.getnext()
                if nxt is not None:
                    nxt_text = " ".join(nxt.itertext()).strip()
                    if re.search(r"\\d", nxt_text):
                        candidates["rooms"].append(nxt_text)

    return {
        "price": pick_best_number(candidates["price"], valid_price),
        "sqm": pick_best_number(candidates["sqm"], valid_sqm),
        "rooms": pick_best_number(candidates["rooms"], valid_rooms)
    }


def extract_fallback(tree):
    labeled = extract_labeled_numbers(tree)

    price_candidates = []
    price_candidates.extend(tree.xpath(
        "//*[contains(translate(@class,'PRICE','price'),'price') or "
        "contains(translate(@id,'PRICE','price'),'price') or "
        "contains(translate(@class,'COST','cost'),'cost') or "
        "contains(translate(@class,'AMOUNT','amount'),'amount')][contains(.,'€') or contains(.,'EUR') or contains(.,'$')]/text()"
    ))

    price_candidates.extend(tree.xpath(
        "//*[contains(translate(text(),'PRICE','price'),'price') or "
        "contains(translate(text(),'PRECIO','precio'),'precio')]/following::*[contains(.,'€') or contains(.,'EUR') or contains(.,'$')][1]/text()"
    ))

    sqm_candidates = []
    sqm_candidates.extend(tree.xpath(
        "//*[contains(translate(@class,'SQM','sqm'),'sqm') or "
        "contains(translate(@class,'M2','m2'),'m2') or "
        "contains(translate(@class,'AREA','area'),'area') or "
        "contains(translate(@class,'SUPERFICIE','superficie'),'superficie')][contains(.,'m²') or contains(.,'m2')]/text()"
    ))

    sqm_candidates.extend(tree.xpath(
        "//*[contains(translate(text(),'SUPERFICIE','superficie'),'superficie') or "
        "contains(translate(text(),'AREA','area'),'area')]/following::*[contains(.,'m²') or contains(.,'m2')][1]/text()"
    ))

    rooms_candidates = []
    rooms_candidates.extend(tree.xpath(
        "//*[contains(translate(@class,'BED','bed'),'bed') or "
        "contains(translate(@class,'HAB','hab'),'hab') or "
        "contains(translate(@class,'ROOM','room'),'room') or "
        "contains(translate(@class,'DORM','dorm'),'dorm')]/text()"
    ))

    rooms_candidates.extend(tree.xpath(
        "//*[contains(translate(text(),'DORMITOR','dormitor'),'dormitor') or "
        "contains(translate(text(),'HABITAC','habitac'),'habitac') or "
        "contains(translate(text(),'BEDROOM','bedroom'),'bedroom')]/following::*[1]/text()"
    ))

    area_text = find_first_text(tree, [
        "//*[contains(translate(@class,'LOCATION','location'),'location') or "
        "contains(translate(@class,'AREA','area'),'area') or "
        "contains(translate(@class,'ADDRESS','address'),'address')]",
        "//*[contains(translate(@id,'LOCATION','location'),'location') or "
        "contains(translate(@id,'AREA','area'),'area') or "
        "contains(translate(@id,'ADDRESS','address'),'address')]"
    ])

    return {
        "price": labeled.get("price") or pick_best_number(price_candidates, valid_price),
        "sqm": labeled.get("sqm") or pick_best_number(sqm_candidates, valid_sqm),
        "rooms": labeled.get("rooms") or pick_best_number(rooms_candidates, valid_rooms),
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

    cur.execute("SELECT id FROM listings WHERE url = %s", (data["url"],))
    existing = cur.fetchone()

    now = datetime.now()

    if existing:
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
