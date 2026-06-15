<div>
  <div class="sidebar-card">
    <div class="section-title">Listing Info</div>
    <div class="sidebar-facts">
      <?php foreach ($sidebarFacts as $fact): ?>
        <div class="sidebar-fact">
          <span><?php echo esc($fact['label']); ?></span>
          <strong><?php echo esc($fact['value']); ?></strong>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($listingUrl !== ''): ?>
      <a class="apply-btn" href="<?php echo esc($listingUrl); ?>" target="_blank" rel="noopener noreferrer">Browse <?php echo esc($listingSource); ?></a>
    <?php else: ?>
      <button class="apply-btn" disabled>Browse <?php echo esc($listingSource); ?></button>
    <?php endif; ?>
  </div>

  <div class="sidebar-card">
    <div class="section-title">Rental Site</div>
    <div class="landlord-row">
      <div class="landlord-avatar"><?php echo esc($landlordInitial); ?></div>
      <div>
        <div class="landlord-name"><?php echo esc($landlordName); ?></div>
        <div class="landlord-meta"><?php echo esc($listingSource); ?> &#183; <?php echo esc($landlordCityText); ?></div>
      </div>
    </div>
  </div>
</div>
