# Exam Guide — Databases
6 questions · 15 minutes each
Remember for each question do:
   - 2.1. Introduction to the concept being presented
   - 2.2. Technical aspects (code, to put it simply)
   - 2.3. Overall use of what is being presented (what does it do for the end user)
   - 2.4. Conclusion
---

## Q1 — Recognizes the elements of databases by analyzing their functions and assessing the usefulness of management systems.

**Video**
Open a terminal, log into MySQL, run SHOW TABLES and SELECT COUNT(*) FROM raw_pages.

**What to explain**
The project needs a database because it stores large amounts of data that must persist across requests and be queryable. Raw HTML is too big for flat files. Price, sqm, and rooms need to be filtered with SQL. User sessions need to survive between page loads. Read the opening comment in data/sql/test2firstlisting.sql — it states the design decision plainly.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/data/sql/test2firstlisting.sql

---

## Q2 — Creates databases by defining their structure and the characteristics of their elements according to the relational model.

**Video**
Open data/sql/test2firstlisting.sql in the editor and scroll through each CREATE TABLE block.

**What to explain**
Walk through the three most important tables in data/sql/test2firstlisting.sql.

raw_pages at lines 11–25: BIGINT id because it can grow very large, LONGTEXT for html_raw and text_raw, UNIQUE KEY on url to prevent crawling the same page twice.

ai_listings at lines 28–53 and the ALTER block at lines 78–83: one row per parsed listing, separate from raw_pages so you can re-run the AI parser without touching the original HTML.

users at lines 86–93: username and email are UNIQUE, password_hash is never plain text, role is an ENUM limited to three values.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/data/sql/test2firstlisting.sql

---

## Q3 — Queries information stored in a database using assistants, graphical tools and the data manipulation language.

**Video**
Run a duplicate check in the browser, then switch to the editor and show the SELECT queries that powered it.

**What to explain**
The main query is in scripts/find_duplicates.php at lines 47–79. It uses IF expressions inside a calculated match_score column — each matching field adds points. HAVING match_score >= 5 filters weak matches. It JOINs raw_pages to get the url and first_seen_at, orders by score, and limits to 20 results.

Also point to the login query in public/login.php at lines 27–34 — a simple SELECT on users by username — as a contrast to show SELECT is used throughout the project, not just for complex queries.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/scripts/find_duplicates.php

---

## Q4 — Modifies information stored in the database using assistants, graphical tools and the data manipulation language.

**Video**
Show three actions: register a new user (INSERT), run a search on a URL already in the database (UPDATE), delete a raw page from the admin panel (DELETE).
- admin login is : admin : 123456

**What to explain**
INSERT — public/register.php lines 38–49: hashes the password with password_hash(), then INSERT INTO users.

UPDATE — python/crawler_v4.py touch_last_seen() at lines 203–204: UPDATE raw_pages SET fetched_at = NOW() for URLs already in the database.

DELETE — public/admin/admin.php lines 175–186: deletes from ai_listings first (it holds the foreign key), then from raw_pages.

**GitHub links**
https://github.com/oscarsorensen/FirstListing/blob/main/public/register.php
https://github.com/oscarsorensen/FirstListing/blob/main/public/admin/admin.php

---

## Q6 — Designs normalized relational models by interpreting entity/relationship diagrams.

**Video**
Open your prepared ER diagram on screen, then switch to data/sql/test2firstlisting.sql and show the FOREIGN KEY lines to confirm the diagram matches the code.

**What to explain**
The five tables and their relationships: users to subscriptions (one to one), users to search_usage (one to many, composite primary key on user_id + month), raw_pages to ai_listings (one to one, CASCADE DELETE), raw_pages to vector_matches (defined but not actively used).

The foreign key constraints are at lines 50–52 for ai_listings, lines 100–103 for subscriptions, and lines 109–111 for search_usage. Explain normalization with the raw_pages / ai_listings split: keeping derived data in a separate table means you can re-run AI extraction without overwriting the original crawl data.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/data/sql/test2firstlisting.sql

---

## Q7 — Manages information stored in non-relational databases, evaluating and using the capabilities provided by the management system.

**Video**
Open data/crawl_log.jsonl and scroll through it — one JSON object per line, no fixed structure. Then open python/crawler_v4.py and show log_crawl_event().

**What to explain**
The JSONL file is the non-relational store. Each line is a self-contained JSON object with no schema — fields can vary between lines. It is append-only, nothing is ever updated or deleted.

There are two log functions. log_crawl_event() at lines 173–179 fires when a new URL is inserted — it calls result.to_dict() to get the base dict, adds action, raw_page_id, and timestamp, then writes one JSON line. log_seen_event() at lines 183–189 fires when a URL was already in the database — it logs url, action: "seen", raw_page_id, and timestamp. Both write to the same file, so the JSONL records every URL the crawler touches, not just new ones.

The contrast: raw_pages is relational — fixed schema, SQL queries, constraint checks. crawl_log.jsonl is non-relational — no schema, readable with any text tool, always writable.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/python/crawler_v4.py
