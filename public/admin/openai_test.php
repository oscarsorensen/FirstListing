<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

function load_env_file(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = array_map('trim', explode('=', $line, 2));
        if ($name === '') {
            continue;
        }
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

load_env_file(__DIR__ . '/../../.env');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed.";
    exit;
}

$prompt = trim((string)($_POST['prompt'] ?? ''));
if ($prompt === '') {
    http_response_code(400);
    echo "Write a prompt first.";
    exit;
}

$apiKey = getenv('OPENAI_API_KEY') ?: getenv('OPENAIAPI') ?: '';
if ($apiKey === '') {
    http_response_code(500);
    echo "API key not found.";
    exit;
}

$payload = [
    'model' => 'gpt-4.1-mini',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => $prompt],
    ],
    'temperature' => 0.2,
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
    http_response_code(500);
    echo 'Request error: ' . curl_error($ch);
    exit;
}

$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$json = json_decode((string)$response, true);

if ($httpCode >= 400) {
    http_response_code($httpCode);
    echo (string)($json['error']['message'] ?? ('HTTP ' . $httpCode));
    exit;
}

$text = (string)($json['choices'][0]['message']['content'] ?? '');
if ($text === '') {
    http_response_code(500);
    echo "No response returned.";
    exit;
}

echo $text;
