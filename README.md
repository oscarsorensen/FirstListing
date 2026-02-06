# FirstListing
FirstListing is a B2B-focused web application that identifies the original publisher of a real estate listing by matching the same property across multiple platforms and building a verified publication timeline.

This repo contains the **School Project MVP** for a proof-of-concept with 5 sites.

## Project idea

The user provides a link to a real estate advertisement.  
The system extracts key information such as price, surface area, location, and images, and searches for the same property across multiple portals, agency websites, and private listings.

All matching listings are grouped together and ordered by publication date, making it possible to identify the original publisher (private owner or agent) and distinguish them from later intermediaries.

## Goal

The main goal is to save time, reduce duplicate work, and improve transparency in the real estate market by clearly identifying the first public listing of a property.

## Target users

- Real estate agents and agencies (primary users)
- Advanced private house-hunters (secondary users)

## School MVP scope

- Crawl 5 working sites (proof-of-concept)
- Store **raw HTML + text + JSON-LD** in MySQL
- AI will later organize raw data into structured fields
- Use SQL + VectorDB to find duplicates
- “First seen” = first time the crawler saw a listing (proxy)

## Pipeline (MVP)

1. **Crawler → raw_pages**
   - Stores `html_raw`, `text_raw`, `jsonld_raw`, `fetched_at`, `first_seen_at`
2. **AI parser (next step) → ai_listings**
   - Extracts `price`, `sqm`, `rooms`, `address`, etc.
3. **Duplicate detection**
   - SQL candidate filter → VectorDB similarity

## Project structure

- `public/` — user + admin pages
- `public/css/` — styles
- `python/` — crawlers and vector scripts
- `data/html/` — local HTML fixtures
- `data/sql/` — SQL schemas
- `config/` — database config
- `docs/` — notes and drafts

## How to run (crawler v4)

1. Create DB and tables in `test2firstlisting`
2. Update DB credentials in `python/crawler_v4.py`
3. Run:
   ```bash
   python3 python/crawler_v4.py
   ```

## Admin

- `public/admin.php` shows raw crawl stats + AI coverage
- `public/admin_raw.php` shows raw HTML/text/JSON‑LD per row

## Technical scope (planned, real product)

- Web scraping
- Database storage
- Property matching algorithms
- Publication date comparison
- Optional image or location-based matching

## Status

This project is currently in an early prototype stage (school MVP).

## License

All rights reserved.  
This project is not open source.
