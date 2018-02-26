<?php

namespace Drupal\mrc_media;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;

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

  /**
   * Create a new media entity when a file_managed file is uploaded.
   *
   * @param array $element
   *   Managed file form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted form state.
   */
  public function mrc_media_save_file_managed_media(array $element, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    $button_key = array_pop($parents);

    if ($button_key == 'remove_button' || $form_state::hasAnyErrors()) {
      return;
    }

    if (!empty($element['#files'])) {
      foreach ($element['#files'] as $file) {
        if ($file instanceof File) {
          $media_bundle = 'file';

          // Switch the media bundle to image if there is a width attribute.
          // Can't find a better solution than that.
          if (isset($element['width'])) {
            $media_bundle = 'image';
          }

          // Load the media type entity to get the source field.
          $entity_type_manager = \Drupal::entityTypeManager();
          $media_type = $entity_type_manager->getStorage('media_type')
            ->load($media_bundle);
          $source_field = $media_type->getSource()
            ->getConfiguration()['source_field'];

          // Check if a media entity has already been created.
          $query = \Drupal::entityQuery('media')
            ->condition($source_field, $file->id());

          // Media entity already created.
          if (!empty($query->execute())) {
            continue;
          }

          // Create the new media entity.
          $media_entity = $entity_type_manager->getStorage('media')
            ->create([
              'bundle' => $media_type->id(),
              $source_field => $file,
              'uid' => \Drupal::currentUser()->id(),
              'status' => TRUE,
              'type' => $media_type->getSource()->getPluginId(),
            ]);

          $source_field = $media_entity->getSource()
            ->getConfiguration()['source_field'];
          // If we don't save file at this point Media entity creates another file
          // entity with same uri for the thumbnail. That should probably be fixed
          // in Media entity, but this workaround should work for now.
          $media_entity->$source_field->entity->save();
          $media_entity->save();
        }
      }
    }
  }


}
