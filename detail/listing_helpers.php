<?php

function listingValue($listing, $keys, $fallback = '')
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $listing)) {
            continue;
        }

        $value = $listing[$key];

        if ($value === null) {
            continue;
        }

        if (is_string($value) && trim($value) === '') {
            continue;
        }

        if (is_array($value) && $value === []) {
            continue;
        }

        return $value;
    }

    return $fallback;
}

function listingText($listing, $keys, $fallback = '')
{
    $value = listingValue($listing, $keys, $fallback);

    if (is_array($value)) {
        $textParts = [];
        foreach ($value as $item) {
            $textParts[] = (string) $item;
        }

        $value = implode(', ', $textParts);
    }

    return trim((string) $value);
}

function firstString($listing, $keys, $fallback = '')
{
    return listingText($listing, $keys, $fallback);
}

function featureValue($listing, $key)
{
    if (!isset($listing['features']) || !is_array($listing['features'])) {
        return '';
    }

    if (isset($listing['features'][$key])) {
        return trim((string) $listing['features'][$key]);
    }

    return '';
}

function buildDescription($listing)
{
    $description = firstString($listing, ['description', 'summary', 'content'], '');
    if ($description !== '') {
        return $description;
    }

    $title = firstString($listing, ['title'], '');
    $propertyType = firstString($listing, ['property_type'], '');
    $city = firstString($listing, ['city'], '');
    $street = firstString($listing, ['street', 'address', 'location'], '');
    $availability = firstString($listing, ['availability'], '');
    $furnished = firstString($listing, ['furnished', 'interior'], '');
    $livingArea = firstString($listing, ['living_area'], '');
    $housemates = firstString($listing, ['housemates'], '');
    $tenantType = firstString($listing, ['tenant_type'], '');
    $floor = firstString($listing, ['floor'], '');
    $depositPolicy = firstString($listing, ['deposit_policy'], '');
    $facilities = summarizeList((array) listingValue($listing, ['facilities'], []));
    $tags = summarizeList((array) listingValue($listing, ['tags'], []));
    $isFurnished = strtolower(trim($furnished)) === 'furnished';

    $sentences = [];

    if ($propertyType !== '') {
        $locationBits = [];
        if ($street !== '') {
            $locationBits[] = 'on ' . $street;
        }
        if ($city !== '') {
            $locationBits[] = 'in ' . $city;
        }

        $lead = $propertyType;
        if ($locationBits !== []) {
            $lead .= ' ' . joinNatural($locationBits);
        }

        $sentences[] = $lead . '.';
    } elseif ($title !== '') {
        $locationBits = [];
        if ($street !== '') {
            $locationBits[] = 'on ' . $street;
        }
        if ($city !== '') {
            $locationBits[] = 'in ' . $city;
        }

        $lead = $title;
        if ($locationBits !== []) {
            $lead .= ' ' . joinNatural($locationBits);
        }

        $sentences[] = $lead . '.';
    }

    $detailParts = [];
    if ($livingArea !== '') {
        $detailParts[] = $livingArea . ' m2';
    }
    if ($isFurnished) {
        $detailParts[] = 'furnished';
    } elseif ($furnished !== '') {
        $detailParts[] = $furnished;
    }
    if ($housemates !== '') {
        $housemateText = $housemates . ' housemate';
        if ((int) $housemates !== 1) {
            $housemateText .= 's';
        }

        $detailParts[] = $housemateText;
    }
    if ($floor !== '') {
        $detailParts[] = 'floor ' . $floor;
    }
    if ($availability !== '') {
        // text: "Available now", "From Aug 1, 2026", or a short phrase.
        $availabilityText = formatAvailability($availability);
        if ($availabilityText === 'Available now') {
            $detailParts[] = 'available now';
        } else if ($availabilityText !== '') {
            // Lowercase the first letter so it reads inside the sentence, e.g.
            // "From Aug 1, 2026" -> "available from Aug 1, 2026".
            $lowerFirst = strtolower(substr($availabilityText, 0, 1)) . substr($availabilityText, 1);
            $detailParts[] = 'available ' . $lowerFirst;
        }
    }
    if ($detailParts !== []) {
        $sentences[] = 'It offers ' . joinNatural($detailParts) . '.';
    }

    $audienceParts = [];
    if ($tenantType !== '') {
        $audienceParts[] = $tenantType;
    }
    if ($audienceParts !== []) {
        $sentences[] = 'Best suited for ' . joinNatural($audienceParts) . '.';
    }

    if ($depositPolicy !== '') {
        if (strtolower($depositPolicy) === 'no deposit') {
            $sentences[] = 'No deposit is required.';
        } else {
            $sentences[] = 'Deposit policy: ' . $depositPolicy . '.';
        }
    }

    if ($facilities !== '') {
        $sentences[] = 'Included facilities: ' . $facilities . '.';
    } elseif ($tags !== '') {
        $sentences[] = 'Highlights: ' . $tags . '.';
    }

    if ($sentences === []) {
        return 'No description was provided for this listing.';
    }

    return implode(' ', $sentences);
}

