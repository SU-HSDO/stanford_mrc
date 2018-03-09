(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.mrcHoverMenu = {
    attach: function (context, settings) {
      $('#header ul.decanter-nav-primary').menu();

      $('#header button.fa-bars').click(function () {
        $(this).siblings('ul').toggleClass('expanded');
      });
      $('#header button.fa-plus').click(function () {
        $(this).siblings('ul').toggleClass('expanded');
        $(this).toggleClass('fa-plus').toggleClass('fa-minus');
      });

      $(window).resize(function () {
        if ($(window).width() > 600) {
          $('#header ul').each(function () {
            $(this).removeClass('expanded')
          });
          $('#header button.fa-minus').each(function () {
            $(this).toggleClass('fa-plus').toggleClass('fa-minus');
          })
        }
      });
    }
  };
})(jQuery, Drupal);
