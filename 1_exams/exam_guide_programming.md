# Exam Guide — Programming
8 questions · 20 minutes each
Remember for each question do:
   - 2.1. Introduction to the concept being presented
   - 2.2. Technical aspects (code, to put it simply)
   - 2.3. Overall use of what is being presented (what does it do for the end user)
   - 2.4. Conclusion
   - make sure the very first sentence names the concept — "A control structure is..." or "Object-oriented programming is..."
---

## Q1 — Recognizes the structure of a computer program, identifying and relating the elements of the programming language used.
RA1. Reconoce la estructura de un programa informático, identificando y relacionando los elementos propios del lenguaje de programación utilizado.
**Video**
Open python/crawler_v4.py and scroll slowly through the whole file, pausing at each section.

**What to explain**
One sentence each for every piece of code.
1. Imports — lines 1–13 — the libraries the program depends on (standard library first, then third-party like requests and mysql.connector).
2. Config/Constants — lines 15–38 — everything you might need to change (DB credentials, URLs, delays) is separated at the top, not buried in logic.
3. CrawlResult class — lines 155–171 — the one data structure, bundles the seven values from a crawled page into a single object.
4. run() function — lines 227–363 — the main logic; from here you can see it calling the helper and database functions defined earlier in the file.
5. Entry point — line 366 — the `if __name__ == "__main__"` guard means run() only executes when the file is run directly, not when imported. Basically, means Start.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/python/crawler_v4.py

---

## Q2 — Writes and tests simple programs, recognizing and applying the fundamentals of object-oriented programming.
RA2. Escribe y prueba programas sencillos, reconociendo y aplicando los fundamentos de la programación orientada a objetos.
Q2 = WHAT the class does and that it works — constructor, self, to_dict, test run.

**Video**
Open python/crawler_v4.py at the CrawlResult class (line 155). Then switch to user.php in the browser, paste a URL, run it, and show the duplicate results appearing. That is the test — the CrawlResult object is what carries the data through the whole pipeline.

**What to explain**
1. Class definition — lines 155–171 — `__init__` is the constructor, runs when you create a new CrawlResult and stores the 7 values using `self`. `to_dict()` converts the object to a plain dict so it can be serialized.
2. Object created and used — lines 259–267 — one object carries all data through the pipeline. Passed to `insert_listing()` at line 270 and `log_crawl_event()` at line 276 — instead of passing 7 separate variables.
3. Testing — submitting a URL in user.php and seeing the duplicate results appear confirms the class was created, populated, and passed correctly through the whole pipeline.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/python/crawler_v4.py

---

## Q3 — Writes and debugs code, analyzing and using the control structures of the language.
RA3. Escribe y depura código, analizando y utilizando las estructuras de control del lenguaje.

**Video**
Open python/crawler_v4.py and scroll through run() starting at line 227. Pause at the if/else, the for loop, and the try/except.

**What to explain**
1. if/else — lines 232–292 — the whole function splits on whether `--url=` was given. That branch determines the entire behaviour of the program.
2. for loop — lines 310–357 — iterates over every URL from the sitemap. Inside, another if/else skips URLs already in the database.
3. try/except — lines 269–288 — catches IntegrityError when the UNIQUE constraint fails, calls `conn.rollback()`, then runs a recovery SELECT to find the existing row ID.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/python/crawler_v4.py

---

## Q4 — Develops programs organized in classes, analyzing and applying the principles of object-oriented programming.
RA4. Desarrolla programas organizados en clases analizando y aplicando los principios de la programación orientada a objetos.
Q4 = WHY the class was designed that way — encapsulation, one parameter instead of seven.

**Video**
Open python/crawler_v4.py at the CrawlResult class (line 155). Then show insert_listing() at line 211 receiving the object, and log_crawl_event() at line 176 calling result.to_dict(). Show the class being used the 3 places in the code, not just defined.

**What to explain**
1. Encapsulation — CrawlResult at lines 155–171 bundles 7 related values into one object. Before this class, those 7 values were separate arguments passed between every function.
2. Class in use — insert_listing(cur, result) at lines 211–222 takes one parameter instead of seven. log_crawl_event() at line 176 calls result.to_dict() to serialize it for the log file.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/python/crawler_v4.py

---

