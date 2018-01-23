(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.hoverMenu = {
    attach: function (context, settings) {

      // Slight delay to close the menu item.
      $('.decanter-nav-primary li').mouseout(function () {
        var childMenu = $(this).children('ul');
        $(childMenu).addClass('focused');

        setTimeout(function () {
          $(childMenu).removeClass('focused');
        }, 500);
      });

      // Click/keypress to expand and collapse the menu.
      $('button.show-hide-submenu').click(function () {
        if ($(this).hasClass('fa-plus')) {
          $(this).removeClass('fa-plus').addClass('fa-minus');
          var text = $(this).find('.visually-hidden').text();
          $(this).find('.visually-hidden').text(text.replace('Show', 'Hide'));
          $(this).siblings('ul').addClass('focused');
          $(this).siblings('a').attr('aria-expanded', 'true');
        }
        else {
          $(this).removeClass('fa-minus').addClass('fa-plus');
          var text = $(this).find('.visually-hidden').text();
          $(this).find('.visually-hidden').text(text.replace('Hide', 'Show'));
          $(this).siblings('ul').removeClass('focused');
          $(this).siblings('a').attr('aria-expanded', 'false');
        }
      });


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
