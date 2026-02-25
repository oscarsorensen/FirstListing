<?php

session_start();

// DB-forbindelse
require_once __DIR__ . '/../../config/db.php';

// Simpel escaping til HTML-output
function esc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Vis tom værdi som streg
function show_value($value) {
    if ($value === null || $value === '') {
        return '—';
    }
    return esc($value);
}

// Kort preview af lang tekst
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

// Link hvis værdi findes, ellers streg
function link_or_dash($url, $label = 'link') {
    if (!$url) {
        return '—';
    }
    return '<a href="' . esc($url) . '" target="_blank">' . esc($label) . '</a>';
}

// Link til raw felt hvis længde findes
function raw_len_link($id, $field, $len) {
    if (!$len) {
        return '—';
    }
    return '<a href="admin_raw.php?id=' . (int)$id . '&field=' . esc($field) . '">' . show_value($len) . '</a>';
}

// Tjek om crawler-proces stadig kører ud fra PID-fil
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

// Omsæt domæne til firmanavn i tabellen
function company_name(?string $domain): string
{
    $d = strtolower(trim((string)$domain));
    if ($d === '') {
        return '—';
    }

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

    $host = preg_replace('/^www\./', '', $d) ?? $d;
    $host = preg_replace('/\.(com|es|net|org|eu)$/', '', $host) ?? $host;
    $host = str_replace(['-', '_'], ' ', $host);
    return ucwords(trim($host)) ?: '—';
}

// Grundopsætning
$target_db = 'test2firstlisting';
$openai_model = 'gpt-4.1-mini';
$project_root = realpath(__DIR__ . '/../../') ?: (__DIR__ . '/../../');
$crawler_pid_file = '/tmp/firstlisting_crawler.pid';
$crawler_log_file = '/tmp/firstlisting_crawler.log';
$pdo->exec("USE {$target_db}");

