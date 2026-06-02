<?php
declare(strict_types=1);

require_once __DIR__ . '/auth_api.php';

startAuthSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('./signin.html');
}

$requestData = $_POST;

if (str_contains((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
    $jsonData = json_decode((string) file_get_contents('php://input'), true);
    $requestData = is_array($jsonData) ? $jsonData : [];
}

$email = trim((string) ($requestData['email'] ?? ''));
$password = (string) ($requestData['password'] ?? '');

if ($email === '' || $password === '') {
    if (wantsJsonResponse()) {
        jsonResponse(['ok' => false, 'error' => 'Email and password are required.'], 422);
    }

    $_SESSION['auth_error'] = 'Email and password are required.';
    redirectTo('./signin.html');
}

$result = callAuthApi('/auth/login', [
    'email' => $email,
    'password' => $password,
]);

if (!$result['ok']) {
    if (wantsJsonResponse()) {
        jsonResponse(['ok' => false, 'error' => $result['error']], $result['status'] ?: 500);
    }

    $_SESSION['auth_error'] = $result['error'];
    redirectTo('./signin.html');
}

storeAuthData($result['data']);

if (wantsJsonResponse()) {
    jsonResponse([
        'ok' => true,
        'redirect' => '../profile/profile.html',
        'data' => $result['data'],
    ]);
}

redirectTo('../profile/profile.html');
