<?php
include 'components/listings/listings.php';
include 'components/indexPage/cityValues.php';
include 'components/indexPage/budgetValues.php';
include 'components/indexPage/moveInValues.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LetMeRent - Browse Listings</title>
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
      <div class="nav-bell">&#128276;</div>
      <a href="profile/profile.php" class="nav-avatar">JL</a>
    </div>
  </nav>

  <?php include 'components/indexPage/searchHero.php'; ?>
  <?php include 'components/indexPage/filterBar.php'; ?>
  <?php include 'components/indexPage/resultsBar.php'; ?>
  <?php include 'components/listings/listingsGrid.php'; ?>
  <?php include 'components/listings/pagination.php'; ?>

  <script src="components/filters/filterDropdowns.js"></script>
  <script src="components/filters/filterSources.js"></script>
  <script src="components/filters/filterRooms.js"></script>
  <script src="components/filters/filterPrice.js"></script>
  <script src="components/filters/filterEnergy.js"></script>
  <script src="components/indexPage/cityGeoData.js"></script>
  <script src="components/indexPage/searchCity.js"></script>
  <script src="components/indexPage/searchBudget.js"></script>
  <script src="components/indexPage/searchMoveIn.js"></script>
</body>
</html>
