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
$MODEL = 'qwen2.5:14b';
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

function txt_or_none($v): string
{
    $v = trim((string)$v);
    return $v === '' ? 'None' : $v;
}

function int_or_null($v): ?int
{
    if ($v === null || $v === '') return null;
    if (is_numeric($v)) return (int)$v;
    $n = preg_replace('/[^0-9]/', '', (string)$v) ?? '';
    return $n === '' ? null : (int)$n;
}

function parse_kv_response(string $responseText): array
{
    $out = [];
    $txt = trim($responseText);
    $txt = preg_replace('/^```.*?\n/s', '', $txt) ?? $txt;
    $txt = preg_replace('/```$/', '', $txt) ?? $txt;

    $lines = preg_split('/\R/u', $txt) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || !str_contains($line, ':')) {
            continue;
        }
        [$k, $v] = array_pad(explode(':', $line, 2), 2, '');
        $k = strtolower(trim($k));
        $v = trim($v);
        if ($k !== '') {
            $out[$k] = $v;
        }
    }
    return $out;
}

function call_ollama_extract(string $url, string $model, string $listingUrl, string $html, string $text, string $jsonld, ?string &$error = null): ?array
{
    $error = null;
    $html = mb_substr($html, 0, 5500, 'UTF-8');
    $text = mb_substr($text, 0, 3800, 'UTF-8');
    $jsonld = mb_substr($jsonld, 0, 2600, 'UTF-8');

    $prompt = <<<PROMPT
You are a data extraction engine for real-estate listings.

Task:
Extract these fields:
title, description, price, sqm, rooms, bathrooms, plot_sqm, property_type, listing_type, address, reference_id, agent_name, agent_phone, agent_email

STRICT rules:
1) Source priority must be: HTML first, then TEXT, then JSON-LD.
2) Use only explicit values from source. No guessing, no invention.
3) If a text field is missing, return "None".
4) If a numeric field is missing, return null.
5) listing_type must be exactly "sale", "rent", or "None".
6) Do NOT return JSON. Return plain text lines only.
7) One field per line, EXACTLY in this format:
title: ...
description: ...
price: ...
sqm: ...
rooms: ...
bathrooms: ...
plot_sqm: ...
property_type: ...
listing_type: ...
address: ...
reference_id: ...
agent_name: ...
agent_phone: ...
agent_email: ...
8) Keep description short (max 240 chars).

Listing URL:
{$listingUrl}

HTML (priority 1):
{$html}

TEXT (priority 2):
{$text}

JSON-LD (priority 3):
{$jsonld}
PROMPT;

    $payload = [
        'model' => $model,
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'temperature' => 0.0,
            'num_predict' => 280,
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

    $parsed = parse_kv_response((string)$env['response']);
    if (!$parsed) {
        $error = 'Model response was not parsable KV text. Raw model text: ' . mb_substr((string)$env['response'], 0, 700, 'UTF-8');
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
    $text = (string)($row['text_raw'] ?? '');
    $jsonld = (string)($row['jsonld_raw'] ?? '');

    $err = null;
    $ai = call_ollama_extract($OLLAMA_URL, $MODEL, $listingUrl, $html, $text, $jsonld, $err);
    if (!is_array($ai)) {
        echo "[raw_page_id={$id}] AI extraction failed";
        if ($err !== null) {
            echo " - {$err}";
        }
        echo "\n";
        continue;
    }

    $title = txt_or_none($ai['title'] ?? null);
    $description = txt_or_none($ai['description'] ?? null);
    $price = int_or_null($ai['price'] ?? null);
    $sqm = int_or_null($ai['sqm'] ?? null);
    $rooms = int_or_null($ai['rooms'] ?? null);
    $bathrooms = int_or_null($ai['bathrooms'] ?? null);
    $plotSqm = int_or_null($ai['plot_sqm'] ?? null);
    $propertyType = txt_or_none($ai['property_type'] ?? null);
    $listingType = txt_or_none($ai['listing_type'] ?? null);
    $address = txt_or_none($ai['address'] ?? null);
    $referenceId = txt_or_none($ai['reference_id'] ?? null);
    $agentName = txt_or_none($ai['agent_name'] ?? null);
    $agentPhone = txt_or_none($ai['agent_phone'] ?? null);
    $agentEmail = txt_or_none($ai['agent_email'] ?? null);

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
