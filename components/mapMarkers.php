<?php

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

// Some listing sources keep extra values inside a nested features array.
function getMapMarkerFeatureValue(array $listing, string $key) {
    if (!isset($listing['features']) || !is_array($listing['features'])) {
        return null;
    }

    if (!isset($listing['features'][$key])) {
        return null;
    }

    return $listing['features'][$key];
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

    if (!empty($listing['images']) && is_array($listing['images']) && !empty($listing['images'][0])) {
        $marker['image'] = $listing['images'][0];
    } else {
        $marker['image'] = '';
    }

    foreach (array('city', 'living_area', 'rooms', 'property_type', 'furnished', 'interior', 'housemates', 'plot_size', 'bathrooms', 'energy_label', 'rental_period', 'deposit', 'availability', 'source', 'neighbourhood') as $field) {
        if (!empty($listing[$field])) {
            $marker[$field] = $listing[$field];
        }
    }

    // Add feature-based tags that are stored differently by some sources.
    if (empty($marker['bathrooms'])) {
        $bathrooms = getMapMarkerFeatureValue($listing, 'Number of bath rooms');
        if (!empty($bathrooms)) {
            $marker['bathrooms'] = $bathrooms;
        }
    }

    $yearBuilt = getMapMarkerFeatureValue($listing, 'Year of construction');
    if (!empty($yearBuilt)) {
        $marker['year_built'] = $yearBuilt;
    }

    $status = getMapMarkerFeatureValue($listing, 'Status');
    if (!empty($status)) {
        $marker['status'] = $status;
    }

    $homeType = getMapMarkerFeatureValue($listing, 'Type apartment');
    if (empty($homeType)) {
        $homeType = getMapMarkerFeatureValue($listing, 'Kind of house');
    }
    if (!empty($homeType)) {
        $marker['home_type'] = $homeType;
    }

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
