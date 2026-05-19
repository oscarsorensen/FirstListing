# FirstListing — Presentation Guide
**10 minutes. Going over is penalised. Practice out loud at home.**

---

## The Red Line

Every section should connect back to this one idea:

> **The same property gets listed by multiple agencies. Tracking that manually is impossible. FirstListing automates it — crawl the evidence, let AI organize the fields, score the matches with SQL, confirm with AI description comparison.**

The pipeline is the story. Each section of the presentation is one more step in that pipeline being explained. Introduction sets the problem. Demo shows it working. Code shows how. Closing reflects on what was built and learned.

---

## Before You Go In

- Pick your demo URL in advance and test it — use one that produces at least one duplicate candidate with an AI verdict. Know which URL you are using before you walk in.
- If the URL is already in the database it prints `[SEEN]` — this is a feature, not a bug. Mention it if it happens.
- Have these tabs open and ready: browser on the landing page logged out, editor with `test2firstlisting.sql`, `user.php`, `crawler_v4.py`, `find_duplicates.php`. No admin panel tab needed.
- Print a one-page bullet outline to hold in your hand. This file is your preparation — not the thing you bring in.

---

## SECTION 1 — Introduction (1 minute)

**Goal: name the problem, name the solution, set up the demo.**

- Your name, first-year DAW student
- The real-world problem: the same property appears on multiple portals listed by different agencies — duplicates are everywhere in real estate
- What FirstListing does: automates duplicate detection — crawl a listing URL, extract its fields with AI, score it against the database, compare descriptions
- One sentence on the tech: PHP backend, Python crawler, MySQL database, OpenAI API — no frameworks
- Transition directly into the demo

> **Own it:** This is a problem you chose because it is real and technically interesting. Say so in one sentence.

---

## SECTION 2 — Show the App (4 minutes)

**Goal: show the full pipeline running end to end, then show the data behind it.**

### Landing page (~20 seconds)
- Show the homepage briefly — what it is, what it promises
- Mention the EN/ES toggle in passing — built in vanilla JS, no library

### Login page (~35 seconds)
- Navigate to login.php — show the form briefly
- Mention: passwords are never stored in plain text — PHP's `password_hash()` stores a bcrypt hash, `password_verify()` checks it at login
- Mention: after login, user identity goes into `$_SESSION` — standard PHP session-based auth
- Mention: all database queries use PDO with parameterized queries — SQL injection is not possible
- Mention: all output runs through `htmlspecialchars()` — prevents XSS
- Then log in and move to the user page

> **Own it:** These are not afterthoughts — security was built in from the start, not added at the end.

### Paste a URL and submit (~30 seconds)
- Go to the user page
- Paste your prepared demo URL and submit
- Explain out loud what is now happening: the crawler is fetching the page, AI will extract the fields, SQL will score the matches, AI will compare descriptions
- Mention it takes 10–20 seconds — this is normal, the whole pipeline runs in one request
- If the page prints `[SEEN]` at the top: name it as a feature — the crawler recognised the URL was already in the database and skipped re-fetching it. The rest of the pipeline still runs.

> **While waiting (~15 seconds):** talk about the two-table design decision — raw evidence stored separately from AI fields. This is a good moment to fill the wait naturally rather than standing in silence.

### Pipeline status and extracted fields (~1 minute)
- Point to the three status pills: Crawled / AI Parsed / Candidates found
- Walk through the extracted fields table slowly — price, sqm, rooms, bathrooms, address, reference ID
- Point out that the AI returns pure JSON at temperature zero — no creativity, only extraction
- Mention `first_seen_at` — explain clearly what it means: the date the crawler first visited this URL, not when it was originally published. This is a proxy, not a legal claim.

### Duplicate candidates table (~1 minute)
- Walk through one candidate row — score, domain, title, price, sqm
- Explain the scoring: reference ID is worth 5, price and sqm are 3 each, rooms and bathrooms 2 each, property type and listing type 1 each — max possible score is 17
- Clarify the two numbers the panel will see: the SQL query returns anything scoring 5 or above, the UI then labels those results — "Very likely" at 10+, "Likely" at 7–9, "Possible" below that. The SQL casts a wide net; the labels tell you how strong each match is.
- Hover over the AI badge — read the reason aloud
- This is the end product: a verdict with a confidence score and a human-readable reason

> **Transition to code:** "That is the full pipeline running end to end. Now let me show you the code and database decisions behind it."

---

## SECTION 3 — Show the Code and Database (4 minutes)

**Goal: show the architecture is deliberate, not accidental. Four files only.**

