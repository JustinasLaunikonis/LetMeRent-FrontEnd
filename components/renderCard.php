<?php

// returns the HTML for a single listing card.
// Call this function by passing one listing array

function renderCard(array $listing) {
    // Source website name
    if (isset($listing['source'])) {
        $source = htmlspecialchars(ucfirst($listing['source']));
    } else {
        $source = 'Unknown';
    }

    // Listing title
    if (isset($listing['title'])) {
        $title = htmlspecialchars($listing['title']);
    } else {
        $title = 'Untitled listing';
    }

    // Price
    if (isset($listing['price'])) {
        $price = htmlspecialchars('€' . $listing['price']);
    } else {
        $price = '—';
    }

    // City
    if (isset($listing['city'])) {
        $city = htmlspecialchars(ucfirst($listing['city']));
    } else {
        $city = '';
    }

    // Link to the listing
    if (isset($listing['url'])) {
        $url = htmlspecialchars($listing['url']);
    } else {
        $url = 'detail/detail.html';
    }

    // Use the first photo if available, otherwise show a placeholder drawing
    if (isset($listing['images']) && !empty($listing['images'])) {
        $images  = $listing['images'];
        $imgHtml = '<img src="' . htmlspecialchars($images[0]) . '" alt="' . $title . '" style="width:100%;height:100%;object-fit:cover;">';
    } else {
        $imgHtml = '
        <svg viewBox="0 0 340 186" xmlns="http://www.w3.org/2000/svg">
          <rect width="340" height="186" fill="#C7D2E0"/>
          <rect x="0" y="100" width="340" height="86" fill="#B8C8D8"/>
          <rect x="55" y="38" width="230" height="130" fill="#8FA3B5" rx="2"/>
          <rect x="82" y="58" width="58" height="62" fill="#7A90A5" rx="1"/>
          <rect x="200" y="58" width="58" height="62" fill="#7A90A5" rx="1"/>
          <rect x="148" y="118" width="44" height="52" fill="#6A8095" rx="2"/>
        </svg>';
    }

    // Date this listing was scraped
    if (isset($listing['scraped_at'])) {
        $scrapedAt = $listing['scraped_at'];
    } else {
        $scrapedAt = null;
    }

    // Build the small detail tags shown on the card
    $tags = '';

    if (!empty($listing['property_type'])) {
        $tags .= '<span class="card-tag">🛏️ ' . htmlspecialchars($listing['property_type']) . '</span>';
    } else if (!empty($listing['rooms'])) {
        $tags .= '<span class="card-tag">🛏️ ' . htmlspecialchars($listing['rooms']) . ' rooms</span>';
    }

    if (!empty($listing['furnished'])) {
        $tags .= '<span class="card-tag">🛋️ ' . htmlspecialchars($listing['furnished']) . '</span>';
    } else if (!empty($listing['interior'])) {
        $tags .= '<span class="card-tag">🛋️ ' . htmlspecialchars($listing['interior']) . '</span>';
    }

    if (!empty($listing['living_area'])) {
        $tags .= '<span class="card-tag">📐 ' . htmlspecialchars($listing['living_area']) . ' m²</span>';
    }

    if (!empty($listing['housemates'])) {
        $tags .= '<span class="card-tag">👥 ' . htmlspecialchars($listing['housemates']) . '</span>';
    }

    if (!empty($listing['energy_label'])) {
        $tags .= '<span class="card-tag">⚡ ' . htmlspecialchars($listing['energy_label']) . '</span>';
    }

    if (!empty($listing['rental_period'])) {
        $tags .= '<span class="card-tag">📋 ' . htmlspecialchars($listing['rental_period']) . '</span>';
    }

    if (!empty($listing['deposit'])) {
        $tags .= '<span class="card-tag">🔑 €' . htmlspecialchars($listing['deposit']) . ' deposit</span>';
    }

    // Availability date shown in the card footer
    $available = '';
    if (!empty($listing['availability'])) {
        $available = '<span class="card-time">📅 ' . htmlspecialchars($listing['availability']) . '</span>';
    }

    // Show a "NEW" badge if the listing was scraped in the last 24 hours
    $isNew = false;
    if ($scrapedAt !== null) {
        $ageInSeconds = time() - strtotime($scrapedAt);
        if ($ageInSeconds < 86400) {
            $isNew = true;
        }
    }

    if ($isNew) {
        $newTag      = '<div class="new-tag"><p>NEW</p></div>';
        $sourceClass = 'card-source card-source--shifted';
    } else {
        $newTag      = '';
        $sourceClass = 'card-source';
    }

    // Build and return the full card HTML
    return '
    <a class="listing-card" href="' . $url . '" target="_blank" rel="noopener">
      <div class="card-img-wrap">
        ' . $imgHtml . '
        ' . $newTag . '
        <div class="' . $sourceClass . '">
          <p>' . $source . '</p>
        </div>
        <div class="card-save">🤍</div>
      </div>

      <div class="card-body">
        <div class="card-price">
          ' . $price . ' <span>/ mo</span>
        </div>
        <div class="card-title">
          <p>' . $title . '</p>
        </div>
        <div class="card-meta">
          ' . $tags . '
        </div>
      </div>

      <div class="card-footer">
        <span class="card-loc">📍 ' . $city . '</span>
        ' . $available . '
      </div>
    </a>';
}
