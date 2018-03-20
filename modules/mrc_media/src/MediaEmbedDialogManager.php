<?php

namespace Drupal\mrc_media;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class MediaEmbedManager
 *
 * @package Drupal\mrc_media
 */
class MediaEmbedDialogManager extends DefaultPluginManager {

  /**
   * Constructs a MediaEmbedManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/MediaEmbedDialog',
      $namespaces,
      $module_handler,
      'Drupal\mrc_media\MediaEmbedDialogInterface',
      'Drupal\mrc_media\Annotation\MediaEmbedDialog'
    );
    $this->alterInfo('media_embed_dialog_info');
    $this->setCacheBackend($cache_backend, 'media_embed_dialog_info_plugins');
  }

}