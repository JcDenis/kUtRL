<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       kUtRL prepend class.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
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
        App::behavior()->addBehavior('kutrlService', fn () => ['default', Service\ServiceDefault::class]);
        if (!defined('SHORTEN_SERVICE_DISABLE_CUSTOM')) {
            App::behavior()->addBehavior('kutrlService', fn () => ['custom', Service\ServiceCustom::class]);
        }
        if (!defined('SHORTEN_SERVICE_DISABLE_LOCAL')) {
            App::behavior()->addBehavior('kutrlService', fn () => ['local', Service\ServiceLocal::class]);
        }
        if (!defined('SHORTEN_SERVICE_DISABLE_BILBOLINKS')) {
            App::behavior()->addBehavior('kutrlService', fn () => ['bilbolinks', Service\ServiceBilbolinks::class]);
        }
        if (!defined('SHORTEN_SERVICE_DISABLE_BITLY')) {
            App::behavior()->addBehavior('kutrlService', fn () => ['bitly', Service\ServiceBitly::class]);
        }
        if (!defined('SHORTEN_SERVICE_DISABLE_ISGD')) {
            App::behavior()->addBehavior('kutrlService', fn () => ['isgd', Service\ServiceIsgd::class]);
        }
        if (!defined('SHORTEN_SERVICE_DISABLE_YOURLS')) {
            App::behavior()->addBehavior('kutrlService', fn () => ['yourls', Service\ServiceYourls::class]);
        }

        # Shorten url passed through wiki functions
        App::behavior()->addBehaviors([
            'coreInitWikiPost'          => Wiki::coreInitWiki(...),
            'coreInitWikiComment'       => Wiki::coreInitWiki(...),
            'coreInitWikiSimpleComment' => Wiki::coreInitWiki(...),
        ]);

        # Public page
        App::url()->register(
            'kutrl',
            'go',
            '^go(/(.*?)|)$',
            FrontendUrl::redirectUrl(...)
        );

        return true;
    }
}
