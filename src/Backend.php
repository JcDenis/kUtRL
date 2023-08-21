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
 * Backend prepend.
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
            dcCore::app()->addBehaviors([
                'adminDashboardFavoritesV2' => [BackendBehaviors::class, 'antispamDashboardFavoritesV2'],
                'adminColumnsListsV2'       => [BackendBehaviors::class, 'adminColumnsListsV2'],
                'adminFiltersListsV2'       => [BackendBehaviors::class, 'adminFiltersListsV2'],
                'adminPostHeaders'          => [BackendBehaviors::class, 'adminPostHeaders'],
                'adminPostFormItems'        => [BackendBehaviors::class, 'adminPostFormItems'],
                'adminAfterPostUpdate'      => [BackendBehaviors::class, 'adminAfterPostUpdate'], // update existing short url
                'adminAfterPostUpdate'      => [BackendBehaviors::class, 'adminAfterPostCreate'], // create new short url
                'adminAfterPostCreate'      => [BackendBehaviors::class, 'adminAfterPostCreate'],
                'adminBeforePostDelete'     => [BackendBehaviors::class, 'adminBeforePostDelete'],
                'adminPostsActions'         => [BackendBehaviors::class, 'adminPostsActions'],
            ]);
        }

        dcCore::app()->addBehavior('initWidgets', [Widgets::class, 'initShorten']);
        dcCore::app()->addBehavior('initWidgets', [Widgets::class, 'initRank']);

        dcCore::app()->addBehaviors([
            'exportFullV2'   => [ImportExportBehaviors::class, 'exportFullV2'],
            'exportSingleV2' => [ImportExportBehaviors::class, 'exportSingleV2'],
            'importInitV2'   => [ImportExportBehaviors::class, 'importInitV2'],
            'importSingleV2' => [ImportExportBehaviors::class, 'importSingleV2'],
            'importFullV2'   => [ImportExportBehaviors::class, 'importFullV2'],
        ]);

        return true;
    }
}
