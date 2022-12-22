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

dcCore::app()->__get('activityReport')->addGroup('kutrl', __('Plugin kUtRL'));

# from BEHAVIOR kutrlAfterCreateShortUrl in kUtRL/inc/lib.kutrl.srv.php
dcCore::app()->__get('activityReport')->addAction(
    'kutrl',
    'create',
    __('Short link creation'),
    __('New short link of type "%s" and hash "%s" was created.'),
    'kutrlAfterCreateShortUrl',
    function ($rs) {
        dcCore::app()->__get('activityReport')->addLog('kutrl', 'create', [$rs->type, $rs->hash]);
    }
);
