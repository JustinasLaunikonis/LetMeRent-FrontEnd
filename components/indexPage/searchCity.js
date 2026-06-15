// Lets the user pick a city in the search bar.
var citySearchField = document.getElementById('city-search-field');
var cityCard = document.getElementById('city-card');
var cityDisplay = document.getElementById('city-display');
var cityInput = document.getElementById('city-input');
var cityOptionsBox = document.querySelector('.city-options');

if (citySearchField && cityCard && cityDisplay && cityInput && cityOptionsBox) {
  // Wait a moment after the last keyboard press before asking PDOK
  var citySearchTimer = null;

  // Each search gets a number.
  // When a reply comes back we only show it if it is still the newest search, so slow replies cannot overwrite newer results.
  var citySearchToken = 0;

  function updateCityDisplay() {
    if (cityInput.value === '') {
      cityDisplay.textContent = 'Any city';
    } else {
      cityDisplay.textContent = cityInput.value;
    }
  }

  function showCityCard() {
    var budgetCard = document.getElementById('budget-card');
    if (budgetCard) {
      budgetCard.classList.remove('show');
    }

    var moveInCard = document.getElementById('move-in-card');
    if (moveInCard) {
      moveInCard.classList.remove('show');
    }

    cityCard.classList.add('show');
    runCitySearch();
  }

  function toggleCityCard() {
    if (cityCard.classList.contains('show')) {
      hideCityCard();
    } else {
      showCityCard();
    }
  }

  function hideCityCard() {
    cityCard.classList.remove('show');
  }

  function showCityMessage(message) {
    cityOptionsBox.innerHTML = '';

    var messageElement = document.createElement('p');
    messageElement.className = 'city-loading';
    messageElement.textContent = message;
    cityOptionsBox.appendChild(messageElement);
  }

  // Show the list of matching cities as clickable buttons.
  function renderCityOptions(cityNames) {
    cityOptionsBox.innerHTML = '';

    if (cityNames.length === 0) {
      showCityMessage('No cities found');
      return;
    }

    for (var i = 0; i < cityNames.length; i++) {
      var cityName = cityNames[i];

      var cityButton = document.createElement('button');
      cityButton.className = 'city-option';
      cityButton.type = 'button';
      cityButton.textContent = cityName;

      cityButton.addEventListener('click', function () {
        cityInput.value = this.textContent;
        updateCityDisplay();
        hideCityCard();
      });

      cityOptionsBox.appendChild(cityButton);
    }
  }

  // Look up cities from PDOK for whatever the user has typed so far.
  function runCitySearch() {
    var searchText = cityInput.value.trim();

    // Nothing typed yet: search as if the user typed the letter "a",
    // so the list still shows some cities to pick from.
    if (searchText === '') {
      searchText = 'a';
    }

    showCityMessage('Searching...');

    // Remember which search this is, so an older reply cannot replace a newer one.
    citySearchToken = citySearchToken + 1;
    var thisToken = citySearchToken;

    window.suggestDutchCities(searchText, function (cities) {
      // A newer search has already started, so ignore this older reply.
      if (thisToken !== citySearchToken) {
        return;
      }

      // null means the lookup failed.
      if (cities === null) {
        showCityMessage('Could not load cities');
        return;
      }

      renderCityOptions(cities);
    });
  }

  // Wait a short moment after typing stops before searching.
  function scheduleCitySearch() {
    if (citySearchTimer !== null) {
      clearTimeout(citySearchTimer);
    }

    citySearchTimer = setTimeout(function () {
      runCitySearch();
    }, 250);
  }

  citySearchField.addEventListener('click', function (event) {
    event.stopPropagation();
    toggleCityCard();
  });

  citySearchField.addEventListener('keydown', function (event) {
    if (event.key === 'Enter') {
      event.preventDefault();
      showCityCard();
      cityInput.focus();
    }

    if (event.key === 'Escape') {
      hideCityCard();
    }
  });

  cityCard.addEventListener('click', function (event) {
    event.stopPropagation();
  });

  document.addEventListener('click', function () {
    hideCityCard();
  });

  cityInput.addEventListener('input', function () {
    updateCityDisplay();
    scheduleCitySearch();
  });
}
