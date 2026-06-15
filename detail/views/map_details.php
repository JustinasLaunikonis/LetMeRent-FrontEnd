<div class="section-title">Location & Details</div>
<div class="map-box detail-google-map-box">
  <div id="detail-google-map" class="google-map detail-google-map"></div>
  <?php if ($detailMapApiKey === ''): ?>
    <div class="map-key-warning">
      Add GOOGLE_MAPS_API_KEY to your .env file to load the Google map.
    </div>
  <?php else: ?>
    <div id="detail-map-load-error" class="map-key-warning" hidden>
      Google Maps could not be loaded. Check your internet connection and API key.
    </div>
  <?php endif; ?>
</div>

<table class="commute-table">
  <tr>
    <td>Location</td>
    <td><?php echo esc($detailMapLocationText); ?></td>
  </tr>
  <tr>
    <td>Source</td>
    <td><?php echo esc($listingSource); ?></td>
  </tr>
  <tr>
    <td>Scraped</td>
    <td><?php echo esc($commuteListed); ?></td>
  </tr>
</table>
