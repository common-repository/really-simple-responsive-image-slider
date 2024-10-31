jQuery(document).ready(function($) {
  $('#rsris-search-field').keyup(function() {
      var f = $(this).val();
      var regex = new RegExp(f, 'gi');

      $('.rsris-items .rsris-item').fadeOut()
        .each(function() {
            if($(this).html().match(regex)) {
                $(this).stop().show();
            }
        });
  });

  // Appends the clicked option to the selected options element
  $(document).on('click', '.rsris-items .rsris-item', function(e){
    var s = $(this);
    var sid = s.attr('data-rsris-item-id');
    s.append('<input type="hidden" name="rsris_slide[]" value="'+sid+'" /><span class="rsris-remove">x</span>');
    $('.rsris-items-selected').append(s);
  });

  $(document).on('click', '.rsris-remove', function(r){
    var r = $(this).parent();
    r.find('input').remove();
    r.find('.rsris-remove').remove();
    $('.rsris-items').append(r);
  });
});