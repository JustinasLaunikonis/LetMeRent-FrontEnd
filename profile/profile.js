// Profile page scripts:
//   1) the custom "distance from campus" drag slider,
//   2) the source multi-select dropdown label,
//   3) the city and university autocompletes.

// ---------------------------------------------------------------------------
// 1) Distance-from-campus slider
// ---------------------------------------------------------------------------
(function () {
  const sliderGroup = document.querySelector('.slider-group');
  if (!sliderGroup) {
    return;
  }

  const sliderTrack = sliderGroup.querySelector('.slider-track');
  const sliderFill = sliderGroup.querySelector('.slider-fill');
  const sliderThumb = sliderGroup.querySelector('.slider-thumb');
  const sliderValue = sliderGroup.querySelector('.slider-value');
  const sliderInput = sliderGroup.querySelector('.slider-input');

  let isDragging = false;

  const min = 0;
  const max = 20;

  function setSliderPosition(percent) {
    percent = Math.max(0, Math.min(1, percent));
    const value = Math.round(min + percent * (max - min));
    sliderFill.style.width = `${percent * 100}%`;
    sliderThumb.style.left = `${percent * 100}%`;
    sliderValue.textContent = `${value} km`;
    sliderInput.value = value;
  }

  function clearSliderPosition() {
    sliderFill.style.width = '0%';
    sliderThumb.style.left = '0%';
    sliderValue.textContent = '';
    sliderInput.value = '';
  }

  function getPercentFromEvent(e) {
    const rect = sliderTrack.getBoundingClientRect();
    const x = e.clientX !== undefined ? e.clientX : e.touches[0].clientX;
    return (x - rect.left) / rect.width;
  }

  sliderThumb.addEventListener('mousedown', () => {
    isDragging = true;
  });
  document.addEventListener('mouseup', () => {
    isDragging = false;
  });
  document.addEventListener('mousemove', (e) => {
    if (isDragging) {
      setSliderPosition(getPercentFromEvent(e));
    }
  });
  sliderTrack.addEventListener('click', (e) => {
    setSliderPosition(getPercentFromEvent(e));
  });
  sliderThumb.addEventListener('touchstart', () => {
    isDragging = true;
  });
  document.addEventListener('touchend', () => {
    isDragging = false;
  });
  document.addEventListener('touchmove', (e) => {
    if (isDragging) {
      setSliderPosition(getPercentFromEvent(e));
    }
  });

  // Start from the value PHP put in the hidden input (empty = no distance set).
  if (sliderInput.value === '') {
    clearSliderPosition();
  } else {
    setSliderPosition(parseFloat(sliderInput.value) / 20);
  }
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
        const selectedLabels = inputs
          .filter((input) => input.checked)
          .map((input) => input.closest('label')?.textContent.trim())
          .filter(Boolean);

        label.textContent = selectedLabels.length > 0 ? selectedLabels.join(', ') : emptyLabel;
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
      const campusSelect = document.getElementById('campus-select');
      const citySelect = document.getElementById('city-select');
      const citySearch = document.getElementById('city-search');

      if (!campusSelect || !citySelect) {
        return;
      }

      const selectedCampus = campusSelect.dataset.selectedCampus || '';
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

      const setCampusOptions = () => {
        const selectedCity = selectedCityValue();
        const selectedCityKey = normalizeCity(selectedCity);
        const filteredCampuses = uniqueCampuses(campuses).filter((campus) => {
          return selectedCity === '' || normalizeCity(campus.city) === selectedCityKey;
        });
        let activeCampus = campusSelect.value || selectedCampus;
        const hasActiveCampus = filteredCampuses.some((campus) => campus.name === activeCampus);

        if (activeCampus !== '' && !hasActiveCampus) {
          if (selectedCity === '') {
            filteredCampuses.unshift({ name: activeCampus, city: '' });
          } else {
            activeCampus = '';
          }
        }

        campusSelect.replaceChildren(new Option('', '', activeCampus === '', activeCampus === ''));

        filteredCampuses.forEach((campus) => {
          const label = selectedCity === '' && campus.city !== '' ? `${campus.name} - ${campus.city}` : campus.name;

          campusSelect.add(new Option(label, campus.name, false, campus.name === activeCampus));
        });

        campusSelect.setAttribute('aria-busy', 'false');
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
        setCampusOptions();
      };

      citySelect.addEventListener('change', setCampusOptions);
      setCampusOptions();
      loadCampuses().catch(setCampusOptions);
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
