<?php
declare(strict_types=1);

function appEnv(string $key, ?string $fallback = null): ?string
{
    $serverValue = getenv($key);

    if ($serverValue !== false && trim($serverValue) !== '') {
        return trim($serverValue);
    }

    $envPath = __DIR__ . '/../.env';

    if (!is_readable($envPath)) {
        return $fallback;
    }

    $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($envLines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);

        if (count($parts) === 2 && trim($parts[0]) === $key) {
            return trim($parts[1]);
        }
    }

    return $fallback;
}

function authApiBaseUrl(): string
{
    return rtrim((string) appEnv('AUTH_API_BASE_URL', ''), '/');
}

function chronoApiBaseUrl(): string
{
    $baseUrl = appEnv('CHRONO_API_BASE_URL', authApiBaseUrl());

    return rtrim((string) $baseUrl, '/');
}

function startAuthSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function redirectTo(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function wantsJsonResponse(): bool
{
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    $requestedWith = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');

    return str_contains($accept, 'application/json') || strtolower($requestedWith) === 'fetch';
}

function jsonResponse(array $body, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($body, JSON_THROW_ON_ERROR);
    exit;
}
