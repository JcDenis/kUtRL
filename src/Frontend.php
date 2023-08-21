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

use dcCore;

use Dotclear\Core\Process;

/**
 * Frontend prepend.
 */
class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        dcCore::app()->tpl->setPath(
            dcCore::app()->tpl->getPath(),
            My::path() . DIRECTORY_SEPARATOR . 'default-templates'
        );
        dcCore::app()->addBehavior('initWidgets', [Widgets::class, 'initShorten']);
        dcCore::app()->addBehavior('initWidgets', [Widgets::class, 'initRank']);

        dcCore::app()->addBehaviors([
            'publicBeforeDocumentV2'      => [FrontendBehaviors::class, 'publicBeforeDocumentV2'],
            'publicHeadContent'           => [FrontendBehaviors::class, 'publicHeadContent'],
            'publicBeforeContentFilterV2' => [FrontendBehaviors::class, 'publicBeforeContentFilterV2'],
            'templateBeforeValueV2'       => [FrontendBehaviors::class, 'templateBeforeValueV2'],
            'templateAfterValueV2'        => [FrontendBehaviors::class, 'templateAfterValueV2'],
        ]);

        dcCore::app()->tpl->addBlock('kutrlPageIf', [FrontendTemplate::class, 'pageIf']);
        dcCore::app()->tpl->addBlock('kutrlMsgIf', [FrontendTemplate::class, 'pageMsgIf']);

        dcCore::app()->tpl->addValue('kutrlPageURL', [FrontendTemplate::class, 'pageURL']);
        dcCore::app()->tpl->addValue('kutrlMsg', [FrontendTemplate::class, 'pageMsg']);
        dcCore::app()->tpl->addValue('kutrlHumanField', [FrontendTemplate::class, 'humanField']);
        dcCore::app()->tpl->addValue('kutrlHumanFieldProtect', [FrontendTemplate::class, 'humanFieldProtect']);

        dcCore::app()->tpl->addBlock('AttachmentKutrlIf', [FrontendTemplate::class, 'AttachmentKutrlIf']);
        dcCore::app()->tpl->addValue('AttachmentKutrl', [FrontendTemplate::class, 'AttachmentKutrl']);
        dcCore::app()->tpl->addBlock('MediaKutrlIf', [FrontendTemplate::class, 'MediaKutrlIf']);
        dcCore::app()->tpl->addValue('MediaKutrl', [FrontendTemplate::class, 'MediaKutrl']);
        dcCore::app()->tpl->addBlock('EntryAuthorKutrlIf', [FrontendTemplate::class, 'EntryAuthorKutrlIf']);
        dcCore::app()->tpl->addValue('EntryAuthorKutrl', [FrontendTemplate::class, 'EntryAuthorKutrl']);
        dcCore::app()->tpl->addBlock('EntryKutrlIf', [FrontendTemplate::class, 'EntryKutrlIf']);
        dcCore::app()->tpl->addValue('EntryKutrl', [FrontendTemplate::class, 'EntryKutrl']);
        dcCore::app()->tpl->addBlock('CommentAuthorKutrlIf', [FrontendTemplate::class, 'CommentAuthorKutrlIf']);
        dcCore::app()->tpl->addValue('CommentAuthorKutrl', [FrontendTemplate::class, 'CommentAuthorKutrl']);
        dcCore::app()->tpl->addBlock('CommentPostKutrlIf', [FrontendTemplate::class, 'CommentPostKutrlIf']);
        dcCore::app()->tpl->addValue('CommentPostKutrl', [FrontendTemplate::class, 'CommentPostKutrl']);

        return true;
    }
}
