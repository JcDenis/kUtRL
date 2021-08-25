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

if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$this->addUserAction(
    /* type */ 'settings',
    /* action */ 'delete_all',
    /* ns */ 'kUtRL',
    /* description */ __('delete all settings')
);

$this->addUserAction(
    /* type */ 'tables',
    /* action */ 'delete',
    /* ns */ 'kutrl',
    /* description */ __('delete table')
);

$this->addUserAction(
    /* type */ 'plugins',
    /* action */ 'delete',
    /* ns */ 'kUtRL',
    /* description */ __('delete plugin files')
);

$this->addUserAction(
    /* type */ 'versions',
    /* action */ 'delete',
    /* ns */ 'kUtRL',
    /* description */ __('delete the version number')
);


# Delete only dc version and plugin files from pluginsBeforeDelete
# Keep table

$this->addDirectAction(
    /* type */ 'settings',
    /* action */ 'delete_all',
    /* ns */ 'kUtRL',
    /* description */ sprintf(__('delete all %s settings'),'kUtRL')
);

$this->addDirectAction(
    /* type */ 'versions',
    /* action */ 'delete',
    /* ns */ 'kUtRL',
    /* description */ sprintf(__('delete %s version number'),'kUtRL')
);

$this->addDirectAction(
    /* type */ 'plugins',
    /* action */ 'delete',
    /* ns */ 'kUtRL',
    /* description */ sprintf(__('delete %s plugin files'),'kUtRL')
);