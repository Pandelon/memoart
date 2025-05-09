/**
 * @file
 * Views Vanilla Javascript Slideshow.
 *
 * Filename:     opacity.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
(function (Drupal, drupalSettings) {
  Drupal.behaviors.vvjsToggleOpacity = {
    attach: function (context, settings) {
      var heroSlideshow = document.querySelector(drupalSettings.vvjs.heroSlideshowSelector);
      var opacityValue = document.querySelector(drupalSettings.vvjs.opacityValueSelector);

      function toggleOpacityValue() {
        if (heroSlideshow.checked) {
          opacityValue.classList.remove('hidden-element');
        } else {
          opacityValue.classList.add('hidden-element');
        }
      }

      if (heroSlideshow && opacityValue) {
        toggleOpacityValue(); // Initial check on page load

        heroSlideshow.addEventListener('change', function () {
          toggleOpacityValue();
        });
      }
    }
  };
})(Drupal, drupalSettings);

