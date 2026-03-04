<?php
// =============================================================
// auth/reset-password.php — Reset Password via Token
// =============================================================
// The user arrives here via a link in their email:
//   /auth/reset-password.php?token=abc123...
//
// Flow:
//   GET  → Validate token → Show new password form
//   POST → Validate inputs → Update password → Clear token → Redirect
// =============================================================

require_once '../config/env.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth-middleware.php';

startSession();
redirectIfLoggedIn();

$token  = trim($_GET['token'] ?? '');
$errors = [];
$user   = null;

// ── Validate the Token ────────────────────────────────────────
// We check the token BEFORE showing the form so we can show a
// helpful error if it's invalid or expired.
if (empty($token)) {
    setFlash('error', 'Missing reset token. Please request a new link.');
    redirect('/auth/forgot-password.php');
}

$pdo = getDB();

// Find a user with this token WHERE the token hasn't expired yet
// NOW() is MySQL's current datetime — we compare against reset_token_expiry
$stmt = $pdo->prepare('
    SELECT id, name, email
    FROM users
    WHERE reset_token = :token
      AND reset_token_expiry > NOW()
    LIMIT 1
');
$stmt->execute([':token' => $token]);
$user = $stmt->fetch();

if (!$user) {
    // Token not found or expired
    setFlash('error', 'This reset link is invalid or has expired. Please request a new one.');
    redirect('/auth/forgot-password.php');
}

// ── Handle New Password Submission ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {

        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        // Validate password strength
        $errors = validatePassword($password);

        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {

            // Hash the new password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Update the password AND clear the reset token
            // We clear both fields so the link can never be re-used
            $stmt = $pdo->prepare('
                UPDATE users
                SET
                    password           = :password,
                    reset_token        = NULL,
                    reset_token_expiry = NULL
                WHERE id = :id
            ');
            $stmt->execute([
                ':password' => $hashedPassword,
                ':id'       => $user['id'],
            ]);

            setFlash('success', 'Password updated successfully! Please log in with your new password.');
            redirect('/auth/login.php');
        }
    }
}

// ── Render ────────────────────────────────────────────────────
$pageTitle = 'Reset Password';
$showNav   = false;
require_once '../includes/header.php';
?>

<main class="auth-wrapper">
    <div class="auth-card">

        <div class="auth-card-header">
            <div class="auth-logo">🔒</div>
            <h1>Reset Password</h1>
            <p>Choose a new password for <strong><?= sanitize($user['email']) ?></strong></p>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= sanitize($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" action="/auth/reset-password.php?token=<?= urlencode($token) ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

            <div class="form-group">
                <label for="password">New Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Min 8 chars, 1 uppercase, 1 number"
                    required
                    autofocus
                    autocomplete="new-password"
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Repeat your new password"
                    required
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Update Password
            </button>
        </form>

    </div>
</main>

<?php require_once '../includes/footer.php'; ?>