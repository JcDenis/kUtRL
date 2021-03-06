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
    return null;
}

$core->blog->settings->addNamespace('kUtRL');

require_once dirname(__FILE__) . '/_widgets.php';

# Plugin menu
$_menu['Plugins']->addItem(
    __('Links shortener'),
    $core->adminurl->get('admin.plugin.kUtRL'),
    dcPage::getPF('kUtRL/icon.png'),
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.kUtRL')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->check('admin', $core->blog->id)
);

# Admin behaviors
if ($core->blog->settings->kUtRL->kutrl_active) {
    $core->addBehavior('adminDashboardFavorites', ['adminKutrl', 'antispamDashboardFavorites']);
    $core->addBehavior('adminColumnsLists', ['adminKutrl', 'adminColumnsLists']);
    $core->addBehavior('adminFiltersLists', ['adminKutrl', 'adminFiltersLists']);
    $core->addBehavior('adminPostHeaders', ['adminKutrl', 'adminPostHeaders']);
    $core->addBehavior('adminPostFormItems', ['adminKutrl', 'adminPostFormItems']);
    $core->addBehavior('adminAfterPostUpdate', ['adminKutrl', 'adminAfterPostUpdate']); // update existing short url
    $core->addBehavior('adminAfterPostUpdate', ['adminKutrl', 'adminAfterPostCreate']); // create new short url
    $core->addBehavior('adminAfterPostCreate', ['adminKutrl', 'adminAfterPostCreate']);
    $core->addBehavior('adminBeforePostDelete', ['adminKutrl', 'adminBeforePostDelete']);
    $core->addBehavior('adminPostsActionsCombo', ['adminKutrl', 'adminPostsActionsCombo']);
    $core->addBehavior('adminPostsActions', ['adminKutrl', 'adminPostsActions']);
}

$core->addBehavior('exportFull', ['backupKutrl', 'exportFull']);
$core->addBehavior('exportSingle', ['backupKutrl', 'exportSingle']);
$core->addBehavior('importInit', ['backupKutrl', 'importInit']);
$core->addBehavior('importSingle', ['backupKutrl', 'importSingle']);
$core->addBehavior('importFull', ['backupKutrl', 'importFull']);

# Admin behaviors class
class adminKutrl
{
    public static function sortbyCombo()
    {
        return [
            __('Date')       => 'kut_dt',
            __('Short link') => 'kut_hash',
            __('Long link')  => 'kut_url',
            __('Service')    => 'kut_service'
        ];
    }

    public static function antispamDashboardFavorites(dcCore $core, $favs)
    {
        $favs->register(
            'kUtRL',
            [
                'title'       => __('Links shortener'),
                'url'         => $core->adminurl->get('admin.plugin.kUtRL'),
                'small-icon'  => dcPage::getPF('kUtRL/icon.png'),
                'large-icon'  => dcPage::getPF('kUtRL/icon-b.png'),
                'permissions' => 'admin'
            ]
        );
    }

    public static function adminColumnsLists(dcCore $core, $cols)
    {
        $cols['kUtRL'] = [
            __('Links shortener'),
            [
                'kut_hash'    => [true, __('Hash')],
                'kut_dt'      => [true, __('Date')],
                'kut_service' => [true, __('Service')]
            ]
        ];
    }

    public static function adminFiltersLists(dcCore $core, $sorts)
    {
        $sorts['kUtRL'] = [
            __('Links shortener'),
            self::sortbyCombo(),
            'kut_dt',
            'desc',
            [__('links per page'), 30]
        ];
    }

    public static function adminPostHeaders()
    {
        return dcPage::jsLoad(dcPage::getPF('kUtRL/js/posts.js'));
    }

    public static function adminPostFormItems($main_items, $sidebar_items, $post)
    {
        global $core;
        $s = $core->blog->settings->kUtRL;

        if (!$s->kutrl_active || !$s->kutrl_admin_service) {
            return null;
        }
        if (null === ($kut = kutrl::quickPlace('admin'))) {
            return null;
        }

        if ($post) {
            $post_url = $post->getURL();
            $rs       = $kut->isKnowUrl($post_url);
        } else {
            $post_url = '';
            $rs       = false;
        }

        $ret = '<div id="kUtRL"><h5>' . __('Short link') . '</h5>' .
        form::hidden(['kutrl_old_post_url'], $post_url);

        if (!$rs) {
            if (empty($_POST['kutrl_old_post_url']) && $s->kutrl_admin_entry_default) {
                $chk = true;
            } else {
                $chk = !empty($_POST['kutrl_create']);
            }
            $ret .= '<p><label class="classic">' .
            form::checkbox('kutrl_create', 1, $chk, '', 3) . ' ' .
            __('Create short link') . '</label></p>';

            if ($kut->allow_custom_hash) {
                $ret .= '<p class="classic">' .
                '<label for="custom">' . __('Custom short link:') . ' ' .
                form::field('kutrl_create_custom', 32, 32, '', 3) .
                '</label></p>';
            }
        } else {
            $count = $rs->counter;
            if ($count == 0) {
                $title = __('never followed');
            } elseif ($count == 1) {
                $title = __('followed one time');
            } else {
                $title = sprintf(__('followed %s times'), $count);
            }
            $href = $kut->url_base . $rs->hash;

            $ret .= '<p><label class="classic">' .
            form::checkbox('kutrl_delete', 1, !empty($_POST['kutrl_delete']), '', 3) . ' ' .
            __('Delete short link') . '</label></p>' .
            '<p><a href="' . $href . '" ' . 'title="' . $title . '">' . $href . '</a></p>';
        }
        $ret .= '</div>';

        $sidebar_items['options-box']['items']['kUtRL'] = $ret;
    }

