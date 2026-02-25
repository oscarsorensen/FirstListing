<?php

// Connect to the database
require_once __DIR__ . '/../../config/db.php';

// Escape a value for safe HTML output
function esc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Select the correct database
$target_db = 'test2firstlisting';
$pdo->exec("USE {$target_db}");

// Read the ID and field name from the URL (?id=X&field=html/text/jsonld)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$field = $_GET['field'] ?? '';

// Map the short field name to the actual database column name
$field_map = [
    'html'   => 'html_raw',
    'text'   => 'text_raw',
    'jsonld' => 'jsonld_raw',
];

// Only fetch data if the ID and field are valid
$row = null;
if ($id > 0 && isset($field_map[$field])) {
    // Fetch the raw content (html/text/jsonld) for the given ID
    $stmt = $pdo->prepare(
        'SELECT id, url, domain, ' . $field_map[$field] . ' AS content
         FROM raw_pages
         WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Raw data</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="container">
    <h1>Raw data</h1>
    <div class="muted">View stored HTML/text/JSON-LD</div>

    <?php if (!$row): ?>
        <div class="panel">
            <strong>No data found.</strong> Check the ID and field.
        </div>
    <?php else: ?>
        <div class="panel">
            <div><strong>ID:</strong> <?= (int)$row['id'] ?></div>
            <div><strong>URL:</strong> <?= esc($row['url']) ?></div>
            <div><strong>Domain:</strong> <?= esc($row['domain']) ?></div>
            <div><strong>Field:</strong> <?= esc($field) ?></div>
        </div>
        <div class="panel">
            <pre style="max-height: 70vh; overflow: auto; white-space: pre-wrap;"><?= esc($row['content']) ?></pre>
        </div>
    <?php endif; ?>

    <p><a href="admin.php">Back to admin</a></p>
</div>
</body>
</html>
