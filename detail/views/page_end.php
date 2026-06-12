  </div>

  <script>
    window.letMeRentDetailMapConfig = {
      hasCoordinates: <?php echo $detailMapHasCoordinates ? 'true' : 'false'; ?>,
      latitude: <?php echo json_encode($detailMapLatitude); ?>,
      longitude: <?php echo json_encode($detailMapLongitude); ?>,
      zoom: <?php echo json_encode($detailMapZoom); ?>,
      title: <?php echo json_encode($listingTitle); ?>
    };
  </script>
  <script src="gallery.js"></script>
  <script src="detailMap.js"></script>
  <?php if ($detailMapApiKey !== ''): ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars(rawurlencode($detailMapApiKey)); ?>&callback=initLetMeRentDetailMap" async defer onerror="showDetailMapLoadError()"></script>
  <?php endif; ?>
</body>
</html>
