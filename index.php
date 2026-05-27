<?php
require 'components/listings.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LetMeRent — Browse Listings</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="index.css">
</head>

<body>
  <!-- Nav Bar -->
  <nav class="nav">
    <a class="nav-logo" href="index.php">
      <div class="logo-icon">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
          <path d="M2 8L9 2L16 8V16H11V12H7V16H2V8Z" fill="white"/>
        </svg>
      </div>
      <p>LetMeRent</p>
    </a>

    <ul class="nav-links">
      <li><a href="index.php" class="active">Browse</a></li>
      <li><a href="map/map.html">Map View</a></li>
      <li><a href="profile/profile.html">My Alerts</a></li>
    </ul>

    <div class="nav-right">
      <div class="nav-bell">🔔</div>
      <a href="profile/profile.html" class="nav-avatar">JL</a>
    </div>
  </nav>

  <!-- Search Hero -->
  <div class="hero">
    <div class="hero-inner">
      <h1>Find your room.</h1>
      <h1>Beat the rush.</h1>
      <p>All Dutch student housing in one place. Scored for your profile.</p>

      <div class="search-bar">
        <div class="search-field">
          <span class="search-field-icon">📍</span>
          <div>
            <p class="search-field-label">City</p>
            <p class="search-field-val">Amsterdam</p>
          </div>
        </div>
        <div class="search-field">
          <span class="search-field-icon">💶</span>
          <div>
            <p class="search-field-label">Max Budget</p>
            <p class="search-field-val">€950 / mo</p>
          </div>
        </div>
        <div class="search-field">
          <span class="search-field-icon">📅</span>
          <div>
            <p class="search-field-label">Move-in</p>
            <p class="search-field-val">Sep 1, 2025</p>
          </div>
        </div>
        <div class="search-field">
          <span class="search-field-icon">🚲</span>
          <div>
            <p class="search-field-label">Max from campus</p>
            <p class="search-field-val">8 km</p>
          </div>
        </div>
        <button class="search-submit">Search</button>
      </div>

      <div class="source-pills">
        <div class="source-pill" data-source="HousingAnywhere">HousingAnywhere</div>
        <div class="source-pill" data-source="Funda">Funda</div>
        <div class="source-pill" data-source="Kamernet">Kamernet</div>
        <div class="source-pill" data-source="Huurwoningen">Huurwoningen</div>
        <div class="source-pill" data-source="iRentalize">iRentalize</div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="filter-bar">
    <div class="filter-chip active">
      <p>All</p>
      <span class="chev">▼</span>
    </div>
    <div class="filter-chip">
      <p>Price</p>
      <span class="chev">▼</span>
    </div>
    <div class="filter-chip">
      <p>Rooms</p>
      <span class="chev">▼</span>
    </div>
    <div class="filter-chip active">
      <p>🐾 Pet-friendly</p>
    </div>
    <div class="filter-chip">
      <p>🛋️ Furnished</p>
    </div>
    <div class="filter-chip">
      <p>📅 Available now</p>
    </div>
    <div class="filter-chip">
      <p>🏫 &lt;3 km campus</p>
    </div>
    <div class="filter-sort">
      <p>Sort:</p>
      <strong>Best match ▼</strong>
    </div>
  </div>

  <!-- Results -->
  <div class="results-bar">
    <div class="results-count">
      <strong>
      <?php
        if ($totalListings > 0) {
          echo $totalListings;
        } else {
          echo 0;
        }
      ?>
      </strong>
      listings · Amsterdam · under €950
    </div>
    <div class="results-actions">
      <span class="profile-applied-label">● Your profile applied</span>
      <div class="view-toggle">
        <button class="view-btn active">⊞</button>
        <button class="view-btn" onclick="location.href='map/map.html'">🗺</button>
      </div>
    </div>
  </div>

  <!-- Listings -->
  <?php
  if ($apiError) {
    echo '<div class="api-error">API error: ' . htmlspecialchars($apiError) . '</div>';
  }

  echo '<div class="listings-grid">';

  if (!empty($listings)) {
    foreach ($listings as $listing) {
      echo renderCard($listing);
    }
  } else {
    echo '<div class="no-results">No listings found. Try a different source or filter.</div>';
  }

  echo '</div>';
  ?>
  
  <script src="components/filterSources.js"></script>
</body>
</html>
