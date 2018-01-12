<?php
//
//namespace Drupal\mrc_ds_blocks\Routing;
//
//use Drupal\Core\ParamConverter\ParamConverterInterface;
//
///**
// * Parameter converter for upcasting fieldgroup config ids to fieldgroup object.
// */
//class MrcDsBlocksConverter implements ParamConverterInterface {
//
//  /**
//   * {@inheritdoc}
//   */
//  public function applies($definition, $name, \Symfony\Component\Routing\Route $route) {
//    return TRUE;
//    //    return isset($definition['type']) && $definition['type'] == 'field_group';
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function convert($value, $definition, $name, array $defaults) {
//    $block = mrc_ds_blocks_load_block($value, $defaults['entity_type_id'], 'stanford_visitor', $defaults['context'], 'default');
//
//    return $block;
//    //    return mrc_ds_blocks_load_block($value);
//    //    return $value;
//    //        kint($value);
//    //    $identifiers = explode('.', $value);
//    //    if (count($identifiers) != 5) {
//    //      return;
//    //    }
//    //
//    //    return mrc_ds_blocks_load_block($identifiers[4], $identifiers[0], $identifiers[1], $identifiers[2], $identifiers[3]);
//  }
//
//
//}
