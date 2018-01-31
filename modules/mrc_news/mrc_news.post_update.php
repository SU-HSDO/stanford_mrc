<?php

/**
 * mrc_news.post_update.php
 */

/**
 * Revert the view.
 */
function mrc_news_post_update_8_0_4() {
  $configs = [
    'views.view.mrc_news',
  ];

  module_load_install('stanford_mrc');
  $path = drupal_get_path('module', 'mrc_news') . '/config/install';
  stanford_mrc_update_configs(TRUE, $configs, $path);
}
