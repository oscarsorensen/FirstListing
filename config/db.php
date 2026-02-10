<?php

$DB_HOST = 'localhost';
$DB_NAME = 'test2firstlisting';
$DB_USER = 'firstlisting_user';  
$DB_PASS = 'girafferharlangehalse';
$DB_CHARSET = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$dsns = [
    // Preferred: local socket (fast and typical on macOS Homebrew MySQL)
    "mysql:unix_socket=/tmp/mysql.sock;dbname=$DB_NAME;charset=$DB_CHARSET",
    // Fallback: TCP localhost
    "mysql:host=127.0.0.1;port=3306;dbname=$DB_NAME;charset=$DB_CHARSET",
    // Last fallback
    "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHARSET",
];

$pdo = null;
foreach ($dsns as $dsn) {
    try {
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
        break;
    } catch (PDOException $e) {
        // try next DSN
    }
}

if (!$pdo) {
    die('Database connection failed.');
}
