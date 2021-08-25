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

if (!defined('DC_CONTEXT_ADMIN')){return;}

# Check user perms
dcPage::check('admin');

# Settings
$s = $core->blog->settings->kUtRL;

# Default values
$show_filters = false;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$section = isset($_REQUEST['section']) ? $_REQUEST['section'] : '';
$img_green = '<img src="images/check-on.png" alt="ok" />';
$img_red = '<img src="images/check-off.png" alt="fail" />';

$header = 
dcPage::jsLoad('index.php?pf=kUtRL/js/main.js').
'<script type="text/javascript">'."\n//<![CDATA[\n".
"jcToolsBox.prototype.text_wait = '".html::escapeJS(__('Please wait'))."';\n".
"\n//]]>\n</script>\n".
'<style type="text/css">
.titleKutrl { margin: -20px; text-align:center; } 
.titleKutrl a { border:none; text-decoration: none; } 
</style>';

$footer = '<hr class="clear"/><p class="right">
<a class="button" href="'.$p_url.'&amp;part=setting" title="'.__('Configure extension').'">'.__('Settings').'</a> - 
<a class="button" href="'.$p_url.'&amp;part=service" title="'.__('Configure services').'">'.__('Services').'</a> - 
kUtRL - '.$core->plugins->moduleInfo('kUtRL','version').'&nbsp;
<img alt="'.__('kUtRL').'" src="index.php?pf=kUtRL/icon.png" />
</p>
 <h2 class="titleKutrl"><a title="kUtRL, '.__('Links shortener').' | http://kutrl.fr" href="http://kutrl.fr">
 <img alt="kUtRL, '.__('Links shortener').' | http://kutrl.fr" src="index.php?pf=kUtRL/inc/img/kutrl_logo.png" />
 </a></h2>
';

# Messages
$msg = isset($_REQUEST['msg']) ? $_REQUEST['msg'] : '';
$msg_list = array(
	'savesetting' => __('Configuration successfully saved'),
	'saveservice' => __('Services successfully updated'),
	'createlink' => __('Link successfully shorten'),
	'deletelinks' => __('Links successfully deleted')
);
if (isset($msg_list[$msg])) {
	$msg = sprintf('<p class="message">%s</p>',$msg_list[$msg]);
}

# Pages
$start_part = $s->kutrl_active ? 'links' : 'setting';
$default_part = isset($_REQUEST['part']) ? $_REQUEST['part'] : $start_part;

if (!file_exists(dirname(__FILE__).'/inc/index.'.$default_part.'.php')) {
	$default_part = 'setting';
}
include dirname(__FILE__).'/inc/index.'.$default_part.'.php';

?>