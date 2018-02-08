<?php

namespace Drupal\mrc_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\inline_entity_form\ElementSubmit;
use Drupal\media\MediaInterface;
use Drupal\mrc_media\BundleSuggestion;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * An Entity Browser widget for creating media entities from embed codes.
 *
 * @EntityBrowserWidget(
 *   id = "embed_code",
 *   label = @Translation("Embed Code"),
 *   description = @Translation("Create media entities from embed codes."),
 * )
 */
class EmbedCode extends WidgetBase {

  /**
   * @var \Drupal\mrc_media\BundleSuggestion
   */
  protected $bundleSuggestion;

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
      $container->get('mrc_media.bundle_suggestion')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, BundleSuggestion $bundles) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->bundleSuggestion = $bundles;
  }

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
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $additional_widget_parameters
   *
   * @return array
   */
  public function getEntityForm(array &$form, FormStateInterface $form_state, array $additional_widget_parameters) {

    if (isset($form['actions'])) {
      $form['actions']['#weight'] = 100;
    }

    $form['entity'] = [
      '#prefix' => '<div id="entity">',
      '#suffix' => '</div>',
      '#weight' => 99,
    ];

    $value = $form_state->getValue('input');
    if (empty($value)) {
      $form['entity']['#markup'] = NULL;
      return $form;
    }

    try {
      $entity = $this->createFromInput($value, $this->getAllowedBundles($form_state));
    }
    catch (\Exception $e) {
      return $form;
    }

    $form['entity'] += [
      '#type' => 'inline_entity_form',
      '#entity_type' => $entity->getEntityTypeId(),
      '#bundle' => $entity->bundle(),
      '#default_value' => $entity,
      '#form_mode' => $this->configuration['form_mode'],
    ];
    // Without this, IEF won't know where to hook into the widget. Don't pass
    // $original_form as the second argument to addCallback(), because it's not
    // just the entity browser part of the form, not the actual complete form.
    ElementSubmit::addCallback($form['actions']['submit'], $form_state->getCompleteForm());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];
    $form['input'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Video URL'),
      '#required' => TRUE,
      '#placeholder' => $this->t('Enter a URL...'),
      '#ajax' => [
        'event' => 'change',
        'wrapper' => 'entity',
        'method' => 'html',
        'callback' => [static::class, 'ajax'],
      ],
    ];
    $form['preview'] = [
      '#prefix' => '<div id="video-preview">',
      '#suffix' => '</div>',
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Video'),
    ];
    //    $form['#attached']['library'][] = 'mrc_media/mrc_media.browser';
    //    $form['#attached']['library'][] = 'mrc_media/mrc_media.embed';

    $this->getEntityForm($form, $form_state, $additional_widget_parameters);
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
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    // IEF will take care of creating the entity upon submission. All we need to
    // do is send it upstream to Entity Browser.
    $entity = $form['widget']['entity']['#entity'];
    $this->selectEntities([$entity], $form_state);
  }

  /**
   * AJAX callback. Returns the rebuilt inline entity form.
   *
   * @param array $form
   *   The complete form.
   * @param FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public static function ajax(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\video_embed_field\ProviderPluginBase $video_provider */
    $video_provider = \Drupal::service('video_embed_field.provider_manager')
      ->loadProviderFromInput($form_state->getValue('input'));

    $iframe = [
      '#markup' => '',
      '#prefix' => '<div id="video-preview">',
      '#suffix' => '</div>',
    ];

    if ($video_provider) {
      $iframe = $video_provider->renderEmbedCode(300, 200, FALSE);
      $iframe += [
        '#prefix' => '<div id="video-preview">',
        '#suffix' => '</div>',
      ];
    }

    $response = new AjaxResponse();
    $response->addCommand((new ReplaceCommand('#video-preview', $iframe)));
    $form_state->setRebuild();
    return $response;
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
   */
  public function createFromInput($value, array $bundles = []) {
    /** @var \Drupal\media\MediaInterface $entity */
    $entity = $this->entityTypeManager
      ->getStorage('media')
      ->create([
        'bundle' => $this->getBundleFromInput($value, TRUE, $bundles)->id(),
      ]);

    $field = static::getSourceField($entity);
    if ($field) {
      $field->setValue($value);
    }
    return $entity;
  }

  /**
   * Returns the media entity's source field item list.
   *
   * @param \Drupal\media\MediaInterface $entity
   *   The media entity.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   *   The media entity's source field item list, or NULL if the media type
   *   plugin does not define a source field.
   */
  public static function getSourceField(MediaInterface $entity) {
    $field = $entity->getSource()
      ->getSourceFieldDefinition($entity->bundle->entity);

    return $field ? $entity->get($field->getName()) : NULL;
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
   * Returns the first media bundle that can accept an input value.
   *
   * @param mixed $value
   *   The input value.
   * @param bool $check_access
   *   (optional) Whether to filter the bundles by create access for the current
   *   user. Defaults to TRUE.
   * @param string[] $bundles
   *   (optional) A set of media bundle IDs which might match the input. If
   *   omitted, all available bundles are checked.
   *
   * @return \Drupal\media\MediaTypeInterface
   *   A media bundle that can accept the input value.
   *
   * @throws \Exception if no bundle can be matched to the input value.
   */
  public function getBundleFromInput($value, $check_access = TRUE, array $bundles = []) {
    // Lightning Media overrides the media_bundle storage handler with a special
    // one that adds an optional second parameter to loadMultiple().
    $media_types = $this->entityTypeManager
      ->getStorage('media_type')
      ->loadMultiple($bundles ?: NULL, $check_access);
    ksort($media_types);

    /** @var \Drupal\media\MediaTypeInterface $media_type */
    foreach ($media_types as $type_id => $media_type) {
      // @todo: Fix this!
      $source = $media_type->getSource();
      //      if ($source instanceof InputMatchInterface && $source->appliesTo($value, $media_type)) {
      if ($type_id == 'video') {
        return $media_type;
      }
      //      }
    }
    throw new \Exception($value);
  }

}
