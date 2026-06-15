(function () {
  var modal = document.querySelector('[data-gallery-modal]');
  // "Show All" button and the image container itself will open the popup
  var openElements = document.querySelectorAll('[data-gallery-open]');

  // Nothing to do if the popup or the open triggers are missing.
  if (!modal || openElements.length === 0) {
    return;
  }

  function openModal() {
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
  }

  // Open the popup when any open trigger is clicked.
  for (var j = 0; j < openElements.length; j++) {
    openElements[j].addEventListener('click', openModal);
  }

  // Any element marked with data-gallery-close should close the popup
  // (the backdrop and the X button).
  var closeElements = modal.querySelectorAll('[data-gallery-close]');
  for (var i = 0; i < closeElements.length; i++) {
    closeElements[i].addEventListener('click', closeModal);
  }

  // Also close when the user presses the Escape key.
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeModal();
    }
  });
})();
