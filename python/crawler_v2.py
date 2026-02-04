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


def has_currency(text):
    if not text:
        return False
    lower = text.lower()
    return "€" in text or "eur" in lower or "euro" in lower or "$" in text


def looks_like_phone(text):
    if not text:
        return False
    digits = re.sub(r"\\D", "", text)
    return 8 <= len(digits) <= 15


def text_from_node(node):
    return " ".join(node.itertext()).strip() if node is not None else ""


def node_context_text(node):
    if node is None:
        return ""
    parts = [text_from_node(node)]
    parent = node.getparent()
    if parent is not None:
        parts.append(text_from_node(parent))
    nxt = node.getnext()
    if nxt is not None:
        parts.append(text_from_node(nxt))
    prev = node.getprevious()
    if prev is not None:
        parts.append(text_from_node(prev))
    return " ".join([p for p in parts if p])


def normalize_label(text):
    if not text:
        return ""
    lower = text.lower()
    lower = re.sub(r"[^a-z0-9\\sáéíóúüñç]", " ", lower)
    lower = re.sub(r"\\s+", " ", lower).strip()
    return lower


def clean_text(text):
    if not text:
        return ""
    text = re.sub(r"\\s+", " ", text).strip()
    return text


def description_quality(text):
    if not text:
        return 0.0
    lower = text.lower()
    words = re.findall(r"[a-záéíóúüñç]+", lower)
    if len(words) < 20:
        return 0.0

    ui_words = {
        "resumen", "descripcion", "descripción", "imagenes", "imágenes",
        "localizacion", "localización", "economia", "economía", "mapa",
        "galeria", "galería", "caracteristicas", "características", "video",
        "contacto", "contact", "agent", "agente", "precio", "price",
        "detalles", "details", "overview", "summary"
    }
    ui_hits = sum(1 for w in words if w in ui_words)
    ui_ratio = ui_hits / max(len(words), 1)

    sentence_hits = text.count(".") + text.count("!") + text.count("?")
    sentence_ratio = sentence_hits / max(len(words), 1)

    unique_ratio = len(set(words)) / max(len(words), 1)

    score = 1.0
    if ui_ratio > 0.2:
        score -= 0.6
    if sentence_ratio < 0.02:
        score -= 0.3
    if unique_ratio < 0.2:
        score -= 0.2

    return max(0.0, score)


def extract_long_description(tree):
    candidates = []
    nodes = tree.xpath(
        "//*[contains(translate(@class,'DESCRIPTION','description'),'description') or "
        "contains(translate(@class,'DESC','desc'),'desc') or "
        "contains(translate(@class,'DETAIL','detail'),'detail') or "
        "contains(translate(@class,'SUMMARY','summary'),'summary') or "
        "contains(translate(@class,'OVERVIEW','overview'),'overview') or "
        "contains(translate(@id,'DESCRIPTION','description'),'description') or "
        "contains(translate(@id,'DESC','desc'),'desc') or "
        "contains(translate(@id,'DETAIL','detail'),'detail') or "
        "contains(translate(@id,'SUMMARY','summary'),'summary') or "
        "contains(translate(@id,'OVERVIEW','overview'),'overview')]"
    )

    for node in nodes:
        text = clean_text(" ".join(node.itertext()))
        if len(text) < 80:
            continue
        if len(text) > 8000:
            continue
        if description_quality(text) <= 0.2:
            continue
        candidates.append(text)

    if not candidates:
        return None

    candidates.sort(key=len, reverse=True)
    return candidates[0]


def extract_key_value_pairs(tree):
    pairs = []

    for dt in tree.xpath("//dt"):
        label = " ".join(dt.itertext()).strip()
        dd = dt.getnext()
        if dd is not None and dd.tag.lower() == "dd":
            value = " ".join(dd.itertext()).strip()
            if label and value:
                pairs.append((label, value))

    for row in tree.xpath("//tr"):
        th = row.xpath("./th[1]")
        td = row.xpath("./td[1]")
        if th and td:
            label = " ".join(th[0].itertext()).strip()
            value = " ".join(td[0].itertext()).strip()
            if label and value:
                pairs.append((label, value))

    for li in tree.xpath("//li"):
        text = " ".join(li.itertext()).strip()
        if ":" in text:
            parts = text.split(":", 1)
            label = parts[0].strip()
            value = parts[1].strip()
            if label and value:
                pairs.append((label, value))

    return pairs


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

    candidates = []
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

        candidates.append({
            "title": item.get("name"),
            "description": item.get("description"),
            "price": valid_price(to_int(price)),
            "currency": currency,
            "sqm": valid_sqm(to_int(sqm)),
            "rooms": valid_rooms(to_int(rooms)),
            "area_text": area_text
        })

    if not candidates:
        return {}

    def score(c):
        return sum(1 for key in ("price", "sqm", "rooms", "area_text", "title", "description") if c.get(key))

    candidates.sort(key=score, reverse=True)
    return candidates[0]


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


