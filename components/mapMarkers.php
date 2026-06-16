<?php

require_once __DIR__ . '/listings/listingTags.php';

function getListingCoordinates(array $listing) {
    // Some API responses use lat/lng, others use latitude/longitude.
    if (isset($listing['lat']) && isset($listing['lng'])) {
        $lat = $listing['lat'];
        $lng = $listing['lng'];
    } else if (isset($listing['latitude']) && isset($listing['longitude'])) {
        $lat = $listing['latitude'];
        $lng = $listing['longitude'];
    } else {
        return null;
    }

    if (!is_numeric($lat) || !is_numeric($lng)) {
        return null;
    }

    return array(
        'lat' => (float)$lat,
        'lng' => (float)$lng
    );
}

function createMapMarker(array $listing, array $coordinates, int $listingIndex, int $markerIndex): array {
    // This is the data JavaScript needs to create one Google Maps pin.
    $marker = array();
    $marker['lat'] = $coordinates['lat'];
    $marker['lng'] = $coordinates['lng'];

    if (!empty($listing['title'])) {
        $marker['title'] = $listing['title'];
    } else {
        $marker['title'] = 'Rental listing';
    }

    // Keep raw price and display fallback separate for the bottom listing bar.
    if (isset($listing['price']) && $listing['price'] !== '') {
        $marker['price'] = $listing['price'];
        $marker['priceLabel'] = 'EUR ' . $listing['price'] . '/mo';
    } else {
        $marker['price'] = '';
        $marker['priceLabel'] = 'Price unknown';
    }

    if (!empty($listing['url'])) {
        $marker['url'] = $listing['url'];
    } else {
        $marker['url'] = '../detail/detail.html';
    }

    // The detail page finds the listing by its id (or MongoDB _id).
    $listingId = '';
    if (isset($listing['id'])) {
        $listingId = (string) $listing['id'];
    } else if (isset($listing['_id'])) {
        $listingId = (string) $listing['_id'];
    }
    if ($listingId !== '') {
        $marker['id'] = $listingId;
    }

    if (!empty($listing['images']) && is_array($listing['images']) && !empty($listing['images'][0])) {
        $marker['image'] = $listing['images'][0];
    } else {
        $marker['image'] = '';
    }

    $marker['tags'] = buildListingCardTags($listing);

    $marker['listingIndex'] = $listingIndex;
    $marker['mapIndex'] = $markerIndex;

    return $marker;
}

function buildMapMarkers(array $listings): array {
    $mapMarkers = array();
    $listingMapIndexes = array();

    $listingIndex = 0;
    foreach ($listings as $listing) {
        $coordinates = getListingCoordinates($listing);

        // A listing without coordinates stays in the sidebar, but cannot have a map pin.
        if ($coordinates === null) {
            $listingIndex = $listingIndex + 1;
            continue;
        }

        $markerIndex = count($mapMarkers);
        $mapMarkers[] = createMapMarker($listing, $coordinates, $listingIndex, $markerIndex);

        // This connects sidebar item number X to marker number Y.
        $listingMapIndexes[$listingIndex] = $markerIndex;

        $listingIndex = $listingIndex + 1;
    }

    return array(
        'markers' => $mapMarkers,
        'listingMapIndexes' => $listingMapIndexes
    );
}
