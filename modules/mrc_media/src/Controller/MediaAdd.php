<?php

namespace Drupal\mrc_media\Controller;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\mrc_media\BundleSuggestion;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MediaAdd extends EntityController {

  /**
   * @var \Drupal\mrc_media\BundleSuggestion
   */
  protected $bundleSuggestion;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('renderer'),
      $container->get('string_translation'),
      $container->get('url_generator'),
      $container->get('mrc_media.bundle_suggestion')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityRepositoryInterface $entity_repository, RendererInterface $renderer, TranslationInterface $string_translation, UrlGeneratorInterface $url_generator, BundleSuggestion $bundle_suggestion) {
    parent::__construct($entity_type_manager, $entity_type_bundle_info, $entity_repository, $renderer, $string_translation, $url_generator);
    $this->bundleSuggestion = $bundle_suggestion;
  }

  /**
   * {@inheritdoc}
   */
  public function addPage($entity_type_id) {
    $page = parent::addPage($entity_type_id);
    $bulk_bundles = [];

    foreach ($this->bundleSuggestion->getUploadBundles() as $media_type) {
      unset($page['#bundles'][$media_type->id()]);
      $bulk_bundles[] = $media_type->label();
    }

    // No media bundles that allow upload.
    if (empty($bulk_bundles)) {
      return $page;
    }

    // Add a bulk upload for all bundles with upload ability.
    $url = new Url('mrc_media.bulk_upload');
    $page['#bundles']['bulk'] = [
      'label' => $this->t('Bulk Upload'),
      'description' => $this->t('Create %bundles.', ['%bundles' => implode(', ', $bulk_bundles)]),
      'add_link' => new Link($this->t('Bulk Upload'), $url),
    ];

    return $page;
  }

}
