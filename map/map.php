<?php
// Load all listings needed for the map page.
require '../components/map/mapListings.php';

// Load the function that renders one listing in the map sidebar.
require '../components/map/renderMapListItem.php';
?>
<!DOCTYPE html>
<html lang="en" class="map-page">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LetMeRent — Map View</title>
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
      <div class="nav-bell">🔔</div>
      <a href="../profile/profile.php" class="nav-avatar">JL</a>
    </div>
  </nav>

  <div class="map-layout">
    <div class="map-sidebar">
      <div class="map-sidebar-head">
        <div class="map-search">
          <input type="text" value="Amsterdam" placeholder="Search…">
        </div>
        <div class="map-count"><?php echo htmlspecialchars($totalListings); ?> listings <span>· profile applied</span></div>
        <div class="map-filter-pills">
          <div class="map-pill active">All</div>
          <div class="map-pill">≥80% match</div>
          <div class="map-pill">&lt;5 km</div>
          <div class="map-pill">🛋️ Furnished</div>
          <div class="map-pill">🐾 Pets ok</div>
        </div>
      </div>
      <div class="map-list">
        <?php
        // Show an API error if something went wrong while fetching listings.
        if ($apiError) {
          echo '<div class="api-error">API error: ' . htmlspecialchars($apiError) . '</div>';
        } else if (!empty($listings)) {
          // Loop through every listing and print one sidebar item for each listing.
          $index = 0;
          foreach ($listings as $listing) {
            // Make only the first listing selected.
            if ($index == 0) {
              echo renderMapListItem($listing, true);
            } else {
              echo renderMapListItem($listing, false);
            }

            // Increase the counter so the next listing is not treated as the first one.
            $index = $index + 1;
          }
        } else {
          echo '<div class="no-results">No listings found. Try a different source or filter.</div>';
        }
        ?>
      </div>
    </div>

    <!-- Map -->
    <div class="map-canvas">
      <div class="fake-map">

        <!-- Roads -->
        <div class="map-road h road-h-1"></div>
        <div class="map-road h road-h-2"></div>
        <div class="map-road h road-h-3"></div>
        <div class="map-road h road-h-4"></div>
        <div class="map-road v road-v-1"></div>
        <div class="map-road v road-v-2"></div>
        <div class="map-road v road-v-3"></div>
        <div class="map-road v road-v-4"></div>

        <!-- Blocks -->
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

        <!-- Distance rings -->
        <div class="distance-ring ring-outer"></div>
        <div class="distance-ring ring-inner"></div>

        <!-- Campus -->
        <div class="campus-pin">
          <div class="campus-circle">
            <div class="campus-label">NHL Stenden Emmen Campus</div>
            🏛️
          </div>
        </div>

        <!-- Pins -->
        <div class="map-pin pin-pos-1"><div class="pin-bubble pin-dark">€875 · 94%</div></div>
        <div class="map-pin pin-pos-2"><div class="pin-bubble pin-green">€850 · 91%</div></div>
        <div class="map-pin pin-pos-3"><div class="pin-bubble pin-green">€790 · 88%</div></div>
        <div class="map-pin pin-pos-4"><div class="pin-bubble pin-green">€810 · 82%</div></div>
        <div class="map-pin pin-pos-5"><div class="pin-bubble pin-amber">€680 · 76%</div></div>
        <div class="map-pin pin-pos-6"><div class="pin-bubble pin-amber">€920 · 71%</div></div>
        <div class="map-pin pin-pos-7"><div class="pin-bubble pin-amber">€945 · 66%</div></div>
        <div class="map-pin pin-pos-8"><div class="pin-bubble pin-grey">€890 · 42%</div></div>

        <!-- Selected popup -->
        <div class="map-popup">
          <div class="popup-img">🏠</div>
          <div>
            <div class="popup-price">€875<span>/mo</span></div>
            <div class="popup-title">Modern studio with balcony — De Pijp</div>
            <div class="popup-tags">
              <span class="badge-sm hi">94%</span>
              <span class="tag">🚲 14min</span>
              <span class="tag">🛋️ Furn.</span>
              <span class="tag">🐾 Pets</span>
            </div>
          </div>
          <a href="../detail/detail.html" class="popup-view">View</a>
        </div>

        <!-- Legend -->
        <div class="map-legend">
          <div class="legend-title">Match score</div>
          <div class="legend-row"><div class="legend-dot dot-green"></div> 80-100% — great</div>
          <div class="legend-row"><div class="legend-dot dot-amber"></div> 60-79% — ok</div>
          <div class="legend-row"><div class="legend-dot dot-grey"></div> &lt;60% — low</div>
          <div class="legend-row"><div class="legend-dot dot-dark dot-round"></div> Selected</div>
          <div class="legend-row"><div class="legend-ring-dot"></div> 5/8 km rings</div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>

