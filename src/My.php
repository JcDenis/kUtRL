<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use Dotclear\Module\MyPlugin;

/**
 * @brief       kUtRL My helper.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class My extends MyPlugin
{
    /**
     * This module database table name.
     *
     * @var     string  TABLE_NAME
     */
    public const TABLE_NAME = 'kutrl';

    /**
     * List of template tag which content URL that can be shorten.
     *
     * @var     array   USED_TAGS
     */
    public const USED_TAGS = [
        'AttachmentURL',
        'CategoryURL',
        'MediaURL',
        'EntryAuthorURL',
        'EntryURL',
        'EntryCategoryURL',
        'CommentAuthorURL',
        'CommentPostURL',
    ];
}
