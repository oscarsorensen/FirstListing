# Legacy Files (Not Active)

Disse filer er flyttet ud af den aktive kodebase for at holde projektet mere overskueligt.

## Flyttet fra `python/`
- `crawler_v1.py`
- `crawler_v2.py`
- `crawler_v3.py`

Begrundelse: Ældre crawler-versioner. `crawler_v4.py` er den nyeste aktive version.

## Flyttet fra `scripts/`
- `ai_parse_claude_v1.php`
- `ai_parse_clause_v2.php`
- `ai_parse_raw_pages.php`
- `ai_parse_v2.php`
- `test.html`

Begrundelse: Tidligere AI-parse forsøg og testfiler, ikke i aktiv drift lige nu.

## Aktivt lige nu
- `python/admin_openai_chat.py` (OpenAI bridge til admin testpanel)
- `python/openai_html_extract.py` (enkel OpenAI extraction test)
- `python/crawler_v4.py` (seneste crawler)

- `admin_openai_chat.py` (old admin bridge, replaced by `public/openai_test.php`)

- `openai_html_extract.py` (standalone HTML extraction test, not used in active app flow)
