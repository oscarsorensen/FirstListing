<?php

// Public chat endpoint — receives a user message and returns an AI reply
// Used by the floating chat widget on the public pages

header('Content-Type: application/json; charset=UTF-8');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

// Read the user's message — stop if it's empty
$message = trim((string)($_POST['message'] ?? ''));
if ($message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Write a message first.']);
    exit;
}

// Read the API key from the .env file
$env = @parse_ini_file(__DIR__ . '/../.env');
$apiKey = trim((string)($env['OPENAI_API_KEY'] ?? ''), "\"'");
if ($apiKey === '') {
    http_response_code(500);
    echo json_encode(['error' => 'API key not configured.']);
    exit;
}

// System prompt — tells the AI what FirstListing is and how it works
$systemPrompt = <<<PROMPT
You are a helpful assistant for FirstListing, a school MVP project about real estate duplicate listing detection. Keep your answers short, clear, and friendly.

Here is how FirstListing works:
1. A Python crawler visits real estate listing pages (currently only jensenestate.es) and stores the raw HTML, text, and JSON-LD in a MySQL database. Support for other portals is not yet implemented.
2. An AI model (GPT-4.1-mini) reads each raw page and extracts structured fields: price, rooms, sqm, bathrooms, address, property type, listing type, agent info, and a reference ID.
3. A SQL scoring system compares listings and assigns a score based on how many fields match. The fields and their weights are: reference ID (5 pts), price (3 pts), sqm (3 pts), rooms (2 pts), bathrooms (2 pts), property type (1 pt), listing type (1 pt). The maximum score is 17. A listing must score at least 10 to be shown as a candidate duplicate.
4. The AI then compares the descriptions of the top candidates to give a similarity verdict and confirm if they are true duplicates.
5. The system records a "first seen" date — the date the crawler first visited that listing URL. This is used as a proxy for who published it first, but it is not a guaranteed fact since the crawler may not have visited all portals at the same time.

Users can paste a listing URL into the user page, and the full pipeline runs automatically. Results show which listings are likely duplicates and a similarity verdict for each one.

The project was built by Oscar, a first-year web development student, as a school MVP. Answer questions about how the site works, the tech used, and the duplicate detection logic.

If someone asks about the admin panel, internal database structure, server configuration, file paths, monthly search limits, or any other internal implementation detail, reply that that information is not relevant for users and is not something you can help with.
PROMPT;

// Build the request payload for OpenAI
$payload = [
    'model' => 'gpt-4.1-mini',
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $message],
    ],
    'temperature' => 0.3,
    'max_tokens' => 300,
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
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);

// Return the reply as JSON
if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Request failed.']);
    exit;
}

$json = json_decode((string)$response, true);
$reply = $json['choices'][0]['message']['content'] ?? ($json['error']['message'] ?? 'No response.');

echo json_encode(['reply' => $reply]);
