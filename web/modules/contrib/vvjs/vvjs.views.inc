<?php

/**
 * @file
 * Contains the hook implementations for the custom Views style plugin.
 *
 * Enhanced with comprehensive validation, better organization, and proper
 * data type constraints for the VVJS slideshow configuration.
 */

use Drupal\vvjs\VvjsConstants;

/**
 * Implements hook_views_data_alter().
 */
function vvjs_views_data_alter(array &$data): void {
  // Define the comprehensive style plugin schema with validation.
  $data['views_style_plugin']['views_vvjs'] = [
    'type' => 'views_style',
    'label' => t('Views Vanilla JavaScript Slideshow'),
    'mapping' => _vvjs_build_views_mapping(),
  ];
}

/**
 * Build the complete Views mapping configuration.
 *
 * @return array
 *   Complete mapping array with validation constraints.
 */
function _vvjs_build_views_mapping(): array {
  return array_merge(
    _vvjs_build_timing_mapping(),
    _vvjs_build_navigation_mapping(),
    _vvjs_build_animation_mapping(),
    _vvjs_build_hero_mapping(),
    _vvjs_build_display_mapping(),
    _vvjs_build_technical_mapping()
  );
}

/**
 * Build timing and auto-advance configuration mapping.
 *
 * @return array
 *   Timing-related field mapping.
 */
function _vvjs_build_timing_mapping(): array {
  return [
    'time_in_seconds' => [
      'type' => VvjsConstants::VIEWS_TYPE_INTEGER,
      'label' => t('Auto-advance Time (milliseconds)'),
      'description' => t('Time between automatic slide transitions. Set to 0 to disable auto-advance.'),
      'constraints' => [
        'Range' => [
          'min' => VvjsConstants::VIEWS_MIN_TIME,
          'max' => VvjsConstants::VIEWS_MAX_TIME,
        ],
      ],
    ],
  ];
}

/**
 * Build navigation controls configuration mapping.
 *
 * @return array
 *   Navigation-related field mapping.
 */
function _vvjs_build_navigation_mapping(): array {
  return [
    'navigation' => [
      'type' => VvjsConstants::VIEWS_TYPE_STRING,
      'label' => t('Slide Indicators Type'),
      'description' => t('Type of navigation indicators to display at the bottom of the slideshow.'),
      'constraints' => [
        'Choice' => _vvjs_get_navigation_choices(),
      ],
    ],
    'arrows' => [
      'type' => VvjsConstants::VIEWS_TYPE_STRING,
      'label' => t('Navigation Arrows Position'),
      'description' => t('Position and visibility of navigation arrows for manual slide control.'),
      'constraints' => [
        'Choice' => _vvjs_get_arrow_choices(),
      ],
    ],
  ];
}

/**
 * Build animation and transition configuration mapping.
 *
 * @return array
 *   Animation-related field mapping.
 */
function _vvjs_build_animation_mapping(): array {
  return [
    'animation' => [
      'type' => VvjsConstants::VIEWS_TYPE_STRING,
      'label' => t('Slide Transition Animation'),
      'description' => t('Type of animation effect used when transitioning between slides.'),
      'constraints' => [
        'Choice' => _vvjs_get_animation_choices(),
      ],
    ],
  ];
}

/**
 * Build hero slideshow configuration mapping.
 *
 * @return array
 *   Hero slideshow-related field mapping.
 */
