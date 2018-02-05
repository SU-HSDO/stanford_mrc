<?php

namespace Drupal\mrc_media;

use Drupal\Core\Database\Database;

/**
* @file
* Contains \Drupal\mrc_media\MediaInfo.
*/

class MediaInfo {

  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * Checks if a video already exists in the media browser.
   *
   * @param $uri
   *
   */
  public function videoExists($uri) {

    $select = Database::getConnection()->select('media__field_media_video_embed_field', 've');
    $select->fields('ve', array('field_media_video_embed_field_value'));
    $select->condition('field_media_video_embed_field_value', $uri, '=');
    $results = $select->execute();
    return $results->fetchCol();
  }

}