<?php

// Reads a single value from the LetMeRent-FrontEnd/.env file.

// This is the one place the whole frontend reads configuration, so every page loads settings the same way.
function readEnv($key, $fallback = '') {
    $fromEnv = getenv($key);
    if ($fromEnv !== false && trim($fromEnv) !== '') {
        return trim($fromEnv);
    }

    $envPath = __DIR__ . '/../.env';
    if (!is_readable($envPath)) {
        return $fallback;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip blank lines and comments.
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        // Each setting looks like KEY=value. Split on the first "=" only.
        $parts = explode('=', $line, 2);
        if (count($parts) === 2 && trim($parts[0]) === $key) {
            // Remove spaces and any quotes around the value.
            return trim(trim($parts[1]), "\"'");
        }
    }

    return $fallback;
}
