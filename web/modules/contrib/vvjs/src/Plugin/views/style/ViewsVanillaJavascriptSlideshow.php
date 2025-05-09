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
   * Does the style plugin use a row plugin.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Set default options.
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['time_in_seconds'] = ['default' => 5000];
    $options['navigation'] = ['default' => 'dots'];
    $options['animation'] = ['default' => 'a-bottom'];
    $options['arrows'] = ['default' => 'arrows-top'];
    $options['unique_id'] = ['default' => $this->generateUniqueId()];
    $options['hero_slideshow'] = ['default' => FALSE];
    $options['overlay_bg_color'] = ['default' => '#000000'];
    $options['overlay_bg_opacity'] = ['default' => '0.3'];
    $options['available_breakpoints'] = ['default' => '576'];
    $options['enable_css'] = ['default' => TRUE];
    $options['min_height'] = ['default' => 40];
    $options['max_content_width'] = ['default' => 60];
    $options['max_width'] = ['default' => 1200];
    $options['overlay_position'] = ['default' => 'd-middle'];
    $options['show_total_slides'] = ['default' => FALSE];
    $options['show_slide_progress'] = ['default' => FALSE];
    $options['show_play_pause'] = ['default' => TRUE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);

    $form['warning_message'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--status">' . $this->t(
          'Note: To see an example, check the vvjs_example view by clicking <a href="@url">here</a> to edit it.', [
            '@url' => Url::fromRoute('entity.view.edit_form', ['view' => 'vvjs_example'])->toString(),
          ]
      ) . '</div>',
    ];

    $form['hero_slideshow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hero Slideshow'),
      '#default_value' => $this->options['hero_slideshow'],
      '#description' => $this->t('Enable this option to create a Hero Slideshow. A Hero Slideshow is a prominent, full-width slideshow often used at the top of a webpage to showcase key content or visuals. It typically features large images with overlaying text or buttons. Note: This requires the row style to be set and the first field in the row to be an image. Additional configuration options will be available once this option is enabled.'),
    ];

    $form['available_breakpoints'] = [
      '#type' => 'select',
      '#title' => $this->t('Available Breakpoints for Hero'),
      '#options' => [
        '576' => $this->t('576px / 36rem'),
        '768' => $this->t('768px / 48rem'),
        '992' => $this->t('992px / 62rem'),
        '1200' => $this->t('1200px / 75rem'),
        '1400' => $this->t('1400px / 87.5rem'),
      ],
      '#default_value' => $this->options['available_breakpoints'],
      '#description' => $this->t('Select the maximum screen width (in pixels) at which the Hero should be disabled.'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[hero_slideshow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['max_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Width (px)'),
      '#default_value' => $this->options['max_width'],
      '#description' => $this->t('Defines the maximum width for the main container of the hero content, typically set in pixels.'),
      '#step' => 1,
      '#min' => 1,
      '#states' => [
        'visible' => [
          ':input[name="style_options[hero_slideshow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['min_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Min Height (vw)'),
      '#default_value' => $this->options['min_height'],
      '#description' => $this->t('Specifies the minimum height for the entire hero container, set in viewport width units (vw).'),
      '#step' => 1,
      '#min' => 1,
      '#states' => [
        'visible' => [
          ':input[name="style_options[hero_slideshow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['max_content_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Content Width (%)'),
      '#default_value' => $this->options['max_content_width'],
      '#description' => $this->t('Determines the width for the remaining fields within the hero section.'),
      '#step' => 1,
      '#min' => 1,
      '#max' => 100,
      '#states' => [
        'visible' => [
          ':input[name="style_options[hero_slideshow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['overlay_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay Position'),
      '#options' => [
        'd-middle' => $this->t('Middle'),
        'd-left' => $this->t('Left'),
        'd-right' => $this->t('Right'),
        'd-top' => $this->t('Top'),
        'd-bottom' => $this->t('Bottom'),
        'd-top-left' => $this->t('Top Left'),
        'd-top-right' => $this->t('Top Right'),
        'd-bottom-left' => $this->t('Bottom Left'),
        'd-bottom-right' => $this->t('Bottom Right'),
        'd-top-middle' => $this->t('Top Middle'),
        'd-bottom-middle' => $this->t('Bottom Middle'),
      ],
      '#default_value' => $this->options['overlay_position'],
      '#description' => $this->t('Select the position where the content overlay will appear within the hero section.'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[hero_slideshow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['overlay_bg_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Overlay Background Color'),
      '#default_value' => $this->options['overlay_bg_color'],
      '#description' => $this->t('Choose the background color for the overlay that appears behind the content within the hero section. This helps improve the readability of the overlay content.'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[hero_slideshow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['overlay_bg_opacity'] = [
      '#type' => 'range',
      '#title' => $this->t('Overlay Background Opacity'),
      '#default_value' => $this->options['overlay_bg_opacity'],
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.1,
      '#description' => $this->t('Adjust the opacity of the overlay background color for the hero section content. A lower value makes the background more transparent, while a higher value makes it more opaque.'),
      '#suffix' => '<span id="background-opacity-value" class="opacity-value">' . $this->options['overlay_bg_opacity'] . '</span>',
      '#attributes' => [
        'oninput' => 'document.getElementById("background-opacity-value").innerText = this.value;',
      ],
      '#states' => [
        'visible' => [
          ':input[name="style_options[hero_slideshow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['time_in_seconds'] = [
      '#type' => 'select',
      '#title' => $this->t('Time In Seconds'),
      '#options' => [
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
      ],
      '#default_value' => $this->options['time_in_seconds'],
      '#description' => $this->t('By default, the Slideshow scrolls every 5 seconds. You can modify this interval. If set between 3-15 seconds, a play/pause button appears and the slideshow pauses on mouse hover. To stop the slideshow, set the field value to none.'),
    ];

    $form['arrows'] = [
      '#type' => 'select',
      '#title' => $this->t('Slide Navigation Arrows'),
      '#options' => [
        'none' => $this->t('None'),
        'arrows-sides' => $this->t('Show arrows on the sides'),
        'arrows-top' => $this->t('Show arrows at the top of the slide'),
      ],
      '#default_value' => $this->options['arrows'],
      '#description' => $this->t('Side arrows are always visible, while top arrows are hidden by default and appear when you hover over the slide.'),
    ];

    $form['navigation'] = [
      '#type' => 'select',
      '#title' => $this->t('Slide Indicators (Bottom Navigation Dots/Numbers)'),
      '#options' => [
        'none' => $this->t('None'),
        'dots' => $this->t('Dots'),
        'numbers' => $this->t('Numbers'),
      ],
      '#default_value' => $this->options['navigation'],
      '#description' => $this->t('Show the bottom slide navigation dots/numbers'),
    ];

    $form['animation'] = [
      '#type' => 'select',
      '#title' => $this->t('Slide Animation Type'),
      '#options' => [
        'none' => $this->t('None'),
        'a-zoom' => $this->t('Zoom'),
        'a-fade' => $this->t('Fade'),
        'a-top' => $this->t('Slide from Top'),
        'a-bottom' => $this->t('Slide from Bottom'),
        'a-left' => $this->t('Slide from Left'),
        'a-right' => $this->t('Slide from Right'),
      ],
      '#default_value' => $this->options['animation'],
      '#description' => $this->t('Choose the animation type for the slides.'),
    ];
    $form['#attached']['library'][] = 'core/drupal.ajax';

    $form['#attached']['drupalSettings']['vvjs'] = [
      'heroSlideshowSelector' => 'input[name="style_options[hero_slideshow]"]',
      'opacityValueSelector' => '#background-opacity-value',
    ];

    $form['#attached']['library'][] = 'vvjs/opacity';

    $form['show_total_slides'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Total Slide Number'),
      '#default_value' => $this->options['show_total_slides'],
      '#description' => $this->t('Enable this option to display the total number of slides in the slideshow. For example, "Slide 1 of 5".'),
    ];

    $form['show_slide_progress'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Animation Progress'),
      '#default_value' => $this->options['show_slide_progress'],
      '#description' => $this->t('Enable this option to display a circular animation indicator that updates with each slide change. The animation duration matches the slide transition time.'),
    ];

    $form['show_play_pause'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Play/Pause Button'),
      '#default_value' => $this->options['show_play_pause'],
      '#description' => $this->t('Enable this option to show a play/pause button at the bottom of the slideshow.'),
    ];

    $form['enable_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable CSS Library'),
      '#default_value' => $this->options['enable_css'],
      '#description' => $this->t('Check this box to include the CSS library for styling the slideshow.'),
    ];

  }

  /**
   * Generates a unique numeric ID for the view display.
   *
   * @throws \Random\RandomException
   */
  protected function generateUniqueId(): int {
    // 8 digit unique ID
    return random_int(10000000, 99999999);
  }

  /**
   * Renders the view with the 3D carousel style.
   *
   * @return array
   *   A render array for the 3D carousel.
   */
  public function render(): array {
    $rows = [];
    foreach ($this->view->result as $row) {
      $rows[] = $this->view->rowPlugin->render($row);
    }

    $libraries = [
      'vvjs/vvjs',
    ];

    if ($this->options['hero_slideshow']) {
      // Updated to attach the CSS library.
      $libraries[] = 'vvjs/vvjs-hero';
      $libraries[] = 'vvjs/vvjs__' . $this->options['available_breakpoints'];
    }

    // Conditionally include the CSS library based on the option.
    if ($this->options['enable_css']) {
      // Updated to attach the CSS library.
      $libraries[] = 'vvjs/vvjs-style';
    }

    return [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
      '#unique_id' => $this->options['unique_id'],
      '#attached' => [
        'library' => $libraries,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    // Get any errors from the parent validation.
    $errors = parent::validate();
    // Custom validation for Hero Slideshow.
    if ($this->options['hero_slideshow'] && !$this->usesFields()) {
      // Add the error directly to the errors array.
      $errors[] = $this->t('Hero Slideshow option requires Fields as row style.');
    }

    // Return the collected errors.
    return $errors;
  }

}
