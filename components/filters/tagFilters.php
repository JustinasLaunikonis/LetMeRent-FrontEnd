<?php
// The switched-on tags are kept in the URL as ?has=key1,key2
// This is then sent to API, which then only returns listings that have those fields
// Clicking a chip reloads the page
// $_GET holds the current filters.

// The list of tags the user can filter on. (see api/routes.py).
// The "label" is what is shown on the chip.

// We only keep tags where "does the listing have it?" is actually a useful way to narrow results.
// Tags that almost every listing has (living area, neighbourhood, status, type) would filter nothing
// Tags like energy label have their own dropdown instead.
$tagFilters = array(
    array('key' => 'furnished',  'label' => '🛋️ Furnished'),
    array('key' => 'housemates', 'label' => '👥 Housemates'),
    array('key' => 'plot_size',  'label' => '🌳 Plot'),
    array('key' => 'bathrooms',  'label' => '🛁 Bathroom'),
);

// Read the tags that are currently switched on from the URL (?has=a,b,c).
$activeHas = array();
if (isset($_GET['has'])) {
    $parts = explode(',', $_GET['has']);
    for ($i = 0; $i < count($parts); $i++) {
        $one = trim($parts[$i]);
        if ($one !== '') {
            $activeHas[] = $one;
        }
    }
}

// Draw one chip per tag.
for ($i = 0; $i < count($tagFilters); $i++) {
    $key   = $tagFilters[$i]['key'];
    $label = $tagFilters[$i]['label'];

    // Is this tag currently switched on?
    $isOn = in_array($key, $activeHas);

    // Build the new list of switched-on tags for when this chip is clicked
    // If the tag is already on, clicking it removes it
    // If it is off, clicking it adds it
    $newHas = array();
    if ($isOn) {
        for ($j = 0; $j < count($activeHas); $j++) {
            if ($activeHas[$j] !== $key) {
                $newHas[] = $activeHas[$j];
            }
        }
    } else {
        for ($j = 0; $j < count($activeHas); $j++) {
            $newHas[] = $activeHas[$j];
        }
        $newHas[] = $key;
    }

    // Build the link, keeping the other active filters and resetting to page 1.
    $params = $_GET;
    unset($params['page']);
    if (count($newHas) > 0) {
        $params['has'] = implode(',', $newHas);
    } else {
        unset($params['has']);
    }
    $href = '?' . http_build_query($params);

    // Highlight the chip when its tag is switched on.
    if ($isOn) {
        $chipClass = 'filter-chip active';
    } else {
        $chipClass = 'filter-chip';
    }

    echo '<a class="' . $chipClass . '" href="' . htmlspecialchars($href) . '">';
    echo '<p>' . htmlspecialchars($label) . '</p>';
    echo '</a>';
}
?>
