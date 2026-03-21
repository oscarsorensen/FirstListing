<?php

// Start the session so we can read and write $_SESSION variables
session_start();
// Load the database connection ($pdo)
require_once __DIR__ . '/../../config/db.php';

// If already logged in as admin, skip the login page
if (isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

$error = '';

// Only run this block when the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    // Make sure neither field is empty
    if ($username === '' || $password === '') {
        $error = 'Please enter a username and password.';
    } else {
        // Look up a user with role='admin' matching the submitted username
        $stmt = $pdo->prepare('
            SELECT id, username, password_hash
            FROM users
            WHERE username = :username AND role = \'admin\'
            LIMIT 1
        ');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify the password against the stored bcrypt hash
        if ($user && password_verify($password, (string)$user['password_hash'])) {
            // Mark this session as an authenticated admin
            $_SESSION['admin_id']       = (int)$user['id'];
            $_SESSION['admin_username'] = (string)$user['username'];
            header('Location: admin.php');
            exit;
        }

        $error = 'Wrong username or password.';
    }
}

// Escape a value for safe HTML output
function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | FirstListing</title>
    <link rel="stylesheet" href="../css/user.css">
</head>
<body>
<div class="page auth-wrap">
    <div class="auth-card">
        <h1>Admin Login</h1>
        <p class="muted">Enter your admin credentials.</p>

        <?php if ($error !== ''): ?>
            <div class="error"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="post" action="admin_login.php">
            <div class="field">
                <label for="username">Username</label>
                <!-- Keep the typed username in the field if the form fails -->
                <input id="username" type="text" name="username" required
                       value="<?= esc((string)($_POST['username'] ?? '')) ?>">
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required>
            </div>

            <div class="actions">
                <button class="btn" type="submit">Login</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
