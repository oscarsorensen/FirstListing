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
            <a href="index.php">Home</a>
            <a href="how.php">How it works</a>
            <a href="helps.php">Why it helps</a>
        </nav>
    </header>

    <section class="hero hero-shell page-hero page-hero-small">
        <div class="hero-bg page-hero-image page-hero-helps"></div>
        <div class="hero-overlay"></div>
        <div class="hero-text">
            <p class="eyebrow">Why it helps</p>
            <h1>Useful signal, cleaner data, and better transparency in one workflow.</h1>
            <p class="lead">
                FirstListing helps reduce duplicate noise and make listing comparisons easier to audit.
                It is especially useful as a school MVP because the evidence trail is visible.
            </p>
            <div class="meta">
                <span>Duplicate reduction</span>
                <span>Confidence + source visibility</span>
                <span>Crawler timestamp signal</span>
            </div>
        </div>
        <div class="hero-side">
            <div class="hero-note">
                <div class="hero-note-title">What this page explains</div>
                <ul>
                    <li>Why clustering duplicates matters</li>
                    <li>Why raw data improves trust</li>
                    <li>Why “first seen” is useful in an MVP</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="trust">
        <div class="trust-card">
            <h2>Reduce duplicate noise</h2>
            <p>
                The same property appears across multiple agents. We cluster those listings so
                you see one clean result.
            </p>
            <div class="tags">
                <span>Fewer duplicates</span>
                <span>Cleaner search</span>
                <span>Faster decisions</span>
            </div>
        </div>
        <div class="trust-card">
            <h2>Transparency by design</h2>
            <p>
                Each match is backed by raw data and a confidence score. You can inspect sources
                to understand why items were linked.
            </p>
            <div class="tags">
                <span>Explainable matches</span>
                <span>Audit trail</span>
                <span>Raw data access</span>
            </div>
        </div>
        <div class="trust-card">
            <h2>First seen signal</h2>
            <p>
                We track when our crawler first saw a listing. It’s a practical proxy for earliest
                publication in a school MVP.
            </p>
            <div class="tags">
                <span>First seen timestamp</span>
                <span>Proxy for origin</span>
                <span>MVP‑friendly</span>
            </div>
        </div>
        <div class="trust-card">
            <h2>Scalable path</h2>
            <p>
                Start with 5 reliable sites, then scale using a hybrid of structured data, API
                extraction, and AI classification.
            </p>
            <div class="tags">
                <span>Hybrid extraction</span>
                <span>AI organizing</span>
                <span>Future‑ready</span>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="final-cta-card">
            <div>
                <p class="eyebrow">Try it</p>
                <h2>Create an account and test the user flow</h2>
                <p class="lead-small">
                    The current MVP is strongest as a technical demonstration: crawling, storing evidence, AI extraction and admin review.
                </p>
            </div>
            <div class="cta-row">
                <a href="register.php" class="cta">Register</a>
                <a href="index.php" class="ghost">Back to home</a>
            </div>
        </div>
    </section>

    <footer class="footer">
        <span>FirstListing — School MVP</span>
        <a href="index.php">Back to home</a>
    </footer>
</div>
</body>
</html>
