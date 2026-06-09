<?php
// Shared city values used by the search bar and results summary.
$selectedCity = 'Emmen';

if (isset($_GET['city'])) {
    $selectedCity = $city;
}

$selectedCityText = $selectedCity;

if ($selectedCity === '') {
    $selectedCityText = 'Any city';
}
?>
