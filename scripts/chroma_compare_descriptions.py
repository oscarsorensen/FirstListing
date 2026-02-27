# ChromaDB-based description similarity search.
#
# This script is READY but not yet connected to the UI.
# It is an alternative to ai_compare_descriptions.php.
# ChromaDB stores text as vector embeddings locally — no API tokens used.
#
# To activate it later:
#   1. Run --mode=index once to build the local vector index from ai_listings.
#   2. Replace the ai_compare_descriptions.php call in user.php with a call to this script:
#      python3 scripts/chroma_compare_descriptions.py --mode=query --raw-id=N
#
# Install dependencies (only once):
#   pip install chromadb mysql-connector-python
#
# The first --mode=index run downloads a small embedding model (~70MB) automatically.
#
# Usage:
#   Index all descriptions from the database:
#     python3 scripts/chroma_compare_descriptions.py --mode=index
#
#   Find similar listings for a specific raw_page_id:
#     python3 scripts/chroma_compare_descriptions.py --mode=query --raw-id=N
#
# Output: JSON printed to stdout (same format as ai_compare_descriptions.php,
#         but uses "similarity" score 0.0–1.0 instead of same_property/confidence).

import json
import sys

import chromadb
import mysql.connector

# Path where ChromaDB stores its local vector index (relative to project root).
# Run this script from the project root directory.
CHROMA_PATH     = "./chromadb_data"
COLLECTION_NAME = "listing_descriptions"

DB_CONFIG = {
    "host":        "localhost",
    "user":        "firstlisting_user",
    "password":    "girafferharlangehalse",
    "database":    "test2firstlisting",
    "charset":     "utf8mb4",
    "use_unicode": True,
}


# Read --mode=, --raw-id=, and --n-results= from CLI arguments
def read_args():
    mode      = None
    raw_id    = None
    n_results = 10

    for arg in sys.argv[1:]:
        if arg.startswith("--mode="):
            mode = arg.split("=", 1)[1].strip()
        elif arg.startswith("--raw-id="):
            raw_id = int(arg.split("=", 1)[1])
        elif arg.startswith("--n-results="):
            n_results = int(arg.split("=", 1)[1])

    return mode, raw_id, n_results


# Connect to ChromaDB using a local persistent storage folder.
# Uses cosine similarity so that similar descriptions score close to 1.0.
def get_chroma_collection():
    client = chromadb.PersistentClient(path=CHROMA_PATH)
    return client.get_or_create_collection(
        COLLECTION_NAME,
        # cosine distance: 0.0 = identical, 2.0 = completely different
        metadata={"hnsw:space": "cosine"},
    )


# Index mode — fetch all descriptions from ai_listings and store them in ChromaDB.
# Run this once after crawling, or again whenever new listings are added.
# ChromaDB's built-in embedding model converts the text to vectors automatically.
# upsert() means it updates existing entries and adds new ones (safe to re-run).
def index_all(collection):
    conn = mysql.connector.connect(**DB_CONFIG)
    cur  = conn.cursor(dictionary=True)

    cur.execute(
        "SELECT raw_page_id, title, description FROM ai_listings WHERE description IS NOT NULL"
    )
    rows = cur.fetchall()

    cur.close()
    conn.close()

    if not rows:
        print(json.dumps({"error": "No descriptions found in ai_listings"}))
        sys.exit(1)

    # ChromaDB requires IDs to be strings
    ids       = [str(r["raw_page_id"]) for r in rows]
    documents = [r["description"]       for r in rows]
    metadatas = [
        {"raw_page_id": r["raw_page_id"], "title": r["title"] or ""}
        for r in rows
    ]

    # Insert in batches to avoid memory issues with large datasets
    BATCH_SIZE = 100
    for i in range(0, len(ids), BATCH_SIZE):
        collection.upsert(
            ids       = ids[i : i + BATCH_SIZE],
            documents = documents[i : i + BATCH_SIZE],
            metadatas = metadatas[i : i + BATCH_SIZE],
        )
        print(f"Indexed {min(i + BATCH_SIZE, len(ids))} / {len(ids)} descriptions")

    print(json.dumps({"indexed": len(ids)}))


# Query mode — find the most similar descriptions to the one from the given raw_page_id.
# ChromaDB returns cosine distances; we convert to similarity scores (0.0 – 1.0).
# A similarity of 0.9+ usually means the descriptions are about the same property.
def query_similar(collection, raw_id: int, n_results: int):
    conn = mysql.connector.connect(**DB_CONFIG)
    cur  = conn.cursor(dictionary=True)

    cur.execute(
        "SELECT description FROM ai_listings WHERE raw_page_id = %s LIMIT 1",
        (raw_id,)
    )
    row = cur.fetchone()

    cur.close()
    conn.close()

    if not row or not row["description"]:
        print(json.dumps({"error": f"No description found for raw_page_id {raw_id}"}))
        sys.exit(1)

    base_description = row["description"]

    # Fetch a few extra results so we can filter out the base listing itself if it appears
    results = collection.query(
        query_texts=[base_description],
        n_results=n_results + 1,
    )

    ids       = results["ids"][0]
    distances = results["distances"][0]
    metadatas = results["metadatas"][0]

    matches = []
    for chroma_id, distance, meta in zip(ids, distances, metadatas):
        # Skip the base listing if it was indexed and appears in its own results
        if int(chroma_id) == raw_id:
            continue

        # Convert cosine distance to a 0–1 similarity score.
        # Cosine distance range: 0.0 (identical) to 2.0 (completely opposite).
        # Similarity = 1 - (distance / 2), so identical = 1.0, opposite = 0.0.
        similarity = round(1.0 - (distance / 2.0), 4)

        matches.append({
            "raw_page_id": int(chroma_id),
            "title":       meta.get("title", ""),
            "similarity":  similarity,
            "distance":    round(distance, 4),
        })

    # Trim to the requested number of results
    print(json.dumps(matches[:n_results], ensure_ascii=False, indent=2))


def run():
    mode, raw_id, n_results = read_args()

    if mode not in ("index", "query"):
        print(json.dumps({"error": "Use --mode=index or --mode=query --raw-id=N"}))
        sys.exit(1)

    collection = get_chroma_collection()

    if mode == "index":
        index_all(collection)
    elif mode == "query":
        if raw_id is None:
            print(json.dumps({"error": "Use --raw-id=N with --mode=query"}))
            sys.exit(1)
        query_similar(collection, raw_id, n_results)


if __name__ == "__main__":
    run()
