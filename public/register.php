<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: user.php');
    exit;
}

function table_has_column(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
    );
    $stmt->execute([':t' => $table, ':c' => $column]);
    return (int)$stmt->fetchColumn() > 0;
}

$hasUsernameColumn = table_has_column($pdo, 'users', 'username');
$hasEmailColumn = table_has_column($pdo, 'users', 'email');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $role = trim((string)($_POST['role'] ?? 'agent'));

    if ($username === '' || strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
        $error = 'Username can only contain letters, numbers, dot, underscore and dash.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email is not valid.';
    } elseif (!$hasUsernameColumn) {
        $error = 'Database missing username column in users table.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!in_array($role, ['agent', 'private'], true)) {
        $error = 'Invalid role.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if ($hasEmailColumn) {
                $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)');
                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email !== '' ? strtolower($email) : null,
                    ':password_hash' => $hash,
                    ':role' => $role,
                ]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)');
                $stmt->execute([
                    ':username' => $username,
                    ':password_hash' => $hash,
                    ':role' => $role,
                ]);
            }

            $_SESSION['user_id'] = (int)$pdo->lastInsertId();
            $_SESSION['username'] = $username;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $role;

            header('Location: user.php');
            exit;
        } catch (PDOException $e) {
            if ((int)$e->getCode() === 23000) {
                $error = 'Username or email already exists.';
            } else {
                $error = 'Database error while creating user: ' . $e->getMessage();
            }
        }
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
    <title>Register | FirstListing</title>
    <link rel="stylesheet" href="css/user.css">
    <style>
        .auth-wrap { max-width: 620px; margin: 48px auto; }
        .auth-card {
            background: linear-gradient(180deg, #ffffff 0%, #f6f9ff 100%);
            border: 1px solid #c8d6ee;
            border-radius: 18px;
            padding: 26px;
            box-shadow: 0 18px 34px rgba(38, 55, 84, 0.16);
        }
        .auth-card h1 {
            margin: 0 0 8px;
            font-size: 34px;
            font-family: "DM Serif Display", serif;
            letter-spacing: 0.01em;
        }
        .auth-sub { margin: 0 0 18px; color: #4e607c; }
        .field { margin-bottom: 14px; }
        .field label { display: block; margin-bottom: 6px; font-size: 13px; color: #55637a; font-weight: 600; }
        .field input, .field select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #c7d5ec;
            border-radius: 10px;
            background: #fff;
        }
        .field input:focus, .field select:focus {
            outline: none;
            border-color: #4f82e7;
            box-shadow: 0 0 0 3px rgba(79, 130, 231, 0.18);
        }
        .error {
            color: #9f1239;
            margin-bottom: 12px;
            background: #ffe4ec;
            border: 1px solid #f6b8ca;
            border-radius: 10px;
            padding: 10px 12px;
        }
        .actions { display: flex; gap: 10px; align-items: center; margin-top: 4px; }
        .btn {
            background: linear-gradient(135deg, #3f72d9, #4f82e7);
            color: #fff;
            border: 0;
            border-radius: 10px;
            padding: 10px 16px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn:hover { filter: brightness(1.05); }
        a { color: #2f63c9; font-weight: 600; }
        .hint { font-size: 12px; color: #647898; margin-top: -4px; margin-bottom: 8px; }
    </style>
</head>
<body>
<div class="page auth-wrap">
    <div class="auth-card">
        <h1>Create Account</h1>
        <p class="auth-sub">Simple signup for FirstListing users.</p>

        <?php if ($error !== ''): ?>
            <div class="error"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="post" action="register.php">
            <div class="field">
                <label for="username">Username</label>
                <input id="username" type="text" name="username" required minlength="3" value="<?= esc((string)($_POST['username'] ?? '')) ?>">
            </div>
            <div class="hint">Use letters/numbers plus . _ -</div>

            <div class="field">
                <label for="email">Email (optional)</label>
                <input id="email" type="email" name="email" value="<?= esc((string)($_POST['email'] ?? '')) ?>">
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required minlength="6">
            </div>

            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="agent">agent</option>
                    <option value="private">private</option>
                </select>
            </div>

            <div class="actions">
                <button class="btn" type="submit">Register</button>
                <a href="login.php">Already have an account?</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
