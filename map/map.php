<?php
// This file prepares the listings, markers, API key, and other map variables.
require '../components/mapPageData.php';
// These defaults prevent warnings if something goes wrong while loading the data file.
if (!isset($mapCenterQuery)) {
  $mapCenterQuery = 'Amsterdam';
}
if (!isset($totalListings)) {
  $totalListings = 0;
}
if (!isset($apiError)) {
  $apiError = null;
}
if (!isset($listings) || !is_array($listings)) {
  $listings = array();
}
// These control how many listings the sidebar shows per page
if (!isset($mapPerPage)) {
  $mapPerPage = 10;
}
if (!isset($mapSkip)) {
  $mapSkip = 0;
}
if (!isset($page)) {
  $page = 1;
}
if (!isset($totalMapPages)) {
  $totalMapPages = 1;
}
if (!isset($listingMapIndexes) || !is_array($listingMapIndexes)) {
  $listingMapIndexes = array();
}
if (!isset($mapMarkers) || !is_array($mapMarkers)) {
  $mapMarkers = array();
}
if (!isset($googleMapsApiKey)) {
  $googleMapsApiKey = '';
}
if (!isset($selectedCity)) {
  $selectedCity = '';
}
if (!isset($selectedMaxBudget)) {
  $selectedMaxBudget = 950;
}
if (!isset($selectedMoveIn)) {
  $selectedMoveIn = '';
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
  <!-- Nav Bar -->
  <nav class="nav">
    <a class="nav-logo" href="../index.php">
      <div class="logo-icon">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
          <!-- Let-Me-Rent Logo -->
          <path d="M2 8L9 2L16 8V16H11V12H7V16H2V8Z" fill="white"/>
        </svg>
      </div>
      <p>LetMeRent</p>
    </a>

    <ul class="nav-links">
      <li><a href="../index.php">Browse</a></li>
      <li><a href="map.php" class="active">Map View</a></li>
      <li><a href="../profile/profile.php">My Alerts</a></li>
    </ul>

    <div class="nav-right">
      <div class="nav-bell">&#128276;</div>
      <a href="../profile/profile.html" class="nav-avatar">JL</a>
    </div>
  </nav>

  <div class="map-layout">
    <div class="map-sidebar">
      <div class="map-sidebar-head">
        <?php
        $panelFilterKeys = array(
            'source',
            'min_price',
            'max_price',
            'min_rooms',
            'max_rooms',
            'sort',
            'energy_label',
            'has',
            'no_living_area',
            'available_by',
        );
        $panelOpen = false;
        for ($i = 0; $i < count($panelFilterKeys); $i++) {
            $oneKey = $panelFilterKeys[$i];
            if (isset($_GET[$oneKey]) && trim($_GET[$oneKey]) !== '') {
                $panelOpen = true;
            }
        }

        // When the user switches pages we do NOT want the panel to pop open again
        if (isset($_GET['page'])) {
            $panelOpen = false;
        }
        if ($panelOpen) {
            $panelClass = 'map-filters-panel open';
            $chevClass = 'chev open';
        } else {
            $panelClass = 'map-filters-panel';
            $chevClass = 'chev';
        }
        ?>

        <!-- City search form -->
        <form class="map-search-form" method="get" action="map.php">
          <?php
          // Keep every filter that is already on, except the city (this form sets it) and the page number.
          $searchSkipKeys = array('city', 'page');
          foreach ($_GET as $paramName => $paramValue) {
              $keepIt = true;
              for ($i = 0; $i < count($searchSkipKeys); $i++) {
                  if ($paramName === $searchSkipKeys[$i]) {
                      $keepIt = false;
                  }
              }
              // Only simple text values can be put in a hidden field.
              if ($keepIt && is_string($paramValue)) {
                  echo '<input type="hidden" name="' . htmlspecialchars($paramName) . '" value="' . htmlspecialchars($paramValue) . '">';
              }
          }
          ?>

          <!-- Type a city (suggestions drop down) and press Enter or click Search. The suggestions come from mapCity.js. -->
          <div class="map-search">
            <span class="map-search-icon">&#128269;</span>
            <input type="text" id="map-city-input" name="city" value="<?php echo htmlspecialchars($selectedCity); ?>" placeholder="Search city..." autocomplete="off">
            <button type="submit" class="map-search-btn">Search</button>
            <div class="map-city-options" id="map-city-options"></div>
          </div>
        </form>

        <div class="map-count"><?php echo htmlspecialchars($totalListings); ?> listings <span>- profile applied</span></div>

        <!-- Button that opens and closes the list of filter options. -->
        <button type="button" class="map-filters-toggle" id="map-filters-toggle">
          <span>&#9776; Filters</span>
          <span class="<?php echo $chevClass; ?>" id="map-filters-chev">&#8595;</span>
        </button>

        <!-- Filters form: budget + move-in apply with the "Apply filters" button -->
        <form class="map-filters-form" method="get" action="map.php">
          <?php
          $filtersSkipKeys = array('city', 'max_price', 'available_by', 'page');
          foreach ($_GET as $paramName => $paramValue) {
              $keepIt = true;
              for ($i = 0; $i < count($filtersSkipKeys); $i++) {
                  if ($paramName === $filtersSkipKeys[$i]) {
                      $keepIt = false;
                  }
              }
              // Only simple text values can be put in a hidden field.
              if ($keepIt && is_string($paramValue)) {
                  echo '<input type="hidden" name="' . htmlspecialchars($paramName) . '" value="' . htmlspecialchars($paramValue) . '">';
              }
          }
          ?>
          <input type="hidden" name="city" id="map-filters-city" value="<?php echo htmlspecialchars($selectedCity); ?>">

          <div class="<?php echo $panelClass; ?>" id="map-filters-panel">
            <!-- Sources -->
            <p class="map-filter-label">Sources</p>
            <?php include __DIR__ . '/../components/filters/sourcePills.php'; ?>

            <!-- Max budget (number box stays in sync with the slider). -->
            <p class="map-filter-label">Max budget</p>
            <div class="map-budget">
              <div class="map-budget-row">
                <span class="map-budget-cur">&euro;</span>
                <input
                  class="map-budget-input"
                  id="map-budget-input"
                  type="number"
                  name="max_price"
                  min="0"
                  max="5000"
                  step="1"
                  value="<?php echo htmlspecialchars((string) $selectedMaxBudget); ?>"
                >
                <?php
                // When the budget is at the maximum (5000) it means
                // "no limit", show a "+" to read it as "5000 +"
                $budgetPlus = '';
                if ((int) $selectedMaxBudget >= 5000) {
                    $budgetPlus = '+';
                }
                ?>
                <span class="map-budget-plus" id="map-budget-plus"><?php echo $budgetPlus; ?></span>
                <span class="map-budget-per">/ mo</span>
              </div>
              <input
                class="map-budget-slider"
                id="map-budget-slider"
                type="range"
                min="0"
                max="5000"
                step="50"
                value="<?php echo htmlspecialchars((string) $selectedMaxBudget); ?>"
                aria-label="Max budget slider"
              >
            </div>

            <!-- Move-in date -->
            <p class="map-filter-label">Move-in by</p>
            <div class="map-movein">
              <input
                class="map-movein-input"
                id="map-movein-input"
                type="date"
                name="available_by"
                value="<?php echo htmlspecialchars($selectedMoveIn); ?>"
              >
              <button class="map-movein-clear" id="map-movein-clear" type="button">Any date</button>
            </div>

            <!-- Sort, rooms, energy and tag chips: the same filter bar as the front page -->
            <p class="map-filter-label">Sort &amp; more</p>
            <?php include __DIR__ . '/../components/indexPage/filterBar.php'; ?>

            <!-- Applies the city / budget / move-in fields above. -->
            <button type="submit" class="map-apply-btn">Apply filters</button>
          </div>
        </form>
      </div>
      <div class="map-list">
        <?php renderMapSidebarList($apiError, $listings, $listingMapIndexes, $mapSkip, $mapPerPage); ?>
      </div>
      <?php if ($totalMapPages > 1) { ?>
        <div class="map-foot">
          <?php include __DIR__ . '/../components/map/mapPagination.php'; ?>
        </div>
      <?php } ?>
    </div>

    <div id="map-resizer" class="map-resizer" role="separator" aria-orientation="vertical" aria-label="Resize sidebar"></div>

    <!-- Map -->
    <div class="map-canvas">
      <div id="google-map" class="google-map"></div>
      <?php if ($googleMapsApiKey === '') { ?>
        <div class="map-key-warning">
          Add GOOGLE_MAPS_API_KEY to your .env file to load the interactive Google map.
        </div>
      <?php } ?>
      <div class="map-circle-tool">
        <div class="map-place-search">
          <div class="map-place-row">
            <span class="map-place-icon">&#128269;</span>
            <input type="text" id="map-place-input" class="map-place-input" placeholder="Search a place" autocomplete="off">
          </div>
          <div class="map-circle-row">
            <span class="map-circle-name">Within</span>
            <span id="map-place-value" class="map-circle-value">2 km</span>
          </div>
          <input
            id="map-place-slider"
            class="map-circle-slider"
            type="range"
            min="100"
            max="15000"
            step="100"
            value="2000"
            aria-label="Search distance in metres"
          >
          <button id="map-place-search-btn" type="button" class="map-place-btn">Search area</button>
          <div id="map-place-status" class="map-place-status" hidden></div>
        </div>

        <button id="map-circle-toggle" type="button" class="map-circle-btn">Draw area circle</button>
        <div id="map-circle-controls" class="map-circle-controls" hidden>
          <div class="map-circle-row">
            <span class="map-circle-name" id="map-circle-name">Radius</span>
            <span id="map-circle-value" class="map-circle-value">5 km</span>
          </div>
          <input
            id="map-circle-slider"
            class="map-circle-slider"
            type="range"
            min="100"
            max="15000"
            step="100"
            value="5000"
            aria-label="Circle radius in metres"
          >
          <button id="map-circle-remove" type="button" class="map-circle-remove">Remove all circles</button>
        </div>
      </div>
      <div id="map-load-error" class="map-load-error" hidden>
        Google Maps rejected the API key. Check that billing is enabled, the Maps JavaScript API is enabled, and your key allows this website URL.
      </div>
      <div id="map-listing-bar" class="map-popup" hidden aria-live="polite">
        <div id="map-listing-image" class="popup-img"></div>
        <div class="popup-body">
          <div id="map-listing-price" class="popup-price"></div>
          <div id="map-listing-title" class="popup-title"></div>
          <div id="map-listing-tags" class="popup-tags"></div>
        </div>
        <div class="popup-actions">
          <a id="map-listing-link" class="popup-view popup-view--secondary" href="../detail/detail.html" target="_blank" rel="noopener">Browse Listing</a>
          <a id="map-listing-detail" class="popup-view" href="../detail/detail.html">More Details</a>
        </div>
        <button id="map-listing-close" class="popup-close" type="button" aria-label="Close listing details">&times;</button>
      </div>
    </div>
  </div>

  <script>
    // PHP sends the marker data to JavaScript here.
    // map.js uses this data to place pins on Google Maps.
    window.letMeRentMapConfig = {
      centerQuery: <?php echo json_encode($mapCenterQuery . ', Netherlands'); ?>,
      markers: <?php echo json_encode($mapMarkers); ?>
    };
  </script>
  <script src="../components/map/mapResizer.js"></script>
  <script src="../components/map/mapCircle.js"></script>
  <script src="../components/map/mapPlaceSearch.js"></script>
  <script src="../components/map.js"></script>
  <script src="../components/filters/filterDropdowns.js"></script>
  <script src="../components/filters/filterRooms.js"></script>
  <script src="../components/filters/filterPrice.js"></script>
  <script src="../components/filters/filterEnergy.js"></script>
  <script src="../components/map/mapFilters.js"></script>
  <script src="../components/map/mapStagedFilters.js"></script>
  <script src="../components/indexPage/cityGeoData.js"></script>
  <script src="../components/map/mapCity.js"></script>

  <?php if ($googleMapsApiKey !== '') { ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars(rawurlencode($googleMapsApiKey)); ?>&libraries=geometry,places&callback=initLetMeRentMap" async defer onerror="showMapLoadError()"></script>
  <?php } ?>
</body>
</html>
