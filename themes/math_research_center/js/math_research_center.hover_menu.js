(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.hoverMenu = {
    attach: function (context, settings) {

      $('.decanter-main-menu--hover-reveal a').focus(function () {
        $(this).siblings('ul').addClass('focused');
      }).blur(function (e) {

        // Need a short delay to let the focus change positions.
        setTimeout(function () {

          $('.decanter-main-menu--hover-reveal ul.decanter-nav-submenu').each(function () {
            if ($(this).find('a:focus').length === 0 && !$(this).siblings('a').is(':focus')) {
              $(this).removeClass('focused');
            }
          })
        }, 10);

      });
    }
  };
})(jQuery, Drupal);