    public static function adminAfterPostUpdate($cur, $post_id)
    {
        global $core;
        $s = $core->blog->settings->kUtRL;

        # Create: see adminAfterPostCreate
        if (!empty($_POST['kutrl_create']) || !$s->kutrl_active) {
            return null;
        }
        if (null === ($kut = kutrl::quickPlace('admin'))) {
            return null;
        }
        if (empty($_POST['kutrl_old_post_url'])) {
            return null;
        }

        $old_post_url = $_POST['kutrl_old_post_url'];

        if (!($rs = $kut->isKnowUrl($old_post_url))) {
            return null;
        }

        $rs = $core->blog->getPosts(['post_id' => $post_id]);
        if ($rs->isEmpty()) {
            return null;
        }
        $title        = html::escapeHTML($rs->post_title);
        $new_post_url = $rs->getURL();

        # Delete
        if (!empty($_POST['kutrl_delete'])) {
            $kut->remove($old_post_url);
        # Update
        } else {
            if ($old_post_url == $new_post_url) {
                return null;
            }

            $kut->remove($old_post_url);

            $rs  = $kut->hash($new_post_url, $custom); // better to update (not yet implemented)
            $url = $kut->url_base . $rs->hash;

            # ex: Send new url to messengers
            if (!empty($rs)) {
                $core->callBehavior('adminAfterKutrlCreate', $core, $rs, $title);
            }
        }
    }

    public static function adminAfterPostCreate($cur, $post_id)
    {
        global $core;
        $s = $core->blog->settings->kUtRL;

        if (empty($_POST['kutrl_create']) || !$s->kutrl_active) {
            return null;
        }
        if (null === ($kut = kutrl::quickPlace('admin'))) {
            return null;
        }

        $rs = $core->blog->getPosts(['post_id' => $post_id]);
        if ($rs->isEmpty()) {
            return null;
        }
        $title = html::escapeHTML($rs->post_title);

        $custom = !empty($_POST['kutrl_create_custom']) && $kut->allow_custom_hash ?
            $_POST['kutrl_create_custom'] : null;

        $rs  = $kut->hash($rs->getURL(), $custom);
        $url = $kut->url_base . $rs->hash;

        # ex: Send new url to messengers
        if (!empty($rs)) {
            $core->callBehavior('adminAfterKutrlCreate', $core, $rs, $title);
        }
    }

    public static function adminBeforePostDelete($post_id)
    {
        global $core;
        $s = $core->blog->settings->kUtRL;

        if (!$s->kutrl_active) {
            return null;
        }
        if (null === ($kut = kutrl::quickPlace('admin'))) {
            return null;
        }

        $rs = $core->blog->getPosts(['post_id' => $post_id]);
        if ($rs->isEmpty()) {
            return null;
        }

        $kut->remove($rs->getURL());
    }

    public static function adminPostsActionsCombo($args)
    {
        global $core;
        $s = $core->blog->settings->kUtRL;

        if (!$s->kutrl_active
         || !$core->auth->check('admin', $core->blog->id)) {
            return null;
        }

        $args[0][__('Links shortener')][__('Create short link')] = 'kutrl_create';
        $args[0][__('Links shortener')][__('Delete short link')] = 'kutrl_delete';
    }

    public static function adminPostsActions(dcCore $core, $posts, $action, $redir)
    {
        if ($action != 'kutrl_create'
         && $action != 'kutrl_delete') {
            return null;
        }
        $s = $core->blog->settings->kUtRL;
        if (!$s->kutrl_active) {
            return null;
        }
        if (null === ($kut = kutrl::quickPlace('admin'))) {
            return null;
        }

        while ($posts->fetch()) {
            $url = $posts->getURL();

            if ($action == 'kutrl_create') {
                $kut->hash($url);
            }
            if ($action == 'kutrl_delete') {
                $kut->remove($url);
            }
        }
        $core->blog->triggerBlog();
        http::redirect($redir . '&done=1');
    }
}

# Import/export behaviors for Import/export plugin
class backupKutrl
{
    public static function exportSingle($core, $exp, $blog_id)
    {
        $exp->export(
            'kutrl',
            'SELECT kut_id, blog_id, kut_service, kut_type, ' .
            'kut_hash, kut_url, kut_dt, kut_password, kut_counter ' .
            'FROM ' . $core->prefix . 'kutrl ' .
            "WHERE blog_id = '" . $blog_id . "' "
        );
    }

    public static function exportFull($core, $exp)
    {
        $exp->exportTable('kutrl');
    }

    public static function importInit($bk, $core)
    {
        $bk->cur_kutrl = $core->con->openCursor($core->prefix . 'kutrl');
        $bk->kutrl     = new kutrlLog($core);
    }

    public static function importSingle($line, $bk, $core)
    {
        if ($line->__name == 'kutrl') {
            # Do nothing if str/type exists !
            if (false === $bk->kutrl->select($line->kut_url, $line->kut_hash, $line->kut_type, $line->kut_service)) {
                $bk->kutrl->insert($line->kut_url, $line->kut_hash, $line->kut_type, $line->kut_service);
            }
        }
    }

    public static function importFull($line, $bk, $core)
    {
        if ($line->__name == 'kutrl') {
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
