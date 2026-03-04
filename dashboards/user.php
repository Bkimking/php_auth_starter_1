<?php
// =============================================================
// dashboards/user.php — Regular User Dashboard
// =============================================================
// This page is only accessible to logged-in users.
// requireLogin() will redirect to login if the session is missing.
// =============================================================

require_once '../config/env.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth-middleware.php';

startSession();

// Protect this page — only logged-in users may proceed
requireLogin();

// Load the full user record from the database
// (The session only holds the basics; the DB has everything up-to-date)
$pdo  = getDB();
$stmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = :id');
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

// If the user record somehow disappeared, log them out
if (!$user) {
    redirect('/auth/logout.php');
}

// ── Render ────────────────────────────────────────────────────
$pageTitle = 'User Dashboard';
require_once '../includes/header.php';
?>

<main class="dashboard-wrapper">

    <!-- Welcome Banner -->
    <div class="dashboard-hero">
        <div class="dashboard-hero-inner">
            <div class="dashboard-avatar">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            <div>
                <h1>Welcome, <?= sanitize($user['name']) ?>!</h1>
                <p class="text-muted">You're logged in as a <strong>regular user</strong>.</p>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="card-grid">

        <div class="stat-card">
            <div class="stat-icon">📧</div>
            <div class="stat-label">Email</div>
            <div class="stat-value"><?= sanitize($user['email']) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🎭</div>
            <div class="stat-label">Role</div>
            <div class="stat-value">
                <span class="badge badge-user"><?= sanitize($user['role']) ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📅</div>
            <div class="stat-label">Member Since</div>
            <div class="stat-value">
                <?= date('M j, Y', strtotime($user['created_at'])) ?>
            </div>
        </div>

    </div>

    <!-- Info Panel -->
    <div class="info-panel">
        <h2>Your Account</h2>
        <p>
            This is your personal dashboard. As a regular user, you can:
        </p>
        <ul class="feature-list">
            <li>✅ View your account details</li>
            <li>✅ Update your profile <em>(feature coming soon)</em></li>
            <li>✅ Change your password <em>(feature coming soon)</em></li>
            <li>🔒 Admin panel access is restricted</li>
        </ul>

        <div class="panel-actions">
            <a href="/auth/logout.php" class="btn btn-outline">Log Out</a>
        </div>
    </div>

    <!-- Learning Note for Students -->
    <div class="learning-note">
        <strong>📚 How this page works:</strong>
        <code>requireLogin()</code> in <code>auth-middleware.php</code> checks your session.
        If you delete your cookies and refresh, you'll be redirected to login.
    </div>

</main>

<?php require_once '../includes/footer.php'; ?>