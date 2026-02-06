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

    <section class="steps">
        <div class="section-title">How it works</div>
        <div class="grid">
            <div class="step">
                <div class="num">01</div>
                <h3>Crawl & store</h3>
                <p>We crawl a small set of sites and store raw HTML, text and JSON‑LD in MySQL.</p>
            </div>
            <div class="step">
                <div class="num">02</div>
                <h3>AI organizes</h3>
                <p>AI extracts structured fields (price, sqm, rooms, address) with confidence scores.</p>
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

    <footer class="footer">
        <span>FirstListing — School MVP</span>
        <a href="index.php">Back to home</a>
    </footer>
</div>
</body>
</html>
