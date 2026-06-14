<?php

// This file prepares everything map/map.php needs before the HTML starts.

require_once __DIR__ . '/map/mapListings.php';
require_once __DIR__ . '/map/renderMapListItem.php';
require_once __DIR__ . '/renderMapSidebar.php';
require_once __DIR__ . '/mapMarkers.php';

function readEnvValue(string $key): string {
    $envPath = __DIR__ . '/../.env';

    if (!file_exists($envPath)) {
        return '';
    }

    $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($envLines as $line) {
        $parts = explode('=', $line, 2);

        if (count($parts) === 2 && trim($parts[0]) === $key) {
            // Remove spaces and optional quotes around values from .env.
            return trim(trim($parts[1]), '"\'');
        }
    }

    return '';
}

// These defaults keep the page from breaking if the API returns an error.
if (!isset($listings) || !is_array($listings)) {
    $listings = array();
}

if (!isset($totalListings)) {
    $totalListings = count($listings);
}

if (!isset($apiError)) {
    $apiError = null;
}

if (!isset($city)) {
    $city = '';
}

$googleMapsApiKey = readEnvValue('GOOGLE_MAPS_API_KEY');

// Support an older/shorter key name too, in case it is used in .env.
if ($googleMapsApiKey === '') {
    $googleMapsApiKey = readEnvValue('GOOGLE_MAPS_KEY');
}

// -------------------------------------------------------------------------
// Sidebar pagination
// -------------------------------------------------------------------------

$mapPerPage = 10;

if (!isset($page) || !is_numeric($page) || (int)$page < 1) {
    $page = 1;
}
$page = (int)$page;

// How many pages there are in total, based on all the listings found.
$totalMapPages = (int)ceil($totalListings / $mapPerPage);
if ($totalMapPages < 1) {
    $totalMapPages = 1;
}

// If the page number is past the last page, move it back to the last page.
if ($page > $totalMapPages) {
    $page = $totalMapPages;
}

$mapSkip = ($page - 1) * $mapPerPage;

// Build the marker list for Google Maps from ALL listings (so every pin shows), and assign sidebar item belongs to which marker
$markerData = buildMapMarkers($listings);
$mapMarkers = $markerData['markers'];
$listingMapIndexes = $markerData['listingMapIndexes'];

if ($city !== '') {
    $mapCenterQuery = $city;
} else {
    $mapCenterQuery = 'Amsterdam';
}

// The sidebar filter form (city / max budget / move-in) needs the same values the front page search bar uses.
require_once __DIR__ . '/indexPage/cityValues.php';
require_once __DIR__ . '/indexPage/budgetValues.php';
require_once __DIR__ . '/indexPage/moveInValues.php';
