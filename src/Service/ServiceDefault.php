<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL\Service;

use Dotclear\Helper\Html\Form\{
    Div,
    Note,
    Text
};
use Dotclear\Plugin\kUtRL\Service;

/**
 * @brief       kUtRL default service class.
 * @ingroup     kUtRL
 *
 * Note: "default" ne veut pas dire service par défaut
 * mais service simple et rapide configuré par des constantes
 * cela permet de configurer ces constantes dans le fichier
 * config de Dotclear pour une plateforme complète.
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ServiceDefault extends Service
{
    protected function init(): void
    {
        $this->config = [
            'id'   => 'default',
            'name' => 'Default',
            'home' => '',

            'url_api'     => $this->getConstant('SHORTEN_SERVICE_API'),
            'url_base'    => $this->getConstant('SHORTEN_SERVICE_BASE'),
            'url_min_len' => strlen((string) $this->getConstant('SHORTEN_SERVICE_BASE')) + 2,

            'url_param'  => $this->getConstant('SHORTEN_SERVICE_PARAM'),
            'url_encode' => $this->getConstant('SHORTEN_SERVICE_ENCODE'),
        ];
    }

    public function settingsForm(): Div
    {
        return (new Div())
            ->items([
                (new Note())
                    ->class('form-note')
                    ->text(__('There is nothing to configure for this service.')),
                (new Text('p', __('There is nothing to configure for this service.'))),
                (new Text(
                    '',
                    '<dl>' .
                    '<dt>' . __('Service name:') . '</dt>' .
                    '<dd>' . $this->getConstant('SHORTEN_SERVICE_NAME') . '</dd>' .
                    '<dt>' . __('Full API URL:') . '</dt>' .
                    '<dd>' . $this->getConstant('SHORTEN_SERVICE_API') . '</dd>' .
                    '<dt>' . __('Query param:') . '</dt>' .
                    '<dd>' . $this->getConstant('SHORTEN_SERVICE_PARAM') . '</dd>' .
                    '<dt>' . __('Short URL domain:') . '</dt>' .
                    '<dd>' . $this->getConstant('SHORTEN_SERVICE_BASE') . '</dd>' .
                    '<dt>' . __('Encode URL:') . '</dt>' .
                    '<dd>' . ($this->getConstant('SHORTEN_SERVICE_ENCODE') ? __('yes') : __('no')) . '</dd>' .
                    '</dl>'
                )),
            ]);
    }

    public function testService(): bool
    {
        $url = $this->get('url_encode') ? urlencode($this->get('url_test')) : $this->get('url_test');
        $arg = [$this->get('url_param') => urlencode($this->get('url_test'))];

        if (!self::post($this->get('url_api'), $arg, true, true)) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }

        return true;
    }

    public function createHash(string $url, ?string $hash = null)
    {
        $enc = $this->get('url_encode') ? urlencode($url) : $url;
        $arg = [$this->get('url_param') => $url];

        if (!($response = self::post($this->get('url_api'), $arg, true, true))) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }

        return $this->fromValue(
            $this->strReplace($this->get('url_base'), '', $response),
            $url,
            $this->get('id')
        );
    }

    private function getConstant(string $c): string|bool
    {
        return defined($c) ? constant($c) : '';
    }
}
