<?php

namespace Drupal\mrc_ds_blocks\Form;

use Drupal\Core\Block\BlockManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mrc_ds_blocks\MrcDsBlocks;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\Context\LazyContextRepository;
use Drupal\Core\Routing\CurrentRouteMatch;

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
   * @var \Drupal\Core\Plugin\Context\LazyContextRepository
   */
  protected $contextRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('context.repository'),
      $container->get('current_route_match')
    );
  }

  public function __construct(BlockManager $block_manager, LazyContextRepository $context_repository, CurrentRouteMatch $route_match) {
    $this->blockManager = $block_manager;
    $this->contextRepository = $context_repository;
    $this->routeMatch = $route_match;
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
    $this->mode = \Drupal::request()->get('view_mode_name');

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
      $form_state->set('step', 'selection');
    }

    $this->entityTypeId = $form_state->get('entity_type_id');
    $this->bundle = $form_state->get('bundle');
    $this->context = $form_state->get('context');

    if ($form_state->get('step') == 'config') {
      $this->buildConfigurationForm($form, $form_state);
      $form['#attached']['library'][] = 'block/drupal.block.admin';
      return $form;
    }

    $this->buildSelectionForm($form, $form_state);
    return $form;
  }

  private function buildSelectionForm(array &$form, FormStateInterface $form_state) {
    // Only add blocks which work without any available context.
    $definitions = $this->blockManager->getDefinitionsForContexts($this->contextRepository->getAvailableContexts());
    // Order by category, and then by admin label.
    $definitions = $this->blockManager->getSortedDefinitions($definitions);

    $form['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by block name'),
      '#attributes' => [
        'class' => ['block-filter-text'],
        'data-element' => '.block-add-table',
        'title' => $this->t('Enter a part of the block name to filter by.'),
      ],
    ];

    $form['blocks'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['block-add-table']],
    ];

    foreach ($definitions as $plugin_id => $plugin_definition) {
      $form['blocks'][$plugin_id]['title'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="block-filter-text-source">{{ label }}</div>',
        '#context' => [
          'label' => $plugin_definition['admin_label'],
        ],
      ];

      $form['blocks'][$plugin_id]['category'] = [
        '#markup' => $plugin_definition['category'],
      ];

      $form['blocks'][$plugin_id]['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Place block'),
        '#name' => 'submit_' . $plugin_id,
      ];
    }
  }

  /**
   * Build the formatter configuration form.
   */
  private function buildConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form['block_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Block Configuration'),
      '#prefix' => '<div id="block-config">',
      '#suffix' => '</div>',
    ];

    $selected_block = $block_id = $form_state->get('block_id');
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('step') != 'config') {
      $block_id = $form_state->getTriggeringElement()['#name'];
      $block_id = substr($block_id, 7);
      $form_state->set('step', 'config');
      $form_state->set('block_id', $block_id);
      $form_state->setRebuild();
      return;
    }

    $block_id = $form_state->get('block_id');
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
      $new_block->config[$key] = $value;
    }

    mrc_ds_blocks_save($new_block);
    drupal_set_message(t('New block %label successfully added.', ['%label' => $block_id]));
    $form_state->setRedirectUrl(MrcDsBlocks::getFieldUiRoute($new_block));
  }

}
