// Keeps the Max Budget number input and slider showing the same value.
// The open/close behaviour comes from registerSearchField() (searchFields.js).
var budgetSearchField = document.getElementById('budget-search-field');
var budgetCard = document.getElementById('budget-card');
var maxBudgetDisplay = document.getElementById('max-budget-display');
var maxBudgetInput = document.getElementById('max-budget-input');
var maxBudgetSlider = document.getElementById('max-budget-slider');

if (budgetSearchField && budgetCard && maxBudgetDisplay && maxBudgetInput && maxBudgetSlider) {
  var minBudget = parseInt(maxBudgetSlider.min, 10);
  var maxBudget = parseInt(maxBudgetSlider.max, 10);

  function updateBudgetDisplay() {
    var displayBudget = maxBudgetInput.value;
    var typedBudget = parseInt(maxBudgetInput.value, 10);

    if (!isNaN(typedBudget)) {
      if (typedBudget >= maxBudget) {
        displayBudget = maxBudget + '+';
      }
    }

    maxBudgetDisplay.innerHTML = '&euro;' + displayBudget + ' / mo';
  }

  // Open/close behaviour for the budget card.
  registerSearchField({
    fieldId: 'budget-search-field',
    cardId: 'budget-card',
    inputId: 'max-budget-input'
  });

  maxBudgetSlider.addEventListener('input', function () {
    maxBudgetInput.value = maxBudgetSlider.value;
    updateBudgetDisplay();
  });

  maxBudgetInput.addEventListener('input', function () {
    if (maxBudgetInput.value === '') {
      return;
    }

    var typedBudget = parseInt(maxBudgetInput.value, 10);

    if (isNaN(typedBudget)) {
      return;
    }

    if (typedBudget < minBudget) {
      typedBudget = minBudget;
    }

    if (typedBudget > maxBudget) {
      typedBudget = maxBudget;
    }

    maxBudgetInput.value = typedBudget;
    maxBudgetSlider.value = typedBudget;
    updateBudgetDisplay();
  });

  maxBudgetInput.addEventListener('change', function () {
    if (maxBudgetInput.value === '') {
      maxBudgetInput.value = maxBudgetSlider.value;
      updateBudgetDisplay();
      return;
    }

    var typedBudget = parseInt(maxBudgetInput.value, 10);

    if (isNaN(typedBudget)) {
      maxBudgetInput.value = maxBudgetSlider.value;
      updateBudgetDisplay();
      return;
    }

    if (typedBudget < minBudget) {
      typedBudget = minBudget;
    }

    if (typedBudget > maxBudget) {
      typedBudget = maxBudget;
    }

    maxBudgetInput.value = typedBudget;
    maxBudgetSlider.value = typedBudget;
    updateBudgetDisplay();
  });
}
