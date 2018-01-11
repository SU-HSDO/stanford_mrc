<?php

namespace Drupal\mrc_ds_blocks\Routing;

use Drupal\Core\ParamConverter\ParamConverterInterface;

/**
 * Parameter converter for upcasting fieldgroup config ids to fieldgroup object.
 */
class MrcDsBlocksConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, \Symfony\Component\Routing\Route $route) {
    return isset($definition['type']) && $definition['type'] == 'field_group';
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    return null;
//    $identifiers = explode('.', $value);
//    if (count($identifiers) != 5) {
//      return;
//    }
//
//    return field_group_load_field_group($identifiers[4], $identifiers[0], $identifiers[1], $identifiers[2], $identifiers[3]);
  }


}
