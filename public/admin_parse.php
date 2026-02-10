<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
ini_set('max_execution_time', '0');
set_time_limit(0);
ignore_user_abort(true);

try {
    $limit = (int)($_POST['ai_parse_limit'] ?? 1);
    if ($limit < 1) {
        $limit = 1;
    }
    if ($limit > 3) {
        $limit = 3;
    }

    $script_path = realpath(__DIR__ . '/../scripts/ai_parse_raw_pages.php');
    if ($script_path === false) {
        echo json_encode([
            'ok' => false,
            'message' => 'Could not find parser script.',
            'output' => ''
        ]);
        exit;
    }

    $php_binary = PHP_BINARY ?: 'php';
    $cmd = escapeshellarg($php_binary) . ' ' . escapeshellarg($script_path) . ' --limit=' . $limit . ' 2>&1';

    $started = microtime(true);
    $output = shell_exec($cmd);
    $duration = microtime(true) - $started;

    $ok = $output !== null;
    $message = $ok
        ? "Parser finished (limit={$limit})."
        : 'Parser command failed (shell_exec disabled or command error).';

    if ($ok && is_string($output)) {
        $lower = strtolower($output);
        if (str_contains($lower, 'database connection failed') || str_contains($lower, 'db insert error')) {
            $ok = false;
            $message = 'Parser ended with database error.';
        } elseif (str_contains($lower, 'model did not return valid json') || str_contains($lower, 'curl error')) {
            $ok = false;
            $message = 'Parser ended with model/output error.';
        }
    }

    echo json_encode([
        'ok' => $ok,
        'message' => $message,
        'output' => $output ?? '',
        'duration_sec' => round($duration, 2)
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'message' => 'Parser endpoint error: ' . $e->getMessage(),
        'output' => ''
    ]);
}
