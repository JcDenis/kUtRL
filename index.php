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
dcPage::check('admin');

# Settings
$s = $core->blog->settings->kUtRL;

# Default values
$show_filters = false;
$p_url = $core->adminurl->get('admin.plugin.kUtRL');
$part = isset($_REQUEST['part']) ? $_REQUEST['part'] : 'links';
$action = isset($_POST['action']) ? $_POST['action'] : '';

# used in setting & links
if (in_array($part, ['setting', 'links'])) {
    $services_combo = [];
    foreach(kutrl::getServices($core) as $service_id => $service) {
        $o = new $service($core);
        $services_combo[__($o->name)] = $o->id;
    }
    $ext_services_combo = array_merge([__('Disabled')=>''], $services_combo);
    $lst_services_combo = array_merge(['-'=>''], $services_combo);
}

# used in setting & service
if (in_array($part, ['setting', 'service'])) {
    $img_green = '<img src="images/check-on.png" alt="ok" />';
    $img_red = '<img src="images/check-off.png" alt="fail" />';
}

# setting
if ($part == 'setting') {
    $s_active = (boolean) $s->kutrl_active;
    $s_plugin_service = (string) $s->kutrl_plugin_service;
    $s_admin_service = (string) $s->kutrl_admin_service;
    $s_tpl_service = (string) $s->kutrl_tpl_service;
    $s_wiki_service = (string) $s->kutrl_wiki_service;
    $s_allow_external_url = (boolean) $s->kutrl_allow_external_url;
    $s_tpl_passive = (boolean) $s->kutrl_tpl_passive;
    $s_tpl_active = (boolean) $s->kutrl_tpl_active;
    $s_admin_entry_default = (string) $s->kutrl_admin_entry_default;

    if (!empty($_POST['save'])) {
        try {
            $s_active = !empty($_POST['s_active']);
            $s_admin_service = (string) $_POST['s_admin_service'];
            $s_plugin_service = (string) $_POST['s_plugin_service'];
            $s_tpl_service = (string) $_POST['s_tpl_service'];
            $s_wiki_service = (string) $_POST['s_wiki_service'];
            $s_allow_external_url = !empty($_POST['s_allow_external_url']);
            $s_tpl_passive = !empty($_POST['s_tpl_passive']);
            $s_tpl_active = !empty($_POST['s_tpl_active']);
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

            $core->blog->triggerBlog();

            dcPage::addSuccessNotice(
                __('Configuration successfully saved')
            );

            http::redirect($p_url . '&part=setting');
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }
}

# services
if ($part == 'service' && !empty($_POST['save'])) {
    try {
        foreach(kutrl::getServices($core) as $service_id => $service) {
            $o = new $service($core);
            $o->saveSettings();
        }
        $core->blog->triggerBlog();

        dcPage::addSuccessNotice(
            __('Configuration successfully saved')
        );

        http::redirect($p_url . '&part=service');
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# link creation
if ($part == 'link') {
    $kut = kutrl::quickPlace('admin');

    if (!empty($_POST['save'])) {
        try {
            if (null === $kut) {
                throw new Exception('Unknow service');
            }
            $url = trim($core->con->escape($_POST['str']));
            $hash = empty($_POST['custom']) ? null : $_POST['custom'];

            if (empty($url)) {
                throw new Exception(__('There is nothing to shorten.'));
            }
            if (!$kut->testService()) {
                throw new Exception(__('Service is not well configured.'));
            }
            if (null !== $hash && !$kut->allow_custom_hash) {
                throw new Exception(__('This service does not allowed custom hash.'));
            }
            if (!$kut->isValidUrl($url)) {
                throw new Exception(__('This link is not a valid URL.'));
            }
            if (!$kut->isLongerUrl($url)) {
                throw new Exception(__('This link is too short.'));
            }
            if (!$kut->isProtocolUrl($url)) {
                throw new Exception(__('This type of link is not allowed.'));
            }
            if (!$kut->allow_external_url && !$kut->isBlogUrl($url)) {
                throw new Exception(__('Short links are limited to this blog URL.'));
            }
            if ($kut->isServiceUrl($url)) {
                throw new Exception(__('This link is already a short link.'));
            }
            if (null !== $hash && false !== ($rs = $kut->isKnowHash($hash))) {
                throw new Exception(__('This custom short url is already taken.'));
            }
            if (false !== ($rs = $kut->isKnowUrl($url))) {
                $url = $rs->url;
                $new_url = $kut->url_base  .$rs->hash;

                dcPage::addSuccessNotice(sprintf(
                    __('Short link for %s is %s'),
                    '<strong>' . html::escapeHTML($url) . '</strong>',
                    '<a href="' . $new_url . '">' . $new_url . '</a>'
                ));
            } else {
                if (false === ($rs = $kut->hash($url, $hash))) {
                    if ($kut->error->flag()) {
                        throw new Exception($kut->error->toHTML());
                    }
                    throw new Exception(__('Failed to create short link. This could be caused by a service failure.'));
                } else {
                    $url = $rs->url;
                    $new_url = $kut->url_base . $rs->hash;

                    dcPage::addSuccessNotice(sprintf(
                        __('Short link for %s is %s'),
                        '<strong>' . html::escapeHTML($url) . '</strong>',
                        '<a href="' . $new_url . '">' . $new_url . '</a>'
                    ));

                    # ex: Send new url to messengers
                    if (!empty($rs)) {
                        $core->callBehavior('adminAfterKutrlCreate', $core, $rs,__('New short URL'));
                    }
                }
            }
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }
}

# links
if ($part == 'links') {
    $log = new kutrlLog($core);

    $sortby_combo = [
        __('Date') => 'kut_dt',
        __('Long link') => 'kut_url',
        __('Short link') => 'kut_hash'
    ];
    $order_combo = [
        __('Descending') => 'desc',
        __('Ascending') => 'asc'
    ];

    $core->auth->user_prefs->addWorkspace('interface');
    $default_sortby = 'kut_dt';
    $default_order = $core->auth->user_prefs->interface->posts_order ?: 'desc';
    $nb_per_page = $core->auth->user_prefs->interface->nb_posts_per_page ?: 30;
    $sortby = !empty($_GET['sortby']) ? $_GET['sortby'] : $default_sortby;
    $order = !empty($_GET['order']) ? $_GET['order'] : $default_order;
    $urlsrv = !empty($_GET['urlsrv']) ? $_GET['urlsrv'] : '';
    $page = !empty($_GET['page']) ? max(1, (integer) $_GET['page']) : 1;
    $show_filters = false;

    if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
        if ($nb_per_page != (integer) $_GET['nb']) {
            $show_filters = true;
        }
        $nb_per_page = (integer) $_GET['nb'];
    }

    $params = [];
    $params['limit'] = [(($page-1)*$nb_per_page), $nb_per_page];

    if (!in_array($sortby, $sortby_combo)) {
        $sortby = $default_sortby;
    }

    if (!in_array($order, $order_combo)) {
        $order = $default_order;
    }
    $params['order'] = $sortby . ' ' . $order;

    if ($urlsrv != '' && in_array($urlsrv, $lst_services_combo)) {
        $params['kut_type'] = $urlsrv;
    }

    if ($sortby != $default_sortby || $order != $default_order || $urlsrv != '') {
        $show_filters = true;
    }

    try {
        $list_all = $log->getLogs($params);
        $list_counter = $log->getLogs($params, true)->f(0);
        $list_current = new kutrlLinksList($core, $list_all, $list_counter);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }

    if (!empty($_POST['deletelinks'])) {
        try {
            foreach($_POST['entries'] as $id) {
                $rs = $log->getLogs(['kut_id' => $id]);
                if ($rs->isEmpty()) {
                    continue;
                }
                if (null === ($o = kutrl::quickService($rs->kut_type))) {
                    continue;
                }
                $o->remove($rs->kut_url);
            }

            $core->blog->triggerBlog();

            dcPage::addSuccessNotice(
                __('Links successfully deleted')
            );

            http::redirect($p_url . '&part=links&urlsrv=' . $urlsrv . '&sortby=' . $sortby . '&order=' . $order . '&nb=' . $nb_per_page . '&page=' . $page);
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }    
    }
}

# display header
echo 
'<html><head><title>kUtRL, ' . __('Links shortener') . '</title>';

if ($part == 'links') {
    echo 
    '<script type="text/javascript">' . "\n" .
    "//<![CDATA[\n" .
    dcPage::jsVar(
        'dotclear.filter_reset_url',
        html::escapeJS($p_url . '&part=links')
    ) . "\n" .
    "\$(function(){\$('.checkboxes-helpers').each(function(){dotclear.checkboxesHelpers(this);});});\n" .
    "//]]>\n" .
    "</script>\n" .
    dcPage::jsFilterControl($show_filters);
}

echo 
'</head><body>';

# display setting
if ($part == 'setting') {
    echo 
    dcPage::breadcrumb(
        [
            __('Links shortener') => '',
            '<span class="page-title">' . __('Plugin configuration') . '</span>'  => '',
            __('Services configuration') => $p_url .'&amp;part=service'
        ],
        ['hl' => false]
    ) .
    dcPage::notices() .

    '<h3>' . __('Plugin configuration') . '</h3>
    <p><a class="back" href="' . $p_url . '">' . __('Back to links list') . '</a></p>

    <form id="setting-form" method="post" action="' . $p_url . '">

    <div class="fieldset" id="setting-plugin">
    <h4>' .  __('Plugin activation') . '</h4>
    <p><label class="classic">' . 
    form::checkbox(['s_active'], '1', $s_active) . 
    __('Enable plugin') . '</label></p>
    </div>

    <div class="fieldset" id="setting-option">
    <h4>' .  __('Behaviors') . '</h4>
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

    <div class="fieldset" id="setting-service">
    <h4>' .  __('Default services') . '</h4>
    <p><label>';
    if (!empty($msg)) {
        if (null !== ($o = kutrl::quickPlace($s_admin_service))) {
            echo $o->testService() ? $img_green : $img_red;
        }
    }
    echo '&nbsp;' . __('Administration:') . '<br />' . 
    form::combo(['s_admin_service'], $services_combo, $s_admin_service) . '
    </label></p>
    <p class="form-note">' . __('Service to use in this admin page and on edit page of an entry.') . '</p>
    <p><label>';
    if (!empty($msg)) {
        if (null !== ($o = kutrl::quickPlace($s_plugin_service))) {
            echo $o->testService() ? $img_green : $img_red;
        }
    }
    echo '&nbsp;' . __('Extensions:') . '<br />' . 
    form::combo(['s_plugin_service'], $services_combo, $s_plugin_service) . '
    </label></p>
    <p class="form-note">' . __('Service to use on third part plugins.') . '</p>
    <p><label>';
    if (!empty($msg)) {
        if (null !== ($o = kutrl::quickPlace($s_tpl_service))) {
            echo $o->testService() ? $img_green : $img_red;
        }
    }
    echo '&nbsp;' . __('Templates:') . '<br />' . 
    form::combo(['s_tpl_service'], $ext_services_combo, $s_tpl_service) . '
    </label></p>
    <p class="form-note">' . __('Shorten links automatically when using template value like "EntryKutrl".') . '</p>
    <p><label>';
    if (!empty($msg)) {
        if (null !== ($o = kutrl::quickPlace($s_wiki_service))) {
            echo $o->testService() ? $img_green : $img_red;
        }
    }
    echo '&nbsp;' . __('Contents:') . '<br />' . 
    form::combo(['s_wiki_service'], $ext_services_combo, $s_wiki_service) . '
    </label></p>
    <p class="form-note">' . __('Shorten links automatically found in contents using wiki synthax.') . '</p>
    </div>

    <p><input type="submit" name="save" value="' . __('Save') . '" />' . 
    $core->formNonce() . 
    form::hidden(['part'], 'setting') . '
    </p>
    </form>';
}

# display service
if ($part == 'service') {
    echo 
    dcPage::breadcrumb(
        [
            __('Links shortener') => '',
            __('Plugin configuration')  => $p_url . '&amp;part=setting',
            '<span class="page-title">' . __('Services configuration') . '</span>' => ''
        ],
        ['hl' => false]
    ) .
    dcPage::notices() .

    '<h3>' . __('Services configuration') . '</h3>' .
    '<p><a class="back" href="' . $p_url . '">' . __('Back to links list') . '</a></p>' .
    '<form id="service-form" method="post" action="' . $p_url . '">';

    foreach(kutrl::getServices($core) as $service_id => $service) {
        $o = new $service($core);

        echo '<div class="fieldset" id="setting-' . $service_id . '"><h4>' . $o->name . '</h4>';

        if (!empty($_POST['save'])) {
            echo '<p><em>' . (
            $o->testService() ?
                $img_green . ' ' . sprintf(__('%s API is well configured and runing.'), $o->name) :
                $img_red . ' ' . sprintf(__('Failed to test %s API.'), $o->name)
            ) . '</em></p>';
            //if ($o->error->flag()) {
                echo $o->error->toHTML();
            //}
        }
        if ($o->home != '') {
            echo '<p><a title="' . __('homepage') . '" href="' . $o->home . '">' . sprintf(__('Learn more about %s.'), $o->name) . '</a></p>';
        }
        $o->settingsForm();

        echo '</div>';
    }

    echo '
    <div class="clear">
    <p><input type="submit" name="save" value="' . __('Save') . '" />' . 
    $core->formNonce() . 
    form::hidden(['part'], 'service') . '
    </p></div>
    </form>';
}

# display link creation
if ($part == 'link') {
    echo 
    dcPage::breadcrumb(
        [
            __('Links shortener') => '',
            __('Links') => $core->adminurl->get('admin.plugin.kUtRL'),
            '<span class="page-title">' . __('New link') . '</span>'  => ''
        ],
        ['hl' => false]
    ) .
    dcPage::notices();

    if (null === $kut) {
        echo '<p>' . __('You must set an admin service.') . '</p>';
    } else {
        echo '
        <div class="fieldset">
        <h4>' . sprintf(__('Shorten link using service "%s"'), $kut->name) . '</h4>
        <form id="create-link" method="post" action="' . $p_url . '">

        <p><label for="str">' . __('Long link:') . '</label>' .
        form::field('str', 100, 255, '') . '</p>';

        if ($kut->allow_custom_hash) {
            echo
            '<p><label for="custom">' . __('Custom short link:') . '</label>' .
            form::field('custom', 50, 32, '') . '</p>' . 
            '<p class="form-note">' . __('Only if you want a custom short link.') . '</p>';

            if ($kut->admin_service == 'local') {
                echo '<p class="form-note">' . 
                __('You can use "bob!!" if you want a semi-custom link, it starts with "bob" and "!!" will be replaced by an increment value.') . 
                '</p>';
            }
        }

        echo '
        <p><input type="submit" name="save" value="' . __('Save') . '" />' . 
        $core->formNonce() . 
        form::hidden(['part'], 'link') . '
        </p></div>
        </form>';
    }
}

if ($part == 'links') {
    echo    
    dcPage::breadcrumb(
        [
            __('Links shortener') => '',
            '<span class="page-title">' . __('Links') . '</span>'  => '',
            __('New link') => $core->adminurl->get('admin.plugin.kUtRL').'&amp;part=link'
        ],
        ['hl' => false]
    ) .
    dcPage::notices();

    echo '
    <form action="' . $p_url . '&amp;part=links" method="get" id="filters-form">
    <h3 class="out-of-screen-if-js">' . __('Show filters and display options') . '</h3>
    <div class="table">
    <div class="cell">
    <h4>' . __('Filters') . '</h4>
    <p><label for="urlsrv" class="ib">' . __('Service:') . '</label>' .
    form::combo('urlsrv', $lst_services_combo, $urlsrv) . '</p>
    </div>

    <div class="cell filters-options">
    <h4>' . __('Display options') . '</h4>
    <p><label for="sortby" class="ib">' . __('Order by:') . '</label>' .
    form::combo('sortby', $sortby_combo, $sortby) . '</p>
    <p><label for="order" class="ib">' . __('Sort:') . '</label>' .
    form::combo('order', $order_combo, $order) . '</p>
    <p><span class="label ib">' . __('Show') . '</span> <label for="nb" class="classic">' .
    form::number('nb', 0, 999, $nb_per_page) . 
    __('entries per page') . '</label></p>' .

    form::hidden('part', 'links') .
    form::hidden('p', 'kUtRL') . '

    </div>
    </div>

    <p><input type="submit" value="' . __('Apply filters and display options') . '" />
    <br class="clear" /></p>
    </form>';

    $list_current->display(
        $page,
        $nb_per_page, 
        '<form action="' . $p_url . '&amp;part=links" method="post" id="form-actions">

        %s

        <div class="two-cols">
        <p class="col checkboxes-helpers"></p>
        <p class="col right">
        <input type="submit" value="' . __('Delete selected short links') . '" />' . 
        form::hidden(['deletelinks'], 1) . 
        form::hidden(['urlsrv'], $urlsrv) . 
        form::hidden(['sortby'], $sortby) . 
        form::hidden(['order'], $order) . 
        form::hidden(['page'], $page) . 
        form::hidden(['nb'], $nb_per_page) . 
        form::hidden(['part'], 'links') . 
        $core->formNonce() . '
        </p>
        </div>
        </form>',
        $show_filters
    );
}

# display footer
dcPage::helpBlock('kUtRL');

echo 
'<p class="clear right">
<a href="' . $p_url . '&amp;part=setting">' . __('Plugin configuration') . '</a> - 
<a href="' . $p_url . '&amp;part=service">' . __('Services configuration') . '</a> - 
kUtRL - ' . $core->plugins->moduleInfo('kUtRL', 'version') . '&nbsp;
<a href="' . $core->plugins->getModules('kUtRL')['support'] . '">
<img alt="' . __('kUtRL') . '" src="index.php?pf=kUtRL/icon.png" /></a>
</p>
</body></html>';