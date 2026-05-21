# Session Log — Exam Prep (2026-05-19)

## What we did

Oscar is preparing for the Proyecto Intermodular I presentation, scheduled 22 or 29 May. The presentation is 10 minutes, structured 1+4+4+1 (intro / app demo / code / closing). Going over is penalised.

We read the entire project thoroughly, then built the presentation guide from scratch over several rounds of review and critique.

## Files created

- `1_exams/project_overview.md` — complete technical breakdown of the project: stack, all 5 database tables, the 4-step pipeline explained file by file, all pages, admin area, security, design decisions. Use this to get up to speed on the project fast.
- `1_exams/presentation.md` — the final presentation guide. This is the main output of this session. See below for what it contains and the decisions behind it.

## What the presentation guide contains

- A red line statement at the top — the narrative thread to keep returning to
- A before-you-go-in checklist (browser on landing page logged out, four editor tabs ready, one-page printed outline)
- Section 1 (1 min): intro — problem, solution, tech stack, transition
- Section 2 (4 min): app demo — landing page → login page → paste URL → pipeline wait → extracted fields → candidates table → transition sentence
- Section 3 (4 min): code — schema (30s), user.php (45s), crawler_v4.py (45s), find_duplicates.php (45s)
- Section 4 (1 min): closing — summary, limitations, what would be added, concrete personal reflection
- Likely questions with prepared answers (8 questions)

## Key decisions made during the session and why

**Admin panel cut from the demo.** It was taking 45-50 seconds and the demo was running over. The core user flow (landing → login → paste URL → results) tells the complete story without it.

**Database schema shortened to 30 seconds.** Originally 1 minute with column definitions walked through. Cut to two sentences on the design decision and point at the table names. Column definitions are not what the panel cares about.

**Login page added to the demo.** Security (PDO, bcrypt, XSS) was completely absent from the original presentation. The login page is a natural 35-second stop that covers all of it without a detour.

**Crawler section cut to CrawlResult class only.** Originally showed both single-URL mode and the class. 45 seconds is not enough for two things. The class is the better story — it shows OOP and a concrete improvement during development.

**set_time_limit and search_usage tracking removed from user.php.** Minor implementation details that diluted the main point: four exec() calls chained together via stdout.

**[SEEN] case moved** from candidates table section to the paste URL section — that is where it actually appears in the UI.

**"from the admin panel" removed** from the crawler section — the admin panel was cut from the demo, so referencing it in the code section would invite questions about something not shown.

## Key factual issue discovered and fixed

The presentation originally said "threshold 10" in the demo and showed `HAVING match_score >= 5` in the code — two different numbers with no explanation. This would have tripped Oscar up if a panel member noticed.

The correct explanation: SQL filters at 5 (wide net), the UI applies labels on top ("Very likely" at 10+, "Likely" at 7-9, "Possible" below that). Both the demo section and the find_duplicates.php stop now explain this explicitly. The threshold question in the likely questions section also gives the full two-layer answer.

## Current state

The presentation guide is considered complete and exam-ready. Oscar has not yet done a timed run-through at home — that is the next step. Estimated runtime is 9 to 9.5 minutes.

Oscar still needs to:
- Pick a specific demo URL that produces at least one duplicate candidate with an AI verdict, and test it
- Do a full timed run-through out loud
- Print a one-page bullet outline to bring into the exam