function listingScore($listing)
{
    if (isset($listing['score']) && is_numeric($listing['score'])) {
        $score = (int) round((float) $listing['score']);
        if ($score < 0) {
            $score = 0;
        }
        if ($score > 100) {
            $score = 100;
        }

        return $score;
    }

    if (isset($listing['match_score']) && is_numeric($listing['match_score'])) {
        $score = (int) round((float) $listing['match_score']);
        if ($score < 0) {
            $score = 0;
        }
        if ($score > 100) {
            $score = 100;
        }

        return $score;
    }

    $score = 40;
    $fieldGroups = [
        ['title'],
        ['price'],
        ['city'],
        ['source'],
        ['images'],
        ['description', 'summary', 'content'],
        ['availability'],
        ['property_type', 'rooms'],
        ['furnished', 'interior'],
        ['living_area'],
        ['neighbourhood'],
        ['lat', 'lng', 'latitude', 'longitude'],
    ];

    foreach ($fieldGroups as $fieldGroup) {
        if (listingText($listing, $fieldGroup, '') !== '') {
            $score += 5;
        }
    }

    if ($score < 45) {
        $score = 45;
    }
    if ($score > 98) {
        $score = 98;
    }

    return $score;
}

// Build a "count + word" label and add an "s" when there is more than one.
// Example: chipCountLabel(1, "bath") -> "1 bath", chipCountLabel(2, "bath") -> "2 baths".
function chipCountLabel($count, $word)
{
    $number = (int) $count;
    if ($number === 1) {
        return $number . ' ' . $word;
    } else {
        return $number . ' ' . $word . 's';
    }
}

