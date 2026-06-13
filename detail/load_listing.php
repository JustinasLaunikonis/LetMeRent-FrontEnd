<?php

if (isset($_GET['id'])) {
    $listingId = trim((string) $_GET['id']);
} else {
    $listingId = '';
}

$pageError = null;
$lookupError = null;

if ($listingId === '') {
    $pageError = 'No listing ID provided.';
    $listing = [];
} else {
    $listing = findListingById($listingId);
    if ($listing === []) {
        if ($lookupError !== null) {
            $pageError = $lookupError;
        } else {
            $pageError = 'Listing not found for ID ' . $listingId . '.';
        }
    }
}
