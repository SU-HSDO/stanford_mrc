(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.hoverMenu = {
    attach: function (context, settings) {

      $(".decanter-main-menu--hover-reveal a").focus(function () {
        $(this).siblings('ul').slideToggle("fast");
      }).blur(function () {
        setTimeout(function () {
          var list = $(e.currentTarget).siblings('ul');
          if ($(list).find('a:focus') === 0) {
            $(list).slideToggle("fast");
          }
        }, 100);
      });
    }
  };
})(jQuery, Drupal);
