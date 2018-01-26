<?php

/**
 * @file
 * mrc_page.post_update.php
 */

/**
 * Adds menu block to node display.
 */
function mrc_page_post_update_8_0_4() {
  \Drupal::service('module_installer')->install(['menu_block']);

  module_load_install('stanford_mrc');
  $path = drupal_get_path('module', 'mrc_page') . '/config/install';

  $configs = [
    'core.entity_view_display.node.stanford_basic_page.default',
  ];
  stanford_mrc_update_configs(TRUE, $configs, $path);
}