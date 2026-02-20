<?php

header('Content-Type: text/plain; charset=UTF-8');

$logFile = '/tmp/firstlisting_crawler.log';

if (!is_file($logFile)) {
    echo "No log file yet.";
    exit;
}

$size = filesize($logFile);
if ($size === false || $size <= 0) {
    echo "Log file is empty.";
    exit;
}

$maxBytes = 12000;
$start = max(0, $size - $maxBytes);
$fh = fopen($logFile, 'rb');
if ($fh === false) {
    echo "Could not read log file.";
    exit;
}

fseek($fh, $start);
$data = stream_get_contents($fh);
fclose($fh);

echo trim((string)$data);
