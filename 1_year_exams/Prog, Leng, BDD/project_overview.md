# FirstListing — Complete Project Overview

## What It Is

**FirstListing** is a school MVP (Minimum Viable Product) web application built by Oscar (first-year DAW student) for the Proyecto Intermodular I exam. The problem it solves: the same real estate property often gets listed by multiple agencies across multiple portals, creating duplicate listings online. FirstListing detects those duplicates.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 7.4+, Python 3 |
| Frontend | HTML5, CSS3, vanilla JavaScript — no frameworks |
| Database | MySQL/MariaDB (`test2firstlisting`) |
| AI | OpenAI GPT-4.1-mini (via REST API with cURL) |
| Server | Apache via Homebrew (`/opt/homebrew/var/www`) |

---

## Database Schema — 5 Tables

1. **`raw_pages`** — stores everything the crawler fetches: full HTML, plain text, JSON-LD structured data, URL, domain, `first_seen_at` and `fetched_at` timestamps.
2. **`ai_listings`** — AI-extracted structured fields linked to a `raw_page_id`: title, description, price, sqm, rooms, bathrooms, plot_sqm, property_type, listing_type, address, reference_id, agent name/phone/email.
3. **`users`** — registered users with username, email (optional), bcrypt password hash, and role (`agent`, `admin`, or `private`).
4. **`subscriptions`** — plan table (basic/standard/pro) per user. Wired up but not currently enforced in the UI.
5. **`search_usage`** — tracks how many duplicate-check searches each user has done per calendar month.
6. **`vector_matches`** — schema exists but is unused. Was originally planned for vector-based similarity. ChromaDB was evaluated and removed.

The design is intentionally split into two layers: raw evidence first (`raw_pages`), AI-organized fields second (`ai_listings`). This allows re-parsing without losing original crawl data.

---

## The Core Pipeline — 4 Steps

This is the heart of the project. It runs synchronously in `user.php` when a user submits a URL.

### Step 1 — Crawl (`python/crawler_v4.py --url=URL`)
- Python fetches the page using `requests`
- Extracts plain text and JSON-LD from the HTML using `lxml`
- Inserts into `raw_pages`, or if the URL already exists, just updates `fetched_at` and prints `[SEEN]`
- Prints `RAW_PAGE_ID:N` to stdout so PHP knows the database row ID
- Also supports **sitemap mode** (no `--url` arg): discovers all listings from `jensenestate.es`'s XML sitemap and bulk-crawls them

### Step 2 — AI Parse (`scripts/openai_parse_raw_pages.php --id=N`)
- Preprocesses the HTML into targeted snippets (title, headings, keyword lines)
- Extracts description candidates from `<meta>` tags and description-class divs
- Sends all of this to GPT-4.1-mini with a strict JSON extraction prompt (temperature=0.0)
- Model returns: title, description, price, sqm, rooms, bathrooms, plot_sqm, property_type, listing_type, address, reference_id, agent info
- Saves to `ai_listings` using `INSERT ... ON DUPLICATE KEY UPDATE`

### Step 3 — SQL Duplicate Scoring (`scripts/find_duplicates.php --raw-id=N`)
- Scores every other listing in the database against the submitted one
- Scoring weights: `reference_id=5, price=3, sqm=3, rooms=2, bathrooms=2, property_type=1, listing_type=1` → max score = 17
- Returns candidates scoring ≥ 5, ordered by score descending, top 20
- A score of 10+ is considered a meaningful match in the UI

### Step 4 — AI Description Comparison (`scripts/ai_compare_descriptions.php --raw-id=N --candidates=id1,id2,...`)
- Takes the top 5 SQL candidates that have descriptions
- Sends each description pair to GPT-4.1-mini: "Same property? yes/no + confidence 0–1 + one-sentence reason"
- Returns a JSON array with `same_property` (bool), `confidence` (float), `reason` (string) per candidate
- Results displayed as colored badges in the UI; hovering shows the reason

---

## User-Facing Pages

| Page | Purpose |
|---|---|
| `index.php` | Landing page — hero, steps overview, demo card |
| `register.php` | Account creation (username, optional email, password, role) |
| `login.php` | Login with session setup |
| `logout.php` | Destroys session, redirects to index |
| `user.php` | **The main tool** — paste URL, run pipeline, see results |
| `how.php` | Technical explanation of the 5-step pipeline (public) |
| `helps.php` | Why duplicate detection matters (public) |
| `privacy.php` | GDPR Privacy Policy |
| `legal.php` | Legal Notice (LSSI compliant) |

---

## Admin Area (`public/admin/`)

Protected by a separate `$_SESSION['admin_id']` (different from user sessions):

