<?php
declare(strict_types=1);

$listingId = trim((string) ($_GET['id'] ?? ''));
$pageError = null;
$lookupError = null;

function readEnvValue(string $key): string
{
    $envPath = __DIR__ . '/../.env';

    if (!file_exists($envPath)) {
        return '';
    }

    $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($envLines === false) {
        return '';
    }

    foreach ($envLines as $line) {
        $parts = explode('=', $line, 2);
        if (count($parts) === 2 && trim($parts[0]) === $key) {
            return trim(trim($parts[1]), "\"'");
        }
    }

    return '';
}

function fetchFromApi(array $params): array
{
    $apiBase = readEnvValue('API_URL');
    if ($apiBase === '') {
        return ['error' => 'API_URL is not configured.'];
    }

    $url = $apiBase . '?' . http_build_query($params);
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['error' => 'Could not reach API: ' . $curlError];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['error' => 'Unexpected API response format.'];
    }

    if (isset($decoded['error']) && is_string($decoded['error'])) {
        return ['error' => $decoded['error']];
    }

    if (isset($decoded['data']) && is_array($decoded['data'])) {
        return [
            'data' => $decoded['data'],
            'count' => isset($decoded['count']) ? (int) $decoded['count'] : count($decoded['data']),
        ];
    }

    if (array_is_list($decoded)) {
        return ['data' => $decoded, 'count' => count($decoded)];
    }

    return ['data' => [$decoded], 'count' => 1];
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function listingValue(array $listing, array $keys, mixed $fallback = ''): mixed
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

function listingText(array $listing, array $keys, string $fallback = ''): string
{
    $value = listingValue($listing, $keys, $fallback);

    if (is_array($value)) {
        $value = implode(', ', array_map('strval', $value));
    }

    return trim((string) $value);
}

function firstString(array $listing, array $keys, string $fallback = ''): string
{
    return listingText($listing, $keys, $fallback);
}

function formatSourceLabel(string $source): string
{
    $normalized = strtolower(trim($source));

    if ($normalized === 'irentalize') {
        return 'iRentalize';
    }

    if ($normalized === 'housinganywhere') {
        return 'HousingAnywhere';
    }

    return $source !== '' ? ucfirst($source) : 'Unknown';
}

function formatMoney(mixed $value): string
{
    if ($value === null || $value === '') {
        return '&mdash;';
    }

    if (is_numeric($value)) {
        return '&euro;' . number_format((float) $value, 0, ',', '.');
    }

    $text = trim((string) $value);
    if ($text === '') {
        return '&mdash;';
    }

    return str_starts_with($text, '€') ? esc($text) : '&euro;' . esc($text);
}

function formatDateValue(mixed $value): string
{
    $text = trim((string) $value);
    if ($text === '') {
        return '';
    }

    $timestamp = strtotime($text);
    if ($timestamp === false) {
        return $text;
    }

    return date('M j, Y', $timestamp);
}

function truncateText(string $value, int $limit): string
{
    if (mb_strlen($value) <= $limit) {
        return $value;
    }

    return mb_substr($value, 0, max(0, $limit - 1)) . '...';
}

function featureValue(array $listing, string $key): string
{
    if (!isset($listing['features']) || !is_array($listing['features'])) {
        return '';
    }

    return trim((string) ($listing['features'][$key] ?? ''));
}

function joinNatural(array $items): string
{
    $filtered = [];

    foreach ($items as $item) {
        $item = trim((string) $item);
        if ($item !== '') {
            $filtered[] = $item;
        }
    }

    if ($filtered === []) {
        return '';
    }

    if (count($filtered) === 1) {
        return $filtered[0];
    }

    $last = array_pop($filtered);

    return implode(', ', $filtered) . ' and ' . $last;
}

function summarizeList(array $values, int $limit = 3): string
{
    $items = [];

    foreach ($values as $value) {
        $value = trim((string) $value);
        if ($value !== '' && !in_array($value, $items, true)) {
            $items[] = $value;
        }
    }

    if ($items === []) {
        return '';
    }

    return joinNatural(array_slice($items, 0, $limit));
}

