<?php

namespace Drupal\mrc_ds_blocks\Form;

use Drupal\Core\Block\BlockManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mrc_ds_blocks\MrcDsBlocks;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for adding a block to a bundle.
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
   * The context for the block.
   *
   * @var string
   */
  protected $context = 'view';

  /**
   * The mode for the display.
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
    $first_block = NULL;
    foreach ($this->blockManager->getDefinitions() as $block_id => $block) {
      $category = is_string($block['category']) ? $block['category'] : $block['category']->render();
      $label = is_string($block['admin_label']) ? $block['admin_label'] : $block['admin_label']->render();
      $options[$category][$block_id] = $label;

      if (empty($first_block)) {
        $first_block = $block_id;
      }
    }

    $form['block'] = [
      '#type' => 'select',
      '#title' => $this->t('Block'),
      '#options' => $options,
      '#ajax' => [
        'callback' => 'Drupal\mrc_ds_blocks\Form\MrcDsBlocksAddForm::ajaxSubmit',
        'wrapper' => 'block-config',
        'event' => 'change',
        'method' => 'replace',
      ],
    ];


    $form['block_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Block Configuration'),
      '#prefix' => '<div id="block-config">',
      '#suffix' => '</div>',
    ];

    $selected_block = $form_state->getValue('block') ?: $first_block;
    $block = $this->blockManager->createInstance($selected_block);

    $block_form = [];
    $form['block_config'] += $block->buildConfigurationForm($block_form, $form_state);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Block'),
      '#button_type' => 'primary',
    ];
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public static function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    return $form['block_config'];
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
    $form_state->cleanValues();

    $new_block = (object) [
      'blockId' => $block_id,
      'entity_type' => $this->entityTypeId,
      'bundle' => $this->bundle,
      'mode' => $this->mode,
      'context' => $this->context,
      'config' => [],
      'parent_name' => '',
      'weight' => 20,
    ];

    foreach ($form_state->getValues() as $key => $value) {
      if ($key != 'block') {
        $new_block->config[$key] = $value;
      }
    }

    mrc_ds_blocks_save($new_block);
    drupal_set_message(t('New block %label successfully added.', ['%label' => $block_label]));
    $form_state->setRedirectUrl(MrcDsBlocks::getFieldUiRoute($new_block));
  }

}
