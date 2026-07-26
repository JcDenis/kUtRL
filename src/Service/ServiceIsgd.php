<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL\Service;

use Dotclear\Plugin\kUtRL\Service;

/**
 * @brief       kUtRL is.gd service class.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ServiceIsgd extends Service
{
    protected function init(): void
    {
        $this->config = [
            'id'   => 'isgd',
            'name' => 'is.gd',
            'home' => 'http://is.gd/',

            'url_api'        => 'http://is.gd/api.php',
            'url_base'       => 'http://is.gd/',
            'url_min_length' => 25,
        ];
    }

    public function testService(): bool
    {
        $arg  = ['longurl' => urlencode($this->srvUrlTest())];
        if ($this->srvUrlApi() === '' || !self::post($this->srvUrlApi(), $arg, true, true)) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }

        return true;
    }

    public function createHash(string $url, ?string $hash = null)
    {
        $arg = ['longurl' => $url];

        if (!($response = self::post($this->srvUrlApi(), $arg, true, true))) {
            $this->error->add(__('Service is unavailable.'));

            return false;
        }

        return is_string($response) ? $this->fromValue(
            $this->strReplace($this->srvUrlBase(), '', $response),
            $url,
            $this->srvId()
        ) : false;
    }
}
