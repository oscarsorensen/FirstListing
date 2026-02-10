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

function preview_text($value, $limit = 300) {
    if ($value === null || $value === '') {
        return '';
    }
    $text = trim(preg_replace('/\s+/', ' ', strip_tags((string)$value)));
    if (strlen($text) <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit) . '…';
}

$target_db = 'test2firstlisting';
$using_target_db = false;
$db_error = null;

try {
    $pdo->exec("USE {$target_db}");
    $using_target_db = true;
} catch (PDOException $e) {
    $db_error = $e->getMessage();
}

function table_exists(PDO $pdo, $table) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t");
    $stmt->execute([':t' => $table]);
    return (int)$stmt->fetchColumn() > 0;
}

$has_raw_pages = $using_target_db ? table_exists($pdo, 'raw_pages') : false;
$has_ai_listings = $using_target_db ? table_exists($pdo, 'ai_listings') : false;

$stats = [
    'raw_total' => 0,
    'raw_with_html' => 0,
    'raw_with_text' => 0,
    'raw_with_jsonld' => 0,
    'raw_first_seen' => null,
    'raw_first_fetch' => null,
    'raw_last_fetch' => null,
    'ai_total' => 0,
    'ai_with_price' => 0,
    'ai_with_sqm' => 0,
    'ai_with_rooms' => 0,
    'ai_with_address' => 0,
];

if ($has_raw_pages) {
    $stats['raw_total'] = (int)$pdo->query('SELECT COUNT(*) FROM raw_pages')->fetchColumn();
    $stats['raw_with_html'] = (int)$pdo->query('SELECT COUNT(*) FROM raw_pages WHERE html_raw IS NOT NULL AND html_raw <> ""')->fetchColumn();
    $stats['raw_with_text'] = (int)$pdo->query('SELECT COUNT(*) FROM raw_pages WHERE text_raw IS NOT NULL AND text_raw <> ""')->fetchColumn();
    $stats['raw_with_jsonld'] = (int)$pdo->query('SELECT COUNT(*) FROM raw_pages WHERE jsonld_raw IS NOT NULL AND jsonld_raw <> ""')->fetchColumn();
    $stats['raw_first_seen'] = $pdo->query('SELECT MIN(first_seen_at) FROM raw_pages')->fetchColumn();
    $stats['raw_first_fetch'] = $pdo->query('SELECT MIN(fetched_at) FROM raw_pages')->fetchColumn();
    $stats['raw_last_fetch'] = $pdo->query('SELECT MAX(fetched_at) FROM raw_pages')->fetchColumn();
}

if ($has_ai_listings) {
    $stats['ai_total'] = (int)$pdo->query('SELECT COUNT(*) FROM ai_listings')->fetchColumn();
    $stats['ai_with_price'] = (int)$pdo->query('SELECT COUNT(*) FROM ai_listings WHERE price IS NOT NULL')->fetchColumn();
    $stats['ai_with_sqm'] = (int)$pdo->query('SELECT COUNT(*) FROM ai_listings WHERE sqm IS NOT NULL')->fetchColumn();
    $stats['ai_with_rooms'] = (int)$pdo->query('SELECT COUNT(*) FROM ai_listings WHERE rooms IS NOT NULL')->fetchColumn();
    $stats['ai_with_address'] = (int)$pdo->query('SELECT COUNT(*) FROM ai_listings WHERE address IS NOT NULL AND address <> ""')->fetchColumn();
}

