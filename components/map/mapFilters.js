// helpers for the map sidebar filters.
// 1) Open and close the "Filters" panel (the list of filter options).
// 2) Keep the Max Budget number box and slider showing the same value.
// 3) Let the "Any date" button clear the move-in date.

// ---------------------------------------------------------------------------
// 1) Open and close the Filters panel
// ---------------------------------------------------------------------------
var filtersToggle = document.getElementById('map-filters-toggle');
var filtersPanel = document.getElementById('map-filters-panel');
var filtersChev = document.getElementById('map-filters-chev');

if (filtersToggle && filtersPanel) {
  filtersToggle.addEventListener('click', function () {
    // If the panel is open, close it. If it is closed, open it.
    if (filtersPanel.classList.contains('open')) {
      filtersPanel.classList.remove('open');
      if (filtersChev) {
        filtersChev.classList.remove('open');
      }
    } else {
      filtersPanel.classList.add('open');
      if (filtersChev) {
        filtersChev.classList.add('open');
      }
    }
  });
}

// ---------------------------------------------------------------------------
// 2) Keep the budget number box and slider in sync
// ---------------------------------------------------------------------------
var budgetInput = document.getElementById('map-budget-input');
var budgetSlider = document.getElementById('map-budget-slider');

if (budgetInput && budgetSlider) {
  var minBudget = parseInt(budgetSlider.min, 10);
  var maxBudget = parseInt(budgetSlider.max, 10);
  var budgetPlus = document.getElementById('map-budget-plus');

  // Make the box only as wide as the number inside it, so the "+" can be next to the amount instead of far away
  function fitBudgetWidth() {
    var length = budgetInput.value.length;
    if (length < 1) {
      length = 1;
    }

    budgetInput.style.width = length + 'ch';
  }

  // Show a "+" after the number when the budget is at the maximum, so it says "5000 +"
  function updateBudgetPlus() {
    if (!budgetPlus) {
      return;
    }

    var currentBudget = parseInt(budgetSlider.value, 10);
    if (!isNaN(currentBudget) && currentBudget >= maxBudget) {
      budgetPlus.textContent = '+';
    } else {
      budgetPlus.textContent = '';
    }
  }

  // Moving the slider updates the number box.
  budgetSlider.addEventListener('input', function () {
    budgetInput.value = budgetSlider.value;
    fitBudgetWidth();
    updateBudgetPlus();
  });

  // Typing a number moves the slider, but only when the number is valid.
  budgetInput.addEventListener('input', function () {
    // Resize the box to whatever has been typed so far.
    fitBudgetWidth();

    if (budgetInput.value === '') {
      return;
    }

    var typedBudget = parseInt(budgetInput.value, 10);

    if (isNaN(typedBudget)) {
      return;
    }

    if (typedBudget < minBudget) {
      typedBudget = minBudget;
    }

    if (typedBudget > maxBudget) {
      typedBudget = maxBudget;
    }

    budgetSlider.value = typedBudget;
    updateBudgetPlus();
  });

  // Set the box width and the "+" correctly when the page first loads.
  fitBudgetWidth();
  updateBudgetPlus();
}

// ---------------------------------------------------------------------------
// 3) "Any date" clears the move-in date
// ---------------------------------------------------------------------------
var moveInInput = document.getElementById('map-movein-input');
var moveInClear = document.getElementById('map-movein-clear');

if (moveInInput && moveInClear) {
  moveInClear.addEventListener('click', function () {
    moveInInput.value = '';
  });
}
