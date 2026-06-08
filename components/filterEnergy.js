// Opens and closes the Energy dropdown menu.
var energyToggle = document.getElementById('energy-dropdown-toggle');
var energyOptions = document.getElementById('energy-dropdown-options');

if (energyToggle && energyOptions) {

  // Show or hide the options when the button is clicked.
  energyToggle.addEventListener('click', function (event) {
    event.stopPropagation();
    energyOptions.classList.toggle('show');
  });

  // Close the menu when the user clicks anywhere else on the page.
  document.addEventListener('click', function () {
    energyOptions.classList.remove('show');
  });
}
