<?php

/**
 * @file
 * mrc_page.post_update.php
 */

/**
 * Adds menu block to node display.
 */
function mrc_page_page_post_update_8_0_4() {
  \Drupal::service('module_installer')->install(['menu_block']);

  $data = [
    'mrc_ds_blocks' => [
      'menu_block:main' => [
        'parent_name' => '',
        'config' => [
          'parent' => 'main:',
          'level' => '1',
          'admin_label' => '',
          'label_display' => 'visible',
          'label' => 'Main navigation',
          'depth' => '0',
          'suggestion' => 'main',
          'provider' => 'menu_block',
          'follow' => 1,
          'follow_parent' => '0',
          'expand' => 1,
        ],
        'weight' => 20,
      ],
    ],
    'ds' => [
      'regions' => [
        'content' => [
          0 => 'field_s_mrc_page_bricks',
        ],
        'sidebar' => [
          0 => 'menu_block:main',
        ],
      ],
      'layout' => [
        'disable_css' => FALSE,
        'library' => NULL,
        'id' => 'pattern_node_basic',
        'entity_classes' => 'all_classes',
        'settings' => [
          'pattern' => [
            'field_templates' => 'default',
          ],
        ],
      ],
    ],
  ];

  /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
  $config_factory = \Drupal::configFactory();
  /** @var \Drupal\Core\Config\Config $config_entity */
  $config_entity = $config_factory->getEditable('core.entity_view_display.node.stanford_basic_page.default');
  $config_entity->set('third_party_settings', $data);
  $config_entity->save();
}