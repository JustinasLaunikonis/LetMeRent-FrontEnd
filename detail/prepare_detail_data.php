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

$listingImages = [];
if (!empty($listing['images']) && is_array($listing['images'])) {
    foreach ($listing['images'] as $image) {
        $image = trim((string) $image);
        if ($image !== '') {
            $listingImages[] = $image;
        }
    }
}

if (isset($listingImages[0])) {
    $galleryMain = $listingImages[0];
} else {
    $galleryMain = '';
}

if (isset($listingImages[1])) {
    $galleryThumb1 = $listingImages[1];
} else {
    $galleryThumb1 = '';
}

if (isset($listingImages[2])) {
    $galleryThumb2 = $listingImages[2];
} else {
    $galleryThumb2 = '';
}

$hasSingleGalleryImage = count($listingImages) === 1;
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

if ($listingAvailability !== '') {
    $sidebarAvailability = $listingAvailability;
} else {
    $sidebarAvailability = 'Not specified';
}

$commuteRows = [
    ['label' => 'Location', 'value' => $commuteLocation],
    ['label' => 'Source', 'value' => $listingSource],
    ['label' => 'Listed', 'value' => $commuteListed],
];
$sidebarFacts = [
    ['label' => 'Price', 'value' => $listingPricePlain],
    ['label' => 'City', 'value' => $sidebarCity],
    ['label' => 'Availability', 'value' => $sidebarAvailability],
    ['label' => 'Source', 'value' => $listingSource],
    ['label' => 'Lease length', 'value' => firstString($listing, ['rental_period'], 'Not specified')],
];

if ($hasSingleGalleryImage) {
    $galleryClass = 'gallery gallery--single';
} else {
    $galleryClass = 'gallery';
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
    $listedText = 'Listed ' . $listingScrapedAt;
} else {
    $listedText = 'Recently listed';
}

if ($listingCity !== '') {
    $landlordCityText = $listingCity;
} else {
    $landlordCityText = 'No city set';
}

if (count($listingImages) > 3) {
    $extraPhotoCount = count($listingImages) - 3;
} else {
    $extraPhotoCount = 0;
}

$landlordName = firstString($listing, ['landlord', 'agent', 'contact_name'], $listingSource);
$landlordInitial = strtoupper(substr($listingSource, 0, 1));
