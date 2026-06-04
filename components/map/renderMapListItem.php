<?php

function renderMapListItem(array $listing, bool $selected, $mapIndex = null): string {
    // Add the selected class when the map page wants this listing highlighted.
    if ($selected) {
        $class = 'map-list-item js-map-listing selected';
    } else {
        $class = 'map-list-item js-map-listing';
    }

    // These attributes let JavaScript connect the sidebar item to Google Maps.
    $lat = null;
    $lng = null;

    if (isset($listing['lat']) && isset($listing['lng'])) {
        $lat = $listing['lat'];
        $lng = $listing['lng'];
    } else if (isset($listing['latitude']) && isset($listing['longitude'])) {
        $lat = $listing['latitude'];
        $lng = $listing['longitude'];
    }

    $mapAttributes = '';
    if (is_numeric($lat) && is_numeric($lng)) {
        $mapAttributes = ' data-lat="' . htmlspecialchars((string)$lat) . '" data-lng="' . htmlspecialchars((string)$lng) . '"';
    }

    // mapIndex is the marker number inside the JavaScript marker list.
    if ($mapIndex !== null) {
        $mapAttributes .= ' data-map-index="' . htmlspecialchars((string)$mapIndex) . '"';
    }

    // Set default values first. Then replace them if the listing has real values.
    $url = '../detail/detail.html';
    if (!empty($listing['url'])) {
        $url = htmlspecialchars($listing['url']);
    }

    $title = 'Untitled listing';
    if (!empty($listing['title'])) {
        $title = htmlspecialchars($listing['title']);
    }

    $price = '&mdash;';
    if (isset($listing['price']) && $listing['price'] !== '') {
        $price = '&euro;' . htmlspecialchars($listing['price']);
    }

    $thumb = '<div class="map-thumb"></div>';
    if (!empty($listing['images']) && is_array($listing['images']) && !empty($listing['images'][0])) {
        $image = htmlspecialchars($listing['images'][0]);
        $thumb = '<div class="map-thumb"><img src="' . $image . '" alt="' . $title . '" style="width:100%;height:100%;object-fit:cover;"></div>';
    }

    // Build a list of small tags to show under the title.
    // The order: city, living area, and rooms are added first.
    $tags = array();
    if (!empty($listing['city'])) {
        array_push($tags, '📍 ' . ucfirst($listing['city']));
    }
    if (!empty($listing['living_area'])) {
        array_push($tags, '📐 ' . $listing['living_area'] . ' m²');
    }
    if (!empty($listing['rooms'])) {
        array_push($tags, '🛏️ ' . $listing['rooms'] . ' rooms');
    } else if (!empty($listing['property_type'])) {
        array_push($tags, '🛏️ ' . $listing['property_type']);
    }
    if (!empty($listing['furnished'])) {
        array_push($tags, '🛋️ ' . $listing['furnished']);
    } else if (!empty($listing['interior'])) {
        array_push($tags, '🛋️ ' . $listing['interior']);
    }
    if (!empty($listing['housemates'])) {
        array_push($tags, '👥 ' . $listing['housemates']);
    }
    if (!empty($listing['energy_label'])) {
        array_push($tags, '⚡ ' . $listing['energy_label']);
    }
    if (!empty($listing['rental_period'])) {
        array_push($tags, '📋 ' . $listing['rental_period']);
    }
    if (!empty($listing['deposit'])) {
        array_push($tags, '🔑 €' . $listing['deposit'] . ' deposit');
    }

    $tagHtml = '';
    // array_slice takes only the first 3 items from the tags array.
    foreach (array_slice($tags, 0, 3) as $tag) {
        $tagHtml .= '<span class="tag">' . htmlspecialchars($tag) . '</span>';
    }

    // Return one complete clickable listing item.
    return '
        <a class="' . $class . '" href="#google-map" data-listing-url="' . $url . '"' . $mapAttributes . '>
          ' . $thumb . '
          <div class="map-item-info">
            <div class="map-item-price">' . $price . '<span>/mo</span></div>
            <div class="map-item-title">' . $title . '</div>
            <div class="map-item-tags">' . $tagHtml . '</div>
          </div>
        </a>';
}
