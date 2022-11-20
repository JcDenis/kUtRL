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

class suprKutrlService extends kutrlService
{
    protected $config = [
        'id'   => 'supr',
        'name' => 'su.pr StumbleUpon',
        'home' => 'http://su.pr',

        'url_api'     => 'http://su.pr/api/',
        'url_base'    => 'http://su.pr/',
        'url_min_len' => 23,
    ];

    private $args = [
        'version' => '1.0',
        'format'  => 'xml',
        'login'   => '',
        'apiKey'  => '',
    ];

    protected function init()
    {
        $this->args['login']  = $this->settings->kutrl_srv_supr_login;
        $this->args['apiKey'] = $this->settings->kutrl_srv_supr_apikey;
    }

    public function saveSettings()
    {
        $this->settings->put('kutrl_srv_supr_login', $_POST['kutrl_srv_supr_login']);
        $this->settings->put('kutrl_srv_supr_apikey', $_POST['kutrl_srv_supr_apikey']);
    }

    public function settingsForm()
    {
        echo
        '<p><label class="classic">' . __('Login:') . '<br />' .
        form::field(['kutrl_srv_supr_login'], 50, 255, $this->settings->kutrl_srv_supr_login) .
        '</label></p>' .
        '<p class="form-note">' .
        sprintf(__('This is your login to sign up to %s'), $this->config['name']) .
        '</p>' .
        '<p><label class="classic">' . __('API Key:') . '<br />' .
        form::field(['kutrl_srv_supr_apikey'], 50, 255, $this->settings->kutrl_srv_supr_apikey) .
        '</label></p>' .
        '<p class="form-note">' .
        sprintf(__('This is your personnal %s API key. You can find it on your account page.'), $this->config['name']) .
        '</p>';
    }

    public function testService()
    {
        if (empty($this->args['login']) || empty($this->args['apiKey'])) {
            $this->error->add(__('Service is not well configured.'));

            return false;
        }

        $args           = $this->args;
        $arg['longUrl'] = $this->url_test;
        if (!($response = self::post($this->url_api . 'shorten', $args, true))) {
            $this->error->add(__('Failed to call service.'));

            return false;
        }

        $rsp = simplexml_load_string($response);

        $status = (string) $rsp->statusCode;
        if ($status != 'OK') {
            $err_no  = (int) $rsp->errorCode;
            $err_msg = (int) $rsp->errorMessage;
            $this->error->add(sprintf(__('An error occured with code %s and message "%s"'), $err_no, $err_msg));

            return false;
        }

        return true;
    }

    public function createHash($url, $hash = null)
    {
        $args            = $this->args;
        $args['longUrl'] = $url;

        if (!($response = self::post($this->url_api . 'shorten', $args, true))) {
            $this->error->add(__('Failed to call service.'));

            return false;
        }

        $rsp = simplexml_load_string($response);

        $status = (string) $rsp->statusCode;
        if ($status != 'OK') {
            $err_no  = (int) $rsp->errorCode;
            $err_msg = (int) $rsp->errorMessage;
            $this->error->add(sprintf(__('An error occured with code %s and message "%s"'), $err_no, $err_msg));

            return false;
        }

        $rs       = new ArrayObject();
        $rs->hash = (string) $rsp->results[0]->nodeKeyVal->hash;
        $rs->url  = (string) $rsp->results[0]->nodeKeyVal->nodeKey;
        $rs->type = $this->id;

        return $rs;
    }
}
