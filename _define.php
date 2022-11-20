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

$this->registerModule(
    'Links shortener',
    'Use, create and serve short url on your blog',
    'Jean-Christian Denis and contributors',
    '2022.11.12',
    [
        'requires'    => [['core', '2.24']],
        'permissions' => dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
            dcAuth::PERMISSION_ADMIN,
        ]),
        'type'       => 'plugin',
        'support'    => 'https://github.com/JcDenis/kUtRL',
        'details'    => 'http://plugins.dotaddict.org/dc2/details/kUtRL',
        'repository' => 'https://raw.githubusercontent.com/JcDenis/kUtRL/master/dcstore.xml',
    ]
);
