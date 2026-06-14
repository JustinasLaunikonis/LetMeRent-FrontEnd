// City autocomplete for the map sidebar search box.
var mapCityInput = document.getElementById('map-city-input');
var mapCityOptions = document.getElementById('map-city-options');

if (mapCityInput && mapCityOptions) {
  // Wait a moment after the last keyboard press before asking PDOK
  var mapCitySearchTimer = null;

  // Each search gets a number.
  // When a reply comes back we only show it if it is still the newest search, so slow replies cannot overwrite newer results.
  var mapCitySearchToken = 0;

  function showMapCityOptions() {
    mapCityOptions.classList.add('show');
  }

  function hideMapCityOptions() {
    mapCityOptions.classList.remove('show');
  }

  function syncFiltersCity() {
    var hiddenCity = document.getElementById('map-filters-city');
    if (hiddenCity) {
      hiddenCity.value = mapCityInput.value;
    }
  }

  // Show the cities that match what the user typed as clickable buttons.
  function renderMapCityOptions(cityNames) {
    mapCityOptions.innerHTML = '';

    if (cityNames.length === 0) {
      hideMapCityOptions();
      return;
    }

    for (var i = 0; i < cityNames.length; i++) {
      var cityName = cityNames[i];

      var cityButton = document.createElement('button');
      cityButton.className = 'map-city-option';
      cityButton.type = 'button';
      cityButton.textContent = cityName;

      // Clicking a suggestion fills the box. The user then clicks Search.
      cityButton.addEventListener('click', function () {
        mapCityInput.value = this.textContent;
        syncFiltersCity();
        hideMapCityOptions();
      });

      mapCityOptions.appendChild(cityButton);
    }

    showMapCityOptions();
  }

  // Look up cities from PDOK for whatever the user has typed so far.
  function runMapCitySearch() {
    var searchText = mapCityInput.value.trim();

    // Dont show anything until the user has typed something.
    if (searchText === '') {
      hideMapCityOptions();
      return;
    }

    // Remember which search this is, so an older reply cannot replace a newer one.
    mapCitySearchToken = mapCitySearchToken + 1;
    var thisToken = mapCitySearchToken;

    window.suggestDutchCities(searchText, function (cities) {
      // A newer search has already started, so ignore this older reply.
      if (thisToken !== mapCitySearchToken) {
        return;
      }

      // null means the lookup failed; just hide the list.
      if (cities === null) {
        hideMapCityOptions();
        return;
      }

      renderMapCityOptions(cities);
    });
  }

  // Wait a short moment after typing stops before searching.
  function scheduleMapCitySearch() {
    if (mapCitySearchTimer !== null) {
      clearTimeout(mapCitySearchTimer);
    }

    mapCitySearchTimer = setTimeout(function () {
      runMapCitySearch();
    }, 250);
  }

  // Update the suggestions while the user types
  mapCityInput.addEventListener('input', function () {
    syncFiltersCity();
    scheduleMapCitySearch();
  });

  // Clicking back into the box shows the matches again.
  mapCityInput.addEventListener('click', function (event) {
    event.stopPropagation();
    runMapCitySearch();
  });

  // Clicking anywhere else hides the suggestions.
  document.addEventListener('click', function (event) {
    if (event.target !== mapCityInput) {
      hideMapCityOptions();
    }
  });
}
