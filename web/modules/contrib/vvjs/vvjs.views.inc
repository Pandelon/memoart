<?php

/**
 * @file
 * Contains the hook implementations for the custom Views style plugin.
 */

/**
 * Implements hook_views_data_alter().
 */
function vvjs_views_data_alter(array &$data): void {
  // Define the style plugin schema.
  $data['views_style_plugin']['views_vvjs'] = [
    'type' => 'views_style',
    'label' => t('Views Vanilla JavaScript Slideshow'),
    'mapping' => [
      'time_in_seconds' => [
        'type' => 'integer',
        'label' => t('Time In Seconds'),
      ],
      'navigation' => [
        'type' => 'string',
        'label' => t('Navigation Type'),
      ],
      'animation' => [
        'type' => 'string',
        'label' => t('Animation Type'),
      ],
      'arrows' => [
        'type' => 'string',
        'label' => t('Slide Navigation Arrows'),
      ],
      'unique_id' => [
        'type' => 'string',
        'label' => t('Unique ID for the view display'),
      ],
      'hero_slideshow' => [
        'type' => 'boolean',
        'label' => t('Enable Hero Slideshow'),
      ],
      'show_total_slides' => [
        'type' => 'boolean',
        'label' => t('Show Total Slides'),
      ],
      'show_play_pause' => [
        'type' => 'boolean',
        'label' => t('Show Play Pause Button'),
      ],
      'show_slide_progress' => [
        'type' => 'boolean',
        'label' => t('Show Slide Animation'),
      ],
      'overlay_bg_color' => [
        'type' => 'string',
        'label' => t('Overlay Background Color'),
      ],
      'overlay_bg_opacity' => [
        'type' => 'float',
        'label' => t('Overlay Background Opacity'),
      ],
      'available_breakpoints' => [
        'type' => 'string',
        'label' => t('Available Breakpoints for Hero'),
      ],
      'enable_css' => [
        'type' => 'boolean',
        'label' => t('Enable CSS Library'),
      ],
      'min_height' => [
        'type' => 'integer',
        'label' => t('Min Height (vw)'),
      ],
      'max_content_width' => [
        'type' => 'integer',
        'label' => t('Max Content Width (%)'),
      ],
      'max_width' => [
        'type' => 'integer',
        'label' => t('Max Width (px)'),
      ],
      'overlay_position' => [
        'type' => 'string',
        'label' => t('Overlay Position'),
      ],
    ],
  ];
}
