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
            <a href="index.php">Home</a>
            <a href="how.php">How it works</a>
            <a href="helps.php">Why it helps</a>
        </nav>
    </header>

    <section class="hero hero-shell page-hero page-hero-small">
        <div class="hero-bg page-hero-image page-hero-how"></div>
        <div class="hero-overlay"></div>
        <div class="hero-text">
            <p class="eyebrow">How the MVP works</p>
            <h1>From raw crawl data to AI-organized duplicate detection.</h1>
            <p class="lead">
                This page shows the technical flow in the project: crawl, store, organize, filter and compare.
                The goal is transparency and a clear proof-of-concept pipeline.
            </p>
            <div class="meta">
                <span>MySQL raw storage</span>
                <span>AI field extraction</span>
                <span>Vector similarity ranking</span>
            </div>
        </div>
        <div class="hero-side">
            <div class="hero-note">
                <div class="hero-note-title">Pipeline focus</div>
                <ul>
                    <li>Traceable raw evidence first</li>
                    <li>AI helps organize, not invent</li>
                    <li>“First seen” is crawler-based</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="steps">
        <div class="section-title">How it works</div>
        <div class="grid">
            <div class="step">
                <div class="num">01</div>
                <h3>Crawl & store</h3>
                <p>We crawl (read) a small set of sites and store raw HTML, text and JSON‑LD in MySQL.</p>
            </div>
            <div class="step">
                <div class="num">02</div>
                <h3>AI organizes</h3>
                <p>AI extracts structured fields (price, sqm, rooms, address).</p>
            </div>
            <div class="step">
                <div class="num">03</div>
                <h3>SQL filter</h3>
                <p>We generate candidate pairs using simple rules like area + price range.</p>
            </div>
            <div class="step">
                <div class="num">04</div>
                <h3>Vector match</h3>
                <p>Vector similarity ranks true duplicates more precisely.</p>
            </div>
            <div class="step">
                <div class="num">05</div>
                <h3>First seen</h3>
                <p>We keep the earliest “first seen” timestamp as the proxy for the original listing.</p>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="final-cta-card">
            <div>
                <p class="eyebrow">Next step</p>
                <h2>See why this workflow is useful in practice</h2>
                <p class="lead-small">
                    The value is not just the AI extraction. It is the combination of evidence, timestamps and matching logic.
                </p>
            </div>
            <div class="cta-row">
                <a href="helps.php" class="cta">Why it helps</a>
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
