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
   * Data attribute prefix for HTML attributes.
   */
  public const DATA_ATTRIBUTE_PREFIX = 'data-';

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
   * Data attribute mapping for slideshow options.
   *
   * Maps internal option keys to HTML data attribute names.
   */
  public const DATA_ATTRIBUTE_MAP = [
    'animation' => 'animation',
    'navigation' => 'navigation',
    'time_in_seconds' => 'time-in-seconds',
    'arrows' => 'arrows',
    'unique_id' => 'unique-id',
    'available_breakpoints' => 'available-breakpoints',
    'min_height' => 'min-height',
    'max_content_width' => 'max-content-width',
    'max_width' => 'max-width',
    'start_index' => 'start-index',
  ];

  /**
   * Boolean option mapping for slideshow controls.
   *
   * Maps internal boolean option keys to HTML data attribute names.
   */
  public const BOOLEAN_ATTRIBUTE_MAP = [
    'show_play_pause' => 'show-play-pause',
    'show_slide_progress' => 'show-slide-animation',
    'show_total_slides' => 'show-total-slides',
    'hero_slideshow' => 'hero-slideshow',
    'enable_css' => 'enable-css',
    'pause_on_hover' => 'pause-on-hover',
    'enable_swipe' => 'enable-swipe',
    'enable_keyboard' => 'enable-keyboard',
    'enable_looping' => 'enable-looping',
  ];

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
   * Views validation constraint constants.
   */

  /**
   * Minimum time value in milliseconds.
   */
  public const VIEWS_MIN_TIME = 0;

  /**
   * Maximum time value in milliseconds.
   */
  public const VIEWS_MAX_TIME = 15000;

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
   * Private constructor to prevent instantiation.
   *
   * This class should only be used for its constants.
   */
  private function __construct() {
    // Prevent instantiation.
  }

}
