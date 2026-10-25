/**
 * @file
 * CSS grid based masonry for the front page view (moni2026).
 *
 * Same algorithm as the memo theme; the resize loop bug (i > length) fixed.
 */
((Drupal, once) => {
  'use strict';

  function resizeMasonryItem(item) {
    const grid = item.closest('.masonry');
    if (!grid) {
      return;
    }
    const style = window.getComputedStyle(grid);
    const rowGap = parseInt(style.getPropertyValue('grid-row-gap'), 10) || 0;
    const rowHeight = parseInt(style.getPropertyValue('grid-auto-rows'), 10) || 0;
    const content = item.querySelector('.masonry-content');
    if (!content) {
      return;
    }
    const height = content.getBoundingClientRect().height;
    const rowSpan = Math.ceil((height + rowGap) / (rowHeight + rowGap)) + 3;
    item.style.gridRowEnd = `span ${rowSpan}`;
  }

  function resizeAll() {
    document.querySelectorAll('.masonry-brick').forEach(resizeMasonryItem);
  }

  ['load', 'resize'].forEach((event) => {
    window.addEventListener(event, resizeAll);
  });

  Drupal.behaviors.moni2026Masonry = {
    attach(context) {
      once('moni2026-masonry', '.masonry', context).forEach((grid) => {
        grid.querySelectorAll('.masonry-brick').forEach((item) => {
          if (typeof window.imagesLoaded === 'function') {
            window.imagesLoaded(item, () => resizeMasonryItem(item));
          }
          else {
            resizeMasonryItem(item);
          }
        });
      });
    },
  };
})(Drupal, once);
