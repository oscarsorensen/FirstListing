<?php

require_once __DIR__ . '/../../config/db.php';

function esc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$target_db = 'test2firstlisting';
$db_error = null;

try {
    $pdo->exec("USE {$target_db}");
} catch (PDOException $e) {
    $db_error = $e->getMessage();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$field = $_GET['field'] ?? '';
$allowed_fields = ['html', 'text', 'jsonld'];
$field_map = [
    'html' => 'html_raw',
    'text' => 'text_raw',
    'jsonld' => 'jsonld_raw',
];

$row = null;
if ($db_error === null && $id > 0 && in_array($field, $allowed_fields, true)) {
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

    <?php if ($db_error !== null): ?>
        <div class="panel">
            <strong>Database error:</strong> <?= esc($db_error) ?>
        </div>
    <?php elseif (!$row): ?>
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
