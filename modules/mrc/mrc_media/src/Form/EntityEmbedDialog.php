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
   * Alter form to integrate with the linkit module.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
    $entity = $form_state->get('entity');
    $method = 'entityEmbed' . ucfirst($entity->bundle());
    if (method_exists($this, $method)) {
      $this->{$method}($form, $form_state);
    }
  }

  /**
   * Adds linkit custom autocomplete functionality to elements.
   *
   * Instead of using the core autocomplete, we use our own.
   *
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Render\Element\FormElement::processAutocomplete
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
      "#weight" => $form['attributes'][$this->settingsKey]['image_link']['#weight'] + .5,
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
    $linkit_settings = $form_state->getValue([
      'attributes',
      'data-entity-embed-display-settings',
      'linkit',
    ]);

    $href = $linkit_settings['href'];
    $href_dirty_check = $linkit_settings['href_dirty_check'];

    // Unset the attributes since this is an external url.
    if ($href !== $href_dirty_check) {
      unset($linkit_settings['data-entity-type']);
      unset($linkit_settings['data-entity-uuid']);
      unset($linkit_settings['data-entity-substitution']);
    }

    unset($linkit_settings['href_dirty_check']);
    $form_state->setValue([
      'attributes',
      'data-entity-embed-display-settings',
      'linkit',
    ], array_filter($linkit_settings));
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
    $styles = $this->entityTypeManager->getStorage('image_style')
      ->loadMultiple();
    $style_options = [];
    /** @var \Drupal\image\Entity\ImageStyle $style */
    foreach ($styles as $style) {
      $style_options[$style->id()] = $style->label();
    }
    asort($style_options);

    $input = [];
    if (isset($form_state->getUserInput()['editor_object'])) {
      $editor_object = $form_state->getUserInput()['editor_object'];
      $display_settings = Json::decode($editor_object[$this->settingsKey]);
      $input = $display_settings ?: [];
    }
    $form['attributes'][$this->settingsKey]['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Style'),
      '#options' => $style_options,
      '#default_value' => isset($input['image_style']) ? $input['image_style'] : '',
      '#empty_option' => $this->t('None (original image)'),
    ];
    $form['attributes'][$this->settingsKey]['alt_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alt Text'),
      '#default_value' => isset($input['alt_text']) ? $input['alt_text'] : '',
    ];
    $form['attributes'][$this->settingsKey]['title_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title Text'),
      '#default_value' => isset($input['title_text']) ? $input['title_text'] : '',
    ];
    $this->buildLinkitField($form, $form_state);
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

    $form['attributes'][$this->settingsKey]['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Optionally enter text to use as the link text.'),
      '#default_value' => isset($input['description']) ? $input['description'] : '',
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
      $source_field = $render['#media']->getSource()
        ->getConfiguration()['source_field'];

      switch ($render['#media']->bundle()) {
        case 'image':

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

          $render[$source_field][0]['#image_style'] = $render['#display_settings']['image_style'];
          break;

        case 'file':

          $render[$source_field][0]['#description'] = $render['#display_settings']['description'];
          break;
      }
    }

    return $render;
  }

}
