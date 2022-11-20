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
if (!defined('DC_RC_PATH')) {
    return null;
}

require_once __DIR__ . '/_widgets.php';

dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), __DIR__ . '/default-templates');

dcCore::app()->addBehavior('publicBeforeDocumentV2', ['pubKutrl', 'publicBeforeDocument']);
dcCore::app()->addBehavior('publicHeadContent', ['pubKutrl', 'publicHeadContent']);
dcCore::app()->addBehavior('publicBeforeContentFilterV2', ['pubKutrl', 'publicBeforeContentFilter']);
dcCore::app()->addBehavior('templateBeforeValueV2', ['pubKutrl', 'templateBeforeValue']);
dcCore::app()->addBehavior('templateAfterValueV2', ['pubKutrl', 'templateAfterValue']);

dcCore::app()->tpl->addBlock('kutrlPageIf', ['tplKutrl', 'pageIf']);
dcCore::app()->tpl->addBlock('kutrlMsgIf', ['tplKutrl', 'pageMsgIf']);

dcCore::app()->tpl->addValue('kutrlPageURL', ['tplKutrl', 'pageURL']);
dcCore::app()->tpl->addValue('kutrlMsg', ['tplKutrl', 'pageMsg']);
dcCore::app()->tpl->addValue('kutrlHumanField', ['tplKutrl', 'humanField']);
dcCore::app()->tpl->addValue('kutrlHumanFieldProtect', ['tplKutrl', 'humanFieldProtect']);

dcCore::app()->tpl->addBlock('AttachmentKutrlIf', ['tplKutrl', 'AttachmentKutrlIf']);
dcCore::app()->tpl->addValue('AttachmentKutrl', ['tplKutrl', 'AttachmentKutrl']);
dcCore::app()->tpl->addBlock('MediaKutrlIf', ['tplKutrl', 'MediaKutrlIf']);
dcCore::app()->tpl->addValue('MediaKutrl', ['tplKutrl', 'MediaKutrl']);
dcCore::app()->tpl->addBlock('EntryAuthorKutrlIf', ['tplKutrl', 'EntryAuthorKutrlIf']);
dcCore::app()->tpl->addValue('EntryAuthorKutrl', ['tplKutrl', 'EntryAuthorKutrl']);
dcCore::app()->tpl->addBlock('EntryKutrlIf', ['tplKutrl', 'EntryKutrlIf']);
dcCore::app()->tpl->addValue('EntryKutrl', ['tplKutrl', 'EntryKutrl']);
dcCore::app()->tpl->addBlock('CommentAuthorKutrlIf', ['tplKutrl', 'CommentAuthorKutrlIf']);
dcCore::app()->tpl->addValue('CommentAuthorKutrl', ['tplKutrl', 'CommentAuthorKutrl']);
dcCore::app()->tpl->addBlock('CommentPostKutrlIf', ['tplKutrl', 'CommentPostKutrlIf']);
dcCore::app()->tpl->addValue('CommentPostKutrl', ['tplKutrl', 'CommentPostKutrl']);

class urlKutrl extends dcUrlHandlers
{
    # Redirect !!! local !!! service only
    public static function redirectUrl($args)
    {
        $s = dcCore::app()->blog->settings->kUtRL;

        # Not active, go to default 404
        if (!$s->kutrl_active) {
            self::p404();

            return null;
        }
        # Not a valid url, go to kutrl 404
        if (!preg_match('#^(|(/(.*?)))$#', $args, $m)) {
            self::kutrl404();

            return null;
        }

        $args                          = $m[3] ?? '';
        dcCore::app()->ctx->kutrl_msg  = '';
        dcCore::app()->ctx->kutrl_hmf  = hmfKutrl::create();
        dcCore::app()->ctx->kutrl_hmfp = hmfKutrl::protect(dcCore::app()->ctx->kutrl_hmf);

        $kut = new localKutrlService();

        # Nothing on url
        if ($m[1] == '/') {
            dcCore::app()->ctx->kutrl_msg = 'No link given.';
        }
        # find suffix on redirect url
        $suffix = '';
        if (preg_match('@^([^?/#]+)(.*?)$@', $args, $more)) {
            $args   = $more[1];
            $suffix = $more[2];
        }
        # No arg, go to kurtl page
        if ($args == '') {
            self::pageKutrl($kut);

            return null;
        }
        # Not find, go to kutrl 404
        if (false === ($url = $kut->getUrl($args))) {
            //$_ctx->kutrl_msg = 'Failed to find short link.';
            //self::pageKutrl($kut);
            self::kutrl404();

            return null;
        }
        # Removed (empty url), go to kutrl 404
        if (!$url) {
            self::kutrl404();

            return null;
        }

        dcCore::app()->blog->triggerBlog();
        http::redirect($url . $suffix);

        return null;
    }

