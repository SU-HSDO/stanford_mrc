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
    if (isset($form['widget']['entity']['#entity'])) {
      return [
        $form['widget']['entity']['#entity'],
      ];
    }
    else {
      return [];
    }
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
    $form['preview'] = [
      '#prefix' => '<div id="video-preview">',
      '#suffix' => '</div>',
    ];
    $form['actions'] = ['#type' => 'actions'];


    $form['actions']['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#name' => 'next',
    ];

    $form['#attached']['library'][] = 'mrc_media/mrc_media.browser';
    $form['#attached']['library'][] = 'mrc_media/mrc_media.embed';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    $value = trim($form_state->getValue('input'));
    $bundle = $this->bundleSuggestion->getBundleFromInput($value);
    if (!$bundle) {
      $form_state->setError($form['widget']['input'], $this->t('You must enter a URL or embed code.'));
    }
  }

  /**
   * Creates a media entity from an input value.
   *
   * @param mixed $value
   *   The input value.
   * @param string[] $bundles
   *   (optional) A set of media bundle IDs which might match the input value.
   *   If omitted, all bundles to which the user has create access are checked.
   *
   * @return \Drupal\media\MediaInterface
   *   The unsaved media entity.
   * @throws
   */
  public function createFromInput($value, array $bundles = []) {
    /** @var \Drupal\media\Entity\MediaType $bundle */
    $bundle = $this->bundleSuggestion->getBundleFromInput($value);
    if (!$bundle) {
      return NULL;
    }
    /** @var \Drupal\media\MediaInterface $entity */
    $entity = $this->entityTypeManager
      ->getStorage('media')
      ->create([
        'bundle' => $bundle->id(),
      ]);

    $field_name = $bundle->getSource()
      ->getConfiguration()['source_field'];
    $field = $entity->get($field_name);
    if ($field) {
      $field->setValue($value);
    }
    return $entity;
  }

}
