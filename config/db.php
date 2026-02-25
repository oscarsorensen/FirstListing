<?php

// Database credentials
$DB_HOST = 'localhost';
$DB_NAME = 'test2firstlisting';
$DB_USER = 'firstlisting_user';
$DB_PASS = 'girafferharlangehalse';
$DB_CHARSET = 'utf8mb4';

// PDO options: throw exceptions on error, return arrays by default, use real prepared statements
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Connect to the database — stop the script if it fails
try {
    $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHARSET";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
