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

dcCore::app()->blog->settings->addNamespace(basename(__DIR__));

require_once __DIR__ . '/_widgets.php';

# Plugin menu
dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
    __('Links shortener'),
    dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__)),
    urldecode(dcPage::getPF(basename(__DIR__) . '/icon.svg')),
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__))) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_ADMIN]), dcCore::app()->blog->id)
);

# Admin behaviors
if (dcCore::app()->blog->settings->get(basename(__DIR__))->get('active')) {
    dcCore::app()->addBehavior('adminDashboardFavoritesV2', ['adminKutrl', 'antispamDashboardFavoritesV2']);
    dcCore::app()->addBehavior('adminColumnsListsV2', ['adminKutrl', 'adminColumnsListsV2']);
    dcCore::app()->addBehavior('adminFiltersListsV2', ['adminKutrl', 'adminFiltersListsV2']);
    dcCore::app()->addBehavior('adminPostHeaders', ['adminKutrl', 'adminPostHeaders']);
    dcCore::app()->addBehavior('adminPostFormItems', ['adminKutrl', 'adminPostFormItems']);
    dcCore::app()->addBehavior('adminAfterPostUpdate', ['adminKutrl', 'adminAfterPostUpdate']); // update existing short url
    dcCore::app()->addBehavior('adminAfterPostUpdate', ['adminKutrl', 'adminAfterPostCreate']); // create new short url
    dcCore::app()->addBehavior('adminAfterPostCreate', ['adminKutrl', 'adminAfterPostCreate']);
    dcCore::app()->addBehavior('adminBeforePostDelete', ['adminKutrl', 'adminBeforePostDelete']);
    dcCore::app()->addBehavior('adminPostsActions', ['adminKutrl', 'adminPostsActions']);
}

dcCore::app()->addBehavior('exportFullV2', ['backupKutrl', 'exportFullV2']);
dcCore::app()->addBehavior('exportSingleV2', ['backupKutrl', 'exportSingleV2']);
dcCore::app()->addBehavior('importInitV2', ['backupKutrl', 'importInitV2']);
dcCore::app()->addBehavior('importSingleV2', ['backupKutrl', 'importSingleV2']);
dcCore::app()->addBehavior('importFullV2', ['backupKutrl', 'importFullV2']);

# Admin behaviors class
class adminKutrl
{
    public static function sortbyCombo()
    {
        return [
            __('Date')       => 'kut_dt',
            __('Short link') => 'kut_hash',
            __('Long link')  => 'kut_url',
            __('Service')    => 'kut_service',
        ];
    }

