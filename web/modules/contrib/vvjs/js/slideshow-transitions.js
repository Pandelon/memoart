/**
 * @file
 * Slideshow transition effects module.
 *
 * Handles slide transitions including instant and crossfade variants.
 * Uses CSS-based transitions for GPU acceleration and themability.
 */

((Drupal) => {
  'use strict';

  /**
   * Slideshow Transitions class.
   *
   * Manages visual transition effects between slides using CSS classes
   * and data attributes. Coordinates with core module via events.
   */
  class SlideshowTransitions {
    /**
     * Constructor.
     *
     * @param {HTMLElement} container
     *   The slideshow container element.
     * @param {Object} slideshowCore
     *   Reference to the core slideshow instance.
     */
    constructor(container, slideshowCore) {
      this.container = container;
      this.core = slideshowCore;
      this.slideshow = container.querySelector('.vvjs-items');
      this.slides = this.slideshow?.querySelectorAll('.vvjs-item') || [];

      // Transition configuration
      this.transitionType = container.dataset.transition || 'instant';
      this.transitionDuration = parseInt(container.dataset.transitionDuration, 10) || 600;
      this.isHeroMode = container.classList.contains('hero-slideshow');

      // Active transition tracking for cleanup
      this.activeTransition = null;

      // Debugging flag (set to false in production)
      this.debug = false;

      this.init();
    }

    /**
     * Initialize the transitions module.
     */
    init() {
      if (!this.slideshow || !this.slides.length) {
        return;
      }

      this.setupSlides();
      this.bindEvents();

      if (this.debug) {
        console.log('[VVJS Transitions] Initialized', {
          type: this.transitionType,
          duration: this.transitionDuration,
          heroMode: this.isHeroMode,
          slides: this.slides.length,
        });
      }
    }

    /**
     * Set up initial slide positioning based on transition type.
     */
    setupSlides() {
      // Instant mode uses existing display:none logic - no changes needed
      if (this.transitionType === 'instant') {
        return;
      }

      // Crossfade modes: position all slides absolutely, set initial states
      if (this.transitionType.startsWith('crossfade')) {
        this.slides.forEach((slide, index) => {
          const isActive = index === 0;

          // Set initial opacity and z-index
          slide.style.opacity = isActive ? '1' : '0';
          slide.style.zIndex = isActive ? '2' : '1';

          // Add state classes (CSS handles positioning)
          slide.classList.toggle('vvjs-active', isActive);
          slide.classList.toggle('vvjs-previous', !isActive);
        });
      }
    }

    /**
     * Bind event listeners.
     */
    bindEvents() {
      // Listen for slide changes from core
      this.container.addEventListener('vvjs:slideChanging', (e) => {
        this.performTransition(e.detail.fromIndex, e.detail.toIndex);
      });

      // Listen for reduced motion preference changes
      const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
      reducedMotionQuery.addEventListener('change', (e) => {
        if (e.matches) {
          this.handleReducedMotion();
        }
      });

      // Initial reduced motion check
      if (reducedMotionQuery.matches) {
        this.handleReducedMotion();
      }
    }

    /**
     * Perform transition between slides.
     *
     * @param {number} fromIndex
     *   The index of the outgoing slide (1-based).
     * @param {number} toIndex
     *   The index of the incoming slide (1-based).
     */
    performTransition(fromIndex, toIndex) {
      // Clean up any active transition
      this.cleanupActiveTransition();

      const outgoing = fromIndex ? this.slides[fromIndex - 1] : null;
      const incoming = this.slides[toIndex - 1];

      if (!incoming) {
        return;
      }

      if (this.debug) {
        console.log('[VVJS Transitions] Transitioning', {
          from: fromIndex,
          to: toIndex,
          type: this.transitionType,
        });
      }

      // Route to appropriate transition method
      if (this.transitionType === 'instant') {
        this.transitionInstant(outgoing, incoming);
      }
      else if (this.transitionType === 'crossfade-classic') {
        this.transitionCrossfadeClassic(outgoing, incoming);
      }
      else if (this.transitionType === 'crossfade-staged') {
        this.transitionCrossfadeStaged(outgoing, incoming);
      }
      else if (this.transitionType === 'crossfade-dynamic') {
        this.transitionCrossfadeDynamic(outgoing, incoming);
      }
      else {
        // Fallback to instant
        this.transitionInstant(outgoing, incoming);
      }
    }

    /**
     * Instant transition (backward compatible default).
     *
     * @param {HTMLElement|null} outgoing
     *   The outgoing slide element.
     * @param {HTMLElement} incoming
     *   The incoming slide element.
     */
    transitionInstant(outgoing, incoming) {
      // Instant transition uses core's display:none logic
      // Just dispatch completion event immediately
      this.onTransitionComplete();
    }

    /**
     * Classic crossfade: both slides fade at same speed.
     *
     * @param {HTMLElement|null} outgoing
     *   The outgoing slide element.
     * @param {HTMLElement} incoming
     *   The incoming slide element.
     */
    transitionCrossfadeClassic(outgoing, incoming) {
      this.applyCrossfadeTransition(outgoing, incoming, 'classic');
    }

    /**
     * Staged crossfade: outgoing fades quickly, incoming slowly with overlap.
     *
     * @param {HTMLElement|null} outgoing
     *   The outgoing slide element.
     * @param {HTMLElement} incoming
     *   The incoming slide element.
     */
    transitionCrossfadeStaged(outgoing, incoming) {
      this.applyCrossfadeTransition(outgoing, incoming, 'staged');
    }

    /**
     * Dynamic crossfade: fast fade-out, slow fade-in.
     *
     * @param {HTMLElement|null} outgoing
     *   The outgoing slide element.
     * @param {HTMLElement} incoming
     *   The incoming slide element.
     */
    transitionCrossfadeDynamic(outgoing, incoming) {
      this.applyCrossfadeTransition(outgoing, incoming, 'dynamic');
    }

    /**
     * Apply crossfade transition with proper timing and cleanup.
     *
     * @param {HTMLElement|null} outgoing
     *   The outgoing slide element.
     * @param {HTMLElement} incoming
     *   The incoming slide element.
     * @param {string} variant
     *   The crossfade variant (classic|staged|dynamic).
     */
    applyCrossfadeTransition(outgoing, incoming, variant) {
      // Set up z-index stacking
      if (outgoing) {
        outgoing.style.zIndex = '1';
        outgoing.classList.remove('vvjs-active');
        outgoing.classList.add('vvjs-previous');
      }

      incoming.style.zIndex = '2';
      incoming.classList.remove('vvjs-previous');
      incoming.classList.add('vvjs-active');

      // Trigger opacity transitions via CSS classes
      // CSS handles the actual animation based on data-transition attribute
      // Animations (zoom, slide) are handled by CSS selectors targeting .vvjs-active
      if (outgoing) {
        outgoing.style.opacity = '0';
      }
      incoming.style.opacity = '1';

      // Set up transition completion detection
      this.setupTransitionCompletion(incoming, variant);
    }

    /**
     * Set up transition completion detection with fallback.
     *
     * @param {HTMLElement} element
     *   The element to watch for transitionend.
     * @param {string} variant
     *   The transition variant for duration calculation.
     */
    setupTransitionCompletion(element, variant) {
      let transitionEnded = false;

      const cleanup = () => {
        if (transitionEnded) {
          return;
        }
        transitionEnded = true;

        // Remove event listener
        if (this.activeTransition?.listener) {
          element.removeEventListener('transitionend', this.activeTransition.listener);
        }

        this.onTransitionComplete();
      };

      // Calculate total duration based on variant
      let totalDuration = this.transitionDuration;
      if (variant === 'staged') {
        // Staged has overlap, so total time is longer
        totalDuration = this.transitionDuration * 1.3;
      }

      // Store transition data for cleanup
      this.activeTransition = {
        element,
        listener: (e) => {
          // Only trigger on opacity transitions, not other properties
          if (e.propertyName === 'opacity' && e.target === element) {
            cleanup();
          }
        },
        timeout: setTimeout(cleanup, totalDuration + 100), // 100ms buffer
      };

      // Listen for transitionend (preferred method)
      element.addEventListener('transitionend', this.activeTransition.listener);
    }

    /**
     * Handle transition completion.
     *
     * Dispatches event for other modules to respond to.
     */
    onTransitionComplete() {
      // Clean up active transition tracking
      this.cleanupActiveTransition();

      // Dispatch completion event
      this.container.dispatchEvent(new CustomEvent('vvjs:transitionComplete', {
        detail: {
          slideIndex: this.core.slideIndex,
          transitionType: this.transitionType,
        },
      }));

      if (this.debug) {
        console.log('[VVJS Transitions] Transition complete');
      }
    }

    /**
     * Clean up active transition resources.
     */
    cleanupActiveTransition() {
      if (this.activeTransition) {
        if (this.activeTransition.timeout) {
          clearTimeout(this.activeTransition.timeout);
        }
        if (this.activeTransition.element && this.activeTransition.listener) {
          this.activeTransition.element.removeEventListener(
            'transitionend',
            this.activeTransition.listener
          );
        }
        this.activeTransition = null;
      }
    }

    /**
     * Handle reduced motion preference.
     *
     * Automatically switches to instant transitions when user prefers reduced motion.
     */
    handleReducedMotion() {
      // Store original transition type
      if (!this.originalTransitionType) {
        this.originalTransitionType = this.transitionType;
      }

      // Switch to instant mode
      this.transitionType = 'instant';
      this.container.dataset.transition = 'instant';

      // Add visual class for CSS
      this.container.classList.add('reduced-motion');

      if (this.debug) {
        console.log('[VVJS Transitions] Reduced motion enabled');
      }
    }

    /**
     * Update transition configuration.
     *
     * @param {string} type
     *   New transition type.
     * @param {number} duration
     *   New transition duration in milliseconds.
     */
    updateConfig(type, duration) {
      this.transitionType = type;
      this.transitionDuration = duration;
      this.container.dataset.transition = type;
      this.container.dataset.transitionDuration = duration;

      // Re-setup slides if switching between instant and crossfade
      this.setupSlides();
    }

    /**
     * Get current transition state.
     *
     * @return {Object}
     *   Current transition configuration.
     */
    getState() {
      return {
        type: this.transitionType,
        duration: this.transitionDuration,
        isHeroMode: this.isHeroMode,
        hasActiveTransition: this.activeTransition !== null,
      };
    }

    /**
     * Clean up when module is destroyed.
     */
    destroy() {
      this.cleanupActiveTransition();

      // Remove any added classes
      this.slides.forEach((slide) => {
        slide.classList.remove('vvjs-active', 'vvjs-previous');
        slide.style.opacity = '';
        slide.style.zIndex = '';
      });

      if (this.debug) {
        console.log('[VVJS Transitions] Destroyed');
      }
    }
  }

  // Export to global namespace
  Drupal.vvjs = Drupal.vvjs || {};
  Drupal.vvjs.SlideshowTransitions = SlideshowTransitions;

})(Drupal);
