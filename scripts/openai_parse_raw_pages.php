<?php

declare(strict_types=1);

// Connect to the database
require_once __DIR__ . '/../config/db.php';

// Default settings
$MODEL = 'gpt-4.1-mini';
$LIMIT = 1;
$RAW_ID = null;
$FORCE = false;

// Read CLI arguments: --limit=N, --id=N, --force
// CLI = Command Line Interface: run this script from the terminal with extra options.
// Example: php openai_parse_raw_pages.php --limit=10 --force
// Here i tell the parser what to do, how many rows to process, if i want to force re-parse etc.
// It is used in the admin panel with Parse Selected
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        // How many rows to process in one run
        $LIMIT = max(1, (int)substr($arg, 8));
    } elseif (str_starts_with($arg, '--id=')) {
        // Process only one specific row by its raw_pages ID
        $RAW_ID = max(1, (int)substr($arg, 5));
    } elseif ($arg === '--force') {
        // Re-parse all rows, even ones already processed
        $FORCE = true;
    }
}

// Read the OpenAI API key from the .env file
function read_api_key(string $envPath): string
{
    $env = @parse_ini_file($envPath);
    if (!is_array($env)) {
        return '';
    }
    return trim((string)($env['OPENAI_API_KEY'] ?? ''), "\"'");
}

// Prepare the raw HTML for the AI by extracting only the most useful parts:
// the page title, h1/h2 headings, and any lines containing real-estate keywords.
// Plain text is already stored in text_raw by the crawler (higher quality than
// strip_tags), so there is no need to re-extract it here.
function preprocess_html(string $html): string
{
    if ($html === '') return '';

    // Remove comments, scripts, styles and extra whitespace
    $html = preg_replace('/<!--.*?-->/s', ' ', $html) ?? $html;
    $html = preg_replace('/<(script|style|noscript|svg)[^>]*>.*?<\/\\1>/is', ' ', $html) ?? $html;
    $html = preg_replace('/\s+/u', ' ', $html) ?? $html;

    $snippets = [];

    // Extract the page title
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
        $snippets[] = 'TITLE: ' . trim(strip_tags($m[1]));
    }

    // Extract the first 4 headings (h1, h2)
    if (preg_match_all('/<h[1-2][^>]*>(.*?)<\/h[1-2]>/is', $html, $hm)) {
        foreach (array_slice($hm[1], 0, 4) as $h) {
            $snippets[] = 'HEADER: ' . trim(strip_tags($h));
        }
    }

    // Extract lines that contain real-estate keywords like price, sqm, rooms etc.
    $keywords = '(price|precio|€|eur|m²|sqm|bed|room|bath|baño|bano|reference|ref|agent|agency|phone|email|address|location)';
    if (preg_match_all('/[^<>]{0,120}' . $keywords . '[^<>]{0,120}/iu', $html, $km)) {
        foreach (array_slice($km[0], 0, 80) as $line) {
            $clean = trim(strip_tags($line));
            if ($clean !== '') {
                $snippets[] = $clean;
            }
        }
    }

    // Deduplicate and join, then trim to stay within token limits
    $joined = implode("\n", array_unique($snippets));
    return "TARGETED_SNIPPETS:\n" . mb_substr($joined, 0, 9000, 'UTF-8');
}

// Try to find the full property description text directly in the HTML.
// This is sent to the AI as the primary source for the "description" field
// so it doesn't summarise or rewrite the text.
function extract_description_candidates(string $html): string
{
    if ($html === '') return '';

    $candidates = [];

    // Check the <meta name="description"> tag first
    if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']/is', $html, $m)) {
        $meta = trim(html_entity_decode(strip_tags((string)$m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($meta !== '') {
            $candidates[] = $meta;
        }
    }

    // Then look for divs/sections with description-related class names
    if (preg_match_all('/<(section|div|article|p)[^>]*(description|descripcion|property-description|listing-description)[^>]*>(.*?)<\/\\1>/is', $html, $m)) {
        foreach ($m[3] as $chunk) {
            $txt = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string)$chunk), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
            if ($txt !== '' && mb_strlen($txt, 'UTF-8') > 80) {
                $candidates[] = $txt;
            }
        }
    }

    // Sort by length (longest first) and remove duplicates
    usort($candidates, function ($a, $b) {
        return mb_strlen((string)$b, 'UTF-8') <=> mb_strlen((string)$a, 'UTF-8');
    });
    $candidates = array_values(array_unique($candidates));

    return mb_substr(implode("\n\n", array_slice($candidates, 0, 5)), 0, 12000, 'UTF-8');
}

// Clean and trim the plain text version of the page
function preprocess_text(string $text): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    return mb_substr($text, 0, 10000, 'UTF-8');
}

