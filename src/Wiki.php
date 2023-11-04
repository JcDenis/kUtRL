<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use Dotclear\App;
use Dotclear\Helper\Html\WikiToHtml;

/**
 * @brief       kUtRL wiki stuff.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Wiki
{
    public static function coreInitWiki(WikiToHtml $wiki2xhtml): void
    {
        # Do nothing on comment preview and post preview
        if (!empty($_POST['preview'])
            || (App::task()->checkContext('FRONTEND') && App::frontend()->context()->preview)
            || !My::settings()->get('active')
        ) {
            return;
        }
        if (null === ($kut = Utils::quickPlace('wiki'))) {
            return;
        }
        foreach ($kut->get('allow_protocols') as $protocol) {
            $wiki2xhtml->registerFunction(
                'url:' . $protocol,
                self::transform(...)
            );
        }
    }

    /**
     * @return  array<string,string>
     */
    public static function transform(string $url, string $content): ?array
    {
        if (!My::settings()->get('active')) {
            return null;
        }
        if (null === ($kut = Utils::quickPlace('wiki'))) {
            return [];
        }
        # Test if long url exists
        $is_new = false;
        $rs     = $kut->isKnowUrl($url);
        if (!$rs) {
            $is_new = true;
            $rs     = $kut->hash($url);
        }
        if (!$rs) {
            return [];
        }
        $res          = [];
        $testurl      = strlen($rs->url) > 35 ? substr($rs->url, 0, 35) . '...' : $rs->url;
        $res['url']   = $kut->get('url_base') . $rs->hash;
        $res['title'] = sprintf(__('%s (Shorten with %s)'), $rs->url, __($kut->get('name')));
        if ($testurl == $content) {
            $res['content'] = $res['url'];
        }

        App::behavior()->callBehavior('wikiAfterKutrlCreate', $rs, __('New short URL'));

        return $res;
    }
}
