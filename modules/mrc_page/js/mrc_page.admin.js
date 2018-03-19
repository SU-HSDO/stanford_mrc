jQuery(document).ready(function($) {
  $('#edit-cancel').click(function(event){
    if(!confirm('Are you sure you want to cancel?')) {
      event.preventDefault();
    }
  });
});
