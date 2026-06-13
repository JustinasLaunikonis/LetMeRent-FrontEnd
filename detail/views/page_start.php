<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LetMeRent - <?php echo esc($listingTitle); ?></title>
  <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="detail.css">
</head>

<body>
  <nav class="nav">
    <a class="nav-logo" href="../index.php">
      <div class="logo-icon">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
          <path d="M2 8L9 2L16 8V16H11V12H7V16H2V8Z" fill="white"/>
        </svg>
      </div>
      <p>LetMeRent</p>
    </a>

    <ul class="nav-links">
      <li><a href="../index.php">Browse</a></li>
      <li><a href="../map/map.php">Map View</a></li>
      <li><a href="../profile/profile.php">My Alerts</a></li>
    </ul>

    <div class="nav-right">
      <div class="nav-bell">&#128276;</div>
      <a href="../profile/profile.php" class="nav-avatar">JL</a>
    </div>
  </nav>

  <div class="breadcrumb">
    <a href="../index.php">Back to results</a>
    <span class="sep">&gt;</span>
    <span class="cur"><?php echo esc($listingTitle); ?></span>
  </div>

  <?php if ($pageError !== null): ?>
    <div class="detail-alert"><?php echo esc($pageError); ?></div>
  <?php endif; ?>

  <div class="detail-wrap">
