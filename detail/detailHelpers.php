<?php

// Helper functions for the listing detail page
// reading values out of a listing, formatting them for display, and looking a listing up by its id through the API.

require_once __DIR__ . '/../includes/api.php';                // fetchFromApi()
require_once __DIR__ . '/../includes/env.php';                // readEnv()
require_once __DIR__ . '/../includes/availabilityFormat.php'; // formatAvailability()
require_once __DIR__ . '/../includes/listingTags.php';        // buildListingTags()

// -------------------------------------------------------------------------
// Formatting helpers
// -------------------------------------------------------------------------

// Make a value safe to print as HTML. Arrays are joined with commas first.
function esc($value)
{
    if ($value === null) {
        return '';
    }

    if (is_array($value)) {
        $value = implode(', ', $value);
    }

    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Capitalize the source name where needed (iRentalize, HousingAnywhere).
function formatSourceLabel($source)
{
    $normalized = strtolower(trim($source));

    if ($normalized === 'irentalize') {
        return 'iRentalize';
    }

    if ($normalized === 'housinganywhere') {
        return 'HousingAnywhere';
    }

    if ($source !== '') {
        return ucfirst($source);
    }

    return 'Unknown';
}

// Show a price as "€1.234". An mdash is shown when there is no price.
function formatMoney($value)
{
    if ($value === null || $value === '') {
        return '&mdash;';
    }

    if (is_numeric($value)) {
        return '&euro;' . number_format((float) $value, 0, ',', '.');
    }

    $text = trim((string) $value);
    if ($text === '') {
        return '&mdash;';
    }

    if (str_starts_with($text, '€')) {
        return esc($text);
    }

    return '&euro;' . esc($text);
}

// Show a date as "Aug 1, 2026". Returns the original text if it is not a date.
function formatDateValue($value)
{
    $text = trim((string) $value);
    if ($text === '') {
        return '';
    }

    $timestamp = strtotime($text);
    if ($timestamp === false) {
        return $text;
    }

    return date('M j, Y', $timestamp);
}

// -------------------------------------------------------------------------
// Reading values out of a listing
// -------------------------------------------------------------------------

// Return the first of the given keys that holds a non-empty value
function listingValue($listing, $keys, $fallback = '')
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $listing)) {
            continue;
        }

        $value = $listing[$key];

        if ($value === null) {
            continue;
        }

        if (is_string($value) && trim($value) === '') {
            continue;
        }

        if (is_array($value) && $value === []) {
            continue;
        }

        return $value;
    }

    return $fallback;
}

// Like listingValue, but always returns arrays joined by commas.
function listingText($listing, $keys, $fallback = '')
{
    $value = listingValue($listing, $keys, $fallback);

    if (is_array($value)) {
        $textParts = [];
        foreach ($value as $item) {
            $textParts[] = (string) $item;
        }

        $value = implode(', ', $textParts);
    }

    return trim((string) $value);
}

// Short alias used throughout the detail page
function firstString($listing, $keys, $fallback = '')
{
    return listingText($listing, $keys, $fallback);
}

// -------------------------------------------------------------------------
// Finding a listing by its id (through the API)
// -------------------------------------------------------------------------

function findListingById($listingId)
{
    global $lookupError;

    // First try the quick lookups by id and by Mongo _id.
    $searchParams = [
        ['id' => $listingId, 'limit' => 1, 'skip' => 0],
        ['_id' => $listingId, 'limit' => 1, 'skip' => 0],
    ];

    foreach ($searchParams as $params) {
        $result = fetchFromApi($params);
        if (isset($result['error'])) {
            $lookupError = $result['error'];
            continue;
        }

        foreach ($result['data'] as $listing) {
            if (!is_array($listing)) {
                continue;
            }

            if (listingMatchesId($listing, $listingId)) {
                return $listing;
            }
        }
    }

    // Fall back to scanning pages of listings until find a match.
    $pageSize = 200;
    $skip = 0;

    while (true) {
        $result = fetchFromApi([
            'limit' => $pageSize,
            'skip' => $skip,
        ]);

        if (isset($result['error'])) {
            $lookupError = $result['error'];
            return [];
        }

        foreach ($result['data'] as $listing) {
            if (!is_array($listing)) {
                continue;
            }

            if (listingMatchesId($listing, $listingId)) {
                return $listing;
            }
        }

        $returned = count($result['data']);
        if (isset($result['count'])) {
            $count = (int) $result['count'];
        } else {
            $count = $returned;
        }
        $skip += $returned;

        if ($returned === 0 || $skip >= $count) {
            break;
        }
    }

    return [];
}

function listingMatchesId($listing, $listingId)
{
    $idValue = '';
    if (isset($listing['id'])) {
        $idValue = (string) $listing['id'];
    }

    $mongoIdValue = '';
    if (isset($listing['_id'])) {
        $mongoIdValue = (string) $listing['_id'];
    }

    if ($idValue === $listingId || $mongoIdValue === $listingId) {
        return true;
    }

    return false;
}
