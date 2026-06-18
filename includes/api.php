<?php

require_once __DIR__ . '/env.php';

// Sends one request to the listings API and returns the result.

// It always returns one of two shapes:
//   - ['error' => 'message']                  when something went wrong
//   - ['data' => [...], 'count' => number]    when listings came back

// The API does all the filtering, sorting and pagination, so the frontend just passes the query parameters straight through
function fetchFromApi(array $params) {
    $apiBase = readEnv('API_URL');
    if ($apiBase === '') {
        return ['error' => 'API_URL is not configured.'];
    }

    $url = $apiBase . '?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);

    if ($response === false) {
        return ['error' => 'Could not reach API: ' . $curlError];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['error' => 'Unexpected API response format.'];
    }

    // The API may send back an error message instead of listings
    if (isset($decoded['error']) && is_string($decoded['error'])) {
        return ['error' => $decoded['error']];
    }

    // Normal shape: { "data": [...], "count": number }.
    if (isset($decoded['data']) && is_array($decoded['data'])) {
        if (isset($decoded['count'])) {
            $count = (int) $decoded['count'];
        } else {
            $count = count($decoded['data']);
        }
        return ['data' => $decoded['data'], 'count' => $count];
    }

    // Some endpoints return a bare list, an empty array, or a single object.
    if ($decoded === []) {
        return ['data' => [], 'count' => 0];
    }
    if (isset($decoded[0])) {
        return ['data' => $decoded, 'count' => count($decoded)];
    }
    return ['data' => [$decoded], 'count' => 1];
}
