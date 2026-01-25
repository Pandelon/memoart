/**
 * @file
 * Main Views Vanilla JavaScript Slideshow orchestrator.
 *
 * Coordinates all slideshow modules and provides the main Drupal behavior.
 *
 * Filename:     vvjs-main.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */

((Drupal, drupalSettings, once) => {
  'use strict';

  /**
   * Main slideshow orchestrator class.
   */
  class VVJSSlideshow {
    constructor(container) {
      this.container = container;
      this.modules = {};

      // Validate required modules are available
      if (!this.validateDependencies()) {
        console.error('VVJS: Required modules not loaded');
        return;
      }

      this.init();
    }

    /**
     * Validate that all required modules are loaded.
     */
    validateDependencies() {
      const requiredModules = [
        'SlideshowCore',
        'SlideshowTransitions',
        'SlideshowNavigation',
        'SlideshowAccessibility',
        'SlideshowProgress',
        'SlideshowVisibility',
        'SlideshowEvents'
      ];

      return requiredModules.every(module =>
        Drupal.vvjs && typeof Drupal.vvjs[module] === 'function'
      );
    }

    /**
     * Initialize all slideshow modules.
     */
    init() {
      try {
        // Initialize core first
        this.modules.core = new Drupal.vvjs.SlideshowCore(this.container);

        // Initialize transitions module second (handles visual transitions)
        this.modules.transitions = new Drupal.vvjs.SlideshowTransitions(
          this.container,
          this.modules.core
        );

        // Initialize dependent modules
        this.modules.navigation = new Drupal.vvjs.SlideshowNavigation(
          this.container,
          this.modules.core
        );

        this.modules.accessibility = new Drupal.vvjs.SlideshowAccessibility(
          this.container,
          this.modules.core
        );

        this.modules.progress = new Drupal.vvjs.SlideshowProgress(
          this.container,
          this.modules.core
        );

        this.modules.visibility = new Drupal.vvjs.SlideshowVisibility(
          this.container,
          this.modules.core
        );

        this.modules.events = new Drupal.vvjs.SlideshowEvents(
          this.container,
          this.modules.core
        );

        // IMPORTANT: Store module references on container for cross-module communication
        this.container.vvjsModules = this.modules;

        // Initialize deep linking if enabled - may activate a slide from URL
        const deepLinkActivated = this.initializeDeepLinking();

        // Start the slideshow
        this.modules.core.startAutoSlide();

        // Mark as initialized
        this.container.classList.add('vvjs-initialized');

        // Dispatch initialization event
        this.container.dispatchEvent(new CustomEvent('vvjs:initialized', {
          detail: { slideshow: this }
        }));

      } catch (error) {
        console.error('VVJS: Initialization error', error);
        this.handleInitializationError(error);
      }
    }

    /**
     * Handle initialization errors gracefully.
     */
    handleInitializationError(error) {
      // Add error class for CSS styling
      this.container.classList.add('vvjs-error');

      // Try to provide basic functionality even if some modules fail
      if (this.modules.core) {
        // At least try to show the first slide
        this.modules.core.updateSlideVisibility();
      }

      // Log error for debugging
      console.error('VVJS initialization failed:', error);
    }

    /**
     * Get reference to specific module.
     */
    getModule(moduleName) {
      return this.modules[moduleName] || null;
    }

    /**
     * Get all module references.
     */
    getAllModules() {
      return { ...this.modules };
    }

    /**
     * Check if slideshow is properly initialized.
     */
    isInitialized() {
      return this.container.classList.contains('vvjs-initialized');
    }

    /**
     * Reinitialize slideshow (useful for dynamic content changes).
     */
    reinitialize() {
      this.destroy();
      this.init();
    }

    /**
     * Destroy slideshow and cleanup all modules.
     */
    destroy() {
      // Destroy modules in reverse order
      Object.keys(this.modules).reverse().forEach(moduleName => {
        const module = this.modules[moduleName];
        if (module && typeof module.destroy === 'function') {
          try {
            module.destroy();
          } catch (error) {
            console.error(`Error destroying ${moduleName} module:`, error);
          }
        }
      });

      // Clear modules
      this.modules = {};

      // Remove classes
      this.container.classList.remove('vvjs-initialized', 'vvjs-error');

      // Dispatch destruction event
      this.container.dispatchEvent(new CustomEvent('vvjs:destroyed'));
    }

    /**
     * Get slideshow configuration and state.
     */
    getState() {
      const core = this.modules.core;
      if (!core) return null;

      return {
        slideIndex: core.slideIndex,
        totalSlides: core.totalSlides,
        isPaused: core.isPaused,
        isVisible: core.isVisible,
        slideTime: core.slideTime,
        modules: Object.keys(this.modules)
      };
    }

    /**
     * Update slideshow configuration.
     */
    updateConfig(config) {
      if (this.modules.core) {
        // Update core configuration
        Object.assign(this.modules.core, config);

        // Notify modules of configuration change
        this.container.dispatchEvent(new CustomEvent('vvjs:configChanged', {
          detail: { config }
        }));
      }
    }

    /**
     * Initialize deep linking functionality.
     * 
     * Checks URL hash on page load and navigates to the specified slide.
     * Also sets up hash change listener for browser back/forward.
     */
    initializeDeepLinking() {
      const deeplinkEnabled = this.container.dataset.deeplinkEnabled === 'true';
      const deeplinkId = this.container.dataset.deeplinkId;

      if (!deeplinkEnabled || !deeplinkId) {
        return false;
      }

      // Check URL hash on page load
      let slideActivated = false;
      const hash = window.location.hash;
      
      if (hash && hash.startsWith(`#${deeplinkId}-`)) {
        const slideNumber = parseInt(hash.split('-').pop(), 10);
        
        if (slideNumber >= 1 && slideNumber <= this.modules.core.totalSlides) {
          // Navigate to the slide from URL
          this.modules.core.goToSlide(slideNumber);
          slideActivated = true;
        }
      }

      // Set up hash change listener (only once per slideshow)
      if (!this.container.vvjsHashChangeHandler) {
        const hashChangeHandler = () => {
          const currentHash = window.location.hash;
          
          // Only respond to hash changes for this specific slideshow
          if (currentHash && currentHash.startsWith(`#${deeplinkId}-`)) {
            const slideNumber = parseInt(currentHash.split('-').pop(), 10);
            
            if (slideNumber >= 1 && slideNumber <= this.modules.core.totalSlides) {
              this.modules.core.goToSlide(slideNumber);
              this.modules.core.startAutoSlide();
            }
          }
        };

        this.container.vvjsHashChangeHandler = hashChangeHandler;
        window.addEventListener('hashchange', hashChangeHandler);
      }

      // Listen for slide changes to update URL hash
      this.container.addEventListener('vvjs:slideChanged', (e) => {
        if (deeplinkEnabled && deeplinkId) {
          const newHash = `#${deeplinkId}-${e.detail.slideIndex}`;
          
          // Use replaceState to avoid cluttering browser history
          if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', newHash);
          } else {
            window.location.hash = newHash;
          }
        }
      });

      return slideActivated;
    }
  }

  /**
   * Main Drupal behavior for VVJS slideshows.
   */
  Drupal.behaviors.VVJSlideshow = {
    attach(context) {
      // Find all slideshow containers that haven't been initialized
      const slideshows = once('vvjSlideshow', '.vvjs-inner', context);

      if (!slideshows.length) {
        return;
      }

      // Initialize each slideshow
      slideshows.forEach((container) => {
        try {
          // Store slideshow instance on the container for external access
          container.vvjsSlideshow = new VVJSSlideshow(container);
        } catch (error) {
          console.error('Failed to initialize VVJS slideshow:', error);

          // Add error state to container
          container.classList.add('vvjs-init-failed');
        }
      });
    },

    /**
     * Cleanup when elements are removed from DOM.
     */
    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        // Find initialized slideshows and destroy them
        const slideshows = context.querySelectorAll('.vvjs-inner.vvjs-initialized');

        slideshows.forEach((container) => {
          if (container.vvjsSlideshow) {
            container.vvjsSlideshow.destroy();
            delete container.vvjsSlideshow;
          }
        });
      }
    }
  };

  /**
   * Global utility functions for external access.
   */
  Drupal.vvjs = Drupal.vvjs || {};

  /**
   * Get slideshow instance by container element or selector.
   */
  Drupal.vvjs.getInstance = function(containerOrSelector) {
    let container;

    if (typeof containerOrSelector === 'string') {
      container = document.querySelector(containerOrSelector);
    } else {
      container = containerOrSelector;
    }

    return container && container.vvjsSlideshow ? container.vvjsSlideshow : null;
  };

  /**
   * Get all active slideshow instances.
   */
  Drupal.vvjs.getAllInstances = function() {
    const containers = document.querySelectorAll('.vvjs-inner.vvjs-initialized');
    return Array.from(containers)
      .map(container => container.vvjsSlideshow)
      .filter(Boolean);
  };

  /**
   * Pause all slideshows on the page.
   */
  Drupal.vvjs.pauseAll = function() {
    Drupal.vvjs.getAllInstances().forEach(slideshow => {
      const core = slideshow.getModule('core');
      if (core && !core.isPaused) {
        core.togglePause();
      }
    });
  };

  /**
   * Resume all slideshows on the page.
   */
  Drupal.vvjs.resumeAll = function() {
    Drupal.vvjs.getAllInstances().forEach(slideshow => {
      const core = slideshow.getModule('core');
      if (core && core.isPaused) {
        core.togglePause();
      }
    });
  };

