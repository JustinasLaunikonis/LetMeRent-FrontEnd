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
        if (strtolower($availability) === 'available now') {
            $detailParts[] = 'available now';
        } else {
            $detailParts[] = 'available ' . $availability;
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

function buildListingChips($listing)
{
    $chips = [];

    $propertyType = firstString($listing, ['property_type'], '');
    $rooms = firstString($listing, ['rooms'], '');
    $furnished = firstString($listing, ['furnished'], '');
    $interior = firstString($listing, ['interior'], '');
    $livingArea = firstString($listing, ['living_area'], '');
    $housemates = firstString($listing, ['housemates'], '');
    $energyLabel = firstString($listing, ['energy_label'], '');
    $availability = firstString($listing, ['availability'], '');
    $rentalPeriod = firstString($listing, ['rental_period'], '');
    $deposit = firstString($listing, ['deposit'], '');
    $neighbourhood = firstString($listing, ['neighbourhood'], '');
    $status = featureValue($listing, 'Status');
    $type = featureValue($listing, 'Type apartment');

    if ($propertyType !== '') {
        $chips[] = '&#128715; ' . esc($propertyType);
    } elseif ($rooms !== '') {
        $chips[] = '&#128715; ' . esc($rooms) . ' rooms';
    }

    if ($furnished !== '') {
        $chips[] = '&#128715; ' . esc($furnished);
    } elseif ($interior !== '') {
        $chips[] = '&#128715; ' . esc($interior);
    }

    if ($livingArea !== '') {
        $chips[] = '&#128208; ' . esc($livingArea) . ' m&sup2;';
    }

    if ($housemates !== '') {
        $chips[] = '&#128101; ' . esc($housemates);
    }

    if ($energyLabel !== '') {
        $chips[] = '&#9889; ' . esc($energyLabel);
    }

    if ($availability !== '') {
        $chips[] = '&#128197; ' . esc($availability);
    }

    if ($rentalPeriod !== '') {
        $chips[] = '&#128196; ' . esc($rentalPeriod);
    }

    if ($deposit !== '') {
        $chips[] = '&#128273; &euro;' . esc($deposit) . ' deposit';
    }

    $bathrooms = featureValue($listing, 'Number of bath rooms');
    if ($bathrooms !== '') {
        $chips[] = '&#128705; ' . esc($bathrooms) . ' bath';
    }

    $plotSize = firstString($listing, ['plot_size'], '');
    if ($plotSize !== '') {
        $chips[] = '&#127811; ' . esc($plotSize) . ' m&sup2; plot';
    }

    $yearBuilt = featureValue($listing, 'Year of construction');
    if ($yearBuilt !== '') {
        $chips[] = '&#127959; ' . esc($yearBuilt);
    }

    if ($neighbourhood !== '') {
        $chips[] = '&#128506; ' . esc($neighbourhood);
    }

    if ($status !== '') {
        $chips[] = '&#9989; ' . esc($status);
    }

    if ($type !== '') {
        $chips[] = '&#127968; ' . esc($type);
    }

    return $chips;
}
