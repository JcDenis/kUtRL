<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       kUtRL backend class.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // sidebar menu
        My::addBackendMenuItem();

        # Admin behaviors
        if (My::settings()->get('active')) {
            App::behavior()->addBehaviors([
                'adminDashboardFavoritesV2' => BackendBehaviors::antispamDashboardFavoritesV2(...),
                'adminColumnsListsV2'       => BackendBehaviors::adminColumnsListsV2(...),
                'adminFiltersListsV2'       => BackendBehaviors::adminFiltersListsV2(...),
                'adminPostHeaders'          => BackendBehaviors::adminPostHeaders(...),
                'adminPostFormItems'        => BackendBehaviors::adminPostFormItems(...),
                'adminAfterPostUpdate'      => BackendBehaviors::adminAfterPostUpdate(...), // update existing short url
                'adminAfterPostCreate'      => BackendBehaviors::adminAfterPostCreate(...),
                'adminBeforePostDelete'     => BackendBehaviors::adminBeforePostDelete(...),
                'adminPostsActions'         => BackendBehaviors::adminPostsActions(...),
            ]);
            // hate duplicate key!
            App::behavior()->addBehavior('adminAfterPostUpdate', BackendBehaviors::adminAfterPostCreate(...)); // create new short url
        }

        App::behavior()->addBehaviors([
            'initWidgets'    => Widgets::init(...),
            'exportFullV2'   => ImportExportBehaviors::exportFullV2(...),
            'exportSingleV2' => ImportExportBehaviors::exportSingleV2(...),
            'importInitV2'   => ImportExportBehaviors::importInitV2(...),
            'importSingleV2' => ImportExportBehaviors::importSingleV2(...),
            'importFullV2'   => ImportExportBehaviors::importFullV2(...),
        ]);

        return true;
    }
}
