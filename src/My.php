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
declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use Dotclear\Module\MyPlugin;

/**
 * This module definitions.
 */
class My extends MyPlugin
{
    /** @var    string  This module database table name */
    public const TABLE_NAME = \initkUtRL::KURL_TABLE_NAME;

    /** @var    array   List of template tag which content URL that can be shorten */
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
