<?php

namespace Drupal\mrc_media\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an entity browser display annotation object.
 *
 * @see hook_entity_browser_display_info_alter()
 *
 * @Annotation
 */
class VideoOption extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The machine name of the video provider.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $provider;

}
