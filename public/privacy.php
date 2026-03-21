<?php
$title = "Privacy Policy — FirstListing";
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
            <p class="eyebrow" lang-change="priv-eyebrow">Privacy</p>
            <h1 lang-change="priv-h1">Privacy Policy</h1>
            <p class="lead" lang-change="priv-lead">
                How FirstListing collects, uses and protects your personal data.
            </p>
        </div>

        <!-- 1. Data controller -->
        <div class="legal-block">
            <h2 lang-change="priv-s1-title">1. Data Controller</h2>
            <p lang-change="priv-s1-p">
                FirstListing is developed by Oscar, a first-year DAW (Desarrollo de Aplicaciones Web)
                student. This is a school project and not a commercial entity.
                Contact: <a href="mailto:contact@firstlisting.es">contact@firstlisting.es</a>
            </p>
        </div>

        <!-- 2. Data we collect -->
        <div class="legal-block">
            <h2 lang-change="priv-s2-title">2. Data We Collect</h2>
            <p lang-change="priv-s2-intro">When you register or use FirstListing, we may collect the following personal data:</p>
            <ul>
                <li lang-change="priv-s2-li1">Username (required to create an account)</li>
                <li lang-change="priv-s2-li2">Email address (optional, used only for account recovery)</li>
                <li lang-change="priv-s2-li3">Password (stored as a bcrypt hash — never in plain text)</li>
                <li lang-change="priv-s2-li4">Search history (URLs you submit for duplicate checking)</li>
                <li lang-change="priv-s2-li5">Usage data (number of searches performed per month)</li>
            </ul>
        </div>

        <!-- 3. How we collect your data -->
        <div class="legal-block">
            <h2 lang-change="priv-s3-title">3. How We Collect Your Data</h2>
            <p lang-change="priv-s3-p">
                We collect your data directly through the forms you complete on our website
                (registration, login). We do not use cookies for tracking or profiling.
                We do not collect data from third-party sources.
            </p>
        </div>

        <!-- 4. Purpose and legal basis -->
        <div class="legal-block">
            <h2 lang-change="priv-s4-title">4. Purpose &amp; Legal Basis</h2>
            <p lang-change="priv-s4-intro">We process your personal data for the following purposes:</p>
            <ul>
                <li lang-change="priv-s4-li1">Account management — legal basis: GDPR Art. 6.1.b (performance of a contract)</li>
                <li lang-change="priv-s4-li2">Service delivery (duplicate checking) — legal basis: GDPR Art. 6.1.b (performance of a contract)</li>
                <li lang-change="priv-s4-li3">Usage limits (monthly search quota) — legal basis: GDPR Art. 6.1.b (performance of a contract)</li>
            </ul>
        </div>

        <!-- 5. Data retention -->
        <div class="legal-block">
            <h2 lang-change="priv-s5-title">5. Data Retention</h2>
            <p lang-change="priv-s5-p">
                Your data is retained for as long as your account remains active. If you request
                account deletion, all personal data will be removed within 30 days. Search history
                (submitted URLs) is retained to support the duplicate detection pipeline.
            </p>
        </div>

        <!-- 6. Data sharing -->
        <div class="legal-block">
            <h2 lang-change="priv-s6-title">6. Data Sharing with Third Parties</h2>
            <p lang-change="priv-s6-p1">
                We do not sell, rent or share your personal data with third parties.
            </p>
            <p lang-change="priv-s6-p2">
                The only third-party service we use is the OpenAI API, for AI-assisted field
                extraction and description comparison. URLs you submit for duplicate checking are
                sent to OpenAI as part of this processing. OpenAI's privacy policy applies to
                that data (openai.com/policies/privacy-policy).
            </p>
        </div>

        <!-- 7. Your rights -->
        <div class="legal-block">
            <h2 lang-change="priv-s7-title">7. Your Rights</h2>
            <p lang-change="priv-s7-intro">Under GDPR and LOPD-GDD you have the following rights:</p>
            <ul>
                <li lang-change="priv-s7-li1">Right of access (Art. 15 GDPR)</li>
                <li lang-change="priv-s7-li2">Right to rectification (Art. 16 GDPR)</li>
                <li lang-change="priv-s7-li3">Right to erasure / right to be forgotten (Art. 17 GDPR)</li>
                <li lang-change="priv-s7-li4">Right to restriction of processing (Art. 18 GDPR)</li>
                <li lang-change="priv-s7-li5">Right to data portability (Art. 20 GDPR)</li>
                <li lang-change="priv-s7-li6">Right to object (Art. 21 GDPR)</li>
            </ul>
            <p lang-change="priv-s7-contact">To exercise any of these rights, contact us at:</p>
            <p><a href="mailto:contact@firstlisting.es">contact@firstlisting.es</a></p>
        </div>

        <!-- 8. Right to complain -->
        <div class="legal-block">
            <h2 lang-change="priv-s8-title">8. Right to Lodge a Complaint</h2>
            <p lang-change="priv-s8-p">
                If you believe your data protection rights have been violated, you have the right
                to lodge a complaint with the Spanish Data Protection Authority (AEPD)
                at www.aepd.es.
            </p>
        </div>

        <!-- 9. Security -->
        <div class="legal-block">
            <h2 lang-change="priv-s9-title">9. Security</h2>
            <p lang-change="priv-s9-p">
                We implement appropriate technical and organisational measures to protect your
                personal data against accidental loss, unauthorised access, disclosure, alteration
                or destruction. Passwords are stored using bcrypt hashing.
            </p>
        </div>

        <!-- 10. Changes to this policy -->
        <div class="legal-block">
            <h2 lang-change="priv-s10-title">10. Changes to This Policy</h2>
            <p lang-change="priv-s10-p">
                We may update this policy from time to time. The date at the bottom of this page
                indicates when it was last revised. We recommend checking this page periodically.
            </p>
        </div>

        <!-- Last updated -->
        <div class="legal-block legal-block-muted">
            <p lang-change="priv-updated">Last updated: March 2026</p>
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
