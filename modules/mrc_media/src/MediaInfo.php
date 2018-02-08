<?php

namespace Drupal\mrc_media;

use Drupal\Core\Database\Database;

/**
 * @file
 * Contains \Drupal\mrc_media\MediaInfo.
 */
class MediaInfo {

  /**
   * Checks if a video already exists in the media browser.
   *
   * @param string $uri
   *
   * @return string|null
   */
  public function videoExists($uri) {

    $select = Database::getConnection()
      ->select('media__field_media_video_embed_field', 've');
    $select->fields('ve', ['field_media_video_embed_field_value']);
    $select->condition('field_media_video_embed_field_value', $uri, '=');
    $results = $select->execute();
    return $results->fetchCol();
  }

  /**
   * Returns entity_id for a given uri.
   *
   * @param string $uri
   *
   * @return string|null
   */
  public function getVideoTargetId($uri) {
    $select = Database::getConnection()
      ->select('media__field_media_video_embed_field', 've');
    $select->fields('ve', ['entity_id']);
    $select->condition('field_media_video_embed_field_value', $uri, '=');
    $results = $select->execute();
    return $results->fetchCol();
  }

}
