<?php
// Works out the values the search bar and the results summary show.
// It reads the current filters (already parsed in listings.php): $city and $max_price come from there, the move-in date is read straight from the URL.

// --- City ---
$selectedCity = '';
if (isset($_GET['city'])) {
    $selectedCity = $city;
}

$selectedCityText = $selectedCity;
if ($selectedCity === '') {
    $selectedCityText = 'Any city';
}

// --- Max budget ---
$selectedMaxBudget = 950;
if ($max_price !== '' && is_numeric($max_price)) {
    $selectedMaxBudget = (int) $max_price;
}

$selectedMaxBudgetText = (string) $selectedMaxBudget;
if ($selectedMaxBudget >= 5000) {
    // 5000 means "no upper limit", shown as "5000+".
    $selectedMaxBudgetText = '5000+';
}

// --- Move-in date ---
$selectedMoveIn = '';
if (isset($_GET['available_by'])) {
    $selectedMoveIn = trim($_GET['available_by']);
}

$selectedMoveInText = $selectedMoveIn;
if ($selectedMoveIn === '') {
    $selectedMoveInText = 'Any date';
}
