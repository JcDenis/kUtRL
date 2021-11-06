<?php
/**
 * @brief kUtRL, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis and contributors
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
# This file is used with plugin activityReport

if (!defined('DC_RC_PATH')) {
    return null;
}

$core->activityReport->addGroup('kutrl', __('Plugin kUtRL'));

# from BEHAVIOR kutrlAfterCreateShortUrl in kUtRL/inc/lib.kutrl.srv.php
$core->activityReport->addAction(
    'kutrl',
    'create',
    __('Short link creation'),
    __('New short link of type "%s" and hash "%s" was created.'),
    'kutrlAfterCreateShortUrl',
    ['kutrlActivityReportBehaviors', 'kutrlCreate']
);

class kutrlActivityReportBehaviors
{
    public static function kutrlCreate($rs)
    {
        $logs = [$rs->type,$rs->hash];

        $GLOBALS['core']->activityReport->addLog('kutrl', 'create', $logs);
    }
}
