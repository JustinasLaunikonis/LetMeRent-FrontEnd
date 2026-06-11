<div>
  <?php include __DIR__ . '/gallery.php'; ?>

  <div class="listing-title-row">
    <div class="listing-title">
      <p><?php echo esc($listingTitle); ?></p>
      <p><?php echo esc($listingTitleLocation); ?></p>
    </div>

    <div class="listing-price"><?php echo $listingPrice; ?> <span>/ mo</span></div>
  </div>

  <div class="listing-location">
    <p>&#128205; <?php echo esc($listingLocationAddress); ?> &#183;</p>
    <a href="<?php echo esc($sourceLink); ?>"<?php echo $sourceLinkTarget; ?>><?php echo esc($listingSource); ?></a>
    <p>&#183; <?php echo esc($listedText); ?></p>
  </div>

  <div class="chip-row">
    <?php if ($chips === []): ?>
      <div class="chip"><p>No listing tags available</p></div>
    <?php else: ?>
      <?php foreach ($chips as $chip): ?>
        <div class="chip"><p><?php echo $chip; ?></p></div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <hr class="section-divider">

  <div class="section-title">Description</div>
  <p class="desc-text"><?php echo esc($listingDescription); ?></p>

  <hr class="section-divider">

  <?php include __DIR__ . '/map_details.php'; ?>
</div>
