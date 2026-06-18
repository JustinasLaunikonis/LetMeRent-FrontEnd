<?php
// listings.php builds the same query parameters the browse page uses but the map fetches its own larger result set below.
// The flag tells listings.php to skip that request.
$skipListingsFetch = true;
require __DIR__ . '/../listings/listings.php';

if (!isset($city)) {
    $city = '';
}

if (!isset($min_price)) {
    $min_price = '';
}

if (!isset($max_price)) {
    $max_price = '';
}

if (!isset($sources)) {
    $sources = array();
}

if (!isset($apiError)) {
    $apiError = null;
}

if (!isset($totalListings)) {
    $totalListings = 0;
}

if (!isset($listings)) {
    $listings = array();
}

// The map sidebar should show all possible listings.
if (!$apiError) {
    $mapListings = array();

    // If no source filter is selected, fetch every listing.
    // Ask for 500 first (skip is 0 so we start from the first listing), then ask again for the full amount if there turn out to be more.
    if (empty($sources)) {
        $mapParams = $baseParams;
        $mapParams['limit'] = 500;
        $mapParams['skip'] = 0;

        $mapResult = fetchFromApi($mapParams);

        // If the API returned an error, save it so we can show it on the page.
        if (isset($mapResult['error'])) {
            $apiError = $mapResult['error'];
        } else {
            // If there are more than 500 listings, ask again for the full amount.
            if (!empty($mapResult['count']) && $mapResult['count'] > count($mapResult['data'])) {
                $mapParams['limit'] = $mapResult['count'];
                $mapResult = fetchFromApi($mapParams);
            }

            if (isset($mapResult['error'])) {
                $apiError = $mapResult['error'];
            } else {
                $mapListings = $mapResult['data'];
                $totalListings = $mapResult['count'];
            }
        }
    } else {
        // If source filters are selected, fetch listings for each source one by one.
        foreach ($sources as $source) {
            $mapParams = $baseParams;
            $mapParams['limit'] = 500;
            $mapParams['skip'] = 0;
            $mapParams['source'] = $source;

            $mapResult = fetchFromApi($mapParams);

            // Break loop if one API request fails.
            if (isset($mapResult['error'])) {
                $apiError = $mapResult['error'];
                break;
            }

            // If the source has more than 500 listings, ask again for the full amount.
            if (!empty($mapResult['count']) && $mapResult['count'] > count($mapResult['data'])) {
                $mapParams['limit'] = $mapResult['count'];
                $mapResult = fetchFromApi($mapParams);

                // Break loop if the second API request fails.
                if (isset($mapResult['error'])) {
                    $apiError = $mapResult['error'];
                    break;
                }
            }

            // Add this source listings to the full map listings array.
            $mapListings = array_merge($mapListings, $mapResult['data']);
        }

        $totalListings = count($mapListings);
    }

    if (!$apiError) {
        $listings = $mapListings;
    }
}
