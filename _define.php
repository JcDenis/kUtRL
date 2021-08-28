<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of kUtRL, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2021 Jean-Christian Denis and contributors
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) {
    return null;
}

$this->registerModule(
    'kUtRL',
    'Use, create and serve short url on your blog',
    'Jean-Christian Denis and contributors',
    '2021.08.28',
    [
        'permissions' => 'admin',
        'type' => 'plugin',
        'dc_min' => '2.19',
        'support' => 'https://github.com/JcDenis/kUtRL',
        'details' => 'http://plugins.dotaddict.org/dc2/details/kUtRL',
        'repository' => 'https://raw.githubusercontent.com/JcDenis/kUtRL/master/dcstore.xml'
    ]
);