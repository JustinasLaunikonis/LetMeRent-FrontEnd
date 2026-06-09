<?php
// Rooms filter dropdown. The user picks 1 room, 2 rooms or 3+ rooms.
// Selecting an option puts ?min_rooms / ?max_rooms in the URL and reloads the page
// $_GET holds the current filters.

// Read the current rooms selection from the URL.
$currentMinRooms = '';
if (isset($_GET['min_rooms'])) {
    $currentMinRooms = trim($_GET['min_rooms']);
}

$currentMaxRooms = '';
if (isset($_GET['max_rooms'])) {
    $currentMaxRooms = trim($_GET['max_rooms']);
}

// Work out which option is selected and the label to show on the button.
$roomsLabel = 'Rooms';
$roomsActive = false;

if ($currentMinRooms === '1' && $currentMaxRooms === '1') {
    $roomsLabel = '1 room';
    $roomsActive = true;
} elseif ($currentMinRooms === '2' && $currentMaxRooms === '2') {
    $roomsLabel = '2 rooms';
    $roomsActive = true;
} elseif ($currentMinRooms === '3' && $currentMaxRooms === '') {
    $roomsLabel = '3+ rooms';
    $roomsActive = true;
}

// Highlight the button when a rooms filter is on.
if ($roomsActive) {
    $roomsToggleClass = 'dropdown-toggle active';
} else {
    $roomsToggleClass = 'dropdown-toggle';
}

// Build the link for "Any" (clears the rooms filter).
$anyParams = $_GET;
unset($anyParams['page']);
unset($anyParams['min_rooms'], $anyParams['max_rooms']);
$anyHref = '?' . http_build_query($anyParams);

// Build the link for "1 room".
$oneParams = $_GET;
unset($oneParams['page']);
$oneParams['min_rooms'] = 1;
$oneParams['max_rooms'] = 1;
$oneHref = '?' . http_build_query($oneParams);

// Build the link for "2 rooms".
$twoParams = $_GET;
unset($twoParams['page']);
$twoParams['min_rooms'] = 2;
$twoParams['max_rooms'] = 2;
$twoHref = '?' . http_build_query($twoParams);

// Build the link for "3+ rooms" (3 or more, so no upper limit).
$threeParams = $_GET;
unset($threeParams['page']);
$threeParams['min_rooms'] = 3;
unset($threeParams['max_rooms']);
$threeHref = '?' . http_build_query($threeParams);
?>

<div class="dropdown" id="rooms-dropdown">
  <div class="<?= $roomsToggleClass ?>" id="rooms-dropdown-toggle">
    <p><?= htmlspecialchars($roomsLabel) ?></p>
    <span class="chev">↓</span>
  </div>
  <div class="dropdown-options" id="rooms-dropdown-options">
    <a class="dropdown-option" href="<?= htmlspecialchars($anyHref) ?>">Any</a>
    <a class="dropdown-option" href="<?= htmlspecialchars($oneHref) ?>">1 room</a>
    <a class="dropdown-option" href="<?= htmlspecialchars($twoHref) ?>">2 rooms</a>
    <a class="dropdown-option" href="<?= htmlspecialchars($threeHref) ?>">3+ rooms</a>
  </div>
</div>
