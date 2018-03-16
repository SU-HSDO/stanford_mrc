<?php

namespace Drupal\mrc_media\Plugin\MediaEmbedDialog;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mrc_media\MediaEmbedDialogBase;

/**
 * Changes embedded file media items.
 *
 * @MediaEmbedDialog(
 *   id = "file",
 *   media_type = "file"
 * )
 */
class File extends MediaEmbedDialogBase {

  public function isApplicable() {
    return $this->configuration['entity']->bundle() == 'file';
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state) {
    parent::alterDialogForm($form, $form_state);

    $input = [];
    if (isset($form_state->getUserInput()['editor_object'])) {
      $editor_object = $form_state->getUserInput()['editor_object'];
      $display_settings = Json::decode($editor_object[$this->settingsKey]);
      $input = $display_settings ?: [];
    }

    /** @var \Drupal\media\Entity\Media $entity */
    $entity = $form_state->getStorage()['entity'];

    $form['attributes'][$this->settingsKey]['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Optionally enter text to use as the link text.'),
      '#default_value' => isset($input['description']) ? $input['description'] : $entity->label(),
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRender(array $element) {
    $element = parent::preRender($element);
    $source_field = $element['#media']->getSource()
      ->getConfiguration()['source_field'];
    $element[$source_field][0]['#description'] = $element['#display_settings']['description'];
    $element['#cache']['max-age'] = 0;
    return $element;
  }

}
