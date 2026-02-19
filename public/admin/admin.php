<?php

require_once __DIR__ . '/../../config/db.php';

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
        'costablancabolig.com' => 'Mediter Real Estate',
        'www.costablancabolig.com' => 'Mediter Real Estate',
    ];

    if (isset($map[$d])) {
        return $map[$d];
    }

    $host = preg_replace('/^www\./', '', $d) ?? $d;
    $host = preg_replace('/\.(com|es|net|org|eu)$/', '', $host) ?? $host;
    $host = str_replace(['-', '_'], ' ', $host);
    return ucwords(trim($host)) ?: '—';
}

$target_db = 'test2firstlisting';
$openai_model = 'gpt-4.1-mini';

$pdo->exec("USE {$target_db}");

$parse_feedback = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'parse_selected') {
    $selectedIds = $_POST['raw_ids'] ?? [];
    if (!is_array($selectedIds)) {
        $selectedIds = [];
    }

    $ids = [];
    foreach ($selectedIds as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
    $ids = array_values($ids);

    if (!$ids) {
        $parse_feedback[] = 'No rows selected.';
    } elseif (!function_exists('exec')) {
        $parse_feedback[] = 'Server does not allow running parser from admin (exec disabled).';
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
                $firstLine = trim((string)($output[0] ?? 'Unknown parser error'));
                $parse_feedback[] = "ID {$id} failed: {$firstLine}";
            }
        }

        $parse_feedback[] = "Parse complete. Success: {$ok}, Failed: {$failed}.";
    }
}

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

$stats['raw_total'] = (int)$pdo->query('SELECT COUNT(*) FROM raw_pages')->fetchColumn();
$stats['raw_with_html'] = (int)$pdo->query('SELECT COUNT(*) FROM raw_pages WHERE html_raw IS NOT NULL AND html_raw <> ""')->fetchColumn();
$stats['raw_with_text'] = (int)$pdo->query('SELECT COUNT(*) FROM raw_pages WHERE text_raw IS NOT NULL AND text_raw <> ""')->fetchColumn();
$stats['raw_with_jsonld'] = (int)$pdo->query('SELECT COUNT(*) FROM raw_pages WHERE jsonld_raw IS NOT NULL AND jsonld_raw <> ""')->fetchColumn();
$stats['raw_first_seen'] = $pdo->query('SELECT MIN(first_seen_at) FROM raw_pages')->fetchColumn();
$stats['raw_first_fetch'] = $pdo->query('SELECT MIN(fetched_at) FROM raw_pages')->fetchColumn();
$stats['raw_last_fetch'] = $pdo->query('SELECT MAX(fetched_at) FROM raw_pages')->fetchColumn();

$stats['ai_total'] = (int)$pdo->query('SELECT COUNT(*) FROM ai_listings')->fetchColumn();
$stats['ai_with_price'] = (int)$pdo->query('SELECT COUNT(*) FROM ai_listings WHERE price IS NOT NULL')->fetchColumn();
$stats['ai_with_sqm'] = (int)$pdo->query('SELECT COUNT(*) FROM ai_listings WHERE sqm IS NOT NULL')->fetchColumn();
$stats['ai_with_rooms'] = (int)$pdo->query('SELECT COUNT(*) FROM ai_listings WHERE rooms IS NOT NULL')->fetchColumn();
$stats['ai_with_address'] = (int)$pdo->query('SELECT COUNT(*) FROM ai_listings WHERE address IS NOT NULL AND address <> ""')->fetchColumn();

$domains_top = $pdo->query('
    SELECT domain, COUNT(*) AS cnt
    FROM raw_pages
    GROUP BY domain
    ORDER BY cnt DESC
    LIMIT 10
')->fetchAll(PDO::FETCH_ASSOC);

$all_domains = $pdo->query('SELECT DISTINCT domain FROM raw_pages ORDER BY domain')->fetchAll(PDO::FETCH_COLUMN);

$domain_filter = trim($_GET['domain'] ?? '');
$has_jsonld = isset($_GET['has_jsonld']);
$has_html = isset($_GET['has_html']);
$has_text = isset($_GET['has_text']);
$status_filter = trim($_GET['status'] ?? '');

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

$sql .= ' ORDER BY fetched_at DESC LIMIT 50';

$raw_pages = [];
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$raw_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ai_latest = [];
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
    <h1>Admin dashboard v2</h1>
    <div class="muted">Raw crawl overview + AI pipeline status</div>

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
            </ul>
        </div>
    </div>

    <div class="panel ai-test-panel">
        <h2>OpenAI Test Panel</h2>
        <div class="ai-test-grid">
            <div>
                <div class="muted">Runs through Python bridge (same setup as your terminal test).</div>

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
        </div>
    </div>

    <div class="table-wrap">
        <form method="post">
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
                        <td colspan="11">No raw pages match the filters.</td>
                    </tr>
                <?php endif; ?>
            </table>
            <div class="actions">
                <button type="submit">Parse selected unparsed</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <h2>Latest AI-parsed listings (if available)</h2>
        <div class="table-wrap">
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

    <p><a href="../index.php">Back to homepage</a></p>
</div>
<script>
const openAiForm = document.getElementById('openai-test-form');
const openAiPrompt = document.getElementById('openai_prompt');
const openAiResponseBox = document.getElementById('openai-response-box');

if (openAiForm && openAiPrompt && openAiResponseBox) {
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
}

const selectAllUnparsedBtn = document.getElementById('select-all-unparsed');
if (selectAllUnparsedBtn) {
    selectAllUnparsedBtn.addEventListener('click', function () {
        document.querySelectorAll('.parse-checkbox').forEach(function (cb) {
            cb.checked = true;
        });
    });
}
</script>
</body>
</html>
