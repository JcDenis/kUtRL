<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       kUtRL frontend class.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
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

        App::frontend()->template()->setPath(
            App::frontend()->template()->getPath(),
            My::path() . DIRECTORY_SEPARATOR . 'default-templates'
        );

        App::behavior()->addBehaviors([
            'initWidgets'                 => Widgets::init(...),
            'publicBeforeDocumentV2'      => FrontendBehaviors::publicBeforeDocumentV2(...),
            'publicHeadContent'           => FrontendBehaviors::publicHeadContent(...),
            'publicBeforeContentFilterV2' => FrontendBehaviors::publicBeforeContentFilterV2(...),
            'templateBeforeValueV2'       => FrontendBehaviors::templateBeforeValueV2(...),
            'templateAfterValueV2'        => FrontendBehaviors::templateAfterValueV2(...),
        ]);

        App::frontend()->template()->addBlock('kutrlPageIf', FrontendTemplate::pageIf(...));
        App::frontend()->template()->addBlock('kutrlMsgIf', FrontendTemplate::pageMsgIf(...));

        App::frontend()->template()->addValue('kutrlPageURL', FrontendTemplate::pageURL(...));
        App::frontend()->template()->addValue('kutrlMsg', FrontendTemplate::pageMsg(...));
        App::frontend()->template()->addValue('kutrlHumanField', FrontendTemplate::humanField(...));
        App::frontend()->template()->addValue('kutrlHumanFieldProtect', FrontendTemplate::humanFieldProtect(...));

        App::frontend()->template()->addBlock('AttachmentKutrlIf', FrontendTemplate::AttachmentKutrlIf(...));
        App::frontend()->template()->addValue('AttachmentKutrl', FrontendTemplate::AttachmentKutrl(...));
        App::frontend()->template()->addBlock('MediaKutrlIf', FrontendTemplate::MediaKutrlIf(...));
        App::frontend()->template()->addValue('MediaKutrl', FrontendTemplate::MediaKutrl(...));
        App::frontend()->template()->addBlock('EntryAuthorKutrlIf', FrontendTemplate::EntryAuthorKutrlIf(...));
        App::frontend()->template()->addValue('EntryAuthorKutrl', FrontendTemplate::EntryAuthorKutrl(...));
        App::frontend()->template()->addBlock('EntryKutrlIf', FrontendTemplate::EntryKutrlIf(...));
        App::frontend()->template()->addValue('EntryKutrl', FrontendTemplate::EntryKutrl(...));
        App::frontend()->template()->addBlock('CommentAuthorKutrlIf', FrontendTemplate::CommentAuthorKutrlIf(...));
        App::frontend()->template()->addValue('CommentAuthorKutrl', FrontendTemplate::CommentAuthorKutrl(...));
        App::frontend()->template()->addBlock('CommentPostKutrlIf', FrontendTemplate::CommentPostKutrlIf(...));
        App::frontend()->template()->addValue('CommentPostKutrl', FrontendTemplate::CommentPostKutrl(...));

        return true;
    }
}
