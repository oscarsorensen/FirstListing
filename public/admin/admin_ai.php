<?php

// DB-forbindelse
require_once __DIR__ . '/../../config/db.php';

// Simpel escaping til output
function esc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Vælg database
$target_db = 'test2firstlisting';
$pdo->exec("USE {$target_db}");

// Hent parametre fra URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$field = $_GET['field'] ?? '';

// Hvis input er gyldigt, hent AI-row
$row = null;
if ($id > 0 && $field === 'description') {
    $stmt = $pdo->prepare(
        'SELECT id, raw_page_id, title, description
         FROM ai_listings
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
    <title>AI data</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="container">
    <h1>AI data</h1>
    <div class="muted">View AI-parsed description</div>

    <?php if (!$row): ?>
        <div class="panel">
            <strong>No data found.</strong> Check the ID and field.
        </div>
    <?php else: ?>
        <div class="panel">
            <div><strong>ID:</strong> <?= (int)$row['id'] ?></div>
            <div><strong>Raw page:</strong> <?= esc($row['raw_page_id']) ?></div>
            <div><strong>Title:</strong> <?= esc($row['title']) ?></div>
        </div>
        <div class="panel">
            <pre style="max-height: 70vh; overflow: auto; white-space: pre-wrap;"><?= esc($row['description']) ?></pre>
        </div>
    <?php endif; ?>

    <p><a href="admin.php">Back to admin</a></p>
</div>
</body>
</html>
