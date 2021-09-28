$(function(){
  $('#form-entries .checkboxes-helpers').each(function () {
    dotclear.checkboxesHelpers(this, undefined, '#form-entries td input[type=checkbox]', '#form-entries #do-action');
  });
  $('#form-entries td input[type=checkbox]').enableShiftClick();
  dotclear.condSubmit('#form-entries td input[type=checkbox]', '#form-entries #do-action');
});