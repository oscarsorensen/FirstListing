# Exam Guide — Markup Languages
5 questions · 18 minutes each
Remember for each question do:
   - 2.1. Introduction to the concept being presented
   - 2.2. Technical aspects (code, to put it simply)
   - 2.3. Overall use of what is being presented (what does it do for the end user)
   - 2.4. Conclusion
---

## Q1 — Recognizes the characteristics of markup languages by analyzing and interpreting code fragments.

**Video**
Open public/index.php in the editor on one side, the rendered homepage in the browser on the other. Scroll through and show how each tag creates something visible.

**What to explain**
HTML tags describe the structure and meaning of content — without them the browser shows a wall of unstyled text. Point to the document skeleton at the top of public/index.php: DOCTYPE, html, head (charset, viewport, title, link to stylesheet), body. Then point to the semantic tags in the body — header, nav, section, footer — these describe what the content is, not just how it looks. PHP runs on the server and generates the HTML dynamically; the browser never sees PHP.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/public/index.php

---

## Q2 — Uses markup languages for the transmission and presentation of information through the web, analyzing the structure of documents and identifying their elements.

**Video**
Split screen: public/user.php in the editor on one side, public/css/user.css on the other, browser showing the result. Point to a class name in the HTML, find the rule in the CSS, show the visual result.

**What to explain**
The HTML in public/user.php runs from line 184 to 438 — header with navigation, a form (method="post", input type="url", submit button), data cards, a results table, and a footer.

The CSS in public/css/user.css starts with custom properties on :root at lines 1–13 — colours and spacing stored as variables, changeable in one place. The class names in the HTML (tool-card, state-pill, score-badge) connect directly to rules in the CSS file.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/public/css/user.css

---

## Q3 — Accesses and manipulates web documents using client-side scripting languages.

**Video**
In the browser, click the ES/EN toggle and watch the text switch. Open DevTools > Application > Local Storage and show the lang key. Then open public/js/lang.js.

**What to explain**
lang.js handles the language toggle. The es object at lines 3–263 is a dictionary of every Spanish translation keyed by short IDs like "usr-h1". The English text lives in the HTML.

applyLang() at lines 277–292 does the swap — it loops over all elements with a lang-change attribute, looks up the key in es, and sets the text. If switching back to English it restores from a data-en attribute that was saved on page load by saveEnText().

The DOMContentLoaded listener at lines 295–311 ties it together: reads the saved language from localStorage, applies it, and wires the toggle button click.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/public/js/lang.js

---

## Q6 — Manages information in data interchange formats by analyzing and using storage technologies and query languages.

**Video**
Paste a URL into user.php, wait for the pipeline, watch the results table appear. Then show the SQL in find_duplicates.php and the PHP in user.php that renders it.

**What to explain**
Trace the data flow: the SELECT query in scripts/find_duplicates.php lines 47–79 produces rows, json_encode at line 101 converts them to a JSON string, PHP captures that string as the command's output.

In public/user.php, json_decode at lines 103–107 turns it back into a PHP array. The foreach at lines 353–392 loops over that array and builds an HTML table row by row — each value inserted into a td element.

The chain: SQL rows → json_encode → text → json_decode → PHP array → HTML table → browser.

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/scripts/find_duplicates.php

---

## Q7 — Operates enterprise information management systems performing import, integration, security and extraction tasks.

**Video**
Full live demo: log in, paste a real listing URL, watch the four-step pipeline run, show the results table with match scores and first seen timestamps.

**What to explain**
The business problem: the same property appears on multiple portals listed by different agents, and there is no easy way to know who published it first. FirstListing crawls portals, timestamps them, and lets an agent check whether a listing already exists in the database and who had it first.

The pipeline runs in public/user.php at lines 51–139: crawler fetches the URL, AI parser extracts structured fields, SQL scoring finds candidates, AI comparison checks descriptions. Point to the four run_cmd() calls and say what each one does for the user. (crawl, parse, sql comparison, description comparison)

**GitHub link**
https://github.com/oscarsorensen/FirstListing/blob/main/public/user.php