/**
   * Helper function to get slideshow container by identifier.
   *
   * @param {string} identifier
   *   The slideshow identifier (deeplink_identifier or CSS selector).
   *
   * @return {HTMLElement|null}
   *   The container element or null if not found.
   */
  function getContainerByIdentifier(identifier) {
    let container;

    // Try deep link identifier first (if not a CSS selector)
    if (!identifier.startsWith('.') && !identifier.startsWith('#')) {
      container = document.querySelector(`[data-deeplink-id="${identifier}"]`);
    }

    // Fallback to CSS selector
    if (!container) {
      container = document.querySelector(identifier);
    }

    return container;
  }

  /**
   * Helper function to get core module from identifier.
   *
   * @param {string} identifier
   *   The slideshow identifier.
   *
   * @return {Object|null}
   *   The core module or null if not found.
   */
  function getCoreModule(identifier) {
    const container = getContainerByIdentifier(identifier);

    if (!container || !container.vvjsSlideshow) {
      return null;
    }

    return container.vvjsSlideshow.getModule('core');
  }

  /**
   * Navigate to a specific slide by identifier.
   *
   * @param {string} identifier
   *   The slideshow identifier (from deeplink_identifier or container selector).
   * @param {number} slideIndex
   *   The slide number to navigate to (1-based).
   *
   * @return {boolean}
   *   True if navigation was successful, false otherwise.
   *
   * @example
   * Drupal.vvjs.goToSlide('gallery', 3);
   */
  Drupal.vvjs.goToSlide = function(identifier, slideIndex) {
    const core = getCoreModule(identifier);

    if (!core) {
      if (typeof console !== 'undefined' && console.warn) {
        console.warn(`VVJS: Slideshow "${identifier}" not found`);
      }
      return false;
    }

    if (slideIndex < 1 || slideIndex > core.totalSlides) {
      if (typeof console !== 'undefined' && console.warn) {
        console.warn(`VVJS: Invalid slide index ${slideIndex}. Must be between 1 and ${core.totalSlides}`);
      }
      return false;
    }

    core.goToSlide(slideIndex);
    core.startAutoSlide();
    return true;
  };

  /**
   * Get the current slide index for a slideshow.
   *
   * @param {string} identifier
   *   The slideshow identifier.
   *
   * @return {number|null}
   *   The current slide index (1-based) or null if not found.
   *
   * @example
   * const currentSlide = Drupal.vvjs.getCurrentSlide('gallery');
   */
  Drupal.vvjs.getCurrentSlide = function(identifier) {
    const core = getCoreModule(identifier);
    return core ? core.slideIndex : null;
  };

  /**
   * Get total number of slides in a slideshow.
   *
   * @param {string} identifier
   *   The slideshow identifier.
   *
   * @return {number|null}
   *   The total number of slides or null if not found.
   *
   * @example
   * const total = Drupal.vvjs.getTotalSlides('gallery');
   */
  Drupal.vvjs.getTotalSlides = function(identifier) {
    const core = getCoreModule(identifier);
    return core ? core.totalSlides : null;
  };

  /**
   * Navigate to next slide.
   *
   * @param {string} identifier
   *   The slideshow identifier.
   *
   * @return {boolean}
   *   True if successful.
   *
   * @example
   * Drupal.vvjs.nextSlide('gallery');
   */
  Drupal.vvjs.nextSlide = function(identifier) {
    const core = getCoreModule(identifier);

    if (core) {
      core.nextSlide();
      core.startAutoSlide();
      return true;
    }

    return false;
  };

  /**
   * Navigate to previous slide.
   *
   * @param {string} identifier
   *   The slideshow identifier.
   *
   * @return {boolean}
   *   True if successful.
   *
   * @example
   * Drupal.vvjs.prevSlide('gallery');
   */
  Drupal.vvjs.prevSlide = function(identifier) {
    const core = getCoreModule(identifier);

    if (core) {
      core.prevSlide();
      core.startAutoSlide();
      return true;
    }

    return false;
  };

  /**
   * Pause a specific slideshow.
   *
   * @param {string} identifier
   *   The slideshow identifier.
   *
   * @return {boolean}
   *   True if successful.
   *
   * @example
   * Drupal.vvjs.pause('gallery');
   */
  Drupal.vvjs.pause = function(identifier) {
    const core = getCoreModule(identifier);

    if (core && !core.isPaused) {
      core.togglePause();
      return true;
    }

    return false;
  };

  /**
   * Resume a specific slideshow.
   *
   * @param {string} identifier
   *   The slideshow identifier.
   *
   * @return {boolean}
   *   True if successful.
   *
   * @example
   * Drupal.vvjs.resume('gallery');
   */
  Drupal.vvjs.resume = function(identifier) {
    const core = getCoreModule(identifier);

    if (core && core.isPaused) {
      core.togglePause();
      return true;
    }

    return false;
  };

})(Drupal, drupalSettings, once);
