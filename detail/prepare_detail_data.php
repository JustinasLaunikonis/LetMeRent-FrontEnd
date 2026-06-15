<?php

$listingTitle = firstString($listing, ['title'], 'Untitled listing');
$listingPrice = formatMoney(listingValue($listing, ['price'], ''));
$listingCity = firstString($listing, ['city'], '');
$listingSource = formatSourceLabel(firstString($listing, ['source'], ''));
$listingUrl = firstString($listing, ['url'], '');
$listingAddress = firstString($listing, ['address', 'street', 'location'], '');
$listingNeighbourhood = firstString($listing, ['neighbourhood'], '');
$listingAvailability = firstString($listing, ['availability'], '');
$listingScrapedAt = formatDateValue(listingValue($listing, ['scraped_at'], ''));
$listingDescription = buildDescription($listing);
$locationParts = [];
if ($listingNeighbourhood !== '') {
    $locationParts[] = $listingNeighbourhood;
}
if ($listingCity !== '') {
    $locationParts[] = $listingCity;
}
if ($listingAddress !== '') {
    $locationParts[] = $listingAddress;
}
$listingLocationLine = trim(implode(' - ', $locationParts));

$listingLatitudeValue = listingValue($listing, ['lat', 'latitude'], '');
$listingLongitudeValue = listingValue($listing, ['lng', 'longitude'], '');

if (is_numeric($listingLatitudeValue) && is_numeric($listingLongitudeValue)) {
    $detailMapHasCoordinates = true;
    $detailMapLatitude = (float) $listingLatitudeValue;
    $detailMapLongitude = (float) $listingLongitudeValue;
} else {
    $detailMapHasCoordinates = false;
    $detailMapLatitude = 52.3676;
    $detailMapLongitude = 4.9041;
}

if ($detailMapLatitude < 50.5) {
    $detailMapLatitude = 50.5;
}
if ($detailMapLatitude > 53.7) {
    $detailMapLatitude = 53.7;
}
if ($detailMapLongitude < 3.3) {
    $detailMapLongitude = 3.3;
}
if ($detailMapLongitude > 7.3) {
    $detailMapLongitude = 7.3;
}

$detailMapLeft = (($detailMapLongitude - 3.3) / (7.3 - 3.3)) * 100;
$detailMapTop = (1 - (($detailMapLatitude - 50.5) / (53.7 - 50.5))) * 100;

if ($detailMapLeft < 8) {
    $detailMapLeft = 8;
}
if ($detailMapLeft > 92) {
    $detailMapLeft = 92;
}
if ($detailMapTop < 10) {
    $detailMapTop = 10;
}
if ($detailMapTop > 90) {
    $detailMapTop = 90;
}

$detailMapLocationText = $listingLocationLine;
if ($detailMapLocationText === '') {
    $detailMapLocationText = $listingCity;
}
if ($detailMapLocationText === '') {
    $detailMapLocationText = 'Listing location';
}

$detailMapApiKey = readEnvValue('GOOGLE_MAPS_API_KEY');
if ($detailMapApiKey === '') {
    $detailMapApiKey = readEnvValue('GOOGLE_MAPS_KEY');
}

$detailMapZoom = 14;
if ($detailMapHasCoordinates === false) {
    $detailMapZoom = 12;
}

if ($pageError === null) {
    $listingScore = listingScore($listing);
    $listingScoreDisplay = (string) $listingScore . '% overview';
    $listingScoreLabel = 'Listing overview';
    $listingScoreHint = 'Based on available listing data';
} else {
    $listingScore = 0;
    $listingScoreDisplay = 'Unavailable';
    $listingScoreLabel = 'Unavailable';
    $listingScoreHint = 'No listing data could be loaded';
}

$listingImages = [];
if (!empty($listing['images']) && is_array($listing['images'])) {
    foreach ($listing['images'] as $image) {
        $image = trim((string) $image);
        if ($image !== '') {
            $listingImages[] = $image;
        }
    }
}

// We show at most 3 images on the detail page.
// one image is shown big, more than one is shown side by side (up to 3).
$galleryImages = array_slice($listingImages, 0, 3);
$galleryCount = count($galleryImages);

// Total number of images available. When there are more than the 3 we show, we display this count in the corner of the gallery.
$totalImageCount = count($listingImages);

if (isset($galleryImages[0])) {
    $galleryMain = $galleryImages[0];
} else {
    $galleryMain = '';
}

$chips = buildListingChips($listing);

if ($listingUrl !== '') {
    $sourceLink = $listingUrl;
    $sourceLinkTarget = ' target="_blank" rel="noopener noreferrer"';
} else {
    $sourceLink = '#';
    $sourceLinkTarget = '';
}

if ($listingNeighbourhood !== '') {
    $mapLocation = $listingNeighbourhood;
} else {
    $mapLocation = $listingCity;
}
if ($mapLocation === '') {
    $mapLocation = 'Listing location';
}

if ($listingCity !== '') {
    $campusLabel = truncateText($listingCity . ' campus', 16);
    $travelLabel = truncateText($listingCity . ' area', 16);
} else {
    $campusLabel = 'Campus';
    $travelLabel = 'No commute data';
}

$listingPricePlain = html_entity_decode($listingPrice, ENT_QUOTES, 'UTF-8');

if ($listingLocationLine !== '') {
    $commuteLocation = $listingLocationLine;
} else {
    $commuteLocation = 'Not specified';
}

if ($listingScrapedAt !== '') {
    $commuteListed = $listingScrapedAt;
} else {
    $commuteListed = 'Unknown';
}

if ($listingCity !== '') {
    $sidebarCity = $listingCity;
} else {
    $sidebarCity = 'Unknown';
}

require_once __DIR__ . '/../components/availabilityFormat.php';
$sidebarAvailability = formatAvailability($listingAvailability);

// When there is no availability text, default to "Available now".
if ($sidebarAvailability === '') {
    $sidebarAvailability = 'Available now';
}

$commuteRows = [
    ['label' => 'Location', 'value' => $commuteLocation],
    ['label' => 'Source', 'value' => $listingSource],
    ['label' => 'Scraped', 'value' => $commuteListed],
];
$sidebarFacts = [
    ['label' => 'Price', 'value' => $listingPricePlain],
    ['label' => 'City', 'value' => $sidebarCity],
    ['label' => 'Availability', 'value' => $sidebarAvailability],
    ['label' => 'Source', 'value' => $listingSource],
    ['label' => 'Lease length', 'value' => firstString($listing, ['rental_period'], 'Not specified')],
];

// One image gets shown big, more than one is laid out in a row.
if ($galleryCount > 1) {
    $galleryClass = 'gallery gallery--grid';
} else {
    $galleryClass = 'gallery gallery--single';
}

if ($listingLocationLine !== '') {
    $listingTitleLocation = $listingLocationLine;
} else {
    $listingTitleLocation = 'Listing details';
}

if ($listingAddress !== '') {
    $listingLocationAddress = $listingAddress;
} else {
    $listingLocationAddress = $listingLocationLine;
}

if ($listingScrapedAt !== '') {
    $listedText = 'Scraped ' . $listingScrapedAt;
} else {
    $listedText = 'Recently scraped';
}

if ($listingCity !== '') {
    $landlordCityText = $listingCity;
} else {
    $landlordCityText = 'No city set';
}

$landlordName = firstString($listing, ['landlord', 'agent', 'contact_name'], $listingSource);
$landlordInitial = strtoupper(substr($listingSource, 0, 1));