$domains_top = [];
if ($has_raw_pages) {
    $domains_top = $pdo->query('
        SELECT domain, COUNT(*) AS cnt
        FROM raw_pages
        GROUP BY domain
        ORDER BY cnt DESC
        LIMIT 10
    ')->fetchAll(PDO::FETCH_ASSOC);
}

$all_domains = $has_raw_pages ? $pdo->query('SELECT DISTINCT domain FROM raw_pages ORDER BY domain')->fetchAll(PDO::FETCH_COLUMN) : [];

$domain_filter = trim($_GET['domain'] ?? '');
$has_jsonld = isset($_GET['has_jsonld']);
$has_html = isset($_GET['has_html']);
$has_text = isset($_GET['has_text']);
$status_filter = trim($_GET['status'] ?? '');

$where = [];
$params = [];

if ($domain_filter !== '') {
    $where[] = 'domain = :domain';
    $params[':domain'] = $domain_filter;
}

if ($has_jsonld) {
    $where[] = 'jsonld_raw IS NOT NULL AND jsonld_raw <> ""';
}

if ($has_html) {
    $where[] = 'html_raw IS NOT NULL AND html_raw <> ""';
}

if ($has_text) {
    $where[] = 'text_raw IS NOT NULL AND text_raw <> ""';
}

if ($status_filter !== '') {
    $where[] = 'http_status = :http_status';
    $params[':http_status'] = $status_filter;
}

$sql = '
    SELECT
        id,
        url,
        domain,
        first_seen_at,
        fetched_at,
        http_status,
        content_type,
        LENGTH(html_raw) AS html_len,
        LENGTH(text_raw) AS text_len,
        LENGTH(jsonld_raw) AS jsonld_len
    FROM raw_pages
';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY fetched_at DESC LIMIT 50';

$raw_pages = [];
if ($has_raw_pages) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $raw_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$ai_latest = [];
if ($has_ai_listings) {
    $ai_latest = $pdo->query('
        SELECT
            ai.id,
            ai.raw_page_id,
            rp.url AS raw_url,
            ai.title,
            ai.description,
            ai.price,
            ai.currency,
            ai.sqm,
            ai.rooms,
            ai.bathrooms,
            ai.plot_sqm,
            ai.property_type,
            ai.listing_type,
            ai.address,
            ai.reference_id,
            ai.agent_name,
            ai.agent_phone,
            ai.agent_email,
            rp.first_seen_at,
            rp.fetched_at AS last_seen_at,
            ai.created_at
        FROM ai_listings ai
        LEFT JOIN raw_pages rp ON rp.id = ai.raw_page_id
        ORDER BY ai.created_at DESC
        LIMIT 20
    ')->fetchAll(PDO::FETCH_ASSOC);
}

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
    <div class="muted">Raw crawl overview + AI pipeline status</div>

    <?php if (!$using_target_db): ?>
        <div class="panel">
            <strong>Database error:</strong> Could not switch to <?= esc($target_db) ?>.
            <?= $db_error ? esc($db_error) : '' ?>
        </div>
    <?php endif; ?>

    <div class="cards">
        <div class="card">
            <div class="label">Raw pages</div>
            <div class="value"><?= (int)$stats['raw_total'] ?></div>
        </div>
        <div class="card">
            <div class="label">With HTML</div>
            <div class="value"><?= (int)$stats['raw_with_html'] ?></div>
        </div>
        <div class="card">
            <div class="label">With text</div>
            <div class="value"><?= (int)$stats['raw_with_text'] ?></div>
        </div>
        <div class="card">
            <div class="label">With JSON-LD</div>
            <div class="value"><?= (int)$stats['raw_with_jsonld'] ?></div>
        </div>
        <div class="card">
            <div class="label">AI listings</div>
            <div class="value"><?= (int)$stats['ai_total'] ?></div>
        </div>
        <div class="card">
            <div class="label">AI with price</div>
            <div class="value"><?= (int)$stats['ai_with_price'] ?></div>
        </div>
        <div class="card">
            <div class="label">First seen</div>
            <div class="value"><?= show_value($stats['raw_first_seen']) ?></div>
        </div>
        <div class="card">
            <div class="label">Last fetch</div>
            <div class="value"><?= show_value($stats['raw_last_fetch']) ?></div>
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
            <h2>AI coverage</h2>
            <ul>
                <li>With price — <?= (int)$stats['ai_with_price'] ?></li>
                <li>With sqm — <?= (int)$stats['ai_with_sqm'] ?></li>
                <li>With rooms — <?= (int)$stats['ai_with_rooms'] ?></li>
                <li>With address — <?= (int)$stats['ai_with_address'] ?></li>
                <?php if (!$has_ai_listings): ?>
                    <li>No data</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="filters">
        <form method="get">
            <div class="field">
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
            <div class="field">
                <label for="status">HTTP status</label>
                <input type="text" name="status" id="status" value="<?= esc($status_filter) ?>" placeholder="200">
            </div>
            <div class="check">
                <label class="check-label"><input type="checkbox" name="has_jsonld" <?= $has_jsonld ? 'checked' : '' ?>> Has JSON-LD</label>
            </div>
            <div class="check">
                <label class="check-label"><input type="checkbox" name="has_html" <?= $has_html ? 'checked' : '' ?>> Has HTML</label>
            </div>
            <div class="check">
                <label class="check-label"><input type="checkbox" name="has_text" <?= $has_text ? 'checked' : '' ?>> Has text</label>
            </div>
            <div class="actions">
                <button type="submit">Apply filters</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <h2>Raw pages (latest crawl data)</h2>
    </div>

    <div class="table-wrap">
    <table>
        <tr>
            <th>ID</th>
            <th>URL</th>
            <th>Domain</th>
            <th>First seen</th>
            <th>Last seen</th>
            <th>Status</th>
            <th>Content type</th>
            <th>HTML len</th>
            <th>Text len</th>
            <th>JSON-LD len</th>
        </tr>
        <?php foreach ($raw_pages as $row): ?>
            <tr>
                <td class="nowrap"><?= (int)$row['id'] ?></td>
                <td class="nowrap">
                    <?php if (!empty($row['url'])): ?>
                        <a href="<?= esc($row['url']) ?>" target="_blank">link</a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td><?= show_value($row['domain']) ?></td>
                <td class="nowrap"><?= show_value($row['first_seen_at']) ?></td>
                <td class="nowrap"><?= show_value($row['fetched_at']) ?></td>
                <td class="nowrap"><?= show_value($row['http_status']) ?></td>
                <td><?= show_value($row['content_type']) ?></td>
                <td class="nowrap">
                    <?php if (!empty($row['html_len'])): ?>
                        <a href="admin_raw.php?id=<?= (int)$row['id'] ?>&field=html"><?= show_value($row['html_len']) ?></a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td class="nowrap">
                    <?php if (!empty($row['text_len'])): ?>
                        <a href="admin_raw.php?id=<?= (int)$row['id'] ?>&field=text"><?= show_value($row['text_len']) ?></a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td class="nowrap">
                    <?php if (!empty($row['jsonld_len'])): ?>
                        <a href="admin_raw.php?id=<?= (int)$row['id'] ?>&field=jsonld"><?= show_value($row['jsonld_len']) ?></a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$raw_pages): ?>
            <tr>
                <td colspan="10">No raw pages match the filters.</td>
            </tr>
        <?php endif; ?>
    </table>
    </div>

    <div class="panel">
        <h2>Latest AI-parsed listings (if available)</h2>
        <div class="table-wrap">
        <table>
            <tr>
                <th>ID</th>
                <th>URL</th>
                <th>Title</th>
                <th>Description</th>
                <th>Price</th>
                <th>SQM</th>
                <th>Rooms</th>
                <th>Baths</th>
                <th>Plot sqm</th>
                <th>Type</th>
                <th>Listing</th>
                <th>Address</th>
                <th>Reference</th>
                <th>Agent</th>
                <th>Phone</th>
                <th>Email</th>
                <th>First seen</th>
                <th>Last seen</th>
                <th>Created</th>
            </tr>
            <?php foreach ($ai_latest as $row): ?>
                <tr>
                    <td class="nowrap"><?= (int)$row['id'] ?></td>
                    <td class="nowrap">
                        <?php if (!empty($row['raw_url'])): ?>
                            <a href="<?= esc($row['raw_url']) ?>" target="_blank">link</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td><?= show_value($row['title']) ?></td>
                    <td class="nowrap">
                        <?php if (!empty($row['description'])): ?>
                            <?php $preview = preview_text($row['description']); ?>
                            <span class="desc-preview" data-preview="<?= esc($preview) ?>">hover</span>
                            <a class="desc-link" href="admin_ai.php?id=<?= (int)$row['id'] ?>&field=description">open</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td class="nowrap">
                        <?php if (!empty($row['price'])): ?>
                            <?= number_format((int)$row['price'], 0, ',', '.') . ' ' . esc($row['currency'] ?? 'EUR') ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td class="nowrap"><?= show_value($row['sqm']) ?></td>
                    <td class="nowrap"><?= show_value($row['rooms']) ?></td>
                    <td class="nowrap"><?= show_value($row['bathrooms']) ?></td>
                    <td class="nowrap"><?= show_value($row['plot_sqm']) ?></td>
                    <td><?= show_value($row['property_type']) ?></td>
                    <td><?= show_value($row['listing_type']) ?></td>
                    <td><?= show_value($row['address']) ?></td>
                    <td class="nowrap"><?= show_value($row['reference_id']) ?></td>
                    <td><?= show_value($row['agent_name']) ?></td>
                    <td class="nowrap"><?= show_value($row['agent_phone']) ?></td>
                    <td><?= show_value($row['agent_email']) ?></td>
                    <td class="nowrap"><?= show_value($row['first_seen_at']) ?></td>
                    <td class="nowrap"><?= show_value($row['last_seen_at']) ?></td>
                    <td class="nowrap"><?= show_value($row['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$ai_latest): ?>
                <tr>
                    <td colspan="20">No AI-parsed listings yet.</td>
                </tr>
            <?php endif; ?>
        </table>
        </div>
    </div>

    <p><a href="index.php">Back to homepage</a></p>
</div>
</body>
</html>