def extract_contacts(tree):
    email = None
    phone = None

    mailto = tree.xpath("//a[starts-with(translate(@href,'MAILTO','mailto'),'mailto:')]/@href")
    if mailto:
        email = mailto[0].split(":", 1)[-1].strip()

    tel = tree.xpath("//a[starts-with(translate(@href,'TEL','tel'),'tel:')]/@href")
    if tel:
        phone = tel[0].split(":", 1)[-1].strip()

    return {
        "agent_email": email,
        "agent_phone": phone
    }


def extract_from_pairs(tree):
    pairs = extract_key_value_pairs(tree)

    price_labels = {"price", "precio", "prix", "preis", "cost", "coste"}
    sqm_labels = {"sqm", "m2", "m²", "superficie", "area", "surface", "size"}
    rooms_labels = {"rooms", "bedrooms", "dormitorios", "dormitorio", "habitaciones", "habitacion", "hab"}
    area_labels = {"location", "localidad", "zona", "area", "address", "direccion", "ciudad"}
    agent_labels = {"agent", "realtor", "agency", "contact", "contacto", "agente", "inmobiliaria"}
    phone_labels = {"phone", "telefono", "tel", "móvil", "movil"}
    email_labels = {"email", "e-mail", "correo"}

    found = {
        "price": None,
        "sqm": None,
        "rooms": None,
        "area_text": None,
        "agent_name": None,
        "agent_phone": None,
        "agent_email": None
    }

    for raw_label, raw_value in pairs:
        label = normalize_label(raw_label)
        value = raw_value.strip()

        if found["price"] is None and any(k in label for k in price_labels) and has_currency(value):
            found["price"] = valid_price(to_int(value))

        if found["sqm"] is None and any(k in label for k in sqm_labels):
            found["sqm"] = valid_sqm(to_int(value))

        if found["rooms"] is None and any(k in label for k in rooms_labels):
            found["rooms"] = valid_rooms(to_int(value))

        if found["area_text"] is None and any(k in label for k in area_labels):
            found["area_text"] = value

        if found["agent_name"] is None and any(k in label for k in agent_labels):
            if not re.search(r"\\d", value):
                found["agent_name"] = value

        if found["agent_phone"] is None and any(k in label for k in phone_labels):
            if looks_like_phone(value):
                found["agent_phone"] = value

        if found["agent_email"] is None and any(k in label for k in email_labels):
            if "@" in value:
                found["agent_email"] = value

    return found


