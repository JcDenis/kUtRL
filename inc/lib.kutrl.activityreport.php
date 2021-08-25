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

# This file is used with plugin activityReport

if (!defined('DC_RC_PATH')){return;}

$core->activityReport->addGroup('kutrl',__('Plugin kUtRL'));

# from BEHAVIOR kutrlAfterCreateShortUrl in kUtRL/inc/lib.kutrl.srv.php
$core->activityReport->addAction(
	'kutrl',
	'create',
	__('Short link creation'),
	__('New short link of type "%s" and hash "%s" was created.'),
	'kutrlAfterCreateShortUrl',
	array('kutrlActivityReportBehaviors','kutrlCreate')
);

class kutrlActivityReportBehaviors
{
	public static function kutrlCreate($rs)
	{
		$logs = array($rs->type,$rs->hash);

		$GLOBALS['core']->activityReport->addLog('kutrl','create',$logs);
	}
}
?>