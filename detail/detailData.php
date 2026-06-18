<?php

// Works out every value the detail page template shows, from the looked-up $listing.
// $pageError is set by detail.php when the listing could not be found.

$listingTitle = firstString($listing, ['title'], 'Untitled listing');
$listingPrice = formatMoney(listingValue($listing, ['price'], ''));
$listingCity = firstString($listing, ['city'], '');
$listingSource = formatSourceLabel(firstString($listing, ['source'], ''));
$listingUrl = firstString($listing, ['url'], '');
$listingAddress = firstString($listing, ['address', 'street', 'location'], '');
$listingNeighbourhood = firstString($listing, ['neighbourhood'], '');
$listingAvailability = firstString($listing, ['availability'], '');
$listingScrapedAt = formatDateValue(listingValue($listing, ['scraped_at'], ''));
$listingDescription = firstString($listing, ['description', 'summary', 'content'], '');

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

// -------------------------------------------------------------------------
// Map: pick the coordinates and keep them inside the Netherlands
// -------------------------------------------------------------------------
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

$detailMapLocationText = $listingLocationLine;
if ($detailMapLocationText === '') {
    $detailMapLocationText = $listingCity;
}
if ($detailMapLocationText === '') {
    $detailMapLocationText = 'Listing location';
}

$detailMapApiKey = readEnv('GOOGLE_MAPS_API_KEY');
if ($detailMapApiKey === '') {
    $detailMapApiKey = readEnv('GOOGLE_MAPS_KEY');
}

$detailMapZoom = 14;
if ($detailMapHasCoordinates === false) {
    $detailMapZoom = 12;
}

// -------------------------------------------------------------------------
// Images: show one big image, or up to 3 side by side.
// -------------------------------------------------------------------------
$listingImages = [];
if (!empty($listing['images']) && is_array($listing['images'])) {
    foreach ($listing['images'] as $image) {
        $image = trim((string) $image);
        if ($image !== '') {
            $listingImages[] = $image;
        }
    }
}

$galleryImages = array_slice($listingImages, 0, 3);
$galleryCount = count($galleryImages);

// Total number of images available.
// When there are more than the 3 we show, display "Show All" in the corner of images container
$totalImageCount = count($listingImages);

if (isset($galleryImages[0])) {
    $galleryMain = $galleryImages[0];
} else {
    $galleryMain = '';
}

// One image gets shown big, more than one is laid out in a row.
if ($galleryCount > 1) {
    $galleryClass = 'gallery gallery--grid';
} else {
    $galleryClass = 'gallery gallery--single';
}

// -------------------------------------------------------------------------
// Tags, source link and the text shown around the listing.
// -------------------------------------------------------------------------
$chips = buildListingTags($listing);

if ($listingUrl !== '') {
    $sourceLink = $listingUrl;
    $sourceLinkTarget = ' target="_blank" rel="noopener noreferrer"';
} else {
    $sourceLink = '#';
    $sourceLinkTarget = '';
}

$listingPricePlain = html_entity_decode($listingPrice, ENT_QUOTES, 'UTF-8');

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

$sidebarAvailability = formatAvailability($listingAvailability);

// When there is no availability text, default to "Available now".
if ($sidebarAvailability === '') {
    $sidebarAvailability = 'Available now';
}

$sidebarFacts = [
    ['label' => 'Price', 'value' => $listingPricePlain],
    ['label' => 'City', 'value' => $sidebarCity],
    ['label' => 'Availability', 'value' => $sidebarAvailability],
    ['label' => 'Source', 'value' => $listingSource],
    ['label' => 'Lease length', 'value' => firstString($listing, ['duration_of_stay', 'rental_period'], 'Not specified')],
];

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
