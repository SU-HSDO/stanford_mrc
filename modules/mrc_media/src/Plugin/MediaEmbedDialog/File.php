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

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    return $this->configuration['entity']->bundle() == 'file';
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultInput() {
    return [
      'description' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state) {
    parent::alterDialogForm($form, $form_state);
    $input = $this->getUserInput($form_state);

    /** @var \Drupal\media\Entity\Media $entity */
    $entity = $form_state->getStorage()['entity'];

    $form['attributes'][$this->settingsKey]['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Optionally enter text to use as the link text.'),
      '#default_value' => $input['description'] ?: $entity->label(),
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRender(array $element) {
    $source_field = static::getMediaSourceField($element['#media']);
    $element[$source_field][0]['#description'] = $element['#display_settings']['description'];
    $element['#cache']['max-age'] = 0;
    return $element;
  }

}
