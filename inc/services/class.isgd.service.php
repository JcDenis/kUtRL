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

if (!defined('DC_RC_PATH')){return;}

class isgdKutrlService extends kutrlService
{
	protected $config = array(
		'id' => 'isgd',
		'name' => 'is.gd',
		'home' => 'http://is.gd/',
		
		'url_api' => 'http://is.gd/api.php',
		'url_base' => 'http://is.gd/',
		'url_min_length' => 25
	);
	
	public function testService()
	{
		$arg = array('longurl' => urlencode($this->url_test));
		if (!self::post($this->url_api,$arg,true,true))
		{
			$this->error->add(__('Service is unavailable.'));
			return false;
		}
		return true;
	}
	
	public function createHash($url,$hash=null)
	{
		$arg = array('longurl' => $url);
		
		if (!($response = self::post($this->url_api,$arg,true,true)))
		{
			$this->error->add(__('Service is unavailable.'));
			return false;
		}
		
		$rs = new ArrayObject();
		$rs->hash = str_replace($this->url_base,'',$response);
		$rs->url = $url;
		$rs->type = $this->id;
		
		return $rs;
	}
}
?>