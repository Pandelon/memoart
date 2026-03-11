/**
 * @file
 * Slideshow accessibility features.
 *
 * Handles screen reader announcements, keyboard navigation,
 * reduced motion preferences, and ARIA attributes.
 */

((Drupal) => {
  'use strict';

  /**
   * Accessibility manager class.
   */
  class SlideshowAccessibility {
    constructor(container, slideshowCore) {
      this.container = container;
      this.core = slideshowCore;
      this.announcer = container.querySelector('.announcer');

      // Configuration - read from data attributes, default to true if not specified
      this.keyboardEnabled = container.dataset.enableKeyboard !== 'false';

      this.init();
    }

    init() {
      if (this.keyboardEnabled) {
        this.setupKeyboardNavigation();
      }
      this.applyReducedMotion();
      this.setupScreenReaderSupport();

      // Listen for slide changes to update announcements
      this.container.addEventListener('vvjs:slideChanged', (e) => {
        this.announceSlide(e.detail.slideIndex, e.detail.totalSlides);
      });
    }

    /**
     * Set up keyboard navigation for the slideshow.
     */
    setupKeyboardNavigation() {
      document.addEventListener('keydown', (e) => {
        // Skip handling if focused element is an input, textarea, or contenteditable
        if (e.target.closest('input, textarea, [contenteditable="true"]')) {
          return;
        }

        // Only handle keyboard navigation if the slideshow is in focus or visible
        if (!this.isSlideshowFocused()) {
          return;
        }

        switch (e.key) {
          case 'ArrowRight':
            e.preventDefault();
            this.core.nextSlide();
            this.core.startAutoSlide();
            break;

          case 'ArrowLeft':
            e.preventDefault();
            this.core.prevSlide();
            this.core.startAutoSlide();
            break;

          case ' ':
          case 'Spacebar':
            e.preventDefault();
            this.core.togglePause();
            break;

          case 'Home':
            e.preventDefault();
            this.core.goToSlide(1);
            this.core.startAutoSlide();
            break;

          case 'End':
            e.preventDefault();
            this.core.goToSlide(this.core.totalSlides);
            this.core.startAutoSlide();
            break;
        }
      });
    }

    /**
     * Check if slideshow is currently focused or should receive keyboard input.
     */
    isSlideshowFocused() {
      const activeElement = document.activeElement;

      // Check if any element within the slideshow has focus
      if (this.container.contains(activeElement)) {
        return true;
      }

      // Check if slideshow is visible in viewport (simple check)
      const rect = this.container.getBoundingClientRect();
      const isVisible = rect.top < window.innerHeight && rect.bottom > 0;

      return isVisible;
    }

    /**
     * Apply reduced motion preferences.
     */
    applyReducedMotion() {
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        // Pause autoplay for users who prefer reduced motion
        this.core.isPaused = true;
        this.core.stopAutoSlide();

        // Add class for CSS to reduce animations
        this.container.classList.add('reduced-motion');

        // Update play button to show play state
        const playPauseButton = this.container.querySelector('.play-pause-button');
        if (playPauseButton) {
          playPauseButton.innerHTML = this.getPlayIcon();
          playPauseButton.setAttribute('aria-label', 'Play slideshow');
        }

        // Announce to screen readers
        this.announceMessage('Slideshow paused due to reduced motion preference');
      }
    }

    /**
     * Set up screen reader support and announcements.
     */
    setupScreenReaderSupport() {
      // Ensure announcer element exists
      if (!this.announcer) {
        this.createAnnouncer();
      }

      // Initial announcement
      this.announceSlide(1, this.core.totalSlides);
    }

    /**
     * Create announcer element if it doesn't exist.
     */
    createAnnouncer() {
      this.announcer = document.createElement('div');
      this.announcer.className = 'announcer visually-hidden';
      this.announcer.setAttribute('aria-live', 'polite');
      this.announcer.setAttribute('aria-atomic', 'true');
      this.container.insertBefore(this.announcer, this.container.firstChild);
    }

    /**
     * Announce slide change to screen readers.
     */
    announceSlide(slideIndex, totalSlides) {
      if (this.announcer) {
        this.announcer.textContent = `Slide ${slideIndex} of ${totalSlides}`;
      }
    }

    /**
     * Announce custom message to screen readers.
     */
    announceMessage(message) {
      if (this.announcer) {
        // Temporarily change the text to trigger announcement
        const originalText = this.announcer.textContent;
        this.announcer.textContent = message;

        // Restore original text after a brief delay
        setTimeout(() => {
          this.announcer.textContent = originalText;
        }, 1000);
      }
    }

    /**
     * Update ARIA attributes for slideshow state.
     */
    updateAriaAttributes(isPlaying) {
      const region = this.container.querySelector('[role="region"]');
      if (region) {
        region.setAttribute('aria-live', isPlaying ? 'off' : 'polite');
      }
    }

    /**
     * Get play icon SVG (duplicate from navigation module for independence).
     */
    getPlayIcon() {
      return `
        <svg class="svg-play" xmlns="http://www.w3.org/2000/svg" viewBox="80 -880 800 800" fill="currentColor">
          <path d="m380-300 280-180-280-180v360ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"></path>
        </svg>`;
    }

    /**
     * Handle tab navigation within slideshow.
     */
    manageFocus() {
      const currentSlide = this.container.querySelector('.vvjs-item.active');
      if (currentSlide) {
        const focusableElements = currentSlide.querySelectorAll(
          'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])'
        );

        // Ensure only current slide elements are focusable
        focusableElements.forEach(el => {
          el.setAttribute('tabindex', '0');
        });
      }
    }
  }

  // Export to global namespace
  Drupal.vvjs = Drupal.vvjs || {};
  Drupal.vvjs.SlideshowAccessibility = SlideshowAccessibility;

})(Drupal);
