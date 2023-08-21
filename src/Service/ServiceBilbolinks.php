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

class ServiceBilbolinks extends Service
{
    protected $config = [
        'id'   => 'bilbolinks',
        'name' => 'BilboLinks',
        'home' => 'http://www.tux-planet.fr/bilbobox/',
    ];

    protected function init(): void
    {
        $base = (string) $this->settings->get('srv_bilbolinks_base');
        if (!empty($base) && substr($base, -1, 1) != '/') {
            $base .= '/';
        }
        $this->config['url_api']     = $base . 'api.php';
        $this->config['url_base']    = $base;
        $this->config['url_min_len'] = 25;
    }

    public function saveSettings(): void
    {
        $base = '';
        if (!empty($_POST['kutrl_srv_bilbolinks_base'])) {
            $base = $_POST['kutrl_srv_bilbolinks_base'];
            if (substr($base, -1, 1) != '/') {
                $base .= '/';
            }
        }
        $this->settings->put('srv_bilbolinks_base', $base);
    }

    public function settingsForm(): Div
    {
        return (new Div())
            ->items([
                (new Para())
                    ->items([
                        (new Label(__('Url of the service:'), Label::OUTSIDE_LABEL_BEFORE))
                            ->for('kutrl_srv_bilbolinks_base'),
                        (new Input('kutrl_srv_bilbolinks_base'))
                            ->size(50)
                            ->maxlenght(255)
                            ->value((string) $this->settings->get('srv_bilbolinks_base')),
                    ]),
                (new Note())
                    ->class('form-note')
                    ->text(__('This is the root URL of the "bilbolinks" service you want to use. Ex: "http://tux-pla.net/".')),
            ]);
    }

    public function testService(): bool
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

    public function createHash(string $url, ?string $hash = null)
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
