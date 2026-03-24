# Exam Guide — Programming
8 questions · 22.5 minutes each
Remember for each question do:
   - 2.1. Introduction to the concept being presented
   - 2.2. Technical aspects (code, to put it simply)
   - 2.3. Overall use of what is being presented (what does it do for the end user)
   - 2.4. Conclusion
---

## Q1 — Recognizes the structure of a computer program, identifying and relating the elements of the programming language used.

**Video**
Open python/crawler_v4.py and scroll slowly through the whole file, pausing at each section.

**What to explain**
Point to each section in order and name it. Imports at lines 1–13 — standard library first, then third-party. Constants and configuration at lines 15–38 — everything you might need to change is at the top. Helper functions at lines 44–145 — small, single-purpose. The CrawlResult class at lines 152–168. Database functions at lines 173–219. The run() function at lines 224–360 — the main logic. The entry point at lines 363–364 — the if __name__ == "__main__" guard means run() only executes when the file is run directly, not when imported.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/python/crawler_v4.py

---

## Q2 — Writes and tests simple programs, recognizing and applying the fundamentals of object-oriented programming.

**Video**
Open python/crawler_v4.py at the CrawlResult class (line 152), then scroll to line 256 to show where it is instantiated. Show both the definition and the usage.

**What to explain**
The class definition at lines 152–168: __init__ is the constructor — it runs when you create a new CrawlResult object and stores the seven parameters as instance variables using self. to_dict() is a method that converts the object to a plain dictionary, needed because json.dumps() cannot serialize a custom object directly.

The object is created at lines 256–264 and then passed to insert_listing() at line 267 and log_crawl_event() at line 273 — one object carries all the data through the pipeline instead of passing seven separate variables.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/python/crawler_v4.py

---

## Q3 — Writes and debugs code, analyzing and using the control structures of the language.

**Video**
Open python/crawler_v4.py and scroll through run() starting at line 224. Highlight the if/else branch, the for loop, and the try/except block.

**What to explain**
if/else at lines 229–289 — the entire function splits on whether --url= was given. The branch determines the whole behaviour of the program.

for loop at lines 307–354 — iterates over every URL from the sitemap. Inside, another if/else skips URLs already in the database.

try/except at lines 266–285 — catches the IntegrityError when a UNIQUE constraint fails, calls conn.rollback() to undo the failed transaction, then does a recovery SELECT to find the existing row ID.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/python/crawler_v4.py

---

## Q4 — Develops programs organized in classes, analyzing and applying the principles of object-oriented programming.

**Video**
Open python/crawler_v4.py and show the CrawlResult class. Then show insert_listing() at line 208 receiving the object, and log_crawl_event() at line 173 calling result.to_dict(). Show the class being used, not just defined.

**What to explain**
CrawlResult at lines 152–168 bundles seven related values into one object — this is encapsulation. Before this class, those seven values were passed as separate arguments between functions.

insert_listing(cur, result) at lines 208–219 accesses result.url, result.domain, and so on — the function signature is one parameter instead of seven. log_crawl_event() at line 173 calls result.to_dict() to get the dict it needs before writing to the log file.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/python/crawler_v4.py

---

## Q5 — Performs input and output operations, using language-specific procedures and class libraries.

**Video**
Run the crawler from the terminal with a --url= argument. Show the printed output (FETCHING, INSERTED, RAW_PAGE_ID:N). Then open data/crawl_log.jsonl to show the file that was written.

**What to explain**
Input from sys.argv — read_single_url() at lines 141–145 loops over sys.argv[1:] and returns the value after --url=. This is Python's standard way of reading command-line arguments.

Output to stdout — line 271 prints RAW_PAGE_ID:N. PHP captures this with exec() and scans for that line using preg_match in public/user.php at lines 60–73. That is the communication protocol between Python and PHP.

File output — log_crawl_event() at lines 173–179 opens the JSONL file in append mode ("a") and writes one JSON line per crawl event.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/python/crawler_v4.py

---

## Q6 — Writes programs that manipulate information by selecting and using advanced data types.

**Video**
Open python/crawler_v4.py and show DB_CONFIG and the jsonld_items list. Then open scripts/find_duplicates.php and show the $candidates array and json_encode.

**What to explain**
Python dicts — DB_CONFIG at lines 22–29 is passed directly to mysql.connector.connect. HEADERS at line 31 is used for every HTTP request.

Python lists and JSON — jsonld_items starts empty at line 67 and is built up with extend() and append() as JSON-LD script tags are found in the HTML. json.dumps() at line 81 converts the list to a string for storing in the database. json.loads() at line 74 parses embedded JSON strings.

PHP arrays — in scripts/find_duplicates.php, fetchAll(PDO::FETCH_ASSOC) at line 93 returns an array of associative arrays (each row is an array keyed by column name). json_encode() at line 101 converts it for output.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/python/crawler_v4.py

---

## Q8 — Uses object-oriented databases, analyzing their characteristics and applying techniques to maintain data persistence.

**Video**
Open data/crawl_log.jsonl and show its contents — flat file, one JSON object per line. Then open python/crawler_v4.py and show log_crawl_event() and CrawlResult.to_dict().

**What to explain**
The JSONL file is the non-relational store. Each line is a self-contained JSON object with no fixed schema. It is append-only — nothing is ever updated or deleted, which guarantees persistence of every event.

There are two log functions. log_crawl_event() at lines 173–179 fires on new insertions — it calls result.to_dict() to get the base dict, adds action, raw_page_id, and timestamp, then writes one JSON line. log_seen_event() at lines 183–189 fires when a URL was already in the database — it logs url, action: "seen", raw_page_id, and timestamp. Both write to the same file, so the JSONL records every URL the crawler touches, not just new ones. CrawlResult.to_dict() at lines 163–168 is what makes the object serializable for log_crawl_event.

The contrast: raw_pages is relational — fixed schema, SQL queries, constraint checks. crawl_log.jsonl is non-relational — no schema, readable with any text tool, always writable.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/python/crawler_v4.py

---

## Q9 — Manages information stored in databases while maintaining data integrity and consistency.

**Video**
Open data/sql/test2firstlisting.sql and show the UNIQUE KEY and FOREIGN KEY lines. Then open python/crawler_v4.py and show the try/except IntegrityError block.

**What to explain**
Constraints in data/sql/test2firstlisting.sql — line 22 defines UNIQUE KEY on the url column of raw_pages, so MySQL rejects any duplicate URL at the database level. Lines 50–52 define the foreign key on ai_listings with ON DELETE CASCADE — deleting a raw_page automatically deletes its ai_listing.

Integrity error handling in python/crawler_v4.py at lines 266–285 — when the INSERT fails because of the UNIQUE constraint, the except block catches IntegrityError, calls conn.rollback() to undo the failed transaction, and runs a recovery SELECT to continue cleanly.

Parameterized queries across all PHP scripts — in config/db.php line 14, PDO::ATTR_EMULATE_PREPARES is false, which forces real prepared statements. Values are bound with placeholders, never concatenated into SQL strings.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/data/sql/test2firstlisting.sql
