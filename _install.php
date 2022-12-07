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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

try {
    # Compare versions
    if (version_compare(
        dcCore::app()->getVersion('kUtRL'), 
        dcCore::app()->plugins->moduleInfo('kUtRL', 'version'), 
        '>='
    )) {
        return null;
    }

    # Table
    $t = new dbStruct(dcCore::app()->con, dcCore::app()->prefix);
    $t->{initkUtRL::KURL_TABLE_NAME}
        ->kut_id('bigint', 0, false)
        ->blog_id('varchar', 32, false)
        ->kut_service('varchar', 32, false, "'kUtRL'")
        ->kut_type('varchar', 32, false)
        ->kut_hash('varchar', 32, false)
        ->kut_url('text', 0, false)
        ->kut_dt('timestamp', 0, false, 'now()')
        ->kut_password('varchar', 32, true)
        ->kut_counter('bigint', 0, false, 0)

        ->primary('pk_kutrl', 'kut_id')
        ->index('idx_kut_blog_id', 'btree', 'blog_id')
        ->index('idx_kut_hash', 'btree', 'kut_hash')
        ->index('idx_kut_service', 'btree', 'kut_service')
        ->index('idx_kut_type', 'btree', 'kut_type');

    $ti      = new dbStruct(dcCore::app()->con, dcCore::app()->prefix);
    $changes = $ti->synchronize($t);

    # Settings
    dcCore::app()->blog->settings->addNamespace('kUtRL');
    $s = dcCore::app()->blog->settings->kUtRL;
    $s->put('kutrl_active', false, 'boolean', 'Enabled kutrl plugin', false, true);
    $s->put('kutrl_plugin_service', 'default', 'string', 'Service to use to shorten links on third part plugins', false, true);
    $s->put('kutrl_admin_service', 'local', 'string', 'Service to use to shorten links on admin', false, true);
    $s->put('kutrl_tpl_service', 'local', 'string', 'Service to use to shorten links on template', false, true);
    $s->put('kutrl_wiki_service', 'local', 'string', 'Service to use to shorten links on contents', false, true);
    $s->put('kutrl_allow_external_url', true, 'boolean', 'Limited short url to current blog\'s url', false, true);
    $s->put('kutrl_tpl_passive', true, 'boolean', 'Return long url on kutrl tags if kutrl is unactivate', false, true);
    $s->put('kutrl_tpl_active', false, 'boolean', 'Return short url on dotclear tags if kutrl is active', false, true);
    $s->put('kutrl_admin_entry_default', true, 'boolean', 'Create short link on new entry by default', false, true);
    # Settings for "local" service
    $local_css = ".shortenkutrlwidget input { border: 1px solid #CCCCCC; }\n" .
    '.dc-kutrl input { border: 1px solid #CCCCCC; margin: 10px; }';
    $s->put('kutrl_srv_local_protocols', 'http:,https:,ftp:,ftps:,irc:', 'string', 'Allowed kutrl local service protocols', false, true);
    $s->put('kutrl_srv_local_public', false, 'boolean', 'Enabled local service public page', false, true);
    $s->put('kutrl_srv_local_css', $local_css, 'string', 'Special CSS for kutrl local service', false, true);
    $s->put('kutrl_srv_local_404_active', false, 'boolean', 'Use special 404 page on unknow urls', false, true);
    # Settings for "bilbolinks" service
    $s->put('kutrl_srv_bilbolinks_base', 'http://tux-pla.net/', 'string', 'URL of bilbolinks service', false, true);
    # Settings for "YOURLS" service
    $s->put('kutrl_srv_yourls_base', '', 'string', 'URL of YOURLS service', false, true);
    $s->put('kutrl_srv_yourls_username', '', 'string', 'User name to YOURLS service', false, true);
    $s->put('kutrl_srv_yourls_password', '', 'string', 'User password to YOURLS service', false, true);

    # Get dcMiniUrl records as this plugin do the same
    if (dcCore::app()->plugins->moduleExists('dcMiniUrl')) {
        require_once __DIR__ . '/inc/patch.dcminiurl.php';
    }

    return true;
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

return false;
