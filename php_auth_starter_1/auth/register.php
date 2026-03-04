<?php
// =============================================================
// auth/register.php — User Registration
// =============================================================
// This page handles:
//   1. Showing the registration form (GET request)
//   2. Processing form submission (POST request)
//      a. Validate inputs
//      b. Check email isn't already taken
//      c. Hash the password
//      d. Insert the new user into the database
//      e. Log them in automatically
//      f. Redirect to their dashboard
// =============================================================

// Load our helpers and database connection
require_once '../config/env.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth-middleware.php';

startSession();

// If already logged in, no need to show the registration form
redirectIfLoggedIn();

// Variables to re-populate the form if validation fails
$name  = '';
$email = '';
$role  = 'user';
$errors = [];

// ── Handle Form Submission ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Verify CSRF token (protects against cross-site attacks)
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {

        // 2. Read and sanitize inputs
        //    trim() removes accidental spaces, sanitize() makes it HTML-safe
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $role     = in_array($_POST['role'] ?? '', ['admin', 'user']) ? $_POST['role'] : 'user';

        // 3. Validate each field
        if (empty($name)) {
            $errors[] = 'Full name is required.';
        } elseif (strlen($name) > 100) {
            $errors[] = 'Name must be 100 characters or fewer.';
        }

        if (empty($email)) {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // filter_var with FILTER_VALIDATE_EMAIL checks the email format
            $errors[] = 'Please enter a valid email address.';
        }

        // Validate password strength using our helper function
        $passwordErrors = validatePassword($password);
        $errors = array_merge($errors, $passwordErrors);

        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        // 4. Only hit the database if all validation passed
        if (empty($errors)) {
            $pdo = getDB();

            // Check if this email is already registered
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $stmt->execute([':email' => $email]);

            if ($stmt->fetch()) {
                // Email already exists — but we give a vague message
                // to avoid revealing which emails are in our database
                $errors[] = 'An account with that email already exists.';
            } else {

                // 5. Hash the password before storing it
                //    PASSWORD_BCRYPT is a one-way hash — we can NEVER reverse it
                //    The '12' is the "cost factor" — higher = slower = more secure
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                // 6. Insert the new user
                //    We use prepared statements — the :placeholders prevent SQL injection
                $stmt = $pdo->prepare('
                    INSERT INTO users (name, email, password, role)
                    VALUES (:name, :email, :password, :role)
                ');

                $stmt->execute([
                    ':name'     => $name,
                    ':email'    => $email,
                    ':password' => $hashedPassword,
                    ':role'     => $role,
                ]);

                // Get the ID of the user we just created
                $userId = $pdo->lastInsertId();

                // 7. Log them in by setting session variables
                //    session_regenerate_id() prevents session fixation attacks
                session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                $_SESSION['name']    = $name;
                $_SESSION['email']   = $email;
                $_SESSION['role']    = $role;

                setFlash('success', "Welcome, {$name}! Your account has been created.");

                // 8. Redirect to the appropriate dashboard
                if ($role === 'admin') {
                    redirect('/dashboards/admin.php');
                } else {
                    redirect('/dashboards/user.php');
                }
            }
        }
    }
}

// ── Render the Page ───────────────────────────────────────────
$pageTitle = 'Create Account';
$showNav   = false; // Hide nav on auth pages for a cleaner look
require_once '../includes/header.php';
?>

<main class="auth-wrapper">
    <div class="auth-card">

        <!-- Header -->
        <div class="auth-card-header">
            <div class="auth-logo">⬡</div>
            <h1>Create Account</h1>
            <p>Join AuthProject today</p>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= sanitize($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form method="POST" action="/auth/register.php" novalidate>

            <!-- CSRF hidden field — required on every form -->
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

            <div class="form-group">
                <label for="name">Full Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= sanitize($name) ?>"
                    placeholder="Jane Smith"
                    required
                    autocomplete="name"
                >
            </div>

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
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Min 8 chars, 1 uppercase, 1 number"
                    required
                    autocomplete="new-password"
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Repeat your password"
                    required
                    autocomplete="new-password"
                >
            </div>

            <div class="form-group">
                <label for="role">Account Type</label>
                <select id="role" name="role">
                    <option value="user"  <?= $role === 'user'  ? 'selected' : '' ?>>Regular User</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrator</option>
                </select>
                <small class="form-hint">In a real app, admin roles would be assigned by a super-admin, not self-selected.</small>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Create Account
            </button>
        </form>

        <div class="auth-footer">
            Already have an account?
            <a href="/auth/login.php">Log in</a>
        </div>

    </div>
</main>

<?php require_once '../includes/footer.php'; ?>