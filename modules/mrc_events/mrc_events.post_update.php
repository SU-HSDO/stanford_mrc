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
function mrc_events_post_update_8_0_5(){
  \Drupal::service('module_installer')->install(['views_taxonomy_term_name_depth']);
  mrc_events_post_update_8_0_4();

  $config_factory = \Drupal::configFactory();
  $config = $config_factory->getEditable('core.entity_form_display.node.stanford_event.default');
  $config->set('content.field_s_event_date.settings.increment', 15);
  $config->save();
}
