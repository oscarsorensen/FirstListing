<?php

$DB_HOST = 'localhost';
$DB_NAME = 'firstlisting_v1';
$DB_USER = 'firstlisting_admin';  
$DB_PASS = 'zf4B84BHrlW9jvl4';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHARSET";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    // Stop execution if DB is unavailable
    die('Database connection failed.');
}
