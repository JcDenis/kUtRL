<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use Dotclear\App;
use Dotclear\Core\Frontend\Url;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;

/**
 * @brief       kUtRL frontend URL handler.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class FrontendUrl extends Url
{
    # Redirect !!! local !!! service only
    public static function redirectUrl(?string $args): void
    {
        # Not active, go to default 404
        if (!My::settings()->get('active')) {
            self::p404();
        }
        # Not a valid url, go to kutrl 404
        if (!preg_match('#^(|(/(.*?)))$#', (string) $args, $m)) {
            self::kutrl404();

            return;
        }

        $args                                  = $m[3] ?? '';
        App::frontend()->context()->kutrl_msg  = '';
        App::frontend()->context()->kutrl_hmf  = FrontendUtils::create();
        App::frontend()->context()->kutrl_hmfp = FrontendUtils::protect(App::frontend()->context()->kutrl_hmf);

        $kut = new Service\ServiceLocal();

        # Nothing on url
        if ($m[1] == '/') {
            App::frontend()->context()->kutrl_msg = 'No link given.';
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
            //App::frontend()->context()->kutrl_msg = 'Failed to find short link.';
            //self::pageKutrl($kut);
            self::kutrl404();

            return;
        }
        # Removed (empty url), go to kutrl 404
        if (!$url) {
            self::kutrl404();

            return;
        }

        App::blog()->triggerBlog();
        Http::redirect($url . $suffix);
    }

    private static function pageKutrl(Service $kut): void
    {
        $s = My::settings();

        # Not active, go to default 404
        if (!$s->get('active')) {
            self::p404();
        }
        # Public page not active, go to kutrl 404
        if (!$s->get('srv_local_public')) {
            self::kutrl404();

            return;
        }
        # Validation form
        $url = !empty($_POST['longurl']) ? trim(App::con()->escapeStr((string) $_POST['longurl'])) : '';
        if (!empty($url)) {
            $hmf  = !empty($_POST['hmf']) ? $_POST['hmf'] : '!';
            $hmfu = !empty($_POST['hmfp']) ? FrontendUtils::unprotect($_POST['hmfp']) : '?';

            $err = false;
            if ($hmf != $hmfu) {
                $err                                  = true;
                App::frontend()->context()->kutrl_msg = __('Failed to verify protected field.');
            }
            if (!$err) {
                if (!$kut->testService()) {
                    $err                                  = true;
                    App::frontend()->context()->kutrl_msg = __('Service is not well configured.');
                }
            }
            if (!$err) {
                if (!$kut->isValidUrl($url)) {
                    $err                                  = true;
                    App::frontend()->context()->kutrl_msg = __('This string is not a valid URL.');
                }
            }
            if (!$err) {
                if (!$kut->isLongerUrl($url)) {
                    $err                                  = true;
                    App::frontend()->context()->kutrl_msg = __('This link is too short.');
                }
            }
            if (!$err) {
                if (!$kut->isProtocolUrl($url)) {
                    $err                                  = true;
                    App::frontend()->context()->kutrl_msg = __('This type of link is not allowed.');
                }
            }

            if (!$err) {
                if (!$kut->get('allow_external_url') && !$kut->isBlogUrl($url)) {
                    $err                                  = true;
                    App::frontend()->context()->kutrl_msg = __('Short links are limited to this blog URL.');
                }
            }
            if (!$err) {
                if ($kut->isServiceUrl($url)) {
                    $err                                  = true;
                    App::frontend()->context()->kutrl_msg = __('This link is already a short link.');
                }
            }
            if (!$err) {
                if (false !== ($rs = $kut->isKnowUrl($url))) {
                    $err = true;

                    $url     = $rs->url;
                    $new_url = $kut->get('url_base') . $rs->hash;

                    App::frontend()->context()->kutrl_msg = sprintf(
                        __('Short link for %s is %s'),
                        Html::escapeHTML($url),
                        '<a href="' . $new_url . '">' . $new_url . '</a>'
                    );
                }
            }
            if (!$err) {
                if (false === ($rs = $kut->hash($url))) {
                    $err                                  = true;
                    App::frontend()->context()->kutrl_msg = __('Failed to create short link.');
                } else {
                    $url     = $rs->url;
                    $new_url = $kut->get('url_base') . $rs->hash;

                    App::frontend()->context()->kutrl_msg = sprintf(
                        __('Short link for %s is %s'),
                        Html::escapeHTML($url),
                        '<a href="' . $new_url . '">' . $new_url . '</a>'
                    );
                    App::blog()->triggerBlog();

                    # ex: Send new url to messengers
                    if (!$rs->isEmpty()) {
                        App::behavior()->callBehavior('publicAfterKutrlCreate', $rs, __('New public short URL'));
                    }
                }
            }
        }

        App::frontend()->template()->appendPath(My::path() . '/default-templates');
        self::serveDocument('kutrl.html');
    }

    protected static function kutrl404(): void
    {
        if (!My::settings()->get('srv_local_404_active')) {
            self::p404();
        }

        App::frontend()->template()->appendPath(My::path() . '/default-templates');

        header('Content-Type: text/html; charset=UTF-8');
        Http::head(404, 'Not Found');
        App::url()->type                         = '404';
        App::frontend()->context()->current_tpl  = 'kutrl404.html';
        App::frontend()->context()->content_type = 'text/html';

        echo App::frontend()->template()->getData(App::frontend()->context()->current_tpl);

        # --BEHAVIOR-- publicAfterDocumentV2
        App::behavior()->callBehavior('publicAfterDocumentV2');
        exit;
    }
}
