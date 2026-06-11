<div class="<?php echo esc($galleryClass); ?>">
  <div class="gallery-main">
    <?php if ($galleryMain !== ''): ?>
      <img src="<?php echo esc($galleryMain); ?>" alt="<?php echo esc($listingTitle); ?>">
    <?php else: ?>
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
    <?php endif; ?>
  </div>

  <?php if (!$hasSingleGalleryImage): ?>
    <div class="gallery-thumb">
      <?php if ($galleryThumb1 !== ''): ?>
        <img src="<?php echo esc($galleryThumb1); ?>" alt="<?php echo esc($listingTitle); ?>">
      <?php else: ?>
        <svg viewBox="0 0 200 228" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
          <rect width="200" height="228" fill="#9AA8B8"/>
          <rect x="18" y="38" width="164" height="152" fill="#7A8A9A"/>
          <rect x="38" y="58" width="124" height="112" fill="#6A7A8A"/>
        </svg>
      <?php endif; ?>
    </div>

    <div class="gallery-thumb">
      <?php if ($galleryThumb2 !== ''): ?>
        <img src="<?php echo esc($galleryThumb2); ?>" alt="<?php echo esc($listingTitle); ?>">
        <?php if ($extraPhotoCount > 0): ?>
          <div class="gallery-count">+<?php echo esc((string) $extraPhotoCount); ?> photos</div>
        <?php endif; ?>
      <?php else: ?>
        <svg viewBox="0 0 200 148" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
          <rect width="200" height="148" fill="#C8D0B8"/>
          <rect x="10" y="18" width="180" height="112" fill="#B0B8A0"/>
          <rect x="28" y="38" width="68" height="68" fill="#9AA88A"/>
          <rect x="106" y="38" width="68" height="68" fill="#9AA88A"/>
        </svg>
        <?php if ($extraPhotoCount > 0): ?>
          <div class="gallery-count">+<?php echo esc((string) $extraPhotoCount); ?> photos</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
