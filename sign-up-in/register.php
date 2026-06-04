<?php
declare(strict_types=1);

require_once __DIR__ . '/auth_api.php';

startAuthSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('./signup.php');
}

$requestData = $_POST;

if (str_contains((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
    $jsonData = json_decode((string) file_get_contents('php://input'), true);
    $requestData = is_array($jsonData) ? $jsonData : [];
}

$username = trim((string) ($requestData['username'] ?? ''));
$email = trim((string) ($requestData['email'] ?? ''));
$password = (string) ($requestData['password'] ?? '');

if ($username === '' || $email === '' || $password === '') {
    if (wantsJsonResponse()) {
        jsonResponse(['ok' => false, 'error' => 'Username, email, and password are required.'], 422);
    }

    $_SESSION['auth_error'] = 'Username, email, and password are required.';
    redirectTo('./signup.php');
}

$result = callAuthApi('/auth/register', [
    'email' => $email,
    'password' => $password,
    'username' => $username,
]);

if (!$result['ok']) {
    if (wantsJsonResponse()) {
        jsonResponse(['ok' => false, 'error' => $result['error']], $result['status'] ?: 500);
    }

    $_SESSION['auth_error'] = $result['error'];
    redirectTo('./signup.php');
}

storeAuthData($result['data']);

if (wantsJsonResponse()) {
    jsonResponse([
        'ok' => true,
        'redirect' => '../profile/profile.php',
        'data' => $result['data'],
    ]);
}

redirectTo('../profile/profile.php');
