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

use dcCore;
use dcUrlHandlers;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;

class FrontendUrl extends dcUrlHandlers
{
    # Redirect !!! local !!! service only
    public static function redirectUrl(?string $args): void
    {
        # Not active, go to default 404
        if (!My::settings()->get('active')) {
            self::p404();

            return;
        }
        # Not a valid url, go to kutrl 404
        if (!preg_match('#^(|(/(.*?)))$#', $args, $m)) {
            self::kutrl404();

            return;
        }

        $args                          = $m[3] ?? '';
        dcCore::app()->ctx->kutrl_msg  = '';
        dcCore::app()->ctx->kutrl_hmf  = FrontendUtils::create();
        dcCore::app()->ctx->kutrl_hmfp = FrontendUtils::protect(dcCore::app()->ctx->kutrl_hmf);

        $kut = new Service\ServiceLocal();

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

            return;
        }
        # Not find, go to kutrl 404
        if (false === ($url = $kut->getUrl($args))) {
            //dcCore::app()->ctx->kutrl_msg = 'Failed to find short link.';
            //self::pageKutrl($kut);
            self::kutrl404();

            return;
        }
        # Removed (empty url), go to kutrl 404
        if (!$url) {
            self::kutrl404();

            return;
        }

        dcCore::app()->blog->triggerBlog();
        Http::redirect($url . $suffix);
    }

    private static function pageKutrl(Service $kut): void
    {
        $s = My::settings();

        # Not active, go to default 404
        if (!$s->get('active')) {
            self::p404();

            return;
        }
        # Public page not active, go to kutrl 404
        if (!$s->get('srv_local_public')) {
            self::kutrl404();

            return;
        }
        # Validation form
        $url = !empty($_POST['longurl']) ? trim(dcCore::app()->con->escapeStr((string) $_POST['longurl'])) : '';
        if (!empty($url)) {
            $hmf  = !empty($_POST['hmf']) ? $_POST['hmf'] : '!';
            $hmfu = !empty($_POST['hmfp']) ? FrontendUtils::unprotect($_POST['hmfp']) : '?';

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
                        Html::escapeHTML($url),
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
                        Html::escapeHTML($url),
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

        dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), My::path() . '/default-templates');
        self::serveDocument('kutrl.html');
    }

    protected static function kutrl404(): void
    {
        if (!My::settigns()->get('srv_local_404_active')) {
            self::p404();

            return;
        }

        dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), My::path() . '/default-templates');

        header('Content-Type: text/html; charset=UTF-8');
        Http::head(404, 'Not Found');
        dcCore::app()->url->type         = '404';
        dcCore::app()->ctx->current_tpl  = 'kutrl404.html';
        dcCore::app()->ctx->content_type = 'text/html';

        echo dcCore::app()->tpl->getData(dcCore::app()->ctx->current_tpl);

        # --BEHAVIOR-- publicAfterDocumentV2
        dcCore::app()->callBehavior('publicAfterDocumentV2');
        exit;
    }
}
