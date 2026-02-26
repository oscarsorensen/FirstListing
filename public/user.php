<?php

// Start the session so we can read who is logged in
session_start();
// Load the database connection ($pdo)
require_once __DIR__ . '/../config/db.php';

// If not logged in, send to the login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Converts special characters to HTML entities to prevent XSS
function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Read search input and result limit from the form, with safe defaults
$searchInput = trim((string)($_POST['search_input'] ?? ''));
$limit = (int)($_POST['limit'] ?? 10);
// Clamp the limit between 1 and 50 so the user can't request an unlimited number of rows
$limit = max(1, min(50, $limit));
$rows = [];
$error = '';

// Only run the search when the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($searchInput === '') {
        $error = 'Insert test data (URL or text) first.';
    } else {
        try {
            // Find all raw pages (and their AI-extracted data) that match the search term.
            // The subquery counts how many OTHER listings share the same reference ID, title,
            // or price+sqm+rooms combination — that number is "copy_hits".
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
            // Wrap the search term in % wildcards so it matches anywhere in the field
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
</head>
<body>
<div class="page user-wrap">
    <div class="user-card">
        <h1>User page</h1>
        <p class="muted">Simple user area (MVP).</p>

        <!-- Display basic info from the session -->
        <div class="data">
            <div class="k">User ID</div><div class="v"><?= (int)$_SESSION['user_id'] ?></div>
            <div class="k">Username</div><div class="v"><?= esc((string)($_SESSION['username'] ?? 'unknown')) ?></div>
            <div class="k">Email</div><div class="v"><?= esc((string)($_SESSION['user_email'] ?? '')) ?: '—' ?></div>
            <div class="k">Role</div><div class="v"><?= esc((string)$_SESSION['user_role']) ?></div>
        </div>

        <div class="actions">
            <a class="btn" href="index.php">Go to homepage</a>
            <a class="link" href="logout.php">Logout</a>
        </div>
    </div>

    <div class="tool-card">
        <h2>Find Copies (Test)</h2>
        <p class="muted-sm">Insert URL/text from a listing and search for possible duplicates in stored data.</p>

        <!-- Show error or success message after a search -->
        <?php if ($error !== ''): ?>
            <div class="error"><?= esc($error) ?></div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="ok">Search completed. Matches: <?= (int)count($rows) ?></div>
        <?php endif; ?>

        <form method="post" action="user.php">
            <div class="field">
                <label for="search_input">Test data (URL or text)</label>
                <!-- Keep the typed text in the textarea after a search -->
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

        <!-- Only show the results table if there are rows to display -->
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
                            <!-- Format price with dot as thousands separator -->
                            <td><?= $r['price'] !== null ? number_format((int)$r['price'], 0, ',', '.') : '—' ?></td>
                            <td><?= $r['sqm'] !== null ? (int)$r['sqm'] : '—' ?></td>
                            <td><?= $r['rooms'] !== null ? (int)$r['rooms'] : '—' ?></td>
                            <td><?= $r['bathrooms'] !== null ? (int)$r['bathrooms'] : '—' ?></td>
                            <td><?= esc((string)($r['reference_id'] ?? '')) ?: '—' ?></td>
                            <!-- Higher copy_hits means more listings share the same data -->
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
