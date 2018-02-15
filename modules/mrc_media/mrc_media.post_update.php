<?php

/**
 * @file
 * mrc_media.post_update.php
 */

/**
 * Revert the media browsers.
 */
function mrc_media_post_update_8_0_5(){
  $configs = [
    'entity_browser.browser.media_browser',
    'views.view.media_entity_browser',
  ];

  module_load_install('stanford_mrc');
  $path = drupal_get_path('module', 'mrc_media') . '/config/install';
  stanford_mrc_update_configs(TRUE, $configs, $path);
}