<?php
$listings      = [];
$apiError      = null;
$totalListings = 0;

// -------------------------------------------------------------------------
// Read filters from the URL
// -------------------------------------------------------------------------
// When the user fills in the search bar and clicks Search,
// the page reloads with values in the URL like ?city=groningen&max_price=1000
// If nothing was filled in we use an empty string.

if (isset($_GET['city'])) {
    $city = trim($_GET['city']);
} else {
    $city = '';
}

if (isset($_GET['min_price'])) {
    $min_price = trim($_GET['min_price']);
} else {
    $min_price = '';
}

if (isset($_GET['max_price'])) {
    $max_price = trim($_GET['max_price']);
} else {
    $max_price = '';
}

// Read the selected sources from the URL (?source=kamernet,funda)
// and split them into a list. If nothing is selected, the list stays empty.
$sources = [];
if (!empty($_GET['source'])) {
    $sourceParts = explode(',', $_GET['source']);
    foreach ($sourceParts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $sources[] = $part;
        }
    }
}

// How many listings to show per page
$limit = 9;

// -------------------------------------------------------------------------
// Build the base parameters to send to the API
// -------------------------------------------------------------------------
// These are added to every API request. Filters are only added if they have a value.

$baseParams = [];
$baseParams['limit'] = $limit;

if ($city !== '') {
    $baseParams['city'] = $city;
}

if ($min_price !== '') {
    $baseParams['min_price'] = $min_price;
}

if ($max_price !== '') {
    $baseParams['max_price'] = $max_price;
}

// -------------------------------------------------------------------------
// Function to fetch listings from the API
// -------------------------------------------------------------------------
// Takes an array of parameters then sends a request to the API, and returns the result.

function fetchFromApi(array $params) {
    // read API base URL from .env file
    $envPath  = __DIR__ . '/../.env';
    $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $apiBase  = '';

    foreach ($envLines as $line) {
        $parts = explode('=', $line, 2);
        if (count($parts) === 2 && trim($parts[0]) === 'API_URL') {
            $apiBase = trim($parts[1]);
        }
    }

    $url = $apiBase . '?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    unset($ch);

    if ($curlError) {
        $result = [];
        $result['error'] = 'Could not reach API: ' . $curlError;
        return $result;
    }

    $decoded = json_decode($response, true);

    if (!isset($decoded['data']) || !is_array($decoded['data'])) {
        $result = [];
        $result['error'] = 'Unexpected API response format.';
        return $result;
    }

    $result = [];
    $result['data']  = $decoded['data'];

    if (isset($decoded['count'])) {
        $result['count'] = $decoded['count'];
    } else {
        $result['count'] = count($decoded['data']);
    }

    return $result;
}

// -------------------------------------------------------------------------
// Fetch listings. one request if no source filter, else one per source
// -------------------------------------------------------------------------

if (empty($sources)) {
    // No source selected, fetch all listings in one request
    $result = fetchFromApi($baseParams);

    if (isset($result['error'])) {
        $apiError = $result['error'];
    } else {
        $listings      = $result['data'];
        $totalListings = $result['count'];
    }
} else {
    // One or more sources selected: fetch each one separately and combine what was received
    foreach ($sources as $source) {
        $paramsWithSource = array_merge($baseParams, ['source' => $source]);
        $result = fetchFromApi($paramsWithSource);

        if (isset($result['error'])) {
            $apiError = $result['error'];
            break;
        }

        $listings      = array_merge($listings, $result['data']);
        $totalListings = $totalListings + $result['count'];
    }

    // cut the combined results to the page limit
    $listings = array_slice($listings, 0, $limit);
}

// Load the function which renders a listing card
require __DIR__ . '/renderCard.php';
