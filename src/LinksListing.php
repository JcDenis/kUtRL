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

use ArrayObject;
use dcCore;
use Dotclear\Core\Backend\Filter\Filters;
use Dotclear\Core\Backend\Listing\{
    Listing,
    Pager
};
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Link,
    Note,
    Para,
    Text,
};
use Dotclear\Helper\Html\Html;

class LinksListing extends Listing
{
    public function display(Filters $filter, string $enclose_block): void
    {
        if ($this->rs->isEmpty()) {
            echo (new Note())
                ->class('info')
                ->text($filter->show() ? __('No short link matches the filter') : __('No short link'))
                ->render();

            return;
        }

        $links = [];
        if (isset($_REQUEST['entries'])) {
            foreach ($_REQUEST['entries'] as $v) {
                $links[(int) $v] = true;
            }
        }

        $pager = new Pager((int) $filter->value('page'), $this->rs_count, (int) $filter->nb, 10);

        $cols = new ArrayObject([
            'kut_url' => (new Text('th', __('Link')))
                ->class('first')
                ->extra('colspan="2"'),
            'kut_hash' => (new Text('th', __('Hash')))
                ->extra('scope="col"'),
            'kut_dt' => (new Text('th', __('Date')))
                ->extra('scope="col"'),
            'kut_service' => (new Text('th', __('Service')))
                ->extra('scope="col"'),
        ]);

        $this->userColumns(My::id(), $cols);

        $lines = [];
        while ($this->rs->fetch()) {
            $lines[] = $this->linkLine(isset($links[$this->rs->kut_id]));
        }

        echo
        $pager->getLinks() .
        sprintf(
            $enclose_block,
            (new Div())
                ->class('table-outer')
                ->items([
                    (new Para(null, 'table'))
                        ->items([
                            (new Text(
                                'caption',
                                $filter->show() ?
                                sprintf(__('List of %s links matching the filter.'), $this->rs_count) :
                                sprintf(__('List of links. (%s)'), $this->rs_count)
                            )),
                            (new Para(null, 'tr'))
                                ->items(iterator_to_array($cols)),
                            (new Para(null, 'tbody'))
                                ->items($lines),
                        ]),
                ])
                ->render()
        ) .
        $pager->getLinks();
    }

    private function linkLine(bool $checked): Para
    {
        $type = $this->rs->kut_type;
        $hash = $this->rs->kut_hash;

        if (null !== ($o = Utils::quickService($type))) {
            $type = (new Link())
                ->href($o->home)
                ->title($o->name)
                ->text($o->name)
                ->render();
            $hash = (new Link())
                ->href($o->url_base . $hash)
                ->title($o->url_base . $hash)
                ->text($hash)
                ->render();
        }

        $cols = new ArrayObject([
            'check' => (new Para(null, 'td'))
                ->class('nowrap minimal')
                ->items([
                    (new Checkbox(['entries[]'], $checked))
                        ->value($this->rs->kut_id),
                ]),
            'kut_url' => (new Para(null, 'td'))
                ->class('maximal')
                ->items([
                    (new Link())
                        ->href($o->home)
                        ->title($this->rs->kut_url)
                        ->text($this->rs->kut_url),
                ]),
            'kut_hash' => (new Text('td', $hash))
                ->class('nowrap'),
            'kut_dt' => (new Text('td', Html::escapeHTML(Date::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->kut_dt, dcCore::app()->auth->getInfo('user_tz')))))
                ->class('nowrap'),
            'kut_service' => (new Text('td', $type))
                ->class('nowrap'),
        ]);

        $this->userColumns(My::id(), $cols);

        return
        (new Para('p' . $this->rs->kut_id, 'tr'))
            ->class('line')
            ->items(iterator_to_array($cols));
    }
}
