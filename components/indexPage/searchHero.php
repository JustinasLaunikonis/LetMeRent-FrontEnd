<!-- Search Hero -->
<div class="hero">
  <div class="hero-inner">
    <h1>Find your room.</h1>
    <h1>Beat the rush.</h1>
    <p>All Dutch student housing in one place. Scored for your profile.</p>

    <form class="search-bar" method="get" action="index.php">
      <?php if (!empty($sources)) { ?>
        <input type="hidden" name="source" value="<?= htmlspecialchars(implode(',', $sources)) ?>">
      <?php } ?>

      <?php if ($sort !== '') { ?>
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
        <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
      <?php } ?>

      <div class="search-field city-search-field" id="city-search-field" tabindex="0">
        <span class="search-field-icon">&#128205;</span>
        <div class="city-field-body">
          <p class="search-field-label">City</p>
          <p class="search-field-val" id="city-display"><?= htmlspecialchars($selectedCityText) ?></p>

          <div class="city-card" id="city-card">
            <label class="city-card-label" for="city-input">City</label>
            <input
              class="city-input"
              id="city-input"
              type="text"
              name="city"
              value="<?= htmlspecialchars($selectedCity) ?>"
              autocomplete="off"
            >
            <div class="city-options">
              <p class="city-loading" id="city-loading">Loading cities...</p>
            </div>
          </div>
        </div>
      </div>

      <div class="search-field budget-search-field" id="budget-search-field" tabindex="0">
        <span class="search-field-icon">&#128182;</span>
        <div class="budget-field-body">
          <p class="search-field-label">Max Budget</p>
          <p class="search-field-val" id="max-budget-display">&euro;<?= htmlspecialchars($selectedMaxBudgetText) ?> / mo</p>

          <div class="budget-card" id="budget-card">
            <label class="budget-card-label" for="max-budget-input">Max budget</label>
            <div class="budget-input-row">
              <span class="budget-currency">&euro;</span>
              <input
                class="budget-number-input"
                id="max-budget-input"
                type="number"
                name="max_price"
                min="0"
                max="5000"
                step="1"
                value="<?= htmlspecialchars((string) $selectedMaxBudget) ?>"
              >
              <span class="budget-period">/ mo</span>
            </div>
            <input
              class="budget-slider"
              id="max-budget-slider"
              type="range"
              min="0"
              max="5000"
              step="50"
              value="<?= htmlspecialchars((string) $selectedMaxBudget) ?>"
              aria-label="Max budget slider"
            >
          </div>
        </div>
      </div>

      <div class="search-field move-in-search-field" id="move-in-search-field" tabindex="0">
        <span class="search-field-icon">&#128197;</span>
        <div class="move-in-field-body">
          <p class="search-field-label">Move-in</p>
          <p class="search-field-val" id="move-in-display"><?= htmlspecialchars($selectedMoveInText) ?></p>

          <div class="move-in-card" id="move-in-card">
            <label class="move-in-card-label" for="move-in-input">Move in by</label>
            <input
              class="move-in-input"
              id="move-in-input"
              type="date"
              name="available_by"
              value="<?= htmlspecialchars($selectedMoveIn) ?>"
            >
            <button class="move-in-clear" id="move-in-clear" type="button">Any date</button>
          </div>
        </div>
      </div>

      <div class="search-field campus-search-field" id="campus-search-field" tabindex="0">
        <span class="search-field-icon">&#127891;</span>
        <div class="campus-field-body">
          <p class="search-field-label">Campus</p>
          <p class="search-field-val" id="campus-display"><?= htmlspecialchars($selectedCampusText) ?></p>

          <div class="campus-card" id="campus-card">
            <label class="campus-card-label" for="campus-search-input">University / Campus</label>
            <input
              class="city-input"
              id="campus-search-input"
              type="text"
              autocomplete="off"
              placeholder="Search your campus"
              value="<?= htmlspecialchars($selectedCampus) ?>"
            >
            <div class="city-options" id="campus-options"></div>

            <label class="campus-card-label" for="campus-distance-slider">Max distance</label>
            <div class="campus-distance-row">
              <span class="campus-distance-name">Within</span>
              <span class="campus-distance-value" id="campus-distance-value">Any distance</span>
            </div>
            <input
              class="campus-distance-slider"
              id="campus-distance-slider"
              type="range"
              min="0"
              max="15"
              step="1"
              value="0"
              aria-label="Maximum distance from campus in kilometres"
            >
          </div>

          <input type="hidden" name="campus" id="campus-name" value="<?= htmlspecialchars($selectedCampus) ?>">
          <input type="hidden" name="campus_lat" id="campus-lat" value="<?= htmlspecialchars($selectedCampusLat) ?>">
          <input type="hidden" name="campus_lng" id="campus-lng" value="<?= htmlspecialchars($selectedCampusLng) ?>">
          <input type="hidden" name="max_distance_km" id="campus-distance" value="<?= htmlspecialchars($selectedDistance) ?>">
        </div>
      </div>

      <button class="search-submit" type="submit">Search</button>
    </form>

    <?php include __DIR__ . '/../filters/sourcePills.php'; ?>
  </div>
</div>