    public static function antispamDashboardFavoritesV2(dcFavorites $favs)
    {
        $favs->register(
            'kUtRL',
            [
                'title'       => __('Links shortener'),
                'url'         => dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__)),
                'small-icon'  => dcPage::getPF(basename(__DIR__) . '/icon.png'),
                'large-icon'  => dcPage::getPF(basename(__DIR__) . '/icon-b.png'),
                'permissions' => dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_ADMIN]),
            ]
        );
    }

    public static function adminColumnsListsV2($cols)
    {
        $cols['kUtRL'] = [
            __('Links shortener'),
            [
                'kut_hash'    => [true, __('Hash')],
                'kut_dt'      => [true, __('Date')],
                'kut_service' => [true, __('Service')],
            ],
        ];
    }

    public static function adminFiltersListsV2($sorts)
    {
        $sorts['kUtRL'] = [
            __('Links shortener'),
            self::sortbyCombo(),
            'kut_dt',
            'desc',
            [__('links per page'), 30],
        ];
    }

    public static function adminPostHeaders()
    {
        return dcPage::jsModuleLoad(basename(__DIR__) . '/js/posts.js');
    }

    public static function adminPostFormItems($main_items, $sidebar_items, $post)
    {
        $s = dcCore::app()->blog->settings->get(basename(__DIR__));

        if (!$s->get('active') || !$s->get('active')) {
            return null;
        }
        if (null === ($kut = kUtRL::quickPlace('admin'))) {
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
            if (empty($_POST['kutrl_old_post_url']) && $s->get('admin_entry_default')) {
                $chk = true;
            } else {
                $chk = !empty($_POST['kutrl_create']);
            }
            $ret .= '<p><label class="classic">' .
            form::checkbox('kutrl_create', 1, $chk, '', '3') . ' ' .
            __('Create short link') . '</label></p>';

            if ($kut->allow_custom_hash) {
                $ret .= '<p class="classic">' .
                '<label for="custom">' . __('Custom short link:') . ' ' .
                form::field('kutrl_create_custom', 32, 32, '', '3') .
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
            form::checkbox('kutrl_delete', 1, !empty($_POST['kutrl_delete']), '', '3') . ' ' .
            __('Delete short link') . '</label></p>' .
            '<p><a href="' . $href . '" ' . 'title="' . $title . '">' . $href . '</a></p>';
        }
        $ret .= '</div>';

        $sidebar_items['options-box']['items']['kUtRL'] = $ret;
    }

    public static function adminAfterPostUpdate($cur, $post_id)
    {
        # Create: see adminAfterPostCreate
        if (!empty($_POST['kutrl_create']) || !dcCore::app()->blog->settings->get(basename(__DIR__))->get('active')) {
            return null;
        }
        if (null === ($kut = kUtRL::quickPlace('admin'))) {
            return null;
        }
        if (empty($_POST['kutrl_old_post_url'])) {
            return null;
        }

        $old_post_url = $_POST['kutrl_old_post_url'];

        if (!($rs = $kut->isKnowUrl($old_post_url))) {
            return null;
        }

        $rs = dcCore::app()->blog->getPosts(['post_id' => $post_id]);
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

            $rs  = $kut->hash($new_post_url, '');//$custom); // better to update (not yet implemented)
            $url = $kut->url_base . $rs->hash;

            # ex: Send new url to messengers
            if (!empty($rs)) {
                dcCore::app()->callBehavior('adminAfterKutrlCreate', $rs, $title);
            }
        }
    }

    public static function adminAfterPostCreate($cur, $post_id)
    {
        if (empty($_POST['kutrl_create']) || !dcCore::app()->blog->settings->get(basename(__DIR__))->get('active')) {
            return null;
        }

        if (null === ($kut = kUtRL::quickPlace('admin'))) {
            return null;
        }

        $rs = dcCore::app()->blog->getPosts(['post_id' => $post_id]);
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
            dcCore::app()->callBehavior('adminAfterKutrlCreate', $rs, $title);
        }
    }

    public static function adminBeforePostDelete($post_id)
    {
        if (!dcCore::app()->blog->settings->get(basename(__DIR__))->get('active')) {
            return null;
        }

        if (null === ($kut = kUtRL::quickPlace('admin'))) {
            return null;
        }

        $rs = dcCore::app()->blog->getPosts(['post_id' => $post_id]);
        if ($rs->isEmpty()) {
            return null;
        }

        $kut->remove($rs->getURL());
    }

    public static function adminPostsActions(dcPostsActions $pa)
    {
        if (!dcCore::app()->blog->settings->get(basename(__DIR__))->get('active')
         || !dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_ADMIN]), dcCore::app()->blog->id)) {
            return null;
        }

        $pa->addAction(
            [__('Links shortener') => [__('Create short link') => 'kutrl_create']],
            ['adminKutrl', 'callbackCreate']
        );
        $pa->addAction(
            [__('Links shortener') => [__('Delete short link') => 'kutrl_delete']],
            ['adminKutrl', 'callbackDelete']
        );
    }

    public static function callbackCreate(dcPostsActions $pa, ArrayObject $post)
    {
        # No entry
        $posts_ids = $pa->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }

        # No right
        if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_ADMIN]), dcCore::app()->blog->id)) {
            throw new Exception(__('No enough right'));
        }

        if (null === ($kut = kUtRL::quickPlace('admin'))) {
            return null;
        }

        # retrieve posts info and create hash
        $posts = dcCore::app()->blog->getPosts(['post_id' => $posts_ids]);
        while ($posts->fetch()) {
            $kut->hash($posts->getURL());
        }

        dcAdminNotices::addSuccessNotice(__('Posts short links have been created.'));
        $pa->redirect(true);
    }

    public static function callbackDelete(dcPostsActions $pa, ArrayObject $post)
    {
        # No entry
        $posts_ids = $pa->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }

        # No right
        if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_ADMIN]), dcCore::app()->blog->id)) {
            throw new Exception(__('No enough right'));
        }

        if (null === ($kut = kUtRL::quickPlace('admin'))) {
            return null;
        }

        # retrieve posts info and create hash
        $posts = dcCore::app()->blog->getPosts(['post_id' => $posts_ids]);
        while ($posts->fetch()) {
            $kut->remove($posts->getURL());
        }

        dcAdminNotices::addSuccessNotice(__('Posts short links have been created.'));
        $pa->redirect(true);
    }
}

# Import/export behaviors for Import/export plugin
class backupKutrl
{
    public static function exportSingleV2($exp, $blog_id)
    {
        $exp->export(
            'kutrl',
            'SELECT kut_id, blog_id, kut_service, kut_type, ' .
            'kut_hash, kut_url, kut_dt, kut_password, kut_counter ' .
            'FROM ' . dcCore::app()->prefix . initkUtRL::KURL_TABLE_NAME . ' ' .
            "WHERE blog_id = '" . $blog_id . "' "
        );
    }

    public static function exportFullV2($exp)
    {
        $exp->exportTable('kutrl');
    }

    public static function importInitV2($bk)
    {
        $bk->cur_kutrl = dcCore::app()->con->openCursor(dcCore::app()->prefix . initkUtRL::KURL_TABLE_NAME);
        $bk->kutrl     = new kutrlLog();
    }

    public static function importSingleV2($line, $bk)
    {
        if ($line->__name == 'kutrl') {
            # Do nothing if str/type exists !
            if (false === $bk->kutrl->select($line->kut_url, $line->kut_hash, $line->kut_type, $line->kut_service)) {
                $bk->kutrl->insert($line->kut_url, $line->kut_hash, $line->kut_type, $line->kut_service);
            }
        }
    }

    public static function importFullV2($line, $bk)
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
