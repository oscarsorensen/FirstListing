<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/*
Simple OpenAI parser for school MVP:
1) Read raw_pages
2) Preprocess HTML/Text/JSON-LD to reduce tokens
3) Ask OpenAI for structured JSON
4) Upsert into ai_listings
*/

$MODEL = 'gpt-4.1-mini';
$LIMIT = 1;
$RAW_ID = null;
$FORCE = false;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $LIMIT = max(1, (int)substr($arg, 8));
    } elseif (str_starts_with($arg, '--id=')) {
        $RAW_ID = max(1, (int)substr($arg, 5));
    } elseif ($arg === '--force') {
        $FORCE = true;
    } elseif (str_starts_with($arg, '--model=')) {
        $MODEL = trim((string)substr($arg, 8));
    }
}

function load_env_file(string $path): void
{
    if (!is_file($path) || !is_readable($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) return;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$name, $value] = array_map('trim', explode('=', $line, 2));
        if ($name === '') continue;
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

function get_api_key(): string
{
    return (string)(getenv('OPENAI_API_KEY') ?: getenv('OPENAIAPI') ?: '');
}

function preprocess_html(string $html): string
{
    if ($html === '') return '';

    $html = preg_replace('/<!--.*?-->/s', ' ', $html) ?? $html;
    $html = preg_replace('/<(script|style|noscript|svg)[^>]*>.*?<\/\\1>/is', ' ', $html) ?? $html;
    $html = preg_replace('/\s+/u', ' ', $html) ?? $html;

    $snippets = [];

    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
        $snippets[] = 'TITLE: ' . trim(strip_tags($m[1]));
    }
    if (preg_match_all('/<h[1-2][^>]*>(.*?)<\/h[1-2]>/is', $html, $hm)) {
        foreach (array_slice($hm[1], 0, 4) as $h) {
            $snippets[] = 'HEADER: ' . trim(strip_tags($h));
        }
    }

    $keywords = '(price|precio|€|eur|m²|sqm|bed|room|bath|baño|bano|reference|ref|agent|agency|phone|email|address|location)';
    if (preg_match_all('/[^<>]{0,120}' . $keywords . '[^<>]{0,120}/iu', $html, $km)) {
        foreach (array_slice($km[0], 0, 80) as $line) {
            $clean = trim(strip_tags($line));
            if ($clean !== '') {
                $snippets[] = $clean;
            }
        }
    }

    $joined = implode("\n", array_unique($snippets));
    $mainText = trim(strip_tags($html));
    $mainText = preg_replace('/\s+/u', ' ', $mainText) ?? $mainText;

    $out = "TARGETED_SNIPPETS:\n" . mb_substr($joined, 0, 9000, 'UTF-8')
        . "\n\nMAIN_TEXT:\n" . mb_substr($mainText, 0, 18000, 'UTF-8');
    return $out;
}

function extract_description_candidates(string $html): string
{
    if ($html === '') return '';

    $candidates = [];

    if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']/is', $html, $m)) {
        $meta = trim(html_entity_decode(strip_tags((string)$m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($meta !== '') {
            $candidates[] = $meta;
        }
    }

    if (preg_match_all('/<(section|div|article|p)[^>]*(description|descripcion|property-description|listing-description)[^>]*>(.*?)<\/\\1>/is', $html, $m)) {
        foreach ($m[3] as $chunk) {
            $txt = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string)$chunk), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
            if ($txt !== '' && mb_strlen($txt, 'UTF-8') > 80) {
                $candidates[] = $txt;
            }
        }
    }

    // Keep longest relevant description blocks first.
    usort($candidates, function ($a, $b) {
        return mb_strlen((string)$b, 'UTF-8') <=> mb_strlen((string)$a, 'UTF-8');
    });
    $candidates = array_values(array_unique($candidates));

    return mb_substr(implode("\n\n", array_slice($candidates, 0, 5)), 0, 12000, 'UTF-8');
}

function preprocess_text(string $text): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    return mb_substr($text, 0, 3000, 'UTF-8');
}

function preprocess_jsonld(string $jsonld): string
{
    $jsonld = trim($jsonld);
    return mb_substr($jsonld, 0, 2500, 'UTF-8');
}

function decode_json_response(string $content): ?array
{
    $txt = trim($content);
    $txt = preg_replace('/^```json\s*/i', '', $txt) ?? $txt;
    $txt = preg_replace('/^```\s*/', '', $txt) ?? $txt;
    $txt = preg_replace('/\s*```$/', '', $txt) ?? $txt;
    $arr = json_decode($txt, true);
    if (is_array($arr)) return $arr;
    $start = strpos($txt, '{');
    $end = strrpos($txt, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $slice = substr($txt, $start, $end - $start + 1);
        $arr = json_decode($slice, true);
        if (is_array($arr)) return $arr;
    }
    return null;
}

