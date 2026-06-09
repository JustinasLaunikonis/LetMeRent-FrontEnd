<?php
// Show the listings as cards. The variables $apiError and $listings come from
// components/listings/listings.php, and renderCard() comes from renderCard.php.

if ($apiError) {
    echo '<div class="api-error">API error: ' . htmlspecialchars($apiError) . '</div>';
}

echo '<div class="listings-grid">';

if (!empty($listings)) {
    foreach ($listings as $listing) {
        echo renderCard($listing);
    }
} else {
    echo '<div class="no-results">No listings found. Try a different source or filter.</div>';
}

echo '</div>';
?>
