<?php

require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->query("
    SELECT id, domain, price, sqm, rooms, first_seen_at, last_seen_at
    FROM listings
    ORDER BY first_seen_at DESC
    LIMIT 20
");

$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin dashboard</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>

<h1>Admin dashboard</h1>
<p>Latest listings (max 20)</p>

<table>
    <tr>
        <th>ID</th>
        <th>Domain</th>
        <th>Price</th>
        <th>SQM</th>
        <th>Rooms</th>
        <th>First seen</th>
        <th>Last seen</th>
    </tr>

    <?php foreach ($listings as $listing): ?>
        <tr>
            <td><?= $listing['id'] ?></td>
            <td><?= htmlspecialchars($listing['domain']) ?></td>
            <td><?= $listing['price'] ?></td>
            <td><?= $listing['sqm'] ?></td>
            <td><?= $listing['rooms'] ?></td>
            <td><?= $listing['first_seen_at'] ?></td>
            <td><?= $listing['last_seen_at'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<p><a href="index.php">Back to homepage</a></p>

</body>
</html>
