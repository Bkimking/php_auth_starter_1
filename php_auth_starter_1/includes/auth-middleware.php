<?php
// =============================================================
// includes/auth-middleware.php — Route Protection Middleware
// =============================================================
// "Middleware" is code that runs BEFORE the main page logic.
// These functions protect pages from being accessed by visitors
// who aren't logged in (or don't have the right role).
//
// Usage — at the top of any protected page:
//   require_once '../includes/auth-middleware.php';
//   requireLogin();           // Any logged-in user
//   requireRole('admin');     // Admins only
// =============================================================

require_once __DIR__ . '/functions.php';

/**
 * Ensures the visitor is logged in.
 *
 * If they are NOT logged in, they get redirected to the login page.
 * We save the URL they were trying to reach so we can send them
 * there after they successfully log in.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        // Save the intended destination for a better UX after login
        startSession();
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';

        setFlash('error', 'Please log in to access that page.');
        redirect('/auth/login.php');
    }
}

/**
 * Ensures the visitor is logged in AND has the required role.
 *
 * @param string $role  Required role: 'admin' or 'user'
 */
function requireRole(string $role): void
{
    // First make sure they're logged in at all
    requireLogin();

    startSession();

    // Now check if their role matches
    if ($_SESSION['role'] !== $role) {
        setFlash('error', 'You do not have permission to access that page.');

        // Redirect them to their own dashboard instead
        if ($_SESSION['role'] === 'admin') {
            redirect('/dashboards/admin.php');
        } else {
            redirect('/dashboards/user.php');
        }
    }
}

/**
 * Redirects already-logged-in users away from auth pages.
 *
 * Call this on login.php and register.php so that logged-in users
 * can't accidentally see those forms again.
 */
function redirectIfLoggedIn(): void
{
    if (isLoggedIn()) {
        startSession();
        $role = $_SESSION['role'] ?? 'user';

        if ($role === 'admin') {
            redirect('/dashboards/admin.php');
        } else {
            redirect('/dashboards/user.php');
        }
    }
}