<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL\Service;

use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Input,
    Label,
    Note,
    Para,
    Text
};
use Dotclear\Plugin\kUtRL\Service;

/**
 * @brief       kUtRL custom service class.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ServiceCustom extends Service
{
    protected function init(): void
    {
        $config = json_decode((string) $this->settings->get('srv_custom'), true);
        if (!is_array($config)) {
            $config = [];
        }

        $this->config = [
            'id'   => 'custom',
            'name' => 'Custom',

            'url_api'    => $config['url_api']   ?? '',
            'url_base'   => $config['url_base']  ?? '',
            'url_param'  => $config['url_param'] ?? '',
            'url_encode' => !empty($config['url_api']),

            'url_min_length' => strlen($config['url_base'] ?? '') + 2,
        ];
    }

    public function saveSettings(): void
    {
        $config = [
            'url_api'    => $_POST['kutrl_srv_custom_url_api'],
            'url_base'   => $_POST['kutrl_srv_custom_url_base'],
            'url_param'  => $_POST['kutrl_srv_custom_url_param'],
            'url_encode' => !empty($_POST['kutrl_srv_custom_url_encode']),
        ];
        $this->settings->put('srv_custom', json_encode($config));
    }

    public function settingsForm(): Div
    {
        $default = [
            'url_api'    => '',
            'url_base'   => '',
            'url_param'  => '',
            'url_encode' => true,
        ];
        $config = json_decode((string) $this->settings->get('srv_custom'), true);
        if (!is_array($config)) {
            $config = [];
        }
        $config = array_merge($default, $config);

        return (new Div())
            ->items([
                (new Text(
                    'p',
                    __('You can set a configurable service.') . '<br />' .
                    __('It consists on a simple query to an URL with only one param.') . '<br />' .
                    __('It must respond with a http code 200 on success.') . '<br />' .
                    __('It must returned the short URL (or only hash) in clear text.')
                )),
                (new Para())
                    ->items([
                        (new Label(__('API URL:'), Label::OUTSIDE_LABEL_BEFORE))
                            ->for('kutrl_srv_custom_url_api'),
                        (new Input('kutrl_srv_custom_url_api'))
                            ->size(50)
                            ->maxlength(255)
                            ->value((string) $config['url_api']),
                    ]),
                (new Note())
                    ->class('form-note')
                    ->text(__('Full path to API of the URL shortener. ex: "http://is.gd/api.php"')),
                (new Para())
                    ->items([
                        (new Label(__('Short URL domain:'), Label::OUTSIDE_LABEL_BEFORE))
                            ->for('kutrl_srv_custom_url_base'),
                        (new Input('kutrl_srv_custom_url_base'))
                            ->size(50)
                            ->maxlength(255)
                            ->value((string) $config['url_base']),
                    ]),
                (new Note())
                    ->class('form-note')
                    ->text(__('Common part of the short URL. ex: "http://is.gd/"')),
                (new Para())
                    ->items([
                        (new Label(__('API URL param:'), Label::OUTSIDE_LABEL_BEFORE))
                            ->for('kutrl_srv_custom_url_param'),
                        (new Input('kutrl_srv_custom_url_param'))
                            ->size(50)
                            ->maxlength(255)
                            ->value((string) $config['url_param']),
                    ]),
                (new Note())
                    ->class('form-note')
                    ->text(__('Param of the query. ex: "longurl"')),
                (new Para())
                    ->items([
                        (new Checkbox('kutrl_srv_custom_url_encode', (bool) $config['url_encode']))
                            ->value(1),
                        (new Label(__('Encode URL'), Label::OUTSIDE_LABEL_AFTER))
                            ->class('classic')
                            ->for('kutrl_srv_custom_url_encode'),
                    ]),
            ]);
    }

    public function testService(): bool
    {
        if (empty($this->get('url_api'))) {
            return false;
        }
        $url = $this->get('url_encode') ? urlencode($this->get('url_test')) : $this->get('url_test');
        $arg = [$this->get('url_param') => $url];
        if (!self::post($this->get('url_api'), $arg, true, true)) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }

        return true;
    }

    public function createHash(string $url, ?string $hash = null)
    {
        $enc = $this->get('url_encode') ? urlencode($url) : $url;
        $arg = [$this->get('url_param') => $enc];

        if (!($response = self::post($this->get('url_api'), $arg, true, true))) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }

        return $this->fromValue(
            (string) str_replace($this->get('url_base'), '', $response),
            $url,
            $this->get('id')
        );
    }
}
