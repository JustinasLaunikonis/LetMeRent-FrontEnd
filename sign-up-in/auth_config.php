<?php
declare(strict_types=1);

const AUTH_API_BASE_URL = 'http://178.238.226.221:5000';

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
