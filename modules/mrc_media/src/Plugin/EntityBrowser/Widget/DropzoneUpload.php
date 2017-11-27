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
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

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
class DropzoneUpload extends WidgetBase {

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, DropzoneJsUploadSave $dropzone_save, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->dropzoneJsSave = $dropzone_save;
    $this->currentUser = $current_user;
  }

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
      $container->get('dropzonejs.upload_save'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $max_filesize = file_upload_max_size() / pow(Bytes::KILOBYTE, 2);
    $config = [
      'upload_location' => 'public://media',
      'dropzone_description' => $this->t('Drop files here to upload them'),
      'max_filesize' => $max_filesize,
      'extensions' => [
        'image' => 'jpg jpeg gif png',
        'file' => 'txt doc docx xls xlsx pdf ppt pps odt ods odp',
      ],
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

    preg_match('%\d+%', $configuration['max_filesize'], $matches);
    $max_filesize = !empty($matches) ? array_shift($matches) : '1';

    $form['max_filesize'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum size of files.'),
      '#min' => '0',
      '#field_suffix' => $this->t('MB'),
      '#default_value' => $max_filesize,
    ];

    $media_types = $this->entityTypeManager->getStorage('media_type')
      ->loadMultiple();

    $form['extensions'] = ['#type' => 'container'];

    /** @var \Drupal\media\Entity\MediaType $media_type */
    foreach ($media_types as $media_type) {
      $form['extensions'][$media_type->id()] = [
        '#type' => 'textfield',
        '#title' => $this->t('File extensions for %label', ['%label' => $media_type->label()]),
        '#description' => $this->t('A space separated list of file extensions'),
        '#default_value' => isset($configuration['extensions'][$media_type->id()]) ? $configuration['extensions'][$media_type->id()] : '',
      ];
    }

    return $form;
  }

  /**
   * Get the configured upload location.
   *
   * @return string
   *   Upload URI location.
   */
  public function getUploadLocation() {
    return $this->configuration['upload_location'];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareEntities(array $form, FormStateInterface $form_state) {
    $file_entities = [];
    $media_entities = [];

    $storage = $form_state->getStorage();
    $return_file = FALSE;
    if (!empty($storage['entity_browser']['validators']['entity_type']['type']) && $storage['entity_browser']['validators']['entity_type']['type'] == 'file') {
      $return_file = TRUE;
    }

    foreach ($this->getFiles($form, $form_state) as $file) {
      if ($file instanceof File) {
        $file_entities[] = $file;
        $media_type = $this->getExtensionBundle(pathinfo($file->getFileUri(), PATHINFO_EXTENSION));

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

    if ($return_file) {
      return $file_entities;
    }

    return $media_entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);
    $storage = $form_state->getStorage();
    $widget = $storage['entity_browser']['widget_context'];

    $form['upload'] = [
      '#title' => $this->t('File upload'),
      '#type' => 'dropzonejs',
      '#required' => TRUE,
      '#dropzone_description' => $this->configuration['dropzone_description'],
      '#max_filesize' => '100MB',
      '#extensions' => !empty($widget['upload_validators']['file_validate_extensions'][0]) ? $widget['upload_validators']['file_validate_extensions'][0] : $this->getBundleExtensions(),
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
   * Get specific bundle extensions or all.
   *
   * @param string|null $bundle_id
   *   Media type id or null to get all.
   *
   * @return string
   *   List of all extensions for the bundle or all bundles.
   */
  private function getBundleExtensions($bundle_id = NULL) {
    if ($bundle_id) {
      if (isset($this->configuration['extensions'][$bundle_id])) {
        return $this->configuration['extensions'][$bundle_id];
      }
      return '';
    }
    $extensions = [];
    foreach ($this->configuration['extensions'] as $bundle_extensions) {
      $extensions[] = $bundle_extensions;
    }
    return implode(' ', $extensions);
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $media_entities = $this->prepareEntities($form, $form_state);;

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

    $this->selectEntities($media_entities, $form_state);
    $this->clearFormValues($element, $form_state);
  }

  /**
   * Clear values from upload form element.
   *
   * @param array $element
   *   Upload form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  protected function clearFormValues(array &$element, FormStateInterface $form_state) {
    // We propagated entities to the other parts of the system. We can now
    // remove them from our values.
    $form_state->setValueForElement($element['upload']['uploaded_files'], '');
    NestedArray::setValue($form_state->getUserInput(), $element['upload']['uploaded_files']['#parents'], '');
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
    $config = $this->configuration;
    $storage = $form_state->getStorage();
    $widget = $storage['entity_browser']['widget_context'];

    $max_filesize = !empty($widget['upload_validators']['file_validate_size'][0]) ? $widget['upload_validators']['file_validate_size'][0] : $config['max_filesize'] . 'MB';

    $additional_validators = [
      'file_validate_size' => [
        Bytes::toInt($max_filesize),
        0,
      ],
    ];

    $files = $form_state->get(['dropzonejs', $this->uuid(), 'files']);

    if (!$files) {
      $files = [];
    }

    // We do some casting because $form_state->getValue() might return NULL.
    foreach ((array) $form_state->getValue([
      'upload',
      'uploaded_files',
    ], []) as $file) {
      if (file_exists($file['path'])) {
        $entity = $this->dropzoneJsSave->createFile(
          $file['path'],
          $this->getUploadLocation(),
          $this->getBundleExtensions(),
          $this->currentUser,
          $additional_validators
        );
        $files[] = $entity;
      }
    }

    if ($form['widget']['upload']['#max_files']) {
      $files = array_slice($files, -$form['widget']['upload']['#max_files']);
    }

    $form_state->set(['dropzonejs', $this->uuid(), 'files'], $files);

    return $files;
  }

  /**
   * Get the media bundle for the uploaded extension.
   *
   * @param string $extension
   *   File extension.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Media bundle type.
   */
  public function getExtensionBundle($extension) {
    foreach ($this->configuration['extensions'] as $bundle => $extensions) {
      $extensions = explode(' ', $extensions);
      if (in_array($extension, $extensions)) {
        return $this->entityTypeManager
          ->getStorage('media_type')
          ->load($bundle);
      }
    }
    return NULL;
  }

  public static function saveFileManaged(&$element, FormStateInterface &$form_state, $request) {
    // Determine whether it was the upload or the remove button that was clicked,
    // and set $element to the managed_file element that contains that button.
//    $parents = $form_state->getTriggeringElement()['#array_parents'];
//    $button_key = array_pop($parents);
//
//    if ($button_key == 'remove_button' || $form_state::hasAnyErrors()) {
//      return;
//    }
//    ddl($element);
//
//    if (!empty($element['#files'])) {
//      foreach ($element['#files'] as $file) {
//        if ($file instanceof File) {
//
//          self::$entityTypeManager;
//          $media_type = $this->getExtensionBundle(pathinfo($file->getFileUri(), PATHINFO_EXTENSION));
//          $media_bundle = 'file';
//          if(){
//            $media_bundle = 'image';
//          }
//
//
//          $media_type = $this->entityTypeManager
//            ->getStorage('media_type')
//            ->load($media_bundle);
//
//          $this->entityTypeManager->getStorage('media')
//            ->create([
//              'bundle' => $media_type->id(),
//              $media_type->getSource()
//                ->getConfiguration()['source_field'] => $file,
//              'uid' => $this->currentUser->id(),
//              'status' => TRUE,
//              'type' => $media_type->getSource()->getPluginId(),
//            ]);
//        }
//      }
//    }
  }

}
