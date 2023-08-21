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
use dcTemplate;

class FrontendTemplate
{
    public static function pageURL(ArrayObject$attr): string
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'dcCore::app()->blog->url.dcCore::app()->url->getBase("kutrl")') . '; ?>';
    }

    public static function pageIf(ArrayObject $attr, string $content): string
    {
        $operator = isset($attr['operator']) ? dcTemplate::getOperator($attr['operator']) : '&&';

        if (isset($attr['is_active'])) {
            $sign = (bool) $attr['is_active'] ? '' : '!';
            $if[] = $sign . 'dcCore::app()->blog->settings->get("' . My::id() . '")->get("srv_local_public")';
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
        $operator = isset($attr['operator']) ? dcTemplate::getOperator($attr['operator']) : '&&';

        if (isset($attr['has_message'])) {
            $sign = (bool) $attr['has_message'] ? '!' : '=';
            $if[] = '"" ' . $sign . '= dcCore::app()->ctx->kutrl_msg';
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
        return '<?php echo dcCore::app()->ctx->kutrl_msg; ?>';
    }

    public static function humanField(ArrayObject $attr): string
    {
        return "<?php echo sprintf(__('Confirm by writing \"%s\" in next field:'),dcCore::app()->ctx->kutrl_hmf); ?>";
    }

    public static function humanFieldProtect(ArrayObject $attr): string
    {
        return
        '<input type="hidden" name="hmfp" id="hmfp" value="<?php echo dcCore::app()->ctx->kutrl_hmfp; ?>" />' .
        '<?php echo dcCore::app()->formNonce(); ?>';
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
        return self::genericKutrlIf('dcCore::app()->ctx->file_url', $attr, $content);
    }

    public static function MediaKutrl(ArrayObject $attr): string
    {
        return self::genericKutrl('dcCore::app()->ctx->file_url', $attr);
    }

    public static function EntryAuthorKutrlIf(ArrayObject$attr, string $content): string
    {
        return self::genericKutrlIf('dcCore::app()->ctx->posts->user_url', $attr, $content);
    }

    public static function EntryAuthorKutrl(ArrayObject $attr): string
    {
        return self::genericKutrl('dcCore::app()->ctx->posts->user_url', $attr);
    }

    public static function EntryKutrlIf(ArrayObject $attr, string $content): string
    {
        return self::genericKutrlIf('dcCore::app()->ctx->posts->getURL()', $attr, $content);
    }

    public static function EntryKutrl(ArrayObject $attr): string
    {
        return self::genericKutrl('dcCore::app()->ctx->posts->getURL()', $attr);
    }

    public static function CommentAuthorKutrlIf(ArrayObject $attr, string $content): string
    {
        return self::genericKutrlIf('dcCore::app()->ctx->comments->getAuthorURL()', $attr, $content);
    }

    public static function CommentAuthorKutrl(ArrayObject $attr): string
    {
        return self::genericKutrl('dcCore::app()->ctx->comments->getAuthorURL()', $attr);
    }

    public static function CommentPostKutrlIf(ArrayObject $attr, string $content): string
    {
        return self::genericKutrlIf('dcCore::app()->ctx->comments->getPostURL()', $attr, $content);
    }

    public static function CommentPostKutrl(ArrayObject $attr): string
    {
        return self::genericKutrl('dcCore::app()->ctx->comments->getPostURL()', $attr);
    }

    protected static function genericKutrlIf(string $str, ArrayObject $attr, string $content): string
    {
        $operator = isset($attr['operator']) ? dcTemplate::getOperator($attr['operator']) : '&&';

        if (isset($attr['is_active'])) {
            $sign = (bool) $attr['is_active'] ? '' : '!';
            $if[] = $sign . 'dcCore::app()->ctx->exists("kutrl")';
        }
        if (isset($attr['passive_mode'])) {
            $sign = (bool) $attr['passive_mode'] ? '' : '!';
            $if[] = $sign . 'dcCore::app()->ctx->kutrl_passive';
        }
        if (isset($attr['has_kutrl'])) {
            $sign = (bool) $attr['has_kutrl'] ? '!' : '=';
            $if[] = '(dcCore::app()->ctx->exists("kutrl") && false ' . $sign . '== dcCore::app()->ctx->kutrl->select(' . $str . ',null,null,"kutrl"))';
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
        $f = dcCore::app()->tpl->getFilters($attr);

        return
        "<?php \n" .
        # Preview
        "if (dcCore::app()->ctx->preview) { \n" .
        ' echo ' . sprintf($f, $str) . '; ' .
        "} else { \n" .
        # Disable
        "if (!dcCore::app()->ctx->exists('kutrl')) { \n" .
        # Passive mode
        ' if (dcCore::app()->ctx->kutrl_passive) { ' .
        '  echo ' . sprintf($f, $str) . '; ' .
        " } \n" .
        "} else { \n" .
        # Existing
        ' if (false !== ($kutrl_rs = dcCore::app()->ctx->kutrl->isKnowUrl(' . $str . '))) { ' .
        '  echo ' . sprintf($f, 'dcCore::app()->ctx->kutrl->url_base.$kutrl_rs->hash') . '; ' .
        " } \n" .
        # New
        ' elseif (false !== ($kutrl_rs = dcCore::app()->ctx->kutrl->hash(' . $str . '))) { ' .
        '  echo ' . sprintf($f, 'dcCore::app()->ctx->kutrl->url_base.$kutrl_rs->hash') . '; ' .

        # ex: Send new url to messengers
        ' if (!empty($kutrl_rs)) { ' .
        "  dcCore::app()->callBehavior('publicAfterKutrlCreate',\$kutrl_rs,__('New public short URL')); " .
        " } \n" .

        " } \n" .
        " unset(\$kutrl_rs); \n" .
        "} \n" .
        "} \n" .
        "?>\n";
    }
}
