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
    '2025.09.13',
    [
        'requires'    => [['core', '2.36']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-09-11T15:55:00+00:00',
    ]
);
