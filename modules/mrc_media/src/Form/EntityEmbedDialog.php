<?php

namespace Drupal\mrc_media\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\linkit\Element\Linkit;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Integrate the linkit module with entity embed dialog.
 */
class EntityEmbedDialog implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The linkit profile storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $editorStorage;

  /**
   * Entity Manager object to get entity information.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * Constant key in the embed dialog.
   *
   * @var string
   */
  protected $settingsKey = 'data-entity-embed-display-settings';

  /**
   * Construct LinkitEntityEmbedDialog service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->editorStorage = $entity_type_manager->getStorage('editor');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Alter form embed form to give an additional fields and settings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
    $entity = $form_state->get('entity');

    switch ($entity->bundle()) {
      case 'image':
        $this->entityEmbedImage($form, $form_state);
        break;

      case 'file':
        $this->entityEmbedFile($form, $form_state);
        break;
    }
  }

  /**
   * Use Linkit functions but replace the autocomplete library with our own.
   *
   * {@inheritdoc}
   *
   * @see Linkit::processLinkitAutocomplete()
   */
  public static function processLinkitAutocomplete(&$element, FormStateInterface $form_state, &$complete_form) {
    Linkit::processLinkitAutocomplete($element, $form_state, $complete_form);
    // Replace linkit autocomplete library with our own to fix some nasty bugs.
    $element['#attached']['library'] = ['mrc_media/mrc_media.autocomplete'];
    return $element;
  }

  /**
   * Adds a linkit field to the form.
   *
   * @param array $form
   *   Embed dialog form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Embed dialog form state object.
   */
  private function buildLinkitField(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\filter\Entity\FilterFormat $filter_format */
    $filter_format = $form_state->getBuildInfo()['args'][0];

    /** @var \Drupal\editor\EditorInterface $editor */
    $editor = $this->editorStorage->load($filter_format->id());
    $plugin_settings = $editor->getSettings()['plugins']['drupallink'];

    // Do not alter the form if Linkit is not enabled for this text format.
    if (!isset($plugin_settings['linkit_enabled']) || (isset($plugin_settings['linkit_enabled']) && !$plugin_settings['linkit_enabled'])) {
      return;
    }

    $input = [];
    if (isset($form_state->getUserInput()['editor_object'])) {
      $editor_object = $form_state->getUserInput()['editor_object'];
      $display_settings = Json::decode($editor_object[$this->settingsKey]);
      $input = isset($display_settings['linkit']) ? $display_settings['linkit'] : [];
    }

    $linkit_profile_id = $editor->getSettings()['plugins']['drupallink']['linkit_profile'];

    $link_form = [
      '#title' => $this->t('Link'),
      '#type' => 'textfield',
      '#description' => $this->t('Start typing to find content.'),
      '#autocomplete_route_name' => 'linkit.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => $linkit_profile_id,
      ],
      '#default_value' => isset($input['href']) ? $input['href'] : '',
      '#states' => [
        'visible' => [
          '[name="attributes[data-entity-embed-display-settings][image_link]"]' => ['value' => 'url'],
        ],
      ],
      '#process' => [
        [self::class, 'processLinkitAutocomplete'],
      ],
    ];

    $form['attributes'][$this->settingsKey]['linkit'] = [
      '#type' => 'container',
      '#weight' => 99.5,
    ];

    $fields = [
      'data-entity-type',
      'data-entity-uuid',
      'data-entity-substitution',
    ];


    $form['attributes'][$this->settingsKey]['linkit']['href_dirty_check'] = [
      '#type' => 'hidden',
      '#default_value' => isset($input['href']) ? $input['href'] : '',
    ];

    $form['attributes'][$this->settingsKey]['linkit']['href'] = $link_form;
    foreach ($fields as $field) {
      $form['attributes'][$this->settingsKey]['linkit'][$field] = [
        '#title' => $field,
        '#type' => 'hidden',
        '#default_value' => isset($input[$field]) ? $input[$field] : '',
      ];
    }

    array_unshift($form['#submit'], [self::class, 'formSubmit']);
  }

  /**
   * Clean up the linkit settings.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public static function formSubmit(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValue([
      'attributes',
      'data-entity-embed-display-settings',
    ]);
    $settings = array_filter($settings);
    // Clean up the display settings, but we still want at least an empty alt
    // text. This also helps prevent an empty array which converts to an empty
    // string. An empty string breaks the render portion.
    $settings['alt_text'] = isset($settings['alt_text']) ? $settings['alt_text'] : '';
    $form_state->setValue([
      'attributes',
      'data-entity-embed-display-settings',
    ], $settings);

    $linkit_key = [
      'attributes',
      'data-entity-embed-display-settings',
      'linkit',
    ];
    $linkit_settings = $form_state->getValue($linkit_key);

    $href = $linkit_settings['href'];
    // No link: unset values to clean up embed code.
    if (!$href) {
      $form_state->unsetValue($linkit_key);
      return;
    }

    $href_dirty_check = $linkit_settings['href_dirty_check'];

    // Unset the attributes since this is an external url.
    if (!$href || $href !== $href_dirty_check) {
      unset($linkit_settings['data-entity-type']);
      unset($linkit_settings['data-entity-uuid']);
      unset($linkit_settings['data-entity-substitution']);
    }

    unset($linkit_settings['href_dirty_check']);

    $form_state->setValue($linkit_key, array_filter($linkit_settings));
  }

  /**
   * Adds a image attribute fields to the form.
   *
   * @param array $form
   *   Embed dialog form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Embed dialog form state object.
   */
  private function entityEmbedImage(array &$form, FormStateInterface $form_state) {
    $input = [];
    if (isset($form_state->getUserInput()['editor_object'])) {
      $editor_object = $form_state->getUserInput()['editor_object'];
      $display_settings = Json::decode($editor_object[$this->settingsKey]);
      $input = $display_settings ?: [];
    }

    /** @var \Drupal\media\Entity\Media $entity */
    $entity = $form_state->getStorage()['entity'];
    $source_field = $entity->getSource()
      ->getConfiguration()['source_field'];
    /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $image_field */
    $image_field = $entity->get($source_field);
    $default_alt = $image_field->getValue()[0]['alt'];

    $form['attributes'][$this->settingsKey]['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Style'),
      '#options' => $this->getImageStyles(),
      '#default_value' => isset($input['image_style']) ? $input['image_style'] : '',
      '#empty_option' => $this->t('None (original image)'),
    ];
    $form['attributes'][$this->settingsKey]['alt_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alt Text'),
      '#default_value' => isset($input['alt_text']) ? $input['alt_text'] : $default_alt,
    ];
    $form['attributes'][$this->settingsKey]['title_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title Text'),
      '#default_value' => isset($input['title_text']) ? $input['title_text'] : '',
    ];
    $this->buildLinkitField($form, $form_state);
  }

  /**
   * @return array
   */
  private function getImageStyles() {
    $styles = $this->entityTypeManager->getStorage('image_style')
      ->loadMultiple();
    $style_options = [];
    /** @var \Drupal\image\Entity\ImageStyle $style */
    foreach ($styles as $style) {
      $style_options[$style->id()] = $style->label();
    }
    asort($style_options);
    return $style_options;
  }

  /**
   * Adds a document attribute fields to the form.
   *
   * @param array $form
   *   Embed dialog form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Embed dialog form state object.
   */
  private function entityEmbedFile(array &$form, FormStateInterface $form_state) {
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
   * Alter the media render array to put the attibutes and values in place.
   *
   * @param array $render
   *   Render array for media entity.
   *
   * @return array
   *   Modified render array.
   */
  public static function preRender(array $render) {
    if (isset($render['#display_settings'])) {
      switch ($render['#media']->bundle()) {
        case 'image':
          self::preRenderImage($render);
          break;

        case 'file':
          self::preRenderFile($render);
          break;
      }
    }

    return $render;
  }

  /**
   * Modify the image media type to add a link and apply an image style.
   *
   * @param array $render
   *   Render array on the media entity.
   */
  private static function preRenderImage(array &$render) {
    $source_field = $render['#media']->getSource()
      ->getConfiguration()['source_field'];

    if (!empty($render['#display_settings']['alt_text'])) {
      $render[$source_field][0]['#item_attributes']['alt'] = $render['#display_settings']['alt_text'];
    }

    if (!empty($render['#display_settings']['title_text'])) {
      $render[$source_field][0]['#item_attributes']['title'] = $render['#display_settings']['title_text'];
    }

    if (!empty($render['#display_settings']['linkit'])) {
      $render[$source_field][0]['#url'] = $render['#display_settings']['linkit']['href'];
      unset($render['#display_settings']['linkit']['href']);
      $render[$source_field][0]['#attributes'] = $render['#display_settings']['linkit'];
    }

    if (!empty($render['#display_settings']['image_style'])) {
      $render[$source_field][0]['#image_style'] = $render['#display_settings']['image_style'];
    }
    else {
      unset($render[$source_field][0]['#image_style']);
    }
  }

  /**
   * Modify the file media type to change the link text.
   *
   * @param array $render
   *   Render array on the media entity.
   */
  private static function preRenderFile(array &$render) {
    $source_field = $render['#media']->getSource()
      ->getConfiguration()['source_field'];
    $render[$source_field][0]['#description'] = $render['#display_settings']['description'];
  }

}
