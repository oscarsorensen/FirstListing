<?php

session_start();

// Block access if not logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Connect to the database
require_once __DIR__ . '/../../config/db.php';

// Escape a value for safe HTML output
function esc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Return a dash if the value is empty, otherwise return it escaped
function show_value($value) {
    if ($value === null || $value === '') {
        return '—';
    }
    return esc($value);
}

// Return a short preview of a long text, trimmed to the character limit
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

// Return a clickable link if the URL exists, otherwise a dash
function link_or_dash($url, $label = 'link') {
    if (!$url) {
        return '—';
    }
    return '<a href="' . esc($url) . '" target="_blank">' . esc($label) . '</a>';
}

// Return a link to the raw data viewer if the field has content, otherwise a dash
function raw_len_link($id, $field, $len) {
    if (!$len) {
        return '—';
    }
    return '<a href="admin_raw.php?id=' . (int)$id . '&field=' . esc($field) . '">' . show_value($len) . '</a>';
}

// Check if the crawler process is still running by reading its PID from a file
function crawler_is_running($pidFile) {
    if (!is_file($pidFile)) {
        return false;
    }
    $pid = (int)trim((string)@file_get_contents($pidFile));
    if ($pid <= 0) {
        return false;
    }
    $out = [];
    $code = 1;
    exec('ps -p ' . $pid . ' -o pid=', $out, $code);
    return $code === 0 && !empty($out);
}

// Convert a domain name to a readable company name for display in the table
function company_name(?string $domain): string
{
    $d = strtolower(trim((string)$domain));
    if ($d === '') {
        return '—';
    }

    // Known domains mapped to their company names
    $map = [
        'movr.es' => 'Movr',
        'www.movr.es' => 'Movr',
        'jensenestate.es' => 'Jensen Estate',
        'www.jensenestate.es' => 'Jensen Estate',
        'mediter.com' => 'Mediter Real Estate',
        'www.mediter.com' => 'Mediter Real Estate',
        'costablancabolig.com' => 'Costa Blanca Bolig',
        'www.costablancabolig.com' => 'Costa Blanca Bolig',
    ];

    if (isset($map[$d])) {
        return $map[$d];
    }

    // Fallback: clean up the domain name into something readable
    $host = preg_replace('/^www\./', '', $d) ?? $d;
    $host = preg_replace('/\.(com|es|net|org|eu)$/', '', $host) ?? $host;
    $host = str_replace(['-', '_'], ' ', $host);
    return ucwords(trim($host)) ?: '—';
}

// Basic setup — paths, database, and default values
$target_db = 'test2firstlisting';
$project_root = realpath(__DIR__ . '/../../') ?: (__DIR__ . '/../../');
$crawler_pid_file = '/tmp/firstlisting_crawler.pid';
$crawler_log_file = '/tmp/firstlisting_crawler.log';
$pdo->exec("USE {$target_db}");

// Initialise feedback arrays and read the POST action
$parse_feedback = [];
$crawler_feedback = $_SESSION['crawler_feedback'] ?? [];
unset($_SESSION['crawler_feedback']);
$selected_sites = ['jensenestate'];
// Use the last value the user ran the crawler with, fall back to 50
$crawl_max_listings = (int)($_SESSION['crawl_max_listings'] ?? 50);
$action = (string)($_POST['action'] ?? '');

