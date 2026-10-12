<?php

declare(strict_types=1);

namespace Drupal\Tests\vvjs\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\KernelTests\KernelTestBase;
use Drupal\views\Views;
use Drupal\vvjs\VvjsConstants;

/**
 * Kernel tests for VVJS style plugin.
 *
 * @group vvjs
 */
class VvjsKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'views',
    'vvjs',
    'system',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['node', 'views']);
    $this->installVvjsExampleView();
  }

  /**
   * Installs the optional vvjs_example view if not already present.
   */
  protected function installVvjsExampleView(): void {
    $config_storage = $this->container->get('config.storage');
    if ($config_storage->exists('views.view.vvjs_example')) {
      return;
    }
    $module_path = $this->container->get('extension.list.module')->getPath('vvjs');
    $optional_path = $module_path . '/config/optional/views.view.vvjs_example.yml';
    if (!is_file($optional_path)) {
      return;
    }
    $data = Yaml::decode(file_get_contents($optional_path));
    $this->container->get('config.factory')->getEditable('views.view.vvjs_example')->setData($data)->save();
  }

  /**
   * Tests that a view with VVJS style returns expected render structure.
   */
  public function testVvjsStylePluginRenderStructure(): void {
    $view = Views::getView('vvjs_example');
    if (!$view) {
      $this->markTestSkipped('View vvjs_example not available.');
    }

    $view->setDisplay('default');
    $view->execute();

    $build = $view->buildRenderable();

    $this->assertArrayHasKey('#theme', $build);
    $this->assertStringContainsString('views_view', $build['#theme']);
    $this->assertArrayHasKey('#attached', $build);
    $this->assertArrayHasKey('library', $build['#attached']);
    $libraries = $build['#attached']['library'];
    $this->assertContains('vvjs/vvjs', $libraries);
  }

  /**
   * Tests that style plugin defineOptions returns expected defaults.
   */
  public function testStylePluginDefineOptionsDefaults(): void {
    $view = Views::getView('vvjs_example');
    if (!$view) {
      $this->markTestSkipped('View vvjs_example not available.');
    }

    $view->setDisplay('default');
    $view->initDisplay();
    $view->display_handler->init($view, $view->current_display);
    $style_plugin = $view->display_handler->getPlugin('style');

    $this->assertInstanceOf(
      'Drupal\vvjs\Plugin\views\style\ViewsVanillaJavascriptSlideshow',
      $style_plugin
    );

    $options = $style_plugin->defineOptions();
    $this->assertSame(VvjsConstants::NAV_DOTS, $options['navigation']['default']);
    $this->assertSame(VvjsConstants::ANIMATION_BOTTOM, $options['animation']['default']);
    $this->assertSame(VvjsConstants::TRANSITION_INSTANT, $options['transition_type']['default']);
    $this->assertSame(0.3, $options['overlay_bg_opacity']['default']);
  }

}
