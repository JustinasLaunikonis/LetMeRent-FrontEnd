<!-- Search Hero -->
<div class="hero">
  <div class="hero-inner">
    <h1>Find your room.</h1>
    <h1>Beat the rush.</h1>
    <p>All Dutch student housing in one place. Scored for your profile.</p>

    <form class="search-bar" method="get" action="index.php">
      <input type="hidden" name="city" value="Amsterdam">

      <?php if (!empty($sources)) { ?>
        <input type="hidden" name="source" value="<?= htmlspecialchars(implode(',', $sources)) ?>">
      <?php } ?>

      <?php if ($sort !== '') { ?>
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
        <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
      <?php } ?>

      <div class="search-field">
        <span class="search-field-icon">&#128205;</span>
        <div>
          <p class="search-field-label">City</p>
          <p class="search-field-val">Amsterdam</p>
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
                step="50"
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

      <div class="search-field">
        <span class="search-field-icon">&#128197;</span>
        <div>
          <p class="search-field-label">Move-in</p>
          <p class="search-field-val">Sep 1, 2025</p>
        </div>
      </div>

      <div class="search-field">
        <span class="search-field-icon">&#128690;</span>
        <div>
          <p class="search-field-label">Max from campus</p>
          <p class="search-field-val">8 km</p>
        </div>
      </div>

      <button class="search-submit" type="submit">Search</button>
    </form>

    <div class="source-pills">
      <div class="source-pill" data-source="HousingAnywhere">HousingAnywhere</div>
      <div class="source-pill" data-source="Funda">Funda</div>
      <div class="source-pill" data-source="Kamernet">Kamernet</div>
      <div class="source-pill" data-source="Huurwoningen">Huurwoningen</div>
      <div class="source-pill" data-source="iRentalize">iRentalize</div>
    </div>
  </div>
</div>
