// Lets the user draw one or more circles (areas) on the map and resize them.
// Each circle is marked with a letter: A, B, C and so on.
// Pressing "Done" filters the map and sidebar to the listings inside the circles.
function setupAreaCircle(map, helpers) {
  // helpers give us the functions that filter the listings (from map.js)
  if (!helpers) {
    helpers = {};
  }

  var toggleButton = document.getElementById('map-circle-toggle');
  var controls = document.getElementById('map-circle-controls');
  var slider = document.getElementById('map-circle-slider');
  var valueLabel = document.getElementById('map-circle-value');
  var removeButton = document.getElementById('map-circle-remove');

  var nameLabel = document.getElementById('map-circle-name');

  if (!toggleButton || !controls || !slider || !valueLabel || !removeButton) {
    return;
  }

  // All the circles the user has drawn
  var circles = [];

  // The circle the radius slider controls
  var activeCircle = null;

  // True after the user pressed "Done", so a filter is showing on the map
  var filterApplied = false;

  // The compass direction (heading) for each edge dot
  var edgeHeadings = [0, 90, 180, 270];

  // Once a circle is smaller than this many pixels on screen, the handle markers
  // (edge dots, "X", line and label) would overflow it, so we just hide them
  var minHandlePixels = 24;

  // Turn a circle number into its letter. 0 -> "A", 1 -> "B", and so on.
  // 65 is the char code for "A"
  function letterForIndex(index) {
    return String.fromCharCode(65 + index);
  }

  // Turn a number of metres into text, like "300 m" or "7.5 km".
  function radiusText(meters) {
    if (meters < 1000) {
      return meters + ' m';
    }
    var km = meters / 1000;
    return km + ' km';
  }

  // Build a blue pin with a letter inside it.
  // its an SVG image so we can pick our own colour and text
  function makePinIcon(letter) {
    var pinSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="48" viewBox="0 0 24 32">'
      + '<path d="M12 0C5.4 0 0 5.4 0 12c0 9 12 20 12 20s12-11 12-20C24 5.4 18.6 0 12 0z" '
      + 'fill="#1558A7"/>'
      + '<text x="12" y="12" text-anchor="middle" dominant-baseline="central" '
      + 'font-family="Arial, sans-serif" font-size="13" font-weight="700" fill="#ffffff">' + letter + '</text>'
      + '</svg>';

    return {
      url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(pinSvg),
      scaledSize: new google.maps.Size(27, 36),
      anchor: new google.maps.Point(13.5, 36)
    };
  }

  // Build a small white box (with blue text) that shows the radius.
  function makeRadiusLabelIcon(text) {
    var boxHeight = 18;
    var boxWidth = text.length * 7 + 12;

    var labelSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + boxWidth + '" height="' + boxHeight + '" '
      + 'viewBox="0 0 ' + boxWidth + ' ' + boxHeight + '">'
      + '<rect x="0.75" y="0.75" width="' + (boxWidth - 1.5) + '" height="' + (boxHeight - 1.5) + '" rx="4" '
      + 'fill="#ffffff" stroke="#1558A7" stroke-width="1.5"/>'
      + '<text x="' + (boxWidth / 2) + '" y="' + (boxHeight / 2) + '" text-anchor="middle" '
      + 'dominant-baseline="central" font-family="Arial, sans-serif" font-size="12" font-weight="700" '
      + 'fill="#1558A7">' + text + '</text>'
      + '</svg>';

    return {
      url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(labelSvg),
      scaledSize: new google.maps.Size(boxWidth, boxHeight),
      anchor: new google.maps.Point(boxWidth / 2, boxHeight + 6)
    };
  }

  // Keep a dragged radius inside the sliders smallest and largest numbers.
  function clampRadius(radiusInMeters) {
    if (radiusInMeters < Number(slider.min)) {
      radiusInMeters = Number(slider.min);
    }
    if (radiusInMeters > Number(slider.max)) {
      radiusInMeters = Number(slider.max);
    }
    return radiusInMeters;
  }

  // The slider is in metres. Below 1000 m show metres, otherwise kilometres.
  function updateValueLabel() {
    valueLabel.textContent = radiusText(Number(slider.value));
  }

  // Make the slider follow one circle, and show its letter next to the slider.
  function setActiveCircle(circle) {
    activeCircle = circle;
    slider.value = circle.logicalRadius;
    updateValueLabel();
    if (nameLabel) {
      nameLabel.textContent = 'Radius · ' + circle.letter;
    }
  }

  // Work out how big a circle looks on screen right now, as a radius in pixels.
  function circlePixelRadius(circle) {
    var lat = circle.areaCircle.getCenter().lat();
    // How many metres one pixel covers at this latitude and zoom level.
    var metersPerPixel = 156543.03392 * Math.cos(lat * Math.PI / 180) / Math.pow(2, map.getZoom());
    return circle.areaCircle.getRadius() / metersPerPixel;
  }

  // Hide circles controls when they gets too small to use. The circle and the center pin always stay.
  function updateHandleVisibility(circle) {
    var bigEnough = true;
    if (circlePixelRadius(circle) < minHandlePixels) {
      bigEnough = false;
    }

    circle.clearMarker.setVisible(bigEnough);
    circle.radiusLine.setVisible(bigEnough);
    circle.radiusLabel.setVisible(bigEnough);
    // The Done button also hides after it is pressed, until the circle changes.
    var showDone = bigEnough;
    if (circle.done) {
      showDone = false;
    }
    circle.doneButton.setVisible(showDone);
    for (var i = 0; i < circle.edgeMarkers.length; i++) {
      circle.edgeMarkers[i].setVisible(bigEnough);
    }
  }

  // Update the handle visibility for every circle (used when the map zooms).
  function updateAllHandleVisibility() {
    for (var i = 0; i < circles.length; i++) {
      updateHandleVisibility(circles[i]);
    }
  }

  // Mark every circle as done and hide all the Done buttons
  function markAllDone() {
    for (var i = 0; i < circles.length; i++) {
      circles[i].done = true;
      updateHandleVisibility(circles[i]);
    }
  }

  function restoreAllListings() {
    clearFilterIfAny();
    for (var i = 0; i < circles.length; i++) {
      circles[i].done = false;
      updateHandleVisibility(circles[i]);
    }
  }

  // if a circle has changed, its Done button comes back, and if a filter was showing we restore every listing
  function markCircleChanged(circle) {
    circle.done = false;
    updateHandleVisibility(circle);

    if (filterApplied) {
      restoreAllListings();
    }
  }

  // Draw the dashed line from the center to the right dot and show the radius.
  function updateRadiusLine(circle) {
    var center = circle.areaCircle.getCenter();
    var radius = circle.areaCircle.getRadius();

    var rightPosition = google.maps.geometry.spherical.computeOffset(center, radius, 90);
    circle.radiusLine.setPath([center, rightPosition]);

    // Put the label halfway along the line and write the radius on it
    var midPosition = google.maps.geometry.spherical.interpolate(center, rightPosition, 0.5);
    circle.radiusLabel.setPosition(midPosition);
    circle.radiusLabel.setIcon(makeRadiusLabelIcon(radiusText(circle.logicalRadius)));
  }

  // Move one circles edge dots, "X" and "Done" button back onto its edge.
  function updateEdgeMarkers(circle) {
    var center = circle.areaCircle.getCenter();
    var radius = circle.areaCircle.getRadius();

    for (var i = 0; i < circle.edgeMarkers.length; i++) {
      var edgePosition = google.maps.geometry.spherical.computeOffset(center, radius, edgeHeadings[i]);
      circle.edgeMarkers[i].setPosition(edgePosition);
    }

    var clearPosition = google.maps.geometry.spherical.computeOffset(center, radius, 45);
    circle.clearMarker.setPosition(clearPosition);

    var bottomPosition = google.maps.geometry.spherical.computeOffset(center, radius, 180);
    circle.doneButton.setPosition(bottomPosition);

    updateRadiusLine(circle);
  }

  // Draw one circle at its radius and place all of its handles on the edge.
  function applyCircleSize(circle) {
    circle.areaCircle.setRadius(circle.logicalRadius);
    updateEdgeMarkers(circle);
    updateHandleVisibility(circle);
  }

  // Make edge dot resize its circle while it is being dragged
  function addEdgeDotDrag(circle, edgeMarker) {
    edgeMarker.addListener('drag', function () {
      var center = circle.areaCircle.getCenter();
      var distance = google.maps.geometry.spherical.computeDistanceBetween(center, edgeMarker.getPosition());

      // The new radius is the distance from the center to the dragged dot.
      circle.logicalRadius = clampRadius(Math.round(distance / 100) * 100);

      // Dragging a dot makes that circle the active one for the slider, brings
      // its Done button back, and clears any showing filter
      setActiveCircle(circle);
      markCircleChanged(circle);
      applyCircleSize(circle);
    });

    // When the drag ends, snap every dot back onto its dezignated point on the
    // circle edge, so a dot never stays where the mouse was let go. (fixed, since was bugged before)
    edgeMarker.addListener('dragend', function () {
      updateEdgeMarkers(circle);
    });
  }

  function applyFilter() {
    if (typeof helpers.filterInCircle !== 'function') {
      return;
    }

    var areas = [];
    for (var i = 0; i < circles.length; i++) {
      areas.push({
        center: circles[i].areaCircle.getCenter(),
        radius: circles[i].logicalRadius
      });
    }

    helpers.filterInCircle(areas);
    filterApplied = true;
  }

  function clearFilterIfAny() {
    if (filterApplied) {
      if (typeof helpers.clearFilter === 'function') {
        helpers.clearFilter();
      }
      filterApplied = false;
    }
  }

  function createCircle() {
    var radiusInMeters = Number(slider.value);

    // Keep the whole circle tool above the listing (red) markers
    var handleZIndex = google.maps.Marker.MAX_ZINDEX + 2;
    var pinZIndex = google.maps.Marker.MAX_ZINDEX + 3;

    // The first one goes in the middle. Each extra one is offset so it does not spawn exactly on top of another.
    var center = map.getCenter();
    if (circles.length > 0) {
      var heading = (circles.length * 60) % 360;
      center = google.maps.geometry.spherical.computeOffset(center, radiusInMeters, heading);
    }

    var letter = letterForIndex(circles.length);

    var circle = {
      letter: letter,
      areaCircle: null,
      centerMarker: null,
      edgeMarkers: [],
      radiusLine: null,
      radiusLabel: null,
      clearMarker: null,
      doneButton: null,
      logicalRadius: radiusInMeters,
      done: false
    };

    circle.areaCircle = new google.maps.Circle({
      map: map,
      center: center,
      radius: radiusInMeters,
      editable: false,
      draggable: false,
      clickable: false,
      strokeColor: '#1558A7',
      strokeOpacity: 0.9,
      strokeWeight: 2,
      fillColor: '#1558A7',
      fillOpacity: 0.12
    });

    // Put our own LetMeRent marker in the middle of the circle.
    circle.centerMarker = new google.maps.Marker({
      map: map,
      position: center,
      icon: makePinIcon(letter),
      draggable: true,
      cursor: 'move',
      zIndex: pinZIndex
    });

    // Touching the pin makes this the active circle for the slider
    circle.centerMarker.addListener('dragstart', function () {
      setActiveCircle(circle);
      markCircleChanged(circle);
    });

    // When the marker is dragged, move the circles center to follow it.
    circle.centerMarker.addListener('drag', function () {
      circle.areaCircle.setCenter(circle.centerMarker.getPosition());
    });

    // When the circles center moves, keep the marker on top of it and move the four edge dots along with it
    circle.areaCircle.addListener('center_changed', function () {
      circle.centerMarker.setPosition(circle.areaCircle.getCenter());
      updateEdgeMarkers(circle);
    });

    var lineDash = {
      path: 'M 0,-1 0,1',
      strokeColor: '#1558A7',
      strokeOpacity: 1,
      scale: 2
    };

    // The dashed radius line from the center to the right edge dot
    circle.radiusLine = new google.maps.Polyline({
      map: map,
      path: [center, center],
      strokeOpacity: 0,
      clickable: false,
      icons: [
        {
          icon: lineDash,
          offset: '0',
          repeat: '10px'
        }
      ]
    });

    // A marker that shows the radius inside a small white box
    circle.radiusLabel = new google.maps.Marker({
      map: map,
      position: center,
      icon: makeRadiusLabelIcon(radiusText(radiusInMeters)),
      optimized: false,
      zIndex: handleZIndex
    });

    // A small round dot, used for the edge handles.
    var dotSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">'
      + '<circle cx="8" cy="8" r="6" fill="#ffffff" stroke="#1558A7" stroke-width="2"/>'
      + '</svg>';

    var dotIcon = {
      url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(dotSvg),
      scaledSize: new google.maps.Size(16, 16),
      anchor: new google.maps.Point(8, 8)
    };

    circle.edgeMarkers = [];
    for (var i = 0; i < edgeHeadings.length; i++) {
      var resizeCursor = 'ew-resize';
      if (edgeHeadings[i] === 0 || edgeHeadings[i] === 180) {
        resizeCursor = 'ns-resize';
      }

      var edgeMarker = new google.maps.Marker({
        map: map,
        position: center,
        icon: dotIcon,
        draggable: true,
        cursor: resizeCursor,
        zIndex: handleZIndex
      });

      // When this dot is dragged it resizes the circle.
      addEdgeDotDrag(circle, edgeMarker);

      circle.edgeMarkers.push(edgeMarker);
    }

    // X marker, used to remove this circle
    var clearSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">'
      + '<rect x="0.75" y="0.75" width="18.5" height="18.5" rx="3" fill="#ffffff" stroke="#1558A7" stroke-width="1.5"/>'
      + '<path d="M6.5 6.5 L13.5 13.5 M13.5 6.5 L6.5 13.5" stroke="#1558A7" stroke-width="1.5" stroke-linecap="round"/>'
      + '</svg>';

    var clearIcon = {
      url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(clearSvg),
      scaledSize: new google.maps.Size(20, 20),
      anchor: new google.maps.Point(10, 10)
    };

    // Build the "X" marker. Clicking it removes only this circle
    circle.clearMarker = new google.maps.Marker({
      map: map,
      position: center,
      icon: clearIcon,
      optimized: false,
      cursor: 'pointer',
      zIndex: handleZIndex
    });
    circle.clearMarker.addListener('click', function () {
      removeOneCircle(circle);
    });

    // Done button
    var doneSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="66" height="22" viewBox="0 0 66 22">'
      + '<rect x="0.75" y="0.75" width="64.5" height="20.5" rx="5" fill="#ffffff" stroke="#1558A7" stroke-width="1.5"/>'
      + '<path d="M11 11 l3.5 3.5 l6 -7" fill="none" stroke="#1558A7" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
      + '<text x="42" y="11" text-anchor="middle" dominant-baseline="central" '
      + 'font-family="Arial, sans-serif" font-size="13" font-weight="700" fill="#1558A7">Done</text>'
      + '</svg>';

    var doneIcon = {
      url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(doneSvg),
      scaledSize: new google.maps.Size(66, 22),
      anchor: new google.maps.Point(33, -10)
    };

    // Build the "Done" button. Clicking it filters the listings to the ones inside all of the circles on the map.
    circle.doneButton = new google.maps.Marker({
      map: map,
      position: center,
      icon: doneIcon,
      optimized: false,
      cursor: 'pointer',
      zIndex: handleZIndex
    });
    circle.doneButton.addListener('click', function () {
      // Pressing Done commits every circle to their locations, hide all the Done buttons
      applyFilter();
      markAllDone();
    });

    // make this circle the active one (for slider top left)
    circles.push(circle);
    setActiveCircle(circle);
  
    applyCircleSize(circle);
  }

  // Take a circles shapes and markers off the map.
  function destroyCircle(circle) {
    circle.areaCircle.setMap(null);
    circle.centerMarker.setMap(null);
    for (var i = 0; i < circle.edgeMarkers.length; i++) {
      circle.edgeMarkers[i].setMap(null);
    }
    circle.radiusLine.setMap(null);
    circle.radiusLabel.setMap(null);
    circle.clearMarker.setMap(null);
    circle.doneButton.setMap(null);
  }

  // Reformat the text on circles so the labels stay A, B, C...
  function relabelCircles() {
    for (var i = 0; i < circles.length; i++) {
      var letter = letterForIndex(i);
      circles[i].letter = letter;
      circles[i].centerMarker.setIcon(makePinIcon(letter));
    }
  }

  // Reset the tool back to its starting look (no circles).
  function resetTool() {
    activeCircle = null;
    controls.hidden = true;
    toggleButton.textContent = 'Draw area circle';
    if (nameLabel) {
      nameLabel.textContent = 'Radius';
    }
  }

  // Remove a single circle (its own "X")
  function removeOneCircle(circle) {
    destroyCircle(circle);

    // Take it out of the list.
    var index = circles.indexOf(circle);
    if (index !== -1) {
      circles.splice(index, 1);
    }

    // Keep the remaining labels in order (A, B, C ...)
    relabelCircles();

    // No circles left: reset everything and clear the filter
    if (circles.length === 0) {
      resetTool();
      clearFilterIfAny();
      return;
    }

    // If we removed an active circle, make the last one active instead
    if (activeCircle === circle) {
      setActiveCircle(circles[circles.length - 1]);
    }

    // A filter was showing, so refresh it with the circles that remain.
    if (filterApplied) {
      applyFilter();
    }
  }

  // Remove every circle and clear the filter
  function removeAllCircles() {
    for (var i = 0; i < circles.length; i++) {
      destroyCircle(circles[i]);
    }
    circles = [];
    resetTool();
    clearFilterIfAny();
  }

  // The main button adds a new circle each time it is clicked
  toggleButton.addEventListener('click', function () {
    createCircle();
    controls.hidden = false;
    toggleButton.textContent = 'Add area circle';
  });

  // Moving the slider changes the active circles radius
  slider.addEventListener('input', function () {
    updateValueLabel();
    if (activeCircle) {
      activeCircle.logicalRadius = Number(slider.value);
      markCircleChanged(activeCircle);
      applyCircleSize(activeCircle);
    }
  });

  // The remove button inside the controls clears every circle.
  removeButton.addEventListener('click', function () {
    removeAllCircles();
  });

  map.addListener('zoom_changed', function () {
    updateAllHandleVisibility();
  });

  updateValueLabel();
}

window.setupAreaCircle = setupAreaCircle;
