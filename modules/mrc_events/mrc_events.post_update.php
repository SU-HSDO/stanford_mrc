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
