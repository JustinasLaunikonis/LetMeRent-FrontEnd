// Profile page scripts:
//   1) the custom "distance from campus" drag slider,
//   2) the source multi-select dropdown label,
//   3) the city and university autocompletes.
//   4) the budget min/max number inputs and sliders, which are linked together
//   5) the fade out of the "profile updated" message after a few seconds.
//   6) the reset button, which puts every filter back to its default.

// ---------------------------------------------------------------------------
// 1) Distance-from-campus slider
// ---------------------------------------------------------------------------
(function () {
  const distanceSlider = document.getElementById('distance-slider');
  const distanceValue = document.getElementById('distance-value');
  const distanceInput = document.getElementById('distance-input');

  if (!distanceSlider || !distanceValue || !distanceInput) {
    return;
  }

  const maxDistance = parseInt(distanceSlider.max, 10);

  // Show the sliders current value next to the label, e.g. "8 km".
  // At the very top the distance is treated as unlimited: show "20+ km" and submit an empty value so no distance filter is applied.
  function updateDistanceValue() {
    const distance = parseInt(distanceSlider.value, 10);

    if (distance >= maxDistance) {
      distanceValue.textContent = maxDistance + '+ km';
      distanceInput.value = '';
    } else {
      distanceValue.textContent = distance + ' km';
      distanceInput.value = distance;
    }
  }

  distanceSlider.addEventListener('input', updateDistanceValue);

  // Make sure the label matches the value the page loaded with.
  updateDistanceValue();
})();

