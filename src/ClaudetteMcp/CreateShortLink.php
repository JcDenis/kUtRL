<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL\ClaudetteMcp;

use Dotclear\App;
use Dotclear\Plugin\ClaudetteMcp\McpTool;
use Dotclear\Plugin\ClaudetteMcp\McpToolException;
use Dotclear\Plugin\kUtRL\My;
use Dotclear\Plugin\kUtRL\Utils;
use Exception;

/**
 * @brief       kUtRL tool for the plugin ClaudetteMcp: create a short link.
 * @ingroup     kUtRL
 *
 * Same checks and service as the "New link" page of the admin.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class CreateShortLink extends McpTool
{
    public function name(): string
    {
        return 'create_short_link';
    }

    public function title(): string
    {
        return 'Create a short link';
    }

    public function description(): string
    {
        return 'Create a short link for a long address, with the service chosen for the administration in the settings of the plugin kUtRL. ' .
            'If the address already has a short link, it is given back. The service may limit short links to the addresses of the blog.';
    }

    public function properties(): array
    {
        return [
            'url' => [
                'type'        => 'string',
                'description' => 'The long address to shorten.',
            ],
            'custom' => [
                'type'        => 'string',
                'description' => 'A custom end for the short link, if the service accepts it.',
            ],
        ];
    }

    public function required(): array
    {
        return ['url'];
    }

    public function readOnly(): bool
    {
        return false;
    }

    /**
     * Like in backend: the plugin must be active on the blog, its page is for blog administrators.
     */
    public function available(): bool
    {
        return My::settings()->getBool('active', false) && $this->can(App::auth()::PERMISSION_ADMIN);
    }

    public function call(array $args): array
    {
        $kut = Utils::quickPlace('admin');
        if ($kut === null) {
            throw new McpToolException('No short link service is set for the administration in the settings of kUtRL');
        }

        $url  = trim(App::db()->con()->escapeStr($this->str($args, 'url')));
        $hash = $this->str($args, 'custom') === '' ? null : $this->str($args, 'custom');

        // Same checks as the admin page
        if ($url === '') {
            throw new McpToolException('There is nothing to shorten');
        }
        if (!$kut->testService()) {
            throw new McpToolException('The short link service is not well configured');
        }
        if ($hash !== null && !$kut->srvAllowCustomHash()) {
            throw new McpToolException('This service does not allow a custom short link');
        }
        if (!$kut->isValidUrl($url)) {
            throw new McpToolException('This link is not a valid address');
        }
        if (!$kut->isLongerUrl($url)) {
            throw new McpToolException('This link is too short');
        }
        if (!$kut->isProtocolUrl($url)) {
            throw new McpToolException('This type of link is not allowed');
        }
        if (!$kut->get('allow_external_url') && !$kut->isBlogUrl($url)) {
            throw new McpToolException('Short links are limited to the addresses of this blog');
        }
        if ($kut->isServiceUrl($url)) {
            throw new McpToolException('This link is already a short link');
        }
        if ($hash !== null && false !== $kut->isKnowHash($hash)) {
            throw new McpToolException('This custom short link is already taken');
        }

        $known = $kut->isKnowUrl($url);
        if ($known === false) {
            try {
                $known = $kut->hash($url, $hash);
            } catch (Exception) {
                $known = false;
            }
            if ($known === false) {
                throw new McpToolException('Failed to create the short link, the service may have failed');
            }
            $created = true;
        }

        return [
            'short_url' => $kut->srvUrlBase() . $known->strField('hash'),
            'url'       => $known->strField('url'),
            'service'   => $kut->srvName(),
            'created'   => $created ?? false,
        ];
    }
}
