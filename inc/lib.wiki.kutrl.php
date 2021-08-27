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

# This file contents class to shorten url pass through wiki

if (!defined('DC_RC_PATH')) {
    return null;
}

class kutrlWiki
{
    public static function coreInitWiki($wiki2xhtml)
    {
        global $core;
        $s = $core->blog->settings->kUtRL;

        # Do nothing on comment preview and post preview
        if (!empty($_POST['preview']) 
         || !empty($GLOBALS['_ctx']) && $GLOBALS['_ctx']->preview
         || !$s->kutrl_active) {
            return null;
        }
        if (null === ($kut = kutrl::quickPlace('wiki'))) {
            return null;
        }
        foreach($kut->allow_protocols as $protocol) {
            $wiki2xhtml->registerFunction(
                'url:' . $protocol,
                ['kutrlWiki', 'transform']
            );
        }
    }

    public static function transform($url, $content)
    {
        global $core;
        $s = $core->blog->settings->kUtRL;

        if (!$s->kutrl_active) {
            return null;
        }
        if (null === ($kut = kutrl::quickPlace('wiki'))) {
            return [];
        }
        # Test if long url exists
        $is_new = false;
        $rs = $kut->isKnowUrl($url);
        if (!$rs) {
            $is_new = true;
            $rs = $kut->hash($url);
        }
        if (!$rs) {
            return [];
        } else {
            $res = [];
            $testurl = strlen($rs->url) > 35 ? substr($rs->url, 0, 35) . '...' : $rs->url;
            $res['url'] = $kut->url_base . $rs->hash;
            $res['title'] = sprintf(__('%s (Shorten with %s)'), $rs->url, __($kut->name));
            if ($testurl == $content) $res['content'] = $res['url'];

            # ex: Send new url to messengers
            if (!empty($rs)) {
                $core->callBehavior('wikiAfterKutrlCreate', $core, $rs, __('New short URL'));
            }
            return $res;
        }
    }
}