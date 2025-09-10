<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use Dotclear\App;

/**
 * @brief       kUtRL importExport stuff.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ImportExportBehaviors
{
    public static function exportSingleV2($exp, $blog_id)
    {
        $exp->export(
            My::TABLE_NAME,
            'SELECT kut_id, blog_id, kut_service, kut_type, ' .
            'kut_hash, kut_url, kut_dt, kut_password, kut_counter ' .
            'FROM ' . App::db()->con()->prefix() . My::TABLE_NAME . ' ' .
            "WHERE blog_id = '" . $blog_id . "' "
        );
    }

    public static function exportFullV2($exp)
    {
        $exp->exportTable(My::TABLE_NAME);
    }

    public static function importInitV2($bk)
    {
        $bk->cur_kutrl = App::db()->con()->openCursor(App::db()->con()->prefix() . My::TABLE_NAME);
        $bk->kutrl     = new Logs();
    }

    public static function importSingleV2($line, $bk)
    {
        if ($line->__name == My::TABLE_NAME) {
            # Do nothing if str/type exists !
            if (false === $bk->kutrl->select($line->kut_url, $line->kut_hash, $line->kut_type, $line->kut_service)) {
                $bk->kutrl->insert($line->kut_url, $line->kut_hash, $line->kut_type, $line->kut_service);
            }
        }
    }

    public static function importFullV2($line, $bk)
    {
        if ($line->__name == My::TABLE_NAME) {
            $bk->cur_kutrl->clean();
            $bk->cur_kutrl->kut_id       = (int) $line->kut_id;
            $bk->cur_kutrl->blog_id      = (string) $line->blog_id;
            $bk->cur_kutrl->kut_service  = (string) $line->kut_service;
            $bk->cur_kutrl->kut_type     = (string) $line->kut_type;
            $bk->cur_kutrl->kut_hash     = (string) $line->kut_hash;
            $bk->cur_kutrl->kut_url      = (string) $line->kut_url;
            $bk->cur_kutrl->kut_dt       = (string) $line->miniurl_dt;
            $bk->cur_kutrl->kut_counter  = (int) $line->kut_counter;
            $bk->cur_kutrl->kut_password = (string) $line->kut_password;
            $bk->cur_kutrl->insert();
        }
    }
}
