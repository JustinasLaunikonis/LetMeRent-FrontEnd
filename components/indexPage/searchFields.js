// Shared open/close behaviour for the search-bar fields (City, Max Budget, Move-in).
// Each field is a clickable box that opens a small card. Opening one card closes the others, and clicking outside closes them.

// Each fields own script calls registerSearchField() and then adds its own field-specific logic (city autocomplete, budget slider, etc.).

// Every card registered here, so opening one can close the rest.
var searchCards = [];

function registerSearchField(options) {
  var field = document.getElementById(options.fieldId);
  var card = document.getElementById(options.cardId);

  if (!field || !card) {
    return null;
  }

  var input = null;
  if (options.inputId) {
    input = document.getElementById(options.inputId);
  }

  // Optional function to run each time the card opens.
  var onOpen = options.onOpen || null;

  searchCards.push(card);

  function hideCard() {
    card.classList.remove('show');
  }

  function showCard() {
    // Close every other search card first, so only one is open at a time.
    for (var i = 0; i < searchCards.length; i++) {
      if (searchCards[i] !== card) {
        searchCards[i].classList.remove('show');
      }
    }

    card.classList.add('show');

    if (onOpen) {
      onOpen();
    }
  }

  function toggleCard() {
    if (card.classList.contains('show')) {
      hideCard();
    } else {
      showCard();
    }
  }

  field.addEventListener('click', function (event) {
    event.stopPropagation();
    toggleCard();
  });

  field.addEventListener('keydown', function (event) {
    if (event.key === 'Enter') {
      event.preventDefault();
      showCard();
      if (input) {
        input.focus();
      }
    }

    if (event.key === 'Escape') {
      hideCard();
    }
  });

  // Clicking inside the card should not close it.
  card.addEventListener('click', function (event) {
    event.stopPropagation();
  });

  // Clicking anywhere else on the page closes the card.
  document.addEventListener('click', function () {
    hideCard();
  });

  // Give the fields own script a way to show or hide its card.
  return {
    field: field,
    card: card,
    input: input,
    show: showCard,
    hide: hideCard,
    toggle: toggleCard
  };
}
