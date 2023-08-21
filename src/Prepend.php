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
declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use dcCore;
use Dotclear\Core\Process;

/**
 * Module prepend.
 */
class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
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

        # Services
        dcCore::app()->addBehavior('kutrlService', fn () => ['default', Service\ServiceDefault::class]);
        if (!defined('SHORTEN_SERVICE_DISABLE_CUSTOM')) {
            dcCore::app()->addBehavior('kutrlService', fn () => ['custom', Service\ServiceCustom::class]);
        }
        if (!defined('SHORTEN_SERVICE_DISABLE_LOCAL')) {
            dcCore::app()->addBehavior('kutrlService', fn () => ['local', Service\ServiceLocal::class]);
        }
        if (!defined('SHORTEN_SERVICE_DISABLE_BILBOLINKS')) {
            dcCore::app()->addBehavior('kutrlService', fn () => ['bilbolinks', Service\ServiceBilbolinks::class]);
        }
        if (!defined('SHORTEN_SERVICE_DISABLE_BITLY')) {
            dcCore::app()->addBehavior('kutrlService', fn () => ['bitly', Service\ServiceBitly::class]);
        }
        if (!defined('SHORTEN_SERVICE_DISABLE_ISGD')) {
            dcCore::app()->addBehavior('kutrlService', fn () => ['isgd', Service\ServiceIsgd::class]);
        }
        if (!defined('SHORTEN_SERVICE_DISABLE_YOURLS')) {
            dcCore::app()->addBehavior('kutrlService', fn () => ['yourls', Service\ServiceYourls::class]);
        }

        # Shorten url passed through wiki functions
        dcCore::app()->addBehaviors([
            'coreInitWikiPost'          => [Wiki::class, 'coreInitWiki'],
            'coreInitWikiComment'       => [Wiki::class, 'coreInitWiki'],
            'coreInitWikiSimpleComment' => [Wiki::class,'coreInitWiki'],
        ]);

        # Public page
        dcCore::app()->url->register(
            'kutrl',
            'go',
            '^go(/(.*?)|)$',
            [FrontendUrl::class, 'redirectUrl']
        );

        # Add kUtRL events on plugin activityReport
        if (defined('ACTIVITY_REPORT_V2')) {
            require_once $d . 'lib.kutrl.activityreport.php';
        }

        return true;
    }
}
