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

# Check user perms
dcPage::check(dcAuth::PERMISSION_ADMIN);

# Settings
$s = dcCore::app()->blog->settings->kUtRL;

# Default values
$img_green = '<img src="images/check-on.png" alt="ok" />';
$img_red   = '<img src="images/check-off.png" alt="fail" />';

$services_combo = [];
foreach (kUtRL::getServices() as $service_id => $service) {
    $o                            = new $service();
    $services_combo[__($o->name)] = $o->id;
}
$ext_services_combo = array_merge([__('Disabled') => ''], $services_combo);
$lst_services_combo = array_merge(['-' => ''], $services_combo);

$s_active              = (bool) $s->kutrl_active;
$s_plugin_service      = (string) $s->kutrl_plugin_service;
$s_admin_service       = (string) $s->kutrl_admin_service;
$s_tpl_service         = (string) $s->kutrl_tpl_service;
$s_wiki_service        = (string) $s->kutrl_wiki_service;
$s_allow_external_url  = (bool) $s->kutrl_allow_external_url;
$s_tpl_passive         = (bool) $s->kutrl_tpl_passive;
$s_tpl_active          = (bool) $s->kutrl_tpl_active;
$s_admin_entry_default = (string) $s->kutrl_admin_entry_default;

if (!empty($_POST['save'])) {
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

        $s->put('kutrl_active', $s_active);
        $s->put('kutrl_plugin_service', $s_plugin_service);
        $s->put('kutrl_admin_service', $s_admin_service);
        $s->put('kutrl_tpl_service', $s_tpl_service);
        $s->put('kutrl_wiki_service', $s_wiki_service);
        $s->put('kutrl_allow_external_url', $s_allow_external_url);
        $s->put('kutrl_tpl_passive', $s_tpl_passive);
        $s->put('kutrl_tpl_active', $s_tpl_active);
        $s->put('kutrl_admin_entry_default', $s_admin_entry_default);

        # services
        foreach (kUtRL::getServices() as $service_id => $service) {
            $o = new $service();
            $o->saveSettings();
        }

        dcCore::app()->blog->triggerBlog();

        dcAdminNotices::addSuccessNotice(
            __('Configuration successfully updated.')
        );

        dcCore::app()->adminurl->redirect(
            'admin.plugins',
            ['module' => 'kUtRL', 'conf' => 1, 'chk' => 1, 'redir' => dcCore::app()->admin->list->getRedir()]
        );
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

echo '
<div class="fieldset"><h4>' . __('Settings') . '</h4>
<div id="setting-plugin">
<h5>' . __('Activation') . '</h5>
<p><label class="classic">' .
form::checkbox(['s_active'], '1', $s_active) .
__('Enable plugin') . '</label></p>
</div>

<hr/><div id="setting-option">
<h5>' . __('Behaviors') . '</h5>
<p><label class="classic">' .
form::checkbox(['s_allow_external_url'], '1', $s_allow_external_url) .
__('Allow short link for external URL') . '</label></p>
<p class="form-note">' . __('Not only link started with this blog URL could be shortened.') . '</p>
<p><label class="classic">' .
form::checkbox(['s_tpl_passive'], '1', $s_tpl_passive) .
__('Passive mode') . '</label></p>
<p class="form-note">' . __('If this extension is disabled and the passive mode is enabled, "kutrl" tags (like EntryKurl) will display long urls instead of nothing on templates.') . '</p>
<p><label class="classic">' .
form::checkbox(['s_tpl_active'], '1', $s_tpl_active) .
__('Active mode') . '</label></p>
<p class="form-note">' . __('If the active mode is enabled, all know default template tags (like EntryURL) will display short urls instead of long ones on templates.') . '<br />' .
__('You can disable URL shortening for a specific template tag by adding attribute disable_kutrl="1" to it . ') . '</p>
<p class="warning">' . __('We strongly discourage using active mode as it crashes public post form and complex url if theme is not customize for kUtRL.') . '</p>
<p><label class="classic">' .
form::checkbox(['s_admin_entry_default'], '1', $s_admin_entry_default) .
__('Create short link for new entries') . '</label></p>
<p class="form-note">' . __('This can be changed on page of creation/edition of an entry.') . '</p>
</div>

<hr/><div id="setting-service">
<h5>' . __('Default services') . '</h5>
<p><label>';
if (!empty($_REQUEST['chk'])) {
    if (null !== ($o = kUtRL::quickPlace($s_admin_service))) {
        echo $o->testService() ? $img_green : $img_red;
    }
}
echo '&nbsp;' . __('Administration:') . '<br />' .
form::combo(['s_admin_service'], $services_combo, $s_admin_service) . '
</label></p>
<p class="form-note">' . __('Service to use in this admin page and on edit page of an entry.') . '</p>
<p><label>';
if (!empty($_REQUEST['chk'])) {
    if (null !== ($o = kUtRL::quickPlace($s_plugin_service))) {
        echo $o->testService() ? $img_green : $img_red;
    }
}
echo '&nbsp;' . __('Extensions:') . '<br />' .
form::combo(['s_plugin_service'], $services_combo, $s_plugin_service) . '
</label></p>
<p class="form-note">' . __('Service to use on third part plugins.') . '</p>
<p><label>';
if (!empty($_REQUEST['chk'])) {
    if (null !== ($o = kUtRL::quickPlace($s_tpl_service))) {
        echo $o->testService() ? $img_green : $img_red;
    }
}
echo '&nbsp;' . __('Templates:') . '<br />' .
form::combo(['s_tpl_service'], $ext_services_combo, $s_tpl_service) . '
</label></p>
<p class="form-note">' . __('Shorten links automatically when using template value like "EntryKutrl".') . '</p>
<p><label>';
if (!empty($_REQUEST['chk'])) {
    if (null !== ($o = kUtRL::quickPlace($s_wiki_service))) {
        echo $o->testService() ? $img_green : $img_red;
    }
}
echo '&nbsp;' . __('Contents:') . '<br />' .
form::combo(['s_wiki_service'], $ext_services_combo, $s_wiki_service) . '
</label></p>
<p class="form-note">' . __('Shorten links automatically found in contents using wiki synthax.') . '</p>
</div>
</div>

<div class="fieldset">
<h4>' . __('Services') . '</h4>
<p class="info">' . __('List of services you can use to shorten links with pkugin kUtRL.') . '</p>
';

foreach (kUtRL::getServices() as $service_id => $service) {
    $o = new $service();

    echo '<hr/><div id="setting-' . $service_id . '"><h5>' . $o->name . '</h5>';

    if (!empty($_REQUEST['chk'])) {
        $img_chk = $img_red . ' ' . sprintf(__('Failed to test %s API.'), $o->name);

        try {
            if ($o->testService()) {
                $img_chk = $img_green . ' ' . sprintf(__('%s API is well configured and runing.'), $o->name);
            }
        } catch (Exception $e) {
            dcCore::app()->error->add(sprintf(__('Failed to test service %s: %s'), $o->name, $e->getMessage()));
        }
        echo sprintf('<p><em>%s</em></p>', $img_chk) . $o->error->toHTML();
    }
    if ($o->home != '') {
        echo '<p><a title="' . __('homepage') . '" href="' . $o->home . '">' . sprintf(__('Learn more about %s.'), $o->name) . '</a></p>';
    }
    $o->settingsForm();

    echo '</div>';
}

echo'</div>';
