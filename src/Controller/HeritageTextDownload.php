<?php

namespace Drupal\heritage_bulk_download\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 *
 */
class HeritageTextDownload extends ControllerBase {

  /**
   *
   */
  public function getTitle($textid = NULL) {

    // Load the text name.
    $title = db_query("SELECT title FROM `node_field_data` WHERE nid = :textid AND type = :type", [':textid' => $textid, ':type' => 'heritage_text'])->fetchField();

    $build = [];
    $build['#markup'] = 'Download Content For ' . $title;
    return $build;

  }

}
