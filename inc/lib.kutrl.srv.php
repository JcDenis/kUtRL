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
# Generic class for shorten link service
# A service class must extends this one
class kutrlService
{
    public $error;
    public $settings;
    public $log;

    protected $config = [];

    public function __construct()
    {
        $this->settings = dcCore::app()->blog->settings->get(basename(dirname(__DIR__)));
        $this->log      = new kutrlLog();
        $this->error    = new dcError();
        //$this->error->setHTMLFormat('%s', "%s\n");

        $this->init();

        // Force setting
        $allow_external_url                  = $this->settings->get('allow_external_url');
        $this->config['$allow_external_url'] = null === $allow_external_url ?
            true : $allow_external_url;

        $this->config = array_merge(
            [
                'id'                 => 'undefined',
                'name'               => 'undefined',
                'home'               => '',

                'allow_external_url' => true,
                'allow_custom_hash'  => false,
                'allow_protocols'    => ['http://'],

                'url_test'           => 'http://github.com/JcDenis/kUtRL/releases',
                'url_api'            => '',
                'url_base'           => '',
                'url_min_len'        => 0,
            ],
            $this->config
        );
    }

    # Magic get for config values
    public function __get($k)
    {
        return $this->get($k);
    }

    # get config value
    public function get($k)
    {
        return $this->config[$k] ?? null;
    }

    # Additionnal actions on child start
    protected function init()
    {
        //
    }

    # Save settings from admin page
    public function saveSettings()
    {
        return null;
    }

    # Settings form for admin page
    public function settingsForm()
    {
        echo
        '<p class="form-note">' .
        __('There is nothing to configure for this service.') .
        '</p>';
    }

    # Test if service is well configured
    public function testService()
    {
        return null;
    }

    # Test if an url is valid
    public function isValidUrl($url)
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }

    # Test if an url contents know prefix
    public function isServiceUrl($url)
    {
        return strpos($url, $this->url_base) === 0;
    }

    # Test if an url is long enoutgh
    public function isLongerUrl($url)
    {
        return (strlen($url) >= (int) $this->url_min_len);
    }

    # Test if an url protocol (eg: http://) is allowed
    public function isProtocolUrl($url)
    {
        foreach ($this->allow_protocols as $protocol) {
            if (empty($protocol)) {
                continue;
            }
            if (strpos($url, $protocol) === 0) {
                return true;
            }
        }

        return false;
    }

    # Test if an url is from current blog
    public function isBlogUrl($url)
    {
        $base = dcCore::app()->blog->url;
        $url  = substr($url, 0, strlen($base));

        return $url == $base;
    }

    # Test if an url is know
    public function isKnowUrl($url)
    {
        return $this->log->select($url, null, $this->id, 'kutrl');
    }

    # Test if an custom short url is know
    public function isKnowHash($hash)
    {
        return $this->log->select(null, $hash, $this->id, 'kutrl');
    }

    # Create hash from url
    public function hash($url, $hash = null)
    {
        $url = trim(dcCore::app()->con->escape($url));
        if ('undefined' === $this->id) {
            return false;
        }
        if ($hash && !$this->allow_custom_hash) {
            return false;
        }
        if ($this->isServiceUrl($url)) {
            return false;
        }
        if (!$this->isLongerUrl($url)) {
            return false;
        }
        if (!$this->allow_external_url && $this->isBlogUrl($url)) {
            return false;
        }
        if ($hash && false !== ($rs = $this->isKnowHash($hash))) {
            return false;
        }
        if (false === ($rs = $this->isKnowUrl($url))) {
            if (false === ($rs = $this->createHash($url, $hash))) {
                return false;
            }

            $this->log->insert($rs->url, $rs->hash, $rs->type, 'kutrl');
            dcCore::app()->blog->triggerBlog();

            # --BEHAVIOR-- kutrlAfterCreateShortUrl
            dcCore::app()->callBehavior('kutrlAfterCreateShortUrl', $rs);
        }

        return $rs;
    }

    # Create a hash for a given url (and its custom hash)
    public function createHash($url, $hash = null)
    {
        return false;
    }

    # Remove an url from list of know urls
    public function remove($url)
    {
        if (!($rs = $this->isKnowUrl($url))) {
            return false;
        }
        $this->deleteUrl($url);
        $this->log->delete($rs->id);

        return true;
    }

    # Delete url on service (second argument really delete urls)
    public function deleteUrl($url, $delete = false)
    {
        return null;
    }

    # Retrieve long url from hash
    public function getUrl($hash)
    {
        return false;
    }

    # Post request
    public static function post($server, $data, $verbose = true, $get = false, $headers = [])
    {
        $url    = (string) $server;
        $client = netHttp::initClient($url, $url);
        $client->setUserAgent('kUtRL - https://github.com/JcDenis/kUtRL');
        $client->setPersistReferers(false);

        if (is_array($headers) && !empty($headers)) {
            foreach ($headers as $header) {
                $client->setMoreHeader($header);
            }
        }
        if ($get) {
            $client->get($url, $data);
        } else {
            $client->post($url, $data);
        }
        if (!$verbose && $client->getStatus() != 200) {
            return false;
        }
        if ($verbose) {
            return $client->getContent();
        }

        return true;
    }
}
