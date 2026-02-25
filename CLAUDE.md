# Project Instructions for Claude

## About This Project

**FirstListing** is a school project (MVP) built by a first-year Desarrollo Aplicaciones Web (DAW) student.

The developer's name is **Oscar**.

## Code Style & Complexity Rules

- **Keep it simple.** This is a school project — code must be readable and understandable by an advanced first-year DAW student.
- Avoid over-engineering. No unnecessary abstractions, design patterns, or frameworks.
- Prefer clear, explicit code over clever or compact code.
- Always use `#` comments (Python) or `//` comments (PHP/JS) placed **above** the code block they describe — never docstrings or inline comments at the end of a line unless very short
- Do not introduce new libraries or tools without a good reason and explicit approval.

## Tech Stack

- **Backend:** PHP 7.4+, Python 3
- **Frontend:** HTML5, CSS3, vanilla JavaScript — no frameworks
- **Database:** MySQL / MariaDB
- **AI:** OpenAI GPT-4.1-mini (via API)
- **Vector DB:** ChromaDB (planned, not yet implemented)

## Project Context

- This is a school MVP, not a production system
- Target: identify the original publisher of a real estate listing by matching it across multiple portals
- Currently crawls one site: `jensenestate.es`
- Pipeline: crawl → AI extract → duplicate detection (planned)

## General Instructions

- Always read relevant files before suggesting or making changes
- Ask before introducing new dependencies or making architectural changes
- Keep database queries simple and use PDO with parameterized queries
- Never commit `.env` files or hardcoded credentials
- **If a change affects other files**, always tell the user which files are affected and confirm they want those updated too before making any changes
