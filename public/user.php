<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Allow up to 2 minutes — crawl + OpenAI parse can take 10–20 seconds
set_time_limit(120);

require_once __DIR__ . '/../config/db.php';

$ROOT    = dirname(__DIR__);
$PYTHON3 = '/opt/homebrew/bin/python3';
$PHP_BIN = '/opt/homebrew/bin/php';

// Result variables — filled in below if a URL was submitted
$submitted      = false;
$input_url      = '';
$errors         = [];
$status_crawled = null;  // true/false/null
$status_parsed  = null;
$status_matches = null;  // int or null
$listing        = null;
$raw_page       = null;
$candidates     = [];
$ai_comparisons = [];    // keyed by raw_page_id

// Run a shell command, capture output lines and exit code
function run_cmd(string $cmd): array
{
    exec($cmd . ' 2>&1', $output, $code);
    return [$output, $code];
}

// === PIPELINE — runs on form submit ===

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['listing_url'])) {
    $submitted = true;
    $input_url = trim((string)$_POST['listing_url']);

    if (!filter_var($input_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid URL.';
    } else {

        // Step 1: Crawl the URL. The crawler prints "RAW_PAGE_ID:N" on success.
        [$crawl_out, ] = run_cmd(
            $PYTHON3 . ' ' . escapeshellarg($ROOT . '/python/crawler_v4.py') . ' --url=' . escapeshellarg($input_url)
        );

        $raw_page_id = null;
        foreach ($crawl_out as $line) {
            if (preg_match('/^RAW_PAGE_ID:(\d+)$/', $line, $m)) {
                $raw_page_id = (int)$m[1];
                break;
            }
        }

        if ($raw_page_id === null) {
            $errors[]       = 'Could not fetch the URL. Check that the address is correct and reachable.';
            $status_crawled = false;
        } else {
            $status_crawled = true;

            // Step 2: Parse the crawled page with OpenAI
            run_cmd($PHP_BIN . ' ' . escapeshellarg($ROOT . '/scripts/openai_parse_raw_pages.php') . ' --id=' . $raw_page_id);

            $st = $pdo->prepare('SELECT * FROM ai_listings WHERE raw_page_id = :id LIMIT 1');
            $st->execute([':id' => $raw_page_id]);
            $listing = $st->fetch(PDO::FETCH_ASSOC) ?: null;

            $st2 = $pdo->prepare('SELECT * FROM raw_pages WHERE id = :id LIMIT 1');
            $st2->execute([':id' => $raw_page_id]);
            $raw_page = $st2->fetch(PDO::FETCH_ASSOC) ?: null;

            if (!$listing) {
                $errors[]      = 'AI parsing failed — could not extract structured data from the page.';
                $status_parsed = false;
            } else {
                $status_parsed = true;

                // Step 3: SQL duplicate scoring
                [$dupes_out, ] = run_cmd(
                    $PHP_BIN . ' ' . escapeshellarg($ROOT . '/scripts/find_duplicates.php') . ' --raw-id=' . $raw_page_id
                );

                $decoded = json_decode(implode('', $dupes_out), true);
                if (is_array($decoded) && !isset($decoded['error'])) {
                    $candidates = $decoded;
                }
                $status_matches = count($candidates);

                // Step 4: AI description comparison for the top 5 candidates that have descriptions
                $cand_ids = [];
                foreach (array_slice($candidates, 0, 5) as $c) {
                    if (!empty($c['description']) && !empty($listing['description'])) {
                        $cand_ids[] = (int)$c['raw_page_id'];
                    }
                }

                if (!empty($cand_ids)) {
                    [$ai_out, ] = run_cmd(
                        $PHP_BIN . ' ' . escapeshellarg($ROOT . '/scripts/ai_compare_descriptions.php') .
                        ' --raw-id=' . $raw_page_id . ' --candidates=' . escapeshellarg(implode(',', $cand_ids))
                    );

                    foreach (json_decode(implode('', $ai_out), true) ?: [] as $r) {
                        if (isset($r['raw_page_id'])) {
                            $ai_comparisons[(int)$r['raw_page_id']] = $r;
                        }
                    }
                }

                // Track searches per user per month
                $track = $pdo->prepare(
                    'INSERT INTO search_usage (user_id, month, searches_used) VALUES (:uid, :month, 1)
                     ON DUPLICATE KEY UPDATE searches_used = searches_used + 1'
                );
                $track->execute([':uid' => $_SESSION['user_id'], ':month' => date('Y-m')]);
            }
        }
    }
}

// === HELPERS ===

function pill_class(?bool $ok): string
{
    if ($ok === null) return 'pending';
    return $ok ? 'ok' : 'error';
}

function pill_text(?bool $ok, string $pending, string $yes, string $no): string
{
    if ($ok === null) return $pending;
    return $ok ? $yes : $no;
}

// Returns [label, css_class] for a match score badge
function score_info(int $score): array
{
    if ($score >= 10) return ['Very likely', 'score-high'];
    if ($score >= 7)  return ['Likely',      'score-med'];
    if ($score >= 4)  return ['Possible',    'score-low'];
    return ['Weak', 'score-low'];
}

// Renders the AI comparison result as an inline badge
function render_ai_badge(?array $ai): string
{
    if ($ai === null) return '<span style="color:#aaa;">—</span>';
    if ($ai['same_property'] === null) {
        return '<span style="color:#aaa;">' . esc((string)($ai['reason'] ?? '')) . '</span>';
    }
    $class = $ai['same_property'] ? 'ai-same' : 'ai-diff';
    $label = $ai['same_property'] ? 'Same property' : 'Different';
    $conf  = $ai['confidence'] !== null ? ' (' . number_format((float)$ai['confidence'] * 100, 0) . '%)' : '';
    return '<span class="ai-badge ' . $class . '" title="' . esc((string)($ai['reason'] ?? '')) . '">' . esc($label . $conf) . '</span>';
}

// Formats a nullable price as "1.234 €" or "—"
function fmt_price(?int $price): string
{
    return $price !== null ? esc(number_format($price, 0, '.', '.')) . ' €' : '—';
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
<div class="page">
    <header class="topbar">
        <div class="brand">
            <span class="dot"></span>
            <span>FirstListing</span>
        </div>
        <nav class="nav">
            <a href="index.php">Home</a>
            <a href="how.php">How it works</a>
            <a href="helps.php">Why it helps</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <section class="hero hero-shell page-hero page-hero-small">
        <div class="hero-bg page-hero-image" style="background-image: linear-gradient(110deg, rgba(8, 14, 24, 0.62) 10%, rgba(8, 14, 24, 0.42) 42%, rgba(8, 14, 24, 0.16) 78%), url('https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=1800&q=80');"></div>
        <div class="hero-overlay"></div>
        <div class="hero-text">
            <p class="eyebrow">User area · Duplicate checker</p>
            <h1>Paste a listing URL to find copies across portals.</h1>
            <p class="lead">
                The crawler fetches the page, AI extracts the structured fields, and then we
                compare them against every listing in our database to find possible duplicates.
            </p>
            <div class="meta">
                <span>User: <?= esc((string)($_SESSION['username'] ?? 'unknown')) ?></span>
                <span>Role: <?= esc((string)($_SESSION['user_role'] ?? 'user')) ?></span>
            </div>
        </div>
        <div class="hero-side">
            <div class="hero-note">
                <div class="hero-note-title">How it works</div>
                <ul>
                    <li>1. Crawler fetches the URL</li>
                    <li>2. AI extracts price, sqm, rooms…</li>
                    <li>3. SQL scores every DB listing</li>
                    <li>4. AI compares descriptions</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="user-layout">
        <div class="user-main">

            <?php foreach ($errors as $err): ?>
                <div class="error"><?= esc($err) ?></div>
            <?php endforeach; ?>

            <!-- URL input form -->
            <div class="tool-card user-tool-card">
                <div class="card-topline">Duplicate Check</div>
                <h2>Paste listing URL</h2>
                <p class="muted-sm">Paste a property URL. The check takes about 10–20 seconds.</p>
                <form method="post" action="user.php" class="user-search-form">
                    <div class="field">
                        <label for="listing_url">Listing URL</label>
                        <input id="listing_url" name="listing_url" type="url"
                               placeholder="https://example.com/listing/123"
                               value="<?= esc($input_url) ?>">
                    </div>
                    <div class="actions">
                        <button class="btn" type="submit">Check duplicates</button>
                        <a class="ghost ghost-light" href="index.php">Back to homepage</a>
                    </div>
                </form>
            </div>

            <!-- Pipeline status -->
            <div class="tool-card user-tool-card">
                <div class="card-topline">Search Status</div>
                <h2><?= $submitted ? 'Pipeline result' : 'Waiting for URL' ?></h2>
                <div class="state-list">
                    <div class="state-item">
                        <div class="state-label">URL crawled</div>
                        <div class="state-pill <?= pill_class($status_crawled) ?>">
                            <?= pill_text($status_crawled, 'Pending', 'OK', 'Failed') ?>
                        </div>
                    </div>
                    <div class="state-item">
                        <div class="state-label">AI parsed</div>
                        <div class="state-pill <?= pill_class($status_parsed) ?>">
                            <?= pill_text($status_parsed, 'Pending', 'OK', 'Failed') ?>
                        </div>
                    </div>
                    <div class="state-item">
                        <div class="state-label">Duplicate candidates found</div>
                        <div class="state-pill <?= $status_matches === null ? 'pending' : 'ok' ?>">
                            <?php
                            if ($status_matches === null)   echo 'Pending';
                            elseif ($status_matches === 0)  echo 'None found';
                            else                            echo $status_matches . ' found';
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Extracted listing fields -->
            <div class="tool-card user-tool-card">
                <div class="card-topline">Extracted Listing Data</div>
                <h2>Structured fields</h2>
                <div class="data data-compact">
                    <div class="k">URL</div>
                    <div class="v">
                        <?php if ($raw_page): ?>
                            <a class="link" href="<?= esc($raw_page['url']) ?>" target="_blank" rel="noopener"><?= esc($raw_page['url']) ?></a>
                        <?php else: ?>—<?php endif; ?>
                    </div>
                    <div class="k">Domain</div>
                    <div class="v"><?= $raw_page ? esc($raw_page['domain']) : '—' ?></div>
                    <div class="k">Title</div>
                    <div class="v"><?= $listing ? esc((string)($listing['title'] ?? '')) ?: '—' : '—' ?></div>
                    <div class="k">Price</div>
                    <div class="v"><?= $listing ? fmt_price($listing['price'] !== null ? (int)$listing['price'] : null) : '—' ?></div>
                    <div class="k">SQM</div>
                    <div class="v"><?= ($listing && $listing['sqm'] !== null) ? esc((string)$listing['sqm']) . ' m²' : '—' ?></div>
                    <div class="k">Plot SQM</div>
                    <div class="v"><?= ($listing && $listing['plot_sqm'] !== null) ? esc((string)$listing['plot_sqm']) . ' m²' : '—' ?></div>
                    <div class="k">Rooms</div>
                    <div class="v"><?= ($listing && $listing['rooms'] !== null) ? esc((string)$listing['rooms']) : '—' ?></div>
                    <div class="k">Baths</div>
                    <div class="v"><?= ($listing && $listing['bathrooms'] !== null) ? esc((string)$listing['bathrooms']) : '—' ?></div>
                    <div class="k">Type</div>
                    <div class="v"><?= $listing ? esc((string)($listing['property_type'] ?? '')) ?: '—' : '—' ?></div>
                    <div class="k">Listing</div>
                    <div class="v"><?= $listing ? esc((string)($listing['listing_type'] ?? '')) ?: '—' : '—' ?></div>
                    <div class="k">Address</div>
                    <div class="v"><?= $listing ? esc((string)($listing['address'] ?? '')) ?: '—' : '—' ?></div>
                    <div class="k">Reference</div>
                    <div class="v"><?= $listing ? esc((string)($listing['reference_id'] ?? '')) ?: '—' : '—' ?></div>
                    <div class="k">Agent</div>
                    <div class="v"><?= $listing ? esc((string)($listing['agent_name'] ?? '')) ?: '—' : '—' ?></div>
                    <div class="k">First seen</div>
                    <div class="v"><?= $raw_page ? esc((string)($raw_page['first_seen_at'] ?? '')) ?: '—' : '—' ?></div>
                    <div class="k">Last seen</div>
                    <div class="v"><?= $raw_page ? esc((string)($raw_page['fetched_at'] ?? '')) ?: '—' : '—' ?></div>
                </div>
            </div>

            <!-- Duplicate candidates table -->
            <div class="tool-card user-tool-card">
                <div class="card-topline">Possible Duplicates</div>
                <h2>
                    <?php
                    if (!$submitted)             echo 'Matches table';
                    elseif ($status_matches ===0) echo 'No duplicates found';
                    else echo ($status_matches ?? 0) . ' candidate' . (($status_matches ?? 0) === 1 ? '' : 's') . ' found';
                    ?>
                </h2>

                <?php if (!empty($candidates)): ?>
                    <div class="table-wrap" style="max-height: 460px; overflow:auto;">
                        <table>
                            <tr>
                                <th>Score</th><th>Domain</th><th>Title</th>
                                <th>Price</th><th>SQM</th><th>Rooms</th>
                                <th>First seen</th><th>AI result</th><th>URL</th>
                            </tr>
                            <?php foreach ($candidates as $c):
                                $score         = (int)$c['match_score'];
                                [$label, $css] = score_info($score);
                                $ai            = $ai_comparisons[(int)$c['raw_page_id']] ?? null;
                            ?>
                            <tr>
                                <td><span class="score-badge <?= $css ?>"><?= esc($label) ?> (<?= $score ?>)</span></td>
                                <td><?= esc((string)($c['domain'] ?? '—')) ?></td>
                                <td><?= esc((string)($c['title'] ?? '—')) ?></td>
                                <td><?= fmt_price($c['price'] !== null ? (int)$c['price'] : null) ?></td>
                                <td><?= $c['sqm'] !== null ? esc((string)$c['sqm']) . ' m²' : '—' ?></td>
                                <td><?= $c['rooms'] !== null ? esc((string)$c['rooms']) : '—' ?></td>
                                <td><?= esc((string)($c['first_seen_at'] ?? '—')) ?></td>
                                <td><?= render_ai_badge($ai) ?></td>
                                <td>
                                    <a class="link" href="<?= esc((string)($c['url'] ?? '#')) ?>" target="_blank" rel="noopener">
                                        <?= esc(parse_url((string)($c['url'] ?? ''), PHP_URL_HOST) ?: $c['url'] ?? '—') ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <p class="hint">Score = sum of matching fields (max 17). Hover AI badge for reason.</p>

                <?php elseif ($submitted && $status_parsed): ?>
                    <p class="muted-sm">No candidates scored 3 or higher. The listing may be unique in our database.</p>

                <?php else: ?>
                    <p class="hint">Submit a URL above to see duplicate candidates here.</p>
                <?php endif; ?>
            </div>

        </div><!-- /.user-main -->

        <aside class="user-side">
            <div class="user-card user-profile-card">
                <div class="card-topline">Account</div>
                <h2><?= esc((string)($_SESSION['username'] ?? 'unknown')) ?></h2>
                <p class="muted-sm">Logged in user area for duplicate checks.</p>
                <div class="data data-compact">
                    <div class="k">User ID</div><div class="v"><?= (int)$_SESSION['user_id'] ?></div>
                    <div class="k">Email</div><div class="v"><?= esc((string)($_SESSION['user_email'] ?? '')) ?: '—' ?></div>
                    <div class="k">Role</div><div class="v"><?= esc((string)($_SESSION['user_role'] ?? 'user')) ?></div>
                </div>
                <div class="actions">
                    <a class="btn" href="logout.php">Logout</a>
                </div>
            </div>

            <div class="tool-card user-tool-card">
                <div class="card-topline">Notes</div>
                <h2>MVP scope reminder</h2>
                <ul class="simple-list">
                    <li>"First seen" = first crawled, not when published</li>
                    <li>Not a legal claim of original ownership</li>
                    <li>Best results depend on crawl coverage</li>
                    <li>AI descriptions compared for top 5 SQL matches only</li>
                </ul>
            </div>
        </aside>
    </section>

    <footer class="footer">
        <span>FirstListing — User area</span>
        <a href="index.php">Back to home</a>
    </footer>
</div>
</body>
</html>
