<?php

namespace Drupal\mrc_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\dropzonejs\DropzoneJsUploadSave;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\inline_entity_form\ElementSubmit;
use Drupal\media\Entity\MediaType;
use Drupal\mrc_media\BundleSuggestion;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class MediaBrowserBase extends WidgetBase {

  /**
   * Dropzone upload save service.
   *
   * @var \Drupal\dropzonejs\DropzoneJsUploadSave
   */
  protected $dropzoneJsSave;

  /**
   * @var \Drupal\mrc_media\BundleSuggestion
   */
  protected $bundleSuggestion;

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
      $container->get('current_user'),
      $container->get('dropzonejs.upload_save')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, BundleSuggestion $bundles, AccountProxyInterface $current_user, DropzoneJsUploadSave $dropzone_save) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->bundleSuggestion = $bundles;
    $this->currentUser = $current_user;
    $this->dropzoneJsSave = $dropzone_save;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['form_mode'] = 'media_browser';
    return $configuration;
  }

  /**
   * Returns the bundles that this widget may use.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return string[]
   *   The bundles that this widget may use. If all bundles may be used, the
   *   returned array will be empty.
   */
  protected function getAllowedBundles(FormStateInterface $form_state) {
    return (array) $form_state->get([
      'entity_browser',
      'widget_context',
      'target_bundles',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);
    $this->getEntityForm($form, $form_state, $additional_widget_parameters);
    return $form;
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
    foreach ($media_entities as &$media_entity) {
      $media_entity->save();
    }
    $this->selectEntities($media_entities, $form_state);
  }

  /**
   * Add the inline entity form after the files have been uploaded.
   *
   * @param array $form
   *   Original form from getFrom().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param array $additional_widget_parameters
   *   Additional parameters we dont need.
   *
   * @return array
   */
  protected function getEntityForm(array &$form, FormStateInterface $form_state, array $additional_widget_parameters) {
    if (isset($form['actions'])) {
      $form['actions']['#weight'] = 100;
    }

    $form['entities'] = [
      '#prefix' => '<div id="entities">',
      '#suffix' => '</div>',
      '#weight' => 99,
    ];

    $media_entities = $this->prepareEntities($form, $form_state);

    // No entities to create forms/previews for.
    if (empty($media_entities)) {
      $form['entities']['#markup'] = NULL;
      return $form;
    }

    unset($form['actions']);

    foreach ($media_entities as $entity) {
      $form['entities'][$entity->id()] = [
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
    return $form;
  }

  /**
   * Build a media entity using the given media type and source data.
   *
   * @param \Drupal\media\Entity\MediaType $media_type
   *   Media type to create entity in.
   * @param mixed $source_value
   *   Files, string or other to put into the source field.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Created media entity.
   */
  protected function prepareMediaEntity(MediaType $media_type, $source_value) {
    $media_storage = $this->entityTypeManager->getStorage('media');

    $source_field = $media_type->getSource()
      ->getConfiguration()['source_field'];

    $entity_data = [
      'bundle' => $media_type->id(),
      $source_field => is_array($source_value) ? $source_value : [$source_value],
      'uid' => $this->currentUser->id(),
      'status' => TRUE,
      'type' => $media_type->getSource()->getPluginId(),
    ];

    return $media_storage->create($entity_data);
  }

}
