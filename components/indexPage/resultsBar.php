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

// Build a link that removes the campus distance filter but keeps everything else.
$clearDistanceQuery = $_GET;
unset($clearDistanceQuery['campus']);
unset($clearDistanceQuery['campus_lat']);
unset($clearDistanceQuery['campus_lng']);
unset($clearDistanceQuery['max_distance_km']);
unset($clearDistanceQuery['page']);
$clearDistanceHref = 'index.php';
if (!empty($clearDistanceQuery)) {
    $clearDistanceHref .= '?' . http_build_query($clearDistanceQuery);
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

    <?php if ($selectedMoveIn !== '') { ?>
      &middot; move-in by <?= htmlspecialchars($selectedMoveInText) ?>
    <?php } ?>

    <?php if (!empty($distanceFilterActive)) { ?>
      <a class="filter-chip active" href="<?= htmlspecialchars($clearDistanceHref, ENT_QUOTES) ?>">
        Within <?= htmlspecialchars($selectedDistance) ?> km of <?= htmlspecialchars($selectedCampus) ?>
        <span class="filter-chip-x">&times;</span>
      </a>
    <?php } ?>

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
