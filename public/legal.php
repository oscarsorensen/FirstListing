<?php
$title = "Legal Notice — FirstListing";
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

    <section class="legal-section">
        <div class="legal-header">
            <p class="eyebrow" lang-change="legal-eyebrow">Legal information</p>
            <h1 lang-change="legal-h1">Legal Notice</h1>
            <p class="lead" lang-change="legal-lead">
                Website owner identification, conditions of use, intellectual property and applicable law.
            </p>
        </div>

        <!-- 1. Owner identification (LSSI Art. 10) -->
        <div class="legal-block">
            <h2 lang-change="legal-s1-title">1. Owner Identification</h2>
            <p lang-change="legal-s1-intro">
                In compliance with Article 10 of Law 34/2002 on Information Society Services
                (LSSI-CE), the following identifying information is provided:
            </p>
            <ul>
                <li lang-change="legal-s1-li1">Website name: FirstListing</li>
                <li lang-change="legal-s1-li2">Owner: Oscar (DAW student — Desarrollo de Aplicaciones Web)</li>
                <li lang-change="legal-s1-li3">Nature: School project — not a commercial service</li>
                <li lang-change="legal-s1-li4">Contact: contact@firstlisting.es</li>
            </ul>
        </div>

        <!-- 2. Purpose of the website -->
        <div class="legal-block">
            <h2 lang-change="legal-s2-title">2. Purpose of the Website</h2>
            <p lang-change="legal-s2-p">
                FirstListing is a proof-of-concept web application developed as a school MVP.
                Its purpose is to demonstrate real estate duplicate listing detection using web
                crawling, AI-assisted field extraction and similarity scoring. It is not a
                commercial service and is not intended for production use.
            </p>
        </div>

        <!-- 3. Conditions of use -->
        <div class="legal-block">
            <h2 lang-change="legal-s3-title">3. Conditions of Use</h2>
            <p lang-change="legal-s3-p">
                By accessing and using this website, you agree to use it for lawful purposes only
                and in a way that does not infringe the rights of others. Automated scraping of
                this website without prior permission is prohibited. The developer reserves the
                right to modify, suspend or terminate access to the website at any time without
                notice.
            </p>
        </div>

        <!-- 4. Intellectual property -->
        <div class="legal-block">
            <h2 lang-change="legal-s4-title">4. Intellectual Property</h2>
            <p lang-change="legal-s4-p">
                All content, source code, design and materials on this website are the intellectual
                property of the developer, unless otherwise stated. Third-party libraries and tools
                are used under their respective open-source licences. Reproduction, distribution or
                public communication of any part of this website without prior written authorisation
                is prohibited.
            </p>
        </div>

        <!-- 5. Limitation of liability -->
        <div class="legal-block">
            <h2 lang-change="legal-s5-title">5. Limitation of Liability</h2>
            <p lang-change="legal-s5-p">
                This website is a student project provided for educational demonstration purposes
                only. The developer makes no warranties about the accuracy, completeness or fitness
                for any particular purpose of the content. The developer shall not be liable for
                any damages arising from the use of, or inability to use, this website.
            </p>
        </div>

        <!-- 6. Links to third-party sites -->
        <div class="legal-block">
            <h2 lang-change="legal-s6-title">6. Links to Third-Party Sites</h2>
            <p lang-change="legal-s6-p">
                This website may contain links to third-party websites. The developer is not
                responsible for the content or privacy practices of those sites. Links are
                provided for convenience only.
            </p>
        </div>

        <!-- 7. Applicable law and jurisdiction -->
        <div class="legal-block">
            <h2 lang-change="legal-s7-title">7. Applicable Law &amp; Jurisdiction</h2>
            <p lang-change="legal-s7-p">
                This website and these terms are governed by Spanish law. For any disputes arising
                out of or relating to this website, the parties submit to the jurisdiction of the
                courts of Spain, unless another jurisdiction is applicable by law.
            </p>
        </div>

        <!-- Last updated -->
        <div class="legal-block legal-block-muted">
            <p lang-change="legal-updated">Last updated: March 2026</p>
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

<script src="js/lang.js"></script>
</body>
</html>
