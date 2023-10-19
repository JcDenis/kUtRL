<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use ArrayObject;
use Dotclear\App;

/**
 * @brief       kUtRL frontend template.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class FrontendTemplate
{
    public static function pageURL(ArrayObject$attr): string
    {
        $f = App::frontend()->template()->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'App::blog()->url().App::url()->getBase("kutrl")') . '; ?>';
    }

    public static function pageIf(ArrayObject $attr, string $content): string
    {
        $operator = isset($attr['operator']) ? App::frontend()->template()->getOperator($attr['operator']) : '&&';

        if (isset($attr['is_active'])) {
            $sign = (bool) $attr['is_active'] ? '' : '!';
            $if[] = $sign . 'App::blog()->settings()->get("' . My::id() . '")->get("srv_local_public")';
        }
        if (empty($if)) {
            return $content;
        }

        return
        '<?php if(' . implode(' ' . $operator . ' ', $if) . ") : ?>\n" .
        $content .
        "<?php endif; unset(\$s);?>\n";
    }

    public static function pageMsgIf(ArrayObject$attr, string $content): string
    {
        $operator = isset($attr['operator']) ? App::frontend()->template()->getOperator($attr['operator']) : '&&';

        if (isset($attr['has_message'])) {
            $sign = (bool) $attr['has_message'] ? '!' : '=';
            $if[] = '"" ' . $sign . '= App::frontend()->context()->kutrl_msg';
        }
        if (empty($if)) {
            return $content;
        }

        return
        '<?php if(' . implode(' ' . $operator . ' ', $if) . ") : ?>\n" .
        $content .
        "<?php endif; ?>\n";
    }

    public static function pageMsg(ArrayObject$attr): string
    {
        return '<?php echo App::frontend()->context()->kutrl_msg; ?>';
    }

    public static function humanField(ArrayObject $attr): string
    {
        return "<?php echo sprintf(__('Confirm by writing \"%s\" in next field:'),App::frontend()->context()->kutrl_hmf); ?>";
    }

    public static function humanFieldProtect(ArrayObject $attr): string
    {
        return
        '<input type="hidden" name="hmfp" id="hmfp" value="<?php echo App::frontend()->context()->kutrl_hmfp; ?>" />' .
        '<?php echo App::nonce()->getFormNonce(); ?>';
    }

    public static function AttachmentKutrlIf(ArrayObject$attr, string $content): string
    {
        return self::genericKutrlIf('$attach_f->file_url', $attr, $content);
    }

    public static function AttachmentKutrl(ArrayObject $attr): string
    {
        return self::genericKutrl('$attach_f->file_url', $attr);
    }

    public static function MediaKutrlIf(ArrayObject$attr, string $content): string
    {
        return self::genericKutrlIf('App::frontend()->context()->file_url', $attr, $content);
    }

    public static function MediaKutrl(ArrayObject $attr): string
    {
        return self::genericKutrl('App::frontend()->context()->file_url', $attr);
    }

    public static function EntryAuthorKutrlIf(ArrayObject$attr, string $content): string
    {
        return self::genericKutrlIf('App::frontend()->context()->posts->user_url', $attr, $content);
    }

    public static function EntryAuthorKutrl(ArrayObject $attr): string
    {
        return self::genericKutrl('App::frontend()->context()->posts->user_url', $attr);
    }

    public static function EntryKutrlIf(ArrayObject $attr, string $content): string
    {
        return self::genericKutrlIf('App::frontend()->context()->posts->getURL()', $attr, $content);
    }

    public static function EntryKutrl(ArrayObject $attr): string
    {
        return self::genericKutrl('App::frontend()->context()->posts->getURL()', $attr);
    }

    public static function CommentAuthorKutrlIf(ArrayObject $attr, string $content): string
    {
        return self::genericKutrlIf('App::frontend()->context()->comments->getAuthorURL()', $attr, $content);
    }

    public static function CommentAuthorKutrl(ArrayObject $attr): string
    {
        return self::genericKutrl('App::frontend()->context()->comments->getAuthorURL()', $attr);
    }

    public static function CommentPostKutrlIf(ArrayObject $attr, string $content): string
    {
        return self::genericKutrlIf('App::frontend()->context()->comments->getPostURL()', $attr, $content);
    }

    public static function CommentPostKutrl(ArrayObject $attr): string
    {
        return self::genericKutrl('App::frontend()->context()->comments->getPostURL()', $attr);
    }

    protected static function genericKutrlIf(string $str, ArrayObject $attr, string $content): string
    {
        $operator = isset($attr['operator']) ? App::fontend()->template()->getOperator($attr['operator']) : '&&';

        if (isset($attr['is_active'])) {
            $sign = (bool) $attr['is_active'] ? '' : '!';
            $if[] = $sign . 'App::frontend()->context()->exists("kutrl")';
        }
        if (isset($attr['passive_mode'])) {
            $sign = (bool) $attr['passive_mode'] ? '' : '!';
            $if[] = $sign . 'App::frontend()->context()->kutrl_passive';
        }
        if (isset($attr['has_kutrl'])) {
            $sign = (bool) $attr['has_kutrl'] ? '!' : '=';
            $if[] = '(App::frontend()->context()->exists("kutrl") && false ' . $sign . '== App::frontend()->context()->kutrl->select(' . $str . ',null,null,"kutrl"))';
        }
        if (empty($if)) {
            return $content;
        }

        return
        '<?php if(' . implode(' ' . $operator . ' ', $if) . ") : ?>\n" .
        $content .
        "<?php endif; ?>\n";
    }

    protected static function genericKutrl(string $str, ArrayObject $attr): string
    {
        $f = App::frontend()->template()->getFilters($attr);

        return
        "<?php \n" .
        # Preview
        "if (App::frontend()->context()->preview) { \n" .
        ' echo ' . sprintf($f, $str) . '; ' .
        "} else { \n" .
        # Disable
        "if (!App::frontend()->context()->exists('kutrl')) { \n" .
        # Passive mode
        ' if (App::frontend()->context()->kutrl_passive) { ' .
        '  echo ' . sprintf($f, $str) . '; ' .
        " } \n" .
        "} else { \n" .
        # Existing
        ' if (false !== ($kutrl_rs = App::frontend()->context()->kutrl->isKnowUrl(' . $str . '))) { ' .
        '  echo ' . sprintf($f, 'App::frontend()->context()->kutrl->url_base.$kutrl_rs->hash') . '; ' .
        " } \n" .
        # New
        ' elseif (false !== ($kutrl_rs = App::frontend()->context()->kutrl->hash(' . $str . '))) { ' .
        '  echo ' . sprintf($f, 'App::frontend()->context()->kutrl->url_base.$kutrl_rs->hash') . '; ' .

        # ex: Send new url to messengers
        ' if (!empty($kutrl_rs)) { ' .
        "  App::behavior()->callBehavior('publicAfterKutrlCreate',\$kutrl_rs,__('New public short URL')); " .
        " } \n" .

        " } \n" .
        " unset(\$kutrl_rs); \n" .
        "} \n" .
        "} \n" .
        "?>\n";
    }
}
