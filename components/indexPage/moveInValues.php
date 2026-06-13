<?php
// Shared move-in date values used by the search bar and results summary.
$selectedMoveIn = '';

if (isset($_GET['available_by'])) {
    $selectedMoveIn = trim($_GET['available_by']);
}

$selectedMoveInText = $selectedMoveIn;

if ($selectedMoveIn === '') {
    $selectedMoveInText = 'Any date';
}
?>
