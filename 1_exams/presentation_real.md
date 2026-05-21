

## PRESENTATION ##

**Goal: name the problem, name the solution, set up the demo.**

### ##############################################################################################################################
## SECTION 1 — Introduction (1 minute)

**Goal: name the problem, name the solution, set up the demo.**

- The problem: same property, multiple portals, different agencies. Duplicates. This is a real problem.
- The solution: FirstListing automates duplicate detection — crawl a listing URL, extract its fields with AI, score it against the database, compare descriptions.
- One sentence on the tech: PHP backend, Python crawler, MySQL database, OpenAI API — no frameworks
- Transition directly into the demo


### ##############################################################################################################################

## SECTION 2 — Show the App (4 minutes)

**Goal: show the full pipeline running end to end, then show the data behind it.**

### Show Landing page and login page (~55 seconds)
- Uses password hash. When logged in, PHP session-based auth. 
- Mention: all database queries use PDO with parameterized queries (defense) — SQL injection is not possible
- Mention: all output runs through `htmlspecialchars()` — prevents XSS (the attack)

### Paste a URL and submit (~30 seconds)
- http://localhost:8080/Projects/Project%20FirstListing/data/demo-portal/fotohouse-NBH-43257.html 
- Explain what is happening: the crawler is fetching the page, AI will extract the fields, SQL will score the matches, AI will compare descriptions
- talk about the two-table design (raw_pages & ai_listings)


### Pipeline status, extracted fields and results (~1 minute)
- Explain results and scoring system.
- Point out that the AI returns pure JSON at temperature zero — no creativity, only extraction
- Mention `first_seen_at` — explain clearly what it means. This is just a proxy.
- Explain the scoring system. SQL wide net, then AI. Efficiency.

### ##############################################################################################################################


## SECTION 3 — Show the Code and Database (4 minutes)

**Goal: show the architecture is deliberate, not accidental. Four files only.**

### The database schema — `data/sql/test2firstlisting.sql` (~30 seconds)


### The crawler — `python/crawler_v4.py` (~45 seconds)
- Show the `CrawlResult` class (~line 156)
- Explain why it exists: before it, seven separate variables were being passed between functions — refactoring into a class made the code cleaner and easier to reason about
- One sentence on the two modes: single-URL mode triggered by the user page, and sitemap mode for bulk crawling — you do not need to show both in the code

### The SQL scorer — `scripts/find_duplicates.php` (~45 seconds)
- Scroll to the big SQL query (~line 47)
- Point to the `IF()` expressions inside the `SUM()` — the weights are right there in the query
- Point out `HAVING match_score >= 5` — this is the SQL filter. Anything scoring 5 or above comes back. The UI then applies the "Very likely / Likely / Possible" labels on top of that. Two layers — SQL nets the candidates, labels rank them.
- This is the most data-engineering part of the project — worth lingering one moment on

> **Own it:** "I chose to put the scoring logic directly in SQL rather than pulling all rows into PHP and scoring them there — it's faster and keeps the logic close to the data."

### ##############################################################################################################################



## SECTION 4 — Closing (1 minute)

**Goal: summarise what was built, show self-awareness, end strong.**

- The project is a four-step pipeline: crawl, extract, score, compare — built on PHP, Python, MySQL, and the OpenAI API
- What you would add with more time: more portals beyond jensenestate.es, and enforcing the subscription plan in the UI (the table is already there)
- The project is honest about its limits: "first seen" is a crawl timestamp, not a legal claim, and the database only knows what the crawler has visited
- End with one concrete thing you learned — not abstract, but specific. For example: you originally tried ChromaDB for similarity matching, found it unreliable because agents rewrite descriptions entirely, and replaced it with direct GPT comparison — that decision taught you to test assumptions before building on them. Or: the two-table split was a decision you made early when you realised re-crawling to fix a bad AI parse would mean losing the original timestamp — understanding that consequence before it happened was a real planning moment. Use whichever is most true for you.

> The closi