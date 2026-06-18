// Opens and closes the filter dropdown menus (Price, Rooms, Energy) in the
// filter bar. One generic handler wires every dropdown, so a new dropdown works
// automatically without needing its own script.

// Close every open dropdown except the one passed in.
function closeFilterDropdowns(exceptOptions) {
  var allOptions = document.querySelectorAll('.filter-bar .dropdown-options');

  for (var i = 0; i < allOptions.length; i++) {
    if (allOptions[i] !== exceptOptions) {
      allOptions[i].classList.remove('show');
    }
  }
}

// one dropdown: clicking its button shows or hides its options.
function setupFilterDropdown(dropdown) {
  var toggle = dropdown.querySelector('.dropdown-toggle');
  var options = dropdown.querySelector('.dropdown-options');

  if (!toggle || !options) {
    return;
  }

  toggle.addEventListener('click', function (event) {
    event.stopPropagation();
    var wasOpen = options.classList.contains('show');

    // Close the other dropdowns first, then toggle this one.
    closeFilterDropdowns(options);

    if (wasOpen) {
      options.classList.remove('show');
    } else {
      options.classList.add('show');
    }
  });
}

// Set up every dropdown in the filter bar.
var filterDropdowns = document.querySelectorAll('.filter-bar .dropdown');
for (var i = 0; i < filterDropdowns.length; i++) {
  setupFilterDropdown(filterDropdowns[i]);
}

// Clicking anywhere else on the page closes any open dropdown.
document.addEventListener('click', function () {
  closeFilterDropdowns(null);
});
