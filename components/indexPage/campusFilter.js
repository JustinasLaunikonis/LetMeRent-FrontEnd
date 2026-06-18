// The "Campus" field in the search bar.

// The user picks a campus (the list depends on the chosen city, just like the profile page) and then a maximum distance.

(function () {
  var campusField = document.getElementById('campus-search-field');
  var campusCard = document.getElementById('campus-card');
  var campusDisplay = document.getElementById('campus-display');
  var campusSearchInput = document.getElementById('campus-search-input');
  var campusOptions = document.getElementById('campus-options');
  var distanceSlider = document.getElementById('campus-distance-slider');
  var distanceValue = document.getElementById('campus-distance-value');

  var campusNameHidden = document.getElementById('campus-name');
  var campusLatHidden = document.getElementById('campus-lat');
  var campusLngHidden = document.getElementById('campus-lng');
  var distanceHidden = document.getElementById('campus-distance');

  var cityInput = document.getElementById('city-input');

  // If anything is missing, or the campus data helper did not load, do nothing.
  if (!campusField || !campusCard || !campusSearchInput || !campusOptions || !distanceSlider || !distanceValue) {
    return;
  }
  if (!campusNameHidden || !campusLatHidden || !campusLngHidden || !distanceHidden) {
    return;
  }
  if (!window.LetMeRentCampus) {
    return;
  }

  // Open/close behaviour, the same as the other search-bar fields.
  var field = registerSearchField({
    fieldId: 'campus-search-field',
    cardId: 'campus-card',
    inputId: 'campus-search-input',
    onOpen: function () {
      buildOptions(campusSearchInput.value);
    }
  });

  function selectedCityValue() {
    if (cityInput) {
      return cityInput.value;
    }
    return '';
  }

  // The text shown on the closed field.
  function updateDisplay() {
    var name = campusNameHidden.value;
    var distance = distanceHidden.value;

    if (name !== '' && distance !== '') {
      campusDisplay.textContent = distance + ' km from campus';
    } else if (name !== '') {
      campusDisplay.textContent = 'Pick a distance';
    } else {
      campusDisplay.textContent = 'Any campus';
    }
  }

  // The distance slider only works once a campus has been chosen.
  function refreshDistanceState() {
    if (campusNameHidden.value === '') {
      distanceSlider.disabled = true;
    } else {
      distanceSlider.disabled = false;
    }
  }

  // Show the slider value as text. 0 means "no distance limit".
  function updateDistanceLabel() {
    var km = Number(distanceSlider.value);
    if (km > 0) {
      distanceValue.textContent = km + ' km';
    } else {
      distanceValue.textContent = 'Any distance';
    }
  }

  function showMessage(message) {
    campusOptions.innerHTML = '';
    var line = document.createElement('p');
    line.className = 'city-loading';
    line.textContent = message;
    campusOptions.appendChild(line);
  }

  // What we show for a campus. When no city is picked we add the city name so the user can tell similar campuses apart.
  function campusLabel(campus) {
    if (selectedCityValue().trim() === '' && campus.city !== '') {
      return campus.name + ' - ' + campus.city;
    }
    return campus.name;
  }

  // Save the chosen campus into the hidden fields.
  function chooseCampus(campus) {
    campusNameHidden.value = campus.name;

    if (typeof campus.lat === 'number' && typeof campus.lng === 'number') {
      campusLatHidden.value = campus.lat;
      campusLngHidden.value = campus.lng;
    } else {
      // No coordinates for this campus, so the distance filter cannot run.
      campusLatHidden.value = '';
      campusLngHidden.value = '';
    }

    campusSearchInput.value = campusLabel(campus);
    refreshDistanceState();
    updateDisplay();

    if (field) {
      field.hide();
    }
  }

  // Build the clickable list of campuses for the current city + typed text.
  function buildOptions(typedText) {
    var matches = window.LetMeRentCampus.forCity(selectedCityValue());
    var typed = (typedText || '').trim().toLowerCase();

    if (typed !== '') {
      var filtered = [];
      for (var i = 0; i < matches.length; i++) {
        var nameMatch = matches[i].name.toLowerCase().indexOf(typed) !== -1;
        var cityMatch = matches[i].city.toLowerCase().indexOf(typed) !== -1;
        if (nameMatch || cityMatch) {
          filtered.push(matches[i]);
        }
      }
      matches = filtered;
    }

    campusOptions.innerHTML = '';

    if (matches.length === 0) {
      showMessage('No campuses found');
      return;
    }

    for (var j = 0; j < matches.length; j++) {
      var campus = matches[j];
      var button = document.createElement('button');
      button.className = 'city-option';
      button.type = 'button';
      button.textContent = campusLabel(campus);

      // A small helper keeps each button pointing at its own campus.
      button.addEventListener('click', makeChooseHandler(campus));

      campusOptions.appendChild(button);
    }
  }

  function makeChooseHandler(campus) {
    return function () {
      chooseCampus(campus);
    };
  }

  // When the city changes, drop a saved campus that no longer fits that city.
  function onCityMaybeChanged() {
    if (campusNameHidden.value !== '') {
      var stillValid = window.LetMeRentCampus.findByName(campusNameHidden.value, selectedCityValue());
      if (stillValid === null) {
        campusNameHidden.value = '';
        campusLatHidden.value = '';
        campusLngHidden.value = '';
        campusSearchInput.value = '';
        distanceHidden.value = '';
        distanceSlider.value = 0;
        updateDistanceLabel();
        refreshDistanceState();
        updateDisplay();
      }
    }

    if (campusCard.classList.contains('show')) {
      buildOptions(campusSearchInput.value);
    }
  }

  campusSearchInput.addEventListener('input', function () {
    buildOptions(campusSearchInput.value);
  });

  distanceSlider.addEventListener('input', function () {
    var km = Number(distanceSlider.value);
    if (km > 0) {
      distanceHidden.value = km;
    } else {
      // 0 on the slider means "no distance limit", so clear the value.
      distanceHidden.value = '';
    }
    updateDistanceLabel();
    updateDisplay();
  });

  if (cityInput) {
    cityInput.addEventListener('input', onCityMaybeChanged);
  }

  // Set up the starting state from whatever was already in the URL.
  if (distanceHidden.value !== '') {
    distanceSlider.value = distanceHidden.value;
  } else {
    distanceSlider.value = 0;
  }
  updateDistanceLabel();
  refreshDistanceState();
  updateDisplay();

  // Load the campus list (the built-in list first, then OpenAlex adds more).
  window.LetMeRentCampus.load(function () {
    // If a campus was already chosen but has no coordinates yet, fill them in
    // now that more campus data is available.
    if (campusNameHidden.value !== '' && campusLatHidden.value === '') {
      var found = window.LetMeRentCampus.findByName(campusNameHidden.value, selectedCityValue());
      if (found !== null && typeof found.lat === 'number' && typeof found.lng === 'number') {
        campusLatHidden.value = found.lat;
        campusLngHidden.value = found.lng;
      }
    }

    if (campusCard.classList.contains('show')) {
      buildOptions(campusSearchInput.value);
    }
  });
})();
