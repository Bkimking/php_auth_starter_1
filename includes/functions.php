<?php
// =============================================================
// includes/functions.php — Shared Helper Functions
// =============================================================
// This file contains small utility functions used across many
// pages. Keeping them here means we only write them once and
// require this file wherever needed.
// =============================================================

// ── Session ──────────────────────────────────────────────────

/**
 * Starts a PHP session if one isn't already running.
 * Always call this before reading or writing $_SESSION.
 */
function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// ── CSRF Protection ───────────────────────────────────────────
// CSRF (Cross-Site Request Forgery) attacks trick a logged-in
// user's browser into submitting a form on a malicious site.
// We prevent this by generating a unique token per session and
// checking it on every POST request.

/**
 * Returns the CSRF token, generating one if it doesn't exist yet.
 * The token is stored in the session so we can verify it later.
 *
 * @return string  A hex token string
 */
function getCsrfToken(): string
{
    startSession();

    // Generate a new token only once per session
    if (empty($_SESSION['csrf_token'])) {
        // random_bytes(32) gives us 32 random bytes → 64 hex chars
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verifies that the CSRF token submitted with a form matches
 * the one stored in the session.
 *
 * Call this at the top of every POST handler.
 *
 * @param string $token  The token from $_POST['csrf_token']
 * @return bool
 */
function verifyCsrfToken(string $token): bool
{
    startSession();

    // hash_equals() is used instead of === to prevent timing attacks
    // (an attacker can't determine token length by measuring response time)
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// ── Input Sanitization ────────────────────────────────────────

/**
 * Cleans a string for safe display in HTML.
 *
 * htmlspecialchars() converts characters like < > " & into safe
 * HTML entities so they render as text, not code.
 *
 * Use this on EVERY user-supplied value before echoing it.
 *
 * @param string $value
 * @return string
 */
function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// ── Flash Messages ────────────────────────────────────────────
// A "flash message" is a one-time notification (success or error)
// that appears after a redirect and disappears on the next page load.

/**
 * Stores a message in the session to be shown after a redirect.
 *
 * @param string $type     'success' | 'error' | 'info'
 * @param string $message  Human-readable message text
 */
function setFlash(string $type, string $message): void
{
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieves and removes the flash message from the session.
 * Returns null if there is no pending message.
 *
 * @return array|null  ['type' => '...', 'message' => '...']
 */
function getFlash(): ?array
{
    startSession();

    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']); // Delete after reading (one-time use)
        return $flash;
    }

    return null;
}

// ── Authentication Helpers ────────────────────────────────────

/**
 * Returns true if the current visitor is logged in.
 *
 * We check for 'user_id' in the session — it gets set in login.php.
 *
 * @return bool
 */
function isLoggedIn(): bool
{
    startSession();
    return isset($_SESSION['user_id']);
}

/**
 * Returns true if the logged-in user has the 'admin' role.
 *
 * @return bool
 */
function isAdmin(): bool
{
    startSession();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirects the browser to a different URL and stops execution.
 *
 * @param string $url  Relative or absolute URL to redirect to
 */
function redirect(string $url): void
{
    header("Location: $url");
    exit; // Always exit after header redirect to stop further code running
}

// ── Password Validation ───────────────────────────────────────

/**
 * Validates password strength requirements.
 * Returns an array of error messages (empty = password is valid).
 *
 * Requirements:
 *  - At least 8 characters
 *  - At least one uppercase letter
 *  - At least one number
 *
 * @param string $password
 * @return string[]  Array of error messages
 */
function validatePassword(string $password): array
{
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    }

    return $errors;
}