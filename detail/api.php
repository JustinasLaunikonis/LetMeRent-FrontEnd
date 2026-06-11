<?php

function readEnvValue($key)
{
    $envPath = __DIR__ . '/../.env';

    if (!file_exists($envPath)) {
        return '';
    }

    $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($envLines === false) {
        return '';
    }

    foreach ($envLines as $line) {
        $parts = explode('=', $line, 2);
        if (count($parts) === 2 && trim($parts[0]) === $key) {
            return trim(trim($parts[1]), "\"'");
        }
    }

    return '';
}

function fetchFromApi($params)
{
    $apiBase = readEnvValue('API_URL');
    if ($apiBase === '') {
        return ['error' => 'API_URL is not configured.'];
    }

    $url = $apiBase . '?' . http_build_query($params);
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['error' => 'Could not reach API: ' . $curlError];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['error' => 'Unexpected API response format.'];
    }

    if (isset($decoded['error']) && is_string($decoded['error'])) {
        return ['error' => $decoded['error']];
    }

    if (isset($decoded['data']) && is_array($decoded['data'])) {
        if (isset($decoded['count'])) {
            $count = (int) $decoded['count'];
        } else {
            $count = count($decoded['data']);
        }

        return [
            'data' => $decoded['data'],
            'count' => $count,
        ];
    }

    if ($decoded === []) {
        return ['data' => [], 'count' => 0];
    }

    if (isset($decoded[0])) {
        return ['data' => $decoded, 'count' => count($decoded)];
    }

    return ['data' => [$decoded], 'count' => 1];
}
