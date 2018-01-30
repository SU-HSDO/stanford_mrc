<?php

/**
 * Release 8.0.4 changes.
 */
function stanford_mrc_post_update_8_0_4() {
  \Drupal::service('module_installer')->install(['mrc_paragraphs_webform']);
}
