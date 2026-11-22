/*global $, dotclear */
'use strict';

$(() => {
  /* toogle admin form sidebar */
  const title = document.querySelector('#kUtRL h5');
  if (title) {
    dotclear.toggleWithLegend(title, document.querySelectorAll('#kUtRL > :not(h5)'), {
      user_pref: 'dcx_kUtRL_admin_form_sidebar',
      legend_click: true,
    });
  }
});