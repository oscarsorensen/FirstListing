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
    if node is None:
        return ""
    try:
        if isinstance(node, html.HtmlComment):
            return ""
    except Exception:
        pass
    if not hasattr(node, "itertext"):
        return ""
    try:
        return " ".join(node.itertext()).strip()
    except Exception:
        return ""


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


# === NEW: FIND MAIN PROPERTY CONTAINER ===

def find_main_container(tree):
    """
    Find the main property container to avoid extracting from related properties.
    Returns the main container element or the full tree if not found.
    
    Strategy: Look for a container BEFORE any "related properties" sections
    """
    # Try common main property container patterns
    main_selectors = [
        "//*[contains(@class, 'property-data')]",  # mediter.com uses this
        "//*[contains(@class, 'property-detail')]",
        "//*[contains(@class, 'main-property')]",
        "//*[contains(@class, 'property-main')]",
        "//*[contains(@class, 'detail-container')]",
        "//*[contains(@id, 'property-detail')]",
        "//*[contains(@id, 'main-property')]",
        "//main",
        "//article"
    ]
    
    for selector in main_selectors:
        containers = tree.xpath(selector)
        if containers:
            # Return the first matching container
            return containers[0]
    
    # Fallback: return the entire tree
    return tree


def get_first_occurrence(tree, xpath_query):
    """
    Helper to get FIRST occurrence of an element.
    This avoids picking up data from "related properties" sections.
    """
    results = tree.xpath(xpath_query)
    return results[0] if results else None


def extract_key_value_pairs(tree):
    """Extract key-value pairs from the MAIN container only"""
    # First, find the main container
    main = find_main_container(tree)
    
    pairs = []

    # Extract from definition lists (dt/dd)
    for dt in main.xpath(".//dt"):
        label = " ".join(dt.itertext()).strip()
        dd = dt.getnext()
        if dd is not None and dd.tag.lower() == "dd":
            value = " ".join(dd.itertext()).strip()
            if label and value:
                pairs.append((label, value))

    # Extract from tables (th/td)
    for row in main.xpath(".//tr"):
        th = row.xpath("./th[1]")
        td = row.xpath("./td[1]")
        if th and td:
            label = " ".join(th[0].itertext()).strip()
            value = " ".join(td[0].itertext()).strip()
            if label and value:
                pairs.append((label, value))

    # Extract from list items with colon separator
    for li in main.xpath(".//li"):
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
    for scr in scripts:
        try:
            data = json.loads(scr)
            if isinstance(data, list):
                items.extend(data)
            else:
                items.append(data)
        except json.JSONDecodeError:
            pass

    price = None
    currency = None
    sqm = None
    rooms = None
    title = None
    description = None
    area_text = None

    for item in items:
        if not isinstance(item, dict):
            continue

        if not price and "price" in item:
            price = to_int(item["price"])
        if not price and "offers" in item and isinstance(item["offers"], dict):
            price = to_int(item["offers"].get("price"))
        if not currency and "priceCurrency" in item:
            currency = item["priceCurrency"]
        if not currency and "offers" in item and isinstance(item["offers"], dict):
            currency = item["offers"].get("priceCurrency")

        if not sqm and "floorSize" in item:
            sqm = to_int(item["floorSize"])
        if not rooms and "numberOfRooms" in item:
            rooms = to_int(item["numberOfRooms"])
        if not title and "name" in item:
            title = clean_text(item["name"])
        if not description and "description" in item:
            description = clean_text(item["description"])

        if not area_text:
            addr = item.get("address")
            if isinstance(addr, dict):
                parts = [
                    addr.get("addressLocality"),
                    addr.get("addressRegion"),
                    addr.get("addressCountry")
                ]
                area_text = ", ".join([p for p in parts if p])
            elif isinstance(addr, str):
                area_text = addr

    return {
        "price": valid_price(price),
        "currency": currency,
        "sqm": valid_sqm(sqm),
        "rooms": valid_rooms(rooms),
        "title": title,
        "description": description,
        "area_text": area_text
    }


def extract_meta(tree):
    price = None
    currency = None
    sqm = None
    rooms = None
    title = None
    description = None

    price_candidates = tree.xpath("//meta[contains(translate(@property,'PRICE','price'),'price')]/@content")
    for pc in price_candidates:
        price = to_int(pc)
        if price:
            break

    currency_candidates = tree.xpath("//meta[contains(translate(@property,'CURRENCY','currency'),'currency')]/@content")
    if currency_candidates:
        currency = currency_candidates[0]

    title_candidates = tree.xpath("//meta[@property='og:title']/@content | //meta[@name='title']/@content | //title/text()")
    if title_candidates:
        title = clean_text(title_candidates[0])

    desc_candidates = tree.xpath("//meta[@property='og:description']/@content | //meta[@name='description']/@content")
    if desc_candidates:
        description = clean_text(desc_candidates[0])

    return {
        "price": valid_price(price),
        "currency": currency,
        "sqm": valid_sqm(sqm),
        "rooms": valid_rooms(rooms),
        "title": title,
        "description": description
    }


