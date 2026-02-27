<?php

declare(strict_types=1);

// CLI script — called from user.php after SQL candidates have been found.
// Usage: php ai_compare_descriptions.php --raw-id=N --candidates=id1,id2,id3
//
// Fetches the description of the base listing (raw_page_id = N) and the
// description of each candidate. Sends each pair to GPT-4.1-mini and asks
// whether the two descriptions are about the same property.
// Outputs a JSON array, one result object per candidate.

require_once __DIR__ . '/../config/db.php';

// Read --raw-id=N and --candidates=id1,id2,... from CLI arguments
$RAW_ID        = null;
$CANDIDATE_IDS = [];

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--raw-id=')) {
        $RAW_ID = max(1, (int)substr($arg, 9));
    } elseif (str_starts_with($arg, '--candidates=')) {
        $ids_raw = substr($arg, 13);
        foreach (explode(',', $ids_raw) as $id) {
            $id = (int)trim($id);
            if ($id > 0) {
                $CANDIDATE_IDS[] = $id;
            }
        }
    }
}

if ($RAW_ID === null || empty($CANDIDATE_IDS)) {
    fwrite(STDERR, "Usage: php ai_compare_descriptions.php --raw-id=N --candidates=id1,id2\n");
    exit(1);
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

// Send both descriptions to GPT-4.1-mini and ask if they describe the same property.
// Returns an array with keys: same_property (bool), confidence (float), reason (string).
// Returns null if the API call fails.
function compare_descriptions(string $apiKey, string $descA, string $descB): ?array
{
    $prompt = <<<PROMPT
Compare these two real estate listing descriptions. Decide if they describe the same physical property.

Description A:
{$descA}

Description B:
{$descB}

Return ONLY valid JSON with these three keys:
{
  "same_property": true or false,
  "confidence": a decimal between 0.0 and 1.0,
  "reason": "one short sentence explaining your decision"
}
PROMPT;

    $payload = [
        'model'           => 'gpt-4.1-mini',
        'messages'        => [
            ['role' => 'system', 'content' => 'You are a real estate duplicate detector. Respond only with valid JSON.'],
            ['role' => 'user',   'content' => $prompt],
        ],
        'temperature'     => 0.0,
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    if ($response === false) {
        return null;
    }

    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $body = json_decode((string)$response, true);
    if ($httpCode >= 400) {
        return null;
    }

    $content = (string)($body['choices'][0]['message']['content'] ?? '');
    if ($content === '') {
        return null;
    }

    $parsed = json_decode($content, true);
    return is_array($parsed) ? $parsed : null;
}

// Load the OpenAI API key
$apiKey = read_api_key(__DIR__ . '/../.env');
if ($apiKey === '') {
    fwrite(STDERR, "Missing OPENAI_API_KEY in .env\n");
    exit(1);
}

// Fetch the base listing's description
$st = $pdo->prepare(
    "SELECT description FROM ai_listings WHERE raw_page_id = :id LIMIT 1"
);
$st->execute([':id' => $RAW_ID]);
$base = $st->fetch(PDO::FETCH_ASSOC);

if (!$base || !$base['description']) {
    echo json_encode(['error' => 'No description found for base listing (raw_page_id ' . $RAW_ID . ')']);
    exit(1);
}

$base_description = (string)$base['description'];

// Fetch candidate descriptions using IN(...) with positional placeholders
$placeholders = implode(',', array_fill(0, count($CANDIDATE_IDS), '?'));
$st2 = $pdo->prepare(
    "SELECT raw_page_id, description FROM ai_listings WHERE raw_page_id IN ({$placeholders})"
);
$st2->execute($CANDIDATE_IDS);
$candidates = $st2->fetchAll(PDO::FETCH_ASSOC);

// Compare the base description against each candidate
$results = [];
foreach ($candidates as $candidate) {
    $cand_id = (int)$candidate['raw_page_id'];

    if (empty($candidate['description'])) {
        // No description to compare — skip AI call
        $results[] = [
            'raw_page_id'   => $cand_id,
            'same_property' => null,
            'confidence'    => null,
            'reason'        => 'No description available for this candidate',
        ];
        continue;
    }

    $comparison = compare_descriptions($apiKey, $base_description, (string)$candidate['description']);

    $results[] = [
        'raw_page_id'   => $cand_id,
        'same_property' => $comparison['same_property'] ?? null,
        'confidence'    => $comparison['confidence']    ?? null,
        'reason'        => $comparison['reason']        ?? null,
    ];
}

echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
