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
    return;
}

class trimKutrlService extends kutrlService
{
    protected $config = [
        'id'   => 'trim',
        'name' => 'tr.im',
        'home' => 'http://tr.im',

        'url_api'     => 'http://api.tr.im/v1/',
        'url_base'    => 'http://tr.im/',
        'url_min_len' => 25,
    ];

    private $args = [
        'username' => '',
        'password' => '',
    ];

    private $api_rate_time = 0;

    protected function init()
    {
        $this->args['username'] = $this->settings->kutrl_srv_trim_username;
        $this->args['password'] = $this->settings->kutrl_srv_trim_password;

        $this->api_rate_time = (int) $this->settings->kutrl_srv_trim_apiratetime;
    }

    public function saveSettings()
    {
        $this->settings->put('kutrl_srv_trim_username', $_POST['kutrl_srv_trim_username']);
        $this->settings->put('kutrl_srv_trim_password', $_POST['kutrl_srv_trim_password']);
    }

    public function settingsForm()
    {
        echo
        '<p><label class="classic">' . __('Login:') . '<br />' .
        form::field(['kutrl_srv_trim_username'], 50, 255, $this->settings->kutrl_srv_trim_username) .
        '</label></p>' .
        '<p class="form-note">' .
        __('This is your login to sign up to tr.im.') .
        '</p>' .
        '<p><label class="classic">' . __('Password:') . '<br />' .
        form::field(['kutrl_srv_trim_password'], 50, 255, $this->settings->kutrl_srv_trim_password) .
        '</label></p>' .
        '<p class="form-note">' .
        __('This is your password to sign up to tr.im.') .
        '</p>';
    }

    public function testService()
    {
        if (empty($this->args['username']) || empty($this->args['password'])) {
            $this->error->add(__('Service is not well configured.'));

            return false;
        }
        if (time() < $this->api_rate_time + 300) { // bloc service within 5min on API rate limit
            $this->error->add(__('Prevent service rate limit.'));

            return false;
        }
        if (!($rsp = self::post($this->url_api . 'verify.xml', $this->args, true, true))) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }
        $r = simplexml_load_string($rsp);

        if ($r['code'] == 200) {
            return true;
        }
        $this->error->add(__('Authentication to service failed.'));

        return false;
    }

    public function createHash($url, $hash = null)
    {
        $arg        = $this->args;
        $arg['url'] = $url;

        if (!($rsp = self::post($this->url_api . 'trim_url.xml', $arg, true, true))) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }

        $r = simplexml_load_string($rsp);

        # API rate limit
        if ($r['code'] == 425) {
            $this->settings->put('kutrl_srv_trim_apiratetime', time());

            $this->error->add(__('Service rate limit exceeded.'));

            return false;
        }
        if (isset($r->trimpath)) {
            $rs       = new ArrayObject();
            $rs->hash = $r->trimpath;
            $rs->url  = $url;
            $rs->type = $this->id;

            return $rs;
        }
        $this->error->add(__('Unreadable service response.'));

        return false;
    }
}