// ---------------------------------------------------------------------------
// 2) + 3) Source dropdown label, and city / campus autocompletes
// ---------------------------------------------------------------------------
    (() => {
      const dropdown = document.querySelector('.source-dropdown');

      if (!dropdown) {
        return;
      }

      const label = dropdown.querySelector('.source-dropdown-label');
      const inputs = [...dropdown.querySelectorAll('input[type="checkbox"]')];
      const emptyLabel = label?.dataset.emptyLabel || 'Any source';

      const updateLabel = () => {
        const selectedCount = inputs.filter((input) => input.checked).length;

        // When nothing is selected, or every source is selected, show the "Any source" label.
        // Otherwise show a simple count, for example "3/5 sources selected", instead of listing every source name.
        if (selectedCount === 0 || selectedCount === inputs.length) {
          label.textContent = emptyLabel;
        } else {
          label.textContent = selectedCount + '/' + inputs.length + ' sources selected';
        }
      };

      inputs.forEach((input) => {
        input.addEventListener('change', updateLabel);
      });

      document.addEventListener('click', (event) => {
        if (!dropdown.contains(event.target)) {
          dropdown.open = false;
        }
      });

      updateLabel();
    })();

    (() => {
      const campusHidden = document.getElementById('campus-select');
      const campusSearch = document.getElementById('campus-search');
      const campusOptions = document.getElementById('campus-options');
      const citySelect = document.getElementById('city-select');

      if (!campusHidden || !campusSearch || !campusOptions || !citySelect) {
        return;
      }

      const fallbackCampuses = [
        { name: 'Amsterdam University of Applied Sciences', city: 'Amsterdam' },
        { name: 'ArtEZ University of the Arts', city: 'Arnhem' },
        { name: 'Avans University of Applied Sciences', city: 'Breda' },
        { name: 'Breda University of Applied Sciences', city: 'Breda' },
        { name: 'Codarts Rotterdam', city: 'Rotterdam' },
        { name: 'Delft University of Technology', city: 'Delft' },
        { name: 'Design Academy Eindhoven', city: 'Eindhoven' },
        { name: 'Eindhoven University of Technology', city: 'Eindhoven' },
        { name: 'Erasmus University Rotterdam', city: 'Rotterdam' },
        { name: 'Fontys University of Applied Sciences', city: 'Eindhoven' },
        { name: 'Gerrit Rietveld Academie', city: 'Amsterdam' },
        { name: 'Hanze University of Applied Sciences', city: 'Groningen' },
        { name: 'Hotelschool The Hague', city: 'Den Haag' },
        { name: 'Inholland University of Applied Sciences', city: 'Amsterdam' },
        { name: 'Leiden University', city: 'Leiden' },
        { name: 'Maastricht University', city: 'Maastricht' },
        { name: 'NHL Stenden University of Applied Sciences - Emmen', city: 'Emmen' },
        { name: 'NHL Stenden University of Applied Sciences', city: 'Leeuwarden' },
        { name: 'Radboud University', city: 'Nijmegen' },
        { name: 'Rotterdam University of Applied Sciences', city: 'Rotterdam' },
        { name: 'Saxion University of Applied Sciences', city: 'Enschede' },
        { name: 'The Hague University of Applied Sciences', city: 'Den Haag' },
        { name: 'Tilburg University', city: 'Tilburg' },
        { name: 'University of Amsterdam', city: 'Amsterdam' },
        { name: 'University of Groningen', city: 'Groningen' },
        { name: 'University of Twente', city: 'Enschede' },
        { name: 'Utrecht University', city: 'Utrecht' },
        { name: 'Vrije Universiteit Amsterdam', city: 'Amsterdam' },
        { name: 'Wageningen University & Research', city: 'Wageningen' },
        { name: 'Windesheim University of Applied Sciences', city: 'Zwolle' },
        { name: 'Zuyd University of Applied Sciences', city: 'Maastricht' }
      ];
      const cityAliases = new Map([
        ['the hague', 'den haag'],
        ['den haag', 'den haag'],
        ["'s-gravenhage", 'den haag'],
        ['s-gravenhage', 'den haag'],
        ['s gravenhage', 'den haag']
      ]);
      const campusEndpoint = 'https://api.openalex.org/institutions?filter=country_code:NL,type:education&per-page=200&select=display_name,geo';

      let campuses = fallbackCampuses;

      const normalizeCity = (city) => {
        const normalized = city
          .toLowerCase()
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .replace(/\s+/g, ' ')
          .trim();

        return cityAliases.get(normalized) || normalized;
      };

      const selectedCityValue = () => citySelect.value || '';

      const uniqueCampuses = (items) => {
        const campusMap = new Map();

        items.forEach((item) => {
          const name = (item.name || '').trim();
          const city = (item.city || '').trim();

          if (name === '') {
            return;
          }

          campusMap.set(`${name.toLowerCase()}|${city.toLowerCase()}`, { name, city });
        });

        return [...campusMap.values()].sort((left, right) => {
          const citySort = left.city.localeCompare(right.city, 'nl', { sensitivity: 'base' });

          return citySort !== 0 ? citySort : left.name.localeCompare(right.name, 'nl', { sensitivity: 'base' });
        });
      };

      // Campuses that match the chosen city (or all of them when no city is set).
      const campusesForCity = () => {
        const selectedCity = selectedCityValue();
        const selectedCityKey = normalizeCity(selectedCity);

        return uniqueCampuses(campuses).filter((campus) => {
          return selectedCity === '' || normalizeCity(campus.city) === selectedCityKey;
        });
      };

      // What we show for a campus. When no city is picked we add the city name so the user can tell similar campuses apart.
      const campusLabel = (campus) => {
        if (selectedCityValue() === '' && campus.city !== '') {
          return `${campus.name} - ${campus.city}`;
        }

        return campus.name;
      };

      // Find a campus object by its stored name.
      const findCampus = (name) => {
        const list = campusesForCity();

        for (let i = 0; i < list.length; i++) {
          if (list[i].name === name) {
            return list[i];
          }
        }

        return null;
      };

      const closeCampusOptions = () => {
        campusOptions.classList.remove('show');
        campusSearch.setAttribute('aria-expanded', 'false');
      };

      // Show a single line of text in the options box (e.g. "No universities found").
      const showCampusMessage = (message) => {
        campusOptions.replaceChildren();

        const messageElement = document.createElement('p');
        messageElement.className = 'city-loading';
        messageElement.textContent = message;
        campusOptions.append(messageElement);

        campusOptions.classList.add('show');
        campusSearch.setAttribute('aria-expanded', 'true');
      };

      // Put the chosen campus in the hidden (submitted) field and the visible box.
      const selectCampus = (campus) => {
        campusHidden.value = campus.name;
        campusSearch.value = campusLabel(campus);
        closeCampusOptions();
      };

      // Build the styled list of campus options (same look as the city dropdown).
      const buildCampusOptions = (matches) => {
        campusOptions.replaceChildren();

        if (matches.length === 0) {
          showCampusMessage('No universities found');
          return;
        }

        matches.forEach((campus) => {
          const option = document.createElement('button');
          option.type = 'button';
          option.className = 'city-option';
          option.setAttribute('role', 'option');
          option.textContent = campusLabel(campus);
          // mousedown (not click) so the choice happens before the input blurs.
          option.addEventListener('mousedown', (event) => {
            event.preventDefault();
            selectCampus(campus);
          });

          campusOptions.append(option);
        });

        campusOptions.classList.add('show');
        campusSearch.setAttribute('aria-expanded', 'true');
      };

      // Show every campus for the current city (used when the box is focused).
      const openCampusOptions = () => {
        buildCampusOptions(campusesForCity());
      };

      // Narrow the list down to whatever the user has typed.
      const filterCampusOptions = () => {
        const typed = campusSearch.value.trim().toLowerCase();
        const matches = campusesForCity().filter((campus) => {
          if (typed === '') {
            return true;
          }

          const nameMatch = campus.name.toLowerCase().indexOf(typed) !== -1;
          const cityMatch = campus.city.toLowerCase().indexOf(typed) !== -1;
          return nameMatch || cityMatch;
        });

        buildCampusOptions(matches);
      };

      // Make the visible box show the label of the campus that is stored.
      const syncCampusDisplay = () => {
        if (campusHidden.value === '') {
          campusSearch.value = '';
          return;
        }

        const campus = findCampus(campusHidden.value);
        if (campus === null) {
          // Not in the current (city-filtered) list, but still show the saved name.
          campusSearch.value = campusHidden.value;
        } else {
          campusSearch.value = campusLabel(campus);
        }
      };

      // When the city changes, drop a saved campus that no longer fits that city.
      const onCityChange = () => {
        if (campusHidden.value !== '') {
          const stillValid = findCampus(campusHidden.value) !== null;
          if (!stillValid && selectedCityValue() !== '') {
            campusHidden.value = '';
          }
        }

        syncCampusDisplay();

        if (campusOptions.classList.contains('show')) {
          filterCampusOptions();
        }
      };

      const loadCampuses = async () => {
        const response = await fetch(campusEndpoint);

        if (!response.ok) {
          throw new Error(`OpenAlex campus request failed with ${response.status}`);
        }

        const data = await response.json();
        const apiCampuses = (data.results || []).map((institution) => ({
          name: institution.display_name || '',
          city: institution.geo?.city || ''
        }));

        campuses = uniqueCampuses([...fallbackCampuses, ...apiCampuses]);
        campusSearch.setAttribute('aria-busy', 'false');
        syncCampusDisplay();

        if (campusOptions.classList.contains('show')) {
          filterCampusOptions();
        }
      };

      campusSearch.addEventListener('focus', openCampusOptions);
      campusSearch.addEventListener('input', filterCampusOptions);
      campusSearch.addEventListener('blur', () => {
        // Small delay so a click on an option is handled before we close.
        window.setTimeout(() => {
          closeCampusOptions();
          syncCampusDisplay();
        }, 120);
      });

      citySelect.addEventListener('change', onCityChange);
      syncCampusDisplay();
      loadCampuses().catch(() => {
        campusSearch.setAttribute('aria-busy', 'false');
      });
    })();

    (() => {
      // City autocomplete. This works the same way as the browse page (index.php)
      const citySelect = document.getElementById('city-select');
      const citySearch = document.getElementById('city-search');
      const cityOptions = document.getElementById('city-options');

      if (!citySelect || !citySearch || !cityOptions) {
        return;
      }

      // Wait a short moment after the last keypress before asking PDOK.
      let citySearchTimer = null;

      // Each search gets a number. When a reply comes back we only use it if it is
      // still the newest search, so a slow reply cannot overwrite newer results.
      let citySearchToken = 0;

      // Keep the hidden field (the one that is submitted, and the one the
      // University / Campus dropdown reads) in step with the visible box,
      // then tell the campus dropdown to refresh.
      const syncCitySelect = () => {
        citySelect.value = citySearch.value.trim();
        citySelect.dispatchEvent(new Event('change'));
      };

      const closeOptions = () => {
        cityOptions.classList.remove('show');
        citySearch.setAttribute('aria-expanded', 'false');
      };

      // Show a single line of text in the options box (e.g. "Searching...").
      const showCityMessage = (message) => {
        cityOptions.replaceChildren();

        const messageElement = document.createElement('p');
        messageElement.className = 'city-loading';
        messageElement.textContent = message;
        cityOptions.append(messageElement);

        cityOptions.classList.add('show');
        citySearch.setAttribute('aria-expanded', 'true');
      };

      const selectCity = (city) => {
        citySearch.value = city;
        syncCitySelect();
        closeOptions();
      };

      // Show the list of matching cities as clickable buttons.
      const renderCityOptions = (cityNames) => {
        cityOptions.replaceChildren();

        if (cityNames.length === 0) {
          showCityMessage('No cities found');
          return;
        }

        cityNames.forEach((city) => {
          const option = document.createElement('button');
          option.type = 'button';
          option.className = 'city-option';
          option.setAttribute('role', 'option');
          option.textContent = city;
          // mousedown (not click) so the choice happens before the input blurs.
          option.addEventListener('mousedown', (event) => {
            event.preventDefault();
            selectCity(city);
          });

          cityOptions.append(option);
        });

        cityOptions.classList.add('show');
        citySearch.setAttribute('aria-expanded', 'true');
      };

      // Look up cities from PDOK for whatever the user has typed so far.
      const runCitySearch = () => {
        let searchText = citySearch.value.trim();

        // Nothing typed yet: search as if the user typed "a", so the list still
        // shows some cities to pick from.
        if (searchText === '') {
          searchText = 'a';
        }

        showCityMessage('Searching...');

        // Remember which search this is, so an older reply cannot replace a newer one.
        citySearchToken = citySearchToken + 1;
        const thisToken = citySearchToken;

        window.suggestDutchCities(searchText, (cities) => {
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
      };

      // Wait a short moment after typing stops before searching.
      const scheduleCitySearch = () => {
        if (citySearchTimer !== null) {
          clearTimeout(citySearchTimer);
        }

        citySearchTimer = setTimeout(runCitySearch, 250);
      };

      citySearch.addEventListener('input', () => {
        // Whatever is typed is what gets submitted, just like the browse page.
        syncCitySelect();
        scheduleCitySearch();
      });

      citySearch.addEventListener('focus', runCitySearch);

      citySearch.addEventListener('blur', () => {
        window.setTimeout(closeOptions, 120);
      });

      citySearch.setAttribute('aria-busy', 'false');

      // If the page loaded with a saved city, make sure the campus list matches it.
      syncCitySelect();
    })();

// ---------------------------------------------------------------------------
// 4) Budget number input + slider sync (min and max budget)
// ---------------------------------------------------------------------------
(function () {
  // Connect one number input to its matching range slider.
  //
  // config.edge is the slider end that means "no limit":
  //   'max' (budget max) - the top of the range, 5000, means "no upper limit".
  //   'min' (budget min) - the bottom of the range, 0, means "no lower limit".
  // At that edge an empty value is submitted (via config.submitId), just like
  // the distance slider. config.clearBox decides what the box shows there:
  //   true  - clear the box so its placeholder ("5000+") shows.
  //   false - keep showing the edge number ("0").
  function linkBudgetField(config) {
    const numberInput = document.getElementById(config.inputId);
    const slider = document.getElementById(config.sliderId);
    const submitInput = document.getElementById(config.submitId);

    if (!numberInput || !slider || !submitInput) {
      return;
    }

    const minBudget = parseInt(slider.min, 10);
    const maxBudget = parseInt(slider.max, 10);
    const edgeValue = config.edge === 'max' ? maxBudget : minBudget;

    // Keep a typed value inside the allowed range.
    function clamp(value) {
      if (value < minBudget) {
        return minBudget;
      }
      if (value > maxBudget) {
        return maxBudget;
      }
      return value;
    }

    function isUnlimited(value) {
      if (config.edge === 'max') {
        return value >= maxBudget;
      }
      return value <= minBudget;
    }

    // Move everything (slider, box, submitted value) to one number.
    function apply(value) {
      const safe = clamp(value);
      slider.value = safe;

      if (isUnlimited(safe)) {
        // No limit on this end: submit an empty value.
        submitInput.value = '';
        if (config.clearBox) {
          numberInput.value = '';
        } else {
          numberInput.value = safe;
        }
      } else {
        submitInput.value = safe;
        numberInput.value = safe;
      }
    }

    // Dragging the slider updates the box and the submitted value.
    slider.addEventListener('input', function () {
      apply(parseInt(slider.value, 10));
    });

    // Typing in the box moves the slider along with it.
    numberInput.addEventListener('input', function () {
      if (numberInput.value === '') {
        slider.value = edgeValue;
        submitInput.value = '';
        return;
      }

      const typed = parseInt(numberInput.value, 10);
      if (isNaN(typed)) {
        return;
      }

      const safe = clamp(typed);
      slider.value = safe;

      if (isUnlimited(safe)) {
        submitInput.value = '';
        if (config.clearBox) {
          numberInput.value = '';
        } else {
          numberInput.value = safe;
        }
      } else {
        submitInput.value = safe;
        numberInput.value = safe;
      }
    });

    numberInput.addEventListener('change', function () {
      if (numberInput.value === '') {
        apply(edgeValue);
        return;
      }

      const typed = parseInt(numberInput.value, 10);
      if (isNaN(typed)) {
        apply(parseInt(slider.value, 10));
        return;
      }

      apply(typed);
    });
  }

  // Min: 0 means "no lower limit". Show "0" in the box, submit empty.
  linkBudgetField({
    inputId: 'min-budget-input',
    sliderId: 'min-budget-slider',
    submitId: 'min-budget-hidden',
    edge: 'min',
    clearBox: false
  });

  // Max: 5000 means "no upper limit".
  // Clear the box so "5000+" placeholder shows, and submit empty
  linkBudgetField({
    inputId: 'max-budget-input',
    sliderId: 'max-budget-slider',
    submitId: 'max-budget-input',
    edge: 'max',
    clearBox: true
  });
})();

// ---------------------------------------------------------------------------
// 5) Fade out the "preferences saved" / error banner after a few seconds.
// ---------------------------------------------------------------------------
(function () {
  const message = document.querySelector('.preference-message');

  if (!message) {
    return;
  }

  // Leave it on screen for a few seconds so the user can read it.
  window.setTimeout(function () {
    // The CSS transition handles the fade; the class just starts it.
    message.classList.add('preference-message--hide');

    // Once the fade has finished, take it out of the page entirely.
    window.setTimeout(function () {
      message.remove();
    }, 400);
  }, 4000);
})();

// ---------------------------------------------------------------------------
// 6) Reset button: put every filter back to its default (empty / no filter).
// ---------------------------------------------------------------------------
(function () {
  const resetButton = document.getElementById('reset-preferences');

  if (!resetButton) {
    return;
  }

  // Fire an event so the script that owns a control updates its display.
  function fire(element, type) {
    element.dispatchEvent(new Event(type, { bubbles: true }));
  }

  resetButton.addEventListener('click', function () {
    // Plain dropdowns go back to their empty option.
    const selectNames = ['pet_friendly', 'min_lease_length', 'room_type', 'furnishing'];
    for (let i = 0; i < selectNames.length; i++) {
      const select = document.querySelector('[name="' + selectNames[i] + '"]');
      if (select) {
        select.value = '';
      }
    }

    // Move-in date: clear it.
    const moveInDate = document.querySelector('[name="move_in_date"]');
    if (moveInDate) {
      moveInDate.value = '';
    }

    // Sources: untick all, then refresh the "Any source" label.
    const sources = document.querySelectorAll('[name="spider[]"]');
    for (let i = 0; i < sources.length; i++) {
      sources[i].checked = false;
    }
    if (sources.length > 0) {
      fire(sources[0], 'change');
    }

    // City: clear the box and the submitted value, then refresh the campus list.
    const citySearch = document.getElementById('city-search');
    const citySelect = document.getElementById('city-select');
    if (citySearch) {
      citySearch.value = '';
    }
    if (citySelect) {
      citySelect.value = '';
      fire(citySelect, 'change');
    }

    // University / campus: clear the box and the submitted value.
    // We do this after the city change above so it cannot refill the box.
    const campusSearch = document.getElementById('campus-search');
    const campusHidden = document.getElementById('campus-select');
    if (campusSearch) {
      campusSearch.value = '';
    }
    if (campusHidden) {
      campusHidden.value = '';
    }

    // Min budget: slider to the bottom (no lower limit).
    // The sliders own listener then fills the box with "0" and submits an empty value.
    const minSlider = document.getElementById('min-budget-slider');
    if (minSlider) {
      minSlider.value = minSlider.min;
      fire(minSlider, 'input');
    }

    // Max budget: slider to the top (no upper limit, shows "5000+").
    const maxSlider = document.getElementById('max-budget-slider');
    if (maxSlider) {
      maxSlider.value = maxSlider.max;
      fire(maxSlider, 'input');
    }

    // Distance: slider to the top (unlimited, shows "20+ km").
    const distanceSlider = document.getElementById('distance-slider');
    if (distanceSlider) {
      distanceSlider.value = distanceSlider.max;
      fire(distanceSlider, 'input');
    }

    // Instant alerts toggle: back on, which is its default.
    const instantAlerts = document.querySelector('.toggle input[type="checkbox"]');
    if (instantAlerts) {
      instantAlerts.checked = true;
    }
  });
})();
