<?php
$title = "Why it helps — FirstListing";
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
        <div class="hero-bg page-hero-image page-hero-helps"></div>
        <div class="hero-overlay"></div>
        <div class="hero-text">
            <p class="eyebrow" lang-change="hlp-eyebrow">Why it helps</p>
            <h1 lang-change="hlp-h1">Useful signal, cleaner data, and better transparency in one workflow.</h1>
            <p class="lead" lang-change="hlp-lead">
                FirstListing helps reduce duplicate noise and make listing comparisons easier to audit.
                It is especially useful as a school MVP because the evidence trail is visible.
            </p>
            <div class="meta">
                <span lang-change="hlp-meta1">Duplicate reduction</span>
                <span lang-change="hlp-meta2">Confidence + source visibility</span>
                <span lang-change="hlp-meta3">Crawler timestamp signal</span>
            </div>
        </div>
        <div class="hero-side">
            <div class="hero-note">
                <div class="hero-note-title" lang-change="hlp-note-title">What this page explains</div>
                <ul>
                    <li lang-change="hlp-note-1">Why clustering duplicates matters</li>
                    <li lang-change="hlp-note-2">Why raw data improves trust</li>
                    <li lang-change="hlp-note-3">Why "first seen" is useful in an MVP</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="trust">
        <div class="trust-card">
            <h2 lang-change="hlp-card1-h2">Reduce duplicate noise</h2>
            <p lang-change="hlp-card1-p">
                The same property appears across multiple agents. We cluster those listings so
                you see one clean result.
            </p>
            <div class="tags">
                <span lang-change="hlp-card1-tag1">Fewer duplicates</span>
                <span lang-change="hlp-card1-tag2">Cleaner search</span>
                <span lang-change="hlp-card1-tag3">Faster decisions</span>
            </div>
        </div>
        <div class="trust-card">
            <h2 lang-change="hlp-card2-h2">Transparency by design</h2>
            <p lang-change="hlp-card2-p">
                Each match is backed by raw data and a confidence score. You can inspect sources
                to understand why items were linked.
            </p>
            <div class="tags">
                <span lang-change="hlp-card2-tag1">Explainable matches</span>
                <span lang-change="hlp-card2-tag2">Audit trail</span>
                <span lang-change="hlp-card2-tag3">Raw data access</span>
            </div>
        </div>
        <div class="trust-card">
            <h2 lang-change="hlp-card3-h2">First seen signal</h2>
            <p lang-change="hlp-card3-p">
                We track when our crawler first saw a listing. It's a practical proxy for earliest
                publication in a school MVP.
            </p>
            <div class="tags">
                <span lang-change="hlp-card3-tag1">First seen timestamp</span>
                <span lang-change="hlp-card3-tag2">Proxy for origin</span>
                <span lang-change="hlp-card3-tag3">MVP‑friendly</span>
            </div>
        </div>
        <div class="trust-card">
            <h2 lang-change="hlp-card4-h2">Scalable path</h2>
            <p lang-change="hlp-card4-p">
                Start with reliable crawled sites, then scale using a hybrid of structured data, API
                extraction, and AI classification.
            </p>
            <div class="tags">
                <span lang-change="hlp-card4-tag1">Hybrid extraction</span>
                <span lang-change="hlp-card4-tag2">AI organizing</span>
                <span lang-change="hlp-card4-tag3">Future‑ready</span>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="final-cta-card" style="justify-content: flex-start; gap: 40px;">
            <div>
                <p class="eyebrow" lang-change="hlp-cta-eyebrow">Try it</p>
                <h2 lang-change="hlp-cta-h2">Create an account and test the user flow</h2>
                <p class="lead-small" lang-change="hlp-cta-lead">
                    The current MVP is strongest as a technical demonstration: crawling, storing evidence, AI extraction and admin review.
                </p>
            </div>
            <div class="cta-row">
                <a href="register.php" class="cta" lang-change="nav-register">Register</a>
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
