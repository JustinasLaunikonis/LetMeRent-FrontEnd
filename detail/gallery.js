(function () {
  function setupGallery(root) {
    var track = root.querySelector('[data-gallery-track]');
    var slides = root.querySelectorAll('.gallery-slide');
    var prevButton = root.querySelector('[data-gallery-prev]');
    var nextButton = root.querySelector('[data-gallery-next]');
    var counterNode = root.querySelector('[data-gallery-counter]');

    if (!track || !prevButton || !nextButton || !counterNode || slides.length <= 1) {
      return;
    }

    var index = 0;

    function updateGallery() {
      track.style.transform = 'translateX(' + (-100 * index) + '%)';
      counterNode.textContent = (index + 1) + ' / ' + slides.length;
    }

    prevButton.addEventListener('click', function () {
      index -= 1;
      if (index < 0) {
        index = slides.length - 1;
      }
      updateGallery();
    });

    nextButton.addEventListener('click', function () {
      index += 1;
      if (index >= slides.length) {
        index = 0;
      }
      updateGallery();
    });

    updateGallery();
  }

  var galleries = document.querySelectorAll('[data-gallery]');
  for (var i = 0; i < galleries.length; i++) {
    setupGallery(galleries[i]);
  }
})();
