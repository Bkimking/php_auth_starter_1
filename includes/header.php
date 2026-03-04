<?php
// =============================================================
// includes/header.php — Shared HTML Header & Navigation
// =============================================================
// This file outputs the <head> section and top navigation bar.
// Include it at the top of every page:
//   require_once '../includes/header.php';
//
// Pages can set these variables BEFORE including this file
// to customize the title and whether the nav shows:
//   $pageTitle = 'My Page';
//   $showNav   = true;
// =============================================================

require_once __DIR__ . '/functions.php';
startSession();

// Default values if the page didn't set them
$pageTitle = $pageTitle ?? 'Auth Project';
$showNav   = $showNav   ?? true;

// Grab any flash message now so it's available to display below
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> — Auth Project</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <!-- Google Font: Sora (display) + Inter (body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<?php if ($showNav): ?>
<!-- ── Navigation Bar ──────────────────────────────────────── -->
<nav class="navbar">
    <div class="nav-inner">
        <!-- Logo / Brand -->
        <a href="/index.php" class="nav-brand">
            <span class="nav-logo">⬡</span> AuthProject
        </a>

        <!-- Navigation links (only shown when logged in) -->
        <?php if (isLoggedIn()): ?>
        <div class="nav-links">
            <!-- Show correct dashboard link based on role -->
            <?php if (isAdmin()): ?>
                <a href="/dashboards/admin.php" class="nav-link">Admin Dashboard</a>
            <?php else: ?>
                <a href="/dashboards/user.php" class="nav-link">Dashboard</a>
            <?php endif; ?>

            <!-- Display the logged-in user's name -->
            <span class="nav-user">
                👤 <?= sanitize($_SESSION['name'] ?? 'User') ?>
            </span>

            <!-- Logout link -->
            <a href="/auth/logout.php" class="nav-link nav-logout">Log Out</a>
        </div>
        <?php else: ?>
        <!-- Not logged in — show login/register links -->
        <div class="nav-links">
            <a href="/auth/login.php" class="nav-link">Log In</a>
            <a href="/auth/register.php" class="btn btn-sm">Sign Up</a>
        </div>
        <?php endif; ?>
    </div>
</nav>
<?php endif; ?>

<!-- ── Flash Message ───────────────────────────────────────── -->
<?php if ($flash): ?>
<div class="flash flash-<?= sanitize($flash['type']) ?>" role="alert">
    <?= sanitize($flash['message']) ?>
    <!-- Close button — pure JS, no library needed -->
    <button class="flash-close" onclick="this.parentElement.remove()" aria-label="Close">✕</button>
</div>
<?php endif; ?>

<!-- ── Page Content Starts Here ───────────────────────────── -->