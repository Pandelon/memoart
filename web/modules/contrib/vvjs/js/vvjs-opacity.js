/**
 * @file
 * Views Vanilla Javascript Slideshow - Opacity Toggle.
 *
 * Filename:     opacity.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 *
 */

(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * Toggle opacity controls based on hero slideshow state.
   *
   * @type {Drupal~behavior}
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the opacity toggle behavior to hero slideshow checkbox.
   */
  Drupal.behaviors.vvjsToggleOpacity = {
    attach: function (context, settings) {
      // Early validation - check if required settings exist
      if (!drupalSettings || !drupalSettings.vvjs) {
        if (typeof console !== 'undefined' && console.warn) {
          console.warn('VVJS: drupalSettings.vvjs not found. Opacity toggle disabled.');
        }
        return;
      }

      const vvjsSettings = drupalSettings.vvjs;

      // Validate required selectors
      if (!vvjsSettings.heroSlideshowSelector || !vvjsSettings.opacityValueSelector) {
        if (typeof console !== 'undefined' && console.warn) {
          console.warn('VVJS: Required selectors missing in drupalSettings.vvjs');
        }
        return;
      }

      // Cache DOM elements with context support for AJAX
      const heroSlideshowCheckbox = context.querySelector(vvjsSettings.heroSlideshowSelector);
      const opacityValueContainer = context.querySelector(vvjsSettings.opacityValueSelector);

      // Exit early if elements don't exist
      if (!heroSlideshowCheckbox || !opacityValueContainer) {
        return; // Silent fail - elements might not be on this page
      }

      // Skip if already processed (prevents duplicate event listeners in AJAX contexts)
      if (heroSlideshowCheckbox.hasAttribute('data-vvjs-opacity-processed')) {
        return;
      }

      /**
       * Updates opacity control visibility and accessibility.
       *
       * @param {boolean} isHeroMode - Whether hero slideshow is enabled
       */
      const updateOpacityVisibility = (isHeroMode) => {
        if (isHeroMode) {
          opacityValueContainer.classList.remove('hidden-element');
          opacityValueContainer.removeAttribute('aria-hidden');

          // Focus management for better UX
          const opacityInput = opacityValueContainer.querySelector('input, select');
          if (opacityInput) {
            opacityInput.removeAttribute('tabindex');
          }
        } else {
          opacityValueContainer.classList.add('hidden-element');
          opacityValueContainer.setAttribute('aria-hidden', 'true');

          // Remove from tab order when hidden
          const opacityInput = opacityValueContainer.querySelector('input, select');
          if (opacityInput) {
            opacityInput.setAttribute('tabindex', '-1');
          }
        }

        // Trigger custom event for other scripts to listen to
        const event = new CustomEvent('vvjs:opacityToggle', {
          detail: {
            isHeroMode: isHeroMode,
            container: opacityValueContainer
          },
          bubbles: true
        });
        heroSlideshowCheckbox.dispatchEvent(event);
      };

      /**
       * Handle checkbox change with improved error handling.
       *
       * @param {Event} event - The change event
       */
      const handleCheckboxChange = (event) => {
        try {
          const isChecked = event.target.checked;
          updateOpacityVisibility(isChecked);
        } catch (error) {
          if (typeof console !== 'undefined' && console.error) {
            console.error('VVJS: Error in opacity toggle handler:', error);
          }
        }
      };

      // Set initial state based on checkbox value
      updateOpacityVisibility(heroSlideshowCheckbox.checked);

      // Add event listener with passive option for better performance
      heroSlideshowCheckbox.addEventListener('change', handleCheckboxChange, { passive: true });

      // Mark as processed to prevent duplicate processing
      heroSlideshowCheckbox.setAttribute('data-vvjs-opacity-processed', 'true');

      // Optional: Add visual feedback during transitions
      if (opacityValueContainer.style.transition === '') {
        opacityValueContainer.style.transition = 'opacity 0.3s ease-in-out';
      }
    },

    /**
     * Clean up when behavior is detached (important for AJAX contexts).
     *
     * @param {HTMLDocument|HTMLElement} context
     *   The context for which the behavior is being detached.
     * @param {object} settings
     *   An object containing settings for the current context.
     * @param {string} trigger
     *   One of 'unload', 'move', or 'serialize'.
     */
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        // Clean up processed markers and event listeners if needed
        const processedElements = context.querySelectorAll('[data-vvjs-opacity-processed]');
        processedElements.forEach(element => {
          element.removeAttribute('data-vvjs-opacity-processed');
        });
      }
    }
  };

})(Drupal, drupalSettings);
