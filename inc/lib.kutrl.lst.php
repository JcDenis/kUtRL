<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of kUtRL, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2021 Jean-Christian Denis and contributors
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

class kutrlLinkslist
{
    protected $core;
    protected $rs;
    protected $rs_count;
    protected $html_prev;
    protected $html_next;

    public function __construct(dcCore $core, $rs, $rs_count)
    {
        $this->core      = &$core;
        $this->rs        = &$rs;
        $this->rs_count  = $rs_count;
        $this->html_prev = __('&#171; prev.');
        $this->html_next = __('next &#187;');
    }

    public function display($page, $nb_per_page, $enclose_block, $filter = false)
    {
        if ($this->rs->isEmpty()) {
            if ($filter) {
                echo '<p><strong>' . __('No short link matches the filter') . '</strong></p>';
            } else {
                echo '<p><strong>' . __('No short link') . '</strong></p>';
            }
        } else {
            $pager   = new dcPager($page, $this->rs_count, $nb_per_page, 10);
            $entries = [];
            if (isset($_REQUEST['entries'])) {
                foreach ($_REQUEST['entries'] as $v) {
                    $entries[(integer) $v] = true;
                }
            }

            $blocks = explode('%s', sprintf($enclose_block, 
                '<div class="table-outer"><table>' .
                ($filter ?
                    '<caption>' . sprintf(__('List of %s links matching the filter.'), $this->rs_count) . '</caption>' :
                    '<caption>' . sprintf(__('List of links (%s)'), $this->rs_count) . '</caption>'
                ) .
                '<tr>' . 
                '<th colspan="2" class="first">' . __('Hash') . '</th>' .
                '<th scope="col">' . __('Link') . '</th>' .
                '<th scope="col">' . __('Date') . '</th>' .
                '<th scope="col">' . __('Service') . '</th>' . 
                '</tr>%s</table>%s</div>'
            ));

            echo $pager->getLinks().$blocks[0];

            while ($this->rs->fetch()) {
                $type = $this->rs->kut_type;
                $hash = $this->rs->kut_hash;

                if (null !== ($o = kutrl::quickService($type))) {
                    $type = '<a href="' . $o->home . '" title="' . $o->name . '">' . $o->name . '</a>';
                    $hash = '<a href="' . $o->url_base . $hash . '" title="' . $o->url_base . $hash . '">' . $hash . '</a>';
                }

                echo
                '<tr class="line">' . 
                '<td class="nowrap">' .
                    form::checkbox(['entries[]'], $this->rs->kut_id, ['checked'  => isset($entries[$this->rs->kut_id])]) .
                '</td>' .
                '<td class="nowrap">' .
                    $hash .
                '</td>' .
                '<td class="maximal" scope="row">' .
                '<a href="' . $this->rs->kut_url . '">' . $this->rs->kut_url . '</a>' .
                '</td>' .
                '<td class="nowrap count">' .
                    dt::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->kut_dt, $this->core->auth->getInfo('user_tz')) .
                '</td>' .
                '<td class="nowrap">' .
                    $type .
                '</td>' .
                '</tr>';
            }

            echo $blocks[1].$blocks[2].$pager->getLinks();
        }
    }
}