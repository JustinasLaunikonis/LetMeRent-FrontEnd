// Lets the user type the name of a place, pick a distance with the slider, and press "Search area".
function setupPlaceSearch(map, circleApi) {
  var input = document.getElementById('map-place-input');
  var slider = document.getElementById('map-place-slider');
  var valueLabel = document.getElementById('map-place-value');
  var searchButton = document.getElementById('map-place-search-btn');
  var statusLabel = document.getElementById('map-place-status');

  if (!input || !slider || !valueLabel || !searchButton) {
    return;
  }

  if (!circleApi || typeof circleApi.createCircleAt !== 'function') {
    return;
  }

  // Turn a number of metres into text, like "300 m" or "2.5 km"
  function radiusText(meters) {
    if (meters < 1000) {
      return meters + ' m';
    }
    return (meters / 1000) + ' km';
  }

  // Show a short message under the search box (or hide it when empty).
  function setStatus(message) {
    if (!statusLabel) {
      return;
    }
    if (message) {
      statusLabel.textContent = message;
      statusLabel.hidden = false;
    } else {
      statusLabel.textContent = '';
      statusLabel.hidden = true;
    }
  }

  // The slider is in metres. Keep the label next to it in sync.
  function updateValueLabel() {
    valueLabel.textContent = radiusText(Number(slider.value));
  }

  slider.addEventListener('input', updateValueLabel);
  updateValueLabel();

  // The Places service needs the "places" library to be loaded with the map
  var placesService = null;
  if (google.maps.places && google.maps.places.PlacesService) {
    placesService = new google.maps.places.PlacesService(map);
  }

  // look for places near what the user is currently looking at
  function currentLocationBias() {
    var bounds = map.getBounds();
    if (bounds) {
      return bounds;
    }
    return map.getCenter();
  }

  function runSearch() {
    var query = input.value.trim();

    if (query === '') {
      setStatus('Type a place to search for.');
      return;
    }

    if (!placesService) {
      setStatus('Place search is unavailable right now.');
      return;
    }

    setStatus('Searching for "' + query + '"...');

    var request = {
      query: query,
      fields: ['name', 'geometry', 'formatted_address'],
      locationBias: currentLocationBias()
    };

    placesService.findPlaceFromQuery(request, function (results, status) {
      var statuses = google.maps.places.PlacesServiceStatus;

      if (status !== statuses.OK || !results || results.length === 0) {
        if (status === statuses.REQUEST_DENIED) {
          setStatus('Place search is not enabled for this sites Google key. Enable the Places API in Google Cloud.');
        } else if (status === statuses.OVER_QUERY_LIMIT) {
          setStatus('Place search has hit its usage limit. Please try again later.');
        } else if (status === statuses.INVALID_REQUEST) {
          setStatus('Could not search for that. Try a different name.');
        } else {
          setStatus('No place found for "' + query + '". Try a different name.');
        }
        return;
      }

      var place = results[0];
      if (!place.geometry || !place.geometry.location) {
        setStatus('Could not find where "' + query + '" is.');
        return;
      }

      var center = place.geometry.location;
      var radius = Number(slider.value);

      map.panTo(center);
      circleApi.createCircleAt(center, radius);

      var placeName = place.name || query;
      setStatus('Listings within ' + radiusText(radius) + ' of ' + placeName + '.');
    });
  }

  searchButton.addEventListener('click', runSearch);
}

window.setupPlaceSearch = setupPlaceSearch;
