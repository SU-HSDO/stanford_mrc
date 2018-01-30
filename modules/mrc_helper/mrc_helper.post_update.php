<?php

/**
 * mrc_helper.post_update.php
 */

/**
 * Adds menu block to term pages.
 */
function mrc_helper_post_update_8_0_4() {
  \Drupal::service('module_installer')->install(['menu_block']);

  module_load_install('stanford_mrc');
  $path = drupal_get_path('module', 'mrc_helper') . '/config/install';

  $configs = [
    'core.entity_view_display.taxonomy_term.mrc_event_series.default',
  ];
  stanford_mrc_update_configs(TRUE, $configs, $path);
}
