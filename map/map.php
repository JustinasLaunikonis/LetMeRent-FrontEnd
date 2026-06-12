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
if (!isset($listingMapIndexes) || !is_array($listingMapIndexes)) {
  $listingMapIndexes = array();
}
if (!isset($mapMarkers) || !is_array($mapMarkers)) {
  $mapMarkers = array();
}
if (!isset($googleMapsApiKey)) {
  $googleMapsApiKey = '';
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
        <div class="map-search">
          <input type="text" value="<?php echo htmlspecialchars($mapCenterQuery); ?>" placeholder="Search...">
        </div>
        <div class="map-count"><?php echo htmlspecialchars($totalListings); ?> listings <span>- profile applied</span></div>
        <div class="map-filter-pills">
          <div class="map-pill active">All</div>
          <div class="map-pill">≥80% match</div>
          <div class="map-pill">&lt;5 km</div>
          <div class="map-pill">🛋️ Furnished</div>
          <div class="map-pill">🐾 Pets ok</div>
        </div>
      </div>
      <div class="map-list">
        <?php renderMapSidebarList($apiError, $listings, $listingMapIndexes); ?>
      </div>
    </div>

    <!-- Map -->
    <div class="map-canvas">
      <div id="google-map" class="google-map"></div>
      <?php if ($googleMapsApiKey === '') { ?>
        <div class="map-key-warning">
          Add GOOGLE_MAPS_API_KEY to your .env file to load the interactive Google map.
        </div>
      <?php } ?>
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
        <a id="map-listing-link" class="popup-view" href="../detail/detail.html" target="_blank" rel="noopener">View listing</a>
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
  <script src="../components/map.js"></script>

  <?php if ($googleMapsApiKey !== '') { ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars(rawurlencode($googleMapsApiKey)); ?>&callback=initLetMeRentMap" async defer onerror="showMapLoadError()"></script>
  <?php } ?>
</body>
</html>
