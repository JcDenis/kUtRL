<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief       kUtRL install class.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            // Table
            $t = App::db()->structure();
            $t->table(My::TABLE_NAME)
                ->field('kut_id', 'bigint', 0, false)
                ->field('blog_id', 'varchar', 32, false)
                ->field('kut_service', 'varchar', 32, false, "'kUtRL'")
                ->field('kut_type', 'varchar', 32, false)
                ->field('kut_hash', 'varchar', 32, false)
                ->field('kut_url', 'text', 0, false)
                ->field('kut_dt', 'timestamp', 0, false, 'now()')
                ->field('kut_password', 'varchar', 32, true)
                ->field('kut_counter', 'bigint', 0, false, 0)

                ->primary('pk_kutrl', 'kut_id')
                ->index('idx_kut_blog_id', 'btree', 'blog_id')
                ->index('idx_kut_hash', 'btree', 'kut_hash')
                ->index('idx_kut_service', 'btree', 'kut_service')
                ->index('idx_kut_type', 'btree', 'kut_type');

            App::db()->structure()->synchronize($t);

            // upgrade version < 2022.12.22 : upgrade settings id and ns and array
            $current = App::version()->getVersion(My::id());
            if ($current && version_compare($current, '2022.12.22', '<')) {
                $record = App::db()->con()->select(
                    'SELECT * FROM ' . App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME . ' ' .
                    "WHERE setting_ns = 'kUtRL' "
                );
                while ($record->fetch()) {
                    if (preg_match('/^kutrl_(.*?)$/', $record->setting_id, $match)) {
                        $cur = App::blogWorkspace()->openBlogWorkspaceCursor();
                        // avoid the use of serialize function
                        if (in_array($record->setting_id, ['kutrl_srv_custom'])) {
                            $cur->setting_value = json_encode(@unserialize(base64_decode((string) $record->setting_value)));
                        }
                        $cur->setting_id = $match[1];
                        $cur->setting_ns = basename(__DIR__);
                        $cur->update(
                            "WHERE setting_id = '" . $record->setting_id . "' and setting_ns = 'kUtRL' " .
                            'AND blog_id ' . (null === $record->blog_id ? 'IS NULL ' : ("= '" . App::db()->con()->escapeStr((string) $record->blog_id) . "' "))
                        );
                    }
                }
            } else {
                // Settings
                $s = My::settings();

                $s->put('active', false, 'boolean', 'Enabled kutrl plugin', false, true);
                $s->put('plugin_service', 'default', 'string', 'Service to use to shorten links on third part plugins', false, true);
                $s->put('admin_service', 'local', 'string', 'Service to use to shorten links on admin', false, true);
                $s->put('tpl_service', 'local', 'string', 'Service to use to shorten links on template', false, true);
                $s->put('wiki_service', 'local', 'string', 'Service to use to shorten links on contents', false, true);
                $s->put('allow_external_url', true, 'boolean', 'Limited short url to current blog\'s url', false, true);
                $s->put('tpl_passive', true, 'boolean', 'Return long url on kutrl tags if kutrl is unactivate', false, true);
                $s->put('tpl_active', false, 'boolean', 'Return short url on dotclear tags if kutrl is active', false, true);
                $s->put('admin_entry_default', true, 'boolean', 'Create short link on new entry by default', false, true);
                # Settings for "local" service
                $local_css = ".shortenkutrlwidget input { border: 1px solid #CCCCCC; }\n" .
                '.dc-kutrl input { border: 1px solid #CCCCCC; margin: 10px; }';
                $s->put('srv_local_protocols', 'http:,https:,ftp:,ftps:,irc:', 'string', 'Allowed kutrl local service protocols', false, true);
                $s->put('srv_local_public', false, 'boolean', 'Enabled local service public page', false, true);
                $s->put('srv_local_css', $local_css, 'string', 'Special CSS for kutrl local service', false, true);
                $s->put('srv_local_404_active', false, 'boolean', 'Use special 404 page on unknow urls', false, true);
                # Settings for "bilbolinks" service
                $s->put('srv_bilbolinks_base', 'http://tux-pla.net/', 'string', 'URL of bilbolinks service', false, true);
                # Settings for "YOURLS" service
                $s->put('srv_yourls_base', '', 'string', 'URL of YOURLS service', false, true);
                $s->put('srv_yourls_username', '', 'string', 'User name to YOURLS service', false, true);
                $s->put('srv_yourls_password', '', 'string', 'User password to YOURLS service', false, true);
            }

            return true;
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return false;
        }
    }
}
