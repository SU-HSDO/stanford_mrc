<?php

namespace Drupal\mrc_ds_blocks\Form;

use Drupal\Core\Block\BlockManager;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for adding a fieldgroup to a bundle.
 */
class MrcDsBlocksAddForm extends FormBase {

  /**
   * The name of the entity type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The context for the group.
   *
   * @var string
   */
  protected $context;

  /**
   * The mode for the group.
   *
   * @var string
   */
  protected $mode;

  /**
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block')
    );
  }

  public function __construct(BlockManager $block_manager) {
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mrc_ds_blocks_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL, $context = NULL) {

    if ($context == 'form') {
      $this->mode = \Drupal::request()->get('form_mode_name');
    }
    else {
      $this->mode = \Drupal::request()->get('view_mode_name');
    }

    if (empty($this->mode)) {
      $this->mode = 'default';
    }

    if (!$form_state->get('context')) {
      $form_state->set('context', $context);
    }
    if (!$form_state->get('entity_type_id')) {
      $form_state->set('entity_type_id', $entity_type_id);
    }
    if (!$form_state->get('bundle')) {
      $form_state->set('bundle', $bundle);
    }
    if (!$form_state->get('step')) {
      $form_state->set('step', 'formatter');
    }

    $this->entityTypeId = $form_state->get('entity_type_id');
    $this->bundle = $form_state->get('bundle');
    $this->context = $form_state->get('context');
    $this->currentStep = $form_state->get('step');

    $this->buildConfigurationForm($form, $form_state);
    return $form;

  }

  /**
   * Build the formatter configuration form.
   */
  function buildConfigurationForm(array &$form, FormStateInterface $form_state) {
    $options = [];
    foreach ($this->blockManager->getDefinitions() as $block_id => $block) {
      $category = is_string($block['category']) ? $block['category'] : $block['category']->render();
      $label = is_string($block['admin_label']) ? $block['admin_label'] : $block['admin_label']->render();
      $options[$category][$block_id] = $label;
    }

    $form['block'] = [
      '#type' => 'select',
      '#title' => $this->t('Block'),
      '#options' => $options,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Block'),
      '#button_type' => 'primary',
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $block_label = '';
    $block_id = $form_state->getValue('block');

    foreach ($form['block']['#options'] as $grouping) {
      if (isset($grouping[$block_id])) {
        $block_label = $grouping[$block_id];
        break;
      }
    }

    $new_block = (object) [
      'blockId' => $block_id,
      'entity_type' => $this->entityTypeId,
      'bundle' => $this->bundle,
      'mode' => $this->mode,
      'context' => $this->context,
      'children' => [],
      'parent_name' => '',
      'weight' => 20,
    ];
    mrc_ds_blocks_save($new_block);

    drupal_set_message(t('New block %label successfully added.', ['%label' => $block_label]));

    $form_state->setRedirectUrl(self::getRoute($new_block));
    \Drupal::cache()->invalidate('field_groups');
  }

  /**
   * Get the field ui route that should be used for given arguments.
   *
   * @param stdClass $group
   *   The group to get the field ui route for.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public static function getRoute($group) {

    $entity_type = \Drupal::entityTypeManager()
      ->getDefinition($group->entity_type);
    if ($entity_type->get('field_ui_base_route')) {

      $context_route_name = "";
      $mode_route_name = "default";
      $route_parameters = self::getRouteBundleParameter($entity_type, $group->bundle);

      // Get correct route name based on context and mode.
      if ($group->context == 'form') {
        $context_route_name = 'entity_form_display';

        if ($group->mode != 'default') {
          $mode_route_name = 'form_mode';
          $route_parameters['form_mode_name'] = $group->mode;
        }

      }
      else {
        $context_route_name = 'entity_view_display';

        if ($group->mode != 'default') {
          $mode_route_name = 'view_mode';
          $route_parameters['view_mode_name'] = $group->mode;
        }

      }

      return new Url("entity.{$context_route_name}.{$group->entity_type}.{$mode_route_name}", $route_parameters);
    }
  }

  /**
   * Gets the route parameter that should be used for Field UI routes.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The actual entity type, not the bundle (e.g. the content entity type).
   * @param string $bundle
   *   The bundle name.
   *
   * @return array
   *   An array that can be used a route parameter.
   */
  public static function getRouteBundleParameter(EntityTypeInterface $entity_type, $bundle) {
    $bundle_parameter_key = $entity_type->getBundleEntityType() ?: 'bundle';
    return [$bundle_parameter_key => $bundle];
  }

}
