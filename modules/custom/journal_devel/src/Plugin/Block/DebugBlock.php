<?php

namespace Drupal\journal_devel\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'DebugBlock' block.
 *
 * @Block(
 *  id = "journal_debug_block",
 *  admin_label = @Translation("Debug block"),
 * )
 */
class DebugBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $route_name = \Drupal::routeMatch()->getRouteName();
    $route_object = \Drupal::routeMatch()->getRouteObject();
    ksm($route_object);
    $build['#cache']['max-age'] = 0;
    $build['journal_debug_block']['#markup'] = 'Implement DebugBlock.<p>' . var_dump($route_name);

    return $build;
  }

}
