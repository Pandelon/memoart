/**
 * @file
 * Slideshow user interaction events.
 *
 * Handles touch gestures, mouse interactions, and other user input events.
 * Uses Pointer Events API as primary input with Touch Events fallback.
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

      // Touch/swipe state (null = not tracking, allows valid 0,0 coordinates).
      this.touchStartX = null;
      this.touchStartY = null;
      this.touchEndX = null;
      this.touchEndY = null;
      this.isDragging = false;
      this.dragThreshold = 40;

      // Mouse state
      this.isMouseOver = false;

      // Configuration - read from data attributes, default to true if not specified
      this.swipeEnabled = container.dataset.enableSwipe !== 'false';
      this.pauseOnHover = container.dataset.pauseOnHover !== 'false';

      this.init();
    }

    init() {
      // Prefer Pointer Events API (97%+ browser support).
      // Fall back to Touch Events for legacy browsers only.
      if ('PointerEvent' in window) {
        this.setupPointerEvents();
      }
      else {
        this.setupTouchEvents();
      }

      // Mouse events for hover pause functionality.
      this.setupMouseEvents();
    }

    /**
     * Set up pointer event handlers (modern unified input API).
     */
    setupPointerEvents() {
      if (!this.slideshow) {
        return;
      }

      // Pointer down - start tracking.
      this.slideshow.addEventListener('pointerdown', (e) => {
        this.handlePointerDown(e);
      });

      // Pointer move - must be non-passive to allow preventDefault().
      this.slideshow.addEventListener('pointermove', (e) => {
        this.handlePointerMove(e);
      }, { passive: false });

      // Pointer up - process gesture.
      this.slideshow.addEventListener('pointerup', (e) => {
        this.handlePointerUp(e);
      });

      // Pointer cancel - reset state.
      this.slideshow.addEventListener('pointercancel', () => {
        this.resetTouchState();
      });

    }

    /**
     * Set up touch event handlers (fallback for legacy browsers).
     */
    setupTouchEvents() {
      if (!this.slideshow) {
        return;
      }

      // Touch start.
      this.slideshow.addEventListener('touchstart', (e) => {
        this.handleTouchStart(e);
      }, { passive: true });

      // Touch move - must be non-passive to allow preventDefault().
      this.slideshow.addEventListener('touchmove', (e) => {
        this.handleTouchMove(e);
      }, { passive: false });

      // Touch end.
      this.slideshow.addEventListener('touchend', (e) => {
        this.handleTouchEnd(e);
      }, { passive: true });

      // Touch cancel.
      this.slideshow.addEventListener('touchcancel', () => {
        this.resetTouchState();
      });
    }

    /**
     * Set up mouse event handlers for hover functionality.
     */
    setupMouseEvents() {
      if (!this.slideshow) {
        return;
      }

      // Mouse enter - pause slideshow.
      this.slideshow.addEventListener('mouseenter', () => {
        this.handleMouseEnter();
      });

      // Mouse leave - resume slideshow.
      this.slideshow.addEventListener('mouseleave', () => {
        this.handleMouseLeave();
      });
    }

    /**
     * Handle pointer move event.
     */
    handlePointerMove(e) {
      if (!this.swipeEnabled) {
        return;
      }

      // Only track touch and pen input.
      if (e.pointerType === 'mouse') {
        return;
      }

      // Skip if no start position recorded (no pointerdown received).
      if (this.touchStartX === null || this.touchStartY === null) {
        return;
      }

      const deltaX = Math.abs(e.clientX - this.touchStartX);
      const deltaY = Math.abs(e.clientY - this.touchStartY);

      // Determine if this is a horizontal swipe.
      if (deltaX > deltaY && deltaX > 10) {
        this.isDragging = true;
        // Prevent default to stop scrolling during horizontal swipe.
        e.preventDefault();
      }
    }

    /**
     * Handle pointer down event.
     */
    handlePointerDown(e) {
      if (!this.swipeEnabled || e.pointerType === 'mouse') {
        return;
      }

      if (this.slideshow.setPointerCapture) {
        this.slideshow.setPointerCapture(e.pointerId);
      }

      this.touchStartX = e.clientX;
      this.touchStartY = e.clientY;
      this.isDragging = false;
    }

    /**
     * Handle pointer up event.
     */
    handlePointerUp(e) {
      if (!this.swipeEnabled || e.pointerType === 'mouse') {
        return;
      }

      if (this.slideshow.releasePointerCapture) {
        this.slideshow.releasePointerCapture(e.pointerId);
      }

      this.touchEndX = e.clientX;
      this.touchEndY = e.clientY;

      this.processSwipeGesture();
      this.resetTouchState();
    }

    /**
     * Handle touch start event (legacy fallback).
     */
    handleTouchStart(e) {
      if (!this.swipeEnabled) {
        return;
      }

      const touch = e.touches[0];
      this.touchStartX = touch.clientX;
      this.touchStartY = touch.clientY;
      this.isDragging = false;
    }

    /**
     * Handle touch move event (legacy fallback).
     */
    handleTouchMove(e) {
      if (!this.swipeEnabled) {
        return;
      }

      const touch = e.touches[0];
      const deltaX = Math.abs(touch.clientX - this.touchStartX);
      const deltaY = Math.abs(touch.clientY - this.touchStartY);

      // Determine if this is a horizontal swipe.
      if (deltaX > deltaY && deltaX > 10) {
        this.isDragging = true;
        // Prevent default to stop scrolling during horizontal swipe.
        e.preventDefault();
      }
    }

    /**
     * Handle touch end event (legacy fallback).
     */
    handleTouchEnd(e) {
      if (!this.swipeEnabled) {
        return;
      }

      const touch = e.changedTouches[0];
      this.touchEndX = touch.clientX;
      this.touchEndY = touch.clientY;

      this.processSwipeGesture();
      this.resetTouchState();
    }

    /**
     * Process swipe gesture and trigger navigation.
     */
    processSwipeGesture() {
      const deltaX = this.touchEndX - this.touchStartX;
      const deltaY = Math.abs(this.touchEndY - this.touchStartY);

      // Only process if horizontal movement is significant and vertical is minimal.
      if (Math.abs(deltaX) > this.dragThreshold && deltaY < this.dragThreshold * 1.5) {
        if (deltaX > 0) {
          // Swipe right - previous slide.
          this.core.prevSlide();
        }
        else {
          // Swipe left - next slide.
          this.core.nextSlide();
        }

        // Restart auto-advance.
        this.core.startAutoSlide();

        // Dispatch custom event.
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
      this.touchStartX = null;
      this.touchStartY = null;
      this.touchEndX = null;
      this.touchEndY = null;
      this.isDragging = false;
    }

    /**
     * Handle mouse enter event.
     */
    handleMouseEnter() {
      if (this.pauseOnHover) {
        this.isMouseOver = true;

        // IMMEDIATE: Stop progress before stopping auto-slide.
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
      // but we could add explicit cleanup here if needed.
      this.resetTouchState();
    }
  }

  // Export to global namespace.
  Drupal.vvjs = Drupal.vvjs || {};
  Drupal.vvjs.SlideshowEvents = SlideshowEvents;

})(Drupal);
