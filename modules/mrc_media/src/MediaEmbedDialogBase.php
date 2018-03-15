<?php

namespace Drupal\mrc_media;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

abstract class MediaEmbedDialogBase extends PluginBase implements MediaEmbedDialogInterface, ContainerFactoryPluginInterface {

  protected $entityTypeManager;

  /**
   * Constant key in the embed dialog.
   *
   * @var string
   */
  protected $settingsKey = 'data-entity-embed-display-settings';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_manager;
  }

  public function alterDialogForm(array &$form, FormStateInterface $form_state) {
    array_unshift($form['#validate'], [self::class, 'validateDialogForm']);
    array_unshift($form['#submit'], [self::class, 'submitDialogForm']);
  }

  public static function validateDialogForm(array &$form, FormStateInterface $form_state) {

  }

  public static function submitDialogForm(array &$form, FormStateInterface $form_state) {

  }

  public function embedAlter(array &$build, MediaInterface $entity, array &$context) {
    $build['entity']['#display_settings'] = $context['data-entity-embed-display-settings'];
    $build['entity']['#pre_render'][] = [static::class, 'preRender'];
  }

  public static function preRender(array $element) {
    return $element;
  }

}
