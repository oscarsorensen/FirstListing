<?php
session_start();
$title = "FirstListing — Proof of Concept";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
            <a href="how.php">How it works</a>
            <a href="helps.php">Why it helps</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="user.php">User</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
            <a href="admin/admin.php" class="pill">Admin</a>
        </nav>
    </header>

    <section class="hero hero-shell">
        <div class="hero-bg"></div>
        <div class="hero-overlay"></div>

        <div class="hero-text">
            <p class="eyebrow">School MVP · Real estate duplicate detection</p>
            <h1>Track duplicate listings and compare who appeared first.</h1>
            <p class="lead">
                FirstListing stores raw listing evidence (HTML, text and JSON-LD), organizes key fields with AI,
                and helps compare duplicate property listings across agencies.
            </p>

            <div class="cta-row">
                <a href="register.php" class="cta">Search duplicates</a>
                <a href="how.php" class="ghost">See how it works</a>
            </div>

            <div class="meta">
                <span>Proof-of-concept</span>
                <span>First seen by our crawler (not a legal ownership claim)</span>
            </div>

            <div class="hero-stats">
                <div class="stat-tile">
                    <div class="stat-label">Raw evidence</div>
                    <div class="stat-value">HTML + Text + JSON-LD</div>
                </div>
                <div class="stat-tile">
                    <div class="stat-label">Extraction</div>
                    <div class="stat-value">AI-assisted fields</div>
                </div>
                <div class="stat-tile">
                    <div class="stat-label">Matching</div>
                    <div class="stat-value">SQL + Vector similarity</div>
                </div>
            </div>
        </div>

        <div class="hero-side">
            <div class="hero-card">
                <div class="card-header">
                    <span>Duplicate match candidate</span>
                    <span class="score">0.91</span>
                </div>
                <div class="card-body">
                    <div class="chip">Guardamar del Segura</div>
                    <div class="title">Apartment · 3 rooms · 90 m²</div>
                    <div class="price">€299,000</div>
                    <div class="mini">
                        <div>
                            <div class="label">First seen</div>
                            <div class="value">2026‑02‑06</div>
                        </div>
                        <div>
                            <div class="label">Source</div>
                            <div class="value">Mediter</div>
                        </div>
                        <div>
                            <div class="label">Matches</div>
                            <div class="value">2 agents</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hero-note">
                <div class="hero-note-title">What the system stores</div>
                <ul>
                    <li>Raw page content for traceability</li>
                    <li>AI-organized fields in separate table</li>
                    <li>Crawler timestamps for first/last seen</li>
                </ul>
            </div>
        </div>
    </section>

    <section id="how" class="steps">
        <div class="section-title">How it works</div>
        <div class="grid">
            <div class="step">
                <div class="num">01</div>
                <h3>Crawl & store</h3>
                <p>We save raw HTML, text, and structured JSON‑LD in MySQL.</p>
            </div>
            <div class="step">
                <div class="num">02</div>
                <h3>AI organizes</h3>
                <p>AI extracts price, rooms, sqm, and address with confidence.</p>
            </div>
            <div class="step">
                <div class="num">03</div>
                <h3>Find duplicates</h3>
                <p>SQL filters candidates, VectorDB ranks the best matches.</p>
            </div>
        </div>
    </section>

    <section id="trust" class="trust">
        <div class="trust-card">
            <h2>Designed for proof, not perfection.</h2>
            <p>
                The MVP intentionally favors recall. We show likely duplicates with
                a confidence score and a “first seen” timestamp.
            </p>
            <div class="tags">
                <span>5 target sites</span>
                <span>AI‑assisted extraction</span>
                <span>Vector similarity</span>
            </div>
        </div>
        <div class="trust-card">
            <h2>What you see</h2>
            <p>
                A clean list of candidates, with the earliest listing highlighted
                and evidence from raw source data.
            </p>
            <div class="tags">
                <span>First seen</span>
                <span>Match score</span>
                <span>Source transparency</span>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="final-cta-card">
            <div>
               
                <h2>Create an account and test duplicate search flow</h2>
                <p class="lead-small">
                    Start with the user page and sample input. The current version focuses on crawler evidence,
                    AI extraction and admin visibility.
                </p>
            </div>
            <div class="cta-row">
                <a href="register.php" class="cta">Register</a>
                <a href="login.php" class="ghost">Login</a>
            </div>
        </div>
    </section>

    <footer class="footer">
        <span>FirstListing — School MVP</span>
    </footer>
</div>

</body>
</html>
