<?php

declare(strict_types=1);

namespace Drupal\vvjs;

/**
 * Defines constants for the VVJS module.
 *
 * This class contains all module-wide constants to avoid global namespace
 * pollution and provide better organization.
 */
final class VvjsConstants {

  /**
   * Data attribute prefix for HTML attributes.
   */
  public const DATA_ATTRIBUTE_PREFIX = 'data-';

  /**
   * Default hex color value.
   */
  public const DEFAULT_HEX_COLOR = '#000000';

  /**
   * Default opacity value.
   */
  public const DEFAULT_OPACITY = 1;

  /**
   * Regular expression pattern for 6-digit hex colors.
   */
  public const HEX_COLOR_PATTERN = '/^#[0-9A-Fa-f]{6}$/';

  /**
   * Regular expression pattern for 3-digit hex colors.
   */
  public const SHORT_HEX_COLOR_PATTERN = '/^#[0-9A-Fa-f]{3}$/';

  /**
   * Token namespace for VVJS tokens.
   */
  public const TOKEN_NAMESPACE = 'vvjs';

  /**
   * Plain text token suffix.
   */
  public const TOKEN_PLAIN_SUFFIX = ':plain';

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
   * Private constructor to prevent instantiation.
   *
   * This class should only be used for its constants.
   */
  private function __construct() {
    // Prevent instantiation.
  }

}
