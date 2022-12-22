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
# This file contents class to shorten url pass through wiki

if (!defined('DC_RC_PATH')) {
    return null;
}

class kutrlWiki
{
    public static function coreInitWiki($wiki2xhtml)
    {
        # Do nothing on comment preview and post preview
        if (!empty($_POST['preview'])
            || isset(dcCore::app()->ctx) && dcCore::app()->ctx->preview
            || !dcCore::app()->blog->settings->get(basename(dirname(__DIR__)))->get('active')
        ) {
            return null;
        }
        if (null === ($kut = kUtRL::quickPlace('wiki'))) {
            return null;
        }
        foreach ($kut->allow_protocols as $protocol) {
            $wiki2xhtml->registerFunction(
                'url:' . $protocol,
                ['kutrlWiki', 'transform']
            );
        }
    }

    public static function transform($url, $content)
    {
        if (!dcCore::app()->blog->settings->get(basename(dirname(__DIR__)))->get('active')) {
            return null;
        }
        if (null === ($kut = kUtRL::quickPlace('wiki'))) {
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

        # ex: Send new url to messengers
        if (!empty($rs)) {
            dcCore::app()->callBehavior('wikiAfterKutrlCreate', $rs, __('New short URL'));
        }

        return $res;
    }
}
