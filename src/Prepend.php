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
$d = __DIR__ . '/inc/';
Clearbricks::lib()->autoload([
    'kUtRL'          => $d . 'class.kutrl.php',
    'kutrlService'   => $d . 'lib.kutrl.srv.php',
    'kutrlLog'       => $d . 'lib.kutrl.log.php',
    'kutrlLinkslist' => $d . 'lib.kutrl.lst.php',
]);

# Services
Clearbricks::lib()->autoload(['defaultKutrlService' => $d . 'services/class.default.service.php']);
dcCore::app()->addBehavior('kutrlService', function () { return ['default','defaultKutrlService']; });
if (!defined('SHORTEN_SERVICE_DISABLE_CUSTOM')) {
    Clearbricks::lib()->autoload(['customKutrlService' => $d . 'services/class.custom.service.php']);
    dcCore::app()->addBehavior('kutrlService', function () { return ['custom','customKutrlService']; });
}
if (!defined('SHORTEN_SERVICE_DISABLE_LOCAL')) {
    Clearbricks::lib()->autoload(['localKutrlService' => $d . 'services/class.local.service.php']);
    dcCore::app()->addBehavior('kutrlService', function () { return ['local','localKutrlService']; });
}
if (!defined('SHORTEN_SERVICE_DISABLE_BILBOLINKS')) {
    Clearbricks::lib()->autoload(['bilbolinksKutrlService' => $d . 'services/class.bilbolinks.service.php']);
    dcCore::app()->addBehavior('kutrlService', function () { return ['bilbolinks','bilbolinksKutrlService']; });
}
if (!defined('SHORTEN_SERVICE_DISABLE_BITLY')) {
    Clearbricks::lib()->autoload(['bitlyKutrlService' => $d . 'services/class.bitly.service.php']);
    dcCore::app()->addBehavior('kutrlService', function () { return ['bitly','bitlyKutrlService']; });
}
//if (!defined('SHORTEN_SERVICE_DISABLE_GOOGL')) {
//    Clearbricks::lib()->autoload(['googlKutrlService' => $d . 'services/class.googl.service.php']);
//    dcCore::app()->addBehavior('kutrlService', function() { return ["googl","googlKutrlService"]; } );
//}
if (!defined('SHORTEN_SERVICE_DISABLE_ISGD')) {
    Clearbricks::lib()->autoload(['isgdKutrlService' => $d . 'services/class.isgd.service.php']);
    dcCore::app()->addBehavior('kutrlService', function () { return ['isgd','isgdKutrlService']; });
}
//if (!defined('SHORTEN_SERVICE_DISABLE_SHORTTO')) {
//    Clearbricks::lib()->autoload(['shorttoKutrlService' => $d . 'services/class.shortto.service.php']);
//    dcCore::app()->addBehavior('kutrlService', function() { return ["shortto","shorttoKutrlService"]; } );
//}
//if (!defined('SHORTEN_SERVICE_DISABLE_TRIM')) {
//    Clearbricks::lib()->autoload(['trimKutrlService' => $d . 'services/class.trim.service.php']);
//    dcCore::app()->addBehavior('kutrlService', function() { return ["trim","trimKutrlService"]; } );
//}
if (!defined('SHORTEN_SERVICE_DISABLE_YOURLS')) {
    Clearbricks::lib()->autoload(['yourlsKutrlService' => $d . 'services/class.yourls.service.php']);
    dcCore::app()->addBehavior('kutrlService', function () { return ['yourls','yourlsKutrlService']; });
}
//if (!defined('SHORTEN_SERVICE_DISABLE_SUPR')) {
//    Clearbricks::lib()->autoload(['suprKutrlService' => $d . 'services/class.supr.service.php']);
//    dcCore::app()->addBehavior('kutrlService', function() { return ["supr","suprKutrlService"]; } );
//}

# Shorten url passed through wiki functions
Clearbricks::lib()->autoload(['kutrlWiki' => $d . 'lib.wiki.kutrl.php']);
dcCore::app()->addBehavior('coreInitWikiPost', ['kutrlWiki','coreInitWiki']);
dcCore::app()->addBehavior('coreInitWikiComment', ['kutrlWiki','coreInitWiki']);
dcCore::app()->addBehavior('coreInitWikiSimpleComment', ['kutrlWiki','coreInitWiki']);

# Public page
dcCore::app()->url->register('kutrl', 'go', '^go(/(.*?)|)$', ['urlKutrl', 'redirectUrl']);

# Add kUtRL events on plugin activityReport
if (defined('ACTIVITY_REPORT_V2')) {
    require_once $d . 'lib.kutrl.activityreport.php';
}
