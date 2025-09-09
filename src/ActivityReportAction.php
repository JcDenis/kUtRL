<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Database\MetaRecord;
use Dotclear\Plugin\activityReport\{
    Action,
    ActivityReport,
    Group
};

/**
 * @brief       kUtRL plugin activityReport class.
 * @ingroup     kUtRL
 *
 * Add links actions to the plugin activity report.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ActivityReportAction
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(true);
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        $group = new Group(My::id(), My::name());

        # from BEHAVIOR kutrlAfterCreateShortUrl in kUtRL/inc/lib.kutrl.srv.php
        $group->add(new Action(
            'kUtRLcreate',
            __('Short link creation'),
            __('New short link of type "%s" and hash "%s" was created.'),
            'kutrlAfterCreateShortUrl',
            function (MetaRecord $rs) {
                ActivityReport::instance()->addLog(My::id(), 'kUtRLcreate', [$rs->type, $rs->hash]);
            }
        ));

        ActivityReport::instance()->groups->add($group);

        return true;
    }
}
