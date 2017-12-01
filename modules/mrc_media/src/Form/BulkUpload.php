<?php

namespace Drupal\mrc_media\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxy;
use Drupal\dropzonejs\DropzoneJsUploadSave;
use Drupal\file\Entity\File;
use Drupal\inline_entity_form\ElementSubmit;
use Drupal\media\Entity\Media;
use Drupal\mrc_media\BundleSuggestion;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BulkUpload.
 *
 * @package Drupal\mrc_media\Form
 */
class BulkUpload extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * @var \Drupal\mrc_media\BundleSuggestion
   */
  private $bundleSuggestion;

  /**
   * @var \Drupal\dropzonejs\DropzoneJsUploadSave
   */
  private $dropzoneSave;

  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  private $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('mrc_media.bundle_suggestion'),
      $container->get('dropzonejs.upload_save'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManager $entity_manager, BundleSuggestion $bundle_suggestion, DropzoneJsUploadSave $dropzone_save, AccountProxy $current_user) {
    $this->entityTypeManager = $entity_manager;
    $this->bundleSuggestion = $bundle_suggestion;
    $this->dropzoneSave = $dropzone_save;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mrc_media_bulk_upload';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $save_step = TRUE;
    if (empty($form_state->get(['dropzonejs', 'media']))) {
      $form['upload'] = [
        '#title' => $this->t('File upload'),
        '#type' => 'dropzonejs',
        '#required' => TRUE,
        '#dropzone_description' => $this->t('Drop files here to upload them'),
        '#max_filesize' => $this->bundleSuggestion->getMaxFilesize(),
        '#extensions' => $this->bundleSuggestion->getAllExtensions(),
        '#max_files' => 0,
        '#clientside_resize' => FALSE,
      ];
      $save_step = FALSE;
    }

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 99,
      'submit' => [
        '#type' => 'submit',
        '#value' => $save_step ? $this->t('Save') : $this->t('Upload'),
        '#eb_widget_main_submit' => !$save_step,
        '#attributes' => ['class' => ['is-entity-browser-submit']],
        '#button_type' => 'primary',
      ],
    ];

    $form['#attached']['library'][] = 'dropzonejs/widget';
    // Disable the submit button until the upload sucesfully completed.
    $form['#attached']['library'][] = 'dropzonejs_eb_widget/common';
    $original_form['#attributes']['class'][] = 'dropzonejs-disable-submit';


    $this->getEntityForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $media_entities = $this->createMediaEntities($form, $form_state);

    foreach ($media_entities as &$media_entity) {
      if ($media_entity instanceof Media) {
        $source_field = $media_entity->getSource()
          ->getConfiguration()['source_field'];
        // If we don't save file at this point Media entity creates another file
        // entity with same uri for the thumbnail. That should probably be fixed
        // in Media entity, but this workaround should work for now.
        $media_entity->$source_field->entity->save();
        $media_entity->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();

    if ($trigger['#eb_widget_main_submit']) {
      $form_state->setRebuild();
      return;
    }

    $count = 0;

    $children = Element::children($form['entities']);
    foreach ($children as $child) {
      $entity_form = $form['entities'][$child];

      if (!isset($entity_form['#ief_element_submit'])) {
        continue;
      }

      foreach ($entity_form['#ief_element_submit'] as $submit_function) {
        call_user_func_array($submit_function, [&$entity_form, $form_state]);
      }

      $count++;
    }

    drupal_set_message($this->t('Saved %count Media Items', ['%count' => $count]));
  }

  /**
   * Gets uploaded files.
   *
   * We implement this to allow child classes to operate on different entity
   * type while still having access to the files in the validate callback here.
   *
   * @param array $form
   *   Form structure.
   * @param FormStateInterface $form_state
   *   Form state object.
   *
   * @return \Drupal\file\FileInterface[]
   *   Array of uploaded files.
   */
  protected function getFiles(array $form, FormStateInterface $form_state) {

    $files = $form_state->getValue(['dropzonejs', 'files']);

    if (!$files) {
      $files = [];
    }

    // We do some casting because $form_state->getValue() might return NULL.
    foreach ((array) $form_state->getValue([
      'upload',
      'uploaded_files',
    ], []) as $file) {
      if (!empty($file['path']) && file_exists($file['path'])) {

        $media_type = $this->bundleSuggestion->getBundleFromFile($file['path']);

        if ($media_type) {
          $additional_validators = [
            'file_validate_size' => [
              $this->bundleSuggestion->getMaxFileSizeBundle($media_type),
              0,
            ],
          ];

          $entity = $this->dropzoneSave->createFile(
            $file['path'],
            $this->bundleSuggestion->getMediaPath($media_type),
            $this->bundleSuggestion->getAllExtensions(),
            $this->currentUser,
            $additional_validators
          );

          $files[] = $entity;
        }
      }
    }

    $form_state->set(['dropzonejs', 'files'], $files);

    return $files;
  }

  /**
   * Add the inline entity form after the files have been uploaded.
   *
   * @param array $form
   *   Original form from getFrom().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  private function getEntityForm(array &$form, FormStateInterface $form_state) {
    if (isset($form['actions'])) {
      $form['actions']['#weight'] = 100;
    }

    $form['entities'] = [
      '#prefix' => '<div id="entities">',
      '#suffix' => '</div>',
      '#weight' => 99,
    ];

    $media_entities = $this->createMediaEntities($form, $form_state);

    if (empty($media_entities)) {
      $form['entities']['#markup'] = NULL;
      return;
    }

    foreach ($media_entities as $entity) {

      $form['entities'][] = [
        '#type' => 'inline_entity_form',
        '#entity_type' => $entity->getEntityTypeId(),
        '#bundle' => $entity->bundle(),
        '#default_value' => $entity,
        '#form_mode' => 'media_browser',
      ];
    }

    // Without this, IEF won't know where to hook into the widget. Don't pass
    // $original_form as the second argument to addCallback(), because it's not
    // just the entity browser part of the form, not the actual complete form.
    ElementSubmit::addCallback($form['actions']['submit'], $form_state->getCompleteForm());

    $form['#attached']['library'][] = 'mrc_media/mrc_media.browser';
  }


  /**
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State object.
   *
   * @return array
   *   Array of media entities before saving.
   */
  private function createMediaEntities(array $form, FormStateInterface $form_state) {

    $file_entities = [];
    $media_entities = [];

    if ($form_state->get(['dropzonejs', 'media'])) {
      return $form_state->get(['dropzonejs', 'media']);
    }

    $files = $this->getFiles($form, $form_state);
    foreach ($files as $file) {
      if ($file instanceof File) {
        $file_entities[] = $file;
        $media_type = $this->bundleSuggestion->getBundleFromFile($file->getFileUri());

        $media_entities[] = $this->entityTypeManager->getStorage('media')
          ->create([
            'bundle' => $media_type->id(),
            $media_type->getSource()
              ->getConfiguration()['source_field'] => $file,
            'uid' => $this->currentUser->id(),
            'status' => TRUE,
            'type' => $media_type->getSource()->getPluginId(),
          ]);
      }
    }
    $form_state->set(['dropzonejs', 'media'], $media_entities);
    return $media_entities;
  }

}
