<?php
declare(strict_types=1);

require_once __DIR__ . '/auth_config.php';

function callAuthApi(string $endpoint, array $payload): array
{
    $ch = curl_init(authApiBaseUrl() . $endpoint);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
    ]);

    $responseBody = curl_exec($ch);
    $curlError = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($responseBody === false) {
        return [
            'ok' => false,
            'status' => 0,
            'data' => null,
            'error' => 'Could not connect to the auth server: ' . $curlError,
        ];
    }

    $data = json_decode($responseBody, true);

    if ($statusCode < 200 || $statusCode >= 300) {
        return [
            'ok' => false,
            'status' => $statusCode,
            'data' => $data,
            'error' => $data['message'] ?? $data['error'] ?? 'Authentication request failed.',
        ];
    }

    return [
        'ok' => true,
        'status' => $statusCode,
        'data' => is_array($data) ? $data : [],
        'error' => null,
    ];
}

function storeAuthData(array $data): void
{
    startAuthSession();

    $_SESSION['user'] = $data['user'] ?? [
        'email' => $data['email'] ?? null,
        'username' => $data['username'] ?? null,
    ];

    $_SESSION['access_token'] = $data['accessToken']
        ?? $data['access_token']
        ?? $data['token']
        ?? $data['jwt']
        ?? null;

    $_SESSION['refresh_token'] = $data['refreshToken']
        ?? $data['refresh_token']
        ?? null;
}

function authHeaders(): array
{
    startAuthSession();

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    if (!empty($_SESSION['access_token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['access_token'];
        $headers[] = 'X-Access-Token: ' . $_SESSION['access_token'];
    }

    if (!empty($_SESSION['refresh_token'])) {
        $headers[] = 'X-Refresh-Token: ' . $_SESSION['refresh_token'];
    }

    return $headers;
}

function callApiWithAuth(string $method, string $endpoint, ?array $payload = null): array
{
    $ch = curl_init(authApiBaseUrl() . $endpoint);
    $method = strtoupper($method);

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => authHeaders(),
    ]);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
    }

    $responseBody = curl_exec($ch);
    $curlError = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($responseBody === false) {
        return [
            'ok' => false,
            'status' => 0,
            'data' => null,
            'error' => 'Could not connect to the API server: ' . $curlError,
        ];
    }

    $data = json_decode($responseBody, true);

    return [
        'ok' => $statusCode >= 200 && $statusCode < 300,
        'status' => $statusCode,
        'data' => is_array($data) ? $data : [],
        'error' => $data['message'] ?? $data['error'] ?? null,
    ];
}