function to_int_or_null($v): ?int
{
    if ($v === null || $v === '') return null;
    if (is_numeric($v)) return (int)$v;
    $n = preg_replace('/[^0-9]/', '', (string)$v) ?? '';
    return $n === '' ? null : (int)$n;
}

function to_text_or_null($v): ?string
{
    if ($v === null) return null;
    $s = trim((string)$v);
    if ($s === '' || strtolower($s) === 'none' || strtolower($s) === 'null') return null;
    return $s;
}

function call_openai_extract(string $apiKey, string $model, string $url, string $descriptionCandidates, string $htmlSnippet, string $textSnippet, string $jsonldSnippet, ?string &$error = null): ?array
{
    $error = null;
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
- plot_sqm = land/plot area
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

    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'You are a strict data extractor.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.0,
        'response_format' => ['type' => 'json_object'],
    ];

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

    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $env = json_decode((string)$response, true);
    if ($httpCode >= 400) {
        $error = (string)($env['error']['message'] ?? ('HTTP ' . $httpCode));
        return null;
    }

    $content = (string)($env['choices'][0]['message']['content'] ?? '');
    if ($content === '') {
        $error = 'Empty model response.';
        return null;
    }

    $parsed = decode_json_response($content);
    if (!is_array($parsed)) {
        $error = 'Model did not return valid JSON.';
        return null;
    }

    return $parsed;
}

load_env_file(__DIR__ . '/../.env');
$apiKey = get_api_key();
if ($apiKey === '') {
    fwrite(STDERR, "Missing OPENAI_API_KEY/OPENAIAPI in .env\n");
    exit(1);
}

$pdo->exec('USE test2firstlisting');

$sql = "SELECT rp.id, rp.url, rp.html_raw, rp.text_raw, rp.jsonld_raw
        FROM raw_pages rp
        LEFT JOIN ai_listings ai ON ai.raw_page_id = rp.id";
$params = [];
if ($RAW_ID !== null) {
    $sql .= " WHERE rp.id = :raw_id";
    $params[':raw_id'] = $RAW_ID;
} elseif (!$FORCE) {
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

foreach ($rows as $row) {
    $id = (int)$row['id'];
    $url = (string)$row['url'];
    $descriptionCandidates = extract_description_candidates((string)($row['html_raw'] ?? ''));
    $htmlSnippet = preprocess_html((string)($row['html_raw'] ?? ''));
    $textSnippet = preprocess_text((string)($row['text_raw'] ?? ''));
    $jsonldSnippet = preprocess_jsonld((string)($row['jsonld_raw'] ?? ''));

    $err = null;
    $ai = call_openai_extract($apiKey, $MODEL, $url, $descriptionCandidates, $htmlSnippet, $textSnippet, $jsonldSnippet, $err);
    if (!is_array($ai)) {
        echo "[raw_page_id={$id}] Failed: {$err}\n";
        continue;
    }

    $title = to_text_or_null($ai['title'] ?? null);
    $description = to_text_or_null($ai['description'] ?? null);
    $price = to_int_or_null($ai['price'] ?? null);
    $sqm = to_int_or_null($ai['sqm'] ?? null);
    $rooms = to_int_or_null($ai['rooms'] ?? null);
    $bathrooms = to_int_or_null($ai['bathrooms'] ?? null);
    $plotSqm = to_int_or_null($ai['plot_sqm'] ?? null);
    $propertyType = to_text_or_null($ai['property_type'] ?? null);
    $listingType = to_text_or_null($ai['listing_type'] ?? null);
    $address = to_text_or_null($ai['address'] ?? null);
    $referenceId = to_text_or_null($ai['reference_id'] ?? null);
    $agentName = to_text_or_null($ai['agent_name'] ?? null);
    $agentPhone = to_text_or_null($ai['agent_phone'] ?? null);
    $agentEmail = to_text_or_null($ai['agent_email'] ?? null);

    // Keep ai_listings.id aligned with raw_pages.id and remove prior mismatched rows.
    $del = $pdo->prepare('DELETE FROM ai_listings WHERE raw_page_id = :rid_raw AND id <> :rid_id');
    $del->execute([':rid_raw' => $id, ':rid_id' => $id]);

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
        ':id' => $id,
        ':raw_page_id' => $id,
        ':title' => $title,
        ':property_type' => $propertyType,
        ':listing_type' => $listingType,
        ':description' => $description,
        ':price' => $price,
        ':currency' => 'EUR',
        ':sqm' => $sqm,
        ':plot_sqm' => $plotSqm,
        ':rooms' => $rooms,
        ':bathrooms' => $bathrooms,
        ':address' => $address,
        ':reference_id' => $referenceId,
        ':agent_name' => $agentName,
        ':agent_phone' => $agentPhone,
        ':agent_email' => $agentEmail,
    ]);

    echo "[raw_page_id={$id}] Parsed and saved\n";
}

echo "Done.\n";