## Q5 — Performs input and output operations, using language-specific procedures and class libraries.
RA5. Realiza operaciones de entrada y salida de información, utilizando procedimientos específicos del lenguaje y librerías de clases.

**Video**
Open python/crawler_v4.py and scroll through read_single_url() at line 141, then line 274 (print RAW_PAGE_ID), then log_crawl_event() at line 176. Then open data/crawl_log.jsonl and show the data.

**What to explain**
1. Input — read_single_url() at lines 141–145 loops over sys.argv[1:] and returns the value after `--url=`. Standard Python way of reading command-line arguments.
2. Output to stdout — line 274 prints `RAW_PAGE_ID:N`. PHP captures this with exec() and reads it with preg_match in user.php lines 60–73. That is the communication protocol between Python and PHP.
3. File output — log_crawl_event() at lines 176–182 opens the JSONL file in append mode `"a"` and writes one JSON line per crawl event.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/python/crawler_v4.py

---

## Q6 — Writes programs that manipulate information by selecting and using advanced data types.
RA6. Escribe programas que manipulen información seleccionando y utilizando tipos avanzados de datos.

**Video**
Open python/crawler_v4.py and show DB_CONFIG and the jsonld_items list. Then open scripts/find_duplicates.php and show the $candidates array and json_encode.

**What to explain**
1. Dict — a dictionary stores data as key-value pairs, like `"host": "localhost"`. DB_CONFIG at lines 22–29 is a dict with all the database connection settings — it gets passed directly into mysql.connector.connect(). Point to it and say: this is a dict, and the whole thing is passed as one argument to the connect function.
2. List — a list is an ordered collection you can add to. jsonld_items at line 67 starts empty, then extend() and append() fill it as JSON-LD script tags are found in the page HTML. Once the list is complete, json.dumps() at line 81 converts it to a string so it can be stored in the database. Point to the empty list, then the append, then the json.dumps — that is the lifecycle of a list in this program. This shows that the program manipulates select information and uses advanced datatypes.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/python/crawler_v4.py

---

## Q8 — Uses object-oriented databases, analyzing their characteristics and applying techniques to maintain data persistence.
RA8. Utiliza bases de datos orientadas a objetos, analizando sus características y aplicando técnicas para mantener la persistencia de la información. - me sirve log en texto, no tiene por qué ser Mongo - propongo jsonl para registros

**Video**
Open data/crawl_log.jsonl and show its contents — flat file, one JSON object per line. Then open python/crawler_v4.py and show log_crawl_event() and CrawlResult.to_dict().

**What to explain**
1. What JSONL is — MongoDB stores records as JSON documents with no fixed schema. JSONL is the same concept as a flat file: each line is a self-contained JSON object, schema-less, serialized from a Python object. The teacher approved this for the project.
2. How it works — log_crawl_event() at lines 176–182 calls result.to_dict() to get the base dict, adds action and timestamp, then writes one JSON line in append mode. Append-only means nothing is ever deleted — every crawl event is preserved.
3. The contrast — raw_pages is relational: fixed schema, SQL queries, constraints. crawl_log.jsonl is non-relational: no schema, readable with any text tool, always writable.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/python/crawler_v4.py

---

## Q9 — Manages information stored in databases while maintaining data integrity and consistency.
RA9. Gestiona información almacenada en bases de datos manteniendo la integridad y consistencia de los datos.

**Video**
Open data/sql/test2firstlisting.sql and show the UNIQUE KEY and FOREIGN KEY lines. Then open python/crawler_v4.py and show the try/except IntegrityError block.

**What to explain**
1. Schema constraints — data/sql/test2firstlisting.sql line 22: UNIQUE KEY on the url column means MySQL rejects any duplicate URL at the database level. Lines 50–52: FOREIGN KEY on ai_listings with ON DELETE CASCADE — deleting a raw_page automatically deletes its ai_listing.
2. Error handling — python/crawler_v4.py lines 269–288: when INSERT fails on the UNIQUE constraint, IntegrityError is caught, conn.rollback() undoes the failed transaction, then a recovery SELECT finds the existing row ID so the pipeline continues cleanly.
3. Parameterized queries — config/db.php line 14: PDO::ATTR_EMULATE_PREPARES = false forces real prepared statements. Values are never concatenated into SQL strings — prevents SQL injection.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/data/sql/test2firstlisting.sql
