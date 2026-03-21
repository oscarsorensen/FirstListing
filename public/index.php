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
            <a href="how.php" lang-change="nav-how">How it works</a>
            <a href="helps.php" lang-change="nav-helps">Why it helps</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="user.php" lang-change="nav-user">User</a>
                <a href="logout.php" lang-change="nav-logout">Logout</a>
            <?php else: ?>
                <a href="login.php" lang-change="nav-login">Login</a>
                <a href="register.php" lang-change="nav-register">Register</a>
            <?php endif; ?>
            <button id="lang-toggle" class="lang-btn">ES</button>
            <a href="admin/admin.php" class="pill">Admin</a>
        </nav>
    </header>

    <section class="hero hero-shell">
        <div class="hero-bg"></div>
        <div class="hero-overlay"></div>

        <div class="hero-text">
            <p class="eyebrow" lang-change="idx-eyebrow">School MVP · Real estate duplicate detection</p>
            <h1 lang-change="idx-h1">Track duplicate listings and compare who appeared first.</h1>
            <p class="lead" lang-change="idx-lead">
                FirstListing stores raw listing evidence (HTML, text and JSON-LD), organizes key fields with AI,
                and helps compare duplicate property listings across agencies.
            </p>

            <div class="cta-row">
                <a href="register.php" class="cta" lang-change="idx-btn-search">Search duplicates</a>
                <a href="how.php" class="ghost" lang-change="idx-btn-how">See how it works</a>
            </div>

            <div class="meta">
                <span lang-change="idx-meta-poc">Proof-of-concept</span>
                <span lang-change="idx-meta-crawler">First seen by our crawler (not a legal ownership claim)</span>
            </div>

            <div class="hero-stats">
                <div class="stat-tile">
                    <div class="stat-label" lang-change="idx-stat-raw">Raw evidence</div>
                    <div class="stat-value">HTML + Text + JSON-LD</div>
                </div>
                <div class="stat-tile">
                    <div class="stat-label" lang-change="idx-stat-extract">Extraction</div>
                    <div class="stat-value" lang-change="idx-stat-ai">AI-assisted fields</div>
                </div>
                <div class="stat-tile">
                    <div class="stat-label" lang-change="idx-stat-match">Matching</div>
                    <div class="stat-value">SQL + Vector similarity</div>
                </div>
            </div>
        </div>

        <div class="hero-side">
            <div class="hero-card">
                <div class="card-header">
                    <span lang-change="idx-card-title">Duplicate match candidate</span>
                    <span class="score">0.91</span>
                </div>
                <div class="card-body">
                    <div class="chip">Guardamar del Segura</div>
                    <div class="title">Apartment · 3 rooms · 90 m²</div>
                    <div class="price">€299,000</div>
                    <div class="mini">
                        <div>
                            <div class="label" lang-change="idx-card-firstseen">First seen</div>
                            <div class="value">2026‑02‑06</div>
                        </div>
                        <div>
                            <div class="label" lang-change="idx-card-source">Source</div>
                            <div class="value">Mediter</div>
                        </div>
                        <div>
                            <div class="label" lang-change="idx-card-matches">Matches</div>
                            <div class="value">2 agents</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hero-note">
                <div class="hero-note-title" lang-change="idx-note-title">What the system stores</div>
                <ul>
                    <li lang-change="idx-note-raw">Raw page content for traceability</li>
                    <li lang-change="idx-note-ai">AI-organized fields in separate table</li>
                    <li lang-change="idx-note-ts">Crawler timestamps for first/last seen</li>
                </ul>
            </div>
        </div>
    </section>

    <section id="how" class="steps">
        <div class="section-title" lang-change="nav-how">How it works</div>
        <div class="grid">
            <div class="step">
                <div class="num">01</div>
                <h3 lang-change="idx-step1-title">Crawl & store</h3>
                <p lang-change="idx-step1-desc">We save raw HTML, text, and structured JSON‑LD in MySQL.</p>
            </div>
            <div class="step">
                <div class="num">02</div>
                <h3 lang-change="idx-step2-title">AI organizes</h3>
                <p lang-change="idx-step2-desc">AI extracts price, rooms, sqm, and address with confidence.</p>
            </div>
            <div class="step">
                <div class="num">03</div>
                <h3 lang-change="idx-step3-title">Find duplicates</h3>
                <p lang-change="idx-step3-desc">SQL filters candidates, VectorDB ranks the best matches.</p>
            </div>
        </div>
    </section>

    <section id="trust" class="trust">
        <div class="trust-card">
            <h2 lang-change="idx-trust1-h2">Designed for proof, not perfection.</h2>
            <p lang-change="idx-trust1-p">
                The MVP intentionally favors recall. We show likely duplicates with
                a confidence score and a "first seen" timestamp.
            </p>
            <div class="tags">
                <span lang-change="idx-trust1-tag1">5 target sites</span>
                <span lang-change="idx-trust1-tag2">AI‑assisted extraction</span>
                <span lang-change="idx-trust1-tag3">Vector similarity</span>
            </div>
        </div>
        <div class="trust-card">
            <h2 lang-change="idx-trust2-h2">What you see</h2>
            <p lang-change="idx-trust2-p">
                A clean list of candidates, with the earliest listing highlighted
                and evidence from raw source data.
            </p>
            <div class="tags">
                <span lang-change="idx-trust2-tag1">First seen</span>
                <span lang-change="idx-trust2-tag2">Match score</span>
                <span lang-change="idx-trust2-tag3">Source transparency</span>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="final-cta-card">
            <div>
                <h2 lang-change="idx-cta-h2">Create an account and test duplicate search flow</h2>
                <p class="lead-small" lang-change="idx-cta-lead">
                    Start with the user page and sample input. The current version focuses on crawler evidence,
                    AI extraction and admin visibility.
                </p>
            </div>
            <div class="cta-row">
                <a href="register.php" class="cta" lang-change="nav-register">Register</a>
                <a href="login.php" class="ghost" lang-change="nav-login">Login</a>
            </div>
        </div>
    </section>

    <footer class="footer">
        <span lang-change="footer-mvp">FirstListing — School MVP</span>
        <div class="footer-links">
            <a href="privacy.php" lang-change="footer-privacy">Privacy Policy</a>
            <a href="legal.php" lang-change="footer-legal">Legal Notice</a>
        </div>
    </footer>
</div>


<?php include __DIR__ . '/partials/chat_widget.php'; ?>
<script src="js/lang.js"></script>
</body>
</html>
