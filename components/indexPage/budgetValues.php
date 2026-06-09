<?php
// Shared budget values used by the search bar and results summary.
$selectedMaxBudget = 950;

if ($max_price !== '' && is_numeric($max_price)) {
    $selectedMaxBudget = (int) $max_price;
}

$selectedMaxBudgetText = (string) $selectedMaxBudget;

if ($selectedMaxBudget >= 5000) {
    $selectedMaxBudgetText = '5000+';
}
?>
