<?php

// This file prepares everything map/map.php needs before the HTML starts.

require __DIR__ . '/mapListings.php';
require __DIR__ . '/renderMapListItem.php';
require __DIR__ . '/renderMapSidebar.php';
require __DIR__ . '/mapMarkers.php';

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

// Build the marker list for Google Maps and remember which sidebar item belongs to which marker.
$markerData = buildMapMarkers($listings);
$mapMarkers = $markerData['markers'];
$listingMapIndexes = $markerData['listingMapIndexes'];

if ($city !== '') {
    $mapCenterQuery = $city;
} else {
    $mapCenterQuery = 'Amsterdam';
}
