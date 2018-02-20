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
    'entity_browser.browser.file_browser',
    'entity_browser.browser.image_browser',
    'entity_browser.browser.video_browser',
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

  \Drupal::service('module_installer')->install(['focal_point']);
  $settings = [
    'preview:image_style' => 'medium',
    'offsets' => '50,50',
    'progress_indicator' => 'throbber',
    'preview_link' => FALSE,
  ];
  $config = $config_factory->getEditable('core.entity_form_display.media.image.default');
  $config->set('content.field_media_image.settings', $settings);
  $config->set('content.field_media_image.type', 'image_focal_point');
  $config->save();
}
