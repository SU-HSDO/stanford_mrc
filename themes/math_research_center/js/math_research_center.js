(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.mathResearchCenter = {
    attach: function (context, settings) {
      $('#block-searchform input[type=search]').focus(function () {
        $(this).closest('form').addClass('expanded');
      }).blur(function () {
        var $this = $(this);
        setTimeout(function () {
          if (!$this.closest('form').find('input[type=submit]').is(':focus')) {
            $this.closest('form').removeClass('expanded');
          }
        }, 50);
      });
    }
  };
})(jQuery, Drupal);
