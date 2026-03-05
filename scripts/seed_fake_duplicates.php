<?php

declare(strict_types=1);

// One-time script — inserts 5 fake listings into raw_pages + ai_listings.
// Each fake is a copy of a real jensenestate.es listing but with a different
// domain and URL, to simulate the same property published on another portal.
//
// Run once: php scripts/seed_fake_duplicates.php
// Safe to re-run — skips any URL that already exists.

require_once __DIR__ . '/../config/db.php';

// Each entry = [fake_url, fake_domain, original_raw_page_id]
$fakes = [
    ['https://www.idealista.es/inmueble/fake-NBH-95679/',  'www.idealista.es',   115],
    ['https://www.fotocasa.es/es/inmueble/fake-NBH-43257/', 'www.fotocasa.es',    93],
    ['https://www.habitaclia.com/inmueble/fake-NBH-54359/', 'www.habitaclia.com', 94],
    ['https://www.pisos.com/inmueble/fake-NBH-41009/',      'www.pisos.com',      104],
    ['https://www.kyero.com/es/inmueble/fake-NBH-31007/',   'www.kyero.com',      105],
];

// Query to fetch all fields we need to copy from the original ai_listing
$fetch = $pdo->prepare(
    'SELECT title, property_type, listing_type, description, price, currency,
            sqm, plot_sqm, rooms, bathrooms, address, reference_id
     FROM ai_listings
     WHERE raw_page_id = :id
     LIMIT 1'
);

// Check if a URL is already in raw_pages (so we can skip it safely)
$check = $pdo->prepare('SELECT id FROM raw_pages WHERE url = :url LIMIT 1');

$insert_raw = $pdo->prepare(
    'INSERT INTO raw_pages (url, domain, first_seen_at, fetched_at, http_status, content_type)
     VALUES (:url, :domain, NOW(), NOW(), 200, :ct)'
);

$insert_ai = $pdo->prepare(
    'INSERT INTO ai_listings
        (raw_page_id, title, property_type, listing_type, description, price, currency,
         sqm, plot_sqm, rooms, bathrooms, address, reference_id, created_at, updated_at)
     VALUES
        (:raw_page_id, :title, :property_type, :listing_type, :description, :price, :currency,
         :sqm, :plot_sqm, :rooms, :bathrooms, :address, :reference_id, NOW(), NOW())'
);

foreach ($fakes as [$url, $domain, $original_id]) {

    // Skip if this fake URL is already in the database
    $check->execute([':url' => $url]);
    if ($check->fetchColumn()) {
        echo "[SKIPPED]  $url (already exists)\n";
        continue;
    }

    // Fetch the original listing's fields
    $fetch->execute([':id' => $original_id]);
    $orig = $fetch->fetch(PDO::FETCH_ASSOC);

    if (!$orig) {
        echo "[ERROR]    Could not find ai_listing for raw_page_id=$original_id\n";
        continue;
    }

    // Insert the fake row into raw_pages
    $insert_raw->execute([
        ':url'    => $url,
        ':domain' => $domain,
        ':ct'     => 'text/html; charset=utf-8',
    ]);
    $new_raw_id = (int)$pdo->lastInsertId();

    // Insert the matching fake row into ai_listings with the same field values
    $insert_ai->execute([
        ':raw_page_id'   => $new_raw_id,
        ':title'         => $orig['title'],
        ':property_type' => $orig['property_type'],
        ':listing_type'  => $orig['listing_type'],
        ':description'   => $orig['description'],
        ':price'         => $orig['price'],
        ':currency'      => $orig['currency'],
        ':sqm'           => $orig['sqm'],
        ':plot_sqm'      => $orig['plot_sqm'],
        ':rooms'         => $orig['rooms'],
        ':bathrooms'     => $orig['bathrooms'],
        ':address'       => $orig['address'],
        ':reference_id'  => $orig['reference_id'],
    ]);

    echo "[INSERTED] $url (raw_page_id=$new_raw_id, copied from $original_id)\n";
}

echo "\nDone.\n";
