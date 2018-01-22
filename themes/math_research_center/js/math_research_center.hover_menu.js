(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.hoverMenu = {
    attach: function (context, settings) {
      $(".decanter-main-menu--hover-reveal li").hover(function () {
        $(this).children("ul").slideToggle("fast");
      });

      $(".decanter-main-menu--hover-reveal li a").focus(function () {
        $(this).parent().children("ul").slideToggle("fast");
      }).blur(menu_lost_focus);

      function menu_lost_focus(e) {
        setTimeout(function () {
          var lostParent = $(e.currentTarget).parent('li');
          if ($(lostParent).find('a:focus').length == 0) {
            $(lostParent).children('ul').slideToggle("fast");
          }
        }, 100);
      }

    }
  };
})(jQuery, Drupal);
