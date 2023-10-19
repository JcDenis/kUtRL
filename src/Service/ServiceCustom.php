<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL\Service;

use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Input,
    Label,
    Note,
    Para
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
    protected $config = [
        'id'   => 'custom',
        'name' => 'Custom',
    ];

    protected function init(): void
    {
        $config = json_decode((string) $this->settings->get('srv_custom'), true);
        if (!is_array($config)) {
            $config = [];
        }

        $this->config['url_api']    = $config['url_api']   ?? '';
        $this->config['url_base']   = $config['url_base']  ?? '';
        $this->config['url_param']  = $config['url_param'] ?? '';
        $this->config['url_encode'] = !empty($config['url_api']);

        $this->config['url_min_length'] = strlen($this->url_base) + 2;
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
                (new Para())
                    ->text(
                        __('You can set a configurable service.') . '<br />' .
                        __('It consists on a simple query to an URL with only one param.') . '<br />' .
                        __('It must respond with a http code 200 on success.') . '<br />' .
                        __('It must returned the short URL (or only hash) in clear text.')
                    ),
                (new Para())
                    ->items([
                        (new Label(__('API URL:'), Label::OUTSIDE_LABEL_BEFORE))
                            ->for('kutrl_srv_custom_url_api'),
                        (new Input('kutrl_srv_custom_url_api'))
                            ->size(50)
                            ->maxlenght(255)
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
                            ->maxlenght(255)
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
                            ->maxlenght(255)
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

    public function createHash(string $url, ?string $hash = null)
    {
        $enc = $this->url_encode ? urlencode($url) : $url;
        $arg = [$this->url_param => $enc];

        if (!($response = self::post($this->url_api, $arg, true, true))) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }
        
        return $this->fromValue(
            str_replace($this->url_base, '', $response),
            $url,
            $this->id
        );
    }
}
