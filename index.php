<?php
include 'components/listings/listings.php';
include 'components/indexPage/searchValues.php';

// Carry the filters back to the map view
$mapQuery = $_GET;
unset($mapQuery['page']);
$mapHref = 'map/map.php';
if (!empty($mapQuery)) {
    $mapHref .= '?' . http_build_query($mapQuery);
}
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
  <?php
  // Top navbar. <Browse> is the current page. Map link keeps the filters.
  $navActive = 'browse';
  $navMapHref = $mapHref;
  include 'includes/nav.php';
  ?>

  <?php include 'components/indexPage/searchHero.php'; ?>
  <?php include 'components/indexPage/filterBar.php'; ?>
  <?php include 'components/indexPage/resultsBar.php'; ?>
  <?php include 'components/listings/listingsGrid.php'; ?>
  <?php include 'components/listings/pagination.php'; ?>

  <script src="components/filters/filterDropdowns.js"></script>
  <script src="components/filters/filterSources.js"></script>
  <script src="components/indexPage/cityGeoData.js"></script>
  <script src="components/indexPage/searchFields.js"></script>
  <script src="components/indexPage/searchCity.js"></script>
  <script src="components/indexPage/searchBudget.js"></script>
  <script src="components/indexPage/searchMoveIn.js"></script>
</body>
</html>
