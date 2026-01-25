/**
 * @file
 * Handles form field visibility toggles in Views UI.
 *
 * Filename:     vvjb-admin.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 *
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Toggle deep link identifier field visibility.
   */
  Drupal.behaviors.vvjsDeeplinkToggle = {
    attach: function (context, settings) {
      const toggleCheckbox = once('vvjs-deeplink-toggle', '[data-vvjs-deeplink-toggle="true"]', context);

      if (!toggleCheckbox.length) {
        return;
      }

      toggleCheckbox.forEach(function(checkbox) {
        const wrapper = context.querySelector('[data-vvjs-deeplink-field="true"]');

        if (!wrapper) {
          return;
        }

        // Mark as processed to prevent duplicate handlers
        if (checkbox.hasAttribute('data-vvjs-processed')) {
          return;
        }
        checkbox.setAttribute('data-vvjs-processed', 'true');

        /**
         * Update field visibility and required state.
         */
        const updateVisibility = function(isEnabled) {
          const input = wrapper.querySelector('input[type="text"]');

          if (isEnabled) {
            wrapper.classList.remove('hidden-element');
            wrapper.removeAttribute('aria-hidden');

            if (input) {
              input.removeAttribute('tabindex');
              input.setAttribute('required', 'required');
            }
          }
          else {
            wrapper.classList.add('hidden-element');
            wrapper.setAttribute('aria-hidden', 'true');

            if (input) {
              input.setAttribute('tabindex', '-1');
              input.removeAttribute('required');
            }
          }
        };

        // Set initial state
        updateVisibility(checkbox.checked);

        // Listen for changes
        checkbox.addEventListener('change', function() {
          updateVisibility(this.checked);
        });
      });
    }
  };

})(Drupal, once);
