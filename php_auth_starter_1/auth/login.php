<?php
// =============================================================
// auth/login.php — User Login
// =============================================================
// Flow:
//   GET  → Show the login form
//   POST → Validate credentials → Set session → Redirect
// =============================================================

require_once '../config/env.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth-middleware.php';

startSession();
redirectIfLoggedIn(); // Bounce logged-in users to their dashboard

$email  = '';
$errors = [];

// ── Handle Form Submission ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. CSRF check — always first
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Basic presence checks
        if (empty($email) || empty($password)) {
            $errors[] = 'Please enter your email and password.';
        } else {

            $pdo = getDB();

            // Look up the user by email
            // We select all fields we'll need for the session
            $stmt = $pdo->prepare('
                SELECT id, name, email, password, role
                FROM users
                WHERE email = :email
                LIMIT 1
            ');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            // 2. Verify credentials
            //    password_verify() compares plain text against the stored hash
            //    We intentionally use the same error message whether the email
            //    or password is wrong — this prevents "user enumeration" attacks
            if (!$user || !password_verify($password, $user['password'])) {
                $errors[] = 'Incorrect email or password.';
            } else {

                // 3. Credentials are valid — create the session
                //    session_regenerate_id(true) creates a new session ID.
                //    This prevents "session fixation" attacks where an attacker
                //    plants a known session ID before the user logs in.
                session_regenerate_id(true);

                // Store user info in the session — available on every page
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = $user['role'];

                setFlash('success', "Welcome back, {$user['name']}!");

                // 4. Redirect to intended page (if set) or to their dashboard
                if (!empty($_SESSION['redirect_after_login'])) {
                    $dest = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    redirect($dest);
                }

                if ($user['role'] === 'admin') {
                    redirect('/dashboards/admin.php');
                } else {
                    redirect('/dashboards/user.php');
                }
            }
        }
    }
}

// ── Render ────────────────────────────────────────────────────
$pageTitle = 'Log In';
$showNav   = false;
require_once '../includes/header.php';
?>

<main class="auth-wrapper">
    <div class="auth-card">

        <div class="auth-card-header">
            <div class="auth-logo">⬡</div>
            <h1>Welcome Back</h1>
            <p>Log in to your account</p>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?= sanitize($errors[0]) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="/auth/login.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= sanitize($email) ?>"
                    placeholder="jane@example.com"
                    required
                    autocomplete="email"
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">
                    Password
                    <a href="/auth/forgot-password.php" class="label-link">Forgot password?</a>
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Your password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Log In
            </button>
        </form>

        <div class="auth-footer">
            Don't have an account?
            <a href="/auth/register.php">Sign up</a>
        </div>

        <!-- Demo credentials for students testing the project -->
        <!-- The demo-box below has been removed. -->
        <!-- To create users, register via /auth/register.php -->
        <!-- <div class="demo-box">
            <strong>Demo accounts:</strong><br>
            Admin: admin@example.com · Password123!<br>
            User: user@example.com · Password123!
        </div> -->

    </div>
</main>

<?php require_once '../includes/footer.php'; ?>