## Matching note (Idealista reference)

If an agent uses the same **reference ID** on their own website and on Idealista,
we can use the **agent reference ID** as a high‑confidence match key **without crawling Idealista**.

Rule of thumb:
- If `reference_id` matches across two listings → treat as same property (very high confidence).
- If `reference_id` is missing or inconsistent → fallback to AI + vector similarity.

Important:
- Do not fetch or copy Idealista data.
- Only store the reference ID if it appears on the agent’s own site or is provided by the user.
