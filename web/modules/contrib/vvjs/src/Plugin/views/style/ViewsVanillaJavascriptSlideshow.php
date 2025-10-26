<?php

namespace Drupal\vvjs\Plugin\views\style;

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
   * Set default options.
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['time_in_seconds'] = ['default' => self::TIMING_DEFAULT];
    $options['navigation'] = ['default' => self::NAV_DOTS];
    $options['animation'] = ['default' => self::ANIMATION_BOTTOM];
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
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    // Call parent first to get default Drupal settings.
    parent::buildOptionsForm($form, $form_state);

    // Set weights for default Drupal elements to ensure they come first.
    $this->setDefaultElementWeights($form);

    // Now add your custom sections with higher weights.
    $this->buildWarningMessage($form);
    $this->buildHeroSlideshowSection($form);
    $this->buildTimingSection($form);
    $this->buildNavigationSection($form);
    $this->buildAnimationSection($form);
    $this->buildDisplayOptionsSection($form);
    $this->buildAdvancedOptionsSection($form);
    $this->buildTokenDocumentation($form);
    $this->attachFormAssets($form);
  }

  /**
   * Set weights for default Drupal form elements to ensure proper order.
   */
  protected function setDefaultElementWeights(array &$form): void {
    // Set weights for common default elements (if they exist)
    $default_elements = [
    // Grouping settings.
      'grouping' => -100,
    // Row CSS classes.
      'row_class' => -90,
    // Default row class checkbox.
      'default_row_class' => -85,
    // Uses fields checkbox.
      'uses_fields' => -80,
    // CSS class.
      'class' => -75,
    // Wrapper class.
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
   */
  protected function buildWarningMessage(array &$form): void {
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

    $form['hero_slideshow_section']['layout']['available_breakpoints'] = [
      '#type' => 'select',
      '#title' => $this->t('Available Breakpoints'),
      '#options' => $this->getBreakpointOptions(),
      '#default_value' => $this->options['available_breakpoints'] ?? self::BREAKPOINT_576,
      '#description' => $this->t('Select the maximum screen width (in pixels) at which the Hero should be disabled.'),
    ];
  }

  /**
   * Build hero overlay options.
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
      '#description' => $this->t('Show the bottom slide navigation dots/numbers'),
    ];
  }

  /**
   * Build animation configuration section.
   */
  protected function buildAnimationSection(array &$form): void {
    $form['animation_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Animation & Effects'),
      '#open' => TRUE,
      '#weight' => -10,
    ];

    $form['animation_section']['animation'] = [
      '#type' => 'select',
      '#title' => $this->t('Slide Animation Type'),
      '#options' => $this->getAnimationOptions(),
      '#default_value' => $this->options['animation'] ?? self::ANIMATION_BOTTOM,
      '#description' => $this->t('Choose the animation type for the slides.'),
    ];
  }

  /**
   * Build display options section.
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
   * Build advanced options section.
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
      '#title' => $this->t('Enable CSS Library'),
      '#default_value' => $this->options['enable_css'] ?? TRUE,
      '#description' => $this->t('Check this box to include the CSS library for styling the slideshow.'),
    ];
  }

  /**
   * Build token documentation section.
   */
  protected function buildTokenDocumentation(array &$form): void {
    $form['vvjs_token_info'] = [
      '#type' => 'details',
      '#title' => $this->t('VVJS Tokens'),
      '#open' => FALSE,
      '#weight' => 20,
    ];

    $form['vvjs_token_info']['description'] = [
      '#markup' => $this->t('<p>When using <em>Global: Text area</em> or <em>Global: Unfiltered text</em> in the Views header, footer, or empty text areas, the default Twig-style tokens (e.g., <code>{{ title }}</code>) will not work with the VVJS style plugin.</p>
        <p>Instead, use the custom VVJS token format to access field values from the <strong>first row</strong> of the View result:</p>
        <ul>
          <li><code>[vvjs:field_name]</code> – The rendered output of the field (e.g., linked title, image, formatted text).</li>
          <li><code>[vvjs:field_name:plain]</code> – A plain-text version of the field, with all HTML stripped.</li>
        </ul>
        <p>Examples:</p>
        <ul>
          <li><code>{{ title }}</code> ➜ <code>[vvjs:title]</code></li>
          <li><code>{{ field_image }}</code> ➜ <code>[vvjs:field_image]</code></li>
          <li><code>{{ body }}</code> ➜ <code>[vvjs:body:plain]</code></li>
        </ul>
        <p>These tokens offer safe and flexible field output for dynamic headings, summaries, and fallback messages in VVJS-enabled Views.</p>'),
    ];
  }

  /**
   * Attach form assets.
   */
  protected function attachFormAssets(array &$form): void {
    $form['#attached']['library'][] = 'core/drupal.ajax';
    $form['#attached']['library'][] = 'vvjs/opacity';

    $form['#attached']['drupalSettings']['vvjs'] = [
      'heroSlideshowSelector' => 'input[name="style_options[hero_slideshow_section][hero_slideshow]"]',
      'opacityValueSelector' => '#background-opacity-value',
    ];
  }

  /**
   * Get animation type options.
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
   * Get breakpoint options.
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
   * Get arrow position options.
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
   * Get navigation options.
   */
  protected function getNavigationOptions(): array {
    return [
      self::NAV_NONE => $this->t('None'),
      self::NAV_DOTS => $this->t('Dots'),
      self::NAV_NUMBERS => $this->t('Numbers'),
    ];
  }

  /**
   * Get overlay position options.
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
   * Get timing options.
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
   * Generates a deterministic unique ID for the view display.
   */
  protected function generateUniqueId(): int {
    if ($this->cachedUniqueId === NULL) {
      $identifier = ($this->view->id() ?? 'unknown') . '_' . ($this->view->current_display ?? 'default');
      $this->cachedUniqueId = (int) abs(crc32($identifier));

      // Ensure 8-digit number like original.
      if ($this->cachedUniqueId < 10000000) {
        $this->cachedUniqueId += 10000000;
      }
      if ($this->cachedUniqueId > 99999999) {
        $this->cachedUniqueId = $this->cachedUniqueId % 90000000 + 10000000;
      }
    }

    return $this->cachedUniqueId;
  }

  /**
   * Validate form input values.
   */
  protected function validateFormValues(FormStateInterface $form_state): array {
    $errors = [];
    $values = $form_state->getValues();

    // Extract values from nested form structure.
    $hero_values = $values['style_options']['hero_slideshow_section'] ?? [];
    $timing_values = $values['style_options']['timing_section'] ?? [];
    $display_values = $values['style_options']['display_section'] ?? [];

    // Validate numeric ranges.
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

    // Validate hex color.
    if (isset($hero_values['overlay']['overlay_bg_color'])) {
      $color = $hero_values['overlay']['overlay_bg_color'];
      if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
        $errors[] = $this->t('Overlay background color must be a valid hex color (e.g., #000000).');
      }
    }

    // Validate opacity.
    if (isset($hero_values['overlay']['overlay_bg_opacity'])) {
      $opacity = (float) $hero_values['overlay']['overlay_bg_opacity'];
      if ($opacity < 0 || $opacity > 1) {
        $errors[] = $this->t('Overlay opacity must be between 0 and 1.');
      }
    }

    // Validate timing dependencies.
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
   * Flatten nested form values to match original structure.
   */
  protected function flattenFormValues(array $values): array {
    $flattened = [];

    // Hero slideshow values.
    if (isset($values['hero_slideshow_section'])) {
      $hero = $values['hero_slideshow_section'];
      $flattened['hero_slideshow'] = $hero['hero_slideshow'] ?? FALSE;

      if (isset($hero['layout'])) {
        $flattened['max_width'] = $hero['layout']['max_width'] ?? self::DEFAULT_MAX_WIDTH;
        $flattened['min_height'] = $hero['layout']['min_height'] ?? self::DEFAULT_MIN_HEIGHT;
        $flattened['max_content_width'] = $hero['layout']['max_content_width'] ?? self::DEFAULT_CONTENT_WIDTH;
        $flattened['available_breakpoints'] = $hero['layout']['available_breakpoints'] ?? self::BREAKPOINT_576;
      }

      if (isset($hero['overlay'])) {
        $flattened['overlay_position'] = $hero['overlay']['overlay_position'] ?? self::OVERLAY_MIDDLE;
        $flattened['overlay_bg_color'] = $hero['overlay']['overlay_bg_color'] ?? '#000000';
        $flattened['overlay_bg_opacity'] = $hero['overlay']['overlay_bg_opacity'] ?? '0.3';
      }
    }

    // Timing values.
    if (isset($values['timing_section'])) {
      $flattened['time_in_seconds'] = $values['timing_section']['time_in_seconds'] ?? self::TIMING_DEFAULT;
    }

    // Navigation values.
    if (isset($values['navigation_section'])) {
      $flattened['arrows'] = $values['navigation_section']['arrows'] ?? self::ARROWS_TOP;
      $flattened['navigation'] = $values['navigation_section']['navigation'] ?? self::NAV_DOTS;
    }

    // Animation values.
    if (isset($values['animation_section'])) {
      $flattened['animation'] = $values['animation_section']['animation'] ?? self::ANIMATION_BOTTOM;
    }

    // Display values.
    if (isset($values['display_section'])) {
      $display = $values['display_section'];
      $flattened['show_total_slides'] = $display['show_total_slides'] ?? FALSE;
      $flattened['show_slide_progress'] = $display['show_slide_progress'] ?? FALSE;
      $flattened['show_play_pause'] = $display['show_play_pause'] ?? TRUE;
    }

    // Advanced values.
    if (isset($values['advanced_section'])) {
      $flattened['enable_css'] = $values['advanced_section']['enable_css'] ?? TRUE;
    }

    // Preserve unique_id.
    $flattened['unique_id'] = $this->options['unique_id'] ?? $this->generateUniqueId();

    return $flattened;
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    // Flatten nested form values to match original structure.
    $values = $form_state->getValue('style_options', []);
    $flattened_values = $this->flattenFormValues($values);

    // Update form state with flattened values.
    $form_state->setValue('style_options', $flattened_values);

    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * Renders the view with the slideshow style.
   *
   * @return array
   *   A render array for the slideshow.
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

    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): array {
    $errors = parent::validate();

    // Validate hero slideshow requirements.
    if (!empty($this->options['hero_slideshow']) && !$this->usesFields()) {
      $errors[] = $this->t('Hero Slideshow option requires Fields as row style.');
    }

    // Validate timing and display option dependencies.
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
