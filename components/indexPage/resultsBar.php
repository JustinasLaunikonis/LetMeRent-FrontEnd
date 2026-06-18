<?php
// Carry the filters back to the map view
if (!isset($mapHref)) {
    $mapQuery = $_GET;
    unset($mapQuery['page']);
    $mapHref = 'map/map.php';
    if (!empty($mapQuery)) {
        $mapHref .= '?' . http_build_query($mapQuery);
    }
}

?>
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

    listings &middot; <?= htmlspecialchars($selectedCityText) ?> &middot;

    <?php
    if ($selectedMaxBudget >= 5000) {
      echo "budget &euro;5000+";
    } else {
      echo "under &euro;" . htmlspecialchars((string) $selectedMaxBudget);
    }
    ?>

  </div>
  <div class="results-actions">
    <?php if (!empty($hasProfileFilters)) { ?>
      <a class="apply-profile-btn" href="<?= htmlspecialchars($applyProfileHref, ENT_QUOTES) ?>">Apply profile settings</a>
    <?php } ?>
    <div class="view-toggle">
      <button class="view-btn active">&#8862;</button>
      <button class="view-btn" onclick="location.href='<?= htmlspecialchars($mapHref, ENT_QUOTES) ?>'">&#128506;</button>
    </div>
  </div>
</div>