    private static function pageKutrl($kut)
    {
        $s = dcCore::app()->blog->settings->kUtRL;

        # Not active, go to default 404
        if (!$s->kutrl_active) {
            self::p404();

            return null;
        }
        # Public page not active, go to kutrl 404
        if (!$s->kutrl_srv_local_public) {
            self::kutrl404();

            return null;
        }
        # Validation form
        $url = !empty($_POST['longurl']) ? trim(dcCore::app()->con->escape($_POST['longurl'])) : '';
        if (!empty($url)) {
            $hmf  = !empty($_POST['hmf']) ? $_POST['hmf'] : '!';
            $hmfu = !empty($_POST['hmfp']) ? hmfKutrl::unprotect($_POST['hmfp']) : '?';

            $err = false;
            if (!$err) {
                if ($hmf != $hmfu) {
                    $err                          = true;
                    dcCore::app()->ctx->kutrl_msg = __('Failed to verify protected field.');
                }
            }
            if (!$err) {
                if (!$kut->testService()) {
                    $err                          = true;
                    dcCore::app()->ctx->kutrl_msg = __('Service is not well configured.');
                }
            }
            if (!$err) {
                if (!$kut->isValidUrl($url)) {
                    $err                          = true;
                    dcCore::app()->ctx->kutrl_msg = __('This string is not a valid URL.');
                }
            }
            if (!$err) {
                if (!$kut->isLongerUrl($url)) {
                    $err                          = true;
                    dcCore::app()->ctx->kutrl_msg = __('This link is too short.');
                }
            }
            if (!$err) {
                if (!$kut->isProtocolUrl($url)) {
                    $err                          = true;
                    dcCore::app()->ctx->kutrl_msg = __('This type of link is not allowed.');
                }
            }

            if (!$err) {
                if (!$kut->allow_external_url && !$kut->isBlogUrl($url)) {
                    $err                          = true;
                    dcCore::app()->ctx->kutrl_msg = __('Short links are limited to this blog URL.');
                }
            }
            if (!$err) {
                if ($kut->isServiceUrl($url)) {
                    $err                          = true;
                    dcCore::app()->ctx->kutrl_msg = __('This link is already a short link.');
                }
            }
            if (!$err) {
                if (false !== ($rs = $kut->isKnowUrl($url))) {
                    $err = true;

                    $url     = $rs->url;
                    $new_url = $kut->url_base . $rs->hash;

                    dcCore::app()->ctx->kutrl_msg = sprintf(
                        __('Short link for %s is %s'),
                        html::escapeHTML($url),
                        '<a href="' . $new_url . '">' . $new_url . '</a>'
                    );
                }
            }
            if (!$err) {
                if (false === ($rs = $kut->hash($url))) {
                    $err                          = true;
                    dcCore::app()->ctx->kutrl_msg = __('Failed to create short link.');
                } else {
                    $url     = $rs->url;
                    $new_url = $kut->url_base . $rs->hash;

                    dcCore::app()->ctx->kutrl_msg = sprintf(
                        __('Short link for %s is %s'),
                        html::escapeHTML($url),
                        '<a href="' . $new_url . '">' . $new_url . '</a>'
                    );
                    dcCore::app()->blog->triggerBlog();

                    # ex: Send new url to messengers
                    if (!empty($rs)) {
                        dcCore::app()->callBehavior('publicAfterKutrlCreate', $rs, __('New public short URL'));
                    }
                }
            }
        }

        dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), __DIR__ . '/default-templates');
        self::serveDocument('kutrl.html');

        return null;
    }

    protected static function kutrl404()
    {
        if (!dcCore::app()->blog->settings->kUtRL->kutrl_srv_local_404_active) {
            self::p404();

            return null;
        }

        dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), __DIR__ . '/default-templates');

        header('Content-Type: text/html; charset=UTF-8');
        http::head(404, 'Not Found');
        dcCore::app()->url->type         = '404';
        dcCore::app()->ctx->current_tpl  = 'kutrl404.html';
        dcCore::app()->ctx->content_type = 'text/html';

        echo dcCore::app()->tpl->getData(dcCore::app()->ctx->current_tpl);

        # --BEHAVIOR-- publicAfterDocument
        dcCore::app()->callBehavior('publicAfterDocumentV2');
        exit;
    }
}

