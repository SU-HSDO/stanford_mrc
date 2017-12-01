<?php

namespace Drupal\mrc_media;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\field\Entity\FieldConfig;
use Drupal\media\Entity\MediaType;

class BundleSuggestion {

  private $entityTypeManager;

  /**
   * MediaHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get the available extension the user can upload.
   *
   * @return string
   *   All available extensions.
   */
  public function getAllExtensions() {
    $media_types = $this->getMediaBundles();

    $extensions = [];
    /** @var \Drupal\media\Entity\MediaType $media_type */
    foreach ($media_types as $media_type) {
      $extensions[] = $this->getBundleExtensions($media_type);
    }

    return implode(' ', $extensions);
  }

  /**
   * Get all allowed file extensions that can be uploaded for a media type.
   *
   * @param \Drupal\media\Entity\MediaType $media_type
   *   Media type entity object.
   *
   * @return string
   *   All file extensions for the given media type.
   */
  public function getBundleExtensions(MediaType $media_type) {
    $source_field = $media_type->getSource()
      ->getConfiguration()['source_field'];
    if ($source_field) {
      $field = FieldConfig::loadByName('media', $media_type->id(), $source_field);
      return $field->getSetting('file_extensions') ?: '';
    }
    return '';
  }

  /**
   * Load the media type from the file uri.
   *
   * @param string $uri
   *   The file uri.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Media type bundle if one matches.
   */
  public function getBundleFromFile($uri) {
    $extension = pathinfo($uri, PATHINFO_EXTENSION);
    foreach ($this->getMediaBundles() as $media_type) {
      if (strpos($this->getBundleExtensions($media_type), $extension) !== FALSE) {
        return $media_type;
      }
    }
    return NULL;
  }

  /**
   * Get the maximum file size for all media bundles.
   *
   * @return int
   *   Maximum file size for all bundles.
   */
  public function getMaxFilesize() {
    $max_filesize = Bytes::toInt(file_upload_max_size());
    $media_types = $this->getMediaBundles();

    foreach ($media_types as $media_type) {

      if ($max = $this->getMaxFileSizeBundle($media_type)) {
        if ($max > $max_filesize) {
          $max_filesize = $max;
        }
      }
    }

    return $max_filesize;
  }

  public function getMaxFileSizeBundle(MediaType $media_type) {
    $source_field = $media_type->getSource()
      ->getConfiguration()['source_field'];

    if ($source_field) {
      $field = FieldConfig::loadByName('media', $media_type->id(), $source_field);
      return Bytes::toInt($field->getSetting('max_filesize'));
    }
    return 0;
  }

  public function getMediaPath(MediaType $media_type) {
    return 'public://';
  }

  /**
   * Get all or some media bundles.
   *
   * @param array $bundles
   *   Optionally specifiy which media bundles to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Keyed array of all media types.
   */
  private function getMediaBundles($bundles = []) {
    return $this->entityTypeManager->getStorage('media_type')
      ->loadMultiple($bundles ?: NULL);
  }

}
