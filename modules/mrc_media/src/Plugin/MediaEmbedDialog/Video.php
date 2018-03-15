<?php

namespace Drupal\mrc_media\Plugin\MediaEmbedDialog;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\mrc_media\MediaEmbedDialogBase;
use Drupal\mrc_media\VideoOptionInterface;
use Drupal\mrc_media\VideoOptionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\video_embed_field\ProviderManager;

/**
 * Changes embedded video media items.
 *
 * @MediaEmbedDialog(
 *   id = "video",
 *   media_type = "video"
 * )
 */
class Video extends MediaEmbedDialogBase {

  /**
   * @var \Drupal\video_embed_field\ProviderManager
   */
  protected $videoManager;

  protected $videoOptions;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('video_embed_field.provider_manager'),
      $container->get('plugin.manager.video_option_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager, ProviderManager $video_manager, VideoOptionManager $video_option) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager);
    $this->videoManager = $video_manager;
    $this->videoOptions = $video_option;
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state) {
    parent::alterDialogForm($form, $form_state);

    $entity = $form_state->getStorage()['entity'];
    $url = $this->getVideoFromEntity($entity);
    /** @var \Drupal\video_embed_field\ProviderPluginBase $provider */
    $provider = $this->videoManager->loadProviderFromInput($url);

    foreach ($this->videoOptions->getDefinitions() as $plugin_id => $configuration) {
      dpm($configuration);
      dpm($provider->getBaseId());
      if ($configuration['provider'] == $provider->getPluginId()) {
        $option_plugin = $this->videoOptions->createInstance($plugin_id);

        $form['player_options'] = $option_plugin->getOptionForm($form, $form_state);
      }
    }
  }

  protected function getVideoFromEntity(MediaInterface $entity) {
    $source_field = $entity->getSource()
      ->getConfiguration()['source_field'];
    return $entity->get($source_field)->getValue()[0]['value'];
  }

}
