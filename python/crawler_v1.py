import requests
import mysql.connector
import time
import re
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

def clean_int(text):
    """
    Bruges KUN når teksten forventes at indeholde ÉT tal
    fx: '198.800 €' → 198800
    """
    if not text:
        return None
    digits = re.sub(r"[^\d]", "", text)
    return int(digits) if digits else None


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


# === DATA EXTRACTION ===

def extract_basic_fields(page_url, response):
    tree = html.fromstring(response.content)

    # TITLE (simpel og sikker)
    title = tree.xpath("string(//title)").strip() or None

    # META DESCRIPTION (case-insensitive)
    description = tree.xpath(
        "string(//meta[translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='description']/@content)"
    ).strip() or None

    # PRICE
    # Vi tager KUN første tekst der indeholder €
    price_text = tree.xpath(
        "string(//*[contains(text(),'€')][1])"
    ).strip()
    price = clean_int(price_text)  # her er clean_int OK

    # SQM
    # XPath finder første tekst med m²
    sqm_text = tree.xpath(
        "string(//*[contains(text(),'m²')][1])"
    ).strip()
    sqm = extract_sqm(sqm_text)  # VIGTIGT: IKKE clean_int

    # ROOMS
    # Stadig simpelt – kan raffineres senere
    rooms_text = tree.xpath(
        "string(//*[contains(text(),'hab') or contains(text(),'bed')][1])"
    ).strip()
    rooms = clean_int(rooms_text)

    # AREA / LOCATION (ofte tekst, ikke tal)
    area_text = tree.xpath(
        "string(//*[contains(@class,'location') or contains(@class,'area')])"
    ).strip() or None

    parsed = urlparse(page_url)

    return {
        "url": page_url,
        "domain": parsed.netloc,
        "source_type": "agent",

        "title": title,
        "description": description,
        "price": price,
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
                sqm = %s,
                rooms = %s,
                area_text = %s,
                last_seen_at = %s
            WHERE url = %s
        """, (
            data["title"],
            data["description"],
            data["price"],
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
                price, sqm, rooms, area_text,
                first_seen_at, last_seen_at
            )
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
        """, (
            data["url"],
            data["domain"],
            data["source_type"],
            data["title"],
            data["description"],
            data["price"],
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
