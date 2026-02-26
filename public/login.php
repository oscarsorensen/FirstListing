<?php

// Start the session so we can read and write $_SESSION variables
session_start();
// Load the database connection ($pdo)
require_once __DIR__ . '/../config/db.php';

// If the user is already logged in, send them to the user page
if (isset($_SESSION['user_id'])) {
    header('Location: user.php');
    exit;
}

$error = '';

// Only run this block when the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read and clean up the submitted values
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    // Make sure neither field is empty
    if ($username === '' || $password === '') {
        $error = 'Invalid login data.';
    } else {
        // Look up the user in the database by username
        $stmt = $pdo->prepare('
            SELECT id, username, email, password_hash, role
            FROM users
            WHERE username = :username
            LIMIT 1
        ');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check that the user exists and the password matches the stored hash
        if ($user && password_verify($password, (string)$user['password_hash'])) {
            // Store user info in the session so other pages know who is logged in
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = (string)$user['username'];
            $_SESSION['user_email'] = (string)($user['email'] ?? '');
            $_SESSION['user_role'] = (string)$user['role'];
            header('Location: user.php');
            exit;
        }

        $error = 'Wrong username or password.';
    }
}

// Converts special characters to HTML entities to prevent XSS
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
    <title>Login | FirstListing</title>
    <link rel="stylesheet" href="css/user.css">
</head>
<body>
<div class="page auth-wrap">
    <div class="auth-card">
        <h1>Login</h1>
        <p class="muted">Enter your user credentials.</p>

        <?php if ($error !== ''): ?>
            <div class="error"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <div class="field">
                <label for="username">Username</label>
                <!-- Keep the typed username in the field if the form fails -->
                <input id="username" type="text" name="username" required value="<?= esc((string)($_POST['username'] ?? '')) ?>">
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required>
            </div>

            <div class="actions">
                <button class="btn" type="submit">Login</button>
                <a href="register.php">Create account</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
