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

class yourlsKutrlService extends kutrlService
{
    protected $config = [
        'id'   => 'yourls',
        'name' => 'YOURLS',
        'home' => 'http://yourls.org'
    ];

    private $args = [
        'username' => '',
        'password' => '',
        'format'   => 'xml',
        'action'   => 'shorturl'
    ];

    protected function init()
    {
        $this->args['username'] = $this->settings->kutrl_srv_yourls_username;
        $this->args['password'] = $this->settings->kutrl_srv_yourls_password;

        $base = (string) $this->settings->kutrl_srv_yourls_base;
        //if (!empty($base) && substr($base,-1,1) != '/') $base .= '/';

        $this->config['url_api']     = $base;
        $this->config['url_base']    = $base;
        $this->config['url_min_len'] = strlen($base) + 3;
    }

    public function saveSettings()
    {
        $this->settings->put('kutrl_srv_yourls_username', $_POST['kutrl_srv_yourls_username']);
        $this->settings->put('kutrl_srv_yourls_password', $_POST['kutrl_srv_yourls_password']);
        $this->settings->put('kutrl_srv_yourls_base', $_POST['kutrl_srv_yourls_base']);
    }

    public function settingsForm()
    {
        echo
        '<p><label class="classic">' .
        __('Url of the service:') . '<br />' .
        form::field(['kutrl_srv_yourls_base'], 50, 255, $this->settings->kutrl_srv_yourls_base) .
        '</label></p>' .
        '<p class="form-note">' .
        __('This is the URL of the YOURLS service you want to use. Ex: "http://www.smaller.org/api.php".') .
        '</p>' .
        '<p><label class="classic">' . __('Login:') . '<br />' .
        form::field(['kutrl_srv_yourls_username'], 50, 255, $this->settings->kutrl_srv_yourls_username) .
        '</label></p>' .
        '<p class="form-note">' .
        __('This is your user name to sign up to this YOURLS service.') .
        '</p>' .
        '<p><label class="classic">' . __('Password:') . '<br />' .
        form::field(['kutrl_srv_yourls_password'], 50, 255, $this->settings->kutrl_srv_yourls_password) .
        '</label></p>' .
        '<p class="form-note">' .
        __('This is your password to sign up to this YOURLS service.') .
        '</p>';
    }

    public function testService()
    {
        if (empty($this->url_api)) {
            $this->error->add(__('Service is not well configured.'));

            return false;
        }

        $args        = $this->args;
        $args['url'] = $this->url_test;

        if (!($response = self::post($this->url_api, $this->args, true))) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }
        $rsp = @simplexml_load_string($response);

        if ($rsp && $rsp->status == 'success') {
            return true;
        }
        $this->error->add(__('Authentication to service failed.'));

        return false;
    }

    public function createHash($url, $hash = null)
    {
        $args = array_merge($this->args, ['url' => $url]);

        if (!($response = self::post($this->url_api, $args, true))) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }

        $rsp = @simplexml_load_string($response);

        if ($rsp && $rsp->status == 'success') {
            $rs       = new ArrayObject();
            $rs->hash = $rsp->url[0]->keyword;
            $rs->url  = $url;
            $rs->type = $this->id;

            return $rs;
        }
        $this->error->add(__('Unreadable service response.'));

        return false;
    }
}
