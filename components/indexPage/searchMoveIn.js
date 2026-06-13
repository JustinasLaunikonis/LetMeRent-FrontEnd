// Lets the user pick a move-in date in the search bar.
// Works like the City and Max Budget fields: clicking the field opens a small
// card with a date picker, and the chosen date is shown on the field.
var moveInSearchField = document.getElementById('move-in-search-field');
var moveInCard = document.getElementById('move-in-card');
var moveInDisplay = document.getElementById('move-in-display');
var moveInInput = document.getElementById('move-in-input');
var moveInClear = document.getElementById('move-in-clear');

if (moveInSearchField && moveInCard && moveInDisplay && moveInInput && moveInClear) {
  function updateMoveInDisplay() {
    if (moveInInput.value === '') {
      moveInDisplay.textContent = 'Any date';
    } else {
      moveInDisplay.textContent = moveInInput.value;
    }
  }

  function showMoveInCard() {
    // Close the other search cards so only one is open at a time.
    var cityCard = document.getElementById('city-card');
    if (cityCard) {
      cityCard.classList.remove('show');
    }

    var budgetCard = document.getElementById('budget-card');
    if (budgetCard) {
      budgetCard.classList.remove('show');
    }

    moveInCard.classList.add('show');
  }

  function hideMoveInCard() {
    moveInCard.classList.remove('show');
  }

  function toggleMoveInCard() {
    if (moveInCard.classList.contains('show')) {
      hideMoveInCard();
    } else {
      showMoveInCard();
    }
  }

  moveInSearchField.addEventListener('click', function (event) {
    event.stopPropagation();
    toggleMoveInCard();
  });

  moveInSearchField.addEventListener('keydown', function (event) {
    if (event.key === 'Enter') {
      event.preventDefault();
      showMoveInCard();
      moveInInput.focus();
    }

    if (event.key === 'Escape') {
      hideMoveInCard();
    }
  });

  moveInCard.addEventListener('click', function (event) {
    event.stopPropagation();
  });

  document.addEventListener('click', function () {
    hideMoveInCard();
  });

  moveInInput.addEventListener('input', function () {
    updateMoveInDisplay();
  });

  // The "Any date" button clears the picked date so the filter is turned off.
  moveInClear.addEventListener('click', function () {
    moveInInput.value = '';
    updateMoveInDisplay();
    hideMoveInCard();
  });
}
