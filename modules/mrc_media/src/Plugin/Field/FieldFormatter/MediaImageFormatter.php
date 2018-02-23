<?php

namespace Drupal\mrc_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'yearonly_academic' formatter.
 *
 * @FieldFormatter (
 *   id = "media_image_formatter",
 *   label = @Translation("Media Image Style"),
 *   description = @Translation("Apply an image style to image media items."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MediaImageFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'image_style' => NULL,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $handler_settings = $this->fieldDefinition->getSetting('handler_settings');

    if (isset($handler_settings['target_bundles']) && !in_array('image', $handler_settings['target_bundles'])) {
      return $elements;
    }

    $elements['image_style'] = [
      '#type' => 'select',
      '#options' => image_style_options(FALSE),
      '#title' => t('Image Style'),
      '#default_value' => $this->getSetting('image_style') ?: '',
      '#empty_option' => $this->t('Use Entity Display'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $image_styles = image_style_options(FALSE);
    $summary = parent::settingsSummary();

    unset($image_styles['']);
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t('Image style: @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = t('Use Entity Display');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    foreach ($elements as &$element) {
      $element['#image_style'] = $this->getSetting('image_style');
    }
    return $elements;
  }

}
