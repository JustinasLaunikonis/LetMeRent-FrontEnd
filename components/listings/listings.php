<?php
// Shared API client (provides fetchFromApi()) and .env reader.
require_once __DIR__ . '/../../includes/api.php';

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

// "Garage / Parking" chip. When switched on (?no_living_area=1) we only show
// listings that have no living area, which are garages and parking spots.
if (isset($_GET['no_living_area'])) {
    $no_living_area = trim($_GET['no_living_area']);
} else {
    $no_living_area = '';
}

// The move-in date the user picked in the search bar (?available_by=2026-09-01).
if (isset($_GET['available_by'])) {
    $available_by = trim($_GET['available_by']);
} else {
    $available_by = '';
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

// How many listings to show per page (4 columns x 3 rows = 12)
$limit = 12;

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

// Campus distance filter.
if (isset($_GET['campus_lat'])) {
    $campus_lat = trim($_GET['campus_lat']);
} else {
    $campus_lat = '';
}

if (isset($_GET['campus_lng'])) {
    $campus_lng = trim($_GET['campus_lng']);
} else {
    $campus_lng = '';
}

if (isset($_GET['max_distance_km'])) {
    $max_distance_km = trim($_GET['max_distance_km']);
} else {
    $max_distance_km = '';
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
    // 5000 = "5000+" option, so it should not cap results.
    if (is_numeric($max_price)) {
        if ((int)$max_price < 5000) {
            $baseParams['max_price'] = $max_price;
        }
    } else {
        $baseParams['max_price'] = $max_price;
    }
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

if ($no_living_area !== '') {
    $baseParams['no_living_area'] = $no_living_area;
}

if ($available_by !== '') {
    $baseParams['available_by'] = $available_by;
}

if ($sort !== '') {
    $baseParams['sort']  = $sort;
    $baseParams['order'] = $order;
}

// Only send the distance filter when we have all three pieces:
// the campus  coordinates and the chosen distance.
if ($campus_lat !== '' && $campus_lng !== '' && $max_distance_km !== '') {
    $baseParams['campus_lat']      = $campus_lat;
    $baseParams['campus_lng']      = $campus_lng;
    $baseParams['max_distance_km'] = $max_distance_km;
}

// -------------------------------------------------------------------------
// Fetch listings. The API does all filtering, sorting and pagination, across every selected source, in a single request.
// -------------------------------------------------------------------------

if (empty($skipListingsFetch)) {
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
} else {
    $totalPages = 1;
}

// Load the function which renders a listing card
require __DIR__ . '/renderCard.php';
