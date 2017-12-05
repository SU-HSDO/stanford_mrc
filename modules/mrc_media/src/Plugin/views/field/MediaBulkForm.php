<?php

namespace Drupal\mrc_media\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Field handler to link entity usage entities.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("media_bulk_form")
 */
class MediaBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No content selected.');
  }

}
