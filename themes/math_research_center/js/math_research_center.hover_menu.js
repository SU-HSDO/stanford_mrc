(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.mrcHoverMenu = {
    attach: function (context, settings) {
      $('#header ul.decanter-nav-primary').menu();

      $('#header button.fa-bars').click(function () {
        $(this).siblings('ul').slideToggle('slow');
      });
      $('#header button.fa-plus').click(function () {
        $(this).siblings('ul').slideToggle('slow');
        $(this).toggleClass('fa-plus').toggleClass('fa-minus');
      })
    }
  };
})(jQuery, Drupal);
