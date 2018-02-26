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
class MediaResponsiveImageFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'image_style' => NULL,
        'link' => 0,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['image_style'] = [
      '#type' => 'select',
      '#options' => $this->getResponsiveStyles(),
      '#title' => t('Image Style'),
      '#default_value' => $this->getSetting('image_style') ?: '',
      '#empty_option' => $this->t('Use Entity Display'),
    ];
    $elements['link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link Media to Parent'),
      '#default_value' => $this->getSetting('link'),
    ];
    return $elements;
  }

  /**
   * Get available responsive image styles.
   *
   * @return array
   *   Keyed array of image styles.
   */
  protected function getResponsiveStyles() {
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
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $is_applicable = parent::isApplicable($field_definition);
    $target_type = $field_definition->getFieldStorageDefinition()
      ->getSetting('target_type');
    return $is_applicable && $target_type == 'media';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $image_styles = $this->getResponsiveStyles();
    $summary = parent::settingsSummary();

    unset($image_styles['']);
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t('Responsive style: @style', ['@style' => $image_styles[$image_style_setting]]);
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
    $image_styles = $this->getResponsiveStyles();
    $style = $this->getSetting('image_style');

    if (empty($style) || !isset($image_styles[$style])) {
      return $elements;
    }

    /** @var \Drupal\Core\Entity\EntityInterface $parent */
    $parent = $items->getParent()->getValue();

    foreach ($elements as &$element) {
      $element['#mrc_media_responsive_image_style'] = $style;

      if ($this->getSetting('link')) {
        $element['#mrc_media_url'] = $parent->toUrl();
      }
    }
    return $elements;
  }

  /**
   * @param $element
   */
  public static function alterMediaRender(&$element) {
    $element['content']['field_media_image']['#formatter'] = 'responsive_image';

    foreach (Element::children($element['content']['field_media_image']) as $delta) {
      $item = &$element['content']['field_media_image'][$delta];
      $item['#theme'] = 'responsive_image_formatter';
      $item['#responsive_image_style_id'] = $element['elements']['#mrc_media_responsive_image_style'];

      if (isset($element['elements']['#mrc_media_url'])) {
        $item['#url'] = $element['elements']['#mrc_media_url'];
      }
    }
  }

}
