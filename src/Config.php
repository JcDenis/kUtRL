<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Text;
use Exception;

/**
 * @brief       kUtRL config class.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Config
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::CONFIG));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // no action
        if (empty($_POST['save'])) {
            return true;
        }

        # Settings
        $s = My::settings();

        $s_active              = (bool) $s->get('active');
        $s_plugin_service      = (string) $s->get('plugin_service');
        $s_admin_service       = (string) $s->get('admin_service');
        $s_tpl_service         = (string) $s->get('tpl_service');
        $s_wiki_service        = (string) $s->get('wiki_service');
        $s_allow_external_url  = (bool) $s->get('allow_external_url');
        $s_tpl_passive         = (bool) $s->get('tpl_passive');
        $s_tpl_active          = (bool) $s->get('tpl_active');
        $s_admin_entry_default = (string) $s->get('admin_entry_default');

        try {
            # settings
            $s_active              = !empty($_POST['s_active']);
            $s_admin_service       = (string) $_POST['s_admin_service'];
            $s_plugin_service      = (string) $_POST['s_plugin_service'];
            $s_tpl_service         = (string) $_POST['s_tpl_service'];
            $s_wiki_service        = (string) $_POST['s_wiki_service'];
            $s_allow_external_url  = !empty($_POST['s_allow_external_url']);
            $s_tpl_passive         = !empty($_POST['s_tpl_passive']);
            $s_tpl_active          = !empty($_POST['s_tpl_active']);
            $s_admin_entry_default = !empty($_POST['s_admin_entry_default']);

            $s->put('active', $s_active);
            $s->put('plugin_service', $s_plugin_service);
            $s->put('admin_service', $s_admin_service);
            $s->put('tpl_service', $s_tpl_service);
            $s->put('wiki_service', $s_wiki_service);
            $s->put('allow_external_url', $s_allow_external_url);
            $s->put('tpl_passive', $s_tpl_passive);
            $s->put('tpl_active', $s_tpl_active);
            $s->put('admin_entry_default', $s_admin_entry_default);

            # services
            foreach (Utils::getServices() as $service_id => $service) {
                if (is_subclass_of($service, Service::class)) {
                    $o = new $service();
                    $o->saveSettings();
                }
            }

            App::blog()->triggerBlog();

            Notices::addSuccessNotice(
                __('Configuration successfully updated.')
            );

            App::backend()->url()->redirect(
                'admin.plugins',
                ['module' => My::id(), 'conf' => 1, 'chk' => 1, 'redir' => App::backend()->__get('list')->getRedir()]
            );
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        # Default values
        $img_green = '<img src="images/check-on.png" alt="ok" />';
        $img_red   = '<img src="images/check-off.png" alt="fail" />';

        # Settings
        $s = My::settings();

        $s_active              = (bool) $s->get('active');
        $s_plugin_service      = (string) $s->get('plugin_service');
        $s_admin_service       = (string) $s->get('admin_service');
        $s_tpl_service         = (string) $s->get('tpl_service');
        $s_wiki_service        = (string) $s->get('wiki_service');
        $s_allow_external_url  = (bool) $s->get('allow_external_url');
        $s_tpl_passive         = (bool) $s->get('tpl_passive');
        $s_tpl_active          = (bool) $s->get('tpl_active');
        $s_admin_entry_default = (bool) $s->get('admin_entry_default');

        $chk_admin_service  = '';
        $chk_plugin_service = '';
        $chk_tpl_service    = '';
        $chk_wiki_service   = '';
        if (!empty($_REQUEST['chk'])) {
            if (null !== ($o = Utils::quickPlace($s_admin_service))) {
                $chk_admin_service = ($o->testService() ? $img_green : $img_red) . '&nbsp;';
            }
            if (null !== ($o = Utils::quickPlace($s_plugin_service))) {
                $chk_plugin_service = ($o->testService() ? $img_green : $img_red) . '&nbsp;';
            }
            if (null !== ($o = Utils::quickPlace($s_tpl_service))) {
                $chk_tpl_service = ($o->testService() ? $img_green : $img_red) . '&nbsp;';
            }
            if (null !== ($o = Utils::quickPlace($s_wiki_service))) {
                $chk_wiki_service = ($o->testService() ? $img_green : $img_red) . '&nbsp;';
            }
        }

        $i_config = [];
        foreach (Utils::getServices() as $service_id => $service) {
            if (!is_subclass_of($service, Service::class)) {
                continue;
            }
            $o = new $service();

            $s_items = [];

            if (!empty($_REQUEST['chk'])) {
                $img_chk = $img_red . ' ' . sprintf(__('Failed to test %s API.'), $o->get('name'));

                try {
                    if ($o->testService()) {
                        $img_chk = $img_green . ' ' . sprintf(__('%s API is well configured and runing.'), $o->get('name'));
                    }
                } catch (Exception $e) {
                    App::error()->add(sprintf(__('Failed to test service %s: %s'), $o->get('name'), $e->getMessage()));
                }
                $s_items[] = (new Text(null, sprintf('<p><em>%s</em></p>', $img_chk) . $o->error->toHTML()));
            }

            if ($o->get('home') != '') {
                $s_items[] = (new Para())
                    ->items([
                        (new Link())
                            ->href($o->get('home'))
                            ->title(__('homepage'))
                            ->text(sprintf(__('Learn more about %s.'), $o->get('name'))),
                    ]);
            }

            $i_config[] = (new Text('hr'));
            $i_config[] = (new Div('settings-' . $service_id))
                ->items([
                    (new Text('h5', $o->get('name'))),
                    ... $s_items,
                    $o->settingsForm(),
                ]);
        }

        echo (new Div())
            ->class('fieldset')
            ->items([
                (new text('h4', __('Settings'))),
                (new Div('setting-plugin'))
                    ->items([
                        (new Text('h5', __('Activation'))),
                        (new Para())
                            ->items([
                                (new Checkbox('s_active', $s_active))
                                    ->value(1),
                                (new Label(__('Enable plugin'), Label::OUTSIDE_LABEL_AFTER))
                                    ->class('classic')
                                    ->for('s_active'),
                            ]),
                    ]),
                (new Text('hr')),

                (new Div('setting-option'))
                    ->items([
                        (new Text('h5', __('Behaviors'))),
                        (new Para())
                            ->items([
                                (new Checkbox('s_allow_external_url', $s_allow_external_url))
                                    ->value(1),
                                (new Label(__('Allow short link for external URL'), Label::OUTSIDE_LABEL_AFTER))
                                    ->class('classic')
                                    ->for('s_allow_external_url'),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(__('Not only link started with this blog URL could be shortened.')),
                        (new Para())
                            ->items([
                                (new Checkbox('s_tpl_passive', $s_tpl_passive))
                                    ->value(1),
                                (new Label(__('Passive mode'), Label::OUTSIDE_LABEL_AFTER))
                                    ->class('classic')
                                    ->for('s_tpl_passive'),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(__('If this extension is disabled and the passive mode is enabled, "kutrl" tags (like EntryKurl) will display long urls instead of nothing on templates.')),
                        (new Para())
                            ->items([
                                (new Checkbox('s_tpl_active', $s_tpl_active))
                                    ->value(1),
                                (new Label(__('Active mode'), Label::OUTSIDE_LABEL_AFTER))
                                    ->class('classic')
                                    ->for('s_tpl_active'),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(__('If the active mode is enabled, all know default template tags (like EntryURL) will display short urls instead of long ones on templates.')),
                        (new Note())
                            ->class('form-note')
                            ->text(__('You can disable URL shortening for a specific template tag by adding attribute disable_kutrl="1" to it . ')),
                        (new Note())
                            ->class('warning')
                            ->text(__('We strongly discourage using active mode as it crashes public post form and complex url if theme is not customize for kUtRL.')),
                        (new Para())
                            ->items([
                                (new Checkbox('s_admin_entry_default', $s_admin_entry_default))
                                    ->value(1),
                                (new Label(__('Create short link for new entries'), Label::OUTSIDE_LABEL_AFTER))
                                    ->class('classic')
                                    ->for('s_admin_entry_default'),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(__('This can be changed on page of creation/edition of an entry.')),
                    ]),
                (new Text('hr')),

                (new Div('setting-service'))
                    ->items([
                        (new Text('h5', __('Default services'))),
                        (new Para())
                            ->items([
                                (new Label($chk_admin_service . __('Administration:'), Label::OUTSIDE_LABEL_BEFORE))
                                    ->for('s_admin_service'),
                                (new Select('s_admin_service'))
                                    ->items(Combo::servicesCombo())
                                    ->default($s_admin_service),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(__('Service to use in this admin page and on edit page of an entry.')),
                        (new Para())
                            ->items([
                                (new Label($chk_plugin_service . __('Extensions:'), Label::OUTSIDE_LABEL_BEFORE))
                                    ->for('s_plugin_service'),
                                (new Select('s_plugin_service'))
                                    ->items(Combo::servicesCombo())
                                    ->default($s_plugin_service),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(__('Service to use on third part plugins.')),
                        (new Para())
                            ->items([
                                (new Label($chk_tpl_service . __('Templates:'), Label::OUTSIDE_LABEL_BEFORE))
                                    ->for('s_tpl_service'),
                                (new Select('s_tpl_service'))
                                    ->items(Combo::servicesCombo())
                                    ->default($s_tpl_service),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(__('Shorten links automatically when using template value like "EntryKutrl".')),
                        (new Para())
                            ->items([
                                (new Label($chk_wiki_service . __('Contents:'), Label::OUTSIDE_LABEL_BEFORE))
                                    ->for('s_wiki_service'),
                                (new Select('s_wiki_service'))
                                    ->items(Combo::servicesCombo())
                                    ->default($s_wiki_service),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(__('Shorten links automatically found in contents using wiki synthax.')),
                    ]),
            ])
            ->render() .

            (new Div())
            ->class('fieldset')
            ->items([
                (new text('h4', __('Settings'))),
                (new Note())
                    ->class('info')
                    ->text(__('List of services you can use to shorten links with pkugin kUtRL.')),
                ... $i_config,
            ])
            ->render();
    }
}
