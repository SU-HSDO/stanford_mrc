<?php

/**
 * mrc_helper.post_update.php
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Adds menu block to term pages.
 */
function mrc_helper_post_update_8_0_4() {
  \Drupal::service('module_installer')->install(['menu_block']);

  $data = [
    'config' => [
      'provider' => 'menu_block',
      'admin_label' => '',
      'label' => 'Main navigation',
      'label_display' => '0',
      'level' => '1',
      'depth' => '0',
      'expand' => '1',
      'parent' => 'main:',
      'follow' => '1',
      'follow_parent' => '0',
      'suggestion' => 'main',
    ],
    'parent_name' => '',
    'weight' => 0,
    'region' => 'sidebar',
  ];

  /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
  $config_factory = \Drupal::configFactory();
  /** @var \Drupal\Core\Config\Config $config_entity */
  $config_entity = $config_factory->getEditable('core.entity_view_display.taxonomy_term.mrc_event_series.default');
  $config_entity->set('third_party_settings.mrc_ds_blocks.menu_block:main', $data);
  $config_entity->set('third_party_settings.ds.regions.sidebar', ['menu_block:main']);
  $config_entity->save();
}
