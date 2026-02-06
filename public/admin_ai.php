<?php

require_once __DIR__ . '/../config/db.php';

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
$allowed_fields = ['description'];

$row = null;
if ($db_error === null && $id > 0 && in_array($field, $allowed_fields, true)) {
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
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="container">
    <h1>AI data</h1>
    <div class="muted">View AI-parsed description</div>

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