def extract_microdata(tree):
    title = find_first_text(tree, [
        "//*[@itemprop='name']",
        "//*[@itemprop='headline']"
    ])
    description = find_first_text(tree, [
        "//*[@itemprop='description']"
    ])
    price_text = find_first_text(tree, [
        "//*[@itemprop='price']/@content",
        "//*[@itemprop='price']"
    ])
    currency = find_first_text(tree, [
        "//*[@itemprop='priceCurrency']/@content",
        "//*[@itemprop='priceCurrency']"
    ])
    rooms_text = find_first_text(tree, [
        "//*[@itemprop='numberOfRooms']/@content",
        "//*[@itemprop='numberOfRooms']"
    ])
    sqm_text = find_first_text(tree, [
        "//*[@itemprop='floorSize']/@content",
        "//*[@itemprop='floorSize']"
    ])

    return {
        "title": title,
        "description": description,
        "price": valid_price(to_int(price_text)),
        "currency": currency,
        "sqm": valid_sqm(to_int(sqm_text)),
        "rooms": valid_rooms(to_int(rooms_text))
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
            if has_number and has_currency(text) and not looks_like_phone(text):
                candidates["price"].append(text)
            else:
                nxt = el.getnext()
                if nxt is not None:
                    nxt_text = text_from_node(nxt)
                    if re.search(r"\\d", nxt_text) and has_currency(nxt_text) and not looks_like_phone(nxt_text):
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
                    nxt_text = text_from_node(nxt)
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
    for node in tree.xpath(
        "//*[contains(translate(@class,'PRICE','price'),'price') or "
        "contains(translate(@id,'PRICE','price'),'price') or "
        "contains(translate(@class,'COST','cost'),'cost') or "
        "contains(translate(@class,'AMOUNT','amount'),'amount')]"
    ):
        text = node_context_text(node)
        if has_currency(text) and not looks_like_phone(text):
            price_candidates.append(text)

    for node in tree.xpath(
        "//*[contains(translate(text(),'PRICE','price'),'price') or "
        "contains(translate(text(),'PRECIO','precio'),'precio')]"
    ):
        nxt = node.getnext()
        text = node_context_text(nxt)
        if has_currency(text) and not looks_like_phone(text):
            price_candidates.append(text)

    sqm_candidates = []
    for node in tree.xpath(
        "//*[contains(translate(@class,'SQM','sqm'),'sqm') or "
        "contains(translate(@class,'M2','m2'),'m2') or "
        "contains(translate(@class,'AREA','area'),'area') or "
        "contains(translate(@class,'SUPERFICIE','superficie'),'superficie')]"
    ):
        text = node_context_text(node)
        if "m²" in text or "m2" in text.lower():
            sqm_candidates.append(text)

    for node in tree.xpath(
        "//*[contains(translate(text(),'SUPERFICIE','superficie'),'superficie') or "
        "contains(translate(text(),'AREA','area'),'area')]"
    ):
        nxt = node.getnext()
        text = node_context_text(nxt)
        if "m²" in text or "m2" in text.lower():
            sqm_candidates.append(text)

    rooms_candidates = []
    for node in tree.xpath(
        "//*[contains(translate(@class,'BED','bed'),'bed') or "
        "contains(translate(@class,'HAB','hab'),'hab') or "
        "contains(translate(@class,'ROOM','room'),'room') or "
        "contains(translate(@class,'DORM','dorm'),'dorm')]"
    ):
        text = node_context_text(node)
        if not any(k in text.lower() for k in ("parking", "garage", "garaje")):
            rooms_candidates.append(text)

    for node in tree.xpath(
        "//*[contains(translate(text(),'DORMITOR','dormitor'),'dormitor') or "
        "contains(translate(text(),'HABITAC','habitac'),'habitac') or "
        "contains(translate(text(),'BEDROOM','bedroom'),'bedroom') or "
        "contains(translate(text(),'ROOMS','rooms'),'rooms')]"
    ):
        nxt = node.getnext()
        text = node_context_text(nxt)
        if not any(k in text.lower() for k in ("parking", "garage", "garaje")):
            rooms_candidates.append(text)

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
    micro = extract_microdata(tree)
    pairs = extract_from_pairs(tree)
    contacts = extract_contacts(tree)
    long_desc = extract_long_description(tree)
    fallback = extract_fallback(tree)

    title = jsonld.get("title") or micro.get("title") or meta.get("title")
    description = jsonld.get("description") or micro.get("description") or meta.get("description")
    if long_desc and (not description or len(long_desc) > len(description)):
        description = long_desc

    price = jsonld.get("price") or micro.get("price") or meta.get("price") or pairs.get("price") or fallback.get("price")
    currency = jsonld.get("currency") or micro.get("currency") or meta.get("currency") or "EUR"

    sqm = jsonld.get("sqm") or micro.get("sqm") or pairs.get("sqm") or fallback.get("sqm")
    rooms = jsonld.get("rooms") or micro.get("rooms") or pairs.get("rooms") or fallback.get("rooms")
    area_text = jsonld.get("area_text") or pairs.get("area_text") or fallback.get("area_text")

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
        "area_text": area_text,
        "agent_name": pairs.get("agent_name"),
        "agent_phone": contacts.get("agent_phone") or pairs.get("agent_phone"),
        "agent_email": contacts.get("agent_email") or pairs.get("agent_email")
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
                agent_name = %s,
                agent_phone = %s,
                agent_email = %s,
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
            data["agent_name"],
            data["agent_phone"],
            data["agent_email"],
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
                agent_name, agent_phone, agent_email,
                first_seen_at, last_seen_at
            )
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
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
            data["agent_name"],
            data["agent_phone"],
            data["agent_email"],
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
