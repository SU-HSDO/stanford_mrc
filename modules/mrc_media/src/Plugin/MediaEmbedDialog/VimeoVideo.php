<?php

namespace Drupal\mrc_media\Plugin\MediaEmbedDialog;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\mrc_media\MediaEmbedDialogBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\video_embed_field\ProviderManager;

/**
 * Changes embedded video media items.
 *
 * @MediaEmbedDialog(
 *   id = "vimeo_video",
 *   media_type = "video"
 * )
 */
class VimeoVideo extends MediaEmbedDialogBase {

  /**
   * @var \Drupal\video_embed_field\ProviderManager
   */
  protected $videoManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('video_embed_field.provider_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager, ProviderManager $video_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager);
    $this->videoManager = $video_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    $entity = $this->configuration['entity'];
    if ($entity->bundle() == 'video') {
      $source_field = $entity->getSource()
        ->getConfiguration()['source_field'];
      $url = $entity->get($source_field)->getValue()[0]['value'];
      $provider = $this->videoManager->loadProviderFromInput($url);
      if ($provider->getPluginId() == 'vimeo') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state) {
    parent::alterDialogForm($form, $form_state);

    $form['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
    ];
  }

}
