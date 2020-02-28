<?php

namespace Drupal\heritage_bulk_download\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a block to Display Audio Play Options.
 *
 * @Block(
 *   id = "manage_all_texts_display_block",
 *   admin_label = @Translation(" Display Manage All Texts "),
 *   category = @Translation("Custom")
 * )
 */
class DisplayManageAllTexts extends BlockBase {

  /**
   *
   */
  public function build() {

    $build['link'] = [
      '#title' => 'Manage All Texts',
      '#type' => 'link',
      '#url' => Url::fromRoute('view.manage_all_texts.page_1'),
    ];

    $build['#cache']['max-age'] = 0;
    return $build;

  }

}
