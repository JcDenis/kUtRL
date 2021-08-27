<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of kUtRL, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2011 JC Denis and contributors
# jcdenis@gdwd.com
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

# This file manage links of kUtRL (called from index.php)

if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

# Short links list
class kutrlLinkslist extends adminGenericList
{
    public function display($page, $nb_per_page, $url)
    {
        if ($this->rs->isEmpty()) {
            echo '<p><strong>' . __('No short link') . '</strong></p>';
        } else {
            $pager = new pager($page, $this->rs_count, $nb_per_page, 10);

            $pager->base_url = $url;

            $html_block =
                '<table class="clear">' .
                '<thead>' .
                '<tr>' .
                '<th class="nowrap" colspan="2">' . __('Hash') . '</th>' .
                '<th class="maximal">' . __('Link') . '</th>' .
                '<th class="nowrap">' . __('Date') . '</th>' .
                '<th class="nowrap">' . __('Service') . '</th>' .
                '</tr>' .
                '</thead>' .
                '<tbody>%s</tbody>' .
                '</table>';

            echo '<p>' . __('Page(s)') . ' : ' . $pager->getLinks() . '</p>';
            $blocks = explode('%s', $html_block);
            echo $blocks[0];

            $this->rs->index(((integer)$page - 1) * $nb_per_page);
            $iter = 0;
            while ($iter < $nb_per_page) {
            
                echo $this->line($url,$iter);
                
                if ($this->rs->isEnd()) {
                    break;
                } else {
                    $this->rs->moveNext();
                }
                $iter++;
            }
            echo $blocks[1];
            echo '<p>' . __('Page(s)') . ' : ' . $pager->getLinks() . '</p>';
        }
    }

    private function line($url, $loop)
    {
        $type = $this->rs->kut_type;
        $hash = $this->rs->kut_hash;

        if (null !== ($o = kutrl::quickService($this->rs->kut_type))) {
            $type = '<a href="' . $o->home . '" title="' . $o->name . '">' . $o->name . '</a>';
            $hash = '<a href="' . $o->url_base . $hash . '" title="' . $o->url_base . $hash . '">' . $hash . '</a>';
        }

        return
        '<tr class="line">' . "\n" .
        '<td class="nowrap">' .
            form::checkbox(['entries[' . $loop . ']'], $this->rs->kut_id, 0) .
        '</td>' .
        '<td class="nowrap">' .
            $hash .
        "</td>\n" .
        '<td class="maximal">' .
        '<a href="' . $this->rs->kut_url . '">' . $this->rs->kut_url . '</a>' .
        "</td>\n" .
        '<td class="nowrap">' .
            dt::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->kut_dt, $this->core->auth->getInfo('user_tz')) .
        "</td>\n" .
        '<td class="nowrap">' .
            $type .
        "</td>\n" .
        '</tr>' . "\n";
    }
}

# Logs class
$log = new kutrlLog($core);

# Filters
$show_filters = false;
$urlsrv = !empty($_GET['urlsrv']) ? $_GET['urlsrv'] : '';
$sortby = !empty($_GET['sortby']) ? $_GET['sortby'] : 'kut_dt';
$order = !empty($_GET['order']) ? $_GET['order'] : 'desc';

$page = !empty($_GET['page']) ? (integer) $_GET['page'] : 1;
$nb_per_page =  30;
if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
    if ($nb_per_page != $_GET['nb']) {
        $show_filters = true;
    }
    $nb_per_page = (integer) $_GET['nb'];
}

# Combos
$sortby_combo = [
    __('Date') => 'kut_dt',
    __('Long link') => 'kut_url',
    __('Short link') => 'kut_hash'
];

$order_combo = [
    __('Descending') => 'desc',
    __('Ascending') => 'asc'
];

$services_combo = [];
foreach(kutrl::getServices($core) as $service_id => $service)
{
    $o = new $service($core);
    $services_combo[__($o->name)] = $o->id;
}
$ext_services_combo = array_merge([__('Disabled') => ''], $services_combo);
$lst_services_combo = array_merge(['-' => ''], $services_combo);

