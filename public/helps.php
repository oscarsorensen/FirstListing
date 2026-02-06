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

    <footer class="footer">
        <span>FirstListing — School MVP</span>
        <a href="index.php">Back to home</a>
    </footer>
</div>
</body>
</html>
