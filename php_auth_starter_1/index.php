<?php
// =============================================================
// index.php — Landing / Home Page
// =============================================================
// This is the entry point of the application.
// Logged-in users are redirected to their dashboard.
// Guests see the marketing/welcome page.
// =============================================================

require_once 'config/env.php';
require_once 'includes/functions.php';
require_once 'includes/auth-middleware.php';

startSession();

// If already logged in, go straight to their dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/dashboards/admin.php');
    } else {
        redirect('/dashboards/user.php');
    }
}

$pageTitle = 'Home';
require_once 'includes/header.php';
?>

<!-- ── Hero Section ───────────────────────────────────────── -->
<main>
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-badge">PHP 8 · MySQL · PHPMailer</div>
            <h1 class="hero-title">
                A Clean Auth Starter<br>for PHP Students
            </h1>
            <p class="hero-subtitle">
                Learn real-world authentication patterns — registration, login,
                role-based dashboards, and secure password reset — all in plain PHP.
            </p>
            <div class="hero-cta">
                <a href="/auth/register.php" class="btn btn-primary btn-lg">Get Started</a>
                <a href="/auth/login.php" class="btn btn-outline btn-lg">Log In</a>
            </div>
        </div>
    </section>

    <!-- ── Feature Cards ──────────────────────────────────── -->
    <section class="features">
        <div class="features-inner">
            <h2 class="section-title">What You'll Learn</h2>
            <div class="feature-grid">

                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h3>Secure Authentication</h3>
                    <p>Password hashing with bcrypt, session management, CSRF protection, and session regeneration on login.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🎭</div>
                    <h3>Role-Based Access</h3>
                    <p>Admins and regular users see different dashboards. Middleware protects routes based on session role.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📧</div>
                    <h3>Email with PHPMailer</h3>
                    <p>Password reset emails sent via SMTP with expiring tokens. Safer and more deliverable than PHP's mail().</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🛡️</div>
                    <h3>PDO & Prepared Statements</h3>
                    <p>Every database query uses PDO prepared statements to prevent SQL injection attacks.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">⚙️</div>
                    <h3>Environment Variables</h3>
                    <p>Secrets live in .env, not in code. Learn why this matters before you ever touch a production server.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📁</div>
                    <h3>Clean Architecture</h3>
                    <p>Code separated into config, auth, dashboards, and includes. Easy to read, easy to extend.</p>
                </div>

            </div>
        </div>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>