### The database schema — `data/sql/test2firstlisting.sql` (~30 seconds)
- State the design decision in two sentences: raw evidence in `raw_pages`, AI-extracted fields in `ai_listings`, linked by foreign key — if the AI prompt improves you re-parse without re-crawling, because the raw data is always there
- Point at the two table names in the file — you do not need to walk through the column definitions
- One sentence on the other tables: `users`, `search_usage` (already in use), `subscriptions` (schema exists, not yet enforced)

> **Own it:** "I decided on this split early because I realised a crawler will never perfectly extract everything in one pass — you need the raw data to fall back on."

### The pipeline glue — `public/user.php` (~45 seconds)
- Open the file, scroll to the pipeline section (~line 59)
- Show the four `exec()` calls in sequence
- Point out: PHP captures stdout from the Python script to get the `RAW_PAGE_ID` — this is how two separate processes talk to each other without a shared file or socket
- Point out: each subsequent step receives the ID from the step before it — the pipeline is a chain

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

---

## SECTION 4 — Closing (1 minute)

**Goal: summarise what was built, show self-awareness, end strong.**

- The project is a four-step pipeline: crawl, extract, score, compare — built on PHP, Python, MySQL, and the OpenAI API
- What you would add with more time: more portals beyond jensenestate.es, and enforcing the subscription plan in the UI (the table is already there)
- The project is honest about its limits: "first seen" is a crawl timestamp, not a legal claim, and the database only knows what the crawler has visited
- End with one concrete thing you learned — not abstract, but specific. For example: you originally tried ChromaDB for similarity matching, found it unreliable because agents rewrite descriptions entirely, and replaced it with direct GPT comparison — that decision taught you to test assumptions before building on them. Or: the two-table split was a decision you made early when you realised re-crawling to fix a bad AI parse would mean losing the original timestamp — understanding that consequence before it happened was a real planning moment. Use whichever is most true for you.

> The closing should sound like a developer reflecting on a real build, not a student summarising a school task.

---

## Likely Questions — Prepare Short Answers

**"How do you prevent SQL injection?"**
- All database queries use PDO with parameterized queries — user input is never concatenated into SQL strings, it is always passed as a bound parameter. The database driver handles escaping. This was the approach from the first query written, not something added later.

**"Why no frameworks?"**
- This is a school project and the goal was to understand what is actually happening at each layer — sessions, PDO, HTTP requests. A framework would have hidden that. For a production project the answer would be different, but for learning, building without a framework was the right call.

**"What does 'first seen' actually prove?"**
- It proves when our crawler first visited that URL — nothing more. It is a practical proxy for earliest known publication, but we cannot claim the listing did not exist before we crawled it.

**"Why PHP and Python together, not one language?"**
- PHP runs the web application and handles the HTTP requests. Python has better libraries for crawling — `requests`, `lxml` for HTML parsing. Each language does what it is better at.

**"How accurate is the AI extraction?"**
- Generally very good for structured fields like price, rooms, sqm. Less reliable for address and agent details when the HTML is messy. The raw data is always stored so it can be re-parsed if needed.

**"Why not use the portal's own API instead of crawling?"**
- Most real estate portals do not offer a public API. Crawling is the only way to get the data. The crawler identifies itself with a User-Agent string and was given permission by the site owner.

**"What would you change if you had more time?"**
- More portal support — the crawler currently only targets one site. The architecture supports adding more.
- Enforcing the subscription plan already in the database.
- A user-facing history of past searches.

**"Why is the duplicate threshold 10 and not a different number?"**
- There are actually two layers. The SQL query filters at 5 — it returns everything that scores 5 or above, casting a wide net. The UI then applies labels: "Very likely" at 10+, "Likely" at 7–9, "Possible" below that.
- The "no results" message in the UI says "no candidates scored 10 or higher" — that appears when nothing comes back from SQL at all, meaning nothing even reached 5. The wording is slightly misleading but the logic behind it is: if nothing scored 5 in SQL, nothing scored 10 either.
- The max possible score is 17. A score of 10 means a listing shares more than half the total possible matching weight — at that point it is a strong candidate. A matching reference ID alone is 5 points, so a shared reference ID plus price already hits 8, plus sqm hits 11 — "Very likely."
- Both numbers — 5 and 10 — are design choices, not mathematical formulas. 5 ensures borderline cases are visible; 10 is where confidence is high enough to call it a strong match.

**"Why not use a vector database for similarity matching?"**
- I tried ChromaDB. The problem is that agents rewrite descriptions entirely — the same property can have completely different wording on different portals. Vector similarity on rewritten text is unreliable. GPT comparing two descriptions directly is more accurate and costs less than $0.001 per call.
