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

use ArrayObject;
use dcCore;
use Dotclear\Helper\Html\Html;

class FrontendBehaviors
{
    # Disable URL shoretning on filtered tag
    public static function templateBeforeValueV2(string $tag, ArrayObject $attr): ?string
    {
        if (!empty($attr['disable_kutrl']) && in_array($tag, My::USED_TAGS)) {
            return '<?php dcCore::app()->ctx->__set("disable_kutrl", true); ?>';
        }

        return null;
    }

    # Re unable it after tag
    public static function templateAfterValueV2(string $tag, ArrayObject $attr): ?string
    {
        if (!empty($attr['disable_kutrl']) && in_array($tag, My::USED_TAGS)) {
            return '<?php dcCore::app()->ctx->__set("disable_kutrl", false); ?>';
        }

        return null;
    }

    # Replace long urls on the fly (on filter) for default tags
    public static function publicBeforeContentFilterV2(string $tag, array $args): ?string
    {
        # Unknow tag
        if (!in_array($tag, My::USED_TAGS)) {
            return null;
        }
        # URL shortening is disabled by tag attribute
        if (true !== dcCore::app()->ctx->__get('disable_kutrl')) {
            # plugin is not activated
            if (!My::settings()->get('active')
                || !My::settings()->get('tpl_active')
                || !dcCore::app()->ctx->exists('kutrl')
            ) {
                return null;
            }
            # Existing
            if (false !== ($kutrl_rs = dcCore::app()->ctx->kutrl->isKnowUrl($args[0]))) {
                $args[0] = dcCore::app()->ctx->kutrl->url_base . $kutrl_rs->hash;
                # New
            } elseif (false !== ($kutrl_rs = dcCore::app()->ctx->kutrl->hash($args[0]))) {
                $args[0] = dcCore::app()->ctx->kutrl->url_base . $kutrl_rs->hash;

                # ex: Send new url to messengers
                if (!empty($kutrl_rs)) {
                    dcCore::app()->callBehavior('publicAfterKutrlCreate', $kutrl_rs, __('New public short URL'));
                }
            }
        }
    }

    public static function publicBeforeDocumentV2(): void
    {
        $s = My::settings();

        # Passive : all kutrl tag return long url
        dcCore::app()->ctx->kutrl_passive = (bool) $s->get('tpl_passive');

        if (!$s->get('active')
            || !$s->get('tpl_service')
            || null === ($kut = Utils::quickPlace('tpl'))
        ) {
            return;
        }

        dcCore::app()->ctx->kutrl = $kut;
    }

    public static function publicHeadContent($_): void
    {
        $css = My::settings()->get('srv_local_css');
        if (!empty($css)) {
            echo
            "\n<!-- CSS for " . My::id() . " --> \n" .
            "<style type=\"text/css\"> \n" .
            Html::escapeHTML($css) . "\n" .
            "</style>\n";
        }
    }
}
