<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: user.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Invalid login data.';
    } else {
        $stmt = $pdo->prepare('
            SELECT id, username, email, password_hash, role
            FROM users
            WHERE username = :username
            LIMIT 1
        ');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, (string)$user['password_hash'])) {
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
    <style>
        .auth-wrap { max-width: 520px; margin: 40px auto; }
        .auth-card { background: #fff; border: 1px solid #cdd9ea; border-radius: 14px; padding: 20px; box-shadow: 0 14px 28px rgba(33,47,75,.14); }
        .auth-card h1 { margin-top: 0; }
        .field { margin-bottom: 12px; }
        .field label { display: block; margin-bottom: 6px; font-size: 13px; color: #55637a; }
        .field input { width: 100%; padding: 10px; border: 1px solid #cdd9ea; border-radius: 8px; }
        .error { color: #b91c1c; margin-bottom: 10px; }
        .actions { display: flex; gap: 10px; align-items: center; }
        .btn { background: #3f72d9; color: #fff; border: 0; border-radius: 8px; padding: 10px 14px; cursor: pointer; }
        a { color: #3f72d9; }
    </style>
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
