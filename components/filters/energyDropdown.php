<?php
// Energy label filter dropdown. The user picks an energy class from A to G.
// Selecting one puts ?energy_label=A in the URL and reloads the page
// The API matches any label that starts with that letter (so A, A+, A+++ all count as A).
// $_GET holds the current filters.

// Read the current selection from the URL.
$currentEnergy = '';
if (isset($_GET['energy_label'])) {
    $currentEnergy = strtoupper(trim($_GET['energy_label']));
}

// The classes the user can choose from.
$energyLetters = array('A', 'B', 'C', 'D', 'E', 'F', 'G');

// Work out the button label and whether the filter is on.
$energyLabel = 'Energy';
$energyActive = false;
if (in_array($currentEnergy, $energyLetters)) {
    $energyLabel = 'Energy: ' . $currentEnergy;
    $energyActive = true;
}

// Highlight the button when an energy filter is on.
if ($energyActive) {
    $energyToggleClass = 'dropdown-toggle active';
} else {
    $energyToggleClass = 'dropdown-toggle';
}

// Build the "Any" link (clears the energy filter).
$anyParams = $_GET;
unset($anyParams['page']);
unset($anyParams['energy_label']);
$anyHref = '?' . http_build_query($anyParams);
?>

<div class="dropdown" id="energy-dropdown">
  <div class="<?= $energyToggleClass ?>" id="energy-dropdown-toggle">
    <p><?= htmlspecialchars($energyLabel) ?></p>
    <span class="chev">↓</span>
  </div>
  
  <div class="dropdown-options" id="energy-dropdown-options">
    <a class="dropdown-option" href="<?= htmlspecialchars($anyHref) ?>">Any</a>
    <?php
    // One option per energy class, each keeping the other active filters.
    for ($i = 0; $i < count($energyLetters); $i++) {
        $letter = $energyLetters[$i];

        $params = $_GET;
        unset($params['page']);
        $params['energy_label'] = $letter;
        $href = '?' . http_build_query($params);

        echo '<a class="dropdown-option" href="' . htmlspecialchars($href) . '">' . htmlspecialchars($letter) . '</a>';
    }
    ?>
  </div>
</div>