// Clean and trim the JSON-LD data — used as a last fallback source
function preprocess_jsonld(string $jsonld): string
{
    $jsonld = trim($jsonld);
    return mb_substr($jsonld, 0, 2500, 'UTF-8');
}

// Try to parse a JSON response from the model.
// We use response_format: json_object (see call_openai_extract), so the API always
// returns plain JSON. The fallback below handles any unexpected edge cases.
function decode_json_response(string $content): ?array
{
    $txt = trim($content);
    $arr = json_decode($txt, true);
    if (is_array($arr)) return $arr;

    // Fallback: try to extract just the JSON object from the response
    $start = strpos($txt, '{');
    $end = strrpos($txt, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $slice = substr($txt, $start, $end - $start + 1);
        $arr = json_decode($slice, true);
        if (is_array($arr)) return $arr;
    }
    return null;
}

// Convert a value to an integer, or null if it's empty or not numeric
function to_int_or_null($v): ?int
{
    if ($v === null || $v === '') return null;
    if (is_numeric($v)) return (int)$v;
    $n = preg_replace('/[^0-9]/', '', (string)$v) ?? '';
    return $n === '' ? null : (int)$n;
}

// Convert a value to a string, or null if it's empty or literally "none"/"null"
function to_text_or_null($v): ?string
{
    if ($v === null || $v === '') return null;
    $s = trim((string)$v);
    if (in_array(strtolower($s), ['none', 'null'], true)) return null;
    return $s;
}

// Send the prepared data to OpenAI and return the extracted fields as an array.
// Returns null if the request fails or the model returns invalid data.
function call_openai_extract(string $apiKey, string $model, string $url, string $descriptionCandidates, string $htmlSnippet, string $textSnippet, string $jsonldSnippet, ?string &$error = null): ?array
{
    $error = null;

    // The prompt tells the model exactly what to extract and in what format
    $prompt = <<<PROMPT
Extract real-estate fields from the provided source snippets.
Priority: DESCRIPTION_CANDIDATES first, then HTML_SNIPPETS, then TEXT_SNIPPET, then JSONLD_SNIPPET.
Do not invent values. If unknown return null.

Return ONLY valid JSON with these keys:
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

Rules:
- price/sqm/rooms/bathrooms/plot_sqm must be integers or null
- listing_type must be "sale", "rent", or null
- description must be copied from source text (no summary, no rewrite)
- if DESCRIPTION_CANDIDATES has text, use that as primary source for description
- rooms = bedrooms count
- bathrooms = bathrooms count
- sqm = built/interior/living area
- plot_sqm/plot size = land/plot area
- reference_id = ref/code/id for the listing

URL:
{$url}

DESCRIPTION_CANDIDATES:
{$descriptionCandidates}

HTML_SNIPPETS:
{$htmlSnippet}

TEXT_SNIPPET:
{$textSnippet}

JSONLD_SNIPPET:
{$jsonldSnippet}
PROMPT;

    // Build the request payload
    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'You are a strict data extractor.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.0,
        'response_format' => ['type' => 'json_object'],
    ];

    // Send the request to OpenAI using cURL
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);

    $response = curl_exec($ch);
    if ($response === false) {
        $error = 'Request error: ' . curl_error($ch);
        return null;
    }

    // Check for HTTP errors
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $body = json_decode((string)$response, true);
    if ($httpCode >= 400) {
        $error = (string)($body['error']['message'] ?? ('HTTP ' . $httpCode));
        return null;
    }

    // Extract the model's reply text
    $content = (string)($body['choices'][0]['message']['content'] ?? '');
    if ($content === '') {
        $error = 'Empty model response.';
        return null;
    }

    // Parse the JSON from the reply
    $parsed = decode_json_response($content);
    if (!is_array($parsed)) {
        $error = 'Model did not return valid JSON.';
        return null;
    }

    return $parsed;
}

// Load the API key — stop if it's missing
$apiKey = read_api_key(__DIR__ . '/../.env');
if ($apiKey === '') {
    fwrite(STDERR, "Missing OPENAI_API_KEY in .env\n");
    exit(1);
}

// Select the correct database
$pdo->exec('USE test2firstlisting');

// Build the query to select raw pages that haven't been parsed yet (or all if --force)
$sql = "SELECT rp.id, rp.url, rp.html_raw, rp.text_raw, rp.jsonld_raw
        FROM raw_pages rp
        LEFT JOIN ai_listings ai ON ai.raw_page_id = rp.id";
