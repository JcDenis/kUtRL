<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of kUtRL, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2011 JC Denis and contributors
# jcdenis@gdwd.com
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) {
    return null;
}

# Generic class to play easily with services
class kUtRL
{
    # Load services list from behavior
    public static function getServices($core)
    {
        $list = $core->getBehaviors('kutrlService');

        if (empty($list)) {
            return [];
        }
        $service = [];  
        foreach($list as $k => $callback) {
            try {
                list($service_id,$service_class) = call_user_func($callback);
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
        global $core;

        try {
            if (empty($id)) {
                return null;
            }
            $services = self::getServices($core);
            if (isset($services[$id])) {
                return new $services[$id]($core);
            }
        } catch(Exception $e) {

        }
        return null; 
    }

    # Silently try to load a service according to its place
    # Return null on error else service on success
    public static function quickPlace($place = 'plugin')
    {
        global $core;

        try {
            if (!in_array($place, ['tpl', 'wiki', 'admin', 'plugin'])) {
                return null;
            }
            $id = $core->blog->settings->kUtRL->get('kutrl_' . $place .'_service');
            if (!empty($id)) {
                return self::quickService($id);
            }
        } catch(Exception $e) {

        }
        return null; 
    }
    
    # Silently try to reduce url (using 'plugin' place)
    # return long url on error else short url on success
    public static function quickReduce($url, $custom = null, $place = 'plugin')
    {
        global $core;

        try {
            $srv = self::quickPlace($place);
            if (empty($srv)) {
                return $url;
            }
            $rs = $srv->hash($url,$custom);
            if (empty($rs)) {
                return $url;
            }
            return $srv->url_base.$rs->hash;
        } catch(Exception $e) {

        }
        return $url; 
    }
}