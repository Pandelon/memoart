/**
 * @file
 * Slideshow user interaction events.
 *
 * Handles touch gestures, mouse interactions, and other user input events.
 */

((Drupal) => {
  'use strict';

  /**
   * Events manager class.
   */
  class SlideshowEvents {
    constructor(container, slideshowCore) {
      this.container = container;
      this.core = slideshowCore;
      this.slideshow = container.querySelector('.vvjs-items');

      // Touch/swipe state
      this.touchStartX = 0;
      this.touchStartY = 0;
      this.touchEndX = 0;
      this.touchEndY = 0;
      this.isDragging = false;
      this.dragThreshold = 50; // Minimum distance for swipe

      // Mouse state
      this.isMouseOver = false;

      // Configuration
      this.swipeEnabled = true;
      this.pauseOnHover = true;

      this.init();
    }

    init() {
      this.setupTouchEvents();
      this.setupMouseEvents();
      this.setupPointerEvents();
    }

    /**
     * Set up touch event handlers for swipe gestures.
     */
    setupTouchEvents() {
      if (!this.slideshow) return;

      // Touch start
      this.slideshow.addEventListener('touchstart', (e) => {
        this.handleTouchStart(e);
      }, { passive: true });

      // Touch move
      this.slideshow.addEventListener('touchmove', (e) => {
        this.handleTouchMove(e);
      }, { passive: true });

      // Touch end
      this.slideshow.addEventListener('touchend', (e) => {
        this.handleTouchEnd(e);
      }, { passive: true });

      // Touch cancel
      this.slideshow.addEventListener('touchcancel', (e) => {
        this.handleTouchCancel(e);
      });
    }

    /**
     * Set up mouse event handlers.
     */
    setupMouseEvents() {
      if (!this.slideshow) return;

      // Mouse enter - pause slideshow
      this.slideshow.addEventListener('mouseenter', () => {
        this.handleMouseEnter();
      });

      // Mouse leave - resume slideshow
      this.slideshow.addEventListener('mouseleave', () => {
        this.handleMouseLeave();
      });

      // Mouse down for potential drag
      this.slideshow.addEventListener('mousedown', (e) => {
        this.handleMouseDown(e);
      });

      // Mouse move for drag detection
      this.slideshow.addEventListener('mousemove', (e) => {
        this.handleMouseMove(e);
      });

      // Mouse up
      this.slideshow.addEventListener('mouseup', (e) => {
        this.handleMouseUp(e);
      });
    }

    /**
     * Set up pointer events for unified touch/mouse handling.
     */
    setupPointerEvents() {
      // Modern browsers support pointer events
      if ('PointerEvent' in window && this.slideshow) {
        this.slideshow.addEventListener('pointerdown', (e) => {
          this.handlePointerDown(e);
        });

        this.slideshow.addEventListener('pointermove', (e) => {
          this.handlePointerMove(e);
        });

        this.slideshow.addEventListener('pointerup', (e) => {
          this.handlePointerUp(e);
        });
      }
    }

    /**
     * Handle touch start event.
     */
    handleTouchStart(e) {
      if (!this.swipeEnabled) return;

      const touch = e.touches[0];
      this.touchStartX = touch.clientX;
      this.touchStartY = touch.clientY;
      this.isDragging = false;

      // Store reference on slideshow element for compatibility
      this.slideshow._touchStartX = touch.clientX;
      this.slideshow._touchStartY = touch.clientY;
    }

    /**
     * Handle touch move event.
     */
    handleTouchMove(e) {
      if (!this.swipeEnabled) return;

      const touch = e.touches[0];
      const deltaX = Math.abs(touch.clientX - this.touchStartX);
      const deltaY = Math.abs(touch.clientY - this.touchStartY);

      // Determine if this is a horizontal swipe
      if (deltaX > deltaY && deltaX > 10) {
        this.isDragging = true;
        // Prevent default to stop scrolling
        e.preventDefault();
      }
    }

    /**
     * Handle touch end event.
     */
    handleTouchEnd(e) {
      if (!this.swipeEnabled) return;

      const touch = e.changedTouches[0];
      this.touchEndX = touch.clientX;
      this.touchEndY = touch.clientY;

      this.processSwipeGesture();
      this.resetTouchState();
    }

    /**
     * Handle touch cancel event.
     */
    handleTouchCancel(e) {
      this.resetTouchState();
    }

    /**
     * Process swipe gesture and trigger navigation.
     */
    processSwipeGesture() {
      const deltaX = this.touchEndX - this.touchStartX;
      const deltaY = Math.abs(this.touchEndY - this.touchStartY);

      // Only process if horizontal movement is significant and vertical is minimal
      if (Math.abs(deltaX) > this.dragThreshold && deltaY < this.dragThreshold * 0.5) {
        if (deltaX > 0) {
          // Swipe right - previous slide
          this.core.prevSlide();
        } else {
          // Swipe left - next slide
          this.core.nextSlide();
        }

        // Restart auto-advance
        this.core.startAutoSlide();

        // Dispatch custom event
        this.container.dispatchEvent(new CustomEvent('vvjs:swipe', {
          detail: {
            direction: deltaX > 0 ? 'right' : 'left',
            distance: Math.abs(deltaX)
          }
        }));
      }
    }

    /**
     * Reset touch state.
     */
    resetTouchState() {
      this.touchStartX = 0;
      this.touchStartY = 0;
      this.touchEndX = 0;
      this.touchEndY = 0;
      this.isDragging = false;
    }

    /**
     * Handle mouse enter event.
     */
    handleMouseEnter() {
      if (this.pauseOnHover) {
        this.isMouseOver = true;

        // IMMEDIATE: Stop progress before stopping auto-slide
        const modules = this.container.vvjsModules;
        if (modules && modules.progress) {
          modules.progress.immediateStop();
        }

        this.core.stopAutoSlide();

        this.container.dispatchEvent(new CustomEvent('vvjs:mouseEnter'));
      }
    }

    /**
     * Handle mouse leave event.
     */
    handleMouseLeave() {
      if (this.pauseOnHover) {
        this.isMouseOver = false;
        this.core.startAutoSlide();

        this.container.dispatchEvent(new CustomEvent('vvjs:mouseLeave'));
      }
    }

    /**
     * Handle mouse down event.
     */
    handleMouseDown(e) {
      // Could be used for mouse drag gestures
      this.touchStartX = e.clientX;
      this.touchStartY = e.clientY;
    }

    /**
     * Handle mouse move event.
     */
    handleMouseMove(e) {
      // Could be used for mouse drag detection
      if (this.isDragging) {
        // Handle mouse drag if enabled
      }
    }

    /**
     * Handle mouse up event.
     */
    handleMouseUp(e) {
      // Could be used to end mouse drag gesture
      this.touchEndX = e.clientX;
      this.touchEndY = e.clientY;
    }

    /**
     * Handle pointer down event.
     */
    handlePointerDown(e) {
      if (e.pointerType === 'touch') {
        this.handleTouchStart(e);
      } else if (e.pointerType === 'mouse') {
        this.handleMouseDown(e);
      }
    }

    /**
     * Handle pointer move event.
     */
    handlePointerMove(e) {
      if (e.pointerType === 'touch') {
        this.handleTouchMove(e);
      } else if (e.pointerType === 'mouse') {
        this.handleMouseMove(e);
      }
    }

    /**
     * Handle pointer up event.
     */
    handlePointerUp(e) {
      if (e.pointerType === 'touch') {
        this.handleTouchEnd(e);
      } else if (e.pointerType === 'mouse') {
        this.handleMouseUp(e);
      }
    }

    /**
     * Enable or disable swipe gestures.
     */
    setSwipeEnabled(enabled) {
      this.swipeEnabled = enabled;
    }

    /**
     * Enable or disable pause on hover.
     */
    setPauseOnHover(enabled) {
      this.pauseOnHover = enabled;
    }

    /**
     * Set swipe sensitivity threshold.
     */
    setSwipeThreshold(threshold) {
      this.dragThreshold = Math.max(10, Math.min(200, threshold));
    }

    /**
     * Get current interaction state.
     */
    getInteractionState() {
      return {
        isMouseOver: this.isMouseOver,
        isDragging: this.isDragging,
        swipeEnabled: this.swipeEnabled,
        pauseOnHover: this.pauseOnHover
      };
    }

    /**
     * Add custom event listener for slideshow events.
     */
    addEventListener(eventType, handler) {
      this.container.addEventListener(`vvjs:${eventType}`, handler);
    }

    /**
     * Remove custom event listener.
     */
    removeEventListener(eventType, handler) {
      this.container.removeEventListener(`vvjs:${eventType}`, handler);
    }

    /**
     * Clean up event listeners.
     */
    destroy() {
      // Event listeners will be automatically removed when the container is destroyed
      // but we could add explicit cleanup here if needed
      this.resetTouchState();
    }
  }

  // Export to global namespace
  Drupal.vvjs = Drupal.vvjs || {};
  Drupal.vvjs.SlideshowEvents = SlideshowEvents;

})(Drupal);
