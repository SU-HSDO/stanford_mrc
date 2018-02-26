<?php

namespace Drupal\mrc_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * Plugin implementation of the 'yearonly_academic' formatter.
 *
 * @FieldFormatter (
 *   id = "media_responsive_image_formatter",
 *   label = @Translation("Media Responsive Image Style"),
 *   description = @Translation("Apply a responsive image style to image media
 *   items."), field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MediaResponsiveImageFormatter extends MediaFormatter {

  /**
   * Get available responsive image styles.
   *
   * @return array
   *   Keyed array of image styles.
   */
  protected function getStyleOptions() {
    $styles = [];
    /** @var ResponsiveImageStyle $style */
    foreach (ResponsiveImageStyle::loadMultiple() as $style) {
      $styles[$style->id()] = $style->label();
    }
    return $styles;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRender($element) {
    $element['field_media_image']['#formatter'] = 'responsive_image';
    foreach (Element::children($element['field_media_image']) as $delta) {
      $item = &$element['field_media_image'][$delta];
      $item['#theme'] = 'responsive_image_formatter';
      $item['#responsive_image_style_id'] = $element['#mrc_media_image_style'];

      if (isset($element['#mrc_media_url'])) {
        $item['#url'] = $element['#mrc_media_url'];
        $item['#attributes']['title'] = $element['#mrc_media_url_title'];
      }
    }

    return $element;
  }

}
