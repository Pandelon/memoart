<?php

declare(strict_types=1);

namespace Drupal\vvjs;

/**
 * Defines constants for the VVJS module.
 *
 * This class contains constants used across the module, primarily for
 * token processing, data attribute mapping, and Views integration.
 */
final class VvjsConstants {

  /**
   * Token namespace for VVJS tokens.
   */
  public const TOKEN_NAMESPACE = 'vvjs';

  /**
   * Plain text token suffix.
   */
  public const TOKEN_PLAIN_SUFFIX = ':plain';

  /**
   * Token pattern for validation.
   *
   * Validates token format: alphanumeric, underscores, optional :plain suffix.
   */
  public const TOKEN_PATTERN = '/^[a-zA-Z0-9_]+(:plain)?$/';

  /**
   * Maximum length for deep link identifier.
   */
  public const DEEPLINK_IDENTIFIER_MAX_LENGTH = 20;

  /**
   * Regular expression pattern for deep link identifier validation.
   */
  public const DEEPLINK_IDENTIFIER_PATTERN = '/^[a-z][a-z0-9-]*[a-z0-9]$/';

  /**
   * Reserved words that cannot be used as deep link identifiers.
   */
  public const DEEPLINK_RESERVED_WORDS = ['slideshow', 'slide', 'vvjs'];

  /**
   * Default opacity value.
   */
  public const DEFAULT_OPACITY = 1;

  /**
   * Views integration field type constants.
   */

  /**
   * Integer field type for Views mapping.
   */
  public const VIEWS_TYPE_INTEGER = 'integer';

  /**
   * String field type for Views mapping.
   */
  public const VIEWS_TYPE_STRING = 'string';

  /**
   * Boolean field type for Views mapping.
   */
  public const VIEWS_TYPE_BOOLEAN = 'boolean';

  /**
   * Float field type for Views mapping.
   */
  public const VIEWS_TYPE_FLOAT = 'float';

  /**
   * Timing constants.
   *
   * VIEWS_MIN_TIME (0) = disabled/static slideshow.
   * TIMING_MIN_ACTIVE = minimum when auto-advance is on.
   */
  public const VIEWS_MIN_TIME = 0;
  public const TIMING_DISABLED = 0;
  public const TIMING_MIN_ACTIVE = 2000;
  public const TIMING_DEFAULT = 5000;

  /**
   * Maximum time value in milliseconds.
   */
  public const VIEWS_MAX_TIME = 15000;

  /**
   * Animation type constants.
   */
  public const ANIMATION_NONE = 'none';
  public const ANIMATION_ZOOM = 'a-zoom';
  public const ANIMATION_FADE = 'a-fade';
  public const ANIMATION_TOP = 'a-top';
  public const ANIMATION_BOTTOM = 'a-bottom';
  public const ANIMATION_LEFT = 'a-left';
  public const ANIMATION_RIGHT = 'a-right';

  /**
   * Breakpoint constants.
   */
  public const BREAKPOINT_576 = '576';
  public const BREAKPOINT_768 = '768';
  public const BREAKPOINT_992 = '992';
  public const BREAKPOINT_1200 = '1200';
  public const BREAKPOINT_1400 = '1400';

  /**
   * Arrow position constants.
   */
  public const ARROWS_NONE = 'none';
  public const ARROWS_SIDES = 'arrows-sides';
  public const ARROWS_SIDES_BIG = 'arrows-sides-big';
  public const ARROWS_TOP = 'arrows-top';
  public const ARROWS_TOP_BIG = 'arrows-top-big';

  /**
   * Navigation type constants.
   */
  public const NAV_NONE = 'none';
  public const NAV_DOTS = 'dots';
  public const NAV_NUMBERS = 'numbers';

  /**
   * Overlay position constants.
   */
  public const OVERLAY_FULL = 'd-full';
  public const OVERLAY_MIDDLE = 'd-middle';
  public const OVERLAY_LEFT = 'd-left';
  public const OVERLAY_RIGHT = 'd-right';
  public const OVERLAY_TOP = 'd-top';
  public const OVERLAY_BOTTOM = 'd-bottom';
  public const OVERLAY_TOP_LEFT = 'd-top-left';
  public const OVERLAY_TOP_RIGHT = 'd-top-right';
  public const OVERLAY_BOTTOM_LEFT = 'd-bottom-left';
  public const OVERLAY_BOTTOM_RIGHT = 'd-bottom-right';
  public const OVERLAY_TOP_MIDDLE = 'd-top-middle';
  public const OVERLAY_BOTTOM_MIDDLE = 'd-bottom-middle';

  /**
   * Default layout/size values.
   */
  public const DEFAULT_MAX_WIDTH = 1200;
  public const DEFAULT_MIN_HEIGHT = 40;
  public const DEFAULT_CONTENT_WIDTH = 60;

  /**
   * Unique ID range for slideshow instances.
   */
  public const UNIQUE_ID_MIN = 10000000;
  public const UNIQUE_ID_MAX = 99999999;

  /**
   * Views validation constraint constants.
   */

  /**
   * Minimum height value in viewport width units.
   */
  public const VIEWS_MIN_HEIGHT = 1;

  /**
   * Maximum height value in viewport width units.
   */
  public const VIEWS_MAX_HEIGHT = 200;

  /**
   * Minimum width value in pixels.
   */
  public const VIEWS_MIN_WIDTH = 1;

  /**
   * Maximum width value in pixels.
   */
  public const VIEWS_MAX_WIDTH = 9999;

  /**
   * Minimum content width value as percentage.
   */
  public const VIEWS_MIN_CONTENT_WIDTH = 1;

  /**
   * Maximum content width value as percentage.
   */
  public const VIEWS_MAX_CONTENT_WIDTH = 100;

  /**
   * Minimum opacity value (0 = transparent).
   */
  public const VIEWS_MIN_OPACITY = 0;

  /**
   * Maximum opacity value (1 = opaque).
   */
  public const VIEWS_MAX_OPACITY = 1;

  /**
   * Transition type constants.
   */
  public const TRANSITION_INSTANT = 'instant';
  public const TRANSITION_CROSSFADE_CLASSIC = 'crossfade-classic';
  public const TRANSITION_CROSSFADE_STAGED = 'crossfade-staged';
  public const TRANSITION_CROSSFADE_DYNAMIC = 'crossfade-dynamic';

  /**
   * Transition duration constraints (milliseconds).
   */
  public const TRANSITION_DURATION_MIN = 200;
  public const TRANSITION_DURATION_MAX = 2000;
  public const TRANSITION_DURATION_DEFAULT = 600;

  /**
   * Scrollable dots configuration constants.
   */
  public const DEFAULT_SCROLLABLE_DOTS_WIDTH = 0;
  public const MIN_SCROLLABLE_DOTS_WIDTH = 120;
  public const MAX_SCROLLABLE_DOTS_WIDTH = 700;

  /**
   * Private constructor to prevent instantiation.
   *
   * This class should only be used for its constants.
   */
  private function __construct() {
    // Prevent instantiation.
  }

}
