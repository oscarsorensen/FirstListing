<?php

require_once __DIR__ . '/../config/db.php';

function esc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function show_value($value) {
    if ($value === null || $value === '') {
        return '—';
    }
    return esc($value);
}

$stats = [
    'total' => (int)$pdo->query('SELECT COUNT(*) FROM listings')->fetchColumn(),
    'with_price' => (int)$pdo->query('SELECT COUNT(*) FROM listings WHERE price IS NOT NULL')->fetchColumn(),
    'with_sqm' => (int)$pdo->query('SELECT COUNT(*) FROM listings WHERE sqm IS NOT NULL')->fetchColumn(),
    'with_rooms' => (int)$pdo->query('SELECT COUNT(*) FROM listings WHERE rooms IS NOT NULL')->fetchColumn(),
    'with_area' => (int)$pdo->query('SELECT COUNT(*) FROM listings WHERE area_text IS NOT NULL AND area_text <> ""')->fetchColumn(),
    'first_seen_min' => $pdo->query('SELECT MIN(first_seen_at) FROM listings')->fetchColumn(),
    'last_seen_max' => $pdo->query('SELECT MAX(last_seen_at) FROM listings')->fetchColumn(),
];

$domains_top = $pdo->query('
    SELECT domain, COUNT(*) AS cnt
    FROM listings
    GROUP BY domain
    ORDER BY cnt DESC
    LIMIT 10
')->fetchAll(PDO::FETCH_ASSOC);

$source_types = $pdo->query('
    SELECT source_type, COUNT(*) AS cnt
    FROM listings
    GROUP BY source_type
    ORDER BY cnt DESC
')->fetchAll(PDO::FETCH_ASSOC);

$all_domains = $pdo->query('SELECT DISTINCT domain FROM listings ORDER BY domain')->fetchAll(PDO::FETCH_COLUMN);
$all_sources = $pdo->query('SELECT DISTINCT source_type FROM listings ORDER BY source_type')->fetchAll(PDO::FETCH_COLUMN);

$domain_filter = trim($_GET['domain'] ?? '');
$source_filter = trim($_GET['source_type'] ?? '');
$has_price = isset($_GET['has_price']);
$has_sqm = isset($_GET['has_sqm']);
$has_rooms = isset($_GET['has_rooms']);

$where = [];
$params = [];

if ($domain_filter !== '') {
    $where[] = 'domain = :domain';
    $params[':domain'] = $domain_filter;
}

if ($source_filter !== '') {
    $where[] = 'source_type = :source_type';
    $params[':source_type'] = $source_filter;
}

if ($has_price) {
    $where[] = 'price IS NOT NULL';
}

if ($has_sqm) {
    $where[] = 'sqm IS NOT NULL';
}

if ($has_rooms) {
    $where[] = 'rooms IS NOT NULL';
}

$sql = '
    SELECT
        id,
        url,
        domain,
        source_type,
        title,
        price,
        currency,
        sqm,
        rooms,
        area_text,
        first_seen_at,
        last_seen_at,
        created_at,
        updated_at
    FROM listings
';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY last_seen_at DESC LIMIT 50';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin dashboard v2</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="container">
    <h1>Admin dashboard v2</h1>
    <div class="muted">Quick overview and latest 50 listings</div>

    <div class="cards">
        <div class="card">
            <div class="label">Total listings</div>
            <div class="value"><?= $stats['total'] ?></div>
        </div>
        <div class="card">
            <div class="label">With price</div>
            <div class="value"><?= $stats['with_price'] ?></div>
        </div>
        <div class="card">
            <div class="label">With sqm</div>
            <div class="value"><?= $stats['with_sqm'] ?></div>
        </div>
        <div class="card">
            <div class="label">With rooms</div>
            <div class="value"><?= $stats['with_rooms'] ?></div>
        </div>
        <div class="card">
            <div class="label">With area text</div>
            <div class="value"><?= $stats['with_area'] ?></div>
        </div>
        <div class="card">
            <div class="label">First seen (min)</div>
            <div class="value"><?= show_value($stats['first_seen_min']) ?></div>
        </div>
        <div class="card">
            <div class="label">Last seen (max)</div>
            <div class="value"><?= show_value($stats['last_seen_max']) ?></div>
        </div>
    </div>

    <div class="row">
        <div class="panel">
            <h2>Top domains (max 10)</h2>
            <ul>
                <?php foreach ($domains_top as $row): ?>
                    <li><?= esc($row['domain']) ?> — <?= (int)$row['cnt'] ?></li>
                <?php endforeach; ?>
                <?php if (!$domains_top): ?>
                    <li>No data</li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="panel">
            <h2>Source types</h2>
            <ul>
                <?php foreach ($source_types as $row): ?>
                    <li><?= esc($row['source_type']) ?> — <?= (int)$row['cnt'] ?></li>
                <?php endforeach; ?>
                <?php if (!$source_types): ?>
                    <li>No data</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="filters">
        <form method="get">
            <div>
                <label for="domain">Domain</label>
                <select name="domain" id="domain">
                    <option value="">All</option>
                    <?php foreach ($all_domains as $domain): ?>
                        <option value="<?= esc($domain) ?>" <?= $domain === $domain_filter ? 'selected' : '' ?>>
                            <?= esc($domain) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="source_type">Source type</label>
                <select name="source_type" id="source_type">
                    <option value="">All</option>
                    <?php foreach ($all_sources as $source): ?>
                        <option value="<?= esc($source) ?>" <?= $source === $source_filter ? 'selected' : '' ?>>
                            <?= esc($source) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label><input type="checkbox" name="has_price" <?= $has_price ? 'checked' : '' ?>> Has price</label>
            </div>
            <div>
                <label><input type="checkbox" name="has_sqm" <?= $has_sqm ? 'checked' : '' ?>> Has sqm</label>
            </div>
            <div>
                <label><input type="checkbox" name="has_rooms" <?= $has_rooms ? 'checked' : '' ?>> Has rooms</label>
            </div>
            <div>
                <button type="submit">Apply filters</button>
            </div>
        </form>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>URL</th>
            <th>Domain</th>
            <th>Source</th>
            <th>Title</th>
            <th>Price</th>
            <th>SQM</th>
            <th>Rooms</th>
            <th>Area</th>
            <th>First seen</th>
            <th>Last seen</th>
            <th>Created</th>
            <th>Updated</th>
        </tr>
        <?php foreach ($listings as $listing): ?>
            <tr>
                <td class="nowrap"><?= (int)$listing['id'] ?></td>
                <td class="nowrap">
                    <?php if (!empty($listing['url'])): ?>
                        <a href="<?= esc($listing['url']) ?>" target="_blank">link</a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td><?= show_value($listing['domain']) ?></td>
                <td><?= show_value($listing['source_type']) ?></td>
                <td><?= show_value($listing['title']) ?></td>
                <td class="nowrap">
                    <?php if (!empty($listing['price'])): ?>
                        <?= number_format((int)$listing['price'], 0, ',', '.') . ' ' . esc($listing['currency'] ?? 'EUR') ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td class="nowrap"><?= show_value($listing['sqm']) ?></td>
                <td class="nowrap"><?= show_value($listing['rooms']) ?></td>
                <td><?= show_value($listing['area_text']) ?></td>
                <td class="nowrap"><?= show_value($listing['first_seen_at']) ?></td>
                <td class="nowrap"><?= show_value($listing['last_seen_at']) ?></td>
                <td class="nowrap"><?= show_value($listing['created_at']) ?></td>
                <td class="nowrap"><?= show_value($listing['updated_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$listings): ?>
            <tr>
                <td colspan="13">No listings match the filters.</td>
            </tr>
        <?php endif; ?>
    </table>

    <p><a href="index.php">Back to homepage</a></p>
</div>
</body>
</html>
