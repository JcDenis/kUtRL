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

    public function userColumns($type, $cols)
    {
        $cols_user = @$this->core->auth->user_prefs->interface->cols;
        if (is_array($cols_user) || $cols_user instanceof ArrayObject) {
            if (isset($cols_user[$type])) {
                foreach ($cols_user[$type] as $cn => $cd) {
                    if (!$cd && isset($cols[$cn])) {
                        unset($cols[$cn]);
                    }
                }
            }
        }
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
            $links = [];
            if (isset($_REQUEST['entries'])) {
                foreach ($_REQUEST['entries'] as $v) {
                    $links[(integer) $v] = true;
                }
            }

            $cols = [
                    'kut_url'     => '<th colspan="2" class="first">' . __('Link') . '</th>',
                    'kut_hash'    => '<th scope="col">' . __('Hash') . '</th>',
                    'kut_dt'      => '<th scope="col">' . __('Date') . '</th>',
                    'kut_service' => '<th scope="col">' . __('Service') . '</th>'
            ];
            $cols = new ArrayObject($cols);
            $this->userColumns('kUtRL', $cols);

            $html_block =
            '<div class="table-outer">' .
            '<table>' .
            '<caption>' . ($filter ? 
                sprintf(__('List of %s links matching the filter.'), $this->rs_count) :
                sprintf(__('List of links (%s)'), $this->rs_count)
            ). '</caption>' .
            '<thead>' .
            '<tr>' . implode(iterator_to_array($cols)) . '</tr>' .
            '</thead>' .
            '<tbody>%s</tbody>' .
            '</table>' .
            '%s</div>';

            if ($enclose_block) {
                $html_block = sprintf($enclose_block, $html_block);
            }
            $blocks = explode('%s', $html_block);

            echo $pager->getLinks() . $blocks[0];

            while ($this->rs->fetch()) {
                echo $this->linkLine(isset($links[$this->rs->kut_id]));
            }

            echo $blocks[1] . $blocks[2] . $pager->getLinks();
        }
    }

    private function linkLine($checked)
    {
        $type = $this->rs->kut_type;
        $hash = $this->rs->kut_hash;

        if (null !== ($o = kutrl::quickService($type))) {
            $type = '<a href="' . $o->home . '" title="' . $o->name . '">' . $o->name . '</a>';
            $hash = '<a href="' . $o->url_base . $hash . '" title="' . $o->url_base . $hash . '">' . $hash . '</a>';
        }

        $cols = [
            'check' => '<td class="nowrap">' .
                    form::checkbox(['entries[]'], $this->rs->kut_id, ['checked'  => isset($entries[$this->rs->kut_id])]) .
                '</td>',
            'kut_url' => '<td class="maximal" scope="row">' .
                '<a href="' . $this->rs->kut_url . '">' . $this->rs->kut_url . '</a>' .
                '</td>',
            'kut_hash' => '<td class="nowrap">' .
                    $hash .
                '</td>',
            'kut_dt' => '<td class="nowrap count">' .
                    dt::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->kut_dt, $this->core->auth->getInfo('user_tz')) .
                '</td>',
            'kut_service' => '<td class="nowrap">' . 
                    $type . 
                '</td>'
        ];

        $cols = new ArrayObject($cols);
        $this->userColumns('kUtRL', $cols);

        return '<tr class="line">' .  implode(iterator_to_array($cols)) . '</tr>' . "\n";
    }
}