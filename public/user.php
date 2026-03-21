<?php

/*
Frontend Test User
OscarFrontend2
123456
*/ 
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
$already_in_db  = false; // true if the submitted URL was already in raw_pages

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
            }
            // The crawler prints "[SEEN]" when the URL was already in the database
            if (str_contains($line, '[SEEN]')) {
                $already_in_db = true;
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
            <a href="index.php" lang-change="nav-home">Home</a>
            <a href="how.php" lang-change="nav-how">How it works</a>
            <a href="helps.php" lang-change="nav-helps">Why it helps</a>
            <a href="logout.php" lang-change="nav-logout">Logout</a>
            <button id="lang-toggle" class="lang-btn">ES</button>
        </nav>
    </header>

    <section class="hero hero-shell page-hero page-hero-small">
        <div class="hero-bg page-hero-image" style="background-image: linear-gradient(110deg, rgba(8, 14, 24, 0.62) 10%, rgba(8, 14, 24, 0.42) 42%, rgba(8, 14, 24, 0.16) 78%), url('https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=1800&q=80');"></div>
        <div class="hero-overlay"></div>
        <div class="hero-text">
            <p class="eyebrow" lang-change="usr-eyebrow">User area · Duplicate checker</p>
            <h1 lang-change="usr-h1">Paste a listing URL to find copies across portals.</h1>
            <p class="lead" lang-change="usr-lead">
                The crawler fetches the page, AI extracts the structured fields, and then we
                compare them against every listing in our database to find possible duplicates.
            </p>
            <div class="meta">
                <span><span lang-change="usr-meta-user">User:</span> <?= esc((string)($_SESSION['username'] ?? 'unknown')) ?></span>
                <span><span lang-change="usr-meta-role">Role:</span> <?= esc((string)($_SESSION['user_role'] ?? 'user')) ?></span>
            </div>
        </div>
        <div class="hero-side">
            <div class="hero-note">
                <div class="hero-note-title" lang-change="usr-note-title">How it works</div>
                <ul>
                    <li lang-change="usr-note-1">1. Crawler fetches the URL</li>
                    <li lang-change="usr-note-2">2. AI extracts price, sqm, rooms…</li>
                    <li lang-change="usr-note-3">3. SQL scores every DB listing</li>
                    <li lang-change="usr-note-4">4. AI compares descriptions</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="user-layout">
        <div class="user-main">

            <?php foreach ($errors as $err): ?>
                <div class="error"><?= esc($err) ?></div>
            <?php endforeach; ?>

            <?php if ($already_in_db && $raw_page): ?>
                <div class="notice-already-seen">
                    This URL is already in our database — first crawled on
                    <strong><?= esc((string)($raw_page['first_seen_at'] ?? '—')) ?></strong>.
                    Showing existing data and searching for cross-portal duplicates below.
                </div>
            <?php endif; ?>

            <!-- URL input form -->
            <div class="tool-card user-tool-card">
                <div class="card-topline" lang-change="usr-card1-top">Duplicate Check</div>
                <h2 lang-change="usr-card1-h2">Paste listing URL</h2>
                <p class="muted-sm" lang-change="usr-card1-sub">Paste a property URL. The check takes about 10–20 seconds.</p>
                <form method="post" action="user.php" class="user-search-form">
                    <div class="field">
                        <label for="listing_url" lang-change="usr-label-url">Listing URL</label>
                        <input id="listing_url" name="listing_url" type="url"
                               placeholder="https://example.com/listing/123"
                               value="<?= esc($input_url) ?>">
                    </div>
                    <div class="actions">
                        <button class="btn" type="submit" lang-change="usr-btn-check">Check duplicates</button>
                        <a class="ghost ghost-light" href="index.php" lang-change="usr-btn-back">Back to homepage</a>
                    </div>
                </form>
            </div>

            <!-- Pipeline status -->
            <div class="tool-card user-tool-card">
                <div class="card-topline" lang-change="usr-card2-top">Search Status</div>
                <h2><?= $submitted ? 'Pipeline result' : 'Waiting for URL' ?></h2>
                <div class="state-list">
                    <div class="state-item">
                        <div class="state-label" lang-change="usr-status-crawled">URL crawled</div>
                        <div class="state-pill <?= pill_class($status_crawled) ?>">
                            <?= pill_text($status_crawled, 'Pending', 'OK', 'Failed') ?>
                        </div>
                    </div>
                    <div class="state-item">
                        <div class="state-label" lang-change="usr-status-parsed">AI parsed</div>
                        <div class="state-pill <?= pill_class($status_parsed) ?>">
                            <?= pill_text($status_parsed, 'Pending', 'OK', 'Failed') ?>
                        </div>
                    </div>
                    <div class="state-item">
                        <div class="state-label" lang-change="usr-status-dupes">Duplicate candidates found</div>
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
                <div class="card-topline" lang-change="usr-card3-top">Extracted Listing Data</div>
                <h2 lang-change="usr-card3-h2">Structured fields</h2>
                <div class="data data-compact">
                    <div class="k" lang-change="usr-field-url">URL</div>
                    <div class="v">
                        <?php if ($raw_page): ?>
                            <a class="link" href="<?= esc($raw_page['url']) ?>" target="_blank" rel="noopener"><?= esc($raw_page['url']) ?></a>
                        <?php else: ?>—<?php endif; ?>
                    </div>
                    <div class="k" lang-change="usr-field-domain">Domain</div>
                    <div class="v"><?= $raw_page ? esc($raw_page['domain']) : '—' ?></div>
                    <div class="k" lang-change="usr-field-title">Title</div>
                    <div class="v"><?= $listing ? esc((string)($listing['title'] ?? '')) ?: '—' : '—' ?></div>
                    <div class="k" lang-change="usr-field-price">Price</div>
                    <div class="v"><?= $listing ? fmt_price($listing['price'] !== null ? (int)$listing['price'] : null) : '—' ?></div>
                    <div class="k" lang-change="usr-field-sqm">SQM</div>
                    <div class="v"><?= ($listing && $listing['sqm'] !== null) ? esc((string)$listing['sqm']) . ' m²' : '—' ?></div>
                    <div class="k" lang-change="usr-field-plotsqm">Plot SQM</div>
                    <div class="v"><?= ($listing && $listing['plot_sqm'] !== null) ? esc((string)$listing['plot_sqm']) . ' m²' : '—' ?></div>
                    <div class="k" lang-change="usr-field-rooms">Rooms</div>
                    <div class="v"><?= ($listing && $listing['rooms'] !== null) ? esc((string)$listing['rooms']) : '—' ?></div>
                    <div class="k" lang-change="usr-field-baths">Baths</div>
                    <div class="v"><?= ($listing && $listing['bathrooms'] !== null) ? esc((string)$listing['bathrooms']) : '—' ?></div>
                    <div class="k" lang-change="usr-field-type">Type</div>
                    <div class="v"><?= $listing ? esc((string)($listing['property_type'] ?? '')) ?: '—' : '—' ?></div>
                    <div class="k" lang-change="usr-field-listing">Listing</div>
                    <div class="v"><?= $listing ? esc((string)($listing['listing_type'] ?? '')) ?: '—' : '—' ?></div>
                    <div class="k" lang-change="usr-field-address">Address</div>
                    <div class="v"><?= $listing ? esc((string)($listing['address'] ?? '')) ?: '—' : '—' ?></div>
                    <div class="k" lang-change="usr-field-ref">Reference</div>
                    <div class="v"><?= $listing ? esc((string)($listing['reference_id'] ?? '')) ?: '—' : '—' ?></div>
                    <div class="k" lang-change="usr-field-agent">Agent</div>
                    <div class="v"><?= $listing ? esc((string)($listing['agent_name'] ?? '')) ?: '—' : '—' ?></div>
                    <div class="k" lang-change="usr-field-firstseen">First seen</div>
                    <div class="v"><?= $raw_page ? esc((string)($raw_page['first_seen_at'] ?? '')) ?: '—' : '—' ?></div>
                    <div class="k" lang-change="usr-field-lastseen">Last seen</div>
                    <div class="v"><?= $raw_page ? esc((string)($raw_page['fetched_at'] ?? '')) ?: '—' : '—' ?></div>
                </div>
            </div>

            <!-- Duplicate candidates table -->
            <div class="tool-card user-tool-card">
                <div class="card-topline" lang-change="usr-card4-top">Possible Duplicates</div>
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
                                <th lang-change="usr-th-score">Score</th><th lang-change="usr-th-domain">Domain</th><th lang-change="usr-th-title">Title</th>
                                <th lang-change="usr-th-price">Price</th><th lang-change="usr-th-sqm">SQM</th><th lang-change="usr-th-rooms">Rooms</th>
                                <th lang-change="usr-th-firstseen">First seen</th><th lang-change="usr-th-ai">AI result</th><th lang-change="usr-th-url">URL</th>
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
                    <p class="hint" lang-change="usr-hint-score">Score = sum of matching fields (max 17). Hover AI badge for reason.</p>

                <?php elseif ($submitted && $status_parsed): ?>
                    <p class="muted-sm" lang-change="usr-no-dupes">No candidates scored 10 or higher. The listing may be unique in our database.</p>

                <?php else: ?>
                    <p class="hint" lang-change="usr-hint-submit">Submit a URL above to see duplicate candidates here.</p>
                <?php endif; ?>
            </div>

        </div><!-- /.user-main -->

        <aside class="user-side">
            <div class="user-card user-profile-card">
                <div class="card-topline" lang-change="usr-account-top">Account</div>
                <h2><?= esc((string)($_SESSION['username'] ?? 'unknown')) ?></h2>
                <p class="muted-sm" lang-change="usr-account-sub">Logged in user area for duplicate checks.</p>
                <div class="data data-compact">
                    <div class="k" lang-change="usr-field-userid">User ID</div><div class="v"><?= (int)$_SESSION['user_id'] ?></div>
                    <div class="k" lang-change="usr-field-email">Email</div><div class="v"><?= esc((string)($_SESSION['user_email'] ?? '')) ?: '—' ?></div>
                    <div class="k" lang-change="usr-field-role">Role</div><div class="v"><?= esc((string)($_SESSION['user_role'] ?? 'user')) ?></div>
                </div>
                <div class="actions">
                    <a class="btn" href="logout.php" lang-change="nav-logout">Logout</a>
                </div>
            </div>

            <div class="tool-card user-tool-card">
                <div class="card-topline" lang-change="usr-notes-top">Notes</div>
                <h2 lang-change="usr-notes-h2">MVP scope reminder</h2>
                <ul class="simple-list">
                    <li lang-change="usr-note-li1">"First seen" = first crawled, not when published</li>
                    <li lang-change="usr-note-li2">Not a legal claim of original ownership</li>
                    <li lang-change="usr-note-li3">Best results depend on crawl coverage</li>
                    <li lang-change="usr-note-li4">AI descriptions compared for top 5 SQL matches only</li>
                </ul>
            </div>
        </aside>
    </section>

    <footer class="footer">
        <span lang-change="usr-footer">FirstListing — User area</span>
        <div class="footer-links">
            <a href="index.php" lang-change="back-home">Back to home</a>
            <a href="privacy.php" lang-change="footer-privacy">Privacy Policy</a>
            <a href="legal.php" lang-change="footer-legal">Legal Notice</a>
        </div>
    </footer>
</div>

<?php include __DIR__ . '/partials/chat_widget.php'; ?>
<script src="js/lang.js"></script>
</body>
</html>
