<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/*
AI-only parser:
1) Read raw_pages
2) Ask model to extract fields (priority: HTML -> TEXT -> JSON-LD)
3) Upsert into ai_listings
*/

$OLLAMA_URL = 'http://localhost:11434/api/generate';
$MODEL = 'qwen2.5:7b-instruct';
$LIMIT = 1;
$FORCE = false;
$RAW_ID = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $LIMIT = max(1, (int)substr($arg, 8));
    } elseif (str_starts_with($arg, '--model=')) {
        $MODEL = trim((string)substr($arg, 8));
    } elseif (str_starts_with($arg, '--id=')) {
        $RAW_ID = max(1, (int)substr($arg, 5));
    } elseif ($arg === '--force') {
        $FORCE = true;
    }
}

function scalar_text($v): ?string
{
    if ($v === null) {
        return null;
    }
    if (is_string($v) || is_int($v) || is_float($v) || is_bool($v)) {
        $t = trim((string)$v);
        return $t === '' ? null : $t;
    }
    if (is_array($v)) {
        $parts = [];
        foreach ($v as $item) {
            $s = scalar_text($item);
            if ($s !== null) {
                $parts[] = $s;
            }
        }
        if (!$parts) {
            return null;
        }
        return trim(implode(', ', $parts));
    }
    return null;
}

function txt_or_none($v): string
{
    $s = scalar_text($v);
    return $s === null ? 'None' : $s;
}

function int_or_null($v): ?int
{
    $s = scalar_text($v);
    if ($s === null) return null;
    if (is_numeric($s)) return (int)$s;
    $n = preg_replace('/[^0-9]/', '', $s) ?? '';
    return $n === '' ? null : (int)$n;
}

function pick_first(array $source, array $keys)
{
    foreach ($keys as $k) {
        if (array_key_exists($k, $source) && $source[$k] !== null && $source[$k] !== '') {
            return $source[$k];
        }
    }
    return null;
}

function decode_json_response(string $responseText): ?array
{
    $txt = trim($responseText);
    $txt = preg_replace('/^```json\s*/i', '', $txt) ?? $txt;
    $txt = preg_replace('/^```\s*/', '', $txt) ?? $txt;
    $txt = preg_replace('/\s*```$/', '', $txt) ?? $txt;

    $arr = json_decode($txt, true);
    if (is_array($arr)) {
        return $arr;
    }

    $start = strpos($txt, '{');
    $end = strrpos($txt, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $slice = substr($txt, $start, $end - $start + 1);
        $arr = json_decode($slice, true);
        if (is_array($arr)) {
            return $arr;
        }
    }

    return null;
}

function parse_kv_fallback(string $responseText): array
{
    $out = [];
    $lines = preg_split('/\R/u', trim($responseText)) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || !str_contains($line, ':')) continue;
        [$k, $v] = array_pad(explode(':', $line, 2), 2, '');
        $k = strtolower(trim($k));
        $v = trim($v);
        if ($k !== '') $out[$k] = $v;
    }
    return $out;
}

