<?php

require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->query("
    SELECT
        id,
        url,
        domain,
        source_type,
        title,
        description,
        price,
        currency,
        sqm,
        rooms,
        area_text,
        agent_name,
        agent_phone,
        agent_email,
        first_seen_at,
        last_seen_at,
        created_at,
        updated_at
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
        <th>URL</th>
        <th>Domain</th>
        <th>Source</th>
        <th>Title</th>
        <th>Description</th>
        <th>Price</th>
        <th>SQM</th>
        <th>Rooms</th>
        <th>Area</th>
        <th>Agent name</th>
        <th>Agent phone</th>
        <th>Agent email</th>
        <th>First seen</th>
        <th>Last seen</th>
        <th>Created</th>
        <th>Updated</th>
    </tr>


    <?php foreach ($listings as $listing): ?>
<tr>
    <td><?= $listing['id'] ?></td>

    <?php if (!empty($listing['url'])): ?>
    <a href="<?= htmlspecialchars($listing['url']) ?>" target="_blank">link</a>

<?php endif; ?>


    <td><?= htmlspecialchars($listing['domain']) ?></td>
    <td><?= htmlspecialchars($listing['source_type'] ?? '—') ?></td>


    <td><?= htmlspecialchars($listing['title'] ?? '') ?></td>

    <td style="max-width:300px;">
        <?= htmlspecialchars($listing['description'] ?? '') ?>
    </td>

    <td><?= $listing['price'] ? number_format($listing['price'], 0, ',', '.') . ' €' : '' ?></td>

    <td><?= $listing['sqm'] ?></td>
    <td><?= $listing['rooms'] ?></td>

    <td><?= htmlspecialchars($listing['area_text'] ?? '') ?></td>

    <td><?= htmlspecialchars($listing['agent_name'] ?? '') ?></td>
    <td><?= htmlspecialchars($listing['agent_phone'] ?? '') ?></td>
    <td><?= htmlspecialchars($listing['agent_email'] ?? '') ?></td>

    <td><?= $listing['first_seen_at'] ?></td>
    <td><?= $listing['last_seen_at'] ?></td>
    <td><?= $listing['created_at'] ?></td>
    <td><?= $listing['updated_at'] ?></td>
</tr>
<?php endforeach; ?>


</table>

<p><a href="index.php">Back to homepage</a></p>

</body>
</html>
