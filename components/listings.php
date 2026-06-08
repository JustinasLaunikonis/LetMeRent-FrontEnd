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

if (isset($_GET['min_rooms'])) {
    $min_rooms = trim($_GET['min_rooms']);
} else {
    $min_rooms = '';
}

if (isset($_GET['max_rooms'])) {
    $max_rooms = trim($_GET['max_rooms']);
} else {
    $max_rooms = '';
}

if (isset($_GET['has'])) {
    $has = trim($_GET['has']);
} else {
    $has = '';
}

if (isset($_GET['energy_label'])) {
    $energy_label = trim($_GET['energy_label']);
} else {
    $energy_label = '';
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

// Which page are we on? Default to page 1.
if (isset($_GET['page']) && is_numeric($_GET['page']) && (int)$_GET['page'] > 0) {
    $page = (int)$_GET['page'];
} else {
    $page = 1;
}

// Calculate how many listings to skip based on the current page
$offset = ($page - 1) * $limit;

// Read sort preference from URL
$sort = '';
if (isset($_GET['sort'])) {
    $sort = trim($_GET['sort']);
}

// Read sort direction from URL (asc or desc). Anything else defaults to asc.
$order = 'asc';
if (isset($_GET['order']) && strtolower(trim($_GET['order'])) === 'desc') {
    $order = 'desc';
}

// -------------------------------------------------------------------------
// Build the parameters to send to the API
// -------------------------------------------------------------------------
// The API handles filtering, sorting and pagination in a single request, even
// across multiple sources, so there is no PHP-side merging or pagination. (Thank you Andrii)
// Filters are only added if they have a value.

$baseParams = [];
$baseParams['limit'] = $limit;
$baseParams['skip']  = $offset;

if ($city !== '') {
    $baseParams['city'] = $city;
}

// Pass every selected source in one request (?source=funda,kamernet,...).
if (!empty($sources)) {
    $baseParams['source'] = implode(',', $sources);
}

if ($min_price !== '') {
    $baseParams['min_price'] = $min_price;
}

if ($max_price !== '') {
    $baseParams['max_price'] = $max_price;
}

if ($min_rooms !== '') {
    $baseParams['min_rooms'] = $min_rooms;
}

if ($max_rooms !== '') {
    $baseParams['max_rooms'] = $max_rooms;
}

if ($has !== '') {
    $baseParams['has'] = $has;
}

if ($energy_label !== '') {
    $baseParams['energy_label'] = $energy_label;
}

if ($sort !== '') {
    $baseParams['sort']  = $sort;
    $baseParams['order'] = $order;
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
// Fetch listings. The API does all filtering, sorting and pagination, across
// every selected source, in a single request.
// -------------------------------------------------------------------------

$result = fetchFromApi($baseParams);

if (isset($result['error'])) {
    $apiError = $result['error'];
} else {
    $listings      = $result['data'];
    $totalListings = $result['count'];
}

// Calculate how many pages exist in total
if ($totalListings > 0) {
    $totalPages = (int)ceil($totalListings / $limit);
} else {
    $totalPages = 1;
}

// Load the function which renders a listing card
require __DIR__ . '/renderCard.php';
