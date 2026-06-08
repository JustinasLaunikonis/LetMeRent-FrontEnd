// Opens and closes the Rooms dropdown menu.
var roomsToggle = document.getElementById('rooms-dropdown-toggle');
var roomsOptions = document.getElementById('rooms-dropdown-options');

if (roomsToggle && roomsOptions) {

  // Show or hide the options when the button is clicked.
  roomsToggle.addEventListener('click', function (event) {
    event.stopPropagation();
    roomsOptions.classList.toggle('show');
  });

  // Close the menu when the user clicks anywhere else on the page.
  document.addEventListener('click', function () {
    roomsOptions.classList.remove('show');
  });
}
