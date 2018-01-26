<?php

/**
 * @file
 * mrc_visitor.post_update.php
 */

/**
 * Changes field settings on visitor & reverts the view.
 */
function mrc_visitor_post_update_8_0_4() {
  $configs = [
    'views.view.mrc_visitor',
    'pathauto.pattern.mrc_visitors',
  ];

  \Drupal::service('module_installer')->install(['mrc_yearonly']);

  module_load_install('stanford_mrc');
  $path = drupal_get_path('module', 'mrc_visitor') . '/config/install';
  stanford_mrc_update_configs(TRUE, $configs, $path);

  /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
  $config_factory = \Drupal::configFactory();
  /** @var \Drupal\Core\Config\Config $config_entity */
  $config_entity = $config_factory->getEditable('core.entity_view_display.node.stanford_visitor.default');
  $config_entity->set('content.field_s_visitor_year_visited.type', 'yearonly_academic');
  $config_entity->set('content.field_s_visitor_year_visited.settings.order', 'asc');
  $config_entity->set('hidden.field_mrc_event_series', 'true');

  $config_entity->save();

    // Save the pathauto pattern so that it's uuids correct and it applies.
  /** @var \Drupal\pathauto\Entity\PathautoPattern $entity */
  $entity = \Drupal::entityTypeManager()
    ->getStorage('pathauto_pattern')
    ->load('mrc_visitors');
  if ($entity) {
    $entity->save();
  }

}
