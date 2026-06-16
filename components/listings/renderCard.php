<?php

require_once __DIR__ . '/../availabilityFormat.php';
require_once __DIR__ . '/listingTags.php';

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

    $tags = '';
    foreach (buildListingCardTags($listing) as $tag) {
        $tags .= '<span class="card-tag">' . htmlspecialchars($tag) . '</span>';
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
