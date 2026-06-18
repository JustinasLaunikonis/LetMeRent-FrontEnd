<?php
// "Clear all" chip
// clicking it reloads the page with no filters at all.
// $_GET holds the current filters.

// The URL parameters that count as "a filter being on".
$filterKeys = array(
    'city',
    'source',
    'min_price',
    'max_price',
    'min_rooms',
    'max_rooms',
    'sort',
    'order',
    'energy_label',
    'has',
    'available_by',
    'no_living_area',
);

// Check whether any of those filters is set in the URL.
$anyFilterOn = false;
for ($i = 0; $i < count($filterKeys); $i++) {
    $key = $filterKeys[$i];
    if (isset($_GET[$key]) && trim($_GET[$key]) !== '') {
        $anyFilterOn = true;
    }
}

// The chip is always shown but when a filter is on we highlight it so the user
// can tell there is something to clear.
// The link goes to "?" which reloads the page with an empty query string (no filters).
if ($anyFilterOn) {
    $clearClass = 'filter-chip active';
} else {
    $clearClass = 'filter-chip';
}
echo '<a class="' . $clearClass . '" href="?"><p>✕ Clear all</p></a>';
?>
