<?php

// Start the session so we can log the user in right after registering
session_start();
// Load the database connection ($pdo)
require_once __DIR__ . '/../config/db.php';

// If already logged in, no need to register again
if (isset($_SESSION['user_id'])) {
    header('Location: user.php');
    exit;
}

$error = '';

// Only run this block when the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read and clean up the submitted values
    $username = trim((string)($_POST['username'] ?? ''));
    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $role     = trim((string)($_POST['role'] ?? 'agent'));

    // Validate all fields before touching the database
    if ($username === '' || strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
        $error = 'Username can only contain letters, numbers, dot, underscore and dash.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email is not valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!in_array($role, ['agent', 'private'], true)) {
        $error = 'Invalid role.';
    } else {
        try {
            // Hash the password — never store plain text passwords
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('
                INSERT INTO users (username, email, password_hash, role)
                VALUES (:username, :email, :password_hash, :role)
            ');
            $stmt->execute([
                ':username'      => $username,
                // Store email as lowercase, or null if not provided
                ':email'         => $email !== '' ? strtolower($email) : null,
                ':password_hash' => $hash,
                ':role'          => $role,
            ]);

            // Log the new user in immediately by setting session variables
            $_SESSION['user_id']    = (int)$pdo->lastInsertId();
            $_SESSION['username']   = $username;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role']  = $role;

            header('Location: user.php');
            exit;
        } catch (PDOException $e) {
            // Error code 23000 means a UNIQUE constraint failed (duplicate username or email)
            if ((int)$e->getCode() === 23000) {
                $error = 'Username or email already exists.';
            } else {
                $error = 'Database error while creating user: ' . $e->getMessage();
            }
        }
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
    <title>Register | FirstListing</title>
    <link rel="stylesheet" href="css/user.css">
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
                <!-- Keep the typed username in the field if the form fails -->
                <input id="username" type="text" name="username" required minlength="3" value="<?= esc((string)($_POST['username'] ?? '')) ?>">
            </div>
            <div class="hint">Use letters/numbers plus . _ -</div>

            <div class="field">
                <label for="email">Email (optional)</label>
                <!-- Keep the typed email in the field if the form fails -->
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
