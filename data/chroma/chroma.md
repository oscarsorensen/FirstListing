ChromaDB-design (v1) til FirstListing

Mål: kun semantisk lighed. SQL er truth. ChromaDB er regenererbar cache.

Collection-struktur
Jeg foreslår 2 collections (du kan starte med kun 1):

A) listings_text_v1
Bruges til title+description (primær).

B) listings_image_v1 (valgfri senere)
Bruges til billed-embeddings eller billed-fingerprints, hvis du ender der.

ID-strategi
Brug altid SQL’s listing-id som reference.

Chroma “id”: listing:<sql_listing_id>:text:v1
Eksempel: listing:42:text:v1

(Det gør det nemt at opdatere/regen uden collisions.)

Dokument (document) som embeddes
I listings_text_v1 embedder du ét samlet felt:

document = normaliseret tekststreng:

title + newline + description

evt. tilføj “area_text, sqm, rooms” som tekst (valgfrit), men kun hvis du vil hjælpe modellen – jeg ville starte uden.

Eksempel:
Title: ...
Description: ...
(ikke pris)

Metadata (minimal)
Metadata er kun til filtrering/performance, ikke til “truth”.

Jeg ville holde metadata ekstremt lille:

listing_id: int (du har den også i id, men rart til filter)

domain: string (til at filtrere portal vs agent)

source_type: string (“agent”, “portal”, “other”) hvis du allerede har det i SQL

lang: string (“es”, “en”, “unknown”) hvis du kan detektere simpelt

text_hash: string (sha1/xxhash af normaliseret tekst) til hurtig “identisk tekst”-match uden embedding

updated_at: iso string (kun for at vide om embedding er stale)

Ikke gem:

price, sqm, rooms som “business truth”

timestamps som “published”

noget du ikke kan regen

Query-strategi (hvordan du bruger den)
Du bruger næsten altid Chroma sådan her:

SQL laver grovfilter (område/sqm/rooms) → får en liste af candidate listing_ids

Chroma laver similarity kun indenfor candidates (hvis du vil), ellers global top-k og så SQL check bagefter

To måder:

A) Global top-k: query embeddings → få top 20–50 → hent fra SQL → filtrer hårdt

B) Candidate-restricted: query → filter metadata listing_id in (...) (Chroma understøtter metadata filtering; “in” kan være begrænset, så batch hvis nødvendigt)

Versionering
Indbyg versions i collection-navn eller i id-suffix:

collection: listings_text_v1

når du ændrer normalisering/model → lav listings_text_v2

Så kan du regen uden at ødelægge gamle.

Minimum du skal implementere i v1
Kun dette:

collection: listings_text_v1

id: listing:<id>:text:v1

document: title+description

metadata: listing_id, domain, source_type, text_hash

Det er nok.

Hvis du vil, skriver jeg næste: SQL-design (tabeller + felter) der matcher dette 1:1, inkl. “embedding status”/regen-flag.