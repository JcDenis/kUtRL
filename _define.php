<?php
/**
 * @file
 * @brief       The plugin kUtRL definition
 * @ingroup     kUtRL
 *
 * @defgroup    kUtRL Plugin kUtRL.
 *
 * Use, create and serve short url on your blog.
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

$this->registerModule(
    'Links shortener',
    'Use, create and serve short url on your blog',
    'Jean-Christian Denis and contributors',
    '2023.11.04',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . basename(__DIR__) . '/issues',
        'details'     => 'https://github.com/JcDenis/' . basename(__DIR__) . '/src/branch/master/README.md',
        'repository'  => 'https://github.com/JcDenis/' . basename(__DIR__) . '/raw/branch/master/dcstore.xml',
    ]
);
