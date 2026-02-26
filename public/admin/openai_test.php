<?php

//my test window i used to test connection to OpenAI API, before i really understood how it worked. Now its just a chat. 
//im thinking about changing this into a chat on the public page later, with info about the site.

// Return plain text so the response shows up in the admin test box
header('Content-Type: text/plain; charset=UTF-8');

// Only allow POST requests from the form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed.';
    exit;
}

// Get the prompt from the form — stop if it's empty
$prompt = trim((string)($_POST['prompt'] ?? ''));
if ($prompt === '') {
    http_response_code(400);
    echo 'Write a prompt first.';
    exit;
}

// Read the API key from the .env file
$env = @parse_ini_file(__DIR__ . '/../../.env');
$apiKey = trim((string)($env['OPENAI_API_KEY'] ?? ''), "\"'");
if ($apiKey === '') {
    http_response_code(500);
    echo 'OPENAI_API_KEY not found.';
    exit;
}

// Build the request payload for OpenAI
$payload = [
    'model' => 'gpt-4.1-mini',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => $prompt],
    ],
    'temperature' => 0.2,
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
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
$response = curl_exec($ch);

// Return the model's reply, or an error message if something went wrong
if ($response === false) {
    http_response_code(500);
    echo 'Request failed.';
    exit;
}

$json = json_decode((string)$response, true);
echo (string)($json['choices'][0]['message']['content'] ?? ($json['error']['message'] ?? 'No response returned.'));
