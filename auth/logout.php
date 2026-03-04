<?php
// =============================================================
// auth/logout.php — Log Out
// =============================================================
// To log a user out we need to:
//   1. Clear all session data
//   2. Destroy the session
//   3. Expire the session cookie in the browser
//   4. Redirect to the login page
//
// There is no HTML for this page — it's pure PHP logic.
// =============================================================

require_once '../includes/functions.php';

startSession();

// 1. Wipe all session variables (user_id, name, role, etc.)
$_SESSION = [];

// 2. If the session uses a cookie, delete it from the browser
//    by setting its expiry date to the past
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000, // Past timestamp = delete the cookie
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 3. Destroy the server-side session file
session_destroy();

// 4. Redirect to login with a goodbye message
//    We can't use setFlash() here because the session is destroyed,
//    so we pass the message as a query parameter instead
header('Location: /auth/login.php?logged_out=1');
exit;