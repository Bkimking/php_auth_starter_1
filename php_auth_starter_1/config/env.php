<?php
// =============================================================
// config/env.php — Manual .env File Loader
// =============================================================
// This file reads the .env file and puts every variable into
// PHP's $_ENV superglobal and putenv() so we can access them
// anywhere with getenv('VAR_NAME') or $_ENV['VAR_NAME'].
//
// Why not use a library?
// We want students to understand *how* env loading works before
// reaching for a package. This is about 30 lines and does the job.
// =============================================================

/**
 * Loads variables from a .env file into the environment.
 *
 * @param string $path  Full path to the .env file.
 */
function loadEnv(string $path): void
{
    // Check the file actually exists before we try to read it
    if (!file_exists($path)) {
        die('ERROR: .env file not found. Copy .env.example to .env and fill in your values.');
    }

    // Read every line of the file into an array
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comment lines (lines starting with #)
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        // Only process lines that contain an equals sign
        if (!str_contains($line, '=')) {
            continue;
        }

        // Split on the FIRST equals sign only
        // This allows values like: DB_PASS=my=tricky=password
        [$key, $value] = explode('=', $line, 2);

        $key   = trim($key);
        $value = trim($value);

        // Remove surrounding quotes if present (single or double)
        // e.g.  MAIL_PASSWORD="abc 123"  →  abc 123
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        // Store in both $_ENV (PHP array) and the OS environment
        // so getenv() works from anywhere in the codebase
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// Load the .env file located one directory above /config
// __DIR__ is the directory of this file, so __DIR__ . '/../.env'
// points to the project root .env
loadEnv(__DIR__ . '/../.env');