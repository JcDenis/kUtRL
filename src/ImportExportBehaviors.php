<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Plugin\importExport\FlatExport;
use Dotclear\Plugin\importExport\FlatBackup;
use Dotclear\Plugin\importExport\FlatBackupItem;
use Dotclear\Plugin\importExport\FlatImportV2;

/**
 * @brief       kUtRL importExport stuff.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ImportExportBehaviors
{
    public static function exportSingleV2(FlatExport $exp, string $blog_id): void
    {
        $exp->export(
            My::TABLE_NAME,
            'SELECT kut_id, blog_id, kut_service, kut_type, ' .
            'kut_hash, kut_url, kut_dt, kut_password, kut_counter ' .
            'FROM ' . App::db()->con()->prefix() . My::TABLE_NAME . ' ' .
            "WHERE blog_id = '" . $blog_id . "' "
        );
    }

    public static function exportFullV2(FlatExport $exp): void
    {
        $exp->exportTable(My::TABLE_NAME);
    }

    public static function importInitV2(FlatBackup $bk): void
    {
        $bk->__set('cur_kutrl', App::db()->con()->openCursor(App::db()->con()->prefix() . My::TABLE_NAME));
        $bk->__set('kutrl', new Logs());
    }

    public static function importSingleV2(FlatBackupItem $line, FlatImportV2 $bk): void
    {
        if ($line->__name == My::TABLE_NAME) {
            # Do nothing if str/type exists !
            if (($bk->__get('kutrl') instanceof Logs) && false === $bk->__get('kutrl')->select($line->f('kut_url'), $line->f('kut_hash'), $line->f('kut_type'), $line->f('kut_service'))) {
                $bk->__get('kutrl')->insert($line->f('kut_url'), $line->f('kut_hash'), $line->f('kut_type'), $line->f('kut_service'));
            }
        }
    }

    public static function importFullV2(FlatBackupItem $line, FlatImportV2 $bk): void
    {
        if ($line->__name == My::TABLE_NAME && is_numeric($line->f('kut_id')) && ($bk->__get('cur_kutrl') instanceof Cursor)) {
            $bk->__get('cur_kutrl')->clean();
            $bk->__get('cur_kutrl')->setField('kut_id', (int) $line->f('kut_id'));
            $bk->__get('cur_kutrl')->setField('blog_id', $line->f('blog_id'));
            $bk->__get('cur_kutrl')->setField('kut_service', $line->f('kut_service'));
            $bk->__get('cur_kutrl')->setField('kut_type', $line->f('kut_type'));
            $bk->__get('cur_kutrl')->setField('kut_hash', $line->f('kut_hash'));
            $bk->__get('cur_kutrl')->setField('kut_url', $line->f('kut_url'));
            $bk->__get('cur_kutrl')->setField('kut_dt', $line->f('miniurl_dt'));
            $bk->__get('cur_kutrl')->setField('kut_counter', is_numeric($line->f('kut_counter')) ? (int) $line->f('kut_counter') : 0);
            $bk->__get('cur_kutrl')->setField('kut_password', $line->f('kut_password'));
            $bk->__get('cur_kutrl')->insert();
        }
    }
}
