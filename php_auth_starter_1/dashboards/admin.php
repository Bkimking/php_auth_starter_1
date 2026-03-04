<?php
// =============================================================
// dashboards/admin.php — Administrator Dashboard
// =============================================================
// requireRole('admin') ensures ONLY admins can reach this page.
// Any other role (even logged-in users) gets redirected away.
// =============================================================

require_once '../config/env.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth-middleware.php';

startSession();

// This single line protects the entire admin section
requireRole('admin');

$pdo = getDB();

// Fetch all users so the admin can see who's registered
$stmt = $pdo->query('
    SELECT id, name, email, role, created_at
    FROM users
    ORDER BY created_at DESC
');
$users = $stmt->fetchAll();

// Count users by role for the stats cards
$totalUsers  = count($users);
$adminCount  = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$regularCount = $totalUsers - $adminCount;

// ── Render ────────────────────────────────────────────────────
$pageTitle = 'Admin Dashboard';
require_once '../includes/header.php';
?>

<main class="dashboard-wrapper">

    <!-- Admin Banner -->
    <div class="dashboard-hero dashboard-hero--admin">
        <div class="dashboard-hero-inner">
            <div class="dashboard-avatar dashboard-avatar--admin">
                <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
            </div>
            <div>
                <h1>Admin Dashboard</h1>
                <p class="text-muted">Logged in as <strong><?= sanitize($_SESSION['name']) ?></strong></p>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="card-grid">
        <div class="stat-card stat-card--highlight">
            <div class="stat-icon">👥</div>
            <div class="stat-label">Total Users</div>
            <div class="stat-value stat-value--large"><?= $totalUsers ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🛡️</div>
            <div class="stat-label">Admins</div>
            <div class="stat-value stat-value--large"><?= $adminCount ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👤</div>
            <div class="stat-label">Regular Users</div>
            <div class="stat-value stat-value--large"><?= $regularCount ?></div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="table-panel">
        <div class="table-panel-header">
            <h2>All Registered Users</h2>
            <span class="badge badge-admin">Admin View</span>
        </div>

        <?php if (empty($users)): ?>
            <p class="text-muted" style="padding: 1rem;">No users registered yet.</p>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr <?= $u['id'] == $_SESSION['user_id'] ? 'class="row-current"' : '' ?>>
                        <td><?= $u['id'] ?></td>
                        <td>
                            <?= sanitize($u['name']) ?>
                            <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                <span class="badge badge-user" style="font-size:0.65rem;">You</span>
                            <?php endif; ?>
                        </td>
                        <td><?= sanitize($u['email']) ?></td>
                        <td>
                            <span class="badge badge-<?= $u['role'] === 'admin' ? 'admin' : 'user' ?>">
                                <?= sanitize($u['role']) ?>
                            </span>
                        </td>
                        <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Learning Note -->
    <div class="learning-note">
        <strong>📚 How role protection works:</strong>
        <code>requireRole('admin')</code> in <code>auth-middleware.php</code> checks
        <code>$_SESSION['role']</code>. Log in as a regular user and try visiting this URL — you'll be redirected away.
    </div>

    <div style="margin-top: 1rem;">
        <a href="/auth/logout.php" class="btn btn-outline">Log Out</a>
    </div>

</main>

<?php require_once '../includes/footer.php'; ?>