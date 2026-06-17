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
      const citySelect = document.getElementById('city-select');
      const citySearch = document.getElementById('city-search');
      const cityOptions = document.getElementById('city-options');

      if (!citySelect || !citySearch || !cityOptions) {
        return;
      }

      const selectedCity = citySelect.value || '';
      const fallbackCities = [
        'Amsterdam',
        'Rotterdam',
        'Den Haag',
        'Utrecht',
        'Eindhoven',
        'Emmen',
        'Groningen',
        'Tilburg',
        'Almere',
        'Breda',
        'Nijmegen',
        'Enschede',
        'Haarlem',
        'Arnhem',
        'Amersfoort',
        'Apeldoorn',
        'Leiden',
        'Dordrecht',
        'Zoetermeer',
        'Zwolle',
        'Maastricht'
      ];

      const endpoint = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/free';
      const maxVisibleOptions = 12;

      const naturalSort = (left, right) => left.localeCompare(right, 'nl', { sensitivity: 'base' });
      let cities = fallbackCities;
      let activeOptionIndex = -1;

      const closeOptions = () => {
        cityOptions.classList.remove('show');
        citySearch.setAttribute('aria-expanded', 'false');
        activeOptionIndex = -1;
      };

      const selectCity = (city) => {
        citySearch.value = city;
        citySelect.value = city;
        closeOptions();
        citySelect.dispatchEvent(new Event('change'));
      };

      const renderCityOptions = () => {
        const query = citySearch.value.trim().toLowerCase();
        const selectedValue = citySelect.value;
        const matchingCities = cities
          .filter((city) => query === '' || city.toLowerCase().includes(query))
          .slice(0, maxVisibleOptions);

        cityOptions.replaceChildren();

        if (matchingCities.length === 0) {
          closeOptions();
          return;
        }

        matchingCities.forEach((city, index) => {
          const option = document.createElement('button');
          option.type = 'button';
          option.className = `city-option${city === selectedValue ? ' active' : ''}`;
          option.id = `city-option-${index}`;
          option.setAttribute('role', 'option');
          option.setAttribute('aria-selected', city === selectedValue ? 'true' : 'false');
          option.textContent = city;
          option.addEventListener('mousedown', (event) => {
            event.preventDefault();
            selectCity(city);
          });

          cityOptions.append(option);
        });

        cityOptions.classList.add('show');
        citySearch.setAttribute('aria-expanded', 'true');
      };

      const clearCityIfNotExactMatch = () => {
        const typedCity = citySearch.value.trim();
        const exactCity = cities.find((city) => city.toLowerCase() === typedCity.toLowerCase());

        citySelect.value = exactCity || '';

        if (exactCity !== undefined && citySearch.value !== exactCity) {
          citySearch.value = exactCity;
        }

        citySelect.dispatchEvent(new Event('change'));
      };

      const setCityOptions = (loadedCities) => {
        const uniqueCities = [...new Set(loadedCities.filter(Boolean))].sort(naturalSort);

        if (selectedCity !== '' && !uniqueCities.includes(selectedCity)) {
          uniqueCities.unshift(selectedCity);
        }

        cities = uniqueCities;
        citySearch.setAttribute('aria-busy', 'false');
        clearCityIfNotExactMatch();
        citySelect.dispatchEvent(new Event('change'));
      };

      const fetchCityPage = async (start) => {
        const params = new URLSearchParams({
          q: '*',
          fq: 'type:woonplaats',
          rows: '100',
          start: String(start),
          fl: 'woonplaatsnaam'
        });
        const response = await fetch(`${endpoint}?${params.toString()}`);

        if (!response.ok) {
          throw new Error(`PDOK city request failed with ${response.status}`);
        }

        return response.json();
      };

      const loadCities = async () => {
        const firstPage = await fetchCityPage(0);
        const total = Number(firstPage?.response?.numFound || 0);
        const starts = [];

        for (let start = 100; start < total; start += 100) {
          starts.push(start);
        }

        const pages = await Promise.all(starts.map(fetchCityPage));
        const docs = [firstPage, ...pages].flatMap((page) => page?.response?.docs || []);

        setCityOptions(docs.map((doc) => doc.woonplaatsnaam));
      };

      loadCities().catch(() => {
        setCityOptions(fallbackCities);
      });

      citySearch.addEventListener('input', () => {
        citySelect.value = '';
        citySelect.dispatchEvent(new Event('change'));
        renderCityOptions();
      });

      citySearch.addEventListener('focus', renderCityOptions);

      citySearch.addEventListener('blur', () => {
        clearCityIfNotExactMatch();
        window.setTimeout(closeOptions, 120);
      });

      citySearch.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
          event.preventDefault();
          renderCityOptions();
          const options = [...cityOptions.querySelectorAll('.city-option')];
          activeOptionIndex = Math.min(activeOptionIndex + 1, options.length - 1);
          options.forEach((option, index) => {
            option.classList.toggle('active', index === activeOptionIndex);
          });
        } else if (event.key === 'ArrowUp') {
          event.preventDefault();
          const options = [...cityOptions.querySelectorAll('.city-option')];
          activeOptionIndex = Math.max(activeOptionIndex - 1, 0);
          options.forEach((option, index) => {
            option.classList.toggle('active', index === activeOptionIndex);
          });
        } else if (event.key === 'Enter') {
          const options = [...cityOptions.querySelectorAll('.city-option')];

          if (activeOptionIndex >= 0 && options[activeOptionIndex]) {
            event.preventDefault();
            selectCity(options[activeOptionIndex].textContent);
          }

          return;
        } else if (event.key === 'Escape') {
          closeOptions();
          return;
        } else {
          return;
        }
      });
    })();
