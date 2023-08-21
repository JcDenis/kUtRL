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
use Dotclear\Core\Backend\Filter\{
    Filters,
    FiltersLibrary
};
use Dotclear\Core\Backend\{
    Notices,
    Page
};
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\{
    Div,
    Form,
    Hidden,
    Link,
    Para,
    Submit,
    Text
};

class ManageLinks extends Process
{
    private static Filters $kutrl_filter;
    private static Linkslisting $kutrl_listing;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE) && ($_REQUEST['part'] ?? 'links') === 'links');
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        $log = new Logs();

        self::$kutrl_filter = new Filters(My::id());
        self::$kutrl_filter->add('part', 'links');
        self::$kutrl_filter->add(FiltersLibrary::getPageFilter());
        self::$kutrl_filter->add(FiltersLibrary::getSelectFilter(
            'urlsrv',
            __('Service:'),
            array_merge(['-' => ''], Combo::servicesCombo()),
            'kut_type'
        ));

        $params = self::$kutrl_filter->params();

        try {
            $list_all            = $log->getLogs($params);
            $list_counter        = $log->getLogs($params, true)->f(0);
            self::$kutrl_listing = new LinksListing($list_all, $list_counter);
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        if (!empty($_POST['deletelinks'])) {
            try {
                foreach ($_POST['entries'] as $id) {
                    $rs = $log->getLogs(['kut_id' => $id]);
                    if ($rs->isEmpty()) {
                        continue;
                    }
                    if (null === ($o = Utils::quickService($rs->kut_type))) {
                        continue;
                    }
                    $o->remove($rs->kut_url);
                }

                dcCore::app()->blog->triggerBlog();

                Notices::addSuccessNotice(__('Links successfully deleted'));
                My::redirect(self::$kutrl_filter->values());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        Page::openModule(
            My::id(),
            self::$kutrl_filter->js(My::manageUrl(['part' => 'links'])) .
            My::jsLoad('admin')
        );

        echo
        Page::breadcrumb([
            __('Plugins') => '',
            My::name()    => My::manageUrl(),
        ]) .
        Notices::getNotices() .
        (new Para())
            ->class('top-add')
            ->items([
                (new Link())
                    ->class('button add')
                    ->href(My::manageUrl(['part' => 'link']))
                    ->title(__('New Link'))
                    ->text(__('New Link')),
            ])
            ->render();

        self::$kutrl_filter->display(
            'admin.plugin.' . My::id(),
            (new Hidden('p', My::id()))->render() . (new Hidden('part', 'links'))->render()
        );

        self::$kutrl_listing->display(
            self::$kutrl_filter,
            (new Form('form-entries'))
                ->action(My::manageUrl())
                ->method('post')
                ->fields([
                    (new Text('', '%s')),
                    (new Div())
                        ->class('two-cols')
                        ->items([
                            (new Para())
                                ->class('col checkboxes-helpers'),
                            (new Para())
                                ->class('col right')
                                ->separator('&nbsp;')
                                ->items([
                                    (new Submit('do-action'))
                                        ->class('delete')
                                        ->value(__('Delete selected short links')),
                                    ... My::hiddenFields(array_merge(['deletelinks' => 1], self::$kutrl_filter->values(true))),
                                ]),
                        ]),
                ])->render()
        );

        Page::helpBlock(My::id());

        Page::closeModule();
    }
}
