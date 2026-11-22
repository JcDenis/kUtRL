<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL\ClaudetteMcp;

use Dotclear\App;
use Dotclear\Plugin\ClaudetteMcp\McpTool;
use Dotclear\Plugin\kUtRL\Logs;
use Dotclear\Plugin\kUtRL\My;
use Dotclear\Plugin\kUtRL\Utils;

/**
 * @brief       kUtRL tool for the plugin ClaudetteMcp: list short links.
 * @ingroup     kUtRL
 *
 * ClaudetteMcp connects an AI assistant to the blog.
 * These classes are only loaded by ClaudetteMcp, which is not required by kUtRL.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ListShortLinks extends McpTool
{
    public function name(): string
    {
        return 'list_short_links';
    }

    /**
     * Family of tools for the keys of ClaudetteMcp (string, not constant: older ClaudetteMcp versions have none).
     */
    public function family(): string
    {
        return 'extras';
    }

    public function title(): string
    {
        return 'List short links';
    }

    public function description(): string
    {
        return 'List the short links of the blog (plugin kUtRL), most recent first, with their long address, ' .
            'the service which made them and their number of visits (counted by the local service only).';
    }

    public function properties(): array
    {
        return [
            'url' => [
                'type'        => 'string',
                'description' => 'Only the short link of this exact long address.',
            ],
            'limit' => [
                'type'        => 'integer',
                'description' => 'Links per page, 1 to 100, default: 50.',
            ],
            'page' => [
                'type'        => 'integer',
                'description' => 'Page number, default: 1.',
            ],
        ];
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
        $log    = new Logs();
        $limit  = $this->int($args, 'limit', 50, 1, 100);
        $page   = $this->int($args, 'page', 1, 1);
        $params = [];
        if ($this->str($args, 'url') !== '') {
            $params['kut_url'] = $this->str($args, 'url');
        }

        $total = $log->getLogs($params, true)->intField(0);

        $params['limit'] = [($page - 1) * $limit, $limit];

        $rs       = $log->getLogs($params);
        $services = [];
        $links    = [];
        while ($rs->fetch()) {
            $type = $rs->strField('kut_type');
            if (!array_key_exists($type, $services)) {
                $services[$type] = Utils::quickService($type);
            }
            $service = $services[$type];

            $links[] = [
                'short_url' => $service !== null ? $service->srvUrlBase() . $rs->strField('kut_hash') : '',
                'url'       => $rs->strField('kut_url'),
                'service'   => $service !== null ? $service->srvName() : $type,
                'date'      => $rs->strField('kut_dt'),
                'visits'    => $rs->intField('kut_counter'),
            ];
        }

        return [
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'links' => $links,
        ];
    }
}
