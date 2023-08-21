/*global $, dotclear */
'use strict';

$(() => {
  /* toogle admin form sidebar */
  $('#kUtRL h5').toggleWithLegend(
  $('#kUtRL').children().not('h5'),
    {user_pref:'dcx_kUtRL_admin_form_sidebar',legend_click:true}
  );
});