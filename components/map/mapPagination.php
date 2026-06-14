<?php
// Prev / Next pagination buttons for the map sidebar listing list.

if (!isset($page)) {
    $page = 1;
}
if (!isset($totalMapPages)) {
    $totalMapPages = 1;
}

// Only show the buttons when there is more than one page.
if ($totalMapPages > 1) {
    $pagerParams = $_GET;
    unset($pagerParams['page']);

    if (!empty($pagerParams)) {
        $pagerBase = '?' . http_build_query($pagerParams) . '&page=';
    } else {
        $pagerBase = '?page=';
    }

    echo '<nav class="map-pagination">';

    // Previous button. It is disabled (just text) on the first page.
    if ($page > 1) {
        $prevPage = $page - 1;
        echo '<a class="map-page-btn" href="' . htmlspecialchars($pagerBase . $prevPage) . '">&#8592; Prev</a>';
    } else {
        echo '<span class="map-page-btn map-page-btn--disabled">&#8592; Prev</span>';
    }

    // Shows which page out of how many.
    echo '<span class="map-page-info">Page ' . $page . ' of ' . $totalMapPages . '</span>';

    // Next button. It is disabled (just text) on the last page.
    if ($page < $totalMapPages) {
        $nextPage = $page + 1;
        echo '<a class="map-page-btn" href="' . htmlspecialchars($pagerBase . $nextPage) . '">Next &#8594;</a>';
    } else {
        echo '<span class="map-page-btn map-page-btn--disabled">Next &#8594;</span>';
    }

    echo '</nav>';
}
?>
