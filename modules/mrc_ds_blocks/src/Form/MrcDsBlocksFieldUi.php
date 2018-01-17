<?php

namespace Drupal\mrc_ds_blocks\Form;

use Drupal\Core\Block\BlockManager;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Drupal\Component\Utility\Html;
use Drupal\mrc_ds_blocks\MrcDsBlocks;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MrcDsBlocksFieldUi.
 */
class MrcDsBlocksFieldUi implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.block'));
  }

  /**
   * MrcDsBlocksFieldUi constructor.
   *
   * @param \Drupal\Core\Block\BlockManager $block_manager
   */
  public function __construct(BlockManager $block_manager) {
    $this->blockManager = $block_manager;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
    $callback_object = $form_state->getBuildInfo()['callback_object'];
    if (!$callback_object instanceof EntityDisplayFormBase) {
      throw new \InvalidArgumentException('Unkown callback object.');
    }

    $display = $callback_object->getEntity();
    $params = mrc_ds_blocks_field_ui_form_params($form, $display);

    $table = &$form['fields'];
    $blocks = $this->blockManager->getDefinitions();

    $form['#mrc_ds_blocks'] = array_keys($params->blocks);

    $base_button = [
      '#submit' => [
        [
          $form_state->getBuildInfo()['callback_object'],
          'multistepSubmit',
        ],
      ],
      '#ajax' => [
        'callback' => [
          $form_state->getBuildInfo()['callback_object'],
          'multistepAjax',
        ],
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
      ],
    ];

    // Go through each block and add it to the table.
    foreach ($params->blocks as $block_id => $block) {

      // Skip if the block doesn't exist.
      if (!$this->blockManager->hasDefinition($block_id)) {
        continue;
      }

      if ($form_state->get('plugin_settings_update') == $block_id) {
        $block = $this->updateBlock($form, $form_state);
      }

      $base_button['#field_name'] = $block_id;
      $block_row = [
        '#block' => $block,
        '#attributes' => [
          'class' => ['draggable', 'tabledrag-leaf'],
          'id' => $block_id,
        ],
        '#row_type' => 'field',
        '#region_callback' => $params->region_callback,
        '#js_settings' => ['rowHandler' => 'field'],
        'human_name' => [
          '#markup' => Html::escape($blocks[$block_id]['admin_label']),
          '#prefix' => '<span class="block-label">',
          '#suffix' => '</span>',
        ],
        'weight' => [
          '#type' => 'textfield',
          '#default_value' => $block->weight,
          '#size' => 3,
          '#attributes' => ['class' => ['field-weight']],
        ],
        'parent_wrapper' => [
          'parent' => [
            '#type' => 'select',
            '#options' => $table['#parent_options'],
            '#empty_value' => '',
            '#default_value' => $block->parent_name,
            '#attributes' => ['class' => ['field-parent']],
            '#parents' => ['fields', $block_id, 'parent'],
          ],
          'hidden_name' => [
            '#type' => 'hidden',
            '#default_value' => $block_id,
            '#attributes' => ['class' => ['field-name']],
          ],
        ],
        'region' => [
          '#type' => 'select',
          '#title' => $this->t('Region for @title', ['@title' => $block_id]),
          '#title_display' => 'invisible',
          '#options' => $callback_object->getRegionOptions(),
          '#default_value' => isset($block->region) ? $block->region : 'hidden',
          '#attributes' => ['class' => ['field-region']],
        ],
        'label' => [],
        'plugin' => [],
        'settings_summary' => [],
      ];

      if ($form_state->get('plugin_settings_edit') == $block_id) {
        $block_row['settings_edit']['#cell_attributes'] = ['colspan' => 2];
        $block_row['plugin'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['field-plugin-settings-edit-form']],
          '#array_parents' => ['fields', $block_id, 'settings_edit_form'],
          '#weight' => -5,
          // Create a settings form where hooks can pick in.
          'settings' => $this->getBlockForm($form_state, $block_id, $block->config),
          'actions' => [
            '#type' => 'actions',
            'save_settings' => $base_button + [
                '#type' => 'submit',
                '#name' => $block_id . '_plugin_settings_update',
                '#value' => $this->t('Update'),
                '#op' => 'update',
              ],
            'cancel_settings' => $base_button + [
                '#type' => 'submit',
                '#name' => $block_id . '_plugin_settings_cancel',
                '#value' => $this->t('Cancel'),
                '#op' => 'cancel',
                // Do not check errors for the 'Cancel' button.
                '#limit_validation_errors' => [],
              ],
          ],
        ];
        $block_row['#attributes']['class'][] = 'field-formatter-settings-editing';
      }
      else {
        $block_row['settings_edit'] = [
          '#type' => 'image_button',
          '#name' => $block_id . '_block_settings_edit',
          '#src' => 'core/misc/icons/787878/cog.svg',
          '#attributes' => [
            'class' => ['field-plugin-settings-edit'],
            'alt' => $this->t('Edit'),
          ],
          '#op' => 'edit',
          // Do not check errors for the 'Edit' button, but make sure we get
          // the value of the 'plugin type' select.
          '#limit_validation_errors' => [['fields', $block_id, 'type']],
          '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
          '#suffix' => '</div>',
        ];

        $block_row['settings_edit'] += $base_button;

        // Add the delete button.
        $block->blockId = $block_id;
        $delete_route = MrcDsBlocks::getDeleteRoute($block);
        $block_row['settings_summary']['#markup'] = Link::fromTextAndUrl(t('delete'), $delete_route)
          ->toString();
      }

      $table[$block_id] = $block_row;
    }
    array_unshift($form['actions']['submit']['#submit'], 'mrc_ds_blocks_form_submit');
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $block_id
   * @param array $config
   *
   * @return array
   */
  protected function getBlockForm(FormStateInterface $form_state, $block_id, $config = []) {
    $form = [];
    $block = $this->blockManager->createInstance($block_id, $config);
    $form = $block->buildConfigurationForm($form, $form_state);
    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \stdClass
   */
  protected function updateBlock(array $form, FormStateInterface $form_state) {

    $callback_object = $form_state->getBuildInfo()['callback_object'];
    if (!$callback_object instanceof EntityDisplayFormBase) {
      throw new \InvalidArgumentException('Unkown callback object.');
    }

    $display = $callback_object->getEntity();

    $parameters = mrc_ds_blocks_field_ui_form_params($form, $display);
    $block_id = $form_state->get('plugin_settings_update');
    $blocks = $parameters->blocks;
    $config = &$blocks[$block_id]->config;

    $form_values = $form_state->getValue([
      'fields',
      $block_id,
      'plugin',
      'settings',
    ]);

    if (!empty($form_values)) {
      $this->matchConfig($config, $form_values);
      return mrc_ds_blocks_save($blocks[$block_id]);
    }
  }

  /**
   * @param mixed $config
   * @param mixed $form_values
   */
  protected function matchConfig(&$config, $form_values) {
    if (!is_array($config)) {
      return;
    }

    foreach ($config as $key => &$value) {
      if (is_array($value)) {
        $this->matchConfig($value, $form_values);
        continue;
      }
      $value = $this->findValue($key, $form_values);
    }
  }

  /**
   * Find a nested value
   *
   * @param $key
   * @param array $values
   *
   * @return mixed|null
   */
  protected function findValue($key, array $values) {
    if (isset($values[$key])) {
      return $values[$key];
    }
    foreach ($values as $value) {
      if (is_array($value)) {
        return $this->findValue($key, $value);
      }
    }
    return NULL;
  }

  /**
   * @param array $row
   *
   * @return mixed
   */
  public static function getRowRegion(&$row) {
    return $row['#block']->region;
  }

}
