<?php

namespace Drupal\mrc_media\Plugin\MediaEmbedDialog;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mrc_media\MediaEmbedDialogBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\video_embed_field\ProviderManager;

/**
 * Changes embedded video media items.
 *
 * @MediaEmbedDialog(
 *   id = "youtube_video",
 *   media_type = "video"
 * )
 */
class YoutubeVideo extends MediaEmbedDialogBase {

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
      $url = $entity->get(static::getMediaSourceField($entity))
        ->getValue()[0]['value'];
      $provider = $this->videoManager->loadProviderFromInput($url);
      if ($provider->getPluginId() == 'youtube') {
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
    $form['attributes'][$this->settingsKey]['display_options'] = [
      '#type' => 'select',
      '#title' => $this->t('Display Options'),
      '#multiple' => TRUE,
      '#options' => [
        'autoplay' => $this->t('Autoplay'),
        'cc_load_policy' => $this->t('Load Captions'),
        'controls' => $this->t('Hide Controls'),
        'fs' => $this->t('Disable Full Screen'),
        'loop' => $this->t('Loop Video'),
        'modestbranding' => $this->t('Small Logo'),
        'rel' => $this->t('Show related videos at the end'),
        'showinfo' => $this->t('Hide video title'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRender(array $element) {
    $opposite_value = ['controls', 'fs', 'rel', 'controls'];
    if (!empty($element['#display_settings']['display_options'])) {
      foreach ($element['#display_settings']['display_options'] as $option) {
        $field = static::getMediaSourceField($element['#media']);
        $element[$field][0]['children']['#query'][$option] = 1;

        if (in_array($option, $opposite_value)) {
          $element[$field][0]['children']['#query'][$option] = 0;
        }
      }
    }
    return $element;
  }

}
