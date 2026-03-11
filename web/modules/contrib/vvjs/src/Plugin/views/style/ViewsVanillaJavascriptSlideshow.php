<?php

declare(strict_types=1);

namespace Drupal\vvjs\Plugin\views\style;

use Drupal\views\Plugin\views\field\EntityField;
use Drupal\vvjs\VvjsConstants;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render items in a Slideshow using vanilla JavaScript.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_vvjs",
 *   title = @Translation("Views Vanilla JavaScript Slideshow"),
 *   help = @Translation("Render items in a Slideshow using vanilla JavaScript."),
 *   theme = "views_view_vvjs",
 *   display_types = { "normal" }
 * )
 */
class ViewsVanillaJavascriptSlideshow extends StylePluginBase {

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
   * Timing constants.
   */
  public const TIMING_MIN = 2000;
  public const TIMING_MAX = 15000;
  public const TIMING_DEFAULT = 5000;

  /**
   * Size constraints.
   */
  public const MIN_WIDTH = 1;
  public const MAX_WIDTH = 9999;
  public const MIN_HEIGHT = 1;
  public const MAX_HEIGHT = 200;
  public const MIN_CONTENT_WIDTH = 1;
  public const MAX_CONTENT_WIDTH = 100;
  public const DEFAULT_MAX_WIDTH = 1200;
  public const DEFAULT_MIN_HEIGHT = 40;
  public const DEFAULT_CONTENT_WIDTH = 60;

  /**
   * Does the style plugin use a row plugin.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowClass = TRUE;

  /**
   * Cached unique ID for this view display.
   *
   * @var int|null
   */
  protected ?int $cachedUniqueId = NULL;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['time_in_seconds'] = ['default' => self::TIMING_DEFAULT];
    $options['navigation'] = ['default' => self::NAV_DOTS];
    $options['animation'] = ['default' => self::ANIMATION_BOTTOM];
    $options['transition_type'] = ['default' => VvjsConstants::TRANSITION_INSTANT];
    $options['transition_duration'] = ['default' => VvjsConstants::TRANSITION_DURATION_DEFAULT];
    $options['arrows'] = ['default' => self::ARROWS_TOP];
    $options['unique_id'] = ['default' => $this->generateUniqueId()];
    $options['hero_slideshow'] = ['default' => FALSE];
    $options['overlay_bg_color'] = ['default' => '#000000'];
    $options['overlay_bg_opacity'] = ['default' => '0.3'];
    $options['available_breakpoints'] = ['default' => self::BREAKPOINT_576];
    $options['enable_css'] = ['default' => TRUE];
    $options['min_height'] = ['default' => self::DEFAULT_MIN_HEIGHT];
    $options['max_content_width'] = ['default' => self::DEFAULT_CONTENT_WIDTH];
    $options['max_width'] = ['default' => self::DEFAULT_MAX_WIDTH];
    $options['overlay_position'] = ['default' => self::OVERLAY_MIDDLE];
    $options['show_total_slides'] = ['default' => FALSE];
    $options['show_slide_progress'] = ['default' => FALSE];
    $options['show_play_pause'] = ['default' => TRUE];
    $options['pause_on_hover'] = ['default' => TRUE];
    $options['enable_swipe'] = ['default' => TRUE];
    $options['enable_keyboard'] = ['default' => TRUE];
    $options['enable_looping'] = ['default' => TRUE];
    $options['start_index'] = ['default' => 1];
    $options['enable_deeplink'] = ['default' => FALSE];
    $options['deeplink_identifier'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);

