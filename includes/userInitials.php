<?php
declare(strict_types=1);

// Shared helper
// Works out the round avatar letters from the logged-in user that is stored in the session.
// The top navbar uses this so every page shows the same initials after a person has logged in.

require_once __DIR__ . '/../sign-up-in/authConfig.php';

function userIsLoggedIn(): bool
{
    startAuthSession();

    // A person counts as logged in when we have a user stored in the session
    if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return false;
    }

    return true;
}

function sessionUserInitials(): string
{
    startAuthSession();

    // No logged-in user yet, so show a placeholder.
    if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return 'AA';
    }

    $user = $_SESSION['user'];

    // Try to build a first/last name from the stored user.
    $firstName = '';
    if (isset($user['firstName'])) {
        $firstName = trim((string) $user['firstName']);
    } elseif (isset($user['first_name'])) {
        $firstName = trim((string) $user['first_name']);
    }

    $lastName = '';
    if (isset($user['lastName'])) {
        $lastName = trim((string) $user['lastName']);
    } elseif (isset($user['last_name'])) {
        $lastName = trim((string) $user['last_name']);
    }

    $displayName = trim($firstName . ' ' . $lastName);

    // If there is no first/last name, fall back to other name fields.
    if ($displayName === '') {
        $nameKeys = ['name', 'fullName', 'full_name', 'username'];
        foreach ($nameKeys as $key) {
            if (isset($user[$key]) && trim((string) $user[$key]) !== '') {
                $displayName = trim((string) $user[$key]);
                break;
            }
        }
    }

    $email = '';
    if (isset($user['email'])) {
        $email = trim((string) $user['email']);
    }

    // Use the name if we have one, otherwise use the email.
    $source = $displayName;
    if ($source === '') {
        $source = $email;
    }

    // Still nothing, so show the neutral placeholder.
    if ($source === '') {
        return 'AA';
    }

    // Take the first letter of the first (and second, if any) word.
    $parts = preg_split('/\s+/', $source);
    $initials = strtoupper(substr($parts[0], 0, 1));

    if (count($parts) > 1) {
        $initials .= strtoupper(substr($parts[1], 0, 1));
    }

    return $initials;
}
