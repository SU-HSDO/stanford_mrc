<?php

namespace Drupal\mrc_media;

use Drupal\Component\Serialization\Json;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Integrate the linkit module with entity embed dialog.
 */
class MrcMediaEntityEmbedDialog implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The linkit profile storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $editorStorage;

  protected $settingsKey = 'data-entity-embed-display-settings';

  /**
   * Construct LinkitEntityEmbedDialog service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->editorStorage = $entityTypeManager->getStorage('editor');
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
    // Hide the image_link option since it only links to the file itself.
    $form['attributes'][$this->settingsKey]['image_link']['#options']['url'] = $this->t('URL');
    $form['attributes'][$this->settingsKey]['image_link']['#weight'] = 99;

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
   * Adds linkit custom autocomplete functionality to elements.
   *
   * Instead of using the core autocomplete, we use our own.
   *
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Render\Element\FormElement::processAutocomplete
   */
  public static function processLinkitAutocomplete(&$element, FormStateInterface $form_state, &$complete_form) {
    $url = NULL;
    $access = FALSE;

    if (!empty($element['#autocomplete_route_name'])) {
      $parameters = isset($element['#autocomplete_route_parameters']) ? $element['#autocomplete_route_parameters'] : [];
      $url = Url::fromRoute($element['#autocomplete_route_name'], $parameters)
        ->toString(TRUE);
      /** @var \Drupal\Core\Access\AccessManagerInterface $access_manager */
      $access_manager = \Drupal::service('access_manager');
      $access = $access_manager->checkNamedRoute($element['#autocomplete_route_name'], $parameters, \Drupal::currentUser(), TRUE);
    }

    if ($access) {
      $metadata = BubbleableMetadata::createFromRenderArray($element);
      if ($access->isAllowed()) {
        $element['#attributes']['class'][] = 'form-linkit-autocomplete';
        $metadata->addAttachments(['library' => ['mrc_media/mrc_media.autocomplete']]);
        // Provide a data attribute for the JavaScript behavior to bind to.
        $element['#attributes']['data-autocomplete-path'] = $url->getGeneratedUrl();
        $metadata = $metadata->merge($url);
      }
      $metadata
        ->merge(BubbleableMetadata::createFromObject($access))
        ->applyTo($element);
    }
    return $element;
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

}
