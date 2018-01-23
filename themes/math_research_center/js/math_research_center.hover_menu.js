(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.hoverMenu = {
    attach: function (context, settings) {

      $('.decanter-main-menu--hover-reveal a').focus(function () {
        // $(this).siblings('ul').addClass('focused');
        $(this).siblings('ul').css('visibility', 'visible').css('opacity', 1);
      }).blur(function (e) {

        // Need a short delay to let the focus change positions.
        setTimeout(function () {

          $('.decanter-main-menu--hover-reveal ul.decanter-nav-submenu').each(function () {
            if ($(this).find('a:focus').length === 0 && !$(this).siblings('a').is(':focus')) {
              // $(this).siblings('ul').removeClass('focused');
              $(this).css('visibility', 'hidden').css('opacity', 0);
            }
          })
        }, 100);

      });
    }
  };
})(jQuery, Drupal);
