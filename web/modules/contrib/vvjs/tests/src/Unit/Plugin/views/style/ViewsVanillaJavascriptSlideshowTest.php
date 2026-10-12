<?php

declare(strict_types=1);

namespace Drupal\Tests\vvjs\Unit\Plugin\views\style;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Transliteration\PhpTransliteration;
use Drupal\Tests\UnitTestCase;
use Drupal\vvjs\Plugin\views\style\ViewsVanillaJavascriptSlideshow;
use Drupal\vvjs\VvjsConstants;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\vvjs\Plugin\views\style\ViewsVanillaJavascriptSlideshow
 *
 * @group vvjs
 */
class ViewsVanillaJavascriptSlideshowTest extends UnitTestCase {

  /**
   * The style plugin under test.
   *
   * @var \Drupal\vvjs\Plugin\views\style\ViewsVanillaJavascriptSlideshow
   */
  protected ViewsVanillaJavascriptSlideshow $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $transliteration = $this->createMock(PhpTransliteration::class);
    $transliteration->method('transliterate')->willReturnArgument(0);

    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);

    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')
      ->willReturnMap([
        ['transliteration', $transliteration],
        ['entity_field.manager', $entity_field_manager],
      ]);

    $configuration = [
      'view' => NULL,
      'display' => NULL,
    ];
    $plugin_id = 'views_vvjs';
    $plugin_definition = [
      'id' => 'views_vvjs',
      'title' => 'Views Vanilla JavaScript Slideshow',
      'theme' => 'views_view_vvjs',
      'display_types' => ['normal'],
    ];

    $this->plugin = ViewsVanillaJavascriptSlideshow::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * @covers ::defineOptions
   */
  public function testDefineOptionsReturnsExpectedKeys(): void {
    $options = $this->plugin->defineOptions();

    $this->assertArrayHasKey('time_in_seconds', $options);
    $this->assertArrayHasKey('navigation', $options);
    $this->assertArrayHasKey('animation', $options);
    $this->assertArrayHasKey('arrows', $options);
    $this->assertArrayHasKey('transition_type', $options);
    $this->assertArrayHasKey('transition_duration', $options);
    $this->assertArrayHasKey('hero_slideshow', $options);
    $this->assertArrayHasKey('overlay_bg_color', $options);
    $this->assertArrayHasKey('overlay_bg_opacity', $options);
    $this->assertArrayHasKey('enable_deeplink', $options);
    $this->assertArrayHasKey('enable_css', $options);
  }

  /**
   * @covers ::defineOptions
   */
  public function testDefineOptionsReturnsExpectedDefaults(): void {
    $options = $this->plugin->defineOptions();

    $this->assertSame(VvjsConstants::NAV_DOTS, $options['navigation']['default']);
    $this->assertSame(VvjsConstants::ANIMATION_BOTTOM, $options['animation']['default']);
    $this->assertSame(VvjsConstants::ARROWS_TOP, $options['arrows']['default']);
    $this->assertSame(VvjsConstants::BREAKPOINT_576, $options['available_breakpoints']['default']);
    $this->assertSame(VvjsConstants::OVERLAY_MIDDLE, $options['overlay_position']['default']);
    $this->assertSame(VvjsConstants::TRANSITION_INSTANT, $options['transition_type']['default']);
    $this->assertSame(0.3, $options['overlay_bg_opacity']['default']);
    $this->assertFalse($options['hero_slideshow']['default']);
    $this->assertTrue($options['enable_css']['default']);
  }

  /**
   * @covers ::flattenFormValues
   */
  public function testFlattenFormValuesMapsNestedToFlat(): void {
    $nested = [
      'hero_slideshow_section' => [
        'hero_slideshow' => TRUE,
        'layout' => [
          'max_width' => 1400,
          'min_height' => 50,
          'max_content_width' => 70,
        ],
        'overlay' => [
          'overlay_position' => VvjsConstants::OVERLAY_LEFT,
          'overlay_bg_color' => '#333333',
          'overlay_bg_opacity' => '0.5',
        ],
      ],
      'responsive_section' => [
        'available_breakpoints' => VvjsConstants::BREAKPOINT_768,
      ],
      'timing_section' => [
        'time_in_seconds' => '5000',
      ],
      'navigation_section' => [
        'arrows' => VvjsConstants::ARROWS_SIDES,
        'navigation' => VvjsConstants::NAV_NUMBERS,
        'scrollable_dots_width' => '300',
      ],
      'animation_section' => [
        'animation' => VvjsConstants::ANIMATION_NONE,
        'transition_type' => VvjsConstants::TRANSITION_CROSSFADE_CLASSIC,
        'transition_duration' => '800',
      ],
      'display_section' => [
        'show_total_slides' => TRUE,
        'show_slide_progress' => TRUE,
        'show_play_pause' => FALSE,
      ],
      'behavior_section' => [
        'pause_on_hover' => FALSE,
        'enable_swipe' => TRUE,
        'enable_keyboard' => TRUE,
        'enable_looping' => TRUE,
        'start_index' => 2,
      ],
      'advanced_section' => [
        'enable_css' => FALSE,
      ],
      'deeplink_section' => [
        'enable_deeplink' => TRUE,
        'deeplink_identifier' => 'gallery',
      ],
    ];

    $reflection = new \ReflectionMethod($this->plugin, 'flattenFormValues');
    $flattened = $reflection->invoke($this->plugin, $nested);

    $this->assertTrue($flattened['hero_slideshow']);
    $this->assertSame(1400, $flattened['max_width']);
    $this->assertSame(50, $flattened['min_height']);
    $this->assertSame(70, $flattened['max_content_width']);
    $this->assertSame(VvjsConstants::OVERLAY_LEFT, $flattened['overlay_position']);
    $this->assertSame('#333333', $flattened['overlay_bg_color']);
    $this->assertSame(0.5, $flattened['overlay_bg_opacity']);
    $this->assertSame(VvjsConstants::BREAKPOINT_768, $flattened['available_breakpoints']);
    $this->assertSame(5000, $flattened['time_in_seconds']);
    $this->assertSame(VvjsConstants::ARROWS_SIDES, $flattened['arrows']);
    $this->assertSame(VvjsConstants::NAV_NUMBERS, $flattened['navigation']);
    $this->assertSame(300, $flattened['scrollable_dots_width']);
    $this->assertSame(VvjsConstants::ANIMATION_NONE, $flattened['animation']);
    $this->assertSame(VvjsConstants::TRANSITION_CROSSFADE_CLASSIC, $flattened['transition_type']);
    $this->assertSame(800, $flattened['transition_duration']);
    $this->assertTrue($flattened['show_total_slides']);
    $this->assertTrue($flattened['show_slide_progress']);
    $this->assertFalse($flattened['show_play_pause']);
    $this->assertFalse($flattened['pause_on_hover']);
    $this->assertSame(2, $flattened['start_index']);
    $this->assertFalse($flattened['enable_css']);
    $this->assertTrue($flattened['enable_deeplink']);
    $this->assertSame('gallery', $flattened['deeplink_identifier']);
  }

}
