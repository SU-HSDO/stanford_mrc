<?php

namespace Drupal\mrc_media\Plugin\EntityBrowser\Widget;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\dropzonejs\DropzoneJsUploadSave;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\inline_entity_form\ElementSubmit;
use Drupal\media\Entity\Media;
use Drupal\mrc_media\BundleSuggestion;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * An Entity Browser widget for creating media entities from embed codes.
 *
 * @EntityBrowserWidget(
 *   id = "dropzonejs_media",
 *   label = @Translation("Media Entity DropzoneJS with edit (all bundles)"),
 *   description = @Translation("Adds DropzoneJS upload integration that saves
 *   Media entities and allows to edit them.")
 * )
 */
class DropzoneUpload extends MediaBrowserBase {

  /**
   * Dropzone upload save service.
   *
   * @var \Drupal\dropzonejs\DropzoneJsUploadSave
   */
  private $dropzoneJsSave;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('mrc_media.bundle_suggestion'),
      $container->get('dropzonejs.upload_save'),
      $container->get('current_user')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, BundleSuggestion $bundle_suggestion, DropzoneJsUploadSave $dropzone_save, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager,$bundle_suggestion);
    $this->dropzoneJsSave = $dropzone_save;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = [
      'upload_location' => 'public://media',
      'dropzone_description' => $this->t('Drop files here to upload them'),
    ];
    return $config + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $configuration = $this->configuration;

    $form['upload_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Upload location'),
      '#default_value' => $configuration['upload_location'],
    ];

    $form['dropzone_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dropzone drag-n-drop zone text'),
      '#default_value' => $configuration['dropzone_description'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareEntities(array $form, FormStateInterface $form_state) {
    $file_entities = [];
    $media_entities = [];

    if ($form_state->get(['dropzonejs', $this->uuid(), 'media'])) {
      return $form_state->get(['dropzonejs', $this->uuid(), 'media']);
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
    $form_state->set(['dropzonejs', $this->uuid(), 'media'], $media_entities);
    return $media_entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);
    $storage = $form_state->getStorage();

    $form['upload'] = [
      '#title' => $this->t('File upload'),
      '#type' => 'dropzonejs',
      '#required' => TRUE,
      '#dropzone_description' => $this->configuration['dropzone_description'],
      '#max_filesize' => $this->bundleSuggestion->getMaxFilesize(),
      '#extensions' => $this->bundleSuggestion->getAllExtensions(),
      '#max_files' => !empty($storage['entity_browser']['validators']['cardinality']['cardinality']) ? $storage['entity_browser']['validators']['cardinality']['cardinality'] : 1,
      '#clientside_resize' => FALSE,
    ];

    $form['#attached']['library'][] = 'dropzonejs/widget';
    // Disable the submit button until the upload sucesfully completed.
    $form['#attached']['library'][] = 'dropzonejs_eb_widget/common';
    $original_form['#attributes']['class'][] = 'dropzonejs-disable-submit';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);

    // Any errors, don't save the entities yet.
    if ($form_state::hasAnyErrors()) {
      return;
    }
    $media_entities = $this->prepareEntities($form, $form_state);

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
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    parent::submit($element, $form, $form_state);

    $children = Element::children($element['entities']);
    foreach ($children as $child) {
      $entity_form = $element['entities'][$child];

      if (!isset($entity_form['#ief_element_submit'])) {
        continue;
      }

      foreach ($entity_form['#ief_element_submit'] as $submit_function) {
        call_user_func_array($submit_function, [&$entity_form, $form_state]);
      }
    }

    $media_entities = $this->prepareEntities($form, $form_state);

    $this->selectEntities($media_entities, $form_state);
    $form_state->set(['dropzonejs', $this->uuid(), 'files'], []);
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
    $files = $form_state->get(['dropzonejs', $this->uuid(), 'files']) ?: [];

    // We do some casting because $form_state->getValue() might return NULL.
    foreach ((array) $form_state->getValue([
      'upload',
      'uploaded_files',
    ], []) as $file) {
      if (!empty($file['path']) && file_exists($file['path'])) {
        $bundle = $this->bundleSuggestion->getBundleFromFile($file['path']);
        $additional_validators = [
          'file_validate_size' => [
            $this->bundleSuggestion->getMaxFileSizeBundle($bundle),
            0,
          ],
        ];

        $entity = $this->dropzoneJsSave->createFile(
          $file['path'],
          $this->configuration['upload_location'],
          $this->bundleSuggestion->getAllExtensions(),
          $this->currentUser,
          $additional_validators
        );
        $files[] = $entity;
      }
    }

    if (!empty($form['widget']['upload']['#max_files']) && $form['widget']['upload']['#max_files']) {
      $files = array_slice($files, -$form['widget']['upload']['#max_files']);
    }

    $form_state->set(['dropzonejs', $this->uuid(), 'files'], $files);

    return $files;
  }

}
