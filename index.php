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

$header = '';
$part = isset($_REQUEST['part']) ? $_REQUEST['part'] : 'links';
$action = isset($_POST['action']) ? $_POST['action'] : '';

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

# links
} else {
    $services_combo = [];
    foreach(kutrl::getServices($core) as $service_id => $service) {
        $o = new $service($core);
        $services_combo[__($o->name)] = $o->id;
    }
    $ext_services_combo = array_merge([__('Disabled')=>''], $services_combo);
    $lst_services_combo = array_merge(['-'=>''], $services_combo);

    $log = new kutrlLog($core);

    $kUtRL_filter = new adminGenericFilter($core, 'kUtRL');
    $kUtRL_filter->add('part', 'links');
    $kUtRL_filter->add(dcAdminFilters::getPageFilter());
    $kUtRL_filter->add(dcAdminFilters::getSelectFilter(
        'urlsrv', __('Service:'), $lst_services_combo, 'kut_type'
    ));

    $params = $kUtRL_filter->params();

    try {
        $list_all = $log->getLogs($params);
        $list_counter = $log->getLogs($params, true)->f(0);
        $list_current = new kutrlLinksList($core, $list_all, $list_counter);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }

    $header = 
        $kUtRL_filter->js($core->adminurl->get('admin.plugin.kUtRL', ['part' => 'links'])) .
        dcPage::jsLoad(dcPage::getPF('kUtRL/js/admin.js'));

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
            $core->adminurl->redirect('admin.plugin.kUtRL', $kUtRL_filter->values());
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }    
    }
}

# display header
echo 
'<html><head><title>kUtRL, ' . __('Links shortener') . '</title>' .
$header .
'</head><body>';

# display link creation
if ($part == 'link') {
    echo 
    dcPage::breadcrumb([
            __('Plugins') => '',
            __('Links shortener') => $core->adminurl->get('admin.plugin.kUtRL'),
            __('New link')  => ''
    ]) .
    dcPage::notices();

    if (null === $kut) {
        echo '<p>' . __('You must set an admin service.') . '</p>';
    } else {
        echo '
        <div class="fieldset">
        <h4>' . sprintf(__('Shorten link using service "%s"'), $kut->name) . '</h4>
        <form id="create-link" method="post" action="' . $core->adminurl->get('admin.plugin.kUtRL') . '">

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
} else {
    echo    
    dcPage::breadcrumb([
            __('Plugins') => '',
            __('Links shortener') => ''
    ]) .
    dcPage::notices() .

    '<p class="top-add"><a class="button add" href="' . 
        $core->adminurl->get('admin.plugin.kUtRL', ['part' => 'link']) . 
    '">' . __('New Link') .'</a></p>';

    $kUtRL_filter->display('admin.plugin.kUtRL', form::hidden('p', 'kUtRL') . form::hidden('part', 'links'));

    $list_current->display(
        $kUtRL_filter->value('page'),
        $kUtRL_filter->nb, 
        '<form action="' . $core->adminurl->get('admin.plugin.kUtRL') . '" method="post" id="form-entries">

        %s

        <div class="two-cols">
        <div class="col left">
        <p class="checkboxes-helpers"></p>
        </div>
        <p class="col right">
        <input id="do-action" type="submit" value="' . __('Delete selected short links') . '" /></p>' .
        $core->adminurl->getHiddenFormFields('admin.plugin.kUtRL', array_merge(['deletelinks' =>  1], $kUtRL_filter->values(true))) . 
        $core->formNonce() . '
        </p>
        </div>
        </form>',
        $kUtRL_filter->show()
    );
}

# display footer
dcPage::helpBlock('kUtRL');

echo '</body></html>';