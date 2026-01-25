/**
 * @file
 * Slideshow navigation controls.
 *
 * Handles navigation buttons, dots, arrows, and play/pause controls.
 */

((Drupal) => {
  'use strict';

  const playIcon = `
    <svg class="svg-play" xmlns="http://www.w3.org/2000/svg" viewBox="80 -880 800 800" fill="currentColor">
      <path d="m380-300 280-180-280-180v360ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"></path>
    </svg>`;

  const pauseIcon = `
    <svg class="svg-pause" xmlns="http://www.w3.org/2000/svg" viewBox="80 -880 800 800" fill="currentColor">
      <path d="M360-320h80v-320h-80v320Zm160 0h80v-320h-80v320ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"></path>
    </svg>`;

  /**
   * Navigation controls class.
   */
  class SlideshowNavigation {
    constructor(container, slideshowCore) {
      this.container = container;
      this.core = slideshowCore;

      // Navigation elements
      this.playPauseButton = container.querySelector('.play-pause-button');
      this.nextButton = container.querySelector('.next-arrow');
      this.prevButton = container.querySelector('.prev-arrow');
      this.dots = container.querySelectorAll('.dots-numbers-button');
      this.currentSlideElement = container.querySelector('.current-slide');

      this.init();
    }

    init() {
      this.bindEvents();
      this.updateControls();

      // Listen for slideshow state changes
      this.container.addEventListener('vvjs:slideChanged', (e) => {
        this.updateControls(e.detail);
      });

      this.container.addEventListener('vvjs:pauseToggled', (e) => {
        this.updatePlayPauseButton(e.detail.isPaused);
      });
    }

    /**
     * Bind event listeners to navigation elements.
     */
    bindEvents() {
      // Play/pause button
      this.playPauseButton?.addEventListener('click', () => {
        // IMMEDIATE: Stop progress before toggling core state
        const modules = this.container.vvjsModules;
        if (modules && modules.progress && !this.core.isPaused) {
          modules.progress.resetProgress();
        }

        this.core.togglePause();
      });

      // Navigation arrows
      this.nextButton?.addEventListener('click', () => {
        this.core.nextSlide();
        this.core.startAutoSlide();
      });

      this.prevButton?.addEventListener('click', () => {
        this.core.prevSlide();
        this.core.startAutoSlide();
      });

      // Dot navigation
      this.dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
          this.core.goToSlide(index + 1);
          this.core.startAutoSlide();
        });
      });
    }

    /**
     * Update navigation controls based on current state.
     */
    updateControls(slideInfo = null) {
      if (slideInfo) {
        this.updateDots(slideInfo.slideIndex);
        this.updateSlideCounter(slideInfo.slideIndex);
      }
    }

    /**
     * Update dot navigation indicators.
     */
    updateDots(currentSlide) {
      this.dots.forEach((dot, index) => {
        const isActive = index + 1 === currentSlide;
        dot.classList.toggle('active', isActive);
        dot.setAttribute('aria-selected', isActive);
      });
    }

    /**
     * Update slide counter display.
     */
    updateSlideCounter(currentSlide) {
      if (this.currentSlideElement) {
        this.currentSlideElement.textContent = currentSlide;
      }
    }

    /**
     * Update play/pause button appearance.
     */
    updatePlayPauseButton(isPaused) {
      if (this.playPauseButton) {
        this.playPauseButton.innerHTML = isPaused ? playIcon : pauseIcon;
        this.playPauseButton.setAttribute(
          'aria-label',
          isPaused ? 'Play slideshow' : 'Pause slideshow'
        );
      }
    }

    /**
     * Enable/disable navigation controls.
     */
    setEnabled(enabled) {
      const controls = [
        this.playPauseButton,
        this.nextButton,
        this.prevButton,
        ...this.dots
      ].filter(Boolean);

      controls.forEach(control => {
        control.disabled = !enabled;
        control.setAttribute('aria-disabled', !enabled);
      });
    }
  }

  // Export to global namespace
  Drupal.vvjs = Drupal.vvjs || {};
  Drupal.vvjs.SlideshowNavigation = SlideshowNavigation;

})(Drupal);
