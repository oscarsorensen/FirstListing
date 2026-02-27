<?php

declare(strict_types=1);

// CLI script — called from user.php after a listing has been crawled and parsed.
// Usage: php find_duplicates.php --raw-id=N
//
// Fetches the ai_listing for that raw_page_id, then scores every other
// ai_listing in the database by how many fields match.
// Outputs a JSON array of candidate rows (each with a match_score field).

require_once __DIR__ . '/../config/db.php';

// Read --raw-id=N from the command-line arguments
$RAW_ID = null;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--raw-id=')) {
        $RAW_ID = max(1, (int)substr($arg, 9));
    }
}

if ($RAW_ID === null) {
    fwrite(STDERR, "Usage: php find_duplicates.php --raw-id=N\n");
    exit(1);
}

// Fetch the base listing we are comparing against
$st = $pdo->prepare(
    "SELECT raw_page_id, title, price, sqm, rooms, bathrooms,
            property_type, listing_type, address, reference_id, description
     FROM ai_listings
     WHERE raw_page_id = :id
     LIMIT 1"
);
$st->execute([':id' => $RAW_ID]);
$base = $st->fetch(PDO::FETCH_ASSOC);

if (!$base) {
    // Parsing has not run yet, or failed
    echo json_encode(['error' => 'No parsed listing found for raw_page_id ' . $RAW_ID]);
    exit(1);
}

// Score each candidate by how many fields match the base listing.
// reference_id=5, price=3, sqm=3, rooms=2, bathrooms=2, property_type=1, listing_type=1.
// HAVING >= 3 filters out very weak matches. Excludes the base listing itself.
$sql = "
    SELECT
        ai.id,
        ai.raw_page_id,
        ai.title,
        ai.price,
        ai.sqm,
        ai.rooms,
        ai.bathrooms,
        ai.property_type,
        ai.listing_type,
        ai.address,
        ai.reference_id,
        ai.description,
        rp.url,
        rp.domain,
        rp.first_seen_at,
        (
            IF(ai.reference_id IS NOT NULL AND ai.reference_id != '' AND ai.reference_id = :ref,   5, 0) +
            IF(ai.price        IS NOT NULL AND ai.price        = :price,                           3, 0) +
            IF(ai.sqm          IS NOT NULL AND ai.sqm          = :sqm,                             3, 0) +
            IF(ai.rooms        IS NOT NULL AND ai.rooms        = :rooms,                           2, 0) +
            IF(ai.bathrooms    IS NOT NULL AND ai.bathrooms    = :bathrooms,                       2, 0) +
            IF(ai.property_type IS NOT NULL AND ai.property_type = :property_type,                1, 0) +
            IF(ai.listing_type  IS NOT NULL AND ai.listing_type  = :listing_type,                 1, 0)
        ) AS match_score
    FROM ai_listings ai
    JOIN raw_pages rp ON rp.id = ai.raw_page_id
    WHERE ai.raw_page_id != :exclude
    HAVING match_score >= 3
    ORDER BY match_score DESC
    LIMIT 20
";

$st2 = $pdo->prepare($sql);
$st2->execute([
    ':ref'           => (string)($base['reference_id'] ?? ''),
    ':price'         => $base['price'],
    ':sqm'           => $base['sqm'],
    ':rooms'         => $base['rooms'],
    ':bathrooms'     => $base['bathrooms'],
    ':property_type' => $base['property_type'],
    ':listing_type'  => $base['listing_type'],
    ':exclude'       => $RAW_ID,
]);

$candidates = $st2->fetchAll(PDO::FETCH_ASSOC);

// Cast match_score to int so the JSON output is clean
foreach ($candidates as &$row) {
    $row['match_score'] = (int)$row['match_score'];
}
unset($row);

echo json_encode($candidates, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
