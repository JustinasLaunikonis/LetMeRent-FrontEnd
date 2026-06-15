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

    if (empty($listing['living_area'])) {
        $tags .= '<span class="card-tag">🅿️ Garage / Parking</span>';
    }

    if (!empty($listing['property_type'])) {
        $tags .= '<span class="card-tag">🛏️ ' . htmlspecialchars($listing['property_type']) . '</span>';
    } else if (!empty($listing['rooms'])) {
        $tags .= '<span class="card-tag">🛏️ ' . htmlspecialchars(countLabel($listing['rooms'], 'room')) . '</span>';
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

    // Utilities. Kamernet stores a phrase ("Incl. utilities"), iRentalize a
    // plain euro number. Show the phrase as-is, or "€N utilities" for a number.
    if (!empty($listing['utilities'])) {
        if (is_numeric($listing['utilities'])) {
            $tags .= '<span class="card-tag">💡 €' . htmlspecialchars($listing['utilities']) . ' utilities</span>';
        } else {
            $tags .= '<span class="card-tag">💡 ' . htmlspecialchars($listing['utilities']) . '</span>';
        }
    }

    // Monthly service fee (iRentalize)
    if (!empty($listing['service_fee'])) {
        $tags .= '<span class="card-tag">💶 €' . htmlspecialchars($listing['service_fee']) . ' service</span>';
    }

    // Other monthly costs (Kamernet)
    if (!empty($listing['additional_costs'])) {
        $tags .= '<span class="card-tag">➕ €' . htmlspecialchars($listing['additional_costs']) . ' extra</span>';
    }

    // Number of bedrooms. Some sources store this on top of "rooms".
    // Only show it when it is different from the rooms value above.
    if (!empty($listing['bedrooms'])) {
        if (empty($listing['rooms']) || $listing['bedrooms'] != $listing['rooms']) {
            $tags .= '<span class="card-tag">🛏️ ' . htmlspecialchars(countLabel($listing['bedrooms'], 'bed')) . '</span>';
        }
    }

    // Number of bathrooms. Most spiders store this at the top level,
    // but Funda keeps it inside its "features" box, so check both.
    if (!empty($listing['bathrooms'])) {
        $bathrooms = $listing['bathrooms'];
    } else {
        $bathrooms = featureValue($listing, 'Number of bath rooms');
    }
    if (!empty($bathrooms)) {
        $tags .= '<span class="card-tag">🛁 ' . htmlspecialchars(countLabel($bathrooms, 'bath')) . '</span>';
    }

    // Number of toilets
    if (!empty($listing['toilets'])) {
        $tags .= '<span class="card-tag">🚽 ' . htmlspecialchars(countLabel($listing['toilets'], 'toilet')) . '</span>';
    }

    // Number of kitchens
    if (!empty($listing['kitchens'])) {
        $tags .= '<span class="card-tag">🍳 ' . htmlspecialchars(countLabel($listing['kitchens'], 'kitchen')) . '</span>';
    }

    // Number of floors
    if (!empty($listing['floors'])) {
        $tags .= '<span class="card-tag">🏢 ' . htmlspecialchars(countLabel($listing['floors'], 'floor')) . '</span>';
    }

    // Balcony and roof terrace (huurwoningen stores "Present" when there is one)
    if (!empty($listing['balcony']) && $listing['balcony'] === 'Present') {
        $tags .= '<span class="card-tag">🌿 Balcony</span>';
    }
    if (!empty($listing['roof_terrace']) && $listing['roof_terrace'] === 'Present') {
        $tags .= '<span class="card-tag">☀️ Roof terrace</span>';
    }

    // Plot size for houses (square meters)
    if (!empty($listing['plot_size'])) {
        $tags .= '<span class="card-tag">🌳 ' . htmlspecialchars($listing['plot_size']) . ' m² plot</span>';
    }

    // Year the building was built. Huurwoningen stores it as "construction_year",
    // Funda keeps it inside its "features" box, so check both.
    if (!empty($listing['construction_year'])) {
        $yearBuilt = $listing['construction_year'];
    } else {
        $yearBuilt = featureValue($listing, 'Year of construction');
    }
    if (!empty($yearBuilt)) {
        $tags .= '<span class="card-tag">🏗️ ' . htmlspecialchars($yearBuilt) . '</span>';
    }

    // Building condition / upkeep (huurwoningen), example: "Good"
    if (!empty($listing['upkeep'])) {
        $tags .= '<span class="card-tag">🛠️ ' . htmlspecialchars($listing['upkeep']) . '</span>';
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

    // Neighbourhood / area name
    if (!empty($listing['neighbourhood'])) {
        $tags .= '<span class="card-tag">🗺️ ' . htmlspecialchars($listing['neighbourhood']) . '</span>';
    }

    // What kind of home it is, exampple: "Galleied apartment" or a house type.
    // This tag can be very long, so we add it LAST on purpose
    $type = featureValue($listing, 'Type apartment');
    if (empty($type)) {
        $type = featureValue($listing, 'Kind of house');
    }
    if (!empty($type)) {
        $tags .= '<span class="card-tag">🏠 ' . htmlspecialchars($type) . '</span>';
    }

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
    <a class="listing-card" href="' . $url . '" target="_blank" rel="noopener">
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
