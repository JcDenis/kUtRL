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

class googlKutrlService extends kutrlService
{
    public $id = 'googl';
    public $name = 'goo.gl';
    public $home = 'http://goo.gl';

    private $url_api = 'https://www.googleapis.com/urlshortener/v1/url';
    private $url_test = 'http://dotclear.jcdenis.com/go/kUtRL';
    private $args = [
        'key' => 'AIzaSyDE1WfOMdnrnX8p51jSmVodenaNk385asc'
    ];
    private $headers = array('Content-Type: application/json');
    
    protected function init()
    {
        $this->url_base = 'http://goo.gl/';
        $this->url_min_length = 20;
    }

    public function testService()
    {
        $args = $this->args;
        $args['shortUrl'] = $this->url_base . 'PLovn';
        if (!($response = self::post($this->url_api, $args, true, true, $this->headers))) {
            $this->error->add(__('Failed to call service.'));
            return false;
        }

        $rsp = json_decode($response);

        if (empty($rsp->status)) {
            $this->error->add(__('An error occured'));
            return false;
        }
        return true;
    }

    public function createHash($url, $hash = null)
    {
        $args = $this->args;
        $args['longUrl'] = $url;
        $args = json_encode($args);

        if (!($response = self::post($this->url_api, $args, true, false, $this->headers))) {
            $this->error->add(__('Failed to call service.'));
            return false;
        }

        $rsp = json_decode($response);

        if (empty($rsp->id)) {
            $this->error->add(__('An error occured'));
            return false;
        }

        $rs = new ArrayObject();
        $rs->hash = str_replace($this->url_base, '', $rsp->id);
        $rs->url = $rsp->longUrl;
        $rs->type = $this->id;

        return $rs;
    }
}