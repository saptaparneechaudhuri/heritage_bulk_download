<?php

namespace Drupal\heritage_bulk_download\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a block to Display Audio Play Options.
 *
 * @Block(
 *   id = "workbench_access",
 *   admin_label = @Translation("WorkBench Access for Managers"),
 *   category = @Translation("Custom")
 * )
 */
class DisplayWorkBench extends BlockBase {

  /**
   *
   */
  public function build() {

    $build['link'] = [
      '#title' => 'Access WorkBench',
      '#type' => 'link',
      '#url' => Url::fromRoute('workbench.content'),
    ];

    $build['#cache']['max-age'] = 0;
    return $build;
  }

}
