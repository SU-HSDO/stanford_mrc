<?php

namespace Drupal\mrc_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\inline_entity_form\ElementSubmit;
use Drupal\media\MediaInterface;

/**
 * An Entity Browser widget for creating media entities from embed codes.
 *
 * @EntityBrowserWidget(
 *   id = "embed_code",
 *   label = @Translation("Embed Code"),
 *   description = @Translation("Create media entities from embed codes."),
 * )
 */
class EmbedCode extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    if (isset($form['widget']['entity']['#entity'])) {
      return [
        $form['widget']['entity']['#entity'],
      ];
    }
    else {
      return [];
    }
  }

  public function getEntityForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    if (isset($form['actions'])) {
      $form['actions']['#weight'] = 100;
    }

    $form['entity'] = [
      '#prefix' => '<div id="entity">',
      '#suffix' => '</div>',
      '#weight' => 99,
    ];

    $value = $this->getInputValue($form_state);
    if (empty($value)) {
      $form['entity']['#markup'] = NULL;
      return $form;
    }

    try {
      $entity = $this->createFromInput($value, $this->getAllowedBundles($form_state));
    }
    catch (\Exception $e) {
      return $form;
    }

    $form['entity'] += [
      '#type' => 'inline_entity_form',
      '#entity_type' => $entity->getEntityTypeId(),
      '#bundle' => $entity->bundle(),
      '#default_value' => $entity,
      '#form_mode' => $this->configuration['form_mode'],
    ];
    // Without this, IEF won't know where to hook into the widget. Don't pass
    // $original_form as the second argument to addCallback(), because it's not
    // just the entity browser part of the form, not the actual complete form.
    ElementSubmit::addCallback($form['actions']['submit'], $form_state->getCompleteForm());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = $this->getEntityForm($original_form, $form_state, $additional_widget_parameters);

    $form['input'] = [
      '#type' => 'textarea',
      '#placeholder' => $this->t('Enter a URL...'),
      '#attributes' => [
        'class' => ['keyup-change'],
      ],
      '#ajax' => [
        'event' => 'change',
        'wrapper' => 'entity',
        'method' => 'html',
        'callback' => [static::class, 'ajax'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    $value = trim($this->getInputValue($form_state));

    if ($value) {
      try {
        $this->getBundleFromInput($value, TRUE, $this->getAllowedBundles($form_state));
      }
      catch (\Exception $e) {
        $form_state->setError($form['widget'], $e->getMessage());
      }
    }
    else {
      $form_state->setError($form['widget'], $this->t('You must enter a URL or embed code.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    // IEF will take care of creating the entity upon submission. All we need to
    // do is send it upstream to Entity Browser.
    $entity = $form['widget']['entity']['#entity'];
    $this->selectEntities([$entity], $form_state);
  }

  /**
   * AJAX callback. Returns the rebuilt inline entity form.
   *
   * @param array $form
   *   The complete form.
   * @param FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public static function ajax(array &$form, FormStateInterface $form_state) {
    return (new AjaxResponse())
      ->addCommand(
        new ReplaceCommand('#entity', $form['widget']['entity'])
      );
  }

  /**
   * Returns the current input value, if any.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return mixed
   *   The input value, ready for further processing. Nothing will be done with
   *   the value if it's empty.
   */
  protected function getInputValue(FormStateInterface $form_state) {
    return $form_state->getValue('input');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['form_mode'] = 'media_browser';
    return $configuration;
  }

  /**
   * Creates a media entity from an input value.
   *
   * @param mixed $value
   *   The input value.
   * @param string[] $bundles
   *   (optional) A set of media bundle IDs which might match the input value.
   *   If omitted, all bundles to which the user has create access are checked.
   *
   * @return \Drupal\media\MediaInterface
   *   The unsaved media entity.
   */
  public function createFromInput($value, array $bundles = []) {
    /** @var \Drupal\media\MediaInterface $entity */
    $entity = $this->entityTypeManager
      ->getStorage('media')
      ->create([
        'bundle' => $this->getBundleFromInput($value, TRUE, $bundles)->id(),
      ]);

    $field = static::getSourceField($entity);
    if ($field) {
      $field->setValue($value);
    }
    return $entity;
  }

  /**
   * Returns the media entity's source field item list.
   *
   * @param \Drupal\media\MediaInterface $entity
   *   The media entity.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   *   The media entity's source field item list, or NULL if the media type
   *   plugin does not define a source field.
   */
  public static function getSourceField(MediaInterface $entity) {
    $field = $entity->getSource()
      ->getSourceFieldDefinition($entity->bundle->entity);

    return $field ? $entity->get($field->getName()) : NULL;
  }

  /**
   * Returns the bundles that this widget may use.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return string[]
   *   The bundles that this widget may use. If all bundles may be used, the
   *   returned array will be empty.
   */
  protected function getAllowedBundles(FormStateInterface $form_state) {
    return (array) $form_state->get([
      'entity_browser',
      'widget_context',
      'target_bundles',
    ]);
  }

  /**
   * Returns the first media bundle that can accept an input value.
   *
   * @param mixed $value
   *   The input value.
   * @param bool $check_access
   *   (optional) Whether to filter the bundles by create access for the current
   *   user. Defaults to TRUE.
   * @param string[] $bundles
   *   (optional) A set of media bundle IDs which might match the input. If
   *   omitted, all available bundles are checked.
   *
   * @return \Drupal\media\MediaTypeInterface
   *   A media bundle that can accept the input value.
   *
   * @throws \Exception if no bundle can be matched to the input value.
   */
  public function getBundleFromInput($value, $check_access = TRUE, array $bundles = []) {
    // Lightning Media overrides the media_bundle storage handler with a special
    // one that adds an optional second parameter to loadMultiple().
    $media_types = $this->entityTypeManager
      ->getStorage('media_type')
      ->loadMultiple($bundles ?: NULL, $check_access);
    ksort($media_types);

    /** @var \Drupal\media\MediaTypeInterface $media_type */
    foreach ($media_types as $type_id => $media_type) {
      $source = $media_type->getSource();
      //      if ($source instanceof InputMatchInterface && $source->appliesTo($value, $media_type)) {
      if ($type_id == 'video') {
        return $media_type;
      }
      //      }
    }
    throw new \Exception($value);
  }

}
