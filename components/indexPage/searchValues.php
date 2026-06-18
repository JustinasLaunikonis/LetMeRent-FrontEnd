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

// --- Campus + max distance from campus ---
$selectedCampus = '';
if (isset($_GET['campus'])) {
    $selectedCampus = trim($_GET['campus']);
}

$selectedCampusLat = '';
if (isset($_GET['campus_lat'])) {
    $selectedCampusLat = trim($_GET['campus_lat']);
}

$selectedCampusLng = '';
if (isset($_GET['campus_lng'])) {
    $selectedCampusLng = trim($_GET['campus_lng']);
}

$selectedDistance = '';
if (isset($_GET['max_distance_km'])) {
    $selectedDistance = trim($_GET['max_distance_km']);
}

if ($selectedCampus !== '' && $selectedDistance !== '') {
    $selectedCampusText = $selectedDistance . ' km from campus';
} else if ($selectedCampus !== '') {
    $selectedCampusText = 'Pick a distance';
} else {
    $selectedCampusText = 'Any campus';
}

// The distance filter only really counts when we have a campus, its coordinates and a distance
$distanceFilterActive = false;
if ($selectedCampus !== '' && $selectedCampusLat !== '' && $selectedCampusLng !== '' && $selectedDistance !== '') {
    $distanceFilterActive = true;
}
