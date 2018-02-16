<?php

namespace Drupal\mrc_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;

/**
 * An Entity Browser widget for creating media entities from embed codes.
 *
 * @EntityBrowserWidget(
 *   id = "embed_code",
 *   label = @Translation("Embed Code"),
 *   description = @Translation("Create media entities from embed codes."),
 * )
 */
class EmbedCode extends MediaBrowserBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    if ($form_state->get(['embed_code', $this->uuid(), 'media'])) {
      return $form_state->get(['embed_code', $this->uuid(), 'media']);
    }

    $media_entities = [];
    $value = $form_state->getValue('input');

    $media_type = $this->bundleSuggestion->getBundleFromInput($value);
    if (!$value || !$media_type) {
      return [];
    }

    // Create the media item.
    $entity = $this->prepareMediaEntity($media_type, $value);
    if ($entity) {
      $entity->save();
      $media_entities[] = $entity;
    }

    $form_state->set(['embed_code', $this->uuid(), 'media'], $media_entities);
    return $media_entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    $form['input'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Video Url'),
      '#required' => TRUE,
      '#placeholder' => $this->t('Enter a URL...'),
    ];

    if ($form_state->get(['embed_code', $this->uuid(), 'media'])) {
      $form['input']['#type'] = 'hidden';
    }

    $form['#attached']['library'][] = 'mrc_media/mrc_media.browser';
    $form['#attached']['library'][] = 'mrc_media/mrc_media.embed';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);
    $value = trim($form_state->getValue('input'));
    $bundle = $this->bundleSuggestion->getBundleFromInput($value);
    if (!$bundle) {
      $form_state->setError($form['widget']['input'], $this->t('You must enter a URL or embed code.'));
    }
  }

}