function call_ollama_extract(string $url, string $model, string $listingUrl, string $html, ?string &$error = null): ?array
{
    $error = null;

    $prompt = <<<PROMPT
You extract real-estate fields from raw property page HTML.

STRICT SOURCE RULES
Use ONLY visible content from the PRIMARY property block.

IGNORE completely:

Similar properties

Featured listings

Sliders

Carousels

Related listings

Footer

Header

Navigation menus

Modals

Contact forms

Hidden inputs

Script tags

Style tags

JSON-LD

Meta tags (unless a field is missing from visible body)

PRIMARY PROPERTY DEFINITION
If multiple properties exist in the HTML:
Select the property where the MAIN TITLE and MAIN PRICE appear together.
All extracted fields must belong to that same property block.
The reference_id must belong to the same block as the selected price.

DESCRIPTION RULES

Prefer the full visible description inside the main property-description block.

If both meta description and visible description exist, use the visible one.

Choose the longest complete visible description.

Do NOT truncate.

Do NOT summarize.

NUMERIC RULES

price: integer only.
Remove currency symbols.
Remove thousand separators.
Example: "495.000€" → 495000

sqm: built/interior/living area only.
Do NOT use plot area.

plot_sqm: land/plot area only.

rooms: bedrooms count only.

bathrooms: bathrooms count only.

Never combine numbers from different blocks.

If multiple numbers exist, select the one nearest to its label.

LISTING TYPE

"sale" if resale / for sale / venta

"rent" if rental / alquiler

Otherwise null

ADDRESS

Extract human-readable location only (street/city/zone).

Do not extract script fragments.

MISSING DATA
If a field is not clearly found in the primary property block, return null.

OUTPUT RULES
Return exactly ONE single JSON object.
No markdown.
No explanation.
No extra text.
No arrays.
No nested objects.

Use exactly these keys:

{
"title": null,
"description": null,
"price": null,
"sqm": null,
"rooms": null,
"bathrooms": null,
"plot_sqm": null,
"property_type": null,
"listing_type": null,
"address": null,
"reference_id": null,
"agent_name": null,
"agent_phone": null,
"agent_email": null
}

INPUT HTML FOLLOWS:
PROMPT;



    $payload = [
        'model' => $model,
        'prompt' => $prompt,
        'stream' => false,
        'format' => 'json',
        'options' => [
            'temperature' => 0.0,
            'num_predict' => 520,
            'num_gpu' => 99,
            'num_thread' => 8,
            'num_ctx' => 4096
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

    $response = curl_exec($ch);
    if ($response === false) {
        $error = 'cURL error: ' . curl_error($ch);
        return null;
    }

    $env = json_decode($response, true);
    if (!is_array($env) || !isset($env['response'])) {
        $error = 'Invalid Ollama envelope. Raw: ' . mb_substr((string)$response, 0, 500, 'UTF-8');
        return null;
    }

    $rawText = (string)$env['response'];
    $parsed = decode_json_response($rawText);
    if (!is_array($parsed)) {
        $parsed = parse_kv_fallback($rawText);
    }
    if (!$parsed) {
        $error = 'Model response was not parsable JSON/KV text. Raw model text: ' . mb_substr($rawText, 0, 700, 'UTF-8');
        return null;
    }
    return $parsed;
}

$pdo->exec('USE test2firstlisting');

$q = "SELECT rp.id, rp.url, rp.html_raw, rp.text_raw, rp.jsonld_raw
      FROM raw_pages rp
      LEFT JOIN ai_listings ai ON ai.id = rp.id";

$params = [];
if ($RAW_ID !== null) {
    $q .= " WHERE rp.id = :raw_id";
    $params[':raw_id'] = $RAW_ID;
} elseif (!$FORCE) {
    $q .= " WHERE ai.id IS NULL OR rp.fetched_at > ai.updated_at";
}

$q .= " ORDER BY rp.fetched_at DESC
      LIMIT :lim";

$st = $pdo->prepare($q);
$paramType = PDO::PARAM_INT;
foreach ($params as $k => $v) {
    $st->bindValue($k, $v, $paramType);
}
$st->bindValue(':lim', $LIMIT, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "No new or updated rows.\n";
    exit(0);
}

echo "Processing " . count($rows) . " rows with model {$MODEL}...\n";

foreach ($rows as $row) {
    $id = (int)$row['id'];
    $listingUrl = (string)$row['url'];
    $html = (string)($row['html_raw'] ?? '');

    $err = null;
    $ai = call_ollama_extract($OLLAMA_URL, $MODEL, $listingUrl, $html, $err);
    if (!is_array($ai)) {
        echo "[raw_page_id={$id}] AI extraction failed";
        if ($err !== null) {
            echo " - {$err}";
        }
        echo "\n";
        continue;
    }

    $title = txt_or_none(pick_first($ai, ['title', 'name', 'property_title']));
    $description = txt_or_none(pick_first($ai, ['description', 'desc', 'full_description']));
    $price = int_or_null(pick_first($ai, ['price', 'price_eur', 'price_amount', 'price_value', 'prize']));
    $sqm = int_or_null(pick_first($ai, ['sqm', 'built_sqm', 'built_area', 'constructed_area', 'floor_size']));
    $rooms = int_or_null(pick_first($ai, ['rooms', 'bedrooms', 'beds', 'habitaciones']));
    $bathrooms = int_or_null(pick_first($ai, ['bathrooms', 'baths', 'bath', 'banos', 'baños']));
    $plotSqm = int_or_null(pick_first($ai, ['plot_sqm', 'plot', 'plot_area', 'land_area', 'parcel_sqm']));
    $propertyType = txt_or_none(pick_first($ai, ['property_type', 'type', 'property']));
    $listingType = txt_or_none(pick_first($ai, ['listing_type', 'operation', 'offer_type']));
    $address = txt_or_none(pick_first($ai, ['address', 'location', 'street_address']));
    $referenceId = txt_or_none(pick_first($ai, ['reference_id', 'reference', 'ref', 'property_id', 'codigo', 'code']));
    $agentName = txt_or_none(pick_first($ai, ['agent_name', 'agent', 'agency', 'agency_name', 'broker']));
    $agentPhone = txt_or_none(pick_first($ai, ['agent_phone', 'phone', 'telephone', 'contact_phone']));
    $agentEmail = txt_or_none(pick_first($ai, ['agent_email', 'email', 'contact_email']));

    // Ensure only one row per raw page ID
    $del = $pdo->prepare('DELETE FROM ai_listings WHERE raw_page_id = :rid_raw AND id <> :rid_id');
    $del->execute([':rid_raw' => $id, ':rid_id' => $id]);

    $upsert = "INSERT INTO ai_listings
        (id, raw_page_id, title, description, price, sqm, rooms, bathrooms, plot_sqm,
         property_type, listing_type, address, reference_id, agent_name, agent_phone, agent_email,
         created_at, updated_at)
        VALUES
        (:id, :raw_page_id, :title, :description, :price, :sqm, :rooms, :bathrooms, :plot_sqm,
         :property_type, :listing_type, :address, :reference_id, :agent_name, :agent_phone, :agent_email,
         NOW(), NOW())
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
        $ins = $pdo->prepare($upsert);
        $ins->execute([
            ':id' => $id,
            ':raw_page_id' => $id,
            ':title' => $title,
            ':description' => $description,
            ':price' => $price,
            ':sqm' => $sqm,
            ':rooms' => $rooms,
            ':bathrooms' => $bathrooms,
            ':plot_sqm' => $plotSqm,
            ':property_type' => $propertyType,
            ':listing_type' => $listingType,
            ':address' => $address,
            ':reference_id' => $referenceId,
            ':agent_name' => $agentName,
            ':agent_phone' => $agentPhone,
            ':agent_email' => $agentEmail,
        ]);
        echo "[raw_page_id={$id}] Organized and stored\n";
    } catch (Throwable $e) {
        echo "[raw_page_id={$id}] DB insert error: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";
