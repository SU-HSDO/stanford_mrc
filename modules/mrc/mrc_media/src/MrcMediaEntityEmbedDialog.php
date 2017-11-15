<?php

namespace Drupal\mrc_media;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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
    /** @var \Drupal\filter\Entity\FilterFormat $filter_format */
    $filter_format = $form_state->getBuildInfo()['args'][0];

    /** @var \Drupal\editor\EditorInterface $editor */
    $editor = $this->editorStorage->load($filter_format->id());
    $plugin_settings = $editor->getSettings()['plugins']['drupallink'];

    // Do not alter the form if Linkit is not enabled for this text format.
    if (!isset($plugin_settings['linkit_enabled']) || (isset($plugin_settings['linkit_enabled']) && !$plugin_settings['linkit_enabled'])) {
      return;
    }

    $linkit_profile_id = $editor->getSettings()['plugins']['drupallink']['linkit_profile'];

    if (isset($form_state->getUserInput()['editor_object'])) {
      $input = $form_state->getUserInput()['editor_object'];
      $form_state->set('link_element', $input);
      $form_state->setCached(TRUE);
    }
    else {
      // Retrieve the link element's attributes from form state.
      $input = $form_state->get('link_element') ?: [];
    }

    $link_form = &$form['attributes']['data-entity-embed-display-settings']['link_url'];

    $form['href_dirty_check'] = [
      '#type' => 'hidden',
      '#default_value' => isset($input['href']) ? $input['href'] : '',
    ];

    $link_form = array_merge($link_form, [
      '#type' => 'linkit',
      '#description' => $this->t('Start typing to find content.'),
      '#autocomplete_route_name' => 'linkit.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => $linkit_profile_id,
      ],
      "#weight" => -10,
      '#default_value' => isset($input['href']) ? $input['href'] : '',
    ]);

    $form['attributes']["#weight"] = -100;
  }

}
