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

class shorttoKutrlService extends kutrlService
{
    protected $config = [
        'id'   => 'shortto',
        'name' => 'short.to',
        'home' => 'http://short.to',

        'url_api'     => 'http://short.to/s.txt',
        'url_base'    => 'http://short.to/',
        'url_min_len' => 25,
    ];

    public function testService()
    {
        $arg = ['url' => urlencode($this->url_test)];
        if (!self::post($this->url_api, $arg, true, true)) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }

        return true;
    }

    public function createHash($url, $hash = null)
    {
        $arg = ['url' => $url];

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
