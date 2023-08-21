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

namespace Dotclear\Plugin\kUtRL\Service;

use ArrayObject;
use Dotclear\Helper\Html\Form\{
    Div,
    Input,
    label,
    Note,
    Para
};
use Dotclear\Plugin\kUtRL\Service;

class ServiceBitly extends Service
{
    protected $config = [
        'id'   => 'bitly',
        'name' => 'bit.ly',
        'home' => 'https://bit.ly',

        'url_api'     => 'https://api-ssl.bitly.com/v4/',
        'url_base'    => 'https://bit.ly/',
        'url_min_len' => 25,

        'allow_protocols' => ['http://', 'https://'],
    ];

    private $args = [
        'apiKey' => '',
    ];

    protected function init(): void
    {
        $this->args['apiKey'] = $this->settings->get('srv_bitly_apikey');
    }

    public function saveSettings(): void
    {
        $this->settings->put('srv_bitly_apikey', $_POST['kutrl_srv_bitly_apikey']);
    }

    public function settingsForm(): Div
    {
        return (new Div())
            ->items([
                (new Para())
                    ->items([
                        (new Label(__('API Key:'), Label::OUTSIDE_LABEL_BEFORE))
                            ->for('kutrl_srv_bitly_apikey'),
                        (new Input('kutrl_srv_bitly_apikey'))
                            ->size(50)
                            ->maxlenght(255)
                            ->value((string) $this->settings->get('srv_bitly_apikey')),
                    ]),
                (new Note())
                    ->class('form-note')
                    ->text(sprintf(__('This is your personnal %s API key. You can find it on your account page.'), $this->config['name'])),
            ]);
    }

    public function testService(): bool
    {
        if (empty($this->args['apiKey'])) {
            $this->error->add(__('Service is not well configured.'));

            return false;
        }

        $args = json_encode(['domain' => 'bit.ly', 'bitlink_id' => 'bit.ly/WP9vc'], JSON_UNESCAPED_SLASHES);
        if (!($response = self::post($this->url_api . 'expand', $args, true, false, $this->headers()))) {
            $this->error->add(__('Failed to call service.'));

            return false;
        }

        return true;
    }

    public function createHash(string $url, ?string $hash = null)
    {
        $args = json_encode(['domain' => 'bit.ly', 'long_url' => $url]);

        if (!($response = self::post($this->url_api . 'shorten', $args, true, false, $this->headers()))) {
            $this->error->add(__('Failed to call service.'));

            return false;
        }

        $rsp = json_decode($response);

        $rs       = new ArrayObject();
        $rs->hash = str_replace($this->url_base, '', (string) $rsp->link);
        $rs->url  = (string) $rsp->long_url;
        $rs->type = $this->id;

        return $rs;
    }

    private function headers()
    {
        return ['Authorization: Bearer ' . $this->args['apiKey'], 'Content-Type: application/json'];
    }
}