# Params for list
$params = [];
$params['limit'] = [(($page-1)*$nb_per_page), $nb_per_page];

if ($sortby != '' && in_array($sortby, $sortby_combo)) {
    if ($urlsrv != '' && in_array($urlsrv, $lst_services_combo)) {
        $params['kut_type'] = $urlsrv;
    }
    if ($order != '' && in_array($order, $order_combo)) {
        $params['order'] = $sortby . ' ' . $order;
    }
    if ($sortby != 'kut_dt' || $order != 'desc' || $urlsrv != '') {
        $show_filters = true;
    }
}

$pager_base_url = 
    $p_url .
    '&amp;tab=list' .
    '&amp;urlsrv=' . $urlsrv .
    '&amp;sortby=' . $sortby .
    '&amp;order=' . $order .
    '&amp;nb=' . $nb_per_page .
    '&amp;page=%s';

# Delete links from list
if ($action == 'deletelinks') {
    try {
        foreach($_POST['entries'] as $k => $id) {
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
        http::redirect($p_url . '&part=links&urlsrv=' . $urlsrv . '&sortby=' . $sortby . '&order=' . $order . '&nb=' . $nb_per_page . '&page=' . $page . '&msg=' . $action);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Get links and pager
try {
    $list_all = $log->getLogs($params);
    $list_counter = $log->getLogs($params, true)->f(0);
    $list_current = new kutrlLinksList($core, $list_all, $list_counter, $pager_base_url);
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}
if (!$show_filters) {
    $header .= dcPage::jsLoad('js/filter-controls.js');
}

echo '
<html>
<head><title>kUtRL, ' . __('Links shortener') . '</title>' . 
"\n<script type=\"text/javascript\"> \n" . 
"$(function(){ $('.checkboxes-helpers').each(function(){dotclear.checkboxesHelpers(this);}); }); \n" . 
"</script>\n" . 
$header . '</head>
<body>
<h2>kUtRL' . 
' &rsaquo; ' . __('Links') . 
' - <a class="button" href="' . $p_url . '&amp;part=link">' . __('New link') . '</a>' . 
'</h2>' . $msg;

if (!$show_filters) {
    echo '<p><a id="filter-control" class="form-control" href="#">' . 
    __('Filters') . '</a></p>';
}

echo '
<form action="' . $p_url . '&amp;part=links" method="get" id="filters-form">
<fieldset><legend>' . __('Filters') . '</legend>
<div class="three-cols">
<div class="col">
<label>' . __('Service:') . form::combo('urlsrv', $lst_services_combo, $urlsrv) . '
</label> 
</div>
<div class="col">
<label>' . __('Order by:') . form::combo('sortby', $sortby_combo, $sortby) . '
</label> 
<label>' . __('Sort:') . form::combo('order', $order_combo, $order) . '
</label>
</div>
<div class="col">
<p>
<label class="classic">' . form::field('nb', 3, 3, $nb_per_page) . ' ' . __('Entries per page') . '
</label> 
<input type="submit" value="' . __('filter') . '" />' . 
form::hidden(['p'], 'kUtRL') . 
form::hidden(['part'], 'links') . '
</p>
</div>
</div>
<br class="clear" />
</fieldset>
</form>
<form action="' . $p_url . '&amp;part=links" method="post" id="form-actions">';

$list_current->display($page, $nb_per_page, $pager_base_url);

echo '
<div class="two-cols">
<p class="col checkboxes-helpers"></p>
<p class="col right">
<input type="submit" value="' . __('Delete selected short links') . '" />' . 
form::hidden(['action'], 'deletelinks') . 
form::hidden(['urlsrv'], $urlsrv) . 
form::hidden(['sortby'], $sortby) . 
form::hidden(['order'], $order) . 
form::hidden(['page'], $page) . 
form::hidden(['nb'], $nb_per_page) . 
form::hidden(['p'], 'kUtRL') . 
form::hidden(['part'], 'links') . 
$core->formNonce() . '
</p>
</div>
</form>';

dcPage::helpBlock('kUtRL');
echo $footer . '</body></html>';
?>