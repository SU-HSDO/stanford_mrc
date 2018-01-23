(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.hoverMenu = {
    attach: function (context, settings) {

      $(".decanter-main-menu--hover-reveal a").focus(function () {
        $(this).siblings('ul').css('visibility', 'visible').css('opacity', 1);
      }).blur(function (e) {

        setTimeout(function () {
          var list = $(e.currentTarget).closest('ul');
          if ($(list).find('a:focus').length === 0 && !$(list).hasClass('decanter-nav-primary')) {
            $(list).css('visibility', 'hidden').css('opacity', 0);
          }
        }, 200);

      });
    }
  };
})(jQuery, Drupal);
