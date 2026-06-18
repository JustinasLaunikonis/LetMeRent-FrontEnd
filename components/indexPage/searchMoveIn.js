// Lets the user pick a move-in date in the search bar. The open/close behaviour comes from registerSearchField() (searchFields.js)
// this file adds the date display and the "Any date" clear button.
var moveInSearchField = document.getElementById('move-in-search-field');
var moveInCard = document.getElementById('move-in-card');
var moveInDisplay = document.getElementById('move-in-display');
var moveInInput = document.getElementById('move-in-input');
var moveInClear = document.getElementById('move-in-clear');

if (moveInSearchField && moveInCard && moveInDisplay && moveInInput && moveInClear) {
  // Filled in once the field is registered below.s
  var moveInField = null;

  function updateMoveInDisplay() {
    if (moveInInput.value === '') {
      moveInDisplay.textContent = 'Any date';
    } else {
      moveInDisplay.textContent = moveInInput.value;
    }
  }

  moveInField = registerSearchField({
    fieldId: 'move-in-search-field',
    cardId: 'move-in-card',
    inputId: 'move-in-input'
  });

  moveInInput.addEventListener('input', function () {
    updateMoveInDisplay();
  });

  // The "Any date" button clears the picked date so the filter is turned off.
  moveInClear.addEventListener('click', function () {
    moveInInput.value = '';
    updateMoveInDisplay();
    if (moveInField) {
      moveInField.hide();
    }
  });
}
