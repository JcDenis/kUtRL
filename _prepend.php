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

if (!defined('DC_RC_PATH')) {
    return;
}

# Set a URL shortener for quick get request
if (!defined('SHORTEN_SERVICE_NAME')) {
    define('SHORTEN_SERVICE_NAME', 'Is.gd');
}
if (!defined('SHORTEN_SERVICE_API')) {
    define('SHORTEN_SERVICE_API', 'https://is.gd/api.php?');
}
if (!defined('SHORTEN_SERVICE_BASE')) {
    define('SHORTEN_SERVICE_BASE', 'https://is.gd/');
}
if (!defined('SHORTEN_SERVICE_PARAM')) {
    define('SHORTEN_SERVICE_PARAM', 'longurl');
}
if (!defined('SHORTEN_SERVICE_ENCODE')) {
    define('SHORTEN_SERVICE_ENCODE', false);
}

# Main class
$d = dirname(__FILE__) . '/inc/';
$__autoload['kutrl']          = $d . 'class.kutrl.php';
$__autoload['kutrlService']   = $d . 'lib.kutrl.srv.php';
$__autoload['kutrlLog']       = $d . 'lib.kutrl.log.php';
$__autoload['kutrlLinksList'] = $d . 'lib.kutrl.lst.php';

# Services
$__autoload['defaultKutrlService'] = $d . 'services/class.default.service.php';
$core->addBehavior('kutrlService', function() { return ["default","defaultKutrlService"]; } );
if (!defined('SHORTEN_SERVICE_DISABLE_CUSTOM')) {
    $__autoload['customKutrlService'] = $d . 'services/class.custom.service.php';
    $core->addBehavior('kutrlService', function() { return ["custom","customKutrlService"]; } );
}
if (!defined('SHORTEN_SERVICE_DISABLE_LOCAL')) {
    $__autoload['localKutrlService'] = $d . 'services/class.local.service.php';
    $core->addBehavior('kutrlService', function() { return ["local","localKutrlService"]; } );
}
if (!defined('SHORTEN_SERVICE_DISABLE_BILBOLINKS')) {
    $__autoload['bilbolinksKutrlService'] = $d . 'services/class.bilbolinks.service.php';
    $core->addBehavior('kutrlService', function() { return ["bilbolinks","bilbolinksKutrlService"]; } );
}
if (!defined('SHORTEN_SERVICE_DISABLE_BITLY')) {
    $__autoload['bitlyKutrlService'] = $d . 'services/class.bitly.service.php';
    $core->addBehavior('kutrlService', function() { return ["bitly","bitlyKutrlService"]; } );
}
//if (!defined('SHORTEN_SERVICE_DISABLE_GOOGL')) {
//    $__autoload['googlKutrlService'] = $d . 'services/class.googl.service.php';
//    $core->addBehavior('kutrlService', function() { return ["googl","googlKutrlService"]; } );
//}
if (!defined('SHORTEN_SERVICE_DISABLE_ISGD')) {
    $__autoload['isgdKutrlService'] = $d . 'services/class.isgd.service.php';
    $core->addBehavior('kutrlService', function() { return ["isgd","isgdKutrlService"]; } );
}
//if (!defined('SHORTEN_SERVICE_DISABLE_SHORTTO')) {
//    $__autoload['shorttoKutrlService'] = $d . 'services/class.shortto.service.php';
//    $core->addBehavior('kutrlService', function() { return ["shortto","shorttoKutrlService"]; } );
//}
//if (!defined('SHORTEN_SERVICE_DISABLE_TRIM')) {
//    $__autoload['trimKutrlService'] = $d . 'services/class.trim.service.php';
//    $core->addBehavior('kutrlService', function() { return ["trim","trimKutrlService"]; } );
//}
if (!defined('SHORTEN_SERVICE_DISABLE_YOURLS')) {
    $__autoload['yourlsKutrlService'] = $d . 'services/class.yourls.service.php';
    $core->addBehavior('kutrlService', function() { return ["yourls","yourlsKutrlService"]; } );
}
//if (!defined('SHORTEN_SERVICE_DISABLE_SUPR')) {
//    $__autoload['suprKutrlService'] = $d . 'services/class.supr.service.php';
//    $core->addBehavior('kutrlService', function() { return ["supr","suprKutrlService"]; } );
//}

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