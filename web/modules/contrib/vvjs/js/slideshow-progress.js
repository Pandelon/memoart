/**
 * @file
 * IMMEDIATE FIX: Enhanced slideshow-progress.js that stops immediately
 *
 * This version ensures progress stops instantly when pause is clicked,
 * matching the original behavior exactly.
 */

((Drupal) => {
  'use strict';

  /**
   * Enhanced Progress indicator class with immediate stop.
   */
  class SlideshowProgress {
    constructor(container, slideshowCore) {
      this.container = container;
      this.core = slideshowCore;
      this.progressBar = container.querySelector('.progressbar');
      this.showProgress = container.dataset.showSlideProgress === 'true';

      // Progress state
      this.progressIntervalId = null;
      this.slideStartTime = Date.now();
      this.isActive = false;

      this.init();
    }

    init() {
      if (!this.showProgress || !this.progressBar) {
        return;
      }

      this.setupProgressBar();
      this.bindEvents();

      // IMMEDIATE: Add direct pause listener to container for instant response
      this.container.addEventListener('click', (e) => {
        if (e.target.closest('.play-pause-button')) {
          // Stop progress IMMEDIATELY when pause button is clicked
          this.immediateStop();
        }
      });

      // IMMEDIATE: Add direct mouse event listeners for instant hover response
      const slideshow = this.container.querySelector('.vvjs-items');
      if (slideshow) {
        slideshow.addEventListener('mouseenter', () => {
          this.immediateStop();
        });

        slideshow.addEventListener('mouseleave', () => {
          // Don't auto-resume - let the core handle this through events
          // The progress will restart when the core starts auto-slide
        });
      }
    }

    /**
     * IMMEDIATE STOP - stops progress instantly
     */
    immediateStop() {
      if (this.progressIntervalId) {
        clearInterval(this.progressIntervalId);
        this.progressIntervalId = null;
        this.isActive = false;
      }
    }

    /**
     * Set up initial progress bar attributes.
     */
    setupProgressBar() {
      this.progressBar.setAttribute('role', 'progressbar');
      this.progressBar.setAttribute('aria-valuenow', '0');
      this.progressBar.setAttribute('aria-valuemin', '0');
      this.progressBar.setAttribute('aria-valuemax', '100');
      this.progressBar.setAttribute('aria-label', 'Slide progress');
    }

    /**
     * Bind event listeners.
     */
    bindEvents() {
      // Listen for slide changes to restart progress
      this.container.addEventListener('vvjs:slideChanged', () => {
        if (!this.core.isPaused) {
          this.startProgress();
        }
      });

      // Listen for pause/play events
      this.container.addEventListener('vvjs:pauseToggled', (e) => {
        if (e.detail.isPaused) {
          this.immediateStop(); // Use immediate stop
        } else {
          this.resumeProgress();
        }
      });

      // IMMEDIATE: Listen for auto-slide events (mouse hover, visibility changes)
      this.container.addEventListener('vvjs:autoSlideStopped', () => {
        this.immediateStop();
      });

      this.container.addEventListener('vvjs:autoSlideStarted', () => {
        if (!this.core.isPaused) {
          this.startProgress();
        }
      });

      // Listen for mouse events for additional responsiveness
      this.container.addEventListener('vvjs:mouseEnter', () => {
        this.immediateStop();
      });

      this.container.addEventListener('vvjs:mouseLeave', () => {
        // Progress will restart via autoSlideStarted event
      });
    }

    /**
     * Start progress bar animation.
     */
    startProgress() {
      if (!this.showProgress || !this.progressBar || this.core.slideTime <= 0) {
        return;
      }

      this.immediateStop(); // Ensure clean start
      this.slideStartTime = Date.now();
      this.isActive = true;

      this.progressIntervalId = setInterval(() => {
        if (this.isActive && !this.core.isPaused) {
          this.updateProgress();
        } else {
          this.immediateStop();
        }
      }, 50);
    }

    /**
     * Update progress bar based on elapsed time.
     */
    updateProgress() {
      if (!this.isActive) return;

      const elapsed = Date.now() - this.slideStartTime;
      const progress = Math.min(100, (elapsed / this.core.slideTime) * 100);

      // Update CSS custom property for styling
      this.progressBar.style.setProperty('--progress', `${progress}%`);

      // Update ARIA attributes
      this.progressBar.setAttribute('aria-valuenow', Math.round(progress));

      // Clear interval when complete
      if (progress >= 100) {
        this.immediateStop();
      }
    }

    /**
     * Pause progress animation - IMMEDIATE.
     */
    pauseProgress() {
      this.immediateStop();
    }

    /**
     * Resume progress animation from current position.
     */
    resumeProgress() {
      if (!this.core.isPaused && this.showProgress) {
        // Calculate remaining time based on current progress
        const currentProgress = parseFloat(this.progressBar.getAttribute('aria-valuenow') || '0');

        if (currentProgress < 100) {
          const remainingTime = this.core.slideTime * (1 - currentProgress / 100);

          // Adjust start time to account for progress already made
          this.slideStartTime = Date.now() - (this.core.slideTime - remainingTime);
          this.isActive = true;

          this.progressIntervalId = setInterval(() => {
            if (this.isActive && !this.core.isPaused) {
              this.updateProgress();
            } else {
              this.immediateStop();
            }
          }, 50);
        }
      }
    }

    /**
     * Clear progress interval - alias for immediateStop.
     */
    clearProgress() {
      this.immediateStop();
    }

    /**
     * Reset progress to zero.
     */
    resetProgress() {
      this.immediateStop();
      if (this.progressBar) {
        this.progressBar.style.setProperty('--progress', '0%');
        this.progressBar.setAttribute('aria-valuenow', '0');
      }
    }

    /**
     * Get current progress as percentage.
     */
    getCurrentProgress() {
      if (!this.progressBar) {
        return 0;
      }

      return parseFloat(this.progressBar.getAttribute('aria-valuenow') || '0');
    }

    /**
     * Cleanup when slideshow is destroyed.
     */
    destroy() {
      this.immediateStop();
    }
  }

  // Export to global namespace
  Drupal.vvjs = Drupal.vvjs || {};
  Drupal.vvjs.SlideshowProgress = SlideshowProgress;

})(Drupal);
