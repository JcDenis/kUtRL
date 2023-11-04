<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL\Service;

use Dotclear\Helper\Html\Form\{
    Div,
    Input,
    Label,
    Note,
    Para
};
use Dotclear\Plugin\kUtRL\Service;

/**
 * @brief       kUtRL bilbolinks service class.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ServiceBilbolinks extends Service
{
    protected function init(): void
    {
        $base = (string) $this->settings->get('srv_bilbolinks_base');
        if (!empty($base) && substr($base, -1, 1) != '/') {
            $base .= '/';
        }

        $this->config = [
            'id'   => 'bilbolinks',
            'name' => 'BilboLinks',
            'home' => 'http://www.tux-planet.fr/bilbobox/',

            'url_api'     => $base . 'api.php',
            'url_base'    => $base,
            'url_min_len' => 25,
        ];
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
                            ->maxlength(255)
                            ->value((string) $this->settings->get('srv_bilbolinks_base')),
                    ]),
                (new Note())
                    ->class('form-note')
                    ->text(__('This is the root URL of the "bilbolinks" service you want to use. Ex: "http://tux-pla.net/".')),
            ]);
    }

    public function testService(): bool
    {
        if (empty($this->get('url_base'))) {
            $this->error->add(__('Service is not well configured.'));

            return false;
        }

        $arg = ['longurl' => urlencode($this->get('url_test'))];
        if (!self::post($this->get('url_api'), $arg, true, true)) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }

        return true;
    }

    public function createHash(string $url, ?string $hash = null)
    {
        $arg = ['longurl' => $url];

        if (!($response = self::post($this->get('url_api'), $arg, true, true))) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }
        if ($response == 'You are too speed!') {
            $this->error->add(__('Service rate limit exceeded.'));

            return false;
        }

        return $this->fromValue(
            (string) str_replace($this->get('url_base'), '', $response),
            $url,
            $this->get('id')
        );
    }
}
