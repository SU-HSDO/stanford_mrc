<?php

/**
 * Release 8.0.4 changes.
 */
function stanford_mrc_post_update_8_0_4() {
  $modules = ['mrc_paragraphs_webform', 'views_block_filter_block'];
  \Drupal::service('module_installer')->install($modules);

  module_load_install('stanford_mrc');
  $path = drupal_get_path('module', 'mrc_events') . '/config/install';
  stanford_mrc_update_configs(TRUE, ['views.view.mrc_events'], $path);
}

/**
 * Release 8.0.5 changes.
 */
function stanford_mrc_post_update_8_0_5() {
  \Drupal::service('module_installer')->install(['focal_point']);
  module_load_install('stanford_mrc');

  $configs = [
    'mrc_events' => ['views.view.mrc_videos'],
    'mrc_helper' => ['core.entity_view_display.taxonomy_term.mrc_event_series.default'],
    'mrc_news' => [
      'core.entity_form_display.node.stanford_news_item.default',
      'views.view.mrc_news',
    ],
    'mrc_visitor' => [
      'views.view.mrc_visitor',
      'field.field.node.stanford_visitor.field_s_visitor_photo',
    ],
    'mrc_paragraphs_slide' => ['core.entity_view_display.paragraph.mrc_slide.default'],
    'mrc_media' => [
      'image.style.event',
      'image.style.event_series',
      'image.style.hero_banner',
      'image.style.large',
      'image.style.linkit_result_thumbnail',
      'image.style.medium',
      'image.style.mrc_news_thumbnail',
      'image.style.news',
      'image.style.slideshow',
      'image.style.spotlight',
      'image.style.thumbnail',
    ],
  ];

  foreach ($configs as $module => $config) {
    $path = drupal_get_path('module', $module) . '/config/install';
    stanford_mrc_update_configs(TRUE, $config, $path);
  }
}

/**
 * Release 8.0.6 changes.
 */
function stanford_mrc_post_update_8_0_6() {
  module_load_install('stanford_mrc');

  $configs = [
    'mrc_news' => [
      'image.style.mrc_news_thumbnail',
    ],
    'mrc_media' => [
      'image.style.event',
      'image.style.linkit_result_thumbnail',
      'image.style.mrc_news_thumbnail',
      'image.style.news',
      'image.style.slideshow',
      'image.style.spotlight',
    ],
  ];

  foreach ($configs as $module => $config) {
    $path = drupal_get_path('module', $module) . '/config/install';
    stanford_mrc_update_configs(TRUE, $config, $path);
  }
}
