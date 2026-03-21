# FirstListing

FirstListing is a school MVP web application that identifies the original publisher of a real estate listing by matching the same property across multiple portals and building a verified publication timeline.

> **School project** — DAW (Desarrollo de Aplicaciones Web), first year. Proof-of-concept, not a production service.

---

## What it does

The user pastes a URL to a real estate listing. The system:

1. Crawls and stores the raw page (HTML, text, JSON-LD)
2. Uses AI to extract structured fields (price, m², rooms, address, etc.)
3. Scores every listing in the database against the input using SQL
4. Runs an AI description comparison on the top candidates
5. Shows the results in the user dashboard — with match scores and "first seen" timestamps

"First seen" = the earliest timestamp the crawler recorded for a listing. It is a proxy for the original publication date, not a legal claim.

---

## Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP 7.4+ |
| Crawler | Python 3 |
| Database | MySQL / MariaDB |
| AI | OpenAI GPT-4.1-mini |
| Frontend | HTML5, CSS3, vanilla JavaScript |

---

## Project structure

```
public/
  index.php          — Landing page
  login.php          — User login
  register.php       — User registration
  logout.php         — Session destroy
  user.php           — User dashboard + duplicate-check pipeline
  how.php            — How it works (public)
  helps.php          — Why it helps (public)
  privacy.php        — Privacy Policy (GDPR)
  legal.php          — Legal Notice (LSSI)
  admin/
    admin.php        — Admin dashboard (protected)
    admin_ai.php     — AI listings viewer (protected)
    admin_raw.php    — Raw crawl data viewer (protected)
    crawler_log.php  — Live crawler log (protected)
    admin_login.php  — Admin login page
    admin_logout.php — Admin logout
  css/
    user.css         — Shared stylesheet
    admin.css        — Admin stylesheet
  js/
    lang.js          — EN/ES language toggle
  partials/
    chat_widget.php  — AI chat widget (included in user pages)

python/
  crawler_v4.py      — Sitemap crawler + single-URL mode (--url=...)

scripts/
  openai_parse_raw_pages.php      — AI parser (--id=N)
  find_duplicates.php             — SQL duplicate scorer (--raw-id=N)
  ai_compare_descriptions.php     — AI description comparator (--raw-id=N --candidates=id1,id2)

config/
  db.php             — PDO database connection

data/
  sql/               — Database schema and migrations
```

---

## Duplicate-check pipeline

The pipeline runs synchronously when a user submits a URL in the dashboard:

```
1. python3 crawler_v4.py --url=<URL>
        → saves raw page, prints RAW_PAGE_ID:N

2. php scripts/openai_parse_raw_pages.php --id=N
        → extracts structured fields into ai_listings

3. php scripts/find_duplicates.php --raw-id=N
        → SQL scoring against all listings, returns JSON candidates (threshold: score ≥ 10)

4. php scripts/ai_compare_descriptions.php --raw-id=N --candidates=id1,id2,...
        → GPT-4.1-mini compares descriptions for top 5 SQL candidates
```

### Scoring system

Fields and their weights (max score = 17):

| Field | Weight |
|---|---|
| Reference ID | 5 |
| Price | 3 |
| m² | 3 |
| Rooms | 2 |
| Bathrooms | 2 |
| Property type | 1 |
| Listing type | 1 |

---

## How to run

### Requirements

- PHP 7.4+
- Python 3
- MySQL / MariaDB
- OpenAI API key in environment

### Setup

1. Create the database and run the migrations in `data/sql/`
2. Update credentials in `config/db.php`
3. Set your OpenAI API key in the environment

### Run the crawler

```bash
# Full sitemap crawl
python3 python/crawler_v4.py

# Single URL
python3 python/crawler_v4.py --url=https://example.com/listing/123
```

### Run the pipeline manually

```bash
php scripts/openai_parse_raw_pages.php --id=N
php scripts/find_duplicates.php --raw-id=N
php scripts/ai_compare_descriptions.php --raw-id=N --candidates=id1,id2
```

---

## Admin area

The admin area is protected by a separate login (`admin/admin_login.php`).

To create the admin account, insert a row into the `users` table:

```bash
php -r "echo password_hash('your_password', PASSWORD_BCRYPT);"
```

```sql
INSERT INTO users (username, password_hash, role)
VALUES ('admin', '<hash>', 'admin');
```

The admin dashboard shows:
- Raw crawl stats and AI coverage
- Full raw page viewer (HTML / text / JSON-LD)
- AI listings viewer
- Crawler controls and live log

---

## Features

- Full EN/ES language toggle on all public pages
- AI chat assistant (user area)
- Monthly search usage tracking per user
- GDPR-compliant Privacy Policy and Legal Notice (LSSI)
- Responsive design

---

## License

All rights reserved. This project is not open source.
