<?php

namespace Drupal\mrc_media;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\field\Entity\FieldConfig;
use Drupal\media\Entity\MediaType;

class BundleSuggestion {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
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
   * Get all media type bundles that are configured to have an upload field.
   *
   * @return \Drupal\media\Entity\MediaType[]
   *   Keyed array of media bundles with upload fields.
   */
  public function getUploadBundles() {
    $upload_bundles = [];
    $media_types = $this->getMediaBundles();

    /** @var \Drupal\media\Entity\MediaType $media_type */
    foreach ($media_types as $media_type) {
      $source_field = $media_type->getSource()
        ->getConfiguration()['source_field'];
      $field = FieldConfig::loadByName('media', $media_type->id(), $source_field);
      if (!empty($field->getSetting('file_extensions'))) {
        $upload_bundles[$media_type->id()] = $media_type;
      }
    }

    return $upload_bundles;
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
   * @return MediaType|null
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

  /**
   * Get maximum file size for the media type.
   *
   * @param \Drupal\media\Entity\MediaType $media_type
   *   The media type bundle to get file size for.
   *
   * @return int
   *   The maximum file size.
   */
  public function getMaxFileSizeBundle(MediaType $media_type) {
    $source_field = $media_type->getSource()
      ->getConfiguration()['source_field'];

    if ($source_field) {
      $field = FieldConfig::loadByName('media', $media_type->id(), $source_field);
      return Bytes::toInt($field->getSetting('max_filesize'));
    }
    return 0;
  }

  public function getUploadPath(MediaType $media_type) {
    $source_field = $media_type->getSource()
      ->getConfiguration()['source_field'];
    $path = 'public://';
    if ($source_field) {
      $field = FieldConfig::loadByName('media', $media_type->id(), $source_field);
      $path = 'public://' . $field->getSetting('file_directory');
    }

    if (strrpos($path, '/') !== strlen($path)) {
      $path .= '/';
    }
    return $path;
  }

  /**
   * Get all or some media bundles.
   *
   * @param array $bundles
   *   Optionally specifiy which media bundles to load.
   *
   * @return MediaType[]
   *   Keyed array of all media types.
   */
  private function getMediaBundles($bundles = []) {
    return $this->entityTypeManager->getStorage('media_type')
      ->loadMultiple($bundles ?: NULL);
  }

}
