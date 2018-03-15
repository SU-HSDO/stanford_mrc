<?php

namespace Drupal\mrc_media\Plugin\MediaEmbedDialog\VideoOption;

use Drupal\Core\Form\FormStateInterface;
use Drupal\mrc_media\VideoOptionBase;

/**
 * Changes embedded video media items.
 *
 * @VideoOption(
 *   id = "vimeo",
 *   provider = "vimeo"
 * )
 */
class Vimeo extends VideoOptionBase {

  public function getOptionForm(array $original_form, FormStateInterface $form_state) {
   return [];
  }

//  public function getOptions() {
//    return [
//      'autoplay' => $this->t('Autoplay'),
//      'cc_load_policy' => $this->t('Load Captions'),
//      'controls' => $this->t('Show Controls'),
//      'fs' => $this->t('Disable Full Screen'),
//      'loop' => $this->t('Loop Video'),
//      'modestbranding' => $this->t('Small Logo'),
//      'rel' => $this->t('Show related videos at the end'),
//      'showinfo' => $this->t('Hide video title'),
//    ];
//  }
//
//  public function getOptionLabel($option) {
//    return $this->getOptions()[$option];
//  }

}