// Handle the "Parse selected" form — runs the AI parser on the chosen raw pages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'parse_selected') {
    $selectedIds = $_POST['raw_ids'] ?? [];
    // Clean the IDs: convert to integers, remove duplicates and zeros
    $ids = array_values(array_unique(array_filter(array_map('intval', is_array($selectedIds) ? $selectedIds : []), fn($v) => $v > 0)));

    if (!$ids) {
        $parse_feedback[] = 'No rows selected.';
    } else {
        $phpBin = escapeshellarg('/opt/homebrew/bin/php');
        $scriptPath = escapeshellarg(__DIR__ . '/../../scripts/openai_parse_raw_pages.php');
        $ok = 0;
        $failed = 0;

        // Remove the time limit — OpenAI calls can take more than 30 seconds
        set_time_limit(0);

        // Release the session lock so other requests to this page are not blocked
        // while the parse loop runs (PHP holds the session file locked by default)
        session_write_close();

        // Run the parser script once for each selected ID
        foreach ($ids as $id) {
            $output = [];
            $code = 1;
            $cmd = $phpBin . ' ' . $scriptPath . ' --id=' . (int)$id . ' --limit=1 --force 2>&1';
            exec($cmd, $output, $code);
            // Show the parser output for every ID (success or failure) so we can diagnose issues
            $outputText = trim(implode(' | ', array_filter(array_map('trim', $output))));
            if ($code === 0) {
                $ok++;
                $parse_feedback[] = "ID {$id} OK: " . ($outputText ?: '(no output)');

                // Log this listing to the JSONL file (non-relational, append-only record)
                $rp = $pdo->prepare('SELECT url, domain FROM raw_pages WHERE id = :id LIMIT 1');
                $rp->execute([':id' => $id]);
                $rpRow = $rp->fetch(PDO::FETCH_ASSOC);
                if ($rpRow) {
                    $logEntry = json_encode([
                        'timestamp'   => date('c'),
                        'url'         => $rpRow['url'],
                        'domain'      => $rpRow['domain'],
                        'raw_page_id' => $id,
                        'action'      => 'parsed',
                    ], JSON_UNESCAPED_UNICODE) . "\n";
                    file_put_contents($project_root . '/data/crawl_log.jsonl', $logEntry, FILE_APPEND | LOCK_EX);
                }
            } else {
                $failed++;
                $parse_feedback[] = "ID {$id} FAILED (exit {$code}): " . ($outputText ?: '(no output)');
            }
        }

        $parse_feedback[] = "Parse complete. Success: {$ok}, Failed: {$failed}.";
    }
}

// Handle the delete raw page form — removes a raw page and its AI listing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_raw') {
    $deleteId = (int)($_POST['raw_id'] ?? 0);
    if ($deleteId > 0) {
        // Delete the AI listing first (references raw_page_id), then the raw page
        $stmt = $pdo->prepare('DELETE FROM ai_listings WHERE raw_page_id = :id');
        $stmt->execute([':id' => $deleteId]);
        $stmt = $pdo->prepare('DELETE FROM raw_pages WHERE id = :id');
        $stmt->execute([':id' => $deleteId]);
        $_SESSION['crawler_feedback'] = ["Deleted raw page ID {$deleteId}."];
    }
    header('Location: admin.php');
    exit;
}

// Handle the crawler control form (run / stop / clear log)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'crawler_control') {
    $selected_sites = $_POST['crawl_sites'] ?? [];
    if (!is_array($selected_sites)) {
        $selected_sites = [];
    }
    $selected_sites = array_values(array_unique(array_map('strval', $selected_sites)));
    $crawl_max_listings = max(1, (int)($_POST['crawl_max_listings'] ?? 50));
    // Remember this value for next time the page loads
    $_SESSION['crawl_max_listings'] = $crawl_max_listings;
    $cmd = (string)($_POST['crawler_cmd'] ?? '');

    if ($cmd === 'run') {
        if (!in_array('jensenestate', $selected_sites, true)) {
            $crawler_feedback[] = 'Select at least one site.';
        } elseif (crawler_is_running($crawler_pid_file)) {
            $crawler_feedback[] = 'Crawler is already running.';
        } else {
            // Start the crawler as a background process and save its PID to a file
            $pythonBin = escapeshellarg('/opt/homebrew/bin/python3');
            $scriptPath = escapeshellarg($project_root . '/python/crawler_v4.py');
            $workdir = escapeshellarg($project_root);
            $logPath = escapeshellarg($crawler_log_file);
            $maxListingsArg = '--max-listings=' . (int)$crawl_max_listings;
            $out = [];
            $code = 1;
            $runCmd = "cd {$workdir} && {$pythonBin} -u {$scriptPath} {$maxListingsArg} > {$logPath} 2>&1 & echo $!";
            exec($runCmd, $out, $code);
            $pid = (int)trim((string)($out[0] ?? '0'));
            if ($code === 0 && $pid > 0) {
                @file_put_contents($crawler_pid_file, (string)$pid);
                $crawler_feedback[] = "Crawler started (PID {$pid}).";
            } else {
                $crawler_feedback[] = 'Could not start crawler.';
            }
        }
    } elseif ($cmd === 'stop') {
        // Kill the crawler process using the saved PID
        if (!crawler_is_running($crawler_pid_file)) {
            $crawler_feedback[] = 'Crawler is not running.';
            @unlink($crawler_pid_file);
        } else {
            $pid = (int)trim((string)@file_get_contents($crawler_pid_file));
            $out = [];
            $code = 1;
            exec('kill ' . $pid, $out, $code);
            @unlink($crawler_pid_file);
            $crawler_feedback[] = $code === 0 ? 'Crawler stopped.' : 'Could not stop crawler.';
        }
    } elseif ($cmd === 'clear_log') {
        // Wipe the log file contents
        if (@file_put_contents($crawler_log_file, '') !== false) {
            $crawler_feedback[] = 'Crawler log cleared.';
        } else {
            $crawler_feedback[] = 'Could not clear crawler log.';
        }
    }

    // Store feedback in session and redirect — this prevents the form from
    // being resubmitted if the user reloads the page (PRG pattern)
    $_SESSION['crawler_feedback'] = $crawler_feedback;
    header('Location: admin.php');
    exit;
}

