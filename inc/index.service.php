<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of kUtRL, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2021 Jean-Christian Denis and contributors
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

# This file manage services of kUtRL (called from index.php)

if (!defined('DC_CONTEXT_ADMIN')) {
	return null;
}

# Save services settings
if ($action == 'saveservice') {
	try {
		foreach(kutrl::getServices($core) as $service_id => $service) {
			$o = new $service($core);
			$o->saveSettings();
		}
		$core->blog->triggerBlog();
		http::redirect($p_url . '&part=service&section=' . $section . '&msg=' . $action);
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

echo '
<html>
<head><title>kUtRL, ' . __('Links shortener') . '</title>' . $header . 
dcPage::jsLoad('index.php?pf=kUtRL/js/service.js') . 
"<script type=\"text/javascript\">\n//<![CDATA[\n" . 
dcPage::jsVar('jcToolsBox.prototype.section', $section) . 
"\n//]]>\n</script>\n" . 
'</head>
<body>
<h2>kUtRL' . 
' &rsaquo; <a href="' . $p_url . '&amp;part=links">' . __('Links') . '</a>' . 
' &rsaquo; ' . __('Services') . 
' - <a class="button" href="' . $p_url . '&amp;part=link">' . __('New link') . '</a>' . 
'</h2>' . $msg . '
<form id="service-form" method="post" action="' . $p_url . '">';

foreach(kutrl::getServices($core) as $service_id => $service) {
	$o = new $service($core);
	
	echo '<fieldset id="setting-' . $service_id . '"><legend>' . $o->name . '</legend>';

	if (!empty($msg)) {
		echo '<p><em>' . (
		$o->testService() ?
			$img_green . ' ' . sprintf(__('%s API is well configured and runing.'), $o->name) :
			$img_red . ' ' . sprintf(__('Failed to test %s API.'), $o->name)
		) . '</em></p>';
		//if ($o->error->flag()) {
			echo $o->error->toHTML();
		//}
	}
	if (!empty($o->home)) {
		echo '<p><a title="' . __('homepage') . '" href="' . $o->home . '">' . sprintf(__('Learn more about %s.'), $o->name) . '</a></p>';
	}
	$o->settingsForm();

	echo '</fieldset>';
}

echo '
<div class="clear">
<p><input type="submit" name="save" value="' . __('save') . '" />' . 
$core->formNonce() . 
form::hidden(['p'], 'kUtRL') . 
form::hidden(['part'], 'service') . 
form::hidden(['action'], 'saveservice') . 
form::hidden(['section'], $section) . '
</p></div>
</form>';
dcPage::helpBlock('kUtRL');
echo $footer . '</body></html>';