/**
 * @file
 * Slideshow visibility management.
 *
 * Handles intersection observer, viewport visibility detection,
 * and automatic pause/resume based on visibility.
 */

((Drupal) => {
  'use strict';

  /**
   * Debounce utility function.
   */
  function debounce(func, delay) {
    let timer;
    return function(...args) {
      clearTimeout(timer);
      timer = setTimeout(() => func.apply(this, args), delay);
    };
  }

  /**
   * Visibility manager class.
   */
  class SlideshowVisibility {
    constructor(container, slideshowCore) {
      this.container = container;
      this.core = slideshowCore;

      // Visibility state
      this.isVisible = true;
      this.previousVisibility = false;
      this.intersectionObserver = null;

      // Configuration
      this.visibilityThreshold = 0.5; // How much of slideshow must be visible
      this.scrollDebounceDelay = 200;
      this.resizeDebounceDelay = 200;

      this.init();
    }

    init() {
      this.setupIntersectionObserver();
      this.setupEventListeners();
      this.handleInitialVisibility();
    }

    /**
     * Set up intersection observer for efficient visibility detection.
     */
    setupIntersectionObserver() {
      if ('IntersectionObserver' in window) {
        this.intersectionObserver = new IntersectionObserver((entries) => {
          const entry = entries[0];
          this.handleVisibilityChange(entry.isIntersecting);
        }, {
          threshold: this.visibilityThreshold,
          rootMargin: '50px' // Start/stop slightly before element enters/leaves viewport
        });

        this.intersectionObserver.observe(this.container);
      } else {
        // Fallback for browsers without IntersectionObserver
        this.setupScrollFallback();
      }
    }

    /**
     * Handle visibility changes detected by intersection observer.
     */
    handleVisibilityChange(isVisible) {
      this.isVisible = isVisible;
      this.core.setVisibility(isVisible);

      // Dispatch event for other modules
      this.container.dispatchEvent(new CustomEvent('vvjs:visibilityChanged', {
        detail: { isVisible: isVisible }
      }));
    }

    /**
     * Set up event listeners for visibility-related events.
     */
    setupEventListeners() {
      // Page visibility (tab switching)
      document.addEventListener('visibilitychange', () => {
        this.handlePageVisibilityChange();
      });

      // Window resize (debounced)
      window.addEventListener('resize', debounce(() => {
        this.handleResize();
      }, this.resizeDebounceDelay));

      // Scroll events (fallback and additional detection)
      if (!this.intersectionObserver) {
        document.addEventListener('scroll', debounce(() => {
          this.handleScroll();
        }, this.scrollDebounceDelay));
      }
    }

    /**
     * Handle page visibility changes (tab switching, minimizing).
     */
    handlePageVisibilityChange() {
      if (document.hidden) {
        this.core.stopAutoSlide();
      } else {
        // Check if slideshow is still visible when tab becomes active
        const isCurrentlyVisible = this.checkElementVisibility();
        if (isCurrentlyVisible && !this.core.isPaused) {
          this.core.startAutoSlide();
        }
      }
    }

    /**
     * Handle window resize events.
     */
    handleResize() {
      // Recalculate slideshow dimensions
      this.core.adjustHeight();

      // Check visibility after resize
      const isCurrentlyVisible = this.checkElementVisibility();
      this.handleVisibilityChange(isCurrentlyVisible);
    }

    /**
     * Handle scroll events (fallback for browsers without IntersectionObserver).
     */
    handleScroll() {
      const isCurrentlyVisible = this.checkElementVisibility();

      if (isCurrentlyVisible !== this.previousVisibility) {
        this.previousVisibility = isCurrentlyVisible;
        this.handleVisibilityChange(isCurrentlyVisible);
      }
    }

    /**
     * Check if slideshow element is visible in viewport.
     */
    checkElementVisibility() {
      const rect = this.container.getBoundingClientRect();
      const windowHeight = window.innerHeight || document.documentElement.clientHeight;
      const windowWidth = window.innerWidth || document.documentElement.clientWidth;

      // Calculate visible area
      const visibleHeight = Math.min(rect.bottom, windowHeight) - Math.max(rect.top, 0);
      const visibleWidth = Math.min(rect.right, windowWidth) - Math.max(rect.left, 0);

      // Element is considered visible if threshold percentage is visible
      const visibilityRatio = (visibleHeight * visibleWidth) / (rect.height * rect.width);

      return visibilityRatio >= this.visibilityThreshold;
    }

    /**
     * Handle initial visibility state.
     */
    handleInitialVisibility() {
      // Check initial visibility
      this.isVisible = this.checkElementVisibility();
      this.previousVisibility = this.isVisible;

      // Set initial core visibility state
      this.core.setVisibility(this.isVisible);
    }

    /**
     * Force visibility check (useful for dynamic content changes).
     */
    forceVisibilityCheck() {
      const isCurrentlyVisible = this.checkElementVisibility();
      this.handleVisibilityChange(isCurrentlyVisible);
    }

    /**
     * Set custom visibility threshold.
     */
    setVisibilityThreshold(threshold) {
      this.visibilityThreshold = Math.max(0, Math.min(1, threshold));

      // Update intersection observer if available
      if (this.intersectionObserver) {
        this.intersectionObserver.disconnect();
        this.setupIntersectionObserver();
      }
    }

    /**
     * Get current visibility state.
     */
    getVisibilityState() {
      return {
        isVisible: this.isVisible,
        isPageVisible: !document.hidden,
        elementVisibility: this.checkElementVisibility()
      };
    }

    /**
     * Manually set visibility state (for testing or special cases).
     */
    setVisibility(visible) {
      this.handleVisibilityChange(visible);
    }

    /**
     * Set up scroll-based visibility detection fallback.
     */
    setupScrollFallback() {
      // More frequent checking for older browsers
      this.scrollDebounceDelay = 100;

      document.addEventListener('scroll', debounce(() => {
        this.handleScroll();
      }, this.scrollDebounceDelay));

      // Also check on load
      window.addEventListener('load', () => {
        this.handleInitialVisibility();
      });
    }

    /**
     * Clean up visibility detection.
     */
    destroy() {
      if (this.intersectionObserver) {
        this.intersectionObserver.disconnect();
        this.intersectionObserver = null;
      }

      // Event listeners are automatically cleaned up when the element is removed
      // but we could add explicit cleanup here if needed
    }
  }

  // Export to global namespace
  Drupal.vvjs = Drupal.vvjs || {};
  Drupal.vvjs.SlideshowVisibility = SlideshowVisibility;

})(Drupal);
