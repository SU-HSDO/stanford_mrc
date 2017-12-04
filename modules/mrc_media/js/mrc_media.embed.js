(function ($, window, Drupal) {
  'use strict';

  Drupal.behaviors.mrcMediaEmbed = {
    attach: function attach() {

      // Disable the submit button until after the ajax is done and the entity
      // form is displayed.
      if ($('#entity').html() == '') {
        $('input.is-entity-browser-submit').prop('disabled', true);
      }
      else {
        $('input.is-entity-browser-submit').prop('disabled', false);
      }
    }
  };
})(jQuery, window, Drupal);