function buildDescription(array $listing): string
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
        $detailParts[] = $housemates . ' housemate' . ((int) $housemates === 1 ? '' : 's');
    }
    if ($floor !== '') {
        $detailParts[] = 'floor ' . $floor;
    }
    if ($availability !== '') {
        $detailParts[] = strtolower($availability) === 'available now' ? 'available now' : 'available ' . $availability;
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
        $sentences[] = strtolower($depositPolicy) === 'no deposit'
            ? 'No deposit is required.'
            : 'Deposit policy: ' . $depositPolicy . '.';
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

function listingScore(array $listing): int
{
    if (isset($listing['score']) && is_numeric($listing['score'])) {
        return max(0, min(100, (int) round((float) $listing['score'])));
    }

    if (isset($listing['match_score']) && is_numeric($listing['match_score'])) {
        return max(0, min(100, (int) round((float) $listing['match_score'])));
    }

    $score = 40;
    foreach ([
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
    ] as $fieldGroup) {
        if (listingText($listing, $fieldGroup, '') !== '') {
            $score += 5;
        }
    }

    return max(45, min(98, $score));
}

function buildListingChips(array $listing): array
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

function findListingById(string $listingId): array
{
    global $lookupError;

    foreach ([
        ['id' => $listingId, 'limit' => 1, 'skip' => 0],
        ['_id' => $listingId, 'limit' => 1, 'skip' => 0],
    ] as $params) {
        $result = fetchFromApi($params);
        if (isset($result['error'])) {
            $lookupError = $result['error'];
            continue;
        }

        foreach ($result['data'] as $listing) {
            if (!is_array($listing)) {
                continue;
            }

            if ((string) ($listing['id'] ?? '') === $listingId || (string) ($listing['_id'] ?? '') === $listingId) {
                return $listing;
            }
        }

    }

    $pageSize = 200;
    $skip = 0;

    while (true) {
        $result = fetchFromApi([
            'limit' => $pageSize,
            'skip' => $skip,
        ]);

        if (isset($result['error'])) {
            $lookupError = $result['error'];
            return [];
        }

        foreach ($result['data'] as $listing) {
            if (!is_array($listing)) {
                continue;
            }

            if ((string) ($listing['id'] ?? '') === $listingId || (string) ($listing['_id'] ?? '') === $listingId) {
                return $listing;
            }
        }

        $returned = count($result['data']);
        $count = isset($result['count']) ? (int) $result['count'] : $returned;
        $skip += $returned;

        if ($returned === 0 || $skip >= $count) {
            break;
        }
    }

    return [];
}

if ($listingId === '') {
    $pageError = 'No listing ID provided.';
    $listing = [];
} else {
    $listing = findListingById($listingId);
    if ($listing === []) {
        $pageError = $lookupError !== null ? $lookupError : 'Listing not found for ID ' . $listingId . '.';
    }
}

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
$listingScore = $pageError === null ? listingScore($listing) : 0;
$listingScoreDisplay = $pageError === null ? (string) $listingScore . '% overview' : 'Unavailable';
$listingScoreLabel = $pageError === null ? 'Listing overview' : 'Unavailable';
$listingScoreHint = $pageError === null ? 'Based on available listing data' : 'No listing data could be loaded';
$listingLocationLine = trim(implode(' - ', array_filter([
    $listingNeighbourhood !== '' ? $listingNeighbourhood : null,
    $listingCity !== '' ? $listingCity : null,
    $listingAddress !== '' ? $listingAddress : null,
])));

$listingImages = [];
if (!empty($listing['images']) && is_array($listing['images'])) {
    foreach ($listing['images'] as $image) {
        $image = trim((string) $image);
        if ($image !== '') {
            $listingImages[] = $image;
        }
    }
}

$galleryMain = $listingImages[0] ?? '';
$galleryThumb1 = $listingImages[1] ?? '';
$galleryThumb2 = $listingImages[2] ?? '';
$hasSingleGalleryImage = count($listingImages) === 1;
$chips = buildListingChips($listing);
$sourceLink = $listingUrl !== '' ? $listingUrl : '#';
$sourceLinkTarget = $listingUrl !== '' ? ' target="_blank" rel="noopener noreferrer"' : '';
$mapLocation = $listingNeighbourhood !== '' ? $listingNeighbourhood : $listingCity;
if ($mapLocation === '') {
    $mapLocation = 'Listing location';
}
$campusLabel = $listingCity !== '' ? truncateText($listingCity . ' campus', 16) : 'Campus';
$travelLabel = $listingCity !== '' ? truncateText($listingCity . ' area', 16) : 'No commute data';
$listingPricePlain = html_entity_decode($listingPrice, ENT_QUOTES, 'UTF-8');
$commuteRows = [
    ['label' => 'Location', 'value' => $listingLocationLine !== '' ? $listingLocationLine : 'Not specified'],
    ['label' => 'Source', 'value' => $listingSource],
    ['label' => 'Listed', 'value' => $listingScrapedAt !== '' ? $listingScrapedAt : 'Unknown'],
];
$sidebarFacts = [
    ['label' => 'Price', 'value' => $listingPricePlain],
    ['label' => 'City', 'value' => $listingCity !== '' ? $listingCity : 'Unknown'],
    ['label' => 'Availability', 'value' => $listingAvailability !== '' ? $listingAvailability : 'Not specified'],
    ['label' => 'Source', 'value' => $listingSource],
    ['label' => 'Lease length', 'value' => firstString($listing, ['rental_period'], 'Not specified')],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LetMeRent - <?php echo esc($listingTitle); ?></title>
  <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="detail.css">
</head>

<body>
  <nav class="nav">
    <a class="nav-logo" href="../index.php">
      <div class="logo-icon">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
          <path d="M2 8L9 2L16 8V16H11V12H7V16H2V8Z" fill="white"/>
        </svg>
      </div>
      <p>LetMeRent</p>
    </a>

    <ul class="nav-links">
      <li><a href="../index.php">Browse</a></li>
      <li><a href="../map/map.php">Map View</a></li>
      <li><a href="../profile/profile.php">My Alerts</a></li>
    </ul>

    <div class="nav-right">
      <div class="nav-bell">&#128276;</div>
      <a href="../profile/profile.php" class="nav-avatar">JL</a>
    </div>
  </nav>

  <div class="breadcrumb">
    <a href="../index.php">Back to results</a>
    <span class="sep">&gt;</span>
    <span class="cur"><?php echo esc($listingTitle); ?></span>
  </div>

  <?php if ($pageError !== null): ?>
    <div class="detail-alert"><?php echo esc($pageError); ?></div>
  <?php endif; ?>

  <div class="detail-wrap">
    <div>
      <div class="gallery<?php echo $hasSingleGalleryImage ? ' gallery--single' : ''; ?>">
        <div class="gallery-main">
          <?php if ($galleryMain !== ''): ?>
            <img src="<?php echo esc($galleryMain); ?>" alt="<?php echo esc($listingTitle); ?>">
          <?php else: ?>
            <svg viewBox="0 0 460 376" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
              <rect width="460" height="376" fill="#B8C8D8"/>
              <rect x="0" y="210" width="460" height="166" fill="#A8B8C8"/>
              <rect x="50" y="58" width="360" height="260" fill="#8FA3B5" rx="2"/>
              <rect x="78" y="86" width="100" height="98" fill="#7A90A5" rx="1"/>
              <rect x="282" y="86" width="100" height="98" fill="#7A90A5" rx="1"/>
              <rect x="172" y="184" width="116" height="134" fill="#6A8095" rx="2"/>
              <path d="M172 184 Q230 156 288 184" stroke="#6A8095" stroke-width="3" fill="none"/>
              <rect x="50" y="316" width="360" height="8" rx="2" fill="#6A8095" opacity="0.5"/>
              <rect x="0" y="0" width="460" height="74" fill="#BFDBFE"/>
              <ellipse cx="36" cy="362" rx="36" ry="20" fill="#8AAE70" opacity="0.6"/>
            </svg>
          <?php endif; ?>
        </div>

        <?php if (!$hasSingleGalleryImage): ?>
          <div class="gallery-thumb">
            <?php if ($galleryThumb1 !== ''): ?>
              <img src="<?php echo esc($galleryThumb1); ?>" alt="<?php echo esc($listingTitle); ?>">
            <?php else: ?>
              <svg viewBox="0 0 200 228" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
                <rect width="200" height="228" fill="#9AA8B8"/>
                <rect x="18" y="38" width="164" height="152" fill="#7A8A9A"/>
                <rect x="38" y="58" width="124" height="112" fill="#6A7A8A"/>
              </svg>
            <?php endif; ?>
          </div>

          <div class="gallery-thumb">
            <?php if ($galleryThumb2 !== ''): ?>
              <img src="<?php echo esc($galleryThumb2); ?>" alt="<?php echo esc($listingTitle); ?>">
              <?php if (count($listingImages) > 3): ?>
                <div class="gallery-count">+<?php echo esc((string) (count($listingImages) - 3)); ?> photos</div>
              <?php endif; ?>
            <?php else: ?>
              <svg viewBox="0 0 200 148" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
                <rect width="200" height="148" fill="#C8D0B8"/>
                <rect x="10" y="18" width="180" height="112" fill="#B0B8A0"/>
                <rect x="28" y="38" width="68" height="68" fill="#9AA88A"/>
                <rect x="106" y="38" width="68" height="68" fill="#9AA88A"/>
              </svg>
              <?php if (count($listingImages) > 3): ?>
                <div class="gallery-count">+<?php echo esc((string) (count($listingImages) - 3)); ?> photos</div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="listing-title-row">
        <div class="listing-title">
          <p><?php echo esc($listingTitle); ?></p>
          <p><?php echo esc($listingLocationLine !== '' ? $listingLocationLine : 'Listing details'); ?></p>
        </div>

        <div class="listing-price"><?php echo $listingPrice; ?> <span>/ mo</span></div>
      </div>

      <div class="listing-location">
        <p>&#128205; <?php echo esc($listingAddress !== '' ? $listingAddress : $listingLocationLine); ?> &#183;</p>
        <a href="<?php echo esc($sourceLink); ?>"<?php echo $sourceLinkTarget; ?>><?php echo esc($listingSource); ?></a>
        <p>&#183; <?php echo esc($listingScrapedAt !== '' ? 'Listed ' . $listingScrapedAt : 'Recently listed'); ?></p>
      </div>

      <div class="chip-row">
        <?php if ($chips === []): ?>
          <div class="chip"><p>No listing tags available</p></div>
        <?php else: ?>
          <?php foreach ($chips as $chip): ?>
            <div class="chip"><p><?php echo $chip; ?></p></div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <hr class="section-divider">

      <div class="section-title">Description</div>
      <p class="desc-text"><?php echo esc($listingDescription); ?></p>

      <hr class="section-divider">

      <div class="section-title">Location & Details</div>
      <div class="map-box">
        <svg viewBox="0 0 680 186" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
          <rect width="680" height="186" fill="#E8F4FD"/>
          <rect x="18" y="12" width="90" height="58" fill="white" opacity="0.7" rx="4"/>
          <rect x="128" y="18" width="68" height="48" fill="white" opacity="0.6" rx="3"/>
          <rect x="216" y="8" width="98" height="64" fill="white" opacity="0.7" rx="4"/>
          <rect x="334" y="16" width="76" height="54" fill="white" opacity="0.6" rx="3"/>
          <rect x="430" y="12" width="92" height="58" fill="white" opacity="0.7" rx="4"/>
          <rect x="18" y="108" width="96" height="58" fill="white" opacity="0.6" rx="4"/>
          <rect x="134" y="118" width="82" height="52" fill="white" opacity="0.7" rx="3"/>
          <rect x="236" y="112" width="88" height="58" fill="white" opacity="0.6" rx="4"/>
          <rect x="346" y="118" width="92" height="52" fill="white" opacity="0.7" rx="3"/>
          <rect x="460" y="108" width="76" height="58" fill="white" opacity="0.6" rx="4"/>
          <rect x="0" y="84" width="680" height="14" fill="white" opacity="0.85"/>
          <rect x="316" y="0" width="14" height="186" fill="white" opacity="0.85"/>

          <g transform="translate(323,42)">
            <rect x="-26" y="-30" width="52" height="24" rx="12" fill="#1558A7"/>
            <text x="0" y="-13" text-anchor="middle" font-size="10" font-family="sans-serif" fill="white" font-weight="bold"><?php echo esc($campusLabel); ?></text>
            <polygon points="0,0 -6,-8 6,-8" fill="#1558A7"/>
          </g>

          <g transform="translate(196,124)">
            <rect x="-24" y="-30" width="48" height="24" rx="12" fill="#059669"/>
            <text x="0" y="-13" text-anchor="middle" font-size="10" font-family="sans-serif" fill="white" font-weight="bold"><?php echo esc($travelLabel); ?></text>
            <polygon points="0,0 -6,-8 6,-8" fill="#059669"/>
          </g>

          <path d="M196 112 Q260 92 323 68" stroke="#1558A7" stroke-width="2.5" stroke-dasharray="6,5" fill="none" opacity="0.7"/>
          <text x="230" y="78" font-size="9" fill="#1558A7" font-family="sans-serif" font-weight="600" opacity="0.9"><?php echo esc($mapLocation); ?></text>
        </svg>
      </div>

      <table class="commute-table">
        <?php foreach ($commuteRows as $row): ?>
          <tr>
            <td><?php echo esc($row['label']); ?></td>
            <td><?php echo esc($row['value']); ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>

    <div>
      <div class="sidebar-card">
        <div class="section-title">Listing Info</div>
        <div class="sidebar-facts">
          <?php foreach ($sidebarFacts as $fact): ?>
            <div class="sidebar-fact">
              <span><?php echo esc($fact['label']); ?></span>
              <strong><?php echo esc($fact['value']); ?></strong>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if ($listingUrl !== ''): ?>
          <a class="apply-btn" href="<?php echo esc($listingUrl); ?>" target="_blank" rel="noopener noreferrer">Browse <?php echo esc($listingSource); ?></a>
        <?php else: ?>
          <button class="apply-btn" disabled>Browse <?php echo esc($listingSource); ?></button>
        <?php endif; ?>
      </div>

      <div class="sidebar-card">
        <div class="section-title">Landlord</div>
        <div class="landlord-row">
          <div class="landlord-avatar"><?php echo esc(mb_strtoupper(mb_substr($listingSource, 0, 1))); ?></div>
          <div>
            <div class="landlord-name"><?php echo esc(firstString($listing, ['landlord', 'agent', 'contact_name'], $listingSource)); ?></div>
            <div class="landlord-meta"><?php echo esc($listingSource); ?> &#183; <?php echo esc($listingCity !== '' ? $listingCity : 'No city set'); ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
