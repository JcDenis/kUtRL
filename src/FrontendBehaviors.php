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
    /**
     * Disable URL shoretning on filtered tag.
     *
     * @param   ArrayObject<string, mixed>  $attr   The attributes
     */
    public static function templateBeforeValueV2(string $tag, ArrayObject $attr): ?string
    {
        if (!empty($attr['disable_kutrl']) && in_array($tag, My::USED_TAGS)) {
            return '<?php App::frontend()->context()->__set("disable_kutrl", true); ?>';
        }

        return null;
    }

    /**
     * Re unable it after tag.
     *
     * @param   ArrayObject<string, mixed>  $attr   The attributes
     */
    public static function templateAfterValueV2(string $tag, ArrayObject $attr): ?string
    {
        if (!empty($attr['disable_kutrl']) && in_array($tag, My::USED_TAGS)) {
            return '<?php App::frontend()->context()->__set("disable_kutrl", false); ?>';
        }

        return null;
    }

    /**
     * Replace long urls on the fly (on filter) for default tags.
     *
     * @param   array<int|string, mixed>  $args   The attributes
     */
    public static function publicBeforeContentFilterV2(string $tag, array $args): ?string
    {
        # Unknow tag
        if (!in_array($tag, My::USED_TAGS)) {
            return null;
        }
        # URL shortening is disabled by tag attribute
        if (true !== App::frontend()->context()->__get('disable_kutrl')) {
            # plugin is not activated
            if (!My::settings()->getBool('active', false)
                || !My::settings()->getBool('tpl_active', false)
                || !App::frontend()->context()->exists('kutrl')
            ) {
                return null;
            }
            # Existing
            $kut = App::frontend()->context()->__get('kutrl');
            if (!($kut instanceof Service) || !is_string($args[0])) {
                return null;
            }
            if (false !== ($kutrl_rs = $kut->isKnowUrl($args[0]))) {
                $args[0] = $kut->srvUrlBase() . $kutrl_rs->strField('hash');
                # New
            } elseif (false !== ($kutrl_rs = $kut->hash($args[0]))) {
                $args[0] = $kut->srvUrlBase() . $kutrl_rs->strField('hash');

                # ex: Send new url to messengers
                App::behavior()->callBehavior('publicAfterKutrlCreate', $kutrl_rs, __('New public short URL'));
            }
        }

        return null;
    }

    public static function publicBeforeDocumentV2(): void
    {
        $s = My::settings();

        # Passive : all kutrl tag return long url
        App::frontend()->context()->__set('kutrl_passive', $s->getBool('tpl_passive', false));

        if (!$s->getBool('active', false)
            || $s->getStr('tpl_service', false) === ''
            || null === ($kut = Utils::quickPlace('tpl'))
        ) {
            return;
        }

        App::frontend()->context()->__set('kutrl', $kut);
    }

    public static function publicHeadContent(): void
    {
        $css = My::settings()->getStr('srv_local_css', false);
        if (!empty($css)) {
            echo
            "\n<!-- CSS for " . My::id() . " --> \n" .
            "<style type=\"text/css\"> \n" .
            Html::escapeHTML($css) . "\n" .
            "</style>\n";
        }
    }
}
