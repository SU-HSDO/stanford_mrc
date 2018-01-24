<?php

namespace Drupal\mrc_yearonly\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\views\ViewExecutable;

/**
 * Filters by academic year.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("yearonly")
 */
class YearOnly extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Allowed node titles');
    $this->operator = '=';
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['academic'] = FALSE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    if ($exposed = $form_state->get('exposed')) {
      return;
    }

    $form['academic'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Academic Year options'),
      '#default_value' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function showExposeForm(&$form, FormStateInterface $form_state) {
    parent::showExposeForm($form, $form_state);
    $exposed = $form_state->get('exposed');

    $form['value'] = [
      '#type' => 'number',
      '#title' => !$exposed ? $this->t('Value') : '',
      '#size' => 4,
      '#default_value' => !$exposed ? $this->value : '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if ($this->isExposed()) {
      return $this->t('exposed');
    }
    return parent::adminSummary();
  }

  public function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'select',
      '#title' => 'value',
      '#options' => $this->getYears(),
    ];
  }

  /**
   * @param string $sort
   * @param bool $academic
   *
   * @return mixed
   */
  protected function getYears($sort = 'desc', $academic = FALSE) {
    $query = \Drupal::database()
      ->select($this->table, 't')
      ->fields('t', [$this->realField])
      ->distinct()
      ->orderBy($this->realField, $sort)
      ->execute();
    $years = $query->fetchAllKeyed(0, 0);
    if ($academic) {
      foreach ($years as &$year) {
        $year = ($year - 1) . " - $year";
      }
    }
    return $years;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();
  }

}
