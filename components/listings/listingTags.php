<?php

// Shared tag logic so the front-page cards
if (!function_exists('featureValue')) {
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
}

if (!function_exists('countLabel')) {
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
}

function buildListingCardTags(array $listing): array {
    $tags = array();

    if (empty($listing['living_area'])) {
        $tags[] = '🅿️ Garage / Parking';
    }

    if (!empty($listing['property_type'])) {
        $tags[] = '🛏️ ' . $listing['property_type'];
    }

    if (!empty($listing['property_types'])) {
        if (empty($listing['property_type']) || $listing['property_types'] !== $listing['property_type']) {
            $tags[] = '🏠 ' . $listing['property_types'];
        }
    }

    if (!empty($listing['rooms'])) {
        $tags[] = '🚪 ' . countLabel($listing['rooms'], 'room');
    }

    if (!empty($listing['furnished'])) {
        $tags[] = '🛋️ ' . $listing['furnished'];
    } else if (!empty($listing['interior'])) {
        $tags[] = '🛋️ ' . $listing['interior'];
    }

    if (!empty($listing['living_area'])) {
        $tags[] = '📐 ' . $listing['living_area'] . ' m²';
    }

    if (!empty($listing['housemates'])) {
        $tags[] = '👥 Housemates: ' . $listing['housemates'];
    }

    if (!empty($listing['energy_label'])) {
        $tags[] = '⚡ ' . $listing['energy_label'];
    }

    if (!empty($listing['deposit'])) {
        $tags[] = '🔑 €' . $listing['deposit'] . ' deposit';
    }

    if (!empty($listing['utilities'])) {
        if (is_numeric($listing['utilities'])) {
            $tags[] = '💡 €' . $listing['utilities'] . ' utilities';
        } else {
            $tags[] = '💡 ' . $listing['utilities'];
        }
    }

    if (!empty($listing['service_fee'])) {
        $tags[] = '💶 €' . $listing['service_fee'] . ' service';
    }

    if (!empty($listing['additional_costs'])) {
        $tags[] = '➕ €' . $listing['additional_costs'] . ' extra';
    }

    if (!empty($listing['bedrooms'])) {
        if (empty($listing['rooms']) || $listing['bedrooms'] != $listing['rooms']) {
            $tags[] = '🛏️ ' . countLabel($listing['bedrooms'], 'bed');
        }
    }

    if (!empty($listing['bathrooms'])) {
        $bathrooms = $listing['bathrooms'];
    } else {
        $bathrooms = featureValue($listing, 'Number of bath rooms');
    }
    if (!empty($bathrooms)) {
        $tags[] = '🛁 ' . countLabel($bathrooms, 'bath');
    }

    if (!empty($listing['toilets'])) {
        $tags[] = '🚽 ' . countLabel($listing['toilets'], 'toilet');
    }

    if (!empty($listing['kitchens'])) {
        $tags[] = '🍳 ' . countLabel($listing['kitchens'], 'kitchen');
    }

    if (!empty($listing['floors'])) {
        $tags[] = '🏢 ' . countLabel($listing['floors'], 'floor');
    }

    if (!empty($listing['floor'])) {
        $tags[] = '🛗 ' . countLabel($listing['floor'], 'floor');
    }

    if (!empty($listing['balcony']) && $listing['balcony'] === 'Present') {
        $tags[] = '🌿 Balcony';
    }
    if (!empty($listing['roof_terrace']) && $listing['roof_terrace'] === 'Present') {
        $tags[] = '☀️ Roof terrace';
    }

    if (!empty($listing['plot_size'])) {
        $tags[] = '🌳 ' . $listing['plot_size'] . ' m² plot';
    }

    if (!empty($listing['construction_year'])) {
        $yearBuilt = $listing['construction_year'];
    } else {
        $yearBuilt = featureValue($listing, 'Year of construction');
    }
    if (!empty($yearBuilt)) {
        $tags[] = '🏗️ ' . $yearBuilt;
    }

    if (!empty($listing['upkeep'])) {
        $tags[] = '🛠️ ' . $listing['upkeep'] . ' upkeep';
    }

    if (!empty($listing['situation'])) {
        $tags[] = '🏙️ ' . $listing['situation'];
    }

    if (!empty($listing['gender_of_housemates'])) {
        $tags[] = '🚻 ' . $listing['gender_of_housemates'];
    }

    if (!empty($listing['kitchen'])) {
        $tags[] = '🍳 ' . $listing['kitchen'] . ' kitchen';
    }
    if (!empty($listing['bathroom'])) {
        $tags[] = '🚿 ' . $listing['bathroom'] . ' bathroom';
    }
    if (!empty($listing['toilet'])) {
        $tags[] = '🚽 ' . $listing['toilet'] . ' toilet';
    }

    if (!empty($listing['garden']) && $listing['garden'] === 'Present') {
        $tags[] = '🌷 Garden';
    }
    if (!empty($listing['storage']) && $listing['storage'] === 'Present') {
        $tags[] = '📦 Storage';
    }

    if (!empty($listing['parking']) && $listing['parking'] === 'Yes') {
        $tags[] = '🅿️ Parking';
    }
    if (!empty($listing['garage']) && $listing['garage'] === 'Yes') {
        $tags[] = '🚗 Garage';
    }

    if (!empty($listing['smoking_allowed']) && $listing['smoking_allowed'] === 'Yes') {
        $tags[] = '🚬 Smoking allowed';
    }
    if (!empty($listing['pets_allowed']) && $listing['pets_allowed'] === 'Yes') {
        $tags[] = '🐾 Pets allowed';
    }

    if (!empty($listing['target_audience'])) {
        $tags[] = '👤 ' . $listing['target_audience'];
    }

    $openTenantValues = array('Everyone welcome', 'Not important', 'Mixed', 'Any', 'Anyone', '');
    if (!empty($listing['tenant_type']) && !in_array($listing['tenant_type'], $openTenantValues)) {
        $tags[] = '👤 ' . $listing['tenant_type'];
    }
    if (!empty($listing['tenant_gender']) && !in_array($listing['tenant_gender'], $openTenantValues)) {
        $tags[] = '🚻 ' . $listing['tenant_gender'];
    }

    if (empty($listing['deposit']) && !empty($listing['deposit_policy'])) {
        $tags[] = '🔑 ' . $listing['deposit_policy'];
    }

    if (!empty($listing['wheelchair_accessible']) && $listing['wheelchair_accessible'] === 'Yes') {
        $tags[] = '♿ Wheelchair accessible';
    }

    if (isset($listing['ideal_tenant']) && is_array($listing['ideal_tenant'])) {
        $idealTenant = $listing['ideal_tenant'];

        $openValues = array('Everyone welcome', 'Not important', '');

        if (!empty($idealTenant['Occupation'])) {
            if (!in_array($idealTenant['Occupation'], $openValues)) {
                $tags[] = '👤 ' . $idealTenant['Occupation'];
            }
        }
        if (!empty($idealTenant['Gender'])) {
            if (!in_array($idealTenant['Gender'], $openValues)) {
                $tags[] = '🚻 ' . $idealTenant['Gender'];
            }
        }
    }

    if (!empty($listing['neighbourhood'])) {
        $tags[] = '🗺️ ' . $listing['neighbourhood'];
    }

    $type = featureValue($listing, 'Type apartment');
    if (empty($type)) {
        $type = featureValue($listing, 'Kind of house');
    }
    if (!empty($type)) {
        $tags[] = '🏠 ' . $type;
    }

    // Drop any tag whose visible text is longer than 50 chars.
    $filtered = array();
    foreach ($tags as $tag) {
        if (mb_strlen(trim($tag)) <= 50) {
            $filtered[] = $tag;
        }
    }

    return $filtered;
}