def extract_microdata(tree):
    price = None
    currency = None
    sqm = None
    rooms = None
    title = None
    description = None

    price_props = tree.xpath("//*[@itemprop='price']/@content | //*[@itemprop='price']/text()")
    for pp in price_props:
        price = to_int(pp)
        if price:
            break

    currency_props = tree.xpath("//*[@itemprop='priceCurrency']/@content | //*[@itemprop='priceCurrency']/text()")
    if currency_props:
        currency = currency_props[0]

    sqm_props = tree.xpath("//*[@itemprop='floorSize']/@content | //*[@itemprop='floorSize']/text()")
    for sp in sqm_props:
        sqm = to_int(sp)
        if sqm:
            break

    rooms_props = tree.xpath("//*[@itemprop='numberOfRooms']/@content | //*[@itemprop='numberOfRooms']/text()")
    for rp in rooms_props:
        rooms = to_int(rp)
        if rooms:
            break

    title_props = tree.xpath("//*[@itemprop='name']/text()")
    if title_props:
        title = clean_text(title_props[0])

    desc_props = tree.xpath("//*[@itemprop='description']/text()")
    if desc_props:
        description = clean_text(desc_props[0])

    return {
        "price": valid_price(price),
        "currency": currency,
        "sqm": valid_sqm(sqm),
        "rooms": valid_rooms(rooms),
        "title": title,
        "description": description
    }


def extract_contacts(tree):
    """Extract agent contact info from tel: and mailto: links"""
    agent_phone = None
    agent_email = None

    tel_links = tree.xpath("//a[starts-with(@href, 'tel:')]/@href")
    if tel_links:
        phone_text = tel_links[0].replace("tel:", "").strip()
        agent_phone = phone_text

    mailto_links = tree.xpath("//a[starts-with(@href, 'mailto:')]/@href")
    if mailto_links:
        email_text = mailto_links[0].replace("mailto:", "").strip()
        agent_email = email_text

    return {
        "agent_phone": agent_phone,
        "agent_email": agent_email
    }


def extract_from_pairs(tree):
    pairs = extract_key_value_pairs(tree)

    price_keywords = ["precio", "price", "cost", "coste"]
    sqm_keywords = ["superficie", "area", "metros", "m2", "sqm", "square"]
    rooms_keywords = ["habitaciones", "dormitorios", "bedrooms", "rooms", "dorm", "hab"]
    agent_keywords = ["agente", "agent", "vendedor", "seller", "contact"]

    price = None
    sqm = None
    rooms = None
    agent_name = None
    agent_phone = None
    agent_email = None
    area_text = None

    for label, value in pairs:
        lower_label = normalize_label(label)

        if not price and any(k in lower_label for k in price_keywords):
            if has_currency(value) and not looks_like_phone(value):
                price = to_int(value)

        if not sqm and any(k in lower_label for k in sqm_keywords):
            sqm = to_int(value)

        if not rooms and any(k in lower_label for k in rooms_keywords):
            if not any(k in lower_label for k in ("parking", "garage", "garaje")):
                rooms = to_int(value)

        if not agent_name and any(k in lower_label for k in agent_keywords):
            agent_name = clean_text(value)

        if "@" in value and not agent_email:
            agent_email = value.strip()

        if looks_like_phone(value) and not agent_phone:
            agent_phone = value.strip()

    return {
        "price": valid_price(price),
        "sqm": valid_sqm(sqm),
        "rooms": valid_rooms(rooms),
        "agent_name": agent_name,
        "agent_phone": agent_phone,
        "agent_email": agent_email,
        "area_text": area_text
    }


def pick_best_number(candidates, validator):
    """Pick the most likely valid number from a list of text candidates"""
    numbers = []
    for c in candidates:
        n = to_int(c)
        if n and validator(n):
            numbers.append(n)
    
    if not numbers:
        return None
    
    # Return median to avoid outliers
    numbers.sort()
    mid = len(numbers) // 2
    return numbers[mid]


