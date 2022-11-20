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
if (!defined('DC_RC_PATH')) {
    return null;
}

# Generic class to play easily with services
class kUtRL
{
    # Load services list from behavior
    public static function getServices()
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

    # Silently try to load a service according to its id
    # Return null on error else service on success
    public static function quickService($id = '')
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

    # Silently try to load a service according to its place
    # Return null on error else service on success
    public static function quickPlace($place = 'plugin')
    {
        try {
            if (!in_array($place, ['tpl', 'wiki', 'admin', 'plugin'])) {
                return null;
            }
            $id = dcCore::app()->blog->settings->kUtRL->get('kutrl_' . $place . '_service');
            if (!empty($id)) {
                return self::quickService($id);
            }
        } catch (Exception $e) {
        }

        return null;
    }

    # Silently try to reduce url (using 'plugin' place)
    # return long url on error else short url on success
    public static function quickReduce($url, $custom = null, $place = 'plugin')
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
