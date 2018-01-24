<?php

namespace Drupal\mrc_yearonly\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'yearonly_academic' formatter.
 *
 * @FieldFormatter (
 *   id = "yearonly_academic",
 *   label = @Translation("Academic Year"),
 *   field_types = {
 *     "yearonly"
 *   }
 * )
 */
class AcademicYearOnly extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#theme' => 'yearonly_academic',
        '#start_year' => $item->value - 1,
        '#end_year' => $item->value,
      ];
    }

    return $element;
  }

}
