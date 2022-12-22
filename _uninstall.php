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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$this->addUserAction(
    /* type */
    'settings',
    /* action */
    'delete_all',
    /* ns */
    basename(__DIR__),
    /* description */
    __('delete all settings')
);

$this->addUserAction(
    /* type */
    'tables',
    /* action */
    'delete',
    /* ns */
    initkUtRL::KURL_TABLE_NAME,
    /* description */
    __('delete table')
);

$this->addUserAction(
    /* type */
    'plugins',
    /* action */
    'delete',
    /* ns */
    basename(__DIR__),
    /* description */
    __('delete plugin files')
);

$this->addUserAction(
    /* type */
    'versions',
    /* action */
    'delete',
    /* ns */
    basename(__DIR__),
    /* description */
    __('delete the version number')
);

# Delete only dc version and plugin files from pluginsBeforeDelete
# Keep table

$this->addDirectAction(
    /* type */
    'settings',
    /* action */
    'delete_all',
    /* ns */
    basename(__DIR__),
    /* description */
    sprintf(__('delete all %s settings'), 'kUtRL')
);

$this->addDirectAction(
    /* type */
    'versions',
    /* action */
    'delete',
    /* ns */
    basename(__DIR__),
    /* description */
    sprintf(__('delete %s version number'), 'kUtRL')
);

$this->addDirectAction(
    /* type */
    'plugins',
    /* action */
    'delete',
    /* ns */
    basename(__DIR__),
    /* description */
    sprintf(__('delete %s plugin files'), 'kUtRL')
);