- **`admin.php`** — main dashboard: stats cards (raw pages, parsed, unparsed, users, searches), crawler control (run/stop/clear log with live log viewer), raw pages table with filters, "Parse selected" button, AI listings table
- **`admin_ai.php`** — view a specific AI listing's full content
- **`admin_raw.php`** — view raw HTML, text, or JSON-LD for a specific page
- **`crawler_log.php`** — serves live crawler log content (polled by JS every 2.5s while crawler runs)
- **`admin_login.php` / `admin_logout.php`** — separate auth for admin

The crawler can be started as a background process from the admin panel. Its PID is saved to `/tmp/firstlisting_crawler.pid` so it can be stopped later.

---

## Other Notable Features

### EN/ES Language Toggle (`public/js/lang.js`)
All page text has `lang-change="key"` attributes. On page load, English text is snapshotted into `data-en` attributes. Clicking the toggle swaps all text to Spanish (from the `es` object in `lang.js`) or back. Preference saved in `localStorage`.

### Floating AI Chat Widget (`public/partials/chat_widget.php`)
A chat bubble in the bottom-right on public pages. Sends messages to `chat.php`, which calls GPT-4.1-mini with a system prompt explaining how FirstListing works. The AI is instructed not to reveal admin or internal implementation details.

### JSONL Crawl Log (`data/crawl_log.jsonl`)
Append-only, non-relational log of every crawl event (inserted/seen/parsed). Demonstrates awareness of non-relational data storage alongside MySQL — a deliberate design choice mentioned in the project.

### `CrawlResult` Class (Python)
A simple OOP element in `crawler_v4.py` that wraps the 7 fields from one crawled page. Refactored from passing 7 separate variables between functions — a good example of code improvement thinking to mention in the presentation.

### Security
- PDO with parameterized queries everywhere — no SQL injection possible
- `password_hash()` / `password_verify()` for passwords (bcrypt)
- `htmlspecialchars()` via a local `esc()` helper for all HTML output — prevents XSS
- Role-based access: `agent`, `admin`, `private`
- Auth check at the top of every protected page; redirect + `exit` pattern

---

## What the Project Explicitly Is NOT

- Not a legal claim of "first published" — "first seen" = first time the crawler visited that URL, not when the listing was originally published
- Not production-ready — a proof-of-concept MVP
- Not multi-portal at demo time — the crawler targets `jensenestate.es` only (a real estate site whose owner gave permission to crawl)

---

## Presentation Structure (10 minutes)

| Time | Section |
|---|---|
| 1 min | Student introduction |
| 4 min | Show the app running |
| 4 min | Show the code and database |
| 1 min | Closing and conclusion |

### What to demo in the "app running" section
1. Register or log in
2. Paste a listing URL into the user page
3. Watch the pipeline status update (Crawled → Parsed → Candidates found)
4. Show the extracted fields table
5. Show the duplicate candidates table with match scores
6. Show the AI badge on a candidate (hover for reason)
7. Briefly show the admin panel — stats, crawler control, raw pages table, AI listings table

### What to show in the "code and database" section
- `user.php` — the 4-step pipeline in PHP (crawl → parse → score → compare)
- `python/crawler_v4.py` — single-URL mode vs sitemap mode, the `CrawlResult` class
- `scripts/openai_parse_raw_pages.php` — prompt construction, JSON extraction
- `scripts/find_duplicates.php` — the weighted SQL scoring query
- `scripts/ai_compare_descriptions.php` — GPT-4.1-mini description comparison
- `data/sql/test2firstlisting.sql` — the two-layer schema design (`raw_pages` + `ai_listings`)

### Grading breakdown (third trimester)
- 40% — working project
- 30% — presentation
- 30% — document

Submitting a working project and giving the presentation are both **mandatory conditions** to be graded.

---

## Key Technical Decisions Worth Mentioning

- **Two-table design**: raw evidence is stored separately from AI-extracted fields. This means the raw HTML/text is never overwritten and can be re-parsed at any time if the AI prompt improves.
- **ChromaDB was considered and removed**: vector similarity was evaluated but GPT-4.1-mini description comparison was more accurate for rewritten descriptions and cost only ~$0.0003 per call.
- **Synchronous pipeline**: all 4 steps run on a single HTTP request in `user.php`. `set_time_limit(120)` prevents PHP from timing out. Takes 10–20 seconds in practice.
- **Scoring is transparent**: the score is a simple integer sum displayed directly in the UI. Users can see exactly why a candidate scored what it did.
- **Non-relational log alongside relational DB**: `crawl_log.jsonl` is an append-only flat file — mentioned as a conscious architectural choice.