class pubKutrl
{
    # List of template tag which content URL that can be shortenn
    public static $know_tags = [
        'AttachmentURL',
        'CategoryURL',
        'MediaURL',
        'EntryAuthorURL',
        'EntryURL',
        'EntryCategoryURL',
        'CommentAuthorURL',
        'CommentPostURL',
    ];

    # Disable URL shoretning on filtered tag
    public static function templateBeforeValue($tag, $attr)
    {
        if (!empty($attr['disable_kutrl']) && in_array($tag, pubKutrl::$know_tags)) {
            return '<?php $GLOBALS["disable_kutrl"] = true; ?>';
        }

        return null;
    }

    # Re unable it after tag
    public static function templateAfterValue($tag, $attr)
    {
        if (!empty($attr['disable_kutrl']) && in_array($tag, pubKutrl::$know_tags)) {
            return '<?php $GLOBALS["disable_kutrl"] = false; ?>';
        }

        return null;
    }

    # Replace long urls on the fly (on filter) for default tags
    public static function publicBeforeContentFilter($tag, $args)
    {
        # Unknow tag
        if (!in_array($tag, pubKutrl::$know_tags)) {
            return null;
        }
        # URL shortening is disabled by tag attribute
        if (empty($GLOBALS['disable_kutrl'])) {
            # kUtRL is not activated
            if (!dcCore::app()->blog->settings->kUtRL->kutrl_active
                || !dcCore::app()->blog->settings->kUtRL->kutrl_tpl_active
            ) {
                return null;
            }

            # Oups
            if (!dcCore::app()->ctx->exists('kutrl')) {
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

    public static function publicBeforeDocument()
    {
        $s = dcCore::app()->blog->settings->kUtRL;

        # Passive : all kutrl tag return long url
        dcCore::app()->ctx->kutrl_passive = (bool) $s->kutrl_tpl_passive;

        if (!$s->kutrl_active || !$s->kutrl_tpl_service) {
            return null;
        }
        if (null === ($kut = kUtRL::quickPlace('tpl'))) {
            return null;
        }
        dcCore::app()->ctx->kutrl = $kut;
    }

    public static function publicHeadContent($core)
    {
        $css = dcCore::app()->blog->settings->kUtRL->kutrl_srv_local_css;
        if ($css) {
            echo
            "\n<!-- CSS for kUtRL --> \n" .
            "<style type=\"text/css\"> \n" .
            html::escapeHTML($css) . "\n" .
            "</style>\n";
        }
    }
}

class tplKutrl
{
    public static function pageURL($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'dcCore::app()->blog->url.dcCore::app()->url->getBase("kutrl")') . '; ?>';
    }

    public static function pageIf($attr, $content)
    {
        $operator = isset($attr['operator']) ? self::getOperator($attr['operator']) : '&&';

        if (isset($attr['is_active'])) {
            $sign = (bool) $attr['is_active'] ? '' : '!';
            $if[] = $sign . 'dcCore::app()->blog->settings->kUtRL->kutrl_srv_local_public';
        }
        if (empty($if)) {
            return $content;
        }

        return
        '<?php if(' . implode(' ' . $operator . ' ', $if) . ") : ?>\n" .
        $content .
        "<?php endif; unset(\$s);?>\n";
    }

    public static function pageMsgIf($attr, $content)
    {
        $operator = isset($attr['operator']) ? self::getOperator($attr['operator']) : '&&';

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

    public static function pageMsg($attr)
    {
        return '<?php echo dcCore::app()->ctx->kutrl_msg; ?>';
    }

    public static function humanField($attr)
    {
        return "<?php echo sprintf(__('Confirm by writing \"%s\" in next field:'),dcCore::app()->ctx->kutrl_hmf); ?>";
    }

    public static function humanFieldProtect($attr)
    {
        return
        '<input type="hidden" name="hmfp" id="hmfp" value="<?php echo dcCore::app()->ctx->kutrl_hmfp; ?>" />' .
        '<?php echo dcCore::app()->formNonce(); ?>';
    }

    public static function AttachmentKutrlIf($attr, $content)
    {
        return self::genericKutrlIf('$attach_f->file_url', $attr, $content);
    }

    public static function AttachmentKutrl($attr)
    {
        return self::genericKutrl('$attach_f->file_url', $attr);
    }

    public static function MediaKutrlIf($attr, $content)
    {
        return self::genericKutrlIf('dcCore::app()->ctx->file_url', $attr, $content);
    }

    public static function MediaKutrl($attr)
    {
        return self::genericKutrl('dcCore::app()->ctx->file_url', $attr);
    }

    public static function EntryAuthorKutrlIf($attr, $content)
    {
        return self::genericKutrlIf('dcCore::app()->ctx->posts->user_url', $attr, $content);
    }

    public static function EntryAuthorKutrl($attr)
    {
        return self::genericKutrl('dcCore::app()->ctx->posts->user_url', $attr);
    }

    public static function EntryKutrlIf($attr, $content)
    {
        return self::genericKutrlIf('dcCore::app()->ctx->posts->getURL()', $attr, $content);
    }

    public static function EntryKutrl($attr)
    {
        return self::genericKutrl('dcCore::app()->ctx->posts->getURL()', $attr);
    }

    public static function CommentAuthorKutrlIf($attr, $content)
    {
        return self::genericKutrlIf('dcCore::app()->ctx->comments->getAuthorURL()', $attr, $content);
    }

    public static function CommentAuthorKutrl($attr)
    {
        return self::genericKutrl('dcCore::app()->ctx->comments->getAuthorURL()', $attr);
    }

    public static function CommentPostKutrlIf($attr, $content)
    {
        return self::genericKutrlIf('dcCore::app()->ctx->comments->getPostURL()', $attr, $content);
    }

    public static function CommentPostKutrl($attr)
    {
        return self::genericKutrl('dcCore::app()->ctx->comments->getPostURL()', $attr);
    }

    protected static function genericKutrlIf($str, $attr, $content)
    {
        $operator = isset($attr['operator']) ? self::getOperator($attr['operator']) : '&&';

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

    protected static function genericKutrl($str, $attr)
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

    protected static function getOperator($op)
    {
        switch (strtolower($op)) {
            case 'or':
            case '||':
                return '||';
            case 'and':
            case '&&':
            default:
                return '&&';
        }
    }
}

class hmfKutrl
{
    public static $chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function create($len = 6)
    {
        $res   = '';
        $chars = self::$chars;
        for ($i = 0; $i < $len; $i++) {
            $res .= $chars[rand(0, strlen($chars) - 1)];
        }

        return $res;
    }

    public static function protect($str)
    {
        $res   = '';
        $chars = self::$chars;
        for ($i = 0; $i < strlen($str); $i++) {
            $res .= $chars[rand(0, strlen($chars) - 1)] . $str[$i];
        }

        return $res;
    }

    public static function unprotect($str)
    {
        $res = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $i++;
            $res .= $str[$i];
        }

        return $res;
    }
}
