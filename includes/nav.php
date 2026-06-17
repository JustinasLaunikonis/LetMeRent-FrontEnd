<?php
// Shared top navbar. Before including this file a page may set:
//   $navBase        relative path back to the site root ('' on index, '../' elsewhere)
//   $navActive      which menu item to highlight: 'browse', 'map', 'alerts' or ''
//   $navBrowseHref  the Browse link (defaults to <base>index.php)
//   $navMapHref     the Map View link (defaults to <base>map/map.php)
//   $navProfileHref the profile / avatar link (defaults to <base>profile/profile.php)
//   $navAvatar      the text shown in the round avatar (defaults to 'AA')

// The Browse and Map links carry the current filters between the browse and map pages, which is why those two pages pass their own hrefs.

if (!isset($navBase)) {
    $navBase = '';
}
if (!isset($navActive)) {
    $navActive = '';
}
if (!isset($navBrowseHref)) {
    $navBrowseHref = $navBase . 'index.php';
}
if (!isset($navMapHref)) {
    $navMapHref = $navBase . 'map/map.php';
}
if (!isset($navProfileHref)) {
    $navProfileHref = $navBase . 'profile/profile.php';
}

// Helper that knows who is logged in (used for the avatar and the logged-out "Sign in" button below).
require_once __DIR__ . '/userInitials.php';

$navLoggedIn = userIsLoggedIn();

if (!isset($navAvatar)) {
    $navAvatar = sessionUserInitials();
}

// Where the "Sign in" button should point for logged-out visitors.
$navSignInHref = $navBase . 'sign-up-in/signin.php';

// Work out the "active" highlight for each menu item.
$browseActive = '';
if ($navActive === 'browse') {
    $browseActive = ' class="active"';
}
$mapActive = '';
if ($navActive === 'map') {
    $mapActive = ' class="active"';
}
$alertsActive = '';
if ($navActive === 'alerts') {
    $alertsActive = ' class="active"';
}
?>

<!-- Nav Bar -->
<nav class="nav">
  <a class="nav-logo" href="<?= htmlspecialchars($navBase . 'index.php', ENT_QUOTES) ?>">
    <div class="logo-icon">
      <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
        <!-- Let-Me-Rent Logo -->
        <path d="M2 8L9 2L16 8V16H11V12H7V16H2V8Z" fill="white"/>
      </svg>
    </div>
    <p>LetMeRent</p>
  </a>

  <ul class="nav-links">
    <li><a href="<?= htmlspecialchars($navBrowseHref, ENT_QUOTES) ?>"<?= $browseActive ?>>Browse</a></li>
    <li><a href="<?= htmlspecialchars($navMapHref, ENT_QUOTES) ?>"<?= $mapActive ?>>Map View</a></li>
    <li><a href="<?= htmlspecialchars($navProfileHref, ENT_QUOTES) ?>"<?= $alertsActive ?>>My Alerts</a></li>
  </ul>

  <div class="nav-right">
    <?php if ($navLoggedIn): ?>
      <!-- Logged in: show the user's avatar. -->
      <a href="<?= htmlspecialchars($navProfileHref, ENT_QUOTES) ?>" class="nav-avatar"><?= htmlspecialchars($navAvatar) ?></a>
    <?php else: ?>
      <!-- Logged out: no fake avatar. Show a clear "Sign in" button instead. -->
      <a href="<?= htmlspecialchars($navSignInHref, ENT_QUOTES) ?>" class="nav-signin">Sign in</a>
    <?php endif; ?>
  </div>
</nav>
