<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\{
    Div,
    Note
};
use Dotclear\Helper\Network\HttpClient;

/**
 * @brief       kUtRL service class.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Service
{
    /**
     * @var     \Dotclear\Interface\Core\ErrorInterface  $error
     */
    public $error;

    /**
     * @var     \Dotclear\Interface\Core\BlogWorkspaceInterface   $settings
     */
    public $settings;

    /**
     * @var     Logs    $log
     */
    public $log;

    /**
     * @var     array<string, mixed>    $config
     */
    protected $config = [];

    public function __construct()
    {
        $this->settings = My::settings();
        $this->log      = new Logs();
        $this->error    = App::error();
        //$this->error->setHTMLFormat('%s', "%s\n");

        $this->init();

        // Force setting
        $allow_external_url                 = $this->settings->get('allow_external_url');
        $this->config['allow_external_url'] = null === $allow_external_url ?
            true : $allow_external_url;

        $this->config = array_merge(
            [
                'id'   => 'undefined',
                'name' => 'undefined',
                'home' => '',

                'allow_external_url' => true,
                'allow_custom_hash'  => false,
                'allow_protocols'    => ['http://'],

                'url_test'    => 'http://github.com/JcDenis/kUtRL/releases',
                'url_api'     => '',
                'url_base'    => '',
                'url_min_len' => 0,
            ],
            $this->config
        );
    }

    /**
     * Magic get for config values.
     *
     * @return  mixed
     */
    public function __get(string $k)
    {
        return $this->get($k);
    }

    /**
     * Get config value.
     *
     * @return  mixed
     */
    public function get(string $k)
    {
        return $this->config[$k] ?? null;
    }

    # Additionnal actions on child start
    protected function init(): void
    {
        //
    }

    # Save settings from admin page
    public function saveSettings(): void
    {
        //
    }

    # Settings form for admin page
    public function settingsForm(): Div
    {
        return (new Div())
            ->items([
                (new Note())
                    ->class('form-note')
                    ->text(__('There is nothing to configure for this service.')),
            ]);
    }

    # Test if service is well configured
    public function testService(): bool
    {
        return true;
    }

    # Test if an url is valid
    public function isValidUrl(string $url): bool
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }

    # Test if an url contents know prefix
    public function isServiceUrl(string $url): bool
    {
        return strpos($url, $this->get('url_base')) === 0;
    }

    # Test if an url is long enoutgh
    public function isLongerUrl(string $url): bool
    {
        return (strlen($url) >= (int) $this->get('url_min_len'));
    }

    # Test if an url protocol (eg: http://) is allowed
    public function isProtocolUrl(string $url): bool
    {
        foreach ($this->get('allow_protocols') as $protocol) {
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
    public function isBlogUrl(string $url): bool
    {
        $base = App::blog()->url();
        $url  = substr($url, 0, strlen($base));

        return $url == $base;
    }

    /**
     * Test if an url is know.
     *
     * @return  false|MetaRecord
     */
    public function isKnowUrl(string $url)
    {
        return $this->log->select($url, null, $this->get('id'), 'kutrl');
    }

    /**
     * Test if an custom short url is know.
     *
     * @return  false|MetaRecord
     */
    public function isKnowHash(string $hash)
    {
        return $this->log->select(null, $hash, $this->get('id'), 'kutrl');
    }

    /**
     * Create hash from url.
     *
     * @return  false|MetaRecord
     */
    public function hash(string $url, ?string $hash = null)
    {
        $url = trim(App::con()->escapeStr((string) $url));
        if ('undefined' === $this->get('id')) {
            return false;
        }
        if ($hash && !$this->get('allow_custom_hash')) {
            return false;
        }
        if ($this->isServiceUrl($url)) {
            return false;
        }
        if (!$this->isLongerUrl($url)) {
            return false;
        }
        if (!$this->get('allow_external_url') && $this->isBlogUrl($url)) {
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
            App::blog()->triggerBlog();

            # --BEHAVIOR-- kutrlAfterCreateShortUrl
            App::behavior()->callBehavior('kutrlAfterCreateShortUrl', $rs);
        }

        return $rs;
    }

    /**
     * Create a hash for a given url (and its custom hash).
     *
     * @return  false|MetaRecord
     */
    public function createHash(string $url, ?string $hash = null)
    {
        return false;
    }

    /**
     * Get a shorlink record from values.
     *
     * @param   string  $hash   The hash
     * @param   string  $url    The url
     * @param   string  $type   The type
     *
     * @return  MetaRecord  The link description record
     */
    public function fromValue(string $hash, string $url, string $type): MetaRecord
    {
        return MetaRecord::newFromArray([['hash' => $hash, 'url' => $url, 'type' => $type]]);
    }

    /**
     * Remove an url from list of know urls.
     */
    public function remove(string $url): bool
    {
        if (!($rs = $this->isKnowUrl($url))) {
            return false;
        }
        $this->deleteUrl($url);
        $this->log->delete((int) $rs->id);

        return true;
    }

    /**
     * Delete url on service (second argument really delete urls).
     */
    public function deleteUrl(string $url, bool $delete = false): bool
    {
        return false;
    }

    /**
     * Retrieve long url from hash.
     *
     * @return  false|string
     */
    public function getUrl(string $hash)
    {
        return false;
    }

    /**
     * Post request.
     *
     * @param   array<int, string>  $headers
     * @return  mixed
     */
    public static function post(string $url, mixed $data, bool $verbose = true, bool $get = false, array $headers = [])
    {
        $client = HttpClient::initClient($url, $url);
        if (false === $client) {
            return false;
        }

        $client->setUserAgent('kUtRL - https://github.com/JcDenis/kUtRL');
        $client->setPersistReferers(false);

        if (!empty($headers)) {
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
