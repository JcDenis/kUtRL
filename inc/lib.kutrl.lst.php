<?php

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