<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Extension;

use Drupal\KernelTests\KernelTestBase;

// cspell:ignore nyan

/**
 * @coversDefaultClass \Drupal\Core\Extension\ThemeEngineExtensionList
 * @group Extension
 */
class ThemeEngineExtensionListTest extends KernelTestBase {

  /**
   * @covers ::getList
   */
  public function testGetList(): void {
    // Confirm that all theme engines are available.
    $theme_engines = \Drupal::service('extension.list.theme_engine')->getList();
    $this->assertArrayHasKey('twig', $theme_engines);
    $this->assertArrayHasKey('nyan_cat', $theme_engines);
    $this->assertCount(2, $theme_engines);
  }

}
