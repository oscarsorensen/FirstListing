<?php

// Return plain text so the admin dashboard can display it in a <pre> box
header('Content-Type: text/plain; charset=UTF-8');

$logFile = '/tmp/firstlisting_crawler.log';

// Stop early if the log file doesn't exist yet
if (!is_file($logFile)) {
    echo "No log file yet.";
    exit;
}

// Stop early if the log file is empty
$size = filesize($logFile);
if ($size === false || $size <= 0) {
    echo "Log file is empty.";
    exit;
}

// Only read the last 12000 bytes so we don't load the entire log into memory
$maxBytes = 12000;
$start = max(0, $size - $maxBytes);
$fh = fopen($logFile, 'rb');
if ($fh === false) {
    echo "Could not read log file.";
    exit;
}

// Seek to the start position and read the rest of the file
fseek($fh, $start);
$data = stream_get_contents($fh);
fclose($fh);

echo trim((string)$data);
