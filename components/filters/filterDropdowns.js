// Shared helpers for the filter dropdown pills.
function closeFilterDropdowns(exceptOptions) {
  var allOptions = document.querySelectorAll('.filter-bar .dropdown-options');

  for (var i = 0; i < allOptions.length; i++) {
    if (allOptions[i] !== exceptOptions) {
      allOptions[i].classList.remove('show');
    }
  }
}
