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

if (!defined('DC_RC_PATH')) {
    return;
}
if (version_compare(str_replace("-r", "-p", DC_VERSION), '2.2-alpha', '<')) {
    return;
}

global $__autoload, $core;

# Set a URL shortener for quick get request
if (!defined('SHORTEN_SERVICE_NAME')) {
    define('SHORTEN_SERVICE_NAME', 'Is.gd');
}
if (!defined('SHORTEN_SERVICE_API')) {
    define('SHORTEN_SERVICE_API', 'http://is.gd/api.php?');
}
if (!defined('SHORTEN_SERVICE_BASE')) {
    define('SHORTEN_SERVICE_BASE', 'http://is.gd/');
}
if (!defined('SHORTEN_SERVICE_PARAM')) {
    define('SHORTEN_SERVICE_PARAM', 'longurl');
}
if (!defined('SHORTEN_SERVICE_ENCODE')) {
    define('SHORTEN_SERVICE_ENCODE', false);
}

# Main class
$d = dirname(__FILE__) . '/inc/';
$__autoload['kutrl'] = $d . 'class.kutrl.php';
$__autoload['kutrlService'] = $d . 'lib.kutrl.srv.php';
$__autoload['kutrlLog'] = $d . 'lib.kutrl.log.php';
$__autoload['kutrlLinksList'] = $d . 'lib.kutrl.lst.php';

# Services
$__autoload['bilbolinksKutrlService'] = $d . 'services/class.bilbolinks.service.php';
$core->addBehavior('kutrlService', function() { return ["bilbolinks","bilbolinksKutrlService"]; } );
//$__autoload['bitlyKutrlService'] = $d . 'services/class.bitly.service.php';
//$core->addBehavior('kutrlService', function() { return ["bitly","bitlyKutrlService"]; } );
$__autoload['customKutrlService'] = $d . 'services/class.custom.service.php';
$core->addBehavior('kutrlService', function() { return ["custom","customKutrlService"]; } );
$__autoload['defaultKutrlService'] = $d . 'services/class.default.service.php';
$core->addBehavior('kutrlService', function() { return ["default","defaultKutrlService"]; } );
//$__autoload['googlKutrlService'] = $d . 'services/class.googl.service.php';
//$core->addBehavior('kutrlService', function() { return ["googl","googlKutrlService"]; } );
$__autoload['isgdKutrlService'] = $d . 'services/class.isgd.service.php';
$core->addBehavior('kutrlService', function() { return ["isgd","isgdKutrlService"]; } );
$__autoload['localKutrlService'] = $d . 'services/class.local.service.php';
$core->addBehavior('kutrlService', function() { return ["local","localKutrlService"]; } );
//$__autoload['shorttoKutrlService'] = $d . 'services/class.shortto.service.php';
//$core->addBehavior('kutrlService', function() { return ["shortto","shorttoKutrlService"]; } );
//$__autoload['trimKutrlService'] = $d . 'services/class.trim.service.php';
//$core->addBehavior('kutrlService', function() { return ["trim","trimKutrlService"]; } );
$__autoload['yourlsKutrlService'] = $d . 'services/class.yourls.service.php';
$core->addBehavior('kutrlService', function() { return ["yourls","yourlsKutrlService"]; } );
//$__autoload['suprKutrlService'] = $d . 'services/class.supr.service.php';
//$core->addBehavior('kutrlService', function() { return ["supr","suprKutrlService"]; } );

# Shorten url passed through wiki functions
$__autoload['kutrlWiki'] = $d . 'lib.wiki.kutrl.php';
$core->addBehavior('coreInitWikiPost',['kutrlWiki','coreInitWiki']);
$core->addBehavior('coreInitWikiComment',['kutrlWiki','coreInitWiki']);
$core->addBehavior('coreInitWikiSimpleComment',['kutrlWiki','coreInitWiki']);

# Public page
$core->url->register('kutrl', 'go', '^go(/(.*?)|)$', ['urlKutrl', 'redirectUrl']);

# Add kUtRL events on plugin activityReport
if (defined('ACTIVITY_REPORT')) {
    require_once $d . 'lib.kutrl.activityreport.php';
}