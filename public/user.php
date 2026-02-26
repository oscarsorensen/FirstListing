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
            <p class="eyebrow">User area · MVP frontend</p>
            <h1>Check one listing URL and review duplicate candidates.</h1>
            <p class="lead">
                Paste a listing URL to start a duplicate check. This page is the user-facing flow for the project:
                input, status, extracted info and possible matches.
            </p>
            <div class="meta">
                <span>User: <?= esc((string)($_SESSION['username'] ?? 'unknown')) ?></span>
                <span>Role: <?= esc((string)($_SESSION['user_role'] ?? 'user')) ?></span>
                <span>Frontend only (search logic comes next)</span>
            </div>
        </div>
        <div class="hero-side">
            <div class="hero-note">
                <div class="hero-note-title">Current scope</div>
                <ul>
                    <li>URL input and results layout</li>
                    <li>Status and extracted fields panels</li>
                    <li>Duplicate matches table placeholder</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="user-layout">
        <div class="user-main">
            <div class="tool-card user-tool-card">
                <div class="card-topline">Duplicate Check</div>
                <h2>Paste listing URL</h2>
                <p class="muted-sm">Frontend ready. You will connect search + parsing logic next.</p>

                <form method="post" action="user.php" class="user-search-form">
                    <div class="field">
                        <label for="listing_url">Listing URL</label>
                        <input id="listing_url" name="listing_url" type="url" placeholder="https://example.com/listing/123">
                    </div>
                    <div class="actions">
                        <button class="btn" type="submit">Check duplicates</button>
                        <a class="ghost ghost-light" href="index.php">Back to homepage</a>
                    </div>
                </form>
            </div>

            <div class="tool-card user-tool-card">
                <div class="card-topline">Search Status</div>
                <h2>Result status (placeholder)</h2>
                <div class="state-list">
                    <div class="state-item">
                        <div class="state-label">URL in raw_pages</div>
                        <div class="state-pill pending">Pending</div>
                    </div>
                    <div class="state-item">
                        <div class="state-label">AI parsed row found</div>
                        <div class="state-pill pending">Pending</div>
                    </div>
                    <div class="state-item">
                        <div class="state-label">Duplicate matches found</div>
                        <div class="state-pill pending">Pending</div>
                    </div>
                </div>
                <p class="hint">You will replace these placeholders with real states after wiring the URL search.</p>
            </div>

            <div class="tool-card user-tool-card">
                <div class="card-topline">Extracted Listing Data</div>
                <h2>Structured fields (placeholder)</h2>
                <div class="data data-compact">
                    <div class="k">URL</div><div class="v">—</div>
                    <div class="k">Company</div><div class="v">—</div>
                    <div class="k">Title</div><div class="v">—</div>
                    <div class="k">Price</div><div class="v">—</div>
                    <div class="k">SQM</div><div class="v">—</div>
                    <div class="k">Rooms</div><div class="v">—</div>
                    <div class="k">Baths</div><div class="v">—</div>
                    <div class="k">Address</div><div class="v">—</div>
                    <div class="k">Reference</div><div class="v">—</div>
                    <div class="k">First seen</div><div class="v">—</div>
                    <div class="k">Last seen</div><div class="v">—</div>
                </div>
            </div>

            <div class="tool-card user-tool-card">
                <div class="card-topline">Possible Duplicates</div>
                <h2>Matches table (placeholder)</h2>
                <div class="table-wrap" style="max-height: 420px; overflow:auto;">
                    <table>
                        <tr>
                            <th>Match score</th>
                            <th>Company</th>
                            <th>Title</th>
                            <th>Price</th>
                            <th>SQM</th>
                            <th>Rooms</th>
                            <th>First seen</th>
                            <th>URL</th>
                        </tr>
                        <tr>
                            <td>—</td><td>—</td><td>No results yet</td><td>—</td><td>—</td><td>—</td><td>—</td><td>—</td>
                        </tr>
                    </table>
                </div>
                <p class="hint">Later: fill this with rows from your matching logic / vector results.</p>
            </div>
        </div>

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
                <div class="card-topline">Recent Searches</div>
                <h2>History (placeholder)</h2>
                <ul class="simple-list">
                    <li>No searches yet</li>
                    <li>Later: show URL + date + status</li>
                </ul>
            </div>

            <div class="tool-card user-tool-card">
                <div class="card-topline">Notes</div>
                <h2>MVP scope reminder</h2>
                <ul class="simple-list">
                    <li>“First seen” means first seen by your crawler</li>
                    <li>Not a legal claim of original ownership</li>
                    <li>Best results depend on raw crawl coverage</li>
                </ul>
            </div>
        </aside>
    </section>

    <footer class="footer">
        <span>FirstListing — User area (MVP frontend)</span>
        <a href="index.php">Back to home</a>
    </footer>
</div>
</body>
</html>
