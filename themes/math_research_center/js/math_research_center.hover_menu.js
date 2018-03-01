(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.mrcHoverMenu = {
    attach: function (context, settings) {
      $('#header ul.decanter-nav-primary').menu();
    }
  };
})(jQuery, Drupal);
