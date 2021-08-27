<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of kUtRL, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2011 JC Denis and contributors
# jcdenis@gdwd.com
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

# This file manage settings of kUtRL (called from index.php)

if (!defined('DC_CONTEXT_ADMIN')) {
	return null;
}

$s_active = (boolean) $s->kutrl_active;
$s_plugin_service = (string) $s->kutrl_plugin_service;
$s_admin_service = (string) $s->kutrl_admin_service;
$s_tpl_service = (string) $s->kutrl_tpl_service;
$s_wiki_service = (string) $s->kutrl_wiki_service;
$s_allow_external_url = (boolean) $s->kutrl_allow_external_url;
$s_tpl_passive = (boolean) $s->kutrl_tpl_passive;
$s_tpl_active = (boolean) $s->kutrl_tpl_active;
$s_admin_entry_default = (string) $s->kutrl_admin_entry_default;

if ($default_part == 'setting' && $action == 'savesetting') {
	try {
		$s_active = !empty($_POST['s_active']);
		$s_admin_service = $_POST['s_admin_service'];
		$s_plugin_service = $_POST['s_plugin_service'];
		$s_tpl_service = $_POST['s_tpl_service'];
		$s_wiki_service = $_POST['s_wiki_service'];
		$s_allow_external_url = !empty($_POST['s_allow_external_url']);
		$s_tpl_passive = !empty($_POST['s_tpl_passive']);
		$s_tpl_active = !empty($_POST['s_tpl_active']);
		$s_admin_entry_default = !empty($_POST['s_admin_entry_default']);

		$s->put('kutrl_active', $s_active);
		$s->put('kutrl_plugin_service', $s_plugin_service);
		$s->put('kutrl_admin_service', $s_admin_service);
		$s->put('kutrl_tpl_service', $s_tpl_service);
		$s->put('kutrl_wiki_service', $s_wiki_service);
		$s->put('kutrl_allow_external_url', $s_allow_external_url);
		$s->put('kutrl_tpl_passive', $s_tpl_passive);
		$s->put('kutrl_tpl_active', $s_tpl_active);
		$s->put('kutrl_admin_entry_default', $s_admin_entry_default);

		$core->blog->triggerBlog();

		http::redirect($p_url . '&part=setting&msg=' . $action . '&section=' . $section);
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

$services_combo = [];
foreach(kutrl::getServices($core) as $service_id => $service) {
	$o = new $service($core);
	$services_combo[__($o->name)] = $o->id;
}
$ext_services_combo = array_merge([__('Disabled')=>''], $services_combo);
$lst_services_combo = array_merge(['-'=>''], $services_combo);

echo '
<html>
<head><title>kUtRL, ' . __('Links shortener') . '</title>' . $header . 
dcPage::jsLoad('index.php?pf=kUtRL/js/setting.js') . 
"<script type=\"text/javascript\">\n//<![CDATA[\n" . 
dcPage::jsVar('jcToolsBox.prototype.section', $section) . 
"\n//]]>\n</script>\n" . 
'</head>
<body>
<h2>kUtRL' . 
' &rsaquo; <a href="' . $p_url . '&amp;part=links">' . __('Links') . '</a>' . 
' &rsaquo; ' . __('Settings') . 
' - <a class="button" href="' . $p_url . '&amp;part=link">' . __('New link') . '</a>' . 
'</h2>' . $msg . '
<form id="setting-form" method="post" action="' . $p_url . '">

<fieldset id="setting-plugin"><legend>' .  __('Plugin activation') . '</legend>
<p><label class="classic">' . 
form::checkbox(['s_active'], '1', $s_active) . 
__('Enable plugin') . '</label></p>
</fieldset>

<fieldset id="setting-option"><legend>' .  __('General rules') . '</legend>
<p><label class="classic">' . 
form::checkbox(['s_allow_external_url'], '1', $s_allow_external_url) . 
__('Allow short link for external URL') . '</label></p>
<p class="form-note">' . __('Not only link started with this blog URL could be shortened.') . '</p>
<p><label class="classic">' . 
form::checkbox(['s_tpl_passive'], '1', $s_tpl_passive) . 
__('Passive mode') . '</label></p>
<p class="form-note">' . __('If this extension is disabled and the passive mode is enabled, "kutrl" tags (like EntryKurl) will display long urls instead of nothing on templates.') . '</p>
<p><label class="classic">' . 
form::checkbox(['s_tpl_active'], '1', $s_tpl_active) . 
__('Active mode') . '</label></p>
<p class="form-note">' . __('If the active mode is enabled, all know default template tags (like EntryURL) will display short urls instead of long ones on templates.') . '<br />' . 
__('You can disable URL shortening for a specific template tag by adding attribute disable_kutrl="1" to it . ') . '</p>
<p><label class="classic">' . 
form::checkbox(['s_admin_entry_default'], '1', $s_admin_entry_default) . 
__('Create short link for new entries') . '</label></p>
<p class="form-note">' . __('This can be changed on page of creation/edition of an entry.') . '</p>
</fieldset>

<fieldset id="setting-service"><legend>' .  __('Default services') . '</legend>
<p><label>';
if (!empty($msg)) {
	if (null !== ($o = kutrl::quickPlace($s_admin_service))) {
		echo $o->testService() ? $img_green : $img_red;
	}
}
echo '&nbsp;' . __('Administration:') . '<br />' . 
form::combo(['s_admin_service'], $services_combo, $s_admin_service) . '
</label></p>
<p class="form-note">' . __('Service to use in this admin page and on edit page of an entry.') . '</p>
<p><label>';
if (!empty($msg)) {
	if (null !== ($o = kutrl::quickPlace($s_plugin_service))) {
		echo $o->testService() ? $img_green : $img_red;
	}
}
echo '&nbsp;' . __('Extensions:') . '<br />' . 
form::combo(['s_plugin_service'], $services_combo, $s_plugin_service) . '
</label></p>
<p class="form-note">' . __('Service to use on third part plugins.') . '</p>
<p><label>';
if (!empty($msg)) {
	if (null !== ($o = kutrl::quickPlace($s_tpl_service))) {
		echo $o->testService() ? $img_green : $img_red;
	}
}
echo '&nbsp;' . __('Templates:') . '<br />' . 
form::combo(['s_tpl_service'], $ext_services_combo, $s_tpl_service) . '
</label></p>
<p class="form-note">' . __('Shorten links automatically when using template value like "EntryKutrl".') . '</p>
<p><label>';
if (!empty($msg)) {
	if (null !== ($o = kutrl::quickPlace($s_wiki_service))) {
		echo $o->testService() ? $img_green : $img_red;
	}
}
echo '&nbsp;' . __('Contents:') . '<br />' . 
form::combo(['s_wiki_service'], $ext_services_combo, $s_wiki_service) . '
</label></p>
<p class="form-note">' . __('Shorten links automatically found in contents using wiki synthax.') . '</p>
</fieldset>

<div class="clear">
<p><input type="submit" name="save" value="' . __('save') . '" />' . 
$core->formNonce() . 
form::hidden(['p'], 'kUtRL') . 
form::hidden(['part'], 'setting') . 
form::hidden(['action'], 'savesetting') . 
form::hidden(['section'], $section) . '
</p></div>
</form>';
dcPage::helpBlock('kUtRL');
echo $footer . '</body></html>';