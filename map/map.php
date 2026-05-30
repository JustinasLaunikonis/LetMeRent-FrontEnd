<?php
$city = 'Emmen';
if (isset($_GET['city']) && trim($_GET['city']) !== '') {
    $city = trim($_GET['city']);
}

$listings = [];
$apiError = null;

function fetchMapDataFromApi(array $params) {
    $envPath  = __DIR__ . '/../.env';
    $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $apiBase  = '';

    foreach ($envLines as $line) {
        $parts = explode('=', $line, 2);
        if (count($parts) === 2 && trim($parts[0]) === 'API_URL') {
            $apiBase = trim($parts[1]);
        }
    }

    $url = $apiBase . '?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    unset($ch);

    if ($curlError) {
        return ['error' => 'Could not reach API: ' . $curlError];
    }

    $decoded = json_decode($response, true);

    if (!isset($decoded['data']) || !is_array($decoded['data'])) {
        return ['error' => 'Unexpected API response format.'];
    }

    if (isset($decoded['count'])) {
        $count = (int)$decoded['count'];
    } else {
        $count = count($decoded['data']);
    }

    return [
        'data'  => $decoded['data'],
        'count' => $count,
    ];
}

function fetchAllCityListings($city) {
    $limit = 500;
    $skip = 0;
    $allListings = [];
    $totalCount = null;

    do {
        $result = fetchMapDataFromApi([
            'city'  => $city,
            'limit' => $limit,
            'skip'  => $skip,
        ]);

        if (isset($result['error'])) {
            return $result;
        }

        $batch = $result['data'];
        $allListings = array_merge($allListings, $batch);
        $totalCount = $result['count'];
        $skip += $limit;
    } while (count($batch) === $limit && count($allListings) < $totalCount);

    return [
        'data'  => $allListings,
        'count' => count($allListings),
    ];
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function listingValue(array $listing, $key, $fallback = '') {
    if (isset($listing[$key]) && $listing[$key] !== '') {
        return $listing[$key];
    }

    return $fallback;
}

function renderMapListing(array $listing, $selected = false) {
    $title = listingValue($listing, 'title', 'Untitled listing');
    $price = listingValue($listing, 'price', '');
    $url = listingValue($listing, 'url', '../detail/detail.html');
    $source = ucfirst(listingValue($listing, 'source', 'Unknown'));

    if ($price !== '') {
        $priceHtml = '&euro;' . e($price) . '<span>/mo</span>';
    } else {
        $priceHtml = 'Price unknown';
    }

    $thumb = '<div class="map-thumb-placeholder">Home</div>';
    if (isset($listing['images']) && is_array($listing['images']) && !empty($listing['images'])) {
        $thumb = '<img src="' . e($listing['images'][0]) . '" alt="' . e($title) . '">';
    }

    $tags = '<span class="tag">' . e($source) . '</span>';

    if (!empty($listing['property_type'])) {
        $tags .= '<span class="tag">' . e($listing['property_type']) . '</span>';
    } else if (!empty($listing['rooms'])) {
        $tags .= '<span class="tag">' . e($listing['rooms']) . ' rooms</span>';
    }

    if (!empty($listing['living_area'])) {
        $tags .= '<span class="tag">' . e($listing['living_area']) . ' m&sup2;</span>';
    }

    if (!empty($listing['furnished'])) {
        $tags .= '<span class="tag">' . e($listing['furnished']) . '</span>';
    } else if (!empty($listing['interior'])) {
        $tags .= '<span class="tag">' . e($listing['interior']) . '</span>';
    }

    $selectedClass = $selected ? ' selected' : '';

    return '
        <a class="map-list-item' . $selectedClass . '" href="' . e($url) . '" target="_blank" rel="noopener">
          <div class="map-thumb">' . $thumb . '</div>
          <div class="map-item-info">
            <div class="map-item-price">' . $priceHtml . '</div>
            <div class="map-item-title">' . e($title) . '</div>
            <div class="map-item-tags">' . $tags . '</div>
          </div>
        </a>';
}

$result = fetchAllCityListings($city);

if (isset($result['error'])) {
    $apiError = $result['error'];
} else {
    $listings = $result['data'];
}

$selectedListing = null;
if (!empty($listings)) {
    $selectedListing = $listings[0];
}
?>

<!DOCTYPE html>
<html lang="en" class="map-page">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LetMeRent - Map View</title>
  <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="map.css">
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
      <li><a href="map.php" class="active">Map View</a></li>
      <li><a href="../profile/profile.html">My Alerts</a></li>
    </ul>

    <div class="nav-right">
      <div class="nav-bell">🔔</div>
      <a href="../profile/profile.html" class="nav-avatar">JL</a>
    </div>
  </nav>

  <div class="map-layout">
    <div class="map-sidebar">
      <div class="map-sidebar-head">
        <form class="map-search" method="get" action="map.php">
          <input type="text" name="city" value="<?php echo e($city); ?>" placeholder="City">
        </form>

        <div class="map-count">
          <?php echo count($listings); ?> listings <span>&middot; <?php echo e($city); ?></span>
        </div>

        <?php
        if ($apiError) {
            echo '<div class="map-api-error">API error: ' . e($apiError) . '</div>';
        }
        ?>
      </div>

      <div class="map-list">
        <?php
        if (!empty($listings)) {
            foreach ($listings as $index => $listing) {
                echo renderMapListing($listing, $index === 0);
            }
        } else if (!$apiError) {
            echo '<div class="map-empty">No listings found for ' . e($city) . '.</div>';
        }
        ?>
      </div>
    </div>

    <div class="map-canvas">
      <div class="fake-map">
        <div class="map-road h road-h-1"></div>
        <div class="map-road h road-h-2"></div>
        <div class="map-road h road-h-3"></div>
        <div class="map-road h road-h-4"></div>
        <div class="map-road v road-v-1"></div>
        <div class="map-road v road-v-2"></div>
        <div class="map-road v road-v-3"></div>
        <div class="map-road v road-v-4"></div>

        <div class="map-block block-1"></div>
        <div class="map-block block-2"></div>
        <div class="map-block block-3"></div>
        <div class="map-block block-4"></div>
        <div class="map-block block-5"></div>
        <div class="map-block block-6"></div>
        <div class="map-block block-7"></div>
        <div class="map-block block-8"></div>
        <div class="map-block block-9"></div>
        <div class="map-block block-10"></div>
        <div class="map-block block-11"></div>
        <div class="map-block block-12"></div>
        <div class="map-block block-13"></div>
        <div class="map-block block-14"></div>
        <div class="map-block block-15"></div>
        <div class="map-block block-16"></div>
        <div class="map-block block-17"></div>
        <div class="map-block block-18"></div>
        <div class="map-block block-19"></div>

        <div class="distance-ring ring-outer"></div>
        <div class="distance-ring ring-inner"></div>

        <div class="campus-pin">
          <div class="campus-circle">
            <div class="campus-label">NHL Stenden Emmen Campus</div>
            Campus
          </div>
        </div>

        <?php
        $pinClasses = ['pin-dark', 'pin-green', 'pin-green', 'pin-green', 'pin-amber', 'pin-amber', 'pin-amber', 'pin-grey'];
        $pinPositions = ['pin-pos-1', 'pin-pos-2', 'pin-pos-3', 'pin-pos-4', 'pin-pos-5', 'pin-pos-6', 'pin-pos-7', 'pin-pos-8'];
        $pinListings = array_slice($listings, 0, 8);

        foreach ($pinListings as $index => $listing) {
            $pinPrice = listingValue($listing, 'price', '?');
            echo '<div class="map-pin ' . $pinPositions[$index] . '"><div class="pin-bubble ' . $pinClasses[$index] . '">&euro;' . e($pinPrice) . '</div></div>';
        }
        ?>

        <?php if ($selectedListing): ?>
        <?php
        $selectedTitle = listingValue($selectedListing, 'title', 'Untitled listing');
        $selectedPrice = listingValue($selectedListing, 'price', '');
        $selectedUrl = listingValue($selectedListing, 'url', '../detail/detail.html');
        ?>
        <div class="map-popup">
          <div class="popup-img">Home</div>
          <div>
            <div class="popup-price">
              <?php echo $selectedPrice !== '' ? '&euro;' . e($selectedPrice) : 'Price unknown'; ?><span>/mo</span>
            </div>
            <div class="popup-title"><?php echo e($selectedTitle); ?></div>
            <div class="popup-tags">
              <?php if (!empty($selectedListing['source'])): ?>
              <span class="tag"><?php echo e(ucfirst($selectedListing['source'])); ?></span>
              <?php endif; ?>
              <?php if (!empty($selectedListing['living_area'])): ?>
              <span class="tag"><?php echo e($selectedListing['living_area']); ?> m&sup2;</span>
              <?php endif; ?>
            </div>
          </div>
          <a href="<?php echo e($selectedUrl); ?>" target="_blank" rel="noopener" class="popup-view">View</a>
        </div>
        <?php endif; ?>

        <div class="map-legend">
          <div class="legend-title">Listings</div>
          <div class="legend-row"><div class="legend-dot dot-green"></div> Available listing</div>
          <div class="legend-row"><div class="legend-dot dot-dark dot-round"></div> Selected</div>
          <div class="legend-row"><div class="legend-ring-dot"></div> Campus range</div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
