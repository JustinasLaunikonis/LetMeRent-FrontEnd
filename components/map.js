// Google calls this function when the API key is rejected.
window.gm_authFailure = function() {
  const errorElement = document.getElementById('map-load-error');
  if (errorElement) {
    errorElement.hidden = false;
  }
};

function showMapLoadError() {
  const errorElement = document.getElementById('map-load-error');
  if (errorElement) {
    errorElement.hidden = false;
    errorElement.textContent = 'Google Maps could not be loaded. Check your internet connection and API key.';
  }
}

// This prevents unsafe HTML from being inserted into the marker popup.
function escapeHtml(value) {
  const element = document.createElement('div');
  element.textContent = value || '';
  return element.innerHTML;
}

// Only allow normal website links in the marker popup.
function safeListingUrl(value) {
  try {
    const url = new URL(value || '../detail/detail.html', window.location.href);

    if (url.protocol === 'http:' || url.protocol === 'https:') {
      return escapeHtml(url.href);
    }
  } catch (error) {
    return '../detail/detail.html';
  }

  return '../detail/detail.html';
}

// Google Maps calls this function after the Google Maps script has loaded.
function initLetMeRentMap() {
  const mapElement = document.getElementById('google-map');
  const config = window.letMeRentMapConfig || {};

  // Amsterdam is only used when there are no listing markers.
  const fallbackCenter = { lat: 52.3676, lng: 4.9041 };
  const map = new google.maps.Map(mapElement, {
    center: fallbackCenter,
    zoom: 12,
    mapTypeControl: false,
    streetViewControl: false,
    fullscreenControl: true
  });

  const bounds = new google.maps.LatLngBounds();
  const infoWindow = new google.maps.InfoWindow();
  const markers = Array.isArray(config.markers) ? config.markers : [];
  const markersByPosition = new Map();
  const markersByMapIndex = new Map();
  const markerEntries = [];
  let selectedMarker = null;
  let markerCount = 0;

  // Round coordinates so tiny decimal differences do not break matching.
  function positionKey(lat, lng) {
    return `${Number(lat).toFixed(6)},${Number(lng).toFixed(6)}`;
  }

  // Open the small info popup for one marker.
  function openListingMarker(marker, listing) {
    if (selectedMarker && selectedMarker !== marker) {
      selectedMarker.setAnimation(null);
      selectedMarker.setZIndex(null);
    }

    selectedMarker = marker;
    marker.setZIndex(google.maps.Marker.MAX_ZINDEX + 1);
    marker.setAnimation(google.maps.Animation.BOUNCE);
    window.setTimeout(() => {
      marker.setAnimation(null);
    }, 700);

    infoWindow.setContent(`
      <div class="gm-info">
        <strong>${escapeHtml(listing.price || 'Price unknown')}</strong>
        <span>${escapeHtml(listing.title || 'Rental listing')}</span>
        <a href="${safeListingUrl(listing.url)}" target="_blank" rel="noopener">View listing</a>
      </div>
    `);
    infoWindow.open(map, marker);
  }

  markers.forEach((listing) => {
    const position = { lat: Number(listing.lat), lng: Number(listing.lng) };

    if (!Number.isFinite(position.lat) || !Number.isFinite(position.lng)) {
      return;
    }

    const marker = new google.maps.Marker({
      position,
      map,
      title: listing.title || 'Rental listing'
    });
    const markerEntry = { marker, listing };

    // Clicking a marker selects the matching listing in the left sidebar.
    marker.addListener('click', () => {
      showOnlyMarker(markerEntry);
      selectSidebarListing(listing.mapIndex);
      openListingMarker(marker, listing);
    });

    markerEntries.push(markerEntry);
    markersByPosition.set(positionKey(position.lat, position.lng), markerEntry);
    markersByMapIndex.set(String(listing.mapIndex), markerEntry);
    bounds.extend(position);
    markerCount++;
  });

  // Reset the map to show every marker again.
  function showAllMarkers() {
    markerEntries.forEach((entry) => {
      entry.marker.setVisible(true);
    });
  }

  // Focus the map by hiding every marker except one.
  function showOnlyMarker(selectedEntry) {
    markerEntries.forEach((entry) => {
      entry.marker.setVisible(entry === selectedEntry);
    });
  }

  // Select the matching item in the left sidebar.
  function selectSidebarListing(mapIndex) {
    const sidebarItem = Array.from(document.querySelectorAll('.js-map-listing')).find((item) => {
      return item.dataset.mapIndex === String(mapIndex);
    });

    document.querySelectorAll('.js-map-listing.selected').forEach((selectedItem) => {
      selectedItem.classList.remove('selected');
    });

    if (!sidebarItem) {
      return;
    }

    sidebarItem.classList.add('selected');
    sidebarItem.scrollIntoView({
      behavior: 'smooth',
      block: 'center'
    });
  }

  // Clicking empty space on the map clears the selected listing.
  map.addListener('click', () => {
    infoWindow.close();
    showAllMarkers();
    document.querySelectorAll('.js-map-listing.selected').forEach((selectedItem) => {
      selectedItem.classList.remove('selected');
    });
  });

  // Clicking a listing in the sidebar focuses the matching marker.
  document.querySelectorAll('.js-map-listing').forEach((item, rowIndex) => {
    item.addEventListener('click', (event) => {
      event.preventDefault();

      const wasSelected = item.classList.contains('selected');
      let markerData = markersByMapIndex.get(String(item.dataset.mapIndex));

      // Fallback: match by coordinates if the marker index is missing.
      if (!markerData && item.dataset.lat && item.dataset.lng) {
        markerData = markersByPosition.get(positionKey(item.dataset.lat, item.dataset.lng));
      }

      // Last fallback: use the same order as the visible sidebar.
      if (!markerData && markerEntries[rowIndex]) {
        markerData = markerEntries[rowIndex];
      }

      document.querySelectorAll('.js-map-listing.selected').forEach((selectedItem) => {
        selectedItem.classList.remove('selected');
      });

      // Clicking the selected listing again resets the map.
      if (wasSelected) {
        infoWindow.close();
        showAllMarkers();
        return;
      }

      item.classList.add('selected');

      if (markerData) {
        const markerPosition = markerData.marker.getPosition();
        showOnlyMarker(markerData);
        map.setCenter(markerPosition);
        map.setZoom(15);
        openListingMarker(markerData.marker, markerData.listing);
        return;
      }

      // If no marker was found, still try to pan to the listing coordinates.
      const lat = Number(item.dataset.lat);
      const lng = Number(item.dataset.lng);

      if (Number.isFinite(lat) && Number.isFinite(lng)) {
        map.panTo({ lat, lng });
        map.setZoom(15);
      }
    });
  });

  if (markerCount > 0) {
    map.fitBounds(bounds);
    showAllMarkers();
    return;
  }

  map.setCenter(fallbackCenter);
}

window.showMapLoadError = showMapLoadError;
window.initLetMeRentMap = initLetMeRentMap;