function _vvjs_build_hero_mapping(): array {
  return [
    'hero_slideshow' => [
      'type' => VvjsConstants::VIEWS_TYPE_BOOLEAN,
      'label' => t('Enable Hero Slideshow Mode'),
      'description' => t('Enable full-width hero slideshow with overlay content positioning.'),
    ],
    'overlay_bg_color' => [
      'type' => VvjsConstants::VIEWS_TYPE_STRING,
      'label' => t('Overlay Background Color'),
      'description' => t('Hex color code for the content overlay background (e.g., #000000).'),
      'constraints' => [
        'Regex' => [
          'pattern' => '/^#[0-9A-Fa-f]{6}$/',
          'message' => t('Must be a valid 6-digit hex color code (e.g., #000000).'),
        ],
      ],
    ],
    'overlay_bg_opacity' => [
      'type' => VvjsConstants::VIEWS_TYPE_FLOAT,
      'label' => t('Overlay Background Opacity'),
      'description' => t('Opacity level for the overlay background, from 0 (transparent) to 1 (opaque).'),
      'constraints' => [
        'Range' => [
          'min' => VvjsConstants::VIEWS_MIN_OPACITY,
          'max' => VvjsConstants::VIEWS_MAX_OPACITY,
        ],
      ],
    ],
    'overlay_position' => [
      'type' => VvjsConstants::VIEWS_TYPE_STRING,
      'label' => t('Overlay Content Position'),
      'description' => t('Position of the content overlay within the hero slideshow area.'),
      'constraints' => [
        'Choice' => _vvjs_get_overlay_position_choices(),
      ],
    ],
    'available_breakpoints' => [
      'type' => VvjsConstants::VIEWS_TYPE_STRING,
      'label' => t('Responsive Breakpoint'),
      'description' => t('Maximum screen width at which hero slideshow features are enabled.'),
      'constraints' => [
        'Choice' => _vvjs_get_breakpoint_choices(),
      ],
    ],
    'min_height' => [
      'type' => VvjsConstants::VIEWS_TYPE_INTEGER,
      'label' => t('Minimum Height (viewport width units)'),
      'description' => t('Minimum height for hero slideshow in vw units (1-200).'),
      'constraints' => [
        'Range' => [
          'min' => VvjsConstants::VIEWS_MIN_HEIGHT,
          'max' => VvjsConstants::VIEWS_MAX_HEIGHT,
        ],
      ],
    ],
    'max_content_width' => [
      'type' => VvjsConstants::VIEWS_TYPE_INTEGER,
      'label' => t('Maximum Content Width (percentage)'),
      'description' => t('Maximum width for overlay content as percentage of container (1-100).'),
      'constraints' => [
        'Range' => [
          'min' => VvjsConstants::VIEWS_MIN_CONTENT_WIDTH,
          'max' => VvjsConstants::VIEWS_MAX_CONTENT_WIDTH,
        ],
      ],
    ],
    'max_width' => [
      'type' => VvjsConstants::VIEWS_TYPE_INTEGER,
      'label' => t('Maximum Container Width (pixels)'),
      'description' => t('Maximum width for the slideshow container in pixels (1-9999).'),
      'constraints' => [
        'Range' => [
          'min' => VvjsConstants::VIEWS_MIN_WIDTH,
          'max' => VvjsConstants::VIEWS_MAX_WIDTH,
        ],
      ],
    ],
  ];
}

/**
 * Build display options configuration mapping.
 *
 * @return array
 *   Display-related field mapping.
 */
function _vvjs_build_display_mapping(): array {
  return [
    'show_total_slides' => [
      'type' => VvjsConstants::VIEWS_TYPE_BOOLEAN,
      'label' => t('Show Total Slide Count'),
      'description' => t('Display current slide number and total count (e.g., "1 of 5").'),
    ],
    'show_play_pause' => [
      'type' => VvjsConstants::VIEWS_TYPE_BOOLEAN,
      'label' => t('Show Play/Pause Button'),
      'description' => t('Display play/pause button for manual control of auto-advance.'),
    ],
    'show_slide_progress' => [
      'type' => VvjsConstants::VIEWS_TYPE_BOOLEAN,
      'label' => t('Show Slide Progress Indicator'),
      'description' => t('Display animated progress indicator showing slide timing progress.'),
    ],
  ];
}

/**
 * Build technical configuration mapping.
 *
 * @return array
 *   Technical configuration field mapping.
 */
function _vvjs_build_technical_mapping(): array {
  return [
    'unique_id' => [
      'type' => VvjsConstants::VIEWS_TYPE_INTEGER,
      'label' => t('Unique Slideshow Identifier'),
      'description' => t('Unique numeric identifier for this slideshow instance (auto-generated).'),
      'constraints' => [
        'Range' => [
          'min' => 10000000,
          'max' => 99999999,
        ],
      ],
    ],
    'enable_css' => [
      'type' => VvjsConstants::VIEWS_TYPE_BOOLEAN,
      'label' => t('Enable Default CSS Styling'),
      'description' => t('Include the default CSS library for slideshow styling and layout.'),
    ],
  ];
}

/**
 * Get valid navigation type choices.
 *
 * @return array
 *   Array of valid navigation options.
 */
function _vvjs_get_navigation_choices(): array {
  return ['none', 'dots', 'numbers'];
}

/**
 * Get valid arrow position choices.
 *
 * @return array
 *   Array of valid arrow position options.
 */
function _vvjs_get_arrow_choices(): array {
  return [
    'none',
    'arrows-sides',
    'arrows-sides-big',
    'arrows-top',
    'arrows-top-big',
  ];
}

/**
 * Get valid animation type choices.
 *
 * @return array
 *   Array of valid animation options.
 */
function _vvjs_get_animation_choices(): array {
  return [
    'none',
    'a-zoom',
    'a-fade',
    'a-top',
    'a-bottom',
    'a-left',
    'a-right',
  ];
}

/**
 * Get valid overlay position choices.
 *
 * @return array
 *   Array of valid overlay position options.
 */
function _vvjs_get_overlay_position_choices(): array {
  return [
    'd-full',
    'd-middle',
    'd-left',
    'd-right',
    'd-top',
    'd-bottom',
    'd-top-left',
    'd-top-right',
    'd-bottom-left',
    'd-bottom-right',
    'd-top-middle',
    'd-bottom-middle',
  ];
}

/**
 * Get valid responsive breakpoint choices.
 *
 * @return array
 *   Array of valid breakpoint options.
 */
function _vvjs_get_breakpoint_choices(): array {
  return ['576', '768', '992', '1200', '1400'];
}
