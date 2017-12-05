(function ($, window, Drupal) {
  'use strict';

  Drupal.behaviors.mrcEventsAdmin = {
    attach: function attach() {
      $('input.show-end-date[type="checkbox"]').each(function () {
        if ($(this).is(':checked')) {
          $(this).parent().prev().show().prev().show();
        }
        else {
          $(this).parent().prev().hide().prev().hide();
        }
      }).change(function () {
        if ($(this).is(':checked')) {
          $(this).parent().prev().show().prev().show();
        }
        else {
          $(this).parent().prev().hide().prev().hide();
        }
      })
    }
  };
})(jQuery, window, Drupal);
