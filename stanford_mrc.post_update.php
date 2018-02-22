<?php

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

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
      'image.style.responsive_large',
      'image.style.responsive_medium',
      'image.style.responsive_small',
      'responsive_image.styles.hero_image',
    ],
  ];

  foreach ($configs as $module => $config) {
    $path = drupal_get_path('module', $module) . '/config/install';
    stanford_mrc_update_configs(TRUE, $config, $path);
  }
}

function stanford_mrc_post_update_8_0_61() {
  module_load_install('mrc_helper');

  /** @var \Drupal\config_update\ConfigReverter $config_update */
  $config_update = \Drupal::service('config_update.config_update');
  $config_factory = \Drupal::configFactory();

  /** @var \Drupal\field\Entity\FieldConfig $field_config */
  foreach (FieldConfig::loadMultiple() as $field_config) {
    $handler_settings = [
      'target_bundles' => [],
      'sort' => ['field' => '_none'],
      'auto_create' => FALSE,
      'auto_create_bundle' => '',
    ];

    switch ($field_config->getType()) {
      case 'image':
        $handler_settings['target_bundles']['image'] = 'image';
        $field_name = 'field_mrc_image';
        break;
      case 'file':
        $handler_settings['target_bundles']['file'] = 'file';
        $field_name = 'field_mrc_file';
        break;
      case 'video_embed_field':
        $handler_settings['target_bundles']['video'] = 'video';
        $field_name = 'field_mrc_video';
        break;
      default:
        continue 2;
    }

    $entity_type = $field_config->getTargetEntityTypeId();
    $bundle = $field_config->getTargetBundle();
    $label = $field_config->label();

    if ($entity_type == 'media') {
      continue;
    }

    mrc_helper_create_field($entity_type, $bundle, $field_name, 'entity_reference', $label, 1);
    $config_update->revert('field_storage_config', "$entity_type.$field_name");

    $new_field_config = FieldConfig::loadByName($entity_type, $bundle, $field_name);
    $new_field_config->setSetting('handler_settings', $handler_settings);
    $new_field_config->set('required', $field_config->isRequired());
    $new_field_config->save();

    $config_update->revert('field_config', "$entity_type.$bundle.$field_name");
//    $config_update->revert('entity_form_display', "$entity_type.$bundle.default");

    $config = $config_factory->getEditable("core.entity_form_display.$entity_type.$bundle.default");
    $form_settings = [
      'type' => 'entity_browser_entity_reference',
      'weight' => $config->get('content.' . $field_config->getName() . '.weight'),
      'region' => 'content',
      'settings' => [
        'entity_browser' => 'image_browser',
        'field_widget_display' => 'label',
        'field_widget_remove' => TRUE,
        'selection_mode' => 'selection_append',
        'field_widget_edit' => FALSE,
        'open' => 'false',
        'field_widget_display_settings' => [],
      ],
    ];

    $config->set("content.$field_name", $form_settings);
    $field_groups = $config->get('third_party_settings.field_group') ?: [];
    foreach ($field_groups as &$group) {
      $pos = array_search($field_config->getName(), $group['children']);
      if ($pos !== FALSE) {
        $group['children'][$pos] = $field_name;
      }
    }

    if (!empty($field_groups)) {
      $config->set('third_party_settings.field_group', $field_groups);
    }
    $config->clear('content.' . $field_config->getName());
    $config->save(TRUE);
  }

}