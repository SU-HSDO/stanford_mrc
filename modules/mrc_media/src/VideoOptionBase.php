<?php

namespace Drupal\mrc_media;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\PluginBase;

/**
 * Class VideoOptionBase
 *
 * @package Drupal\mrc_media
 */
abstract class VideoOptionBase extends PluginBase implements VideoOptionInterface {

  abstract function getOptionForm(array $original_form, FormStateInterface $form_state);
//
//  abstract function getOptions();
//
//  abstract function getOptionLabel($option);

}
