// Scripts for the listing detail page: the image gallery popup and the small Google map that shows the listings location.

// ---------------------------------------------------------------------------
// Image gallery popup
// ---------------------------------------------------------------------------
(function () {
  var modal = document.querySelector('[data-gallery-modal]');
  // The "Show All" button and the image grid itself open the popup.
  var openElements = document.querySelectorAll('[data-gallery-open]');

  // Nothing to do if the popup or the open triggers are missing
  if (!modal || openElements.length === 0) {
    return;
  }

  function openModal() {
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
  }

  for (var j = 0; j < openElements.length; j++) {
    openElements[j].addEventListener('click', openModal);
  }

  var closeElements = modal.querySelectorAll('[data-gallery-close]');
  for (var i = 0; i < closeElements.length; i++) {
    closeElements[i].addEventListener('click', closeModal);
  }

  // Also close when the user presses the Escape key
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeModal();
    }
  });
})();

// ---------------------------------------------------------------------------
// Google map for the listing location
// ---------------------------------------------------------------------------
(function () {
  function showDetailMapLoadError() {
    var errorElement = document.getElementById('detail-map-load-error');
    if (errorElement) {
      errorElement.hidden = false;
    }
  }

  function initLetMeRentDetailMap() {
    var mapElement = document.getElementById('detail-google-map');
    if (!mapElement) {
      return;
    }

    var config = window.letMeRentDetailMapConfig || {};
    var fallbackCenter = { lat: 52.3676, lng: 4.9041 };
    var center = fallbackCenter;

    if (Number.isFinite(Number(config.latitude)) && Number.isFinite(Number(config.longitude))) {
      center = {
        lat: Number(config.latitude),
        lng: Number(config.longitude)
      };
    }

    var map = new google.maps.Map(mapElement, {
      center: center,
      zoom: Number(config.zoom) || 14,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: true
    });

    if (config.hasCoordinates) {
      new google.maps.Marker({
        position: center,
        map: map,
        title: config.title || 'Listing location'
      });
    }
  }

  window.initLetMeRentDetailMap = initLetMeRentDetailMap;
  window.showDetailMapLoadError = showDetailMapLoadError;
})();
