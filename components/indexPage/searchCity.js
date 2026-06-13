// Loads Dutch cities from Geocoded
var citySearchField = document.getElementById('city-search-field');
var cityCard = document.getElementById('city-card');
var cityDisplay = document.getElementById('city-display');
var cityInput = document.getElementById('city-input');
var cityOptionsBox = document.querySelector('.city-options');

if (citySearchField && cityCard && cityDisplay && cityInput && cityOptionsBox) {
  var allDutchCities = [];
  var citiesLoaded = false;
  var citiesLoading = false;
  var geocodedBaseUrl = 'https://api.geocoded.me';

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
    loadDutchCities();
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

  function getStateCode(state) {
    if (state.iso2) {
      return state.iso2;
    }

    if (state.isoCode) {
      return state.isoCode;
    }

    if (state.code) {
      return state.code;
    }

    if (state.state_code) {
      return state.state_code;
    }

    if (state.stateCode) {
      return state.stateCode;
    }

    return '';
  }

  function getListFromResponse(responseData) {
    if (Array.isArray(responseData)) {
      return responseData;
    }

    if (responseData && Array.isArray(responseData.data)) {
      return responseData.data;
    }

    return [];
  }

  function addCityName(cityNames, city) {
    var cityName = '';

    if (city.name) {
      cityName = city.name;
    }

    if (cityName !== '') {
      cityNames[cityName] = true;
    }
  }

  function showCityMessage(message) {
    cityOptionsBox.innerHTML = '';

    var messageElement = document.createElement('p');
    messageElement.className = 'city-loading';
    messageElement.textContent = message;
    cityOptionsBox.appendChild(messageElement);
  }

  function renderCityOptions() {
    cityOptionsBox.innerHTML = '';

    var searchText = cityInput.value.toLowerCase();
    var shownCount = 0;

    for (var i = 0; i < allDutchCities.length; i++) {
      var cityName = allDutchCities[i];
      var cityNameLower = cityName.toLowerCase();

      if (searchText === '' || cityNameLower.indexOf(searchText) !== -1) {
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
        shownCount = shownCount + 1;
      }
    }

    if (shownCount === 0) {
      showCityMessage('No cities found');
    }
  }

  function saveLoadedCities(cityNames) {
    allDutchCities = Object.keys(cityNames);
    allDutchCities.sort();
    citiesLoaded = true;
    citiesLoading = false;
    renderCityOptions();
  }

  function loadCitiesForStates(states) {
    var cityNames = {};
    var requests = [];

    for (var i = 0; i < states.length; i++) {
      var stateCode = getStateCode(states[i]);

      if (stateCode !== '') {
        var url = geocodedBaseUrl + '/countries/NL/states/' + encodeURIComponent(stateCode) + '/cities?fields=name';

        var request = fetch(url)
          .then(function (response) {
            return response.json();
          })
          .then(function (cityResponse) {
            var cities = getListFromResponse(cityResponse);

            if (Array.isArray(cities)) {
              for (var j = 0; j < cities.length; j++) {
                addCityName(cityNames, cities[j]);
              }
            }
          });

        requests.push(request);
      }
    }

    Promise.all(requests)
      .then(function () {
        saveLoadedCities(cityNames);
      })
      .catch(function () {
        citiesLoading = false;
        showCityMessage('Could not load cities');
      });
  }

  function loadDutchCities() {
    if (citiesLoaded) {
      renderCityOptions();
      return;
    }

    if (citiesLoading) {
      return;
    }

    citiesLoading = true;
    showCityMessage('Loading cities...');

    fetch(geocodedBaseUrl + '/countries/NL/states?fields=iso2,code,state_code,name')
      .then(function (response) {
        return response.json();
      })
      .then(function (stateResponse) {
        var states = getListFromResponse(stateResponse);

        if (Array.isArray(states)) {
          loadCitiesForStates(states);
        } else {
          citiesLoading = false;
          showCityMessage('Could not load cities');
        }
      })
      .catch(function () {
        citiesLoading = false;
        showCityMessage('Could not load cities');
      });
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

    if (citiesLoaded) {
      renderCityOptions();
    }
  });
}
