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
