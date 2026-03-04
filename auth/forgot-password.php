<?php
// =============================================================
// auth/forgot-password.php — Request Password Reset
// =============================================================
// Flow:
//   1. User enters their email address
//   2. We generate a secure random token and store it
//      in the database with a 1-hour expiry time
//   3. We email them a link containing that token
//   4. Whether or not the email exists, we show the same
//      success message (prevents user enumeration)
// =============================================================

require_once '../config/env.php';
require_once '../config/database.php';

// Load PHPMailer via Composer's autoloader
// Make sure you've run: composer require phpmailer/phpmailer
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

require_once '../config/mail.php';
require_once '../includes/functions.php';
require_once '../includes/auth-middleware.php';

startSession();
redirectIfLoggedIn();

$submitted = false; // Controls whether to show the form or the success message
$errors    = [];

// ── Handle Form Submission ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {

        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {

            $pdo = getDB();

            // Look up the user (we won't reveal if they exist or not)
            $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = :email');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            // Only send the email if the user actually exists
            if ($user) {

                // Generate a cryptographically secure random token
                // bin2hex(random_bytes(32)) = 64 character hex string
                $token = bin2hex(random_bytes(32));

                // // Token expires 1 hour from now
                // $expiry = date('Y-m-d H:i:s', time() + 3600);

                // Store the token and expiry in the database
                $stmt = $pdo->prepare('
    UPDATE users
    SET reset_token        = :token,
        reset_token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR)
    WHERE id = :id
');
$stmt->execute([
    ':token' => $token,
    ':id'    => $user['id'],
]);

                // Build the reset URL
                $appUrl    = rtrim(getenv('APP_URL'), '/');
                $resetLink = $appUrl . '/auth/reset-password.php?token=' . $token;

                // Send the email
                // If it fails, we still show success (don't reveal user existence)
                sendPasswordResetEmail($email, $user['name'], $resetLink);
            }

            // Always show the success message regardless of whether the email
            // was found — this prevents attackers from discovering valid emails
            $submitted = true;
        }
    }
}

// ── Render ────────────────────────────────────────────────────
$pageTitle = 'Forgot Password';
$showNav   = false;
require_once '../includes/header.php';
?>

<main class="auth-wrapper">
    <div class="auth-card">

        <div class="auth-card-header">
            <div class="auth-logo">🔑</div>
            <h1>Forgot Password?</h1>
            <p>We'll send you a reset link</p>
        </div>

        <?php if ($submitted): ?>
            <!-- Show this AFTER submission regardless of email validity -->
            <div class="alert alert-success">
                <strong>Check your inbox!</strong><br>
                If that email is registered, you'll receive a password reset link shortly.
                The link expires in 1 hour.
            </div>
            <div class="auth-footer" style="margin-top: 1.5rem;">
                <a href="/auth/login.php">← Back to Login</a>
            </div>

        <?php else: ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?= sanitize($errors[0]) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="/auth/forgot-password.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="jane@example.com"
                        required
                        autofocus
                        autocomplete="email"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    Send Reset Link
                </button>
            </form>

            <div class="auth-footer">
                <a href="/auth/login.php">← Back to Login</a>
            </div>

        <?php endif; ?>

    </div>
</main>

<?php require_once '../includes/footer.php'; ?>