$crawler_running = crawler_is_running($crawler_pid_file);
$crawled_total = (int)$pdo->query('SELECT COUNT(*) FROM raw_pages')->fetchColumn();

// Count how many raw pages have been parsed by AI
$parsed_total = (int)$pdo->query('SELECT COUNT(DISTINCT raw_page_id) FROM ai_listings')->fetchColumn();
$unparsed_total = $crawled_total - $parsed_total;

// Count registered users and total searches run
$user_total    = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$search_total  = (int)$pdo->query('SELECT SUM(searches_used) FROM search_usage')->fetchColumn();

$all_domains = $pdo->query('SELECT DISTINCT domain FROM raw_pages ORDER BY domain')->fetchAll(PDO::FETCH_COLUMN);

$domain_filter = trim($_GET['domain'] ?? '');
$has_jsonld = isset($_GET['has_jsonld']);
$has_html = isset($_GET['has_html']);
$has_text = isset($_GET['has_text']);
$status_filter = trim($_GET['status'] ?? '');
$sort_raw = trim($_GET['sort_raw'] ?? '');
$sort_ai  = trim($_GET['sort_ai'] ?? '');

// Build WHERE filters for the raw_pages query based on the active filter selections
$where = [];
$params = [];

if ($domain_filter !== '') {
    $where[] = 'rp.domain = :domain';
    $params[':domain'] = $domain_filter;
}

if ($has_jsonld) {
    $where[] = 'rp.jsonld_raw IS NOT NULL AND rp.jsonld_raw <> ""';
}

if ($has_html) {
    $where[] = 'rp.html_raw IS NOT NULL AND rp.html_raw <> ""';
}

if ($has_text) {
    $where[] = 'rp.text_raw IS NOT NULL AND rp.text_raw <> ""';
}

if ($status_filter !== '') {
    $where[] = 'rp.http_status = :http_status';
    $params[':http_status'] = $status_filter;
}

$sql = '
    SELECT
        rp.id,
        rp.url,
        rp.domain,
        rp.first_seen_at,
        rp.fetched_at,
        rp.http_status,
        rp.content_type,
        LENGTH(rp.html_raw) AS html_len,
        LENGTH(rp.text_raw) AS text_len,
        LENGTH(rp.jsonld_raw) AS jsonld_len,
        EXISTS(SELECT 1 FROM ai_listings ai WHERE ai.raw_page_id = rp.id) AS is_parsed
    FROM raw_pages rp
';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= $sort_raw === 'id'
    ? ' ORDER BY rp.id DESC LIMIT 200'
    : ' ORDER BY is_parsed ASC, rp.fetched_at DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$raw_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch the latest AI-parsed listings to show in the table
$ai_order = $sort_ai === 'id' ? 'ai.id DESC' : 'ai.created_at DESC';
$ai_latest = $pdo->query("
    SELECT
        ai.id,
        ai.raw_page_id,
        rp.url AS raw_url,
        rp.domain AS raw_domain,
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
    ORDER BY {$ai_order}
    LIMIT 200
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="container">
    <div class="page-header">
        <div>
            <h1>Admin</h1>
            <div class="subtitle">Raw crawl overview + AI pipeline status</div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <a href="../index.php" class="btn-ghost">Back to homepage</a>
            <a href="admin_logout.php" class="btn-ghost">Logout</a>
        </div>
    </div>

    <!-- Stats cards -->
    <div class="cards">
        <div class="card">
            <div class="label">Raw pages</div>
            <div class="value"><?= $crawled_total ?></div>
        </div>
        <div class="card">
            <div class="label">Parsed</div>
            <div class="value"><?= $parsed_total ?></div>
        </div>
        <div class="card">
            <div class="label">Unparsed</div>
            <div class="value"><?= $unparsed_total ?></div>
        </div>
        <div class="card">
            <div class="label">Users</div>
            <div class="value"><?= $user_total ?></div>
        </div>
        <div class="card">
            <div class="label">Searches run</div>
            <div class="value"><?= $search_total ?></div>
        </div>
    </div>

    <div class="panel">
        <h2>Crawler Control</h2>
        <span class="badge <?= $crawler_running ? 'badge-running' : 'badge-stopped' ?>">
            <?= $crawler_running ? 'Running' : 'Stopped' ?>
        </span>
        <?php if ($crawler_feedback): ?>
            <ul style="margin-top: 10px;">
                <?php foreach ($crawler_feedback as $line): ?>
                    <li><?= esc($line) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="action" value="crawler_control">
            <div class="field">
                <label>Sites to crawl</label>
                <div class="crawler-options">
                    <label class="check-label">
                        <input type="checkbox" name="crawl_sites[]" value="jensenestate" <?= in_array('jensenestate', $selected_sites, true) ? 'checked' : '' ?>>
                        Jensen Estate
                    </label>
                    <label class="check-label" for="crawl_max_listings">
                        - Listings per run
                        <input type="number" id="crawl_max_listings" name="crawl_max_listings" min="1" step="1" value="<?= (int)$crawl_max_listings ?>" style="width:80px; margin-left:6px;">
                    </label>
                </div>
            </div>
            <div class="actions">
                <button type="submit" name="crawler_cmd" value="run" id="run-crawler-btn">Run Crawler</button>
                <button type="submit" name="crawler_cmd" value="stop" class="btn-danger">Stop Crawler</button>
                <button type="submit" name="crawler_cmd" value="clear_log" class="btn-ghost">Clear Log</button>
                <div id="crawl-loading" class="parse-loading-bar" style="display: none;">
                    <span class="parse-spinner"></span>
                    <span>Crawling...</span>
                </div>
            </div>
        </form>

        <div class="field">
            <label for="crawler-log-box">Crawler log (live)</label>
            <pre id="crawler-log-box" class="ai-output" style="min-height: 180px;">Loading log...</pre>
        </div>
    </div>

    <div class="panel" id="raw-table">
        <h2>Raw pages</h2>
        <?php if ($parse_feedback): ?>
            <ul>
                <?php foreach ($parse_feedback as $line): ?>
                    <li><?= esc($line) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- Filters (GET form — changes the URL to filter the table below) -->
        <form method="get" class="filter-row">
            <div class="filter-field">
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
            <div class="filter-field">
                <label for="status">HTTP status</label>
                <input type="text" name="status" id="status" value="<?= esc($status_filter) ?>" placeholder="200">
            </div>
            <div class="filter-checks">
                <label class="check-label"><input type="checkbox" name="has_jsonld" <?= $has_jsonld ? 'checked' : '' ?>> JSON-LD</label>
                <label class="check-label"><input type="checkbox" name="has_html" <?= $has_html ? 'checked' : '' ?>> HTML</label>
                <label class="check-label"><input type="checkbox" name="has_text" <?= $has_text ? 'checked' : '' ?>> Text</label>
            </div>
            <div class="filter-submit">
                <button type="submit">Apply</button>
            </div>
        </form>

        <!-- Parse actions (POST form submit tied to the table below) -->
        <div class="actions">
            <button type="button" id="select-all-unparsed">Select all unparsed</button>
            <button type="button" id="deselect-all-unparsed" class="btn-ghost">Deselect all</button>
            <button type="submit" id="parse-selected-btn" form="parse-unparsed-form">Parse selected</button>
            <div id="parse-loading" class="parse-loading-bar" style="display: none;">
                <span class="parse-spinner"></span>
                <span id="parse-loading-text">Parsing...</span>
                <span id="parse-eta" class="parse-eta-text"></span>
            </div>
        </div>
    </div>

    <!-- Standalone delete form — outside the parse form to avoid nested form issues -->
    <form method="post" id="delete-raw-form" style="display:none;">
        <input type="hidden" name="action" value="delete_raw">
        <input type="hidden" name="raw_id" id="delete-raw-id" value="">
    </form>

    <div class="table-wrap">
        <form method="post" id="parse-unparsed-form">
            <input type="hidden" name="action" value="parse_selected">
            <table>
                <?php
                $raw_sort_params = $_GET;
                if ($sort_raw === 'id') { unset($raw_sort_params['sort_raw']); } else { $raw_sort_params['sort_raw'] = 'id'; }
                $raw_sort_url = '?' . http_build_query($raw_sort_params);
                ?>
                <tr>
                    <th>Parsed</th>
                    <th>ID <a href="<?= esc($raw_sort_url) ?>#raw-table" class="sort-btn <?= $sort_raw === 'id' ? 'sort-btn-active' : '' ?>">sort</a></th>
                    <th>URL</th>
                    <th>Domain</th>
                    <th>First seen</th>
                    <th>Last seen</th>
                    <th>Status</th>
                    <th>Content type</th>
                    <th>HTML len</th>
                    <th>Text len</th>
                    <th>JSON-LD len</th>
                    <th></th>
                </tr>
                <?php foreach ($raw_pages as $row): ?>
                    <?php $isParsed = (int)$row['is_parsed'] === 1; ?>
                    <tr>
                        <td class="nowrap">
                            <?php if ($isParsed): ?>
                                <span class="tag-parsed">Yes</span>
                            <?php else: ?>
                                <label>
                                    No
                                    <input type="checkbox" class="parse-checkbox" name="raw_ids[]" value="<?= (int)$row['id'] ?>">
                                </label>
                            <?php endif; ?>
                        </td>
                        <td class="nowrap"><?= (int)$row['id'] ?></td>
                        <td class="nowrap"><?= link_or_dash($row['url'] ?? null, 'link') ?></td>
                        <td><?= show_value($row['domain']) ?></td>
                        <td class="nowrap"><?= show_value($row['first_seen_at']) ?></td>
                        <td class="nowrap"><?= show_value($row['fetched_at']) ?></td>
                        <td class="nowrap"><?= show_value($row['http_status']) ?></td>
                        <td><?= show_value($row['content_type']) ?></td>
                        <td class="nowrap"><?= raw_len_link($row['id'], 'html', $row['html_len']) ?></td>
                        <td class="nowrap"><?= raw_len_link($row['id'], 'text', $row['text_len']) ?></td>
                        <td class="nowrap"><?= raw_len_link($row['id'], 'jsonld', $row['jsonld_len']) ?></td>
                        <td class="nowrap">
                            <button type="button" class="btn-danger btn-sm delete-btn" data-id="<?= (int)$row['id'] ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$raw_pages): ?>
                    <tr>
                        <td colspan="12">No raw pages match the filters.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </form>
    </div>

    <div class="panel" id="ai-table">
        <h2>AI-parsed listings</h2>
        <div class="table-wrap">
        <table>
            <?php
            $ai_sort_params = $_GET;
            if ($sort_ai === 'id') { unset($ai_sort_params['sort_ai']); } else { $ai_sort_params['sort_ai'] = 'id'; }
            $ai_sort_url = '?' . http_build_query($ai_sort_params);
            ?>
            <tr>
                <th>ID <a href="<?= esc($ai_sort_url) ?>#ai-table" class="sort-btn <?= $sort_ai === 'id' ? 'sort-btn-active' : '' ?>">sort</a></th>
                <th>URL</th>
                <th>Company</th>
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
                <th>Created (in AI-parsed)</th>
            </tr>
            <?php foreach ($ai_latest as $row): ?>
                <tr>
                    <td class="nowrap"><?= (int)$row['id'] ?></td>
                    <td class="nowrap"><?= link_or_dash($row['raw_url'] ?? null, 'link') ?></td>
                    <td><?= esc(company_name($row['raw_domain'] ?? null)) ?></td>
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
                    <td colspan="21">No AI-parsed listings yet.</td>
                </tr>
            <?php endif; ?>
        </table>
        </div>
    </div>

</div>
<script>
const selectAllUnparsedBtn = document.getElementById('select-all-unparsed');
const deselectAllUnparsedBtn = document.getElementById('deselect-all-unparsed');
selectAllUnparsedBtn.addEventListener('click', function () {
    document.querySelectorAll('.parse-checkbox').forEach(function (cb) {
        cb.checked = true;
    });
});
deselectAllUnparsedBtn.addEventListener('click', function () {
    document.querySelectorAll('.parse-checkbox').forEach(function (cb) {
        cb.checked = false;
    });
});

const parseSelectedBtn = document.getElementById('parse-selected-btn');
const parseLoading = document.getElementById('parse-loading');
const parseLoadingText = document.getElementById('parse-loading-text');
const parseEta = document.getElementById('parse-eta');

if (parseSelectedBtn && parseLoading) {
    parseSelectedBtn.addEventListener('click', function () {
        const count = document.querySelectorAll('.parse-checkbox:checked').length;
        if (count === 0) return;

        // Roughly 4 seconds per item based on OpenAI API response time
        let secondsLeft = count * 4;

        parseLoading.style.display = 'inline-flex';
        parseLoadingText.textContent = 'Parsing ' + count + ' item' + (count !== 1 ? 's' : '') + '...';

        // Format seconds into a readable string
        function formatTime(s) {
            if (s >= 60) {
                const m = Math.floor(s / 60);
                const r = s % 60;
                return m + 'm' + (r > 0 ? ' ' + r + 's' : '');
            }
            return s + 's';
        }

        parseEta.textContent = '~' + formatTime(secondsLeft) + ' left';

        // Count down every second until done
        const etaTimer = setInterval(function () {
            secondsLeft--;
            if (secondsLeft <= 0) {
                parseEta.textContent = 'almost done...';
                clearInterval(etaTimer);
            } else {
                parseEta.textContent = '~' + formatTime(secondsLeft) + ' left';
            }
        }, 1000);
    });
}

// Show the crawling animation when the Run Crawler button is clicked
const runCrawlerBtn = document.getElementById('run-crawler-btn');
const crawlLoading = document.getElementById('crawl-loading');
if (runCrawlerBtn && crawlLoading) {
    runCrawlerBtn.addEventListener('click', function () {
        crawlLoading.style.display = 'inline-flex';
    });
}

// Only poll the crawler log while the crawler is actually running
const crawlerRunning = <?= $crawler_running ? 'true' : 'false' ?>;
const crawlerLogBox = document.getElementById('crawler-log-box');

async function refreshCrawlerLog() {
    if (!crawlerLogBox) return;
    try {
        const res = await fetch('crawler_log.php');
        const text = await res.text();
        crawlerLogBox.textContent = text || 'No log yet.';
    } catch (err) {
        crawlerLogBox.textContent = 'Could not load log.';
    }
}

refreshCrawlerLog();
if (crawlerRunning) {
    setInterval(refreshCrawlerLog, 2500);
}

// Ask for confirmation before deleting a raw page, then submit the standalone delete form
const deleteForm = document.getElementById('delete-raw-form');
const deleteRawId = document.getElementById('delete-raw-id');
document.querySelectorAll('.delete-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const id = btn.dataset.id;
        if (confirm('Delete raw page ID ' + id + '? This also removes its AI listing.')) {
            deleteRawId.value = id;
            deleteForm.submit();
        }
    });
});
</script>
</body>
</html>
