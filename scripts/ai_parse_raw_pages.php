<?php

declare(strict_types=1);
error_reporting(E_ALL & ~E_DEPRECATED);

/*
Simple parser (no AI):
1) Read raw_pages
2) Extract from jsonld_raw, text_raw, html_raw
3) Insert/update ai_listings
*/

require_once __DIR__ . '/../config/db.php';

$LIMIT = 1;

function txt_or_none(?string $v): string
{
    $v = trim((string)$v);
    return $v === '' ? 'None' : $v;
}

function to_int_or_null($v): ?int
{
    if ($v === null) {
        return null;
    }
    if (is_int($v)) {
        return $v;
    }
    if (is_float($v)) {
        return (int)$v;
    }
    $s = trim((string)$v);
    if ($s === '') {
        return null;
    }
    $s = preg_replace('/[^0-9]/', '', $s) ?? '';
    if ($s === '') {
        return null;
    }
    return (int)$s;
}

function first_regex(?string $text, string $pattern): ?string
{
    if (!$text) {
        return null;
    }
    if (preg_match($pattern, $text, $m)) {
        return trim((string)$m[1]);
    }
    return null;
}

function jsonld_items(?string $jsonldRaw): array
{
    if (!$jsonldRaw) {
        return [];
    }
    $d = json_decode($jsonldRaw, true);
    if (!is_array($d)) {
        return [];
    }
    if (array_keys($d) !== range(0, count($d) - 1)) {
        return [$d];
    }
    return $d;
}

function get_jsonld_value(array $items, array $keys)
{
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        foreach ($keys as $k) {
            if (array_key_exists($k, $item) && $item[$k] !== null && $item[$k] !== '') {
                return $item[$k];
            }
        }
        if (isset($item['offers']) && is_array($item['offers'])) {
            foreach ($keys as $k) {
                if (isset($item['offers'][$k]) && $item['offers'][$k] !== '') {
                    return $item['offers'][$k];
                }
            }
        }
    }
    return null;
}

function listing_type_from_text(string $text): string
{
    $t = mb_strtolower($text, 'UTF-8');
    if (str_contains($t, 'rent') || str_contains($t, 'alquiler')) {
        return 'rent';
    }
    if (str_contains($t, 'sale') || str_contains($t, 'venta') || str_contains($t, 'resale')) {
        return 'sale';
    }
    return 'None';
}

function property_type_from_text(string $text): string
{
    $t = mb_strtolower($text, 'UTF-8');
    if (str_contains($t, 'villa')) return 'Villa';
    if (str_contains($t, 'apartment')) return 'Apartment';
    if (str_contains($t, 'duplex')) return 'Duplex';
    if (str_contains($t, 'bungalow')) return 'Bungalow';
    if (str_contains($t, 'townhouse')) return 'Townhouse';
    return 'None';
}

$pdo->exec('USE test2firstlisting');

$sql = "SELECT rp.id, rp.url, rp.html_raw, rp.text_raw, rp.jsonld_raw
        FROM raw_pages rp
        LEFT JOIN ai_listings ai ON ai.id = rp.id
        WHERE ai.id IS NULL OR rp.fetched_at > ai.updated_at
        ORDER BY rp.fetched_at DESC
        LIMIT :lim";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':lim', $LIMIT, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "No new or updated raw pages to organize.\n";
    exit;
}

echo "Processing " . count($rows) . " rows...\n";

