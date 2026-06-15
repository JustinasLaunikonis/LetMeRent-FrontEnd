<?php if ($galleryMain === ''): ?>
  <div class="<?php echo esc($galleryClass); ?>">
    <div class="gallery-main">
      <svg viewBox="0 0 460 376" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
        <rect width="460" height="376" fill="#B8C8D8"/>
        <rect x="0" y="210" width="460" height="166" fill="#A8B8C8"/>
        <rect x="50" y="58" width="360" height="260" fill="#8FA3B5" rx="2"/>
        <rect x="78" y="86" width="100" height="98" fill="#7A90A5" rx="1"/>
        <rect x="282" y="86" width="100" height="98" fill="#7A90A5" rx="1"/>
        <rect x="172" y="184" width="116" height="134" fill="#6A8095" rx="2"/>
        <path d="M172 184 Q230 156 288 184" stroke="#6A8095" stroke-width="3" fill="none"/>
        <rect x="50" y="316" width="360" height="8" rx="2" fill="#6A8095" opacity="0.5"/>
        <rect x="0" y="0" width="460" height="74" fill="#BFDBFE"/>
        <ellipse cx="36" cy="362" rx="36" ry="20" fill="#8AAE70" opacity="0.6"/>
      </svg>
    </div>
  </div>
<?php elseif ($galleryCount === 1): ?>
  <div class="<?php echo esc($galleryClass); ?>">
    <div class="gallery-main">
      <img src="<?php echo esc($galleryMain); ?>" alt="<?php echo esc($listingTitle); ?>">
    </div>
  </div>
<?php elseif ($galleryCount === 2): ?>
  <div class="<?php echo esc($galleryClass); ?> gallery--clickable" data-gallery-open>
    <div class="gallery-main">
      <img src="<?php echo esc($galleryImages[0]); ?>" alt="<?php echo esc($listingTitle); ?>">
    </div>
    <div class="gallery-side">
      <div class="gallery-cell">
        <img src="<?php echo esc($galleryImages[1]); ?>" alt="<?php echo esc($listingTitle); ?>">
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="<?php echo esc($galleryClass); ?> gallery--clickable" data-gallery-open>
    <div class="gallery-main">
      <img src="<?php echo esc($galleryImages[0]); ?>" alt="<?php echo esc($listingTitle); ?>">
    </div>
    <div class="gallery-side">
      <?php for ($i = 1; $i < $galleryCount; $i++): ?>
        <div class="gallery-cell">
          <img src="<?php echo esc($galleryImages[$i]); ?>" alt="<?php echo esc($listingTitle); ?>">
        </div>
      <?php endfor; ?>
    </div>
    <?php if ($totalImageCount > 3): ?>
      <button type="button" class="gallery-count" data-gallery-open>Show All</button>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($totalImageCount > 1): ?>
  <div class="gallery-modal" data-gallery-modal hidden>
    <div class="gallery-modal-backdrop" data-gallery-close></div>
    <button type="button" class="gallery-modal-close" data-gallery-close aria-label="Close gallery">&times;</button>
    <div class="gallery-modal-content">
      <?php foreach ($listingImages as $image): ?>
        <img src="<?php echo esc($image); ?>" alt="<?php echo esc($listingTitle); ?>">
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>
