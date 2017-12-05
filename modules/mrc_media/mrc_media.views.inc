<?php

/**
 * @file
 * mrc_media.views.inc
 */

/**
 * Implements hook_views_data_alter().
 */
function mrc_media_views_data_alter(array &$data) {
  $data['entity_usage']['link'] = [
    'title' => t('Usage Link'),
    'help' => t('Link to the referencing entity.'),
    'field' => [
      'id' => 'entity_usage_link',
    ],
  ];
  $data['media']['media_bulk_form'] = [
    'title' => t('Media operations bulk form'),
    'help' => t('Add a form element that lets you run operations on multiple medias.'),
    'field' => [
      'id' => 'media_bulk_form',
    ],
  ];

  return $data;
}