    $this->setDefaultElementWeights($form);
    $this->buildWarningMessage($form);
    $this->buildHeroSlideshowSection($form);
    $this->buildResponsiveSection($form);
    $this->buildDeepLinkingSection($form);
    $this->buildTimingSection($form);
    $this->buildNavigationSection($form);
    $this->buildAnimationSection($form);
    $this->buildDisplayOptionsSection($form);
    $this->buildBehaviorSettingsSection($form);
    $this->buildAdvancedOptionsSection($form);
    $this->buildTokenDocumentation($form);
    $this->attachFormAssets($form);
  }

  /**
   * Set weights for default Drupal form elements to ensure proper order.
   *
   * @param array $form
   *   The form array (passed by reference).
   */
  protected function setDefaultElementWeights(array &$form): void {
    $default_elements = [
      'grouping' => -100,
      'row_class' => -90,
      'default_row_class' => -85,
      'uses_fields' => -80,
      'class' => -75,
      'wrapper_class' => -70,
    ];

    foreach ($default_elements as $element_key => $weight) {
      if (isset($form[$element_key])) {
        $form[$element_key]['#weight'] = $weight;
      }
    }
  }

  /**
   * Build warning message section.
   *
   * @param array $form
   *   The form array (passed by reference).
   */
  protected function buildWarningMessage(array &$form): void {
    if ($this->view->storage->id() === 'vvjs_example') {
      return;
    }
    $form['warning_message'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--status">' . $this->t(
          'Note: To see an example, check the vvjs_example view by clicking <a href="@url">here</a> to edit it.', [
            '@url' => Url::fromRoute('entity.view.edit_form', ['view' => 'vvjs_example'])->toString(),
          ]
      ) . '</div>',
      '#weight' => -50,
    ];
  }

  /**
   * Build hero slideshow configuration section.
   *
   * @param array $form
   *   The form array (passed by reference).
   */
  protected function buildHeroSlideshowSection(array &$form): void {
    $form['hero_slideshow_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Hero Slideshow Configuration'),
      '#open' => TRUE,
      '#weight' => -40,
    ];

    $form['hero_slideshow_section']['hero_slideshow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hero Slideshow'),
      '#default_value' => $this->options['hero_slideshow'] ?? FALSE,
      '#description' => $this->t('Enable this option to create a Hero Slideshow. A Hero Slideshow is a prominent, full-width slideshow often used at the top of a webpage to showcase key content or visuals. It typically features large images with overlaying text or buttons. Note: This requires the row style to be set and the first field in the row to be an image. Additional configuration options will be available once this option is enabled.'),
    ];

    $this->buildHeroLayoutOptions($form);
    $this->buildHeroOverlayOptions($form);
  }

  /**
   * Build hero layout options.
   *
   * @param array $form
   *   The form array (passed by reference).
   */
  protected function buildHeroLayoutOptions(array &$form): void {
    $hero_visible_state = [
      'visible' => [
        ':input[name="style_options[hero_slideshow_section][hero_slideshow]"]' => ['checked' => TRUE],
      ],
    ];

    $form['hero_slideshow_section']['layout'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout Settings'),
      '#open' => TRUE,
      '#states' => $hero_visible_state,
    ];

    $form['hero_slideshow_section']['layout']['max_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Width (px)'),
      '#default_value' => $this->options['max_width'] ?? self::DEFAULT_MAX_WIDTH,
      '#description' => $this->t('Defines the maximum width for the main container of the hero content, typically set in pixels.'),
      '#step' => 1,
      '#min' => self::MIN_WIDTH,
      '#max' => self::MAX_WIDTH,
    ];

    $form['hero_slideshow_section']['layout']['min_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Min Height (vw)'),
      '#default_value' => $this->options['min_height'] ?? self::DEFAULT_MIN_HEIGHT,
      '#description' => $this->t('Specifies the minimum height for the entire hero container, set in viewport width units (vw).'),
      '#step' => 1,
      '#min' => self::MIN_HEIGHT,
      '#max' => self::MAX_HEIGHT,
    ];

    $form['hero_slideshow_section']['layout']['max_content_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Content Width (%)'),
      '#default_value' => $this->options['max_content_width'] ?? self::DEFAULT_CONTENT_WIDTH,
      '#description' => $this->t('Determines the width for the remaining fields within the hero section.'),
      '#step' => 1,
      '#min' => self::MIN_CONTENT_WIDTH,
      '#max' => self::MAX_CONTENT_WIDTH,
    ];

  }

  /**
   * Build hero overlay options.
   *
   * @param array $form
   *   The form array (passed by reference).
   */
  protected function buildHeroOverlayOptions(array &$form): void {
    $hero_visible_state = [
      'visible' => [
        ':input[name="style_options[hero_slideshow_section][hero_slideshow]"]' => ['checked' => TRUE],
      ],
    ];

    $form['hero_slideshow_section']['overlay'] = [
      '#type' => 'details',
      '#title' => $this->t('Overlay Settings'),
      '#open' => TRUE,
      '#states' => $hero_visible_state,
    ];

    $form['hero_slideshow_section']['overlay']['overlay_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay Position'),
      '#options' => $this->getOverlayPositionOptions(),
      '#default_value' => $this->options['overlay_position'] ?? self::OVERLAY_MIDDLE,
      '#description' => $this->t('Select the position where the content overlay will appear within the hero section.'),
    ];

    $form['hero_slideshow_section']['overlay']['overlay_bg_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Overlay Background Color'),
      '#default_value' => $this->options['overlay_bg_color'] ?? '#000000',
      '#description' => $this->t('Choose the background color for the overlay that appears behind the content within the hero section. This helps improve the readability of the overlay content.'),
    ];

    $form['hero_slideshow_section']['overlay']['overlay_bg_opacity'] = [
      '#type' => 'range',
      '#title' => $this->t('Overlay Background Opacity'),
      '#default_value' => $this->options['overlay_bg_opacity'] ?? '0.3',
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.1,
      '#description' => $this->t('Adjust the opacity of the overlay background color for the hero section content. A lower value makes the background more transparent, while a higher value makes it more opaque.'),
      '#suffix' => '<span id="background-opacity-value" class="opacity-value">' . ($this->options['overlay_bg_opacity'] ?? '0.3') . '</span>',
      '#attributes' => [
        'oninput' => 'document.getElementById("background-opacity-value").innerText = this.value;',
      ],
    ];
  }

  /**
   * Build timing configuration section.
   *
   * @param array $form
   *   The form array (passed by reference).
   */
  protected function buildTimingSection(array &$form): void {
    $form['timing_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Timing & Auto-play'),
      '#open' => TRUE,
      '#weight' => -30,
    ];

    $form['timing_section']['time_in_seconds'] = [
      '#type' => 'select',
      '#title' => $this->t('Auto-advance Time'),
      '#options' => $this->getTimingOptions(),
      '#default_value' => $this->options['time_in_seconds'] ?? self::TIMING_DEFAULT,
      '#description' => $this->t('By default, the Slideshow scrolls every 5 seconds. You can modify this interval. If set between 3-15 seconds, a play/pause button appears and the slideshow pauses on mouse hover. To stop the slideshow, set the field value to none.'),
    ];
  }

  /**
   * Build navigation configuration section.
   *
   * @param array $form
   *   The form array (passed by reference).
   */
  protected function buildNavigationSection(array &$form): void {
    $form['navigation_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Navigation Controls'),
      '#open' => TRUE,
      '#weight' => -20,
    ];

    $form['navigation_section']['arrows'] = [
      '#type' => 'select',
      '#title' => $this->t('Slide Navigation Arrows'),
      '#options' => $this->getArrowOptions(),
      '#default_value' => $this->options['arrows'] ?? self::ARROWS_TOP,
      '#description' => $this->t('Side arrows appear beside the slide. Top arrows appear above the slide with low opacity (0.3) and become fully visible on hover. Options marked "big screen only" will only display on screens wider than the selected breakpoint.'),
    ];

    $form['navigation_section']['navigation'] = [
      '#type' => 'select',
      '#title' => $this->t('Slide Indicators (Bottom Navigation Dots/Numbers)'),
      '#options' => $this->getNavigationOptions(),
      '#default_value' => $this->options['navigation'] ?? self::NAV_DOTS,
      '#description' => $this->t('Show the bottom slide navigation dots/numbers. <strong>Note: This feature is required by Deep Linking.</strong>'),
    ];
  }

  /**
   * Build animation and transitions configuration section.
   *
   * @param array $form
   *   The form array (passed by reference).
   */
  protected function buildAnimationSection(array &$form): void {
    $form['animation_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Animation & Effects and Transitions'),
      '#open' => TRUE,
      '#weight' => -10,
    ];

    $form['animation_section']['animation'] = [
      '#type' => 'select',
      '#title' => $this->t('Slide Animation Type'),
      '#options' => $this->getAnimationOptions(),
      '#default_value' => $this->options['animation'] ?? self::ANIMATION_BOTTOM,
      '#description' => $this->t('Choose the animation type for the slides. When set to "None", transition options become available.'),
    ];

    // Transition options - only visible when animation is "none".
    $form['animation_section']['transition_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Transition Type'),
      '#options' => $this->getTransitionOptions(),
      '#default_value' => $this->options['transition_type'] ?? VvjsConstants::TRANSITION_INSTANT,
      '#description' => $this->t('Select the transition effect between slides. Available only when Slide Animation Type is set to "None".'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[animation_section][animation]"]' => ['value' => self::ANIMATION_NONE],
        ],
      ],
    ];

    $form['animation_section']['transition_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Transition Duration'),
      '#description' => $this->t('Duration of the crossfade transition in milliseconds. Recommended: 400-800ms.'),
      '#default_value' => $this->options['transition_duration'] ?? VvjsConstants::TRANSITION_DURATION_DEFAULT,
      '#min' => VvjsConstants::TRANSITION_DURATION_MIN,
      '#max' => VvjsConstants::TRANSITION_DURATION_MAX,
      '#step' => 50,
      '#field_suffix' => $this->t('ms'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[animation_section][animation]"]' => ['value' => self::ANIMATION_NONE],
          ':input[name="style_options[animation_section][transition_type]"]' => [
            ['value' => VvjsConstants::TRANSITION_CROSSFADE_CLASSIC],
            ['value' => VvjsConstants::TRANSITION_CROSSFADE_STAGED],
            ['value' => VvjsConstants::TRANSITION_CROSSFADE_DYNAMIC],
          ],
        ],
      ],
    ];

    $form['animation_section']['transition_help'] = [
      '#type' => 'item',
      '#markup' => $this->t('<div class="vvjs-transitions-help"><strong>Transition Types Explained:</strong><ul>
        <li><strong>Instant:</strong> No transition effect (default, backward compatible)</li>
        <li><strong>Crossfade - Classic:</strong> Both slides fade at the same speed simultaneously (most common)</li>
        <li><strong>Crossfade - Staged:</strong> Outgoing fades quickly, incoming fades slowly with overlap (elegant, smooth)</li>
        <li><strong>Crossfade - Dynamic:</strong> Fast fade-out, slow fade-in (energetic, attention-grabbing)</li>
      </ul>
      <p><strong>Performance Note:</strong> All crossfade effects use GPU-accelerated CSS transitions. Users with "prefers-reduced-motion" enabled will automatically see instant transitions.</p>
      </div>'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[animation_section][animation]"]' => ['value' => self::ANIMATION_NONE],
          ':input[name="style_options[animation_section][transition_type]"]' => [
            ['value' => VvjsConstants::TRANSITION_CROSSFADE_CLASSIC],
            ['value' => VvjsConstants::TRANSITION_CROSSFADE_STAGED],
            ['value' => VvjsConstants::TRANSITION_CROSSFADE_DYNAMIC],
          ],
        ],
      ],
    ];
  }

  /**
   * Build slide transitions section.
   *
   * @param array $form
   *   The form array (passed by reference).
   */
  protected function buildTransitionsSection(array &$form): void {
    $form['transitions_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Slide Transitions'),
      '#description' => $this->t('Control how slides transition from one to another. Crossfade creates smooth blending between slides using CSS transitions.'),
      '#open' => FALSE,
      '#weight' => -9,
    ];

    $form['transitions_section']['transition_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Transition Type'),
      '#options' => $this->getTransitionOptions(),
      '#default_value' => $this->options['transition_type'] ?? VvjsConstants::TRANSITION_INSTANT,
      '#description' => $this->t('Select the transition effect between slides.'),
    ];

    $form['transitions_section']['transition_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Transition Duration'),
      '#description' => $this->t('Duration of the crossfade transition in milliseconds. Recommended: 400-800ms.'),
      '#default_value' => $this->options['transition_duration'] ?? VvjsConstants::TRANSITION_DURATION_DEFAULT,
      '#min' => VvjsConstants::TRANSITION_DURATION_MIN,
      '#max' => VvjsConstants::TRANSITION_DURATION_MAX,
      '#step' => 50,
      '#field_suffix' => $this->t('ms'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[transitions_section][transition_type]"]' => [
            ['value' => VvjsConstants::TRANSITION_CROSSFADE_CLASSIC],
            ['value' => VvjsConstants::TRANSITION_CROSSFADE_STAGED],
            ['value' => VvjsConstants::TRANSITION_CROSSFADE_DYNAMIC],
          ],
        ],
      ],
    ];

    $form['transitions_section']['transition_help'] = [
      '#type' => 'item',
      '#markup' => $this->t('<div class="vvjs-transitions-help"><strong>Transition Types Explained:</strong><ul>
        <li><strong>Instant:</strong> No transition effect (default, backward compatible)</li>
        <li><strong>Crossfade - Classic:</strong> Both slides fade at the same speed simultaneously (most common)</li>
        <li><strong>Crossfade - Staged:</strong> Outgoing fades quickly, incoming fades slowly with overlap (elegant, smooth)</li>
        <li><strong>Crossfade - Dynamic:</strong> Fast fade-out, slow fade-in (energetic, attention-grabbing)</li>
      </ul>
      <p><strong>Performance Note:</strong> All crossfade effects use GPU-accelerated CSS transitions. Users with "prefers-reduced-motion" enabled will automatically see instant transitions.</p>
      </div>'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[transitions_section][transition_type]"]' => [
            ['value' => VvjsConstants::TRANSITION_CROSSFADE_CLASSIC],
            ['value' => VvjsConstants::TRANSITION_CROSSFADE_STAGED],
            ['value' => VvjsConstants::TRANSITION_CROSSFADE_DYNAMIC],
          ],
        ],
      ],
    ];
  }

  /**
   * Build display options section.
   *
   * @param array $form
   *   The form array (passed by reference).
   */
  protected function buildDisplayOptionsSection(array &$form): void {
    $form['display_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Options'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    $timing_enabled_state = [
      'enabled' => [
        ':input[name="style_options[timing_section][time_in_seconds]"]' => ['!value' => '0'],
      ],
    ];

    $form['display_section']['show_total_slides'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Total Slide Number'),
      '#default_value' => $this->options['show_total_slides'] ?? FALSE,
      '#description' => $this->t('Enable this option to display the total number of slides in the slideshow. For example, "Slide 1 of 5".'),
    ];

    $form['display_section']['show_slide_progress'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Animation Progress'),
      '#default_value' => $this->options['show_slide_progress'] ?? FALSE,
      '#description' => $this->t('Enable this option to display a circular animation indicator that updates with each slide change. The animation duration matches the slide transition time. (Time In Seconds >= 2 s)'),
      '#states' => $timing_enabled_state,
    ];

    $form['display_section']['show_play_pause'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Play/Pause Button'),
      '#default_value' => $this->options['show_play_pause'] ?? TRUE,
      '#description' => $this->t('Enable this option to show a play/pause button at the bottom of the slideshow. (Time In Seconds >= 2 s)'),
      '#states' => $timing_enabled_state,
    ];
  }

  /**
   * Build behavior settings section.
   *
   * @param array $form
   *   The form array (passed by reference).
   */
  protected function buildBehaviorSettingsSection(array &$form): void {
    $form['behavior_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Behavior Settings'),
      '#open' => TRUE,
      '#weight' => 5,
    ];

    $timing_enabled_state = [
      'enabled' => [
        ':input[name="style_options[timing_section][time_in_seconds]"]' => ['!value' => '0'],
      ],
    ];

    $form['behavior_section']['pause_on_hover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on Hover'),
      '#default_value' => $this->options['pause_on_hover'] ?? TRUE,
      '#description' => $this->t('Pause the slideshow when the mouse hovers over it. Uncheck to keep the slideshow running on hover.'),
      '#states' => $timing_enabled_state,
    ];

    $form['behavior_section']['enable_swipe'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Touch/Swipe Gestures'),
      '#default_value' => $this->options['enable_swipe'] ?? TRUE,
      '#description' => $this->t('Allow users to navigate slides using touch swipe gestures on mobile devices.'),
    ];

    $form['behavior_section']['enable_keyboard'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Keyboard Navigation'),
      '#default_value' => $this->options['enable_keyboard'] ?? TRUE,
      '#description' => $this->t('Allow users to navigate slides using keyboard arrow keys, Space to pause/play, Home/End to jump to first/last slide.'),
    ];

    $form['behavior_section']['enable_looping'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Looping'),
      '#default_value' => $this->options['enable_looping'] ?? TRUE,
      '#description' => $this->t('When enabled, the slideshow will loop back to the first slide after the last slide. When disabled, it will stop at the last slide.'),
    ];

    $form['behavior_section']['start_index'] = [
      '#type' => 'number',
      '#title' => $this->t('Start Index'),
      '#default_value' => $this->options['start_index'] ?? 1,
      '#min' => 1,
      '#step' => 1,
      '#description' => $this->t('Choose which slide the slideshow should display first when it loads. For example, enter 1 to start with the first slide, 2 for the second, etc. This is useful when you have multiple slideshows side by side and want each to start at a different position for a staggered effect. If the number exceeds the total slides, the slideshow will automatically start from the last slide.'),
    ];
  }

  /**
   * Build advanced options section.
   *
   * @param array $form
   *   The form array (passed by reference).
   */
  protected function buildAdvancedOptionsSection(array &$form): void {
    $form['advanced_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Options'),
      '#open' => FALSE,
      '#weight' => 10,
    ];

    $form['advanced_section']['enable_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Default CSS'),
      '#default_value' => $this->options['enable_css'] ?? TRUE,
      '#description' => $this->t('Include the default CSS library for slideshow styling. Disable if you want to provide custom styles.'),
    ];
  }

  /**
   * Build token documentation section.
   *
   * @param array $form
   *   The form array (passed by reference).
   */
  protected function buildTokenDocumentation(array &$form): void {
    $form['token_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Token Documentation'),
      '#open' => FALSE,
      '#weight' => 100,
    ];

    $form['token_section']['description'] = [
      '#markup' => $this->t('<p>When using <em>Global: Text area</em> or <em>Global: Unfiltered text</em> in the Views header, footer, or empty text areas, the default Twig-style tokens (e.g., <code>{{ title }}</code>) will not work with the VVJS style plugin.</p>
        <p>Instead, use the custom VVJS token format to access field values from the <strong>first row</strong> of the View result:</p>
        <ul>
          <li><code>[vvjs:field_name]</code> — The rendered output of the field (e.g., linked title, image, formatted text).</li>
          <li><code>[vvjs:field_name:plain]</code> — A plain-text version of the field, with all HTML stripped.</li>
        </ul>
        <p>Examples:</p>
        <ul>
          <li><code>{{ title }}</code> → <code>[vvjs:title]</code></li>
          <li><code>{{ field_image }}</code> → <code>[vvjs:field_image]</code></li>
          <li><code>{{ body }}</code> → <code>[vvjs:body:plain]</code></li>
        </ul>
        <p>These tokens offer safe and flexible field output for dynamic headings, summaries, and fallback messages in VVJS-enabled Views.</p>'),
    ];
  }

  /**
   * Attach form assets.
   *
   * @param array $form
   *   The form array (passed by reference).
   */
  protected function attachFormAssets(array &$form): void {
    $form['#attached']['library'][] = 'core/drupal.ajax';
    $form['#attached']['library'][] = 'vvjs/vvjs-opacity';
    $form['#attached']['library'][] = 'vvjs/vvjs-admin';

    $form['#attached']['drupalSettings']['vvjs'] = [
      'heroSlideshowSelector' => 'input[name="style_options[hero_slideshow_section][hero_slideshow]"]',
      'opacityValueSelector' => '#background-opacity-value',
    ];
  }

  /**
   * Get animation options for the select list.
   *
   * @return array
   *   Array of animation options.
   */
  protected function getAnimationOptions(): array {
    return [
      self::ANIMATION_NONE => $this->t('None'),
      self::ANIMATION_ZOOM => $this->t('Zoom'),
      self::ANIMATION_FADE => $this->t('Fade'),
      self::ANIMATION_TOP => $this->t('Slide from Top'),
      self::ANIMATION_BOTTOM => $this->t('Slide from Bottom'),
      self::ANIMATION_LEFT => $this->t('Slide from Left'),
      self::ANIMATION_RIGHT => $this->t('Slide from Right'),
    ];
  }

  /**
   * Get transition type options for the select list.
   *
   * @return array
   *   Array of transition type options.
   */
  protected function getTransitionOptions(): array {
    return [
      VvjsConstants::TRANSITION_INSTANT => $this->t('Instant (no transition)'),
      VvjsConstants::TRANSITION_CROSSFADE_CLASSIC => $this->t('Crossfade - Classic'),
      VvjsConstants::TRANSITION_CROSSFADE_STAGED => $this->t('Crossfade - Staged (elegant)'),
      VvjsConstants::TRANSITION_CROSSFADE_DYNAMIC => $this->t('Crossfade - Dynamic (energetic)'),
    ];
  }

  /**
   * Build responsive configuration section.
   *
   * @param array $form
   *   The form array (passed by reference).
   */
  protected function buildResponsiveSection(array &$form): void {
    $form['responsive_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Responsive Settings'),
      '#open' => TRUE,
      // Between hero (-40) and timing (-30) so it appears near the top.
      '#weight' => -35,
    ];

    $form['responsive_section']['available_breakpoints'] = [
      '#type' => 'select',
      '#title' => $this->t('Responsive breakpoint'),
      '#options' => $this->getBreakpointOptions(),
      '#default_value' => $this->options['available_breakpoints'] ?? self::BREAKPOINT_576,
      '#description' => $this->t('Select the viewport width at which the slideshow switches to its compact responsive layout.'),
    ];
  }

  /**
   * Build deep linking configuration section.
   *
   * @param array $form
   *   The form array (passed by reference).
   */
  protected function buildDeepLinkingSection(array &$form): void {
    $form['deeplink_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Deep Linking Settings'),
      '#open' => TRUE,
      '#weight' => -15,
      '#attributes' => [
        'data-vvjs-deeplink-section' => 'true',
      ],
    ];

    $form['deeplink_section']['enable_deeplink'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Deep Linking'),
      '#description' => $this->t('Enable deep linking to create shareable URLs for specific slides. <strong>Note: This feature requires navigation (dots or numbers) to be enabled.</strong>'),
      '#default_value' => $this->options['enable_deeplink'],
      '#attributes' => [
        'data-vvjs-deeplink-toggle' => 'true',
      ],
    ];

    $form['deeplink_section']['deeplink_identifier'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL Identifier'),
      '#description' => $this->t('Short identifier used in slide links. Example: "gallery" creates links like #gallery-3. Will be automatically cleaned: converted to lowercase, spaces become hyphens, special characters removed.'),
      '#default_value' => $this->options['deeplink_identifier'],
      '#maxlength' => VvjsConstants::DEEPLINK_IDENTIFIER_MAX_LENGTH,
      '#size' => 20,
      '#placeholder' => 'gallery',
      '#wrapper_attributes' => [
        'class' => ['deeplink-identifier-wrapper'],
        'data-vvjs-deeplink-field' => 'true',
      ],
      '#element_validate' => [[$this, 'validateDeeplinkIdentifier']],
    ];
  }

  /**
   * Validates and sanitizes the deep link identifier field.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateDeeplinkIdentifier(array $element, FormStateInterface $form_state): void {
    // Get deep link values.
    $deeplink_values = $form_state->getValue(['style_options', 'deeplink_section']) ?? [];
    $enable_deeplink = !empty($deeplink_values['enable_deeplink']);
    $identifier = (string) ($deeplink_values['deeplink_identifier'] ?? '');

    // Get navigation setting (Slide Indicators).
    $navigation_values = $form_state->getValue(['style_options', 'navigation_section']) ?? [];
    $navigation = $navigation_values['navigation'] ?? self::NAV_DOTS;

    // 1. If deep linking is disabled, ignore identifier and exit early.
    if (!$enable_deeplink) {
      // Optional: clear any stale identifier so config stays clean.
      $form_state->setValue(['style_options', 'deeplink_section', 'deeplink_identifier'], '');
      return;
    }

    // 2. Deep linking is enabled → require navigation ≠ none.
    if ($navigation === self::NAV_NONE) {
      $form_state->setError(
        $element,
        $this->t('Deep Linking requires Slide Indicators (Dots or Numbers) to be enabled. Please set "Slide Indicators (Bottom Navigation Dots/Numbers)" to Dots or Numbers, or disable Deep Linking.')
      );
      return;
    }

    // 3. From here, deep linking is enabled and navigation is valid,
    //    so we enforce identifier rules.
    // Required when deep linking is enabled.
    if ($identifier === '') {
      $form_state->setError($element, $this->t('URL Identifier is required when Deep Linking is enabled.'));
      return;
    }

    // Transliterate and clean similar to URL aliases.
    $transliteration = \Drupal::transliteration();
    $clean = $transliteration->transliterate($identifier, 'en');

    // Convert to lowercase.
    $clean = strtolower($clean);

    // Replace spaces and underscores with hyphens.
    $clean = preg_replace('/[\s_]+/', '-', $clean);

    // Remove all characters except letters, numbers, and hyphens.
    $clean = preg_replace('/[^a-z0-9-]/', '', $clean);

    // Remove consecutive hyphens.
    $clean = preg_replace('/-+/', '-', $clean);

    // Remove leading/trailing hyphens.
    $clean = trim($clean, '-');

    // Ensure it starts with a letter.
    $clean = preg_replace('/^[0-9-]+/', '', $clean);

    // If empty after cleaning, show error.
    if ($clean === '') {
      $form_state->setError($element, $this->t('URL Identifier must contain at least one letter.'));
      return;
    }

    // Check reserved words.
    if (in_array($clean, VvjsConstants::DEEPLINK_RESERVED_WORDS, TRUE)) {
      $form_state->setError(
        $element,
        $this->t('Please choose a more specific identifier. "@identifier" is a reserved word.', ['@identifier' => $clean])
      );
      return;
    }

    // Set the cleaned value back to form state.
    $form_state->setValue(['style_options', 'deeplink_section', 'deeplink_identifier'], $clean);
  }

  /**
   * Get breakpoint options for the select list.
   *
   * @return array
   *   Array of breakpoint options.
   */
  protected function getBreakpointOptions(): array {
    return [
      self::BREAKPOINT_576 => $this->t('576px / 36rem'),
      self::BREAKPOINT_768 => $this->t('768px / 48rem'),
      self::BREAKPOINT_992 => $this->t('992px / 62rem'),
      self::BREAKPOINT_1200 => $this->t('1200px / 75rem'),
      self::BREAKPOINT_1400 => $this->t('1400px / 87.5rem'),
    ];
  }

  /**
   * Get arrow options for the select list.
   *
   * @return array
   *   Array of arrow options.
   */
  protected function getArrowOptions(): array {
    return [
      self::ARROWS_NONE => $this->t('None'),
      self::ARROWS_SIDES => $this->t('Show arrows on the sides'),
      self::ARROWS_SIDES_BIG => $this->t('Show arrows on the sides (big screen only)'),
      self::ARROWS_TOP => $this->t('Show arrows at the top of the slide'),
      self::ARROWS_TOP_BIG => $this->t('Show arrows at the top of the slide (big screen only)'),
    ];
  }

  /**
   * Get navigation options for the select list.
   *
   * @return array
   *   Array of navigation options.
   */
  protected function getNavigationOptions(): array {
    return [
      self::NAV_NONE => $this->t('None'),
      self::NAV_DOTS => $this->t('Dots'),
      self::NAV_NUMBERS => $this->t('Numbers'),
    ];
  }

  /**
   * Get overlay position options for the select list.
   *
   * @return array
   *   Array of overlay position options.
   */
  protected function getOverlayPositionOptions(): array {
    return [
      self::OVERLAY_FULL => $this->t('Full Width'),
      self::OVERLAY_MIDDLE => $this->t('Middle'),
      self::OVERLAY_LEFT => $this->t('Left'),
      self::OVERLAY_RIGHT => $this->t('Right'),
      self::OVERLAY_TOP => $this->t('Top'),
      self::OVERLAY_BOTTOM => $this->t('Bottom'),
      self::OVERLAY_TOP_LEFT => $this->t('Top Left'),
      self::OVERLAY_TOP_RIGHT => $this->t('Top Right'),
      self::OVERLAY_BOTTOM_LEFT => $this->t('Bottom Left'),
      self::OVERLAY_BOTTOM_RIGHT => $this->t('Bottom Right'),
      self::OVERLAY_TOP_MIDDLE => $this->t('Top Middle'),
      self::OVERLAY_BOTTOM_MIDDLE => $this->t('Bottom Middle'),
    ];
  }

  /**
   * Get timing options for the select list.
   *
   * @return array
   *   Array of timing options.
   */
  protected function getTimingOptions(): array {
    return [
      '0' => $this->t('None'),
      '2000' => $this->t('2 s'),
      '3000' => $this->t('3 s'),
      '4000' => $this->t('4 s'),
      '5000' => $this->t('5 s'),
      '6000' => $this->t('6 s'),
      '7000' => $this->t('7 s'),
      '8000' => $this->t('8 s'),
      '9000' => $this->t('9 s'),
      '10000' => $this->t('10 s'),
      '11000' => $this->t('11 s'),
      '12000' => $this->t('12 s'),
      '13000' => $this->t('13 s'),
      '14000' => $this->t('14 s'),
      '15000' => $this->t('15 s'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::validateOptionsForm($form, $form_state);

    $errors = $this->validateFormValues($form_state);
    foreach ($errors as $error) {
      $form_state->setError($form, $error);
    }
  }

  /**
   * Validate form input values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   Array of validation error messages.
   */
  protected function validateFormValues(FormStateInterface $form_state): array {
    $errors = [];
    $values = $form_state->getValues();

    $hero_values = $values['style_options']['hero_slideshow_section'] ?? [];
    $timing_values = $values['style_options']['timing_section'] ?? [];
    $display_values = $values['style_options']['display_section'] ?? [];

    if (isset($hero_values['layout']['max_width'])) {
      $max_width = (int) $hero_values['layout']['max_width'];
      if ($max_width < self::MIN_WIDTH || $max_width > self::MAX_WIDTH) {
        $errors[] = $this->t('Max Width must be between @min and @max pixels.', [
          '@min' => self::MIN_WIDTH,
          '@max' => self::MAX_WIDTH,
        ]);
      }
    }

    if (isset($hero_values['layout']['min_height'])) {
      $min_height = (int) $hero_values['layout']['min_height'];
      if ($min_height < self::MIN_HEIGHT || $min_height > self::MAX_HEIGHT) {
        $errors[] = $this->t('Min Height must be between @min and @max vw.', [
          '@min' => self::MIN_HEIGHT,
          '@max' => self::MAX_HEIGHT,
        ]);
      }
    }

    if (isset($hero_values['layout']['max_content_width'])) {
      $content_width = (int) $hero_values['layout']['max_content_width'];
      if ($content_width < self::MIN_CONTENT_WIDTH || $content_width > self::MAX_CONTENT_WIDTH) {
        $errors[] = $this->t('Content Width must be between @min and @max percent.', [
          '@min' => self::MIN_CONTENT_WIDTH,
          '@max' => self::MAX_CONTENT_WIDTH,
        ]);
      }
    }

    if (isset($hero_values['overlay']['overlay_bg_color'])) {
      $color = $hero_values['overlay']['overlay_bg_color'];
      if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
        $errors[] = $this->t('Overlay background color must be a valid hex color (e.g., #000000).');
      }
    }

    if (isset($hero_values['overlay']['overlay_bg_opacity'])) {
      $opacity = (float) $hero_values['overlay']['overlay_bg_opacity'];
      if ($opacity < 0 || $opacity > 1) {
        $errors[] = $this->t('Overlay opacity must be between 0 and 1.');
      }
    }

    $timing = $timing_values['time_in_seconds'] ?? '0';
    if ($timing === '0') {
      if (!empty($display_values['show_slide_progress'])) {
        $errors[] = $this->t('Slide progress requires auto-advance timing to be enabled.');
      }
      if (!empty($display_values['show_play_pause'])) {
        $errors[] = $this->t('Play/pause button requires auto-advance timing to be enabled.');
      }
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    $values = $form_state->getValue('style_options', []);
    $flattened_values = $this->flattenFormValues($values);

    $form_state->setValue('style_options', $flattened_values);

    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * Flatten nested form values to match original structure.
   *
   * @param array $values
   *   Nested form values.
   *
   * @return array
   *   Flattened values array.
   */
  protected function flattenFormValues(array $values): array {
    $flattened = [];

    if (isset($values['hero_slideshow_section'])) {
      $hero = $values['hero_slideshow_section'];
      $flattened['hero_slideshow'] = $hero['hero_slideshow'] ?? FALSE;

      if (isset($hero['layout'])) {
        $flattened['max_width'] = $hero['layout']['max_width'] ?? self::DEFAULT_MAX_WIDTH;
        $flattened['min_height'] = $hero['layout']['min_height'] ?? self::DEFAULT_MIN_HEIGHT;
        $flattened['max_content_width'] = $hero['layout']['max_content_width'] ?? self::DEFAULT_CONTENT_WIDTH;
      }

      if (isset($hero['overlay'])) {
        $flattened['overlay_position'] = $hero['overlay']['overlay_position'] ?? self::OVERLAY_MIDDLE;
        $flattened['overlay_bg_color'] = $hero['overlay']['overlay_bg_color'] ?? '#000000';
        $flattened['overlay_bg_opacity'] = $hero['overlay']['overlay_bg_opacity'] ?? '0.3';
      }
    }

    if (isset($values['responsive_section']['available_breakpoints'])) {
      $flattened['available_breakpoints'] = $values['responsive_section']['available_breakpoints'] ?? self::BREAKPOINT_576;
    }

    if (isset($values['deeplink_section'])) {
      $flattened['enable_deeplink'] = $values['deeplink_section']['enable_deeplink'] ?? FALSE;
      $flattened['deeplink_identifier'] = $values['deeplink_section']['deeplink_identifier'] ?? '';
    }

    if (isset($values['timing_section'])) {
      $flattened['time_in_seconds'] = $values['timing_section']['time_in_seconds'] ?? self::TIMING_DEFAULT;
    }

    if (isset($values['navigation_section'])) {
      $flattened['arrows'] = $values['navigation_section']['arrows'] ?? self::ARROWS_TOP;
      $flattened['navigation'] = $values['navigation_section']['navigation'] ?? self::NAV_DOTS;
    }

    if (isset($values['animation_section'])) {
      $animation = $values['animation_section']['animation'] ?? self::ANIMATION_BOTTOM;
      $flattened['animation'] = $animation;

      // Only preserve transition values when animation is "none".
      if ($animation === self::ANIMATION_NONE) {
        $flattened['transition_type'] = $values['animation_section']['transition_type'] ?? VvjsConstants::TRANSITION_INSTANT;
        $flattened['transition_duration'] = $values['animation_section']['transition_duration'] ?? VvjsConstants::TRANSITION_DURATION_DEFAULT;
      }
      else {
        // Clear transition values when animation is not "none".
        $flattened['transition_type'] = VvjsConstants::TRANSITION_INSTANT;
        $flattened['transition_duration'] = VvjsConstants::TRANSITION_DURATION_DEFAULT;
      }
    }

    if (isset($values['display_section'])) {
      $display = $values['display_section'];
      $flattened['show_total_slides'] = $display['show_total_slides'] ?? FALSE;
      $flattened['show_slide_progress'] = $display['show_slide_progress'] ?? FALSE;
      $flattened['show_play_pause'] = $display['show_play_pause'] ?? TRUE;
    }

    if (isset($values['behavior_section'])) {
      $behavior = $values['behavior_section'];
      $flattened['pause_on_hover'] = $behavior['pause_on_hover'] ?? TRUE;
      $flattened['enable_swipe'] = $behavior['enable_swipe'] ?? TRUE;
      $flattened['enable_keyboard'] = $behavior['enable_keyboard'] ?? TRUE;
      $flattened['enable_looping'] = $behavior['enable_looping'] ?? TRUE;
      $flattened['start_index'] = (int) ($behavior['start_index'] ?? 1);
    }

    if (isset($values['advanced_section'])) {
      $flattened['enable_css'] = $values['advanced_section']['enable_css'] ?? TRUE;
    }

    $flattened['unique_id'] = $this->options['unique_id'] ?? $this->generateUniqueId();

    return $flattened;
  }

  /**
   * Generates a unique numeric ID for the view display.
   *
   * @return int
   *   A unique ID between 10000000 and 99999999.
   *
   * @throws \Exception
   *   If an appropriate source of randomness cannot be found.
   */
  protected function generateUniqueId(): int {
    if ($this->cachedUniqueId !== NULL) {
      return $this->cachedUniqueId;
    }

    $this->cachedUniqueId = random_int(10000000, 99999999);

    if ($this->cachedUniqueId < 10000000) {
      $this->cachedUniqueId += 10000000;
    }
    if ($this->cachedUniqueId > 99999999) {
      $this->cachedUniqueId = $this->cachedUniqueId % 90000000 + 10000000;
    }

    return $this->cachedUniqueId;
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $rows = [];

    if (!empty($this->view->result)) {
      foreach ($this->view->result as $row) {
        $rendered_row = $this->view->rowPlugin->render($row);
        if ($rendered_row !== NULL) {
          $rows[] = $rendered_row;
        }
      }
    }

    $libraries = $this->buildLibraryList();

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
      '#unique_id' => $this->options['unique_id'] ?? $this->generateUniqueId(),
      '#attached' => [
        'library' => $libraries,
      ],
    ];

    return $build;
  }

  /**
   * Build the list of libraries to attach.
   *
   * @return array
   *   An array of library names to attach.
   */
  protected function buildLibraryList(): array {
    $libraries = [
      'vvjs/vvjs',
      'vvjs/vvjs__' . ($this->options['available_breakpoints'] ?? self::BREAKPOINT_576),
    ];

    if (!empty($this->options['hero_slideshow'])) {
      $libraries[] = 'vvjs/vvjs-hero';
      $libraries[] = 'vvjs/vvjs-hero__' . ($this->options['available_breakpoints'] ?? self::BREAKPOINT_576);
    }

    if (!empty($this->options['enable_css'])) {
      $libraries[] = 'vvjs/vvjs-style';
    }

    // Add transitions library if crossfade is enabled.
    $transitionType = $this->options['transition_type'] ?? VvjsConstants::TRANSITION_INSTANT;
    if (str_starts_with($transitionType, 'crossfade')) {
      $libraries[] = 'vvjs/vvjs-transitions';
    }

    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): array {
    $errors = parent::validate();

    if (!empty($this->options['hero_slideshow'])) {
      // Check if using fields.
      if (!$this->usesFields()) {
        $errors[] = $this->t('Hero Slideshow option requires Fields as row style.');
      }
      else {
        // Check if first field is an image.
        $fields = $this->view->display_handler->getHandlers('field');

        if (empty($fields)) {
          $errors[] = $this->t('Hero Slideshow requires at least one field to be configured.');
        }
        else {
          $first_field = reset($fields);
          $is_image = FALSE;

          // Check if it's an EntityField (not a global or custom field).
          if ($first_field instanceof EntityField) {
            // Get field name from the field's definition.
            $field_name = $first_field->definition['field_name'] ?? NULL;

            if ($field_name) {
              // Get the entity type from the view.
              $entity_type_id = $this->view->getBaseEntityType()->id();

              // Use entity field manager service to get
              // field storage definitions.
              $entity_field_manager = \Drupal::service('entity_field.manager');
              $field_storage_definitions = $entity_field_manager->getFieldStorageDefinitions($entity_type_id);

              // Check if this field exists and is an image type.
              if (isset($field_storage_definitions[$field_name])) {
                $field_type = $field_storage_definitions[$field_name]->getType();
                $is_image = ($field_type === 'image');
              }
            }
          }

          if (!$is_image) {
            $errors[] = $this->t('Hero Slideshow requires the first field to be an Image field. Please add an image field as the first field in your Fields configuration.');
          }
        }
      }
    }

    $timing = $this->options['time_in_seconds'] ?? '0';
    if ($timing === '0') {
      if (!empty($this->options['show_slide_progress'])) {
        $errors[] = $this->t('Slide progress indicator requires auto-advance timing to be enabled (cannot be "None").');
      }
      if (!empty($this->options['show_play_pause'])) {
        $errors[] = $this->t('Play/pause button requires auto-advance timing to be enabled (cannot be "None").');
      }
    }

    return $errors;
  }

}
