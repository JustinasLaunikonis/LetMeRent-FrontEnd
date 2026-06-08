<?php include 'components/listings.php'; ?>

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
      <li><a href="map/map.php">Map View</a></li>
      <li><a href="profile/profile.php">My Alerts</a></li>
    </ul>

    <div class="nav-right">
      <div class="nav-bell">🔔</div>
      <a href="profile/profile.php" class="nav-avatar">JL</a>
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
    <?php include 'components/resetFilters.php'; ?>

    <?php include 'components/priceChip.php'; ?>

    <?php include 'components/roomsDropdown.php'; ?>

    <?php include 'components/energyDropdown.php'; ?>

    <?php include 'components/tagFilters.php'; ?>
    
  </div>

  <!-- Results -->
  <div class="results-bar">
    <div class="results-count">
      <strong><?php include 'components/resultsCount.php'; ?></strong>
      listings · Amsterdam · under €950
    </div>
    <div class="results-actions">
      <span class="profile-applied-label">● Your profile applied</span>
      <div class="view-toggle">
        <button class="view-btn active">⊞</button>
        <button class="view-btn" onclick="location.href='map/map.php'">🗺</button>
      </div>
    </div>
  </div>

  <?php include 'components/listingsGrid.php'; ?>

  <?php include 'components/pagination.php'; ?>

  <script src="components/filterSources.js"></script>
  <script src="components/filterRooms.js"></script>
  <script src="components/filterPrice.js"></script>
  <script src="components/filterEnergy.js"></script>
</body>
</html>
