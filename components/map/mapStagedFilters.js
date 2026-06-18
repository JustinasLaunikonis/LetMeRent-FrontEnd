// Map page: dont apply filters until "Apply filters" is clicked.

// On the front page the source pills, the price/rooms/energy dropdowns and the
// tag chips reload the page the moment they are clicked.
// On the map page we do NOT want that. The user should be able to pick several filters and only see them take effect after pressing "Apply filters".
// The city is the one exception: it has its own search form with its own button.

var stagedPanel = document.getElementById('map-filters-panel');
var stagedForm = document.querySelector('.map-filters-form');

if (stagedPanel && stagedForm) {

  // Clicking "Apply filters" reloads the page.
  if (sessionStorage.getItem('mapFiltersCollapsed') === '1') {
    sessionStorage.removeItem('mapFiltersCollapsed');
    stagedPanel.classList.remove('open');

    var loadedChev = document.getElementById('map-filters-chev');
    if (loadedChev) {
      loadedChev.classList.remove('open');
    }
  }

  var currentParams = new URLSearchParams(location.search);
  currentParams.delete('page');

  var stagedParams = new URLSearchParams(location.search);
  stagedParams.delete('page');

  // -------------------------------------------------------------------------
  // Small helpers
  // -------------------------------------------------------------------------

  // Read a comma list (like "furnished,plot_size")
  function readCommaList(params, name) {
    var value = params.get(name);
    if (value === null) {
      value = '';
    }

    var parts = value.split(',');
    var list = [];
    for (var i = 0; i < parts.length; i++) {
      var part = parts[i].trim();
      if (part !== '') {
        list.push(part);
      }
    }
    return list;
  }

  // The base label shown on a dropdown button when its filter is cleared.
  function baseDropdownLabel(dropdownId) {
    if (dropdownId === 'price-dropdown') {
      return 'Price';
    }
    if (dropdownId === 'rooms-dropdown') {
      return 'Rooms';
    }
    if (dropdownId === 'energy-dropdown') {
      return 'Energy';
    }
    return '';
  }

  function dropdownOwnedKeys(dropdownId) {
    if (dropdownId === 'price-dropdown') {
      return ['sort', 'order'];
    }
    if (dropdownId === 'rooms-dropdown') {
      return ['min_rooms', 'max_rooms'];
    }
    if (dropdownId === 'energy-dropdown') {
      return ['energy_label'];
    }
    return [];
  }

  // -------------------------------------------------------------------------
  // Source pills (Funda, Kamernet, ...). highlight the ones already chosen
  // and toggle them on/off without reloading. (On the map page we do NOT load
  // filterSources.js, which would reload the page, so  handle the pills here.)
  // -------------------------------------------------------------------------
  var stagedPills = stagedPanel.querySelectorAll('.source-pill');

  // Read the staged sources as a lower-case list.
  function readStagedSources() {
    var list = readCommaList(stagedParams, 'source');
    for (var i = 0; i < list.length; i++) {
      list[i] = list[i].toLowerCase();
    }
    return list;
  }

  // Show which pills are on, based on the staged list.
  function highlightPills() {
    var activeSources = readStagedSources();

    for (var i = 0; i < stagedPills.length; i++) {
      var pill = stagedPills[i];
      var pillSource = pill.dataset.source.toLowerCase();

      var isOn = false;
      for (var j = 0; j < activeSources.length; j++) {
        if (activeSources[j] === pillSource) {
          isOn = true;
        }
      }

      if (isOn) {
        pill.classList.add('active');
      } else {
        pill.classList.remove('active');
      }
    }
  }

  // Highlight the pills the way the URL has them when the page loads.
  highlightPills();

  // Clicking a pill toggles that source and rebuilds the staged "source" value.
  for (var p = 0; p < stagedPills.length; p++) {
    stagedPills[p].addEventListener('click', function () {
      var clickedSource = this.dataset.source.toLowerCase();
      var activeSources = readStagedSources();

      // If it is already on, take it out. Otherwise add it.
      var index = activeSources.indexOf(clickedSource);
      if (index !== -1) {
        activeSources.splice(index, 1);
      } else {
        activeSources.push(clickedSource);
      }

      // Write the new source list back into the staged filters.
      if (activeSources.length > 0) {
        stagedParams.set('source', activeSources.join(','));
      } else {
        stagedParams.delete('source');
      }

      highlightPills();
    });
  }

  // -------------------------------------------------------------------------
  // Price / rooms / energy dropdown options.
  // These are links. stop them from reloading and stage the choice instead.
  // -------------------------------------------------------------------------
  var stagedOptions = stagedPanel.querySelectorAll('a.dropdown-option');

  for (var o = 0; o < stagedOptions.length; o++) {
    stagedOptions[o].addEventListener('click', function (event) {
      event.preventDefault();

      var dropdown = this.closest('.dropdown');
      if (!dropdown) {
        return;
      }

      // The links address describes the choice, e.g. "?sort=price&order=asc"
      var targetParams = parseHrefParams(this.getAttribute('href'));
      var ownedKeys = dropdownOwnedKeys(dropdown.id);

      for (var k = 0; k < ownedKeys.length; k++) {
        stagedParams.delete(ownedKeys[k]);
      }
      for (var m = 0; m < ownedKeys.length; m++) {
        var key = ownedKeys[m];
        if (targetParams.has(key)) {
          stagedParams.set(key, targetParams.get(key));
        }
      }

      updateDropdownLook(dropdown, this);
    });
  }

  // Turn the links address ("?a=1&b=2") into a params object, or an empty one
  function parseHrefParams(href) {
    var queryPart = '';
    if (href.indexOf('?') !== -1) {
      queryPart = href.split('?')[1];
    }

    var params = new URLSearchParams(queryPart);
    params.delete('page');
    return params;
  }

  // After a dropdown option is picked, update its button label and highlight, then close the open menu.
  function updateDropdownLook(dropdown, optionLink) {
    var toggle = dropdown.querySelector('.dropdown-toggle');
    var labelEl = dropdown.querySelector('.dropdown-toggle p');
    var optionText = optionLink.textContent.trim();

    // "Default" and "Any" are the clear options: go back to the base label.
    if (optionText === 'Default' || optionText === 'Any') {
      if (labelEl) {
        labelEl.textContent = baseDropdownLabel(dropdown.id);
      }
      if (toggle) {
        toggle.classList.remove('active');
      }
    } else {
      if (labelEl) {
        labelEl.textContent = optionText;
      }
      if (toggle) {
        toggle.classList.add('active');
      }
    }

    var options = dropdown.querySelector('.dropdown-options');
    if (options) {
      options.classList.remove('show');
    }
  }

  // -------------------------------------------------------------------------
  // Tag chips (Furnished, Housemates, Plot, Garage).
  // Each chip switches one thing on or off
  // -------------------------------------------------------------------------
  var stagedChips = stagedPanel.querySelectorAll('a.filter-chip');
  var currentHasList = readCommaList(currentParams, 'has');

  for (var c = 0; c < stagedChips.length; c++) {
    var chip = stagedChips[c];
    var chipHref = chip.getAttribute('href');

    // "Clear all" is the only chip whose link is just "?"
    if (chipHref === '?') {
      chip.addEventListener('click', function (event) {
        event.preventDefault();
        clearAllStaged();
      });
      continue;
    }

    // Figure out what this chip controls and remember it on the element.
    var chipTarget = parseHrefParams(chipHref);

    var currentGarage = currentParams.get('no_living_area');
    var targetGarage = chipTarget.get('no_living_area');

    if (currentGarage !== targetGarage) {
      // This is the garage / parking chip (it flips ?no_living_area=1).
      chip.dataset.stagedKind = 'garage';
    } else {
      // A normal tag chip: it adds or removes one key from the ?has= list.
      var targetHasList = readCommaList(chipTarget, 'has');
      chip.dataset.stagedKind = 'has';
      chip.dataset.stagedKey = findDifferentKey(currentHasList, targetHasList);
    }

    chip.addEventListener('click', function (event) {
      event.preventDefault();
      toggleChip(this);
    });
  }

  function findDifferentKey(listA, listB) {
    for (var i = 0; i < listA.length; i++) {
      if (listB.indexOf(listA[i]) === -1) {
        return listA[i];
      }
    }
    for (var j = 0; j < listB.length; j++) {
      if (listA.indexOf(listB[j]) === -1) {
        return listB[j];
      }
    }
    return '';
  }

  // Switch a tag chip on or off in the staged filters and update its highlight.
  function toggleChip(chip) {
    if (chip.dataset.stagedKind === 'garage') {
      if (stagedParams.get('no_living_area') === '1') {
        stagedParams.delete('no_living_area');
      } else {
        stagedParams.set('no_living_area', '1');
      }
    } else {
      var key = chip.dataset.stagedKey;
      var hasList = readCommaList(stagedParams, 'has');

      var index = hasList.indexOf(key);
      if (index !== -1) {
        hasList.splice(index, 1);
      } else {
        hasList.push(key);
      }

      if (hasList.length > 0) {
        stagedParams.set('has', hasList.join(','));
      } else {
        stagedParams.delete('has');
      }
    }

    updateChipLook(chip);
  }

  // Show a chip as on or off based on the staged filters.
  function updateChipLook(chip) {
    var isOn = false;

    if (chip.dataset.stagedKind === 'garage') {
      isOn = (stagedParams.get('no_living_area') === '1');
    } else {
      var hasList = readCommaList(stagedParams, 'has');
      isOn = (hasList.indexOf(chip.dataset.stagedKey) !== -1);
    }

    if (isOn) {
      chip.classList.add('active');
    } else {
      chip.classList.remove('active');
    }
  }

  // -------------------------------------------------------------------------
  // "Clear all": empty the staged filters and reset every control on screen,
  // including the city box, the budget and the move-in date.
  // -------------------------------------------------------------------------
  function clearAllStaged() {
    stagedParams = new URLSearchParams();

    // Turn off all source pills.
    highlightPills();

    // Turn off all tag chips (but leave the "Clear all" chip itself alone).
    for (var i = 0; i < stagedChips.length; i++) {
      if (stagedChips[i].getAttribute('href') !== '?') {
        stagedChips[i].classList.remove('active');
      }
    }

    // Reset all dropdown buttons to their base label.
    var dropdowns = stagedPanel.querySelectorAll('.dropdown');
    for (var d = 0; d < dropdowns.length; d++) {
      var toggle = dropdowns[d].querySelector('.dropdown-toggle');
      var labelEl = dropdowns[d].querySelector('.dropdown-toggle p');
      if (labelEl) {
        labelEl.textContent = baseDropdownLabel(dropdowns[d].id);
      }
      if (toggle) {
        toggle.classList.remove('active');
      }
    }

    // Clear the city boxes.
    var cityInput = document.getElementById('map-city-input');
    if (cityInput) {
      cityInput.value = '';
    }
    var cityHidden = document.getElementById('map-filters-city');
    if (cityHidden) {
      cityHidden.value = '';
    }

    // Clear the move-in date.
    var moveInInput = document.getElementById('map-movein-input');
    if (moveInInput) {
      moveInInput.value = '';
    }

    // Put the budget back to the maximum (which means "no limit").
    var budgetSlider = document.getElementById('map-budget-slider');
    if (budgetSlider) {
      budgetSlider.value = budgetSlider.max;
      budgetSlider.dispatchEvent(new Event('input'));
    }
  }

  // -------------------------------------------------------------------------
  // "Apply filters": build the new URL from the staged list
  // (plus the budget, move-in and city boxes) and reload the page once.
  // -------------------------------------------------------------------------
  stagedForm.addEventListener('submit', function (event) {
    event.preventDefault();

    // The sliders top value means "no limit", so in that case we leave
    // max_price out of the URL. Otherwise we send the chosen amount
    var budgetInput = document.getElementById('map-budget-input');
    var budgetSlider = document.getElementById('map-budget-slider');
    if (budgetInput && budgetSlider) {
      var maxBudget = parseInt(budgetSlider.max, 10);
      var typedBudget = parseInt(budgetInput.value, 10);

      if (isNaN(typedBudget) || typedBudget >= maxBudget) {
        stagedParams.delete('max_price');
      } else {
        stagedParams.set('max_price', String(typedBudget));
      }
    }

    // Move-in date
    var moveInInput = document.getElementById('map-movein-input');
    if (moveInInput && moveInInput.value !== '') {
      stagedParams.set('available_by', moveInInput.value);
    } else {
      stagedParams.delete('available_by');
    }

    // City (kept in sync with the search box by mapCity.js)
    var cityHidden = document.getElementById('map-filters-city');
    if (cityHidden && cityHidden.value.trim() !== '') {
      stagedParams.set('city', cityHidden.value.trim());
    } else {
      stagedParams.delete('city');
    }

    // Any filter change starts back at page 1
    stagedParams.delete('page');

    // Remember that we applied filters, so the panel starts collapsed after the page reloads below.
    sessionStorage.setItem('mapFiltersCollapsed', '1');

    location.href = 'map.php?' + stagedParams.toString();
  });
}
