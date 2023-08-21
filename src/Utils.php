<?php
/**
 * @brief kUtRL, a plugin for Dotclear 2
 *
 * Generic class to play easily with services
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

namespace Dotclear\Plugin\kUtRL;

use dcCore;
use Exception;

class Utils
{
    /**
     * Load services list from behavior.
     *
     * @return  array<string,string>    The services list
     */
    public static function getServices(): ?array
    {
        $list = dcCore::app()->getBehaviors('kutrlService');

        if (empty($list)) {
            return [];
        }
        $services = [];
        foreach ($list as $k => $callback) {
            try {
                [$service_id, $service_class]   = call_user_func($callback);
                $services[(string) $service_id] = (string) $service_class;
            } catch (Exception $e) {
            }
        }

        return $services;
    }

    /**
     * Silently try to load a service according to its id.
     *
     * @param   string  $id     The service ID
     *
     * @return  Service     The service instance or null on error;
     */
    public static function quickService(string $id = ''): ?Service
    {
        try {
            if (empty($id)) {
                return null;
            }
            $services = self::getServices();
            if (isset($services[$id])) {
                return new $services[$id]();
            }
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * Silently try to load a service according to its place.
     *
     * @param   string  The execution context
     *
     * @return  Service     The service or null on error
     */
    public static function quickPlace(string $place = 'plugin'): ?Service
    {
        try {
            if (!in_array($place, ['tpl', 'wiki', 'admin', 'plugin'])) {
                return null;
            }
            $id = My::settings()->get($place . '_service');
            if (!empty($id)) {
                return self::quickService($id);
            }
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * Silently try to reduce url (using 'plugin' place).
     *
     * @param   string  $url    The long URL
     * @param   string  $cutom  The custom short URI
     * @param   string  $place  The context
     *
     * @return  string The short url on success else the long url
     */
    public static function quickReduce(string $url, ?string $custom = null, string $place = 'plugin'): string
    {
        try {
            $srv = self::quickPlace($place);
            if (empty($srv)) {
                return $url;
            }
            $rs = $srv->hash($url, $custom);
            if (empty($rs)) {
                return $url;
            }

            return $srv->url_base . $rs->hash;
        } catch (Exception $e) {
        }

        return $url;
    }
}
