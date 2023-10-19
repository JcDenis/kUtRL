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
 * @brief       kUtRL yourls service class.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ServiceYourls extends Service
{
    protected $config = [
        'id'   => 'yourls',
        'name' => 'YOURLS',
        'home' => 'http://yourls.org',
    ];

    private $args = [
        'username' => '',
        'password' => '',
        'format'   => 'xml',
        'action'   => 'shorturl',
    ];

    protected function init(): void
    {
        $this->args['username'] = $this->settings->get('srv_yourls_username');
        $this->args['password'] = $this->settings->get('srv_yourls_password');

        $base = (string) $this->settings->get('srv_yourls_base');
        //if (!empty($base) && substr($base,-1,1) != '/') $base .= '/';

        $this->config['url_api']     = $base;
        $this->config['url_base']    = $base;
        $this->config['url_min_len'] = strlen($base) + 3;
    }

    public function saveSettings(): void
    {
        $this->settings->put('srv_yourls_username', $_POST['kutrl_srv_yourls_username']);
        $this->settings->put('srv_yourls_password', $_POST['kutrl_srv_yourls_password']);
        $this->settings->put('srv_yourls_base', $_POST['kutrl_srv_yourls_base']);
    }

    public function settingsForm(): Div
    {
        return (new Div())
            ->items([
                (new Para())
                    ->items([
                        (new Label(__('Url of the service:'), Label::OUTSIDE_LABEL_BEFORE))
                            ->for('kutrl_srv_yourls_base'),
                        (new Input('kutrl_srv_yourls_base'))
                            ->size(50)
                            ->maxlenght(255)
                            ->value((string) $this->settings->get('srv_yourls_base')),
                        (new Note())
                            ->class('form-note')
                            ->text(__('This is the URL of the YOURLS service you want to use. Ex: "http://www.smaller.org/api.php".')),
                    ]),
                (new Para())
                    ->items([
                        (new Label(__('Login:'), Label::OUTSIDE_LABEL_BEFORE))
                            ->for('kutrl_srv_yourls_username'),
                        (new Input('kutrl_srv_yourls_username'))
                            ->size(50)
                            ->maxlenght(255)
                            ->value((string) $this->settings->get('srv_yourls_username')),
                        (new Note())
                            ->class('form-note')
                            ->text(__('This is your user name to sign up to this YOURLS service.')),
                    ]),
                (new Para())
                    ->items([
                        (new Label(__('Password:'), Label::OUTSIDE_LABEL_BEFORE))
                            ->for('kutrl_srv_yourls_password'),
                        (new Input('kutrl_srv_yourls_password'))
                            ->size(50)
                            ->maxlenght(255)
                            ->value((string) $this->settings->get('srv_yourls_password')),
                        (new Note())
                            ->class('form-note')
                            ->text(__('This is your password to sign up to this YOURLS service.')),
                    ]),

            ]);
    }

    public function testService(): bool
    {
        if (empty($this->url_api)) {
            $this->error->add(__('Service is not well configured.'));

            return false;
        }

        $args        = $this->args;
        $args['url'] = $this->url_test;

        if (!($response = self::post($this->url_api, $this->args, true))) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }
        $rsp = @simplexml_load_string($response);

        if ($rsp && $rsp->status == 'success') {
            return true;
        }
        $this->error->add(__('Authentication to service failed.'));

        return false;
    }

    public function createHash(string $url, ?string $hash = null)
    {
        $args = array_merge($this->args, ['url' => $url]);

        if (!($response = self::post($this->url_api, $args, true))) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }

        $rsp = @simplexml_load_string($response);

        if ($rsp && $rsp->status == 'success') {
            return $this->fromValue(
                $rsp->url[0]->keyword,
                $url,
                $this->id
            );
        }
        $this->error->add(__('Unreadable service response.'));

        return false;
    }
}
