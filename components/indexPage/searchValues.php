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

// The distance slider goes from 0 up to 20.
// The top of the slider (20) means "20+ km", which is treated as no distance limit, so an empty distance shows as "20+ km".
if ($selectedDistance === '') {
    $selectedDistanceSlider = '20';
    $selectedDistanceText = '20+ km';
} else {
    $selectedDistanceSlider = $selectedDistance;
    $selectedDistanceText = $selectedDistance . ' km';
}

if ($selectedCampus !== '' && $selectedDistance !== '') {
    $selectedCampusText = $selectedDistance . ' km from campus';
} else if ($selectedCampus !== '') {
    // A campus is picked but no distance limit, so show the unlimited label.
    $selectedCampusText = '20+ km from campus';
} else {
    $selectedCampusText = 'Any campus';
}

// The distance filter only really counts when we have a campus, its coordinates and a distance
$distanceFilterActive = false;
if ($selectedCampus !== '' && $selectedCampusLat !== '' && $selectedCampusLng !== '' && $selectedDistance !== '') {
    $distanceFilterActive = true;
}
