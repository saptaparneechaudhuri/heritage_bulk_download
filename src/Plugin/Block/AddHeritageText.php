<?php

namespace Drupal\heritage_bulk_download\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a block to Display Audio Play Options.
 *
 * @Block(
 *   id = "heritage_add_text",
 *   admin_label = @Translation("Add Heritage Texts"),
 *   category = @Translation("Custom")
 * )
 */


/**
 * This block is only shown to the managers. It can be configured from roles in the
 * block configuration.
 */
class AddHeritageText extends BlockBase {

  /**
   *
   */
  public function build() {
    $node_type = 'heritage_text';

    $build['link'] = [
      '#title' => 'Add Heritage Text',
      '#type' => 'link',
      '#url' => Url::fromRoute('node.add', ['node_type' => $node_type]),
    ];

    $build['#cache']['max-age'] = 0;
    return $build;
  }

}
