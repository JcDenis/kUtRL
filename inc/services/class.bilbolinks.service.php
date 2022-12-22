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

class bilbolinksKutrlService extends kutrlService
{
    protected $config = [
        'id'   => 'bilbolinks',
        'name' => 'BilboLinks',
        'home' => 'http://www.tux-planet.fr/bilbobox/',
    ];

    protected function init()
    {
        $base = (string) $this->settings->get('kutrl_srv_bilbolinks_base');
        if (!empty($base) && substr($base, -1, 1) != '/') {
            $base .= '/';
        }
        $this->config['url_api']     = $base . 'api.php';
        $this->config['url_base']    = $base;
        $this->config['url_min_len'] = 25;
    }

    public function saveSettings()
    {
        $base = '';
        if (!empty($_POST['kutrl_srv_bilbolinks_base'])) {
            $base = $_POST['kutrl_srv_bilbolinks_base'];
            if (substr($base, -1, 1) != '/') {
                $base .= '/';
            }
        }
        $this->settings->put('kutrl_srv_bilbolinks_base', $base);
    }

    public function settingsForm()
    {
        echo
        '<p><label class="classic">' .
        __('Url of the service:') . '<br />' .
        form::field(['kutrl_srv_bilbolinks_base'], 50, 255, $this->settings->get('kutrl_srv_bilbolinks_base')) .
        '</label></p>' .
        '<p class="form-note">' .
        __('This is the root URL of the "bilbolinks" service you want to use. Ex: "http://tux-pla.net/".') .
        '</p>';
    }

    public function testService()
    {
        if (empty($this->url_base)) {
            $this->error->add(__('Service is not well configured.'));

            return false;
        }

        $arg = ['longurl' => urlencode($this->url_test)];
        if (!self::post($this->url_api, $arg, true, true)) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }

        return true;
    }

    public function createHash($url, $hash = null)
    {
        $arg = ['longurl' => $url];

        if (!($response = self::post($this->url_api, $arg, true, true))) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }
        if ($response == 'You are too speed!') {
            $this->error->add(__('Service rate limit exceeded.'));

            return false;
        }
        $rs       = new ArrayObject();
        $rs->hash = str_replace($this->url_base, '', $response);
        $rs->url  = $url;
        $rs->type = $this->id;

        return $rs;
    }
}
