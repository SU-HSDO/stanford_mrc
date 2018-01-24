(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.hoverMenu = {
    attach: function (context, settings) {
      $('ul.decanter-nav-primary').menu();
    }
  };
})(jQuery, Drupal);