def extract_labeled_numbers(tree):
    """
    Extract numbers near labels using FIRST occurrence strategy.
    This prevents picking up data from related properties at bottom of page.
    """
    from lxml.etree import _Element
    
    candidates = {
        "price": [],
        "sqm": [],
        "rooms": []
    }

    price_keywords = ["precio", "price", "cost", "coste"]
    sqm_keywords = ["superficie", "area", "metros", "m2", "sqm"]
    rooms_keywords = ["habitaciones", "dormitorios", "bedrooms", "rooms", "dorm", "hab", "beds"]
    parking_keywords = ["parking", "garage", "garaje", "aparcamiento"]

    # STRATEGY: Collect candidates, but give priority to earlier occurrences
    position_weights = {}  # Track position in document
    
    for idx, el in enumerate(tree.xpath(".//*")):
        # Skip if not an actual element (could be comment, processing instruction, etc.)
        if not isinstance(el, _Element):
            continue
            
        text = text_from_node(el)
        if not text:
            continue

        lower = text.lower()
        has_number = bool(re.search(r'\\d', text))

        if any(k in lower for k in price_keywords):
            if has_number and has_currency(text) and not looks_like_phone(text):
                candidates["price"].append((idx, text))
            else:
                nxt = el.getnext()
                if nxt is not None and isinstance(nxt, _Element):
                    nxt_text = text_from_node(nxt)
                    if re.search(r"\\d", nxt_text) and has_currency(nxt_text) and not looks_like_phone(nxt_text):
                        candidates["price"].append((idx, nxt_text))

        if any(k in lower for k in sqm_keywords):
            if has_number:
                candidates["sqm"].append((idx, text))
            else:
                nxt = el.getnext()
                if nxt is not None and isinstance(nxt, _Element):
                    nxt_text = " ".join(nxt.itertext()).strip()
                    if re.search(r"\\d", nxt_text):
                        candidates["sqm"].append((idx, nxt_text))

        if any(k in lower for k in rooms_keywords) and not any(k in lower for k in parking_keywords):
            if has_number:
                candidates["rooms"].append((idx, text))
            else:
                nxt = el.getnext()
                if nxt is not None and isinstance(nxt, _Element):
                    nxt_text = text_from_node(nxt)
                    if re.search(r"\\d", nxt_text):
                        candidates["rooms"].append((idx, nxt_text))

    # Pick FIRST valid occurrence (earliest in document)
    def pick_first_valid(candidates_with_pos, validator):
        if not candidates_with_pos:
            return None
        # Sort by position (idx)
        candidates_with_pos.sort(key=lambda x: x[0])
        # Try each in order until we find a valid one
        for idx, text in candidates_with_pos:
            n = to_int(text)
            if n and validator(n):
                return n
        return None

    return {
        "price": pick_first_valid(candidates["price"], valid_price),
        "sqm": pick_first_valid(candidates["sqm"], valid_sqm),
        "rooms": pick_first_valid(candidates["rooms"], valid_rooms)
    }


def extract_fallback(tree):
    """
    Fallback extraction using FIRST occurrence strategy
    """
    labeled = extract_labeled_numbers(tree)

    # If labeled extraction worked, use it
    if labeled.get("price") and labeled.get("sqm") and labeled.get("rooms"):
        return labeled

    # Otherwise, search for class/id based extraction (use FIRST occurrence)
    price_node = get_first_occurrence(tree,
        ".//*[contains(translate(@class,'PRICE','price'),'price') or "
        "contains(translate(@id,'PRICE','price'),'price') or "
        "contains(translate(@class,'PRECIO','precio'),'precio')]"
    )
    price = None
    if price_node:
        text = node_context_text(price_node)
        if has_currency(text) and not looks_like_phone(text):
            price = to_int(text)

    sqm_node = get_first_occurrence(tree,
        ".//*[contains(translate(@class,'M2','m2'),'m2') or "
        "contains(translate(@class,'SQM','sqm'),'sqm') or "
        "contains(translate(@class,'SUPERFICIE','superficie'),'superficie')]"
    )
    sqm = None
    if sqm_node:
        text = node_context_text(sqm_node)
        if "m²" in text or "m2" in text.lower():
            sqm = to_int(text)

    rooms_node = get_first_occurrence(tree,
        ".//*[contains(translate(@class,'BED','bed'),'bed') or "
        "contains(translate(@class,'BEDS','beds'),'beds') or "
        "contains(translate(@class,'DORM','dorm'),'dorm')]"
    )
    rooms = None
    if rooms_node:
        text = node_context_text(rooms_node)
        if not any(k in text.lower() for k in ("parking", "garage", "garaje")):
            rooms = to_int(text)

    area_text = find_first_text(tree, [
        ".//*[contains(translate(@class,'LOCATION','location'),'location') or "
        "contains(translate(@class,'AREA','area'),'area') or "
        "contains(translate(@class,'ADDRESS','address'),'address')]"
    ])

    return {
        "price": labeled.get("price") or valid_price(price),
        "sqm": labeled.get("sqm") or valid_sqm(sqm),
        "rooms": labeled.get("rooms") or valid_rooms(rooms),
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
            
            # Print extracted data for debugging
            print("=" * 60)
            print(f"URL: {data['url']}")
            print(f"Price: {data['price']} {data['currency']}")
            print(f"Sqm: {data['sqm']}")
            print(f"Rooms: {data['rooms']}")
            print(f"Agent: {data.get('agent_name', 'N/A')}")
            print(f"Phone: {data.get('agent_phone', 'N/A')}")
            print(f"Email: {data.get('agent_email', 'N/A')}")
            print("=" * 60)
            
            upsert_listing(data, conn)

    finally:
        conn.close()


if __name__ == "__main__":
    run()
