<?php
/**
 * @brief kUtRL, a plugin for Dotclear 2
 *
 * This file contents class to shorten url pass through wiki
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
use Dotclear\Helper\Html\WikiToHtml;

class Wiki
{
    public static function coreInitWiki(WikiToHtml $wiki2xhtml): void
    {
        # Do nothing on comment preview and post preview
        if (!empty($_POST['preview'])
            || isset(dcCore::app()->ctx) && dcCore::app()->ctx->preview
            || !My::settings()?->get('active')
        ) {
            return;
        }
        if (null === ($kut = Utils::quickPlace('wiki'))) {
            return;
        }
        foreach ($kut->allow_protocols as $protocol) {
            $wiki2xhtml->registerFunction(
                'url:' . $protocol,
                [self::class, 'transform']
            );
        }
    }

    /**
     * @return  array<string,string>
     */
    public static function transform(string $url, string $content): ?array
    {
        if (!My::settings()?->get('active')) {
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
        $res['url']   = $kut->url_base . $rs->hash;
        $res['title'] = sprintf(__('%s (Shorten with %s)'), $rs->url, __($kut->name));
        if ($testurl == $content) {
            $res['content'] = $res['url'];
        }

        dcCore::app()->callBehavior('wikiAfterKutrlCreate', $rs, __('New short URL'));

        return $res;
    }
}
