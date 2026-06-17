<?php

// ?string means $apiError can be a string message or null when there is no error
// $offset and $count say how many listings to show, so the sidebar can display one page at a time
function renderMapSidebarList(?string $apiError, array $listings, array $listingMapIndexes, int $offset = 0, ?int $count = null): void {
    // Stop early if the API failed
    if ($apiError) {
        echo '<div class="api-error">API error: ' . htmlspecialchars($apiError) . '</div>';
        return;
    }

    if (empty($listings)) {
        echo '<div class="no-results">No listings found. Try a different source or filter.</div>';
        return;
    }

    // Work out the last item to show for this page.
    $total = count($listings);
    if ($count === null) {
        $end = $total;
    } else {
        $end = $offset + $count;
    }
    if ($end > $total) {
        $end = $total;
    }

    for ($index = $offset; $index < $end; $index++) {
        $listing = $listings[$index];

        // mapIndex connects this sidebar item to a marker. It is null when this listing has no coordinates
        if (isset($listingMapIndexes[$index])) {
            $mapIndex = $listingMapIndexes[$index];
        } else {
            $mapIndex = null;
        }

        echo renderMapListItem($listing, false, $mapIndex);
    }
}
