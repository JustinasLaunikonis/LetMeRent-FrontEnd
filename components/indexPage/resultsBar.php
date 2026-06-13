<!-- Results -->
<div class="results-bar">
  <div class="results-count">
    <strong><?php include 'components/listings/resultsCount.php'; ?></strong>

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

  </div>
  <div class="results-actions">
    <span class="profile-applied-label">&#9679; Your profile applied</span>
    <div class="view-toggle">
      <button class="view-btn active">&#8862;</button>
      <button class="view-btn" onclick="location.href='map/map.php'">&#128506;</button>
    </div>
  </div>
</div>
