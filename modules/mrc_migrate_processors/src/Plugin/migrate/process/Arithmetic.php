<?php

namespace Drupal\mrc_migrate_processors\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Do some math formula on a numerical value.
 *
 * Available configuration keys:
 * - operation: Mathematical operation.
 * - fields:
 *
 * Examples:
 *
 * @code
 * process:
 *   plugin: arithmetic
 *   operation: +
 *   source: some_numerical_field
 *   fields:
 *     - another_numerical_field
 *     - constants/some_number
 * @endcode
 *
 * This will perform the mathematical operation on the source fields.
 *
 * @MigrateProcessPlugin(
 *   id = "arithmetic"
 * )
 */
class Arithmetic extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($this->configuration['operation']) || empty($this->configuration['fields'])) {
      return $value;
    }

    $fields = is_array($this->configuration['fields']) ? $this->configuration['fields'] : [$this->configuration['fields']];
    array_unshift($fields, $value);

    foreach ($fields as &$item) {
      if (is_string($item) && $row->hasSourceProperty($item)) {
        $item = $row->getSourceProperty($item);
      }
    }

    $formula = implode($this->configuration['operation'], $fields);
    return $this->calculateString($formula);
  }

  /**
   * Peform the basic math operation.
   *
   * @param string $math_string
   *   The forumla string.
   *
   * @return int
   *   Computed value.
   */
  protected function calculateString($math_string) {
    $math_string = trim($math_string);
    // Remove any non-numbers chars; exception for math operators.
    $math_string = preg_replace('[^0-9\+-\*\/\(\) ]', '', $math_string);
    $compute = create_function("", "return (" . $math_string . ");");
    return 0 + $compute();

  }

}