$params = [];
if ($RAW_ID !== null) {
    // Parse a specific row by ID
    $sql .= " WHERE rp.id = :raw_id";
    $params[':raw_id'] = $RAW_ID;
} elseif (!$FORCE) {
    // Only parse rows that have no AI result yet, or where the page was re-fetched
    $sql .= " WHERE ai.id IS NULL OR rp.fetched_at > ai.updated_at";
}
$sql .= " ORDER BY rp.fetched_at DESC LIMIT :lim";

$st = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $st->bindValue($k, $v, PDO::PARAM_INT);
}
$st->bindValue(':lim', $LIMIT, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "No new or updated rows.\n";
    exit(0);
}

echo "Processing " . count($rows) . " row(s) with {$MODEL}...\n";

// Loop through each row, send it to OpenAI, and save the result to ai_listings
foreach ($rows as $row) {
    $id = (int)$row['id'];
    $url = (string)$row['url'];

    // Prepare the four input blocks sent to the model
    $descriptionCandidates = extract_description_candidates((string)($row['html_raw'] ?? ''));
    $htmlSnippet            = preprocess_html((string)($row['html_raw'] ?? ''));
    $textSnippet            = preprocess_text((string)($row['text_raw'] ?? ''));
    $jsonldSnippet          = preprocess_jsonld((string)($row['jsonld_raw'] ?? ''));

    // Call OpenAI and get the extracted fields
    $err = null;
    $ai = call_openai_extract($apiKey, $MODEL, $url, $descriptionCandidates, $htmlSnippet, $textSnippet, $jsonldSnippet, $err);
    if (!is_array($ai)) {
        echo "[raw_page_id={$id}] Failed: {$err}\n";
        continue;
    }

    // Convert each field to the correct type (int or string), or null if missing
    $title        = to_text_or_null($ai['title'] ?? null);
    $description  = to_text_or_null($ai['description'] ?? null);
    $price        = to_int_or_null($ai['price'] ?? null);
    $sqm          = to_int_or_null($ai['sqm'] ?? null);
    $rooms        = to_int_or_null($ai['rooms'] ?? null);
    $bathrooms    = to_int_or_null($ai['bathrooms'] ?? null);
    $plotSqm      = to_int_or_null($ai['plot_sqm'] ?? null);
    $propertyType = to_text_or_null($ai['property_type'] ?? null);
    $listingType  = to_text_or_null($ai['listing_type'] ?? null);
    $address      = to_text_or_null($ai['address'] ?? null);
    $referenceId  = to_text_or_null($ai['reference_id'] ?? null);
    $agentName    = to_text_or_null($ai['agent_name'] ?? null);
    $agentPhone   = to_text_or_null($ai['agent_phone'] ?? null);
    $agentEmail   = to_text_or_null($ai['agent_email'] ?? null);

    // Insert or update the AI result in ai_listings
    $upsert = "INSERT INTO ai_listings
        (id, raw_page_id, title, property_type, listing_type, description, price, currency, sqm, plot_sqm, rooms, bathrooms,
         address, reference_id, agent_name, agent_phone, agent_email, created_at, updated_at)
        VALUES
        (:id, :raw_page_id, :title, :property_type, :listing_type, :description, :price, :currency, :sqm, :plot_sqm, :rooms, :bathrooms,
         :address, :reference_id, :agent_name, :agent_phone, :agent_email, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            raw_page_id = VALUES(raw_page_id),
            title = VALUES(title),
            property_type = VALUES(property_type),
            listing_type = VALUES(listing_type),
            description = VALUES(description),
            price = VALUES(price),
            sqm = VALUES(sqm),
            plot_sqm = VALUES(plot_sqm),
            rooms = VALUES(rooms),
            bathrooms = VALUES(bathrooms),
            address = VALUES(address),
            reference_id = VALUES(reference_id),
            agent_name = VALUES(agent_name),
            agent_phone = VALUES(agent_phone),
            agent_email = VALUES(agent_email),
            updated_at = NOW()";

    $ins = $pdo->prepare($upsert);
    $ins->execute([
        ':id'            => $id,
        ':raw_page_id'   => $id,
        ':title'         => $title,
        ':property_type' => $propertyType,
        ':listing_type'  => $listingType,
        ':description'   => $description,
        ':price'         => $price,
        ':currency'      => 'EUR',
        ':sqm'           => $sqm,
        ':plot_sqm'      => $plotSqm,
        ':rooms'         => $rooms,
        ':bathrooms'     => $bathrooms,
        ':address'       => $address,
        ':reference_id'  => $referenceId,
        ':agent_name'    => $agentName,
        ':agent_phone'   => $agentPhone,
        ':agent_email'   => $agentEmail,
    ]);

    echo "[raw_page_id={$id}] Parsed and saved\n";
}

echo "Done.\n";
