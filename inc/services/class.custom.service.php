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

class customKutrlService extends kutrlService
{
    protected $config = [
        'id'   => 'custom',
        'name' => 'Custom'
    ];

    protected function init()
    {
        $config = unserialize(base64_decode($this->settings->kutrl_srv_custom));
        if (!is_array($config)) {
            $config = [];
        }

        $this->config['url_api']    = !empty($config['url_api']) ? $config['url_api'] : '';
        $this->config['url_base']   = !empty($config['url_base']) ? $config['url_base'] : '';
        $this->config['url_param']  = !empty($config['url_param']) ? $config['url_param'] : '';
        $this->config['url_encode'] = !empty($config['url_api']);

        $this->config['url_min_length'] = strlen($this->url_base) + 2;
    }

    public function saveSettings()
    {
        $config = [
            'url_api'    => $_POST['kutrl_srv_custom_url_api'],
            'url_base'   => $_POST['kutrl_srv_custom_url_base'],
            'url_param'  => $_POST['kutrl_srv_custom_url_param'],
            'url_encode' => !empty($_POST['kutrl_srv_custom_url_encode'])
        ];
        $this->settings->put('kutrl_srv_custom', base64_encode(serialize($config)));
    }

    public function settingsForm()
    {
        $default = [
            'url_api'    => '',
            'url_base'   => '',
            'url_param'  => '',
            'url_encode' => true
        ];
        $config = unserialize(base64_decode($this->settings->kutrl_srv_custom));
        if (!is_array($config)) {
            $config = [];
        }
        $config = array_merge($default, $config);

        echo
        '<p>' . __('You can set a configurable service.') . '<br />' .
        __('It consists on a simple query to an URL with only one param.') . '<br />' .
        __('It must respond with a http code 200 on success.') . '<br />' .
        __('It must returned the short URL (or only hash) in clear text.') . '</p>' .
        '<p><label class="classic">' . __('API URL:') . '<br />' .
        form::field(['kutrl_srv_custom_url_api'], 50, 255, $config['url_api']) .
        '</label></p>' .
        '<p class="form-note">' . __('Full path to API of the URL shortener. ex: "http://is.gd/api.php"') . '</p>' .
        '<p><label class="classic">' . __('Short URL domain:') . '<br />' .
        form::field(['kutrl_srv_custom_url_base'], 50, 255, $config['url_base']) .
        '</label></p>' .
        '<p class="form-note">' . __('Common part of the short URL. ex: "http://is.gd/"') . '</p>' .
        '<p><label class="classic">' . __('API URL param:') . '<br />' .
        form::field(['kutrl_srv_custom_url_param'], 50, 255, $config['url_param']) .
        '</label></p>' .
        '<p class="form-note">' . __('Param of the query. ex: "longurl"') . '</p>' .
        '<p><label class="classic">' .
        form::checkbox(['kutrl_srv_custom_url_encode'], '1', $config['url_encode']) . ' ' .
        __('Encode URL') .
        '</label></p>';
    }

    public function testService()
    {
        if (empty($this->url_api)) {
            return false;
        }
        $url = $this->url_encode ? urlencode($this->url_test) : $this->url_test;
        $arg = [$this->url_param => $url];
        if (!self::post($this->url_api, $arg, true, true)) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }

        return true;
    }

    public function createHash($url, $hash = null)
    {
        $enc = $this->url_encode ? urlencode($url) : $url;
        $arg = [$this->url_param => $enc];

        if (!($response = self::post($this->url_api, $arg, true, true))) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }
        $rs       = new ArrayObject();
        $rs->hash = str_replace($this->url_base, '', $response);
        $rs->url  = $url;
        $rs->type = $this->id;

        return $rs;
    }
}
