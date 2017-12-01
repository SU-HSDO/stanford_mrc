<?php

namespace Drupal\mrc_media\Controller;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Link;
use Drupal\Core\Url;

class MediaAdd extends EntityController {

  public function addPage($entity_type_id) {
    $page = parent::addPage($entity_type_id);

    $url = new Url('mrc_media.bulk_upload');
    unset($page['#bundles']['file'], $page['#bundles']['image']);;

    $page['#bundles']['bulk'] = [
      'label' => $this->t('Bulk Upload'),
      'description' => $this->t('Upload multiple files/images at once'),
      'add_link' => new Link($this->t('Bulk Upload'), $url),
    ];

    return $page;
  }
}