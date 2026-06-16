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

// Like escapeHtml, but also escapes the double-quote character so a value is
// safe to put inside a double-quoted HTML attribute (escapeHtml leaves quotes).
function escapeAttribute(value) {
  let text = escapeHtml(value);
  text = text.split('"').join('&quot;');
  return text;
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

function safeDetailUrl(id) {
  if (id === undefined || id === null || String(id) === '') {
    return '../detail/detail.html';
  }
  return '../detail/detail.php?id=' + encodeURIComponent(String(id));
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
  const listingDetail = document.getElementById('map-listing-detail');
  const listingClose = document.getElementById('map-listing-close');
  const config = window.letMeRentMapConfig || {};

  // The sidebar normally shows one server-rendered page of listings, plus the count text at the top.
  // When the circle tool filters, rebuild the sidebar
  const sidebarElement = document.querySelector('.map-sidebar');
  const sidebarListElement = document.querySelector('.map-list');
  const sidebarCountElement = document.querySelector('.map-count');

  // The footer holds the page buttons.
  let sidebarFootElement = document.querySelector('.map-foot');
  let footExistedOriginally = false;
  let originalFootHtml = '';
  if (sidebarFootElement) {
    footExistedOriginally = true;
    originalFootHtml = sidebarFootElement.innerHTML;
  }
  let originalSidebarHtml = '';
  if (sidebarListElement) {
    originalSidebarHtml = sidebarListElement.innerHTML;
  }
  let originalCountHtml = '';
  if (sidebarCountElement) {
    originalCountHtml = sidebarCountElement.innerHTML;
  }

  // Circle tool state.
  let circleActive = false;
  let circleAreas = [];
  // One true/false for each drawn circle, saying whether it overlaps another circle.
  let circleHasOverlap = [];
  let circleTargetLevel = 1;
  let circleListings = [];
  let circlePage = 1;
  const circlePerPage = 10;

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

  function getListingTags(listing) {
    if (Array.isArray(listing.tags)) {
      return listing.tags;
    }
    return [];
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

    listingTags.innerHTML = getListingTags(listing).map((tag) => {
      return `<span class="tag">${escapeHtml(tag)}</span>`;
    }).join('');

    listingLink.href = safeListingUrl(listing.url);
    if (listingDetail) {
      listingDetail.href = safeDetailUrl(listing.id);
    }
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

  // Work out which drawn circles overlap each other.
  // Two circles overlap when the distance between their centers is smaller than their two radii added together.
  function updateCircleOverlaps() {
    circleHasOverlap = [];
    for (let i = 0; i < circleAreas.length; i++) {
      circleHasOverlap.push(false);
    }

    for (let i = 0; i < circleAreas.length; i++) {
      for (let j = i + 1; j < circleAreas.length; j++) {
        const distance = google.maps.geometry.spherical.computeDistanceBetween(circleAreas[i].center, circleAreas[j].center);
        if (distance < circleAreas[i].radius + circleAreas[j].radius) {
          circleHasOverlap[i] = true;
          circleHasOverlap[j] = true;
        }
      }
    }
  }

  // Count how many of the drawn circles overlap a position.
  function countInsideCircles(position) {
    let count = 0;
    for (let i = 0; i < circleAreas.length; i++) {
      const distance = google.maps.geometry.spherical.computeDistanceBetween(circleAreas[i].center, position);
      if (distance <= circleAreas[i].radius) {
        count++;
      }
    }
    return count;
  }

  // With N circles we want listings inside all N.
  // If none exist we drop to N-1, then N-2, and so on, never below 2.
  // The result is the highest number of circles any single listing falls inside
  // When no listing reaches two circles this returns 1,
  // then isInsideFilteredArea uses the single-circle rule.
  function computeCircleTargetLevel() {
    let maxCount = 0;
    markerEntries.forEach((entry) => {
      const count = countInsideCircles(entry.marker.getPosition());
      if (count > maxCount) {
        maxCount = count;
      }
    });

    if (maxCount < 2) {
      return 1;
    }
    return maxCount;
  }

  function isInsideFilteredArea(position) {
    const insideCount = countInsideCircles(position);
    if (insideCount == 0) {
      return false;
    }

    // Deepest overlap level reached by some listing
    if (circleTargetLevel >= 2) {
      return insideCount >= circleTargetLevel;
    }

    // No listing is inside two or more circles
    for (let i = 0; i < circleAreas.length; i++) {
      const distance = google.maps.geometry.spherical.computeDistanceBetween(circleAreas[i].center, position);
      if (distance <= circleAreas[i].radius) {
        return !circleHasOverlap[i];
      }
    }
    return false;
  }

  // Show only the markers that pass the circle filter above.
  function showMarkersInsideCircle() {
    markerEntries.forEach((entry) => {
      entry.marker.setVisible(isInsideFilteredArea(entry.marker.getPosition()));
    });
  }

  function resetMarkerVisibility() {
    if (circleActive && circleAreas.length > 0) {
      showMarkersInsideCircle();
    } else {
      showAllMarkers();
    }
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
    resetMarkerVisibility();
    document.querySelectorAll('.js-map-listing.selected').forEach((selectedItem) => {
      selectedItem.classList.remove('selected');
    });
  });

  function wireSidebarItem(item) {
    item.addEventListener('click', (event) => {
      event.preventDefault();

      const wasSelected = item.classList.contains('selected');
      let markerData = markersByMapIndex.get(String(item.dataset.mapIndex));

      // Fallback: match by coordinates if the marker index is missing.
      if (!markerData && item.dataset.lat && item.dataset.lng) {
        markerData = markersByPosition.get(positionKey(item.dataset.lat, item.dataset.lng));
      }

      document.querySelectorAll('.js-map-listing.selected').forEach((selectedItem) => {
        selectedItem.classList.remove('selected');
      });

      // Clicking the selected listing again resets the map.
      if (wasSelected) {
        hideListingBar();
        resetMarkerVisibility();
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
  }

  function wireAllSidebarItems() {
    document.querySelectorAll('.js-map-listing').forEach((item) => {
      wireSidebarItem(item);
    });
  }

  // Clicking a listing in the sidebar focuses the matching marker.
  wireAllSidebarItems();

  if (listingClose) {
    listingClose.addEventListener('click', () => {
      // Closing the bar should fully reset the selected map/list state.
      hideListingBar();
      resetMarkerVisibility();
      document.querySelectorAll('.js-map-listing.selected').forEach((selectedItem) => {
        selectedItem.classList.remove('selected');
      });
    });
  }

  // Build the HTML for one sidebar card from a listing object
  function renderSidebarCardHtml(listing) {
    let thumb = '<div class="map-thumb"></div>';
    const imageUrl = safeImageUrl(listing.image);
    if (imageUrl) {
      const altText = escapeAttribute(listing.title || 'Rental listing');
      thumb = '<div class="map-thumb"><img src="' + escapeAttribute(imageUrl) + '" alt="' + altText + '" style="width:100%;height:100%;object-fit:cover;"></div>';
    }

    // Price (or a dash when the price is unknown).
    let priceHtml = '&mdash;';
    if (listing.price !== undefined && listing.price !== null && listing.price !== '') {
      priceHtml = '&euro;' + escapeHtml(String(listing.price));
    }

    const title = escapeHtml(listing.title || 'Untitled listing');

    // The sidebar always shows the first three tags.
    const tags = getListingTags(listing);
    let tagHtml = '';
    for (let i = 0; i < tags.length && i < 3; i++) {
      tagHtml += '<span class="tag">' + escapeHtml(tags[i]) + '</span>';
    }

    const url = escapeAttribute(safeListingUrl(listing.url));
    let dataAttributes = '';
    if (Number.isFinite(Number(listing.lat)) && Number.isFinite(Number(listing.lng))) {
      dataAttributes += ' data-lat="' + escapeAttribute(String(listing.lat)) + '" data-lng="' + escapeAttribute(String(listing.lng)) + '"';
    }
    if (listing.mapIndex !== undefined && listing.mapIndex !== null) {
      dataAttributes += ' data-map-index="' + escapeAttribute(String(listing.mapIndex)) + '"';
    }

    return '<a class="map-list-item js-map-listing" href="#google-map" data-listing-url="' + url + '"' + dataAttributes + '>'
      + thumb
      + '<div class="map-item-info">'
      + '<div class="map-item-price">' + priceHtml + '<span>/mo</span></div>'
      + '<div class="map-item-title">' + title + '</div>'
      + '<div class="map-item-tags">' + tagHtml + '</div>'
      + '</div>'
      + '</a>';
  }

  // Update the count text at the top of the sidebar.
  // When a circle is active show how many listings fell inside it
  function updateSidebarCount(count, circle) {
    if (!sidebarCountElement) {
      return;
    }
    if (circle) {
      sidebarCountElement.innerHTML = count + ' listings <span>- in selected area</span>';
    } else {
      sidebarCountElement.innerHTML = originalCountHtml;
    }
  }

  // Find the footer, making one if the server did not render it (it only does so when the unfiltered list has more than one page).
  function getOrCreateFoot() {
    if (sidebarFootElement) {
      return sidebarFootElement;
    }
    const foot = document.createElement('div');
    foot.className = 'map-foot';
    if (sidebarElement) {
      sidebarElement.appendChild(foot);
    }
    sidebarFootElement = foot;
    return sidebarFootElement;
  }

  // Draw the Prev / Next buttons for the circle results
  function renderCirclePager(totalPages) {
    // With one page (or none) there is nothing to page through.
    if (totalPages <= 1) {
      if (sidebarFootElement) {
        sidebarFootElement.innerHTML = '';
        sidebarFootElement.style.display = 'none';
      }
      return;
    }

    const foot = getOrCreateFoot();
    if (!foot) {
      return;
    }
    foot.style.display = '';

    // Prev button (greyed out on the first page).
    let prevHtml = '';
    if (circlePage > 1) {
      prevHtml = '<a class="map-page-btn" href="#" id="circle-prev">&#8592; Prev</a>';
    } else {
      prevHtml = '<span class="map-page-btn map-page-btn--disabled">&#8592; Prev</span>';
    }

    // Next button (greyed out on the last page).
    let nextHtml = '';
    if (circlePage < totalPages) {
      nextHtml = '<a class="map-page-btn" href="#" id="circle-next">Next &#8594;</a>';
    } else {
      nextHtml = '<span class="map-page-btn map-page-btn--disabled">Next &#8594;</span>';
    }

    foot.innerHTML = '<nav class="map-pagination">'
      + prevHtml
      + '<span class="map-page-info">Page ' + circlePage + ' of ' + totalPages + '</span>'
      + nextHtml
      + '</nav>';

    // make the buttons to change the page without reloading.
    const prevButton = document.getElementById('circle-prev');
    if (prevButton) {
      prevButton.addEventListener('click', (event) => {
        event.preventDefault();
        circlePage = circlePage - 1;
        renderCirclePage();
      });
    }
    const nextButton = document.getElementById('circle-next');
    if (nextButton) {
      nextButton.addEventListener('click', (event) => {
        event.preventDefault();
        circlePage = circlePage + 1;
        renderCirclePage();
      });
    }
  }

  // Render the current page of circle results into the sidebar.
  function renderCirclePage() {
    if (!sidebarListElement) {
      return;
    }

    let totalPages = Math.ceil(circleListings.length / circlePerPage);
    if (totalPages < 1) {
      totalPages = 1;
    }

    // Keep the page number inside the valid range.
    if (circlePage < 1) {
      circlePage = 1;
    }
    if (circlePage > totalPages) {
      circlePage = totalPages;
    }

    const start = (circlePage - 1) * circlePerPage;
    let end = start + circlePerPage;
    if (end > circleListings.length) {
      end = circleListings.length;
    }

    let html = '';
    for (let i = start; i < end; i++) {
      html += renderSidebarCardHtml(circleListings[i]);
    }
    sidebarListElement.innerHTML = html;
    wireAllSidebarItems();

    // Start each new page at the top of the list.
    sidebarListElement.scrollTop = 0;

    renderCirclePager(totalPages);
  }

  // Show only the listings whose marker is inside one of the circles, and
  // rebuild the sidebar to list them one page at a time.
  function filterListingsInCircle(areas) {
    circleActive = true;
    circleAreas = areas;

    updateCircleOverlaps();
    circleTargetLevel = computeCircleTargetLevel();

    // Collect the listings that pass the circle filter, keeping the marker order.
    circleListings = [];
    markerEntries.forEach((entry) => {
      const inside = isInsideFilteredArea(entry.marker.getPosition());
      entry.marker.setVisible(inside);
      if (inside) {
        circleListings.push(entry.listing);
      }
    });

    // Always start on the first page of the new results.
    circlePage = 1;

    if (sidebarListElement) {
      if (circleListings.length === 0) {
        sidebarListElement.innerHTML = '<div class="no-results">No listings in this area. Try a bigger circle or move it.</div>';
        // Nothing to page through, so hide the footer if there is one.
        if (sidebarFootElement) {
          sidebarFootElement.innerHTML = '';
          sidebarFootElement.style.display = 'none';
        }
      } else {
        renderCirclePage();
      }
    }

    updateSidebarCount(circleListings.length, true);
  }

  // Put the sidebar and markers back to normal (when the circle is removed).
  function clearListingsCircleFilter() {
    circleActive = false;
    circleAreas = [];
    circleListings = [];
    circleTargetLevel = 1;
    circlePage = 1;

    showAllMarkers();

    if (sidebarListElement) {
      sidebarListElement.innerHTML = originalSidebarHtml;
      wireAllSidebarItems();
    }

    // Restore the footer to how it started.
    if (footExistedOriginally) {
      if (sidebarFootElement) {
        sidebarFootElement.innerHTML = originalFootHtml;
        sidebarFootElement.style.display = '';
      }
    } else if (sidebarFootElement) {
      // made the footer ourselves for the circle pager, so remove it again.
      sidebarFootElement.remove();
      sidebarFootElement = null;
    }

    updateSidebarCount(0, false);
  }

  // Set up the "draw area circle" tool
  let areaCircleApi = null;
  if (typeof window.setupAreaCircle === 'function') {
    areaCircleApi = window.setupAreaCircle(map, {
      filterInCircle: filterListingsInCircle,
      clearFilter: clearListingsCircleFilter
    });
  }

  // "search a place" box
  if (typeof window.setupPlaceSearch === 'function') {
    window.setupPlaceSearch(map, areaCircleApi);
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
