// Lets the user drag the handle between the sidebar and the map to make the sidebar wider or narrower.
// The width is capped at 30% of the screen width.
(function () {
  const MIN_WIDTH = 300;
  const MAX_SCREEN_FRACTION = 0.3;

  // Remember the chosen width across page changes (pagination reloads the page)
  const STORAGE_KEY = 'mapSidebarWidth';

  const layout = document.querySelector('.map-layout');
  const resizer = document.getElementById('map-resizer');
  const sidebar = document.querySelector('.map-sidebar');

  if (!layout || !resizer || !sidebar) {
    return;
  }

  // max 30% of the current window width
  function maxWidth() {
    return Math.round(window.innerWidth * MAX_SCREEN_FRACTION);
  }

  // Keep a requested width inside the allowed range
  function clampWidth(width) {
    const max = Math.max(MIN_WIDTH, maxWidth());
    if (width < MIN_WIDTH) {
      return MIN_WIDTH;
    }
    if (width > max) {
      return max;
    }
    return width;
  }

  function applyWidth(width) {
    const finalWidth = clampWidth(width);
    layout.style.setProperty('--map-sidebar-width', finalWidth + 'px');
    try {
      window.localStorage.setItem(STORAGE_KEY, String(finalWidth));
    } catch (error){
1      // Ignore write errors
    }
  }

  // Restore the width chosen on a previous page before the user interacts.
  (function restoreWidth() {
    let saved = null;
    try {
      saved = window.localStorage.getItem(STORAGE_KEY);
    } catch (error) {
      saved = null;
    }
    if (saved !== null && saved !== '') {
      const width = parseInt(saved, 10);
      if (!isNaN(width)) {
        layout.style.setProperty('--map-sidebar-width', clampWidth(width) + 'px');
      }
    }
  })();

  let dragging = false;

  function onPointerMove(event) {
    if (!dragging) {
      return;
    }
    
    const layoutLeft = layout.getBoundingClientRect().left;
    applyWidth(event.clientX - layoutLeft);
    event.preventDefault();
  }

  function stopDragging() {
    if (!dragging) {
      return;
    }
    dragging = false;
    resizer.classList.remove('dragging');
    document.body.classList.remove('map-resizing');
    document.removeEventListener('pointermove', onPointerMove);
    document.removeEventListener('pointerup', stopDragging);
  }

  resizer.addEventListener('pointerdown', (event) => {
    dragging = true;
    resizer.classList.add('dragging');
    document.body.classList.add('map-resizing');
    document.addEventListener('pointermove', onPointerMove);
    document.addEventListener('pointerup', stopDragging);
    event.preventDefault();
  });

  // If the window shrinks, the 30% cap may now be smaller than the current width.
  window.addEventListener('resize', () => {
    const currentWidth = sidebar.getBoundingClientRect().width;
    if (currentWidth > maxWidth()) {
      applyWidth(currentWidth);
    }
  });
})();
