<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Html\Html;

/**
 * @brief       kUtRL frontend behaviors.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class FrontendBehaviors
{
    # Disable URL shoretning on filtered tag
    public static function templateBeforeValueV2(string $tag, ArrayObject $attr): ?string
    {
        if (!empty($attr['disable_kutrl']) && in_array($tag, My::USED_TAGS)) {
            return '<?php App::frontend()->context()->__set("disable_kutrl", true); ?>';
        }

        return null;
    }

    # Re unable it after tag
    public static function templateAfterValueV2(string $tag, ArrayObject $attr): ?string
    {
        if (!empty($attr['disable_kutrl']) && in_array($tag, My::USED_TAGS)) {
            return '<?php App::frontend()->context()->__set("disable_kutrl", false); ?>';
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
        if (true !== App::frontend()->context()->__get('disable_kutrl')) {
            # plugin is not activated
            if (!My::settings()->get('active')
                || !My::settings()->get('tpl_active')
                || !App::frontend()->context()->exists('kutrl')
            ) {
                return null;
            }
            # Existing
            if (false !== ($kutrl_rs = App::frontend()->context()->kutrl->isKnowUrl($args[0]))) {
                $args[0] = App::frontend()->context()->kutrl->url_base . $kutrl_rs->hash;
                # New
            } elseif (false !== ($kutrl_rs = App::frontend()->context()->kutrl->hash($args[0]))) {
                $args[0] = App::frontend()->context()->kutrl->url_base . $kutrl_rs->hash;

                # ex: Send new url to messengers
                if (!empty($kutrl_rs)) {
                    App::behavior()->callBehavior('publicAfterKutrlCreate', $kutrl_rs, __('New public short URL'));
                }
            }
        }
    }

    public static function publicBeforeDocumentV2(): void
    {
        $s = My::settings();

        # Passive : all kutrl tag return long url
        App::frontend()->context()->kutrl_passive = (bool) $s->get('tpl_passive');

        if (!$s->get('active')
            || !$s->get('tpl_service')
            || null === ($kut = Utils::quickPlace('tpl'))
        ) {
            return;
        }

        App::frontend()->context()->kutrl = $kut;
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
