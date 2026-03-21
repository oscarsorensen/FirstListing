<?php
$title = "How it works — FirstListing";
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
            <a href="index.php" lang-change="nav-home">Home</a>
            <a href="how.php" lang-change="nav-how">How it works</a>
            <a href="helps.php" lang-change="nav-helps">Why it helps</a>
            <button id="lang-toggle" class="lang-btn">ES</button>
        </nav>
    </header>

    <section class="hero hero-shell page-hero page-hero-small">
        <div class="hero-bg page-hero-image page-hero-how"></div>
        <div class="hero-overlay"></div>
        <div class="hero-text">
            <p class="eyebrow" lang-change="how-eyebrow">How the MVP works</p>
            <h1 lang-change="how-h1">From raw crawl data to AI-organized duplicate detection.</h1>
            <p class="lead" lang-change="how-lead">
                This page shows the technical flow in the project: crawl, store, organize, filter and compare.
                The goal is transparency and a clear proof-of-concept pipeline.
            </p>
            <div class="meta">
                <span lang-change="how-meta1">MySQL raw storage</span>
                <span lang-change="how-meta2">AI field extraction</span>
                <span lang-change="how-meta3">AI description comparison</span>
            </div>
        </div>
        <div class="hero-side">
            <div class="hero-note">
                <div class="hero-note-title" lang-change="how-note-title">Pipeline focus</div>
                <ul>
                    <li lang-change="how-note-1">Traceable raw evidence first</li>
                    <li lang-change="how-note-2">AI helps organize, not invent</li>
                    <li lang-change="how-note-3">"First seen" is crawler-based</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="steps">
        <div class="section-title" lang-change="nav-how">How it works</div>
        <div class="grid">
            <div class="step">
                <div class="num">01</div>
                <h3 lang-change="how-step1-title">Crawl & store</h3>
                <p lang-change="how-step1-desc">We crawl (read) a small set of sites and store raw HTML, text and JSON‑LD in MySQL.</p>
            </div>
            <div class="step">
                <div class="num">02</div>
                <h3 lang-change="how-step2-title">AI organizes</h3>
                <p lang-change="how-step2-desc">AI extracts structured fields (price, sqm, rooms, address).</p>
            </div>
            <div class="step">
                <div class="num">03</div>
                <h3 lang-change="how-step3-title">SQL filter</h3>
                <p lang-change="how-step3-desc">We generate candidate pairs using simple rules like area + price range.</p>
            </div>
            <div class="step">
                <div class="num">04</div>
                <h3 lang-change="how-step4-title">AI compare</h3>
                <p lang-change="how-step4-desc">AI compares listing descriptions to confirm true duplicates.</p>
            </div>
            <div class="step">
                <div class="num">05</div>
                <h3 lang-change="how-step5-title">First seen</h3>
                <p lang-change="how-step5-desc">We keep the earliest "first seen" timestamp as the proxy for the original listing.</p>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="final-cta-card" style="justify-content: flex-start; gap: 40px;">
            <div>
                <p class="eyebrow" lang-change="how-cta-eyebrow">Next step</p>
                <h2 lang-change="how-cta-h2">See why this workflow is useful in practice</h2>
                <p class="lead-small" lang-change="how-cta-lead">
                    The value is not just the AI extraction. It is the combination of evidence, timestamps and matching logic.
                </p>
            </div>
            <div class="cta-row">
                <a href="helps.php" class="cta" lang-change="how-cta-btn">Why it helps</a>
            </div>
        </div>
    </section>

    <footer class="footer">
        <span lang-change="footer-mvp">FirstListing — School MVP</span>
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