// Parse valgte raw IDs fra tabellen
$parse_feedback = [];
$crawler_feedback = $_SESSION['crawler_feedback'] ?? [];
unset($_SESSION['crawler_feedback']);
$selected_sites = ['jensenestate'];
$crawl_max_listings = 50;
$action = (string)($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'parse_selected') {
    $selectedIds = $_POST['raw_ids'] ?? [];
    $ids = array_values(array_unique(array_filter(array_map('intval', is_array($selectedIds) ? $selectedIds : []), fn($v) => $v > 0)));

    if (!$ids) {
        $parse_feedback[] = 'No rows selected.';
    } else {
        $phpBin = escapeshellarg('/opt/homebrew/bin/php');
        $scriptPath = escapeshellarg(__DIR__ . '/../../scripts/openai_parse_raw_pages.php');
        $ok = 0;
        $failed = 0;

        foreach ($ids as $id) {
            $output = [];
            $code = 1;
            $cmd = $phpBin . ' ' . $scriptPath . ' --id=' . (int)$id . ' --limit=1 --force 2>&1';
            exec($cmd, $output, $code);
            if ($code === 0) {
                $ok++;
            } else {
                $failed++;
                $firstLine = trim((string)($output[0] ?? 'Parser error'));
                $parse_feedback[] = "ID {$id} failed: {$firstLine}";
            }
        }

        $parse_feedback[] = "Parse complete. Success: {$ok}, Failed: {$failed}.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'crawler_control') {
    $selected_sites = $_POST['crawl_sites'] ?? [];
    if (!is_array($selected_sites)) {
        $selected_sites = [];
    }
    $selected_sites = array_values(array_unique(array_map('strval', $selected_sites)));
    $crawl_max_listings = max(1, (int)($_POST['crawl_max_listings'] ?? 50));
    $cmd = (string)($_POST['crawler_cmd'] ?? '');

    if ($cmd === 'run') {
        if (!in_array('jensenestate', $selected_sites, true)) {
            $crawler_feedback[] = 'Select at least one site.';
        } elseif (crawler_is_running($crawler_pid_file)) {
            $crawler_feedback[] = 'Crawler is already running.';
        } else {
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

$all_domains = $pdo->query('SELECT DISTINCT domain FROM raw_pages ORDER BY domain')->fetchAll(PDO::FETCH_COLUMN);

$domain_filter = trim($_GET['domain'] ?? '');
$has_jsonld = isset($_GET['has_jsonld']);
$has_html = isset($_GET['has_html']);
$has_text = isset($_GET['has_text']);
$status_filter = trim($_GET['status'] ?? '');

// Byg filtre til raw_pages tabellen
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

$sql .= ' ORDER BY is_parsed ASC, rp.fetched_at DESC LIMIT 50';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$raw_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hent seneste AI-resultater
$ai_latest = $pdo->query('
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
    ORDER BY ai.created_at DESC
    LIMIT 20
')->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin dashboard v2</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="container">
    <div style="text-align: right;">
        <a href="../index.php">Back to homepage</a>
    </div>
    <h1>Admin dashboard v2</h1>
    <div class="muted">Raw crawl overview + AI pipeline status</div>

    <div class="row">
        <div class="panel ai-test-panel" style="flex: 1;">
            <h2>OpenAI Test Panel</h2>
            <div class="ai-test-grid">
                <div>
                    <div class="muted">Simple OpenAI test (prompt -> response).</div>

                    <form id="openai-test-form" class="ai-test-form">
                        <div class="muted">Model: <strong><?= esc($openai_model) ?></strong></div>

                        <div class="field">
                            <label for="openai_prompt">Prompt</label>
                            <textarea id="openai_prompt" name="openai_prompt" rows="5" placeholder="Ask something, e.g. extract fields from this HTML..."></textarea>
                        </div>

                        <div class="actions">
                            <button type="submit">Run test</button>
                        </div>
                    </form>
                </div>
                <div>
                    <label class="muted">Response</label>
                    <pre id="openai-response-box" class="ai-output"></pre>
                </div>
            </div>
        </div>
        <div class="panel" style="flex: 1;">
            <h2>Crawler Control</h2>
            <div class="muted">Status: <strong><?= $crawler_running ? 'Running' : 'Stopped' ?></strong></div>
            <div class="muted">Log: <?= esc($crawler_log_file) ?></div>
            <?php if ($crawler_feedback): ?>
                <ul>
                    <?php foreach ($crawler_feedback as $line): ?>
                        <li><?= esc($line) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="action" value="crawler_control">
                <div class="field">
                    <label>Sites to crawl</label>
                    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                        <label class="check-label">
                            <input type="checkbox" name="crawl_sites[]" value="jensenestate" <?= in_array('jensenestate', $selected_sites, true) ? 'checked' : '' ?>>
                            Jensen Estate
                        </label>
                        <span class="muted">Crawled: <strong><?= $crawled_total ?> / 761</strong></span>
                        <label class="check-label" for="crawl_max_listings" style="display:flex; gap:8px; align-items:center;">
                            Max listings
                            <input type="number" id="crawl_max_listings" name="crawl_max_listings" min="1" step="1" value="<?= (int)$crawl_max_listings ?>" style="width:90px;">
                        </label>
                    </div>
                </div>
                <div class="actions">
                    <button type="submit" name="crawler_cmd" value="run">Run Crawler</button>
                    <button type="submit" name="crawler_cmd" value="stop">Stop Crawler</button>
                    <button type="submit" name="crawler_cmd" value="clear_log">Clear Log</button>
                </div>
            </form>

            <div class="field">
                <label for="crawler-log-box">Crawler log (live)</label>
                <pre id="crawler-log-box" class="ai-output" style="min-height: 180px;">Loading log...</pre>
            </div>
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
        <?php if ($parse_feedback): ?>
            <ul>
                <?php foreach ($parse_feedback as $line): ?>
                    <li><?= esc($line) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="actions">
            <button type="button" id="select-all-unparsed">Select all unparsed</button>
            <button type="button" id="deselect-all-unparsed">Deselect all</button>
            <button type="submit" id="parse-selected-btn" form="parse-unparsed-form">Parse selected</button>
            <span id="parse-loading" class="muted" style="display: none;">Parsing</span>
        </div>
    </div>

    <div class="table-wrap" style="max-height: 800px; overflow: auto;">
        <form method="post" id="parse-unparsed-form">
            <input type="hidden" name="action" value="parse_selected">
            <table>
                <tr>
                    <th>Parsed</th>
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
                    <?php $isParsed = (int)$row['is_parsed'] === 1; ?>
                    <tr>
                        <td class="nowrap">
                            <?php if ($isParsed): ?>
                                Yes
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
                    </tr>
                <?php endforeach; ?>
                <?php if (!$raw_pages): ?>
                    <tr>
                        <td colspan="11">No raw pages match the filters.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </form>
    </div>

    <div class="panel">
        <h2>AI-parsed listings </h2>
        <div class="table-wrap" style="max-height: 800px; overflow: auto;">
        <table>
            <tr>
                <th>ID</th>
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
const openAiForm = document.getElementById('openai-test-form');
const openAiPrompt = document.getElementById('openai_prompt');
const openAiResponseBox = document.getElementById('openai-response-box');

openAiForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    const prompt = openAiPrompt.value.trim();
    if (!prompt) {
        openAiResponseBox.textContent = 'Write a prompt first.';
        return;
    }

    openAiResponseBox.textContent = 'Loading...';

    try {
        const body = new URLSearchParams();
        body.set('prompt', prompt);

        const res = await fetch('openai_test.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: body.toString()
        });

        const text = await res.text();
        openAiResponseBox.textContent = text || 'No response returned.';
    } catch (err) {
        openAiResponseBox.textContent = 'Request failed.';
    }
});

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
let parseLoadingTimer = null;
if (parseSelectedBtn && parseLoading) {
    parseSelectedBtn.addEventListener('click', function () {
        parseLoading.style.display = 'inline';
        let dots = 0;
        parseLoading.textContent = 'Parsing';
        parseLoadingTimer = setInterval(function () {
            dots = (dots + 1) % 4;
            parseLoading.textContent = 'Parsing' + '.'.repeat(dots);
        }, 300);
    });
}

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
setInterval(refreshCrawlerLog, 2500);
</script>
</body>
</html>