foreach ($rows as $row) {
    $rawPageId = (int)$row['id'];
    $url = (string)$row['url'];
    $html = (string)($row['html_raw'] ?? '');
    $text = (string)($row['text_raw'] ?? '');
    $jsonld = (string)($row['jsonld_raw'] ?? '');
    $all = $html . "\n" . $text . "\n" . $jsonld;

    $items = jsonld_items($jsonld);

    $title = get_jsonld_value($items, ['name', 'headline']);
    if (!$title) {
        $title = first_regex($html, '/<meta[^>]+property="og:title"[^>]+content="([^"]+)"/i');
    }

    $description = get_jsonld_value($items, ['description']);
    if (!$description) {
        $description = first_regex($html, '/<meta[^>]+property="og:description"[^>]+content="([^"]+)"/i');
    }

    $price = to_int_or_null(get_jsonld_value($items, ['price']));
    if ($price === null) {
        $price = to_int_or_null(first_regex($all, '/(?:€|eur)\s*([0-9\.\, ]{4,})/i'));
    }

    $sqm = to_int_or_null(get_jsonld_value($items, ['floorSize', 'area', 'size']));
    if ($sqm === null) {
        $sqm = to_int_or_null(first_regex($all, '/([0-9]{2,4})\s*(?:m²|m2|sqm)/iu'));
    }

    $rooms = to_int_or_null(get_jsonld_value($items, ['numberOfRooms', 'bedrooms']));
    if ($rooms === null) {
        $rooms = to_int_or_null(first_regex($all, '/(?:rooms?|bedrooms?|dormitorios?)\D{0,10}([0-9]{1,2})/iu'));
    }

    $bathrooms = to_int_or_null(get_jsonld_value($items, ['numberOfBathroomsTotal', 'bathrooms']));
    if ($bathrooms === null) {
        $bathrooms = to_int_or_null(first_regex($all, '/(?:bathrooms?|baños?)\D{0,10}([0-9]{1,2})/iu'));
    }

    $plotSqm = to_int_or_null(first_regex($all, '/(?:plot|parcela)\D{0,20}([0-9]{2,4})\s*(?:m²|m2|sqm)/iu'));

    $address = null;
    foreach ($items as $it) {
        if (isset($it['address']) && is_array($it['address'])) {
            $parts = [
                $it['address']['streetAddress'] ?? null,
                $it['address']['addressLocality'] ?? null,
                $it['address']['addressRegion'] ?? null,
            ];
            $parts = array_values(array_filter($parts, fn($x) => is_string($x) && trim($x) !== ''));
            if ($parts) {
                $address = implode(', ', $parts);
                break;
            }
        }
    }

    $referenceId = first_regex($all, '/(?:reference|ref|id)\s*[:#]?\s*([A-Z0-9\-]{4,})/i');
    $agentName = get_jsonld_value($items, ['name']);
    $agentPhone = first_regex($all, '/(?:\+?\d[\d \-]{7,}\d)/');
    $agentEmail = first_regex($all, '/([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})/i');

    $propertyType = property_type_from_text((string)($title . ' ' . $text));
    $listingType = listing_type_from_text((string)($title . ' ' . $text));

    // Remove old duplicate rows for same raw_page_id
    $del = $pdo->prepare('DELETE FROM ai_listings WHERE raw_page_id = :rid AND id <> :rid');
    $del->execute([':rid' => $rawPageId]);

    $insert = "INSERT INTO ai_listings
        (id, raw_page_id, title, description, price, sqm, rooms, bathrooms, plot_sqm,
         property_type, listing_type, address, reference_id,
         agent_name, agent_phone, agent_email, created_at, updated_at)
        VALUES
        (:id, :raw_page_id, :title, :description, :price, :sqm, :rooms, :bathrooms, :plot_sqm,
         :property_type, :listing_type, :address, :reference_id,
         :agent_name, :agent_phone, :agent_email, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            description = VALUES(description),
            price = VALUES(price),
            sqm = VALUES(sqm),
            rooms = VALUES(rooms),
            bathrooms = VALUES(bathrooms),
            plot_sqm = VALUES(plot_sqm),
            property_type = VALUES(property_type),
            listing_type = VALUES(listing_type),
            address = VALUES(address),
            reference_id = VALUES(reference_id),
            agent_name = VALUES(agent_name),
            agent_phone = VALUES(agent_phone),
            agent_email = VALUES(agent_email),
            updated_at = NOW()";

    try {
        $ins = $pdo->prepare($insert);
        $ins->execute([
            ':id' => $rawPageId,
            ':raw_page_id' => $rawPageId,
            ':title' => txt_or_none(is_string($title) ? $title : null),
            ':description' => txt_or_none(is_string($description) ? $description : null),
            ':price' => $price,
            ':sqm' => $sqm,
            ':rooms' => $rooms,
            ':bathrooms' => $bathrooms,
            ':plot_sqm' => $plotSqm,
            ':property_type' => txt_or_none($propertyType),
            ':listing_type' => txt_or_none($listingType),
            ':address' => txt_or_none(is_string($address) ? $address : null),
            ':reference_id' => txt_or_none(is_string($referenceId) ? $referenceId : null),
            ':agent_name' => txt_or_none(is_string($agentName) ? $agentName : null),
            ':agent_phone' => txt_or_none(is_string($agentPhone) ? $agentPhone : null),
            ':agent_email' => txt_or_none(is_string($agentEmail) ? $agentEmail : null),
        ]);
        echo "[raw_page_id={$rawPageId}] Organized in ai_listings\n";
    } catch (Throwable $e) {
        echo "[raw_page_id={$rawPageId}] DB insert error: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";

