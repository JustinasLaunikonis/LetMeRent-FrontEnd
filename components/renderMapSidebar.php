<?php

// ?string means $apiError can be a string message or null when there is no error.
function renderMapSidebarList(?string $apiError, array $listings, array $listingMapIndexes): void {
    // Stop early if the API failed.
    if ($apiError) {
        echo '<div class="api-error">API error: ' . htmlspecialchars($apiError) . '</div>';
        return;
    }

    // Show a friendly message when there are no listings.
    if (empty($listings)) {
        echo '<div class="no-results">No listings found. Try a different source or filter.</div>';
        return;
    }

    $index = 0;
    foreach ($listings as $listing) {
        // mapIndex connects this sidebar item to a marker.
        // It is null when this listing has no coordinates.
        if (isset($listingMapIndexes[$index])) {
            $mapIndex = $listingMapIndexes[$index];
        } else {
            $mapIndex = null;
        }

        echo renderMapListItem($listing, false, $mapIndex);

        $index = $index + 1;
    }
}