function buildListingChips($listing)
{
    $chips = [];

    if (empty($listing['living_area'])) {
        $chips[] = '&#127359;&#65039; Garage / Parking';
    }

    if (!empty($listing['property_type'])) {
        $chips[] = '&#128719;&#65039; ' . esc($listing['property_type']);
    }

    if (!empty($listing['property_types'])) {
        if (empty($listing['property_type']) || $listing['property_types'] !== $listing['property_type']) {
            $chips[] = '&#127968; ' . esc($listing['property_types']);
        }
    }

    if (!empty($listing['rooms'])) {
        $chips[] = '&#128682; ' . esc(chipCountLabel($listing['rooms'], 'room'));
    }

    if (!empty($listing['furnished'])) {
        $chips[] = '&#128715;&#65039; ' . esc($listing['furnished']);
    } else if (!empty($listing['interior'])) {
        $chips[] = '&#128715;&#65039; ' . esc($listing['interior']);
    }

    if (!empty($listing['living_area'])) {
        $chips[] = '&#128208; ' . esc($listing['living_area']) . ' m&sup2;';
    }

    if (!empty($listing['housemates'])) {
        $chips[] = '&#128101; Housemates: ' . esc($listing['housemates']);
    }

    if (!empty($listing['energy_label'])) {
        $chips[] = '&#9889; ' . esc($listing['energy_label']);
    }

    if (!empty($listing['deposit'])) {
        $chips[] = '&#128273; &euro;' . esc($listing['deposit']) . ' deposit';
    }

    if (!empty($listing['utilities'])) {
        if (is_numeric($listing['utilities'])) {
            $chips[] = '&#128161; &euro;' . esc($listing['utilities']) . ' utilities';
        } else {
            $chips[] = '&#128161; ' . esc($listing['utilities']);
        }
    }

    if (!empty($listing['service_fee'])) {
        $chips[] = '&#128182; &euro;' . esc($listing['service_fee']) . ' service';
    }

    if (!empty($listing['additional_costs'])) {
        $chips[] = '&#10133; &euro;' . esc($listing['additional_costs']) . ' extra';
    }

    if (!empty($listing['bedrooms'])) {
        if (empty($listing['rooms']) || $listing['bedrooms'] != $listing['rooms']) {
            $chips[] = '&#128719;&#65039; ' . esc(chipCountLabel($listing['bedrooms'], 'bed'));
        }
    }

    if (!empty($listing['bathrooms'])) {
        $bathrooms = $listing['bathrooms'];
    } else {
        $bathrooms = featureValue($listing, 'Number of bath rooms');
    }
    if (!empty($bathrooms)) {
        $chips[] = '&#128705; ' . esc(chipCountLabel($bathrooms, 'bath'));
    }

    if (!empty($listing['toilets'])) {
        $chips[] = '&#128701; ' . esc(chipCountLabel($listing['toilets'], 'toilet'));
    }

    if (!empty($listing['kitchens'])) {
        $chips[] = '&#127859; ' . esc(chipCountLabel($listing['kitchens'], 'kitchen'));
    }

    if (!empty($listing['floors'])) {
        $chips[] = '&#127970; ' . esc(chipCountLabel($listing['floors'], 'floor'));
    }

    if (!empty($listing['floor'])) {
        $chips[] = '&#128727; ' . esc(chipCountLabel($listing['floor'], 'floor'));
    }

    if (!empty($listing['balcony']) && $listing['balcony'] === 'Present') {
        $chips[] = '&#127807; Balcony';
    }
    if (!empty($listing['roof_terrace']) && $listing['roof_terrace'] === 'Present') {
        $chips[] = '&#9728;&#65039; Roof terrace';
    }

    if (!empty($listing['plot_size'])) {
        $chips[] = '&#127795; ' . esc($listing['plot_size']) . ' m&sup2; plot';
    }

    if (!empty($listing['construction_year'])) {
        $yearBuilt = $listing['construction_year'];
    } else {
        $yearBuilt = featureValue($listing, 'Year of construction');
    }
    if (!empty($yearBuilt)) {
        $chips[] = '&#127959;&#65039; ' . esc($yearBuilt);
    }

    if (!empty($listing['upkeep'])) {
        $chips[] = '&#128736;&#65039; ' . esc($listing['upkeep']) . ' upkeep';
    }

    if (!empty($listing['situation'])) {
        $chips[] = '&#127961;&#65039; ' . esc($listing['situation']);
    }

    if (!empty($listing['gender_of_housemates'])) {
        $chips[] = '&#128699; ' . esc($listing['gender_of_housemates']);
    }

    if (!empty($listing['kitchen'])) {
        $chips[] = '&#127859; ' . esc($listing['kitchen']) . ' kitchen';
    }
    if (!empty($listing['bathroom'])) {
        $chips[] = '&#128703; ' . esc($listing['bathroom']) . ' bathroom';
    }
    if (!empty($listing['toilet'])) {
        $chips[] = '&#128701; ' . esc($listing['toilet']) . ' toilet';
    }

    if (!empty($listing['garden']) && $listing['garden'] === 'Present') {
        $chips[] = '&#127799; Garden';
    }
    if (!empty($listing['storage']) && $listing['storage'] === 'Present') {
        $chips[] = '&#128230; Storage';
    }

    if (!empty($listing['parking']) && $listing['parking'] === 'Yes') {
        $chips[] = '&#127359;&#65039; Parking';
    }
    if (!empty($listing['garage']) && $listing['garage'] === 'Yes') {
        $chips[] = '&#128663; Garage';
    }

    if (!empty($listing['smoking_allowed']) && $listing['smoking_allowed'] === 'Yes') {
        $chips[] = '&#128684; Smoking allowed';
    }
    if (!empty($listing['pets_allowed']) && $listing['pets_allowed'] === 'Yes') {
        $chips[] = '&#128062; Pets allowed';
    }

    if (!empty($listing['target_audience'])) {
        $chips[] = '&#128100; ' . esc($listing['target_audience']);
    }

    if (isset($listing['ideal_tenant']) && is_array($listing['ideal_tenant'])) {
        $idealTenant = $listing['ideal_tenant'];

        $openValues = array('Everyone welcome', 'Not important', '');

        if (!empty($idealTenant['Occupation'])) {
            if (!in_array($idealTenant['Occupation'], $openValues)) {
                $chips[] = '&#128100; ' . esc($idealTenant['Occupation']);
            }
        }
        if (!empty($idealTenant['Gender'])) {
            if (!in_array($idealTenant['Gender'], $openValues)) {
                $chips[] = '&#128699; ' . esc($idealTenant['Gender']);
            }
        }
    }

    if (!empty($listing['neighbourhood'])) {
        $chips[] = '&#128506;&#65039; ' . esc($listing['neighbourhood']);
    }

    $type = featureValue($listing, 'Type apartment');
    if (empty($type)) {
        $type = featureValue($listing, 'Kind of house');
    }
    if (!empty($type)) {
        $chips[] = '&#127968; ' . esc($type);
    }

    return $chips;
}
