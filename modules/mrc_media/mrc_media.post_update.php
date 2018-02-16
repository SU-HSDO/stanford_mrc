<?php

/**
 * @file
 * mrc_media.post_update.php
 */

/**
 * Revert the media browsers.
 */
function mrc_media_post_update_8_0_6() {
  $configs = [
    'entity_browser.browser.media_browser',
    'views.view.media_entity_browser',
  ];

  module_load_install('stanford_mrc');
  $path = drupal_get_path('module', 'mrc_media') . '/config/install';
  stanford_mrc_update_configs(TRUE, $configs, $path);

  $config_factory = \Drupal::configFactory();
  $config = $config_factory->getEditable('embed.button.media_browser');
  $config->set('label', 'Embed Media');
  $config->save(TRUE);

  $config = $config_factory->getEditable('field.field.media.file.field_media_file');
  $config->set('settings.file_extensions', 'txt rtf doc docx ppt pptx xls xlsx pdf');
  $config->save();
}
