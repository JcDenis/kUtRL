<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\{
    Action\ActionsPosts,
    Favorites,
    Notices
};
use Dotclear\Database\{
    Cursor,
    MetaRecord
};
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Hidden,
    Input,
    Label,
    Link,
    Para,
    Text
};
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief       kUtRL backend behaviors.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class BackendBehaviors
{
    public static function antispamDashboardFavoritesV2(Favorites $favs): void
    {
        $favs->register(
            My::id(),
            [
                'title'       => My::name(),
                'url'         => My::manageUrl(),
                'small-icon'  => My::icons(),
                'large-icon'  => My::icons(),
                'permissions' => App::auth()->makePermissions([App::auth()::PERMISSION_ADMIN]),
            ]
        );
    }

    public static function adminColumnsListsV2(ArrayObject $cols): void
    {
        $cols[My::id()] = [
            My::name(),
            [
                'kut_hash'    => [true, __('Hash')],
                'kut_dt'      => [true, __('Date')],
                'kut_service' => [true, __('Service')],
            ],
        ];
    }

    public static function adminFiltersListsV2(ArrayObject $sorts): void
    {
        $sorts[My::id()] = [
            My::name(),
            Combo::sortbyCombo(),
            'kut_dt',
            'desc',
            [__('links per page'), 30],
        ];
    }

    public static function adminPostHeaders(): string
    {
        return My::jsLoad('posts');
    }

    public static function adminPostFormItems(ArrayObject $main_items, ArrayObject $sidebar_items, ?MetaRecord $post): void
    {
        $s = My::settings();

        if (!$s->get('active')
            || !$s->get('active')
            || null === ($kut = Utils::quickPlace('admin'))
        ) {
            return;
        }

        $post_url = '';
        $rs       = false;
        if ($post) {
            $post_url = $post->getURL();
            $rs       = $kut->isKnowUrl($post_url);
        }

        $items = [];

        if (!$rs) {
            $chk = !empty($_POST['kutrl_create']);
            if (empty($_POST['kutrl_old_post_url']) && $s->get('admin_entry_default')) {
                $chk = true;
            }

            $items[] = (new Para())
                ->items([
                    (new Checkbox('kutrl_create', $chk))
                        ->value(1),
                    (new Label(__('Create short link'), Label::OUTSIDE_LABEL_AFTER))
                        ->class('classic')
                        ->for('kutrl_create'),
                ]);

            if ($kut->allow_custom_hash) {
                $items[] = (new Para())
                    ->class('classic')
                    ->items([
                        (new Label(__('Custom short link:'), Label::OUTSIDE_LABEL_BEFORE))
                            ->for('kutrl_create_custom'),
                        (new Input('kutrl_create_custom'))
                            ->size(32)
                            ->maxlenght(32)
                            ->class('maximal')
                            ->value(''),
                    ]);
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

            $items[] = (new Para())
                ->items([
                    (new Checkbox('kutrl_delete', !empty($_POST['kutrl_delete'])))
                        ->value(1),
                    (new Label(__('Delete short link'), Label::OUTSIDE_LABEL_AFTER))
                        ->class('classic')
                        ->for('kutrl_delete'),
                ]);

            $items[] = (new Para())
                ->items([
                    (new Link())
                        ->href($href)
                        ->title($title)
                        ->text($href),
                ]);
        }

        $sidebar_items['options-box']['items'][My::id()] = (new Div(My::id()))
            ->items([
                (new Text('h5', __('Short link'))),
                (new Hidden('kutrl_old_post_url', $post_url)),
                ... $items,
            ])
            ->render();
    }

    public static function adminAfterPostUpdate(Cursor $cur, string|int $post_id): void
    {
        # Create: see adminAfterPostCreate
        if (!empty($_POST['kutrl_create'])
            || !My::settings()->get('active')
            || null === ($kut = Utils::quickPlace('admin'))
            || empty($_POST['kutrl_old_post_url'])
        ) {
            return;
        }

        $old_post_url = $_POST['kutrl_old_post_url'];
        if (!($rs = $kut->isKnowUrl($old_post_url))) {
            return;
        }

        $rs = App::blog()->getPosts(['post_id' => $post_id]);
        if ($rs->isEmpty()) {
            return;
        }
        $title        = Html::escapeHTML($rs->post_title);
        $new_post_url = $rs->getURL();

        # Delete
        if (!empty($_POST['kutrl_delete'])) {
            $kut->remove($old_post_url);
            # Update
        } else {
            if ($old_post_url == $new_post_url) {
                return;
            }

            $kut->remove($old_post_url);

            $rs  = $kut->hash($new_post_url, '');//$custom); // better to update (not yet implemented)
            $url = $kut->url_base . $rs->hash;

            # ex: Send new url to messengers
            if (!empty($rs)) {
                App::behavior()->callBehavior('adminAfterKutrlCreate', $rs, $title);
            }
        }
    }

    public static function adminAfterPostCreate(Cursor $cur, int $post_id): void
    {
        if (empty($_POST['kutrl_create'])
            || !My::settings()->get('active')
            || null === ($kut = Utils::quickPlace('admin'))
        ) {
            return;
        }

        $rs = App::blog()->getPosts(['post_id' => $post_id]);
        if ($rs->isEmpty()) {
            return;
        }
        $title = Html::escapeHTML($rs->post_title);

        $custom = !empty($_POST['kutrl_create_custom']) && $kut->allow_custom_hash ?
            $_POST['kutrl_create_custom'] : null;

        $rs  = $kut->hash($rs->getURL(), $custom);
        $url = $kut->url_base . $rs->hash;

        # ex: Send new url to messengers
        if (!empty($rs)) {
            App::behavior()->callBehavior('adminAfterKutrlCreate', $rs, $title);
        }
    }

    public static function adminBeforePostDelete(string|int $post_id): void
    {
        if (!My::settings()->get('active')
            || null === ($kut = Utils::quickPlace('admin'))
        ) {
            return;
        }

        $rs = App::blog()->getPosts(['post_id' => $post_id]);
        if ($rs->isEmpty()) {
            return;
        }

        $kut->remove($rs->getURL());
    }

    public static function adminPostsActions(ActionsPosts $pa): void
    {
        if (!My::settings()->get('active')
         || !App::auth()->check(App::auth()->makePermissions([App::auth()::PERMISSION_ADMIN]), App::blog()->id())) {
            return;
        }

        $pa->addAction(
            [My::name() => [__('Create short link') => 'kutrl_create']],
            self::callbackCreate(...)
        );
        $pa->addAction(
            [My::name() => [__('Delete short link') => 'kutrl_delete']],
            self::callbackDelete(...)
        );
    }

    public static function callbackCreate(ActionsPosts $pa, ArrayObject $post): void
    {
        # No entry
        $posts_ids = $pa->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }

        # No right
        if (!App::auth()->check(App::auth()->makePermissions([App::auth()::PERMISSION_ADMIN]), App::blog()->id())) {
            throw new Exception(__('No enough right'));
        }

        if (null === ($kut = Utils::quickPlace('admin'))) {
            return;
        }

        # retrieve posts info and create hash
        $posts = App::blog()->getPosts(['post_id' => $posts_ids]);
        while ($posts->fetch()) {
            $kut->hash($posts->getURL());
        }

        Notices::addSuccessNotice(__('Posts short links have been created.'));
        $pa->redirect(true);
    }

    public static function callbackDelete(ActionsPosts $pa, ArrayObject $post): void
    {
        # No entry
        $posts_ids = $pa->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }

        # No right
        if (!App::auth()->check(App::auth()->makePermissions([App::auth()::PERMISSION_ADMIN]), App::blog()->id())) {
            throw new Exception(__('No enough right'));
        }

        if (null === ($kut = Utils::quickPlace('admin'))) {
            return;
        }

        # retrieve posts info and create hash
        $posts = App::blog()->getPosts(['post_id' => $posts_ids]);
        while ($posts->fetch()) {
            $kut->remove($posts->getURL());
        }

        Notices::addSuccessNotice(__('Posts short links have been created.'));
        $pa->redirect(true);
    }
}
