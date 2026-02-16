<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();

$searchInput = trim((string)($_POST['search_input'] ?? ''));
$limit = (int)($_POST['limit'] ?? 10);
$limit = max(1, min(50, $limit));
$rows = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($searchInput === '') {
        $error = 'Insert test data (URL or text) first.';
    } else {
        try {
            $sql = "
            SELECT
                rp.id AS raw_page_id,
                rp.url,
                rp.domain,
                rp.fetched_at,
                ai.id AS ai_id,
                ai.title,
                ai.price,
                ai.sqm,
                ai.rooms,
                ai.bathrooms,
                ai.reference_id,
                (
                    SELECT COUNT(*)
                    FROM ai_listings ai2
                    JOIN raw_pages rp2 ON rp2.id = ai2.raw_page_id
                    WHERE ai.id IS NOT NULL
                      AND ai2.id <> ai.id
                      AND (
                        (ai.reference_id IS NOT NULL AND ai.reference_id <> '' AND ai2.reference_id = ai.reference_id)
                        OR (ai.title IS NOT NULL AND ai.title <> '' AND ai2.title = ai.title)
                        OR (
                            ai.price IS NOT NULL AND ai.sqm IS NOT NULL AND ai.rooms IS NOT NULL
                            AND ai2.price = ai.price
                            AND ai2.sqm = ai.sqm
                            AND ai2.rooms = ai.rooms
                        )
                      )
                ) AS copy_hits
            FROM raw_pages rp
            LEFT JOIN ai_listings ai ON ai.raw_page_id = rp.id
            WHERE
                rp.url LIKE :q
                OR rp.domain LIKE :q
                OR ai.title LIKE :q
                OR ai.description LIKE :q
                OR ai.address LIKE :q
                OR ai.reference_id LIKE :q
            ORDER BY copy_hits DESC, rp.fetched_at DESC
            LIMIT :lim
        ";

            $stmt = $pdo->prepare($sql);
            $q = '%' . $searchInput . '%';
            $stmt->bindValue(':q', $q, PDO::PARAM_STR);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $error = 'Search failed: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User | FirstListing</title>
    <link rel="stylesheet" href="css/user.css">
    <style>
        .user-wrap { max-width: 1100px; margin: 34px auto 50px; }
        .user-card,
        .tool-card {
            background: #fff;
            border: 1px solid #cdd9ea;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 14px 28px rgba(33,47,75,.14);
        }
        .tool-card { margin-top: 16px; }
        .user-card h1 { margin-top: 0; }
        .data { display: grid; grid-template-columns: 130px 1fr; gap: 8px 12px; margin: 16px 0; }
        .k { color: #55637a; }
        .v { font-weight: 600; }
        .actions { display: flex; gap: 12px; }
        .btn { background: #3f72d9; color: #fff; text-decoration: none; border-radius: 8px; padding: 10px 14px; border: 0; cursor: pointer; }
        .link { color: #3f72d9; }
        .field { margin-bottom: 12px; }
        .field label { display: block; margin-bottom: 6px; color: #55637a; font-size: 13px; }
        .field input, .field textarea { width: 100%; border: 1px solid #cdd9ea; border-radius: 8px; padding: 10px; font-size: 14px; }
        .row { display: grid; grid-template-columns: 1fr 140px; gap: 12px; }
        .hint { color: #64748b; font-size: 12px; margin-top: 3px; }
        .error { color: #9f1239; background: #ffe4ec; border: 1px solid #f6b8ca; border-radius: 8px; padding: 8px 10px; margin-bottom: 12px; }
        .ok { color: #0f766e; background: #ecfeff; border: 1px solid #bae6fd; border-radius: 8px; padding: 8px 10px; margin-bottom: 12px; }
        .table-wrap { overflow-x: auto; margin-top: 12px; }
        table { width: 100%; border-collapse: collapse; min-width: 860px; }
        th, td { border-bottom: 1px solid #e4eaf5; padding: 8px 10px; text-align: left; font-size: 13px; vertical-align: top; }
        th { background: #f2f6fd; text-transform: uppercase; font-size: 11px; letter-spacing: .03em; }
        .muted-sm { color: #708199; font-size: 12px; }
    </style>
</head>
<body>
<div class="page user-wrap">
    <div class="user-card">
        <h1>User page</h1>
        <p class="muted">Simple user area (MVP).</p>

        <div class="data">
            <div class="k">User ID</div><div class="v"><?= (int)$_SESSION['user_id'] ?></div>
            <div class="k">Username</div><div class="v"><?= esc((string)($_SESSION['username'] ?? 'unknown')) ?></div>
            <div class="k">Email</div><div class="v"><?= esc((string)($_SESSION['user_email'] ?? '')) ?: '—' ?></div>
            <div class="k">Role</div><div class="v"><?= esc((string)$_SESSION['user_role']) ?></div>
            <div class="k">Database</div><div class="v"><?= esc($dbName) ?></div>
        </div>

        <div class="actions">
            <a class="btn" href="index.php">Go to homepage</a>
            <a class="link" href="logout.php">Logout</a>
        </div>
    </div>

    <div class="tool-card">
        <h2>Find Copies (Test)</h2>
        <p class="muted-sm">Insert URL/text from a listing and search for possible duplicates in stored data.</p>

        <?php if ($error !== ''): ?>
            <div class="error"><?= esc($error) ?></div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="ok">Search completed. Matches: <?= (int)count($rows) ?></div>
        <?php endif; ?>

        <form method="post" action="user.php">
            <div class="field">
                <label for="search_input">Test data (URL or text)</label>
                <textarea id="search_input" name="search_input" rows="4" placeholder="Paste URL, title, reference, or text from a listing..."><?= esc($searchInput) ?></textarea>
                <div class="hint">Tip: URL, title keyword, or reference code gives the best quick results.</div>
            </div>

            <div class="row">
                <div class="field">
                    <label for="limit">Result limit (1-50)</label>
                    <input id="limit" name="limit" type="number" min="1" max="50" value="<?= (int)$limit ?>">
                </div>
                <div class="field" style="display:flex; align-items:end;">
                    <button class="btn" type="submit">Find copies</button>
                </div>
            </div>
        </form>

        <?php if ($rows): ?>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Raw ID</th>
                        <th>URL</th>
                        <th>Domain</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>SQM</th>
                        <th>Rooms</th>
                        <th>Baths</th>
                        <th>Ref</th>
                        <th>Copy hits</th>
                    </tr>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= (int)$r['raw_page_id'] ?></td>
                            <td>
                                <?php if (!empty($r['url'])): ?>
                                    <a href="<?= esc((string)$r['url']) ?>" target="_blank">open</a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?= esc((string)($r['domain'] ?? '')) ?></td>
                            <td><?= esc((string)($r['title'] ?? '')) ?></td>
                            <td><?= $r['price'] !== null ? number_format((int)$r['price'], 0, ',', '.') : '—' ?></td>
                            <td><?= $r['sqm'] !== null ? (int)$r['sqm'] : '—' ?></td>
                            <td><?= $r['rooms'] !== null ? (int)$r['rooms'] : '—' ?></td>
                            <td><?= $r['bathrooms'] !== null ? (int)$r['bathrooms'] : '—' ?></td>
                            <td><?= esc((string)($r['reference_id'] ?? '')) ?: '—' ?></td>
                            <td><?= (int)($r['copy_hits'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
