/**
 * @file
 * Core slideshow functionality.
 *
 * Handles the main slideshow logic including slide transitions,
 * state management, and core functionality.
 */

((Drupal) => {
  'use strict';

  /**
   * Core Slideshow class.
   */
  class SlideshowCore {
    constructor(container, options = {}) {
      this.container = container;
      this.slideshow = container.querySelector('.vvjs-items');
      this.slides = this.slideshow.querySelectorAll('.vvjs-item');

      // Configuration
      this.slideTime = parseInt(container.dataset.time, 10) || 5000;
      this.totalSlides = this.slides.length;
      this.loopingEnabled = container.dataset.enableLooping !== 'false';

      // State - use data-start-index if provided (1-indexed), default to 1
      const startIndex = parseInt(container.dataset.startIndex, 10) || 1;
      this.slideIndex = Math.max(1, Math.min(startIndex, this.totalSlides));
      this.currentSlideIndex = this.slideIndex; // Track for transition events
      this.isPaused = container.dataset.static === 'true';
      this.isVisible = true;
      this.autoSlideIntervalId = null;

      // Initialize
      this.init();
    }

    init() {
      this.updateSlideVisibility();
      this.adjustHeight();

      // Set initial paused class state
      this.container.classList.toggle('vvjs-is-paused', this.isPaused);

      // Listen for transition completion
      this.container.addEventListener('vvjs:transitionComplete', () => {
        this.updateAccessibilityAttributes();
        this.adjustHeight();
      });
    }

    /**
     * Updates slide visibility and accessibility attributes.
     */
    updateSlideVisibility() {
      const previousIndex = this.currentSlideIndex;
      const newIndex = this.slideIndex;

      // Dispatch event BEFORE transition - transitions module handles visuals
      this.container.dispatchEvent(new CustomEvent('vvjs:slideChanging', {
        detail: {
          fromIndex: previousIndex,
          toIndex: newIndex,
        },
      }));

      // Update current index
      this.currentSlideIndex = newIndex;

      // For instant transitions (default), update immediately
      // For crossfade, accessibility updates happen after transition completes
      const transitionType = this.container.dataset.transition || 'instant';
      if (transitionType === 'instant') {
        this.updateAccessibilityAttributes();
        this.adjustHeight();
      }
    }

    /**
     * Update accessibility attributes for all slides.
     *
     * Called after transition completes to ensure screen readers
     * don't announce hidden slides during crossfade.
     */
    updateAccessibilityAttributes() {
      this.slides.forEach((slide, index) => {
        const isActive = index + 1 === this.slideIndex;

        // For instant transitions, use display
        const transitionType = this.container.dataset.transition || 'instant';
        if (transitionType === 'instant') {
          slide.style.display = isActive ? 'block' : 'none';
        }

        // Accessibility attributes
        slide.setAttribute('aria-hidden', !isActive);
        slide.toggleAttribute('inert', !isActive);
        slide.classList.toggle('active', isActive);

        // Manage focus for interactive elements
        slide.querySelectorAll('a, button, input').forEach(el => {
          el.setAttribute('tabindex', isActive ? '0' : '-1');
        });
      });

      // Trigger events for other modules to respond to
      this.container.dispatchEvent(new CustomEvent('vvjs:slideChanged', {
        detail: { slideIndex: this.slideIndex, totalSlides: this.totalSlides },
      }));
    }

    /**
     * Adjusts slideshow height based on current slide content.
     */
    adjustHeight() {
      const currentSlide = this.slides[this.slideIndex - 1];
      if (!currentSlide) return;

      const transitionType = this.container.dataset.transition || 'instant';
      const computedStyle = window.getComputedStyle(this.slideshow);

      let contentHeight;

      // For crossfade, temporarily ensure slide is visible to measure
      if (transitionType.startsWith('crossfade')) {
        const prevOpacity = currentSlide.style.opacity;
        const prevZIndex = currentSlide.style.zIndex;

        currentSlide.style.opacity = '1';
        currentSlide.style.zIndex = '9999';

        const slideRect = currentSlide.getBoundingClientRect();
        contentHeight = slideRect.height;

        // Restore original values
        currentSlide.style.opacity = prevOpacity;
        currentSlide.style.zIndex = prevZIndex;
      }
      else {
        // Instant transition - direct measurement
        const slideRect = currentSlide.getBoundingClientRect();
        contentHeight = slideRect.height;
      }

      const paddingTop = parseFloat(computedStyle.paddingTop) || 0;
      const paddingBottom = parseFloat(computedStyle.paddingBottom) || 0;
      const borderTop = parseFloat(computedStyle.borderTopWidth) || 0;
      const borderBottom = parseFloat(computedStyle.borderBottomWidth) || 0;

      const totalHeight = contentHeight + paddingTop + paddingBottom + borderTop + borderBottom;
      this.slideshow.style.height = `${totalHeight}px`;
    }

    /**
     * Navigate to next slide.
     */
    nextSlide() {
      if (this.loopingEnabled) {
        this.slideIndex = (this.slideIndex % this.totalSlides) + 1;
      } else {
        // Stop at last slide when looping is disabled
        if (this.slideIndex < this.totalSlides) {
          this.slideIndex++;
        } else {
          // At last slide with no looping - stop auto-advance
          this.stopAutoSlide();
          return;
        }
      }
      this.updateSlideVisibility();
      this.adjustHeight();
    }

    /**
     * Navigate to previous slide.
     */
    prevSlide() {
      if (this.loopingEnabled) {
        this.slideIndex = (this.slideIndex === 1) ? this.totalSlides : this.slideIndex - 1;
      } else {
        // Stop at first slide when looping is disabled
        if (this.slideIndex > 1) {
          this.slideIndex--;
        } else {
          return;
        }
      }
      this.updateSlideVisibility();
      this.adjustHeight();
    }

    /**
     * Navigate to specific slide.
     */
    goToSlide(index) {
      if (index >= 1 && index <= this.totalSlides) {
        this.slideIndex = index;
        this.updateSlideVisibility();
        this.adjustHeight();
      }
    }

    /**
     * Start automatic slideshow.
     */
    startAutoSlide() {
      this.stopAutoSlide();
      if (this.slideTime > 0 && !this.isPaused && this.isVisible) {
        this.autoSlideIntervalId = setInterval(() => this.nextSlide(), this.slideTime);

        // Dispatch event for progress module
        this.container.dispatchEvent(new CustomEvent('vvjs:autoSlideStarted'));
      }
    }

    /**
     * Stop automatic slideshow.
     */
    stopAutoSlide() {
      if (this.autoSlideIntervalId) {
        clearInterval(this.autoSlideIntervalId);
        this.autoSlideIntervalId = null;

        // Dispatch event for progress module
        this.container.dispatchEvent(new CustomEvent('vvjs:autoSlideStopped'));
      }
    }

    /**
     * Pause/resume slideshow.
     */
    togglePause() {
      this.isPaused = !this.isPaused;

      // Add/remove paused class for CSS styling
      this.container.classList.toggle('vvjs-is-paused', this.isPaused);

      // IMPORTANT: Dispatch event BEFORE stopping/starting to ensure immediate response
      this.container.dispatchEvent(new CustomEvent('vvjs:pauseToggled', {
        detail: { isPaused: this.isPaused }
      }));

      if (this.isPaused) {
        this.stopAutoSlide();
      } else {
        this.startAutoSlide();
      }
    }

    /**
     * Set visibility state.
     */
    setVisibility(visible) {
      this.isVisible = visible;
      if (visible && !this.isPaused) {
        this.startAutoSlide();
      } else {
        this.stopAutoSlide();
      }
    }

    /**
     * Destroy slideshow and cleanup.
     */
    destroy() {
      this.stopAutoSlide();
      // Additional cleanup can be added here
    }
  }

  // Export to global namespace for other modules
  Drupal.vvjs = Drupal.vvjs || {};
  Drupal.vvjs.SlideshowCore = SlideshowCore;

})(Drupal);
