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

// This prevents unsafe HTML from being inserted into listing detail markup.
function escapeHtml(value) {
  const element = document.createElement('div');
  element.textContent = value || '';
  return element.innerHTML;
}

// Only allow normal website links in the listing detail bar.
function safeListingUrl(value) {
  try {
    const url = new URL(value || '../detail/detail.html', window.location.href);

    if (url.protocol === 'http:' || url.protocol === 'https:') {
      return url.href;
    }
  } catch (error) {
    return '../detail/detail.html';
  }

  return '../detail/detail.html';
}

function safeImageUrl(value) {
  try {
    const url = new URL(value || '', window.location.href);

    if (url.protocol === 'http:' || url.protocol === 'https:') {
      return url.href;
    }
  } catch (error) {
    return '';
  }

  return '';
}

// Google Maps calls this function after the Google Maps script has loaded.
function initLetMeRentMap() {
  const mapElement = document.getElementById('google-map');

  // Elements used by the bottom listing bar that replaces the Google Maps popup.
  const listingBar = document.getElementById('map-listing-bar');
  const listingImage = document.getElementById('map-listing-image');
  const listingPrice = document.getElementById('map-listing-price');
  const listingTitle = document.getElementById('map-listing-title');
  const listingTags = document.getElementById('map-listing-tags');
  const listingLink = document.getElementById('map-listing-link');
  const listingClose = document.getElementById('map-listing-close');
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

  // Keep price rendering in one place so unknown prices have a clean fallback.
  function formatPrice(listing) {
    if (listing.price !== undefined && listing.price !== null && listing.price !== '') {
      return `&euro;${escapeHtml(String(listing.price))}<span>/mo</span>`;
    }

    return escapeHtml(listing.priceLabel || 'Price unknown');
  }

  // Build the same kind of detail tags used by the listing cards.
  function buildListingTags(listing) {
    const tags = [];

    if (listing.city) {
      tags.push(`📍 ${String(listing.city).charAt(0).toUpperCase() + String(listing.city).slice(1)}`);
    }
    if (listing.rooms) {
      tags.push(`🛏️ ${listing.rooms} rooms`);
    } else if (listing.property_type) {
      tags.push(`🛏️ ${listing.property_type}`);
    }
    if (listing.furnished) {
      tags.push(`🛋️ ${listing.furnished}`);
    } else if (listing.interior) {
      tags.push(`🛋️ ${listing.interior}`);
    }
    if (listing.living_area) {
      tags.push(`📐 ${listing.living_area} m²`);
    }
    if (listing.housemates) {
      tags.push(`👥 ${listing.housemates}`);
    }
    if (listing.energy_label) {
      tags.push(`⚡ ${listing.energy_label}`);
    }
    if (listing.rental_period) {
      tags.push(`📋 ${listing.rental_period}`);
    }
    if (listing.deposit) {
      tags.push(`🔑 €${listing.deposit} deposit`);
    }
    if (listing.bathrooms) {
      tags.push(`🛁 ${listing.bathrooms} bath`);
    }
    if (listing.plot_size) {
      tags.push(`🌳 ${listing.plot_size} m² plot`);
    }
    if (listing.year_built) {
      tags.push(`🏗️ ${listing.year_built}`);
    }
    if (listing.neighbourhood) {
      tags.push(`🗺️ ${listing.neighbourhood}`);
    }
    if (listing.status) {
      tags.push(`✅ ${listing.status}`);
    }
    if (listing.home_type) {
      tags.push(`🏠 ${listing.home_type}`);
    }
    if (listing.availability) {
      tags.push(`📅 ${listing.availability}`);
    }

    return tags;
  }

  // Fill the bottom bar with the selected listing's data and reveal it.
  function showListingBar(listing) {
    if (!listingBar || !listingImage || !listingPrice || !listingTitle || !listingTags || !listingLink) {
      return;
    }

    const imageUrl = safeImageUrl(listing.image);
    listingImage.innerHTML = '';

    if (imageUrl) {
      const image = document.createElement('img');
      image.src = imageUrl;
      image.alt = listing.title || 'Rental listing';
      listingImage.appendChild(image);
    }

    listingPrice.innerHTML = formatPrice(listing);
    listingTitle.textContent = listing.title || 'Rental listing';
    listingTags.innerHTML = buildListingTags(listing).map((tag) => {
      return `<span class="tag">${escapeHtml(tag)}</span>`;
    }).join('');
    listingLink.href = safeListingUrl(listing.url);
    listingBar.hidden = false;
  }

  // Hide the bar and return the previously selected marker to its normal state.
  function hideListingBar() {
    if (listingBar) {
      listingBar.hidden = true;
    }

    if (selectedMarker) {
      selectedMarker.setAnimation(null);
      selectedMarker.setZIndex(null);
      selectedMarker = null;
    }
  }

  // Select one marker and show its details in the bottom bar.
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

    showListingBar(listing);
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
    hideListingBar();
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
        hideListingBar();
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

  if (listingClose) {
    listingClose.addEventListener('click', () => {
      // Closing the bar should fully reset the selected map/list state.
      hideListingBar();
      showAllMarkers();
      document.querySelectorAll('.js-map-listing.selected').forEach((selectedItem) => {
        selectedItem.classList.remove('selected');
      });
    });
  }

  if (markerCount > 0) {
    map.fitBounds(bounds);
    showAllMarkers();
    return;
  }

  map.setCenter(fallbackCenter);
}

window.showMapLoadError = showMapLoadError;
window.initLetMeRentMap = initLetMeRentMap;
