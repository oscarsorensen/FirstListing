# OpenAI API Setup (FirstListing)

Denne guide gør din opsætning klar til at bruge en stærkere model til HTML-parsing.

## 1) Installer dependencies

```bash
cd "/opt/homebrew/var/www/Projects/Project FirstListing"
python3 -m pip install -r python/requirements.txt
```

## 2) Sæt API key som miljøvariabel

Brug standard-navnet `OPENAI_API_KEY`:

```bash
export OPENAI_API_KEY="din_nøgle_her"
```

Hvis du vil bruge samme navn som i klassen, virker scriptet også med:

```bash
export OPENAIAPI="din_nøgle_her"
```

## 3) Kør parser-template på en HTML-fil

```bash
python3 python/openai_html_extract.py \
  --html-file /sti/til/listing.html \
  --out /tmp/listing_extracted.json \
  --model gpt-5.2
```

## 4) Hvad scriptet returnerer

Scriptet returnerer JSON med disse felter:

- `title`
- `description`
- `price`
- `sqm`
- `rooms`
- `baths`
- `plot_sqm`
- `type`
- `listing`
- `address`
- `reference`
- `agent`
- `phone`
- `email`

Hvis et felt ikke findes i HTML, bliver det `null`.

## 5) Vigtigt (sikkerhed)

- Commit aldrig din key i Git.
- Hold dig til miljøvariabler (`OPENAI_API_KEY` / `OPENAIAPI`).
- `.env` og `*.env` er tilføjet i `.gitignore`.
