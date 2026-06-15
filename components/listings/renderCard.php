<?php

require_once __DIR__ . '/../availabilityFormat.php';

// Get a single value out of the listings "features" box.
// Some websites (like Funda) put a lot of extra features, if none exist return null
function featureValue(array $listing, $key) {
    if (!isset($listing['features'])) {
        return null;
    }
    if (!is_array($listing['features'])) {
        return null;
    }
    if (!isset($listing['features'][$key])) {
        return null;
    }

    return $listing['features'][$key];
}

// Build a "count + word" label and add an "s" when there is more than one.
// Example: countLabel(1, "bath") -> "1 bath", countLabel(2, "bath") -> "2 baths".
function countLabel($count, $word) {
    $number = (int) $count;
    if ($number === 1) {
        return $number . ' ' . $word;
    } else {
        return $number . ' ' . $word . 's';
    }
}

// returns the HTML for a single listing card.
// Call this function by passing one listing array

function renderCard(array $listing) {
    // Some source names have special capital letters.
    if (isset($listing['source'])) {
        if (strtolower($listing['source']) === 'irentalize') {
            $source = 'iRentalize';
        } else if (strtolower($listing['source']) === 'housinganywhere') {
            $source = 'HousingAnywhere';
        } else {
            $source = ucfirst($listing['source']);
        }
        $source = htmlspecialchars($source);
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

    // Link to detail page instead
    // The detail page finds the listing by its id (or MongoDB _id),
    $listingId = '';
    if (isset($listing['id'])) {
        $listingId = (string) $listing['id'];
    } else if (isset($listing['_id'])) {
        $listingId = (string) $listing['_id'];
    }

    if ($listingId !== '') {
        $url = 'detail/detail.php?id=' . htmlspecialchars(urlencode($listingId));
    } else {
        $url = 'detail/detail.php';
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

    if (empty($listing['living_area'])) {
        $tags .= '<span class="card-tag">🅿️ Garage / Parking</span>';
    }

    if (!empty($listing['property_type'])) {
        $tags .= '<span class="card-tag">🛏️ ' . htmlspecialchars($listing['property_type']) . '</span>';
    }

    if (!empty($listing['property_types'])) {
        if (empty($listing['property_type']) || $listing['property_types'] !== $listing['property_type']) {
            $tags .= '<span class="card-tag">🏠 ' . htmlspecialchars($listing['property_types']) . '</span>';
        }
    }

    if (!empty($listing['rooms'])) {
        $tags .= '<span class="card-tag">🚪 ' . htmlspecialchars(countLabel($listing['rooms'], 'room')) . '</span>';
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
        $tags .= '<span class="card-tag">👥 Housemates: ' . htmlspecialchars($listing['housemates']) . '</span>';
    }

    if (!empty($listing['energy_label'])) {
        $tags .= '<span class="card-tag">⚡ ' . htmlspecialchars($listing['energy_label']) . '</span>';
    }

    if (!empty($listing['deposit'])) {
        $tags .= '<span class="card-tag">🔑 €' . htmlspecialchars($listing['deposit']) . ' deposit</span>';
    }

    if (!empty($listing['utilities'])) {
        if (is_numeric($listing['utilities'])) {
            $tags .= '<span class="card-tag">💡 €' . htmlspecialchars($listing['utilities']) . ' utilities</span>';
        } else {
            $tags .= '<span class="card-tag">💡 ' . htmlspecialchars($listing['utilities']) . '</span>';
        }
    }

    if (!empty($listing['service_fee'])) {
        $tags .= '<span class="card-tag">💶 €' . htmlspecialchars($listing['service_fee']) . ' service</span>';
    }

    if (!empty($listing['additional_costs'])) {
        $tags .= '<span class="card-tag">➕ €' . htmlspecialchars($listing['additional_costs']) . ' extra</span>';
    }

    if (!empty($listing['bedrooms'])) {
        if (empty($listing['rooms']) || $listing['bedrooms'] != $listing['rooms']) {
            $tags .= '<span class="card-tag">🛏️ ' . htmlspecialchars(countLabel($listing['bedrooms'], 'bed')) . '</span>';
        }
    }

    if (!empty($listing['bathrooms'])) {
        $bathrooms = $listing['bathrooms'];
    } else {
        $bathrooms = featureValue($listing, 'Number of bath rooms');
    }
    if (!empty($bathrooms)) {
        $tags .= '<span class="card-tag">🛁 ' . htmlspecialchars(countLabel($bathrooms, 'bath')) . '</span>';
    }

    if (!empty($listing['toilets'])) {
        $tags .= '<span class="card-tag">🚽 ' . htmlspecialchars(countLabel($listing['toilets'], 'toilet')) . '</span>';
    }

    if (!empty($listing['kitchens'])) {
        $tags .= '<span class="card-tag">🍳 ' . htmlspecialchars(countLabel($listing['kitchens'], 'kitchen')) . '</span>';
    }

    if (!empty($listing['floors'])) {
        $tags .= '<span class="card-tag">🏢 ' . htmlspecialchars(countLabel($listing['floors'], 'floor')) . '</span>';
    }

    if (!empty($listing['floor'])) {
        $tags .= '<span class="card-tag">🛗 ' . htmlspecialchars(countLabel($listing['floor'], 'floor')) . '</span>';
    }

    if (!empty($listing['balcony']) && $listing['balcony'] === 'Present') {
        $tags .= '<span class="card-tag">🌿 Balcony</span>';
    }
    if (!empty($listing['roof_terrace']) && $listing['roof_terrace'] === 'Present') {
        $tags .= '<span class="card-tag">☀️ Roof terrace</span>';
    }

    if (!empty($listing['plot_size'])) {
        $tags .= '<span class="card-tag">🌳 ' . htmlspecialchars($listing['plot_size']) . ' m² plot</span>';
    }

    if (!empty($listing['construction_year'])) {
        $yearBuilt = $listing['construction_year'];
    } else {
        $yearBuilt = featureValue($listing, 'Year of construction');
    }
    if (!empty($yearBuilt)) {
        $tags .= '<span class="card-tag">🏗️ ' . htmlspecialchars($yearBuilt) . '</span>';
    }

    if (!empty($listing['upkeep'])) {
        $tags .= '<span class="card-tag">🛠️ ' . htmlspecialchars($listing['upkeep']) . ' upkeep</span>';
    }

    if (!empty($listing['situation'])) {
        $tags .= '<span class="card-tag">🏙️ ' . htmlspecialchars($listing['situation']) . '</span>';
    }

    if (!empty($listing['gender_of_housemates'])) {
        $tags .= '<span class="card-tag">🚻 ' . htmlspecialchars($listing['gender_of_housemates']) . '</span>';
    }

    if (!empty($listing['kitchen'])) {
        $tags .= '<span class="card-tag">🍳 ' . htmlspecialchars($listing['kitchen']) . ' kitchen</span>';
    }
    if (!empty($listing['bathroom'])) {
        $tags .= '<span class="card-tag">🚿 ' . htmlspecialchars($listing['bathroom']) . ' bathroom</span>';
    }
    if (!empty($listing['toilet'])) {
        $tags .= '<span class="card-tag">🚽 ' . htmlspecialchars($listing['toilet']) . ' toilet</span>';
    }

    if (!empty($listing['garden']) && $listing['garden'] === 'Present') {
        $tags .= '<span class="card-tag">🌷 Garden</span>';
    }
    if (!empty($listing['storage']) && $listing['storage'] === 'Present') {
        $tags .= '<span class="card-tag">📦 Storage</span>';
    }

    if (!empty($listing['parking']) && $listing['parking'] === 'Yes') {
        $tags .= '<span class="card-tag">🅿️ Parking</span>';
    }
    if (!empty($listing['garage']) && $listing['garage'] === 'Yes') {
        $tags .= '<span class="card-tag">🚗 Garage</span>';
    }

    if (!empty($listing['smoking_allowed']) && $listing['smoking_allowed'] === 'Yes') {
        $tags .= '<span class="card-tag">🚬 Smoking allowed</span>';
    }
    if (!empty($listing['pets_allowed']) && $listing['pets_allowed'] === 'Yes') {
        $tags .= '<span class="card-tag">🐾 Pets allowed</span>';
    }

    if (!empty($listing['target_audience'])) {
        $tags .= '<span class="card-tag">👤 ' . htmlspecialchars($listing['target_audience']) . '</span>';
    }

    $openTenantValues = array('Everyone welcome', 'Not important', 'Mixed', 'Any', 'Anyone', '');
    if (!empty($listing['tenant_type']) && !in_array($listing['tenant_type'], $openTenantValues)) {
        $tags .= '<span class="card-tag">👤 ' . htmlspecialchars($listing['tenant_type']) . '</span>';
    }
    if (!empty($listing['tenant_gender']) && !in_array($listing['tenant_gender'], $openTenantValues)) {
        $tags .= '<span class="card-tag">🚻 ' . htmlspecialchars($listing['tenant_gender']) . '</span>';
    }

    if (empty($listing['deposit']) && !empty($listing['deposit_policy'])) {
        $tags .= '<span class="card-tag">🔑 ' . htmlspecialchars($listing['deposit_policy']) . '</span>';
    }

    if (!empty($listing['wheelchair_accessible']) && $listing['wheelchair_accessible'] === 'Yes') {
        $tags .= '<span class="card-tag">♿ Wheelchair accessible</span>';
    }

    if (isset($listing['ideal_tenant']) && is_array($listing['ideal_tenant'])) {
        $idealTenant = $listing['ideal_tenant'];

        $openValues = array('Everyone welcome', 'Not important', '');

        if (!empty($idealTenant['Occupation'])) {
            if (!in_array($idealTenant['Occupation'], $openValues)) {
                $tags .= '<span class="card-tag">👤 ' . htmlspecialchars($idealTenant['Occupation']) . '</span>';
            }
        }
        if (!empty($idealTenant['Gender'])) {
            if (!in_array($idealTenant['Gender'], $openValues)) {
                $tags .= '<span class="card-tag">🚻 ' . htmlspecialchars($idealTenant['Gender']) . '</span>';
            }
        }
    }

    if (!empty($listing['neighbourhood'])) {
        $tags .= '<span class="card-tag">🗺️ ' . htmlspecialchars($listing['neighbourhood']) . '</span>';
    }

    $type = featureValue($listing, 'Type apartment');
    if (empty($type)) {
        $type = featureValue($listing, 'Kind of house');
    }
    if (!empty($type)) {
        $tags .= '<span class="card-tag">🏠 ' . htmlspecialchars($type) . '</span>';
    }

    // Drop any tag whose visible text is longer than 50 chars
    // The detail page keeps these tags
    $tags = preg_replace_callback(
        '/<span class="card-tag">(.*?)<\/span>/s',
        function ($match) {
            $text = html_entity_decode(strip_tags($match[1]), ENT_QUOTES, 'UTF-8');
            if (mb_strlen(trim($text)) > 50) {
                return '';
            }
            return $match[0];
        },
        $tags
    );

    // Availability date shown in the card footer
    $available = '';
    if (!empty($listing['availability'])) {
        $availabilityText = formatAvailability($listing['availability']);
        if ($availabilityText !== '') {
            $available = '<span class="card-time">📅 ' . htmlspecialchars($availabilityText) . '</span>';
        }
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

    // Build and return the full card HTML.
    return '
    <div class="listing-cell">
    <a class="listing-card" href="' . $url . '">
      <div class="card-img-wrap">
        ' . $imgHtml . '
        ' . $newTag . '
        <div class="' . $sourceClass . '">
          <p>' . $source . '</p>
        </div>
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
    </a>
    </div>';
}
