<?php

/**
 * mrc_events.post_update.php
 */

/**
 * Reverts the view.
 */
function mrc_events_post_update_8_0_4() {
  $configs = [
    'views.view.mrc_events',
  ];
  module_load_install('stanford_mrc');
  $path = drupal_get_path('module', 'mrc_events') . '/config/install';
  stanford_mrc_update_configs(TRUE, $configs, $path);
}

/**
 * Enable new module and revert view.
 * Changes to events node.
 */
function mrc_events_post_update_8_0_5() {
  \Drupal::service('module_installer')
    ->install(['views_taxonomy_term_name_depth']);
  $configs = [
    'views.view.mrc_events',
    'core.entity_view_display.node.stanford_event.default',
  ];
  module_load_install('stanford_mrc');
  $path = drupal_get_path('module', 'mrc_events') . '/config/install';
  stanford_mrc_update_configs(TRUE, $configs, $path);

  $config_factory = \Drupal::configFactory();
  $config = $config_factory->getEditable('core.entity_form_display.node.stanford_event.default');
  $config->set('content.field_s_event_date.settings.increment', 15);
  $config->save();
}

/**
 * Create new image style.
 */
function mrc_events_post_update_8_0_6() {
  \Drupal::service('module_installer')->install(['focal_point']);
  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_update->import('image_style', 'event_350');
}

/**
 * Revert the events display.
 */
function mrc_events_post_update_8_0_7() {
  \Drupal::service('module_installer')->install(['menu_position', 'eck']);
  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_update->revert('entity_view_display', 'node.stanford_event.default');
  $config_update->import('menu_position_rule', 'events');

  $config_update->import('eck_entity_type', 'event_collections');
  $config_update->import('eck_entity_bundle', 'event_collections.speaker');
}
