<?php

// Start the login session first, before anything is printed to the page.
// The navbar reads this session to show the avatar or a "Sign in" button.
require_once __DIR__ . '/../sign-up-in/authConfig.php';
startAuthSession();

// Detail page: look up one listing by its id and show it.
require __DIR__ . '/detailHelpers.php';

// id of listing comes from the URL (?id=...).
if (isset($_GET['id'])) {
    $listingId = trim((string) $_GET['id']);
} else {
    $listingId = '';
}

$pageError = null;
$lookupError = null;

if ($listingId === '') {
    $pageError = 'No listing ID provided.';
    $listing = [];
} else {
    $listing = findListingById($listingId);
    if ($listing === []) {
        if ($lookupError !== null) {
            $pageError = $lookupError;
        } else {
            $pageError = 'Listing not found for ID ' . $listingId . '.';
        }
    }
}

// Build all the values the template below shows
require __DIR__ . '/detailData.php';
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
  <?php
  // Top navbar. nothing to highlight while on details page.
  $navBase = '../';
  include __DIR__ . '/../includes/nav.php';
  ?>

  <div class="breadcrumb">
    <a href="../index.php">Back to results</a>
    <span class="sep">&gt;</span>
    <span class="cur"><?php echo esc($listingTitle); ?></span>
  </div>

  <?php
    if ($pageError !== null) {
      echo '<div class="detail-alert">' . esc($pageError) . '</div>';
    }
  ?>

  <div class="detail-wrap">

    <!-- Main content -->
    <div>
      <!-- Fallback images -->
      <?php
        if ($galleryMain === '') {
          echo 'div class="esc($galleryClass)">';
          echo '<div class="gallery-main">';
          echo '<svg viewBox="0 0 460 376" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">';
          echo '<rect width="460" height="376" fill="#B8C8D8"/>';
          echo '<rect x="0" y="210" width="460" height="166" fill="#A8B8C8"/>';
          echo '<rect x="50" y="58" width="360" height="260" fill="#8FA3B5" rx="2"/>';
          echo '<rect x="78" y="86" width="100" height="98" fill="#7A90A5" rx="1"/>';
          echo '<rect x="282" y="86" width="100" height="98" fill="#7A90A5" rx="1"/>';
          echo '<rect x="172" y="184" width="116" height="134" fill="#6A8095" rx="2"/>';
          echo '<path d="M172 184 Q230 156 288 184" stroke="#6A8095" stroke-width="3" fill="none"/>';
          echo '<rect x="50" y="316" width="360" height="8" rx="2" fill="#6A8095" opacity="0.5"/>';
          echo '<rect x="0" y="0" width="460" height="74" fill="#BFDBFE"/>';
          echo '<ellipse cx="36" cy="362" rx="36" ry="20" fill="#8AAE70" opacity="0.6"/>';
          echo '</svg>';
          echo '</div>';
          echo '</div>';
        } else if ($galleryCount === 1) {
          echo '<div class="' . esc($galleryClass) . '">';
          echo '<div class="gallery-main">';
          echo '<img src="' . esc($galleryMain) . '" alt="' . esc($listingTitle) . '">';
          echo '</div>';
          echo '</div>';
        } else if ($galleryCount === 2) {
          echo '<div class="' . esc($galleryClass) . ' gallery--clickable" data-gallery-open>';
          echo '<div class="gallery-main">';
          echo '<img src="' . esc($galleryImages[0]) . '" alt="' . esc($listingTitle) . '">';
          echo '</div>';
          echo '<div class="gallery-side">';
          echo '<div class="gallery-cell">';
          echo '<img src="' . esc($galleryImages[1]) . '" alt="' . esc($listingTitle) . '">';
          echo '</div>';
          echo '</div>';
          echo '</div>';
        } else {
          echo '<div class="' . esc($galleryClass) . ' gallery--clickable" data-gallery-open>';
          echo '<div class="gallery-main">';
          echo '<img src="' . esc($galleryImages[0]) . '" alt="' . esc($listingTitle) . '">';
          echo '</div>';
          echo '<div class="gallery-side">';

          for ($i = 1; $i < $galleryCount; $i++) {
            echo '<div class="gallery-cell">';
            echo '<img src="' . esc($galleryImages[$i]) . '" alt="' . esc($listingTitle) . '">';
            echo '</div>';
          }

          if ($totalImageCount > 3) {
            echo '<button type="button" class="gallery-count" data-gallery-open>Show All</button>';
          }

          echo '</div>';
          echo '</div>';
        }
      ?>

      <?php
        if ($totalImageCount > 1){
          echo '<div class="gallery-modal" data-gallery-modal hidden>';
          echo '<div class="gallery-modal-backdrop" data-gallery-close></div>';
          echo '<button type="button" class="gallery-modal-close" data-gallery-close aria-label="Close gallery">&times;</button>';
          echo '<div class="gallery-modal-content">';

          foreach ($listingImages as $image){
            echo '<img src="' . esc($image) . '" alt="' . esc($listingTitle) . '">';
          }

          echo '</div>';
          echo '</div>';
        }
      ?>

      <div class="listing-title-row">
        <div class="listing-title">
          <p><?php echo esc($listingTitle); ?></p>
          <p><?php echo esc($listingTitleLocation); ?></p>
        </div>

        <div class="listing-price"><?php echo $listingPrice; ?> <span>/ mo</span></div>
      </div>

      <div class="listing-location">
        <p>&#128205; <?php echo esc($listingLocationAddress); ?> &#183;</p>
        <a href="<?php echo esc($sourceLink); ?>"<?php echo $sourceLinkTarget; ?>><?php echo esc($listingSource); ?></a>
        <p>&#183; <?php echo esc($listedText); ?></p>
      </div>

      <div class="chip-row">
        <?php
          if ($chips === []) {
            echo '<div class="chip"><p>No listing tags available</p></div>';
          } else {
            foreach ($chips as $chip){
              echo '<div class="chip"><p>' . esc($chip) . '</p></div>';
            }
          }
        ?>
      </div>

      <hr class="section-divider">

      <?php
        if ($listingDescription !== ''){
          echo '<div class="section-title">Description</div>';
          echo '<p class="desc-text">' . esc($listingDescription) . '</p>';

          echo '<hr class="section-divider">';
        }
      ?>

      <!-- Location & details -->
      <div class="section-title">Location &amp; Details</div>
      <div class="map-box detail-google-map-box">
        <div id="detail-google-map" class="google-map detail-google-map"></div>
        <?php
          if ($detailMapApiKey === ''){
            echo '<div class="map-key-warning">Add GOOGLE_MAPS_API_KEY to your .env file to load the Google map.</div>';
          } else {
            echo '<div class="map-key-warning" hidden>Google Maps could not be loaded. Check your internet connection and API key.</div>';
          }
        ?>
      </div>

      <table class="commute-table">
        <tr>
          <td>Location</td>
          <td><?php echo esc($detailMapLocationText); ?></td>
        </tr>
        <tr>
          <td>Source</td>
          <td><?php echo esc($listingSource); ?></td>
        </tr>
        <tr>
          <td>Scraped</td>
          <td><?php echo esc($commuteListed); ?></td>
        </tr>
      </table>
    </div>

    <!-- Sidebar -->
    <div>
      <div class="sidebar-card">
        <div class="section-title">Listing Info</div>
        <div class="sidebar-facts">
          <?php
            foreach ($sidebarFacts as $fact){
              echo '<div class="sidebar-fact">';
              echo '<span>' . esc($fact['label']) . '</span>';
              echo '<strong>' . esc($fact['value']) . '</strong>';
              echo '</div>';
            }
          ?>
        </div>

        <?php
          if ($listingUrl !== ''){
            echo '<a class="apply-btn" href="' . esc($listingUrl) . '" target="_blank" rel="noopener noreferrer">Browse ' . esc($listingSource) . '</a>';
          } else {
            echo '<button class="apply-btn" disabled>Browse ' . esc($listingSource) . '</button>';
          }
        ?>
      </div>

      <div class="sidebar-card">
        <div class="section-title">Rental Site</div>
        <div class="landlord-row">
          <div class="landlord-avatar"><?php echo esc($landlordInitial); ?></div>
          <div>
            <div class="landlord-name"><?php echo esc($landlordName); ?></div>
            <div class="landlord-meta"><?php echo esc($listingSource); ?> &#183; <?php echo esc($landlordCityText); ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    window.letMeRentDetailMapConfig = {
      hasCoordinates: <?php echo $detailMapHasCoordinates ? 'true' : 'false'; ?>,
      latitude: <?php echo json_encode($detailMapLatitude); ?>,
      longitude: <?php echo json_encode($detailMapLongitude); ?>,
      zoom: <?php echo json_encode($detailMapZoom); ?>,
      title: <?php echo json_encode($listingTitle); ?>
    };
  </script>

  <script src="detail.js"></script>
  <?php
    if ($detailMapApiKey !== ''){
      echo '<script src="https://maps.googleapis.com/maps/api/js?key=' . htmlspecialchars(rawurlencode($detailMapApiKey)) . '&callback=initLetMeRentDetailMap" async defer onerror="showDetailMapLoadError()"></script>';
    }
  ?>
</body>
</html>
