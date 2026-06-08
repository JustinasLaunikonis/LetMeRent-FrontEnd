<?php
// Price sort dropdown. The user picks how the listings are ordered by price.
// Selecting an option puts ?sort=price and ?order=asc/desc in the URL and reloads the page
// "Default" clears the price sort
// The variables $sort and $order come from components/listings/listings.php.

// Work out which option is selected and the label to show on the button.
$priceLabel = 'Price';
$priceActive = false;

if ($sort === 'price' && $order === 'asc') {
    $priceLabel = 'Price: low to high';
    $priceActive = true;
} elseif ($sort === 'price' && $order === 'desc') {
    $priceLabel = 'Price: high to low';
    $priceActive = true;
}

// Highlight the button when a price sort is on.
if ($priceActive) {
    $priceToggleClass = 'dropdown-toggle active';
} else {
    $priceToggleClass = 'dropdown-toggle';
}

// Build the link for "Default" (clears the price sort).
$defaultParams = $_GET;
unset($defaultParams['page']); // any filter change resets back to page 1
unset($defaultParams['sort'], $defaultParams['order']);
$defaultHref = '?' . http_build_query($defaultParams);

// Build the link for "Price: low to high".
$ascParams = $_GET;
unset($ascParams['page']);
$ascParams['sort']  = 'price';
$ascParams['order'] = 'asc';
$ascHref = '?' . http_build_query($ascParams);

// Build the link for "Price: high to low".
$descParams = $_GET;
unset($descParams['page']);
$descParams['sort']  = 'price';
$descParams['order'] = 'desc';
$descHref = '?' . http_build_query($descParams);
?>

<div class="dropdown" id="price-dropdown">
  <div class="<?= $priceToggleClass ?>" id="price-dropdown-toggle">
    <p><?= htmlspecialchars($priceLabel) ?></p>
    <span class="chev">↓</span>
  </div>
  <div class="dropdown-options" id="price-dropdown-options">
    <a class="dropdown-option" href="<?= htmlspecialchars($defaultHref) ?>">Default</a>
    <a class="dropdown-option" href="<?= htmlspecialchars($ascHref) ?>">Price: low to high</a>
    <a class="dropdown-option" href="<?= htmlspecialchars($descHref) ?>">Price: high to low</a>
  </div>
</div>
