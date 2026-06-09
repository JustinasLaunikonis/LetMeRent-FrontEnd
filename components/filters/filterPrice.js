// Opens and closes the Price dropdown menu.
var priceToggle = document.getElementById('price-dropdown-toggle');
var priceOptions = document.getElementById('price-dropdown-options');

if (priceToggle && priceOptions) {

  // Show or hide the options when the button is clicked.
  priceToggle.addEventListener('click', function (event) {
    event.stopPropagation();
    var priceWasOpen = priceOptions.classList.contains('show');
    closeFilterDropdowns(priceOptions);

    if (priceWasOpen) {
      priceOptions.classList.remove('show');
    } else {
      priceOptions.classList.add('show');
    }
  });

  // Close the menu when the user clicks anywhere else on the page.
  document.addEventListener('click', function () {
    priceOptions.classList.remove('show');
  });
}
