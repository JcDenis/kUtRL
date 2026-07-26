<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Filter\Filters;
use Dotclear\Core\Backend\Listing\Listing;
use Dotclear\Core\Backend\Listing\Pager;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Component;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;

/**
 * @brief       kUtRL links listing.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
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
        if (isset($_REQUEST['entries']) && is_array($_REQUEST['entries'])) {
            foreach ($_REQUEST['entries'] as $v) {
                if (is_numeric($v)) {
                    $links[(int) $v] = true;
                }
            }
        }

        $pager = new Pager(
            is_numeric($filter->value('page')) ? (int) $filter->value('page') : 0,
            (int) $this->rs_count,
            is_numeric($filter->value('nb')) ? (int) $filter->value('nb') : 10,
            10
        );

        /**
         * @var ArrayObject<string, Component>
         */
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
            $lines[] = $this->linkLine(isset($links[$this->rs->intField('kut_id')]));
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
        $type = $this->rs->strField('kut_type');
        $hash = $this->rs->strField('kut_hash');
        $tz   = is_string(App::auth()->getInfo('user_tz')) ? App::auth()->getInfo('user_tz') : '';

        if (null !== ($o = Utils::quickService($type))) {
            $type = (new Link())
                ->href($o->srvHome())
                ->title($o->srvName())
                ->text($o->srvName())
                ->render();
            $hash = (new Link())
                ->href($o->srvUrlBase() . $hash)
                ->title($o->srvUrlBase() . $hash)
                ->text($hash)
                ->render();
        }

        /**
         * @var ArrayObject<string, Component>
         */
        $cols = new ArrayObject([
            'check' => (new Para(null, 'td'))
                ->class('nowrap minimal')
                ->items([
                    (new Checkbox(['entries[]'], $checked))
                        ->value($this->rs->strField('kut_id')),
                ]),
            'kut_url' => (new Para(null, 'td'))
                ->class('maximal')
                ->items([
                    (new Link())
                        ->href($this->rs->strField('kut_url'))
                        ->title($this->rs->strField('kut_url'))
                        ->text($this->rs->strField('kut_url')),
                ]),
            'kut_hash' => (new Text('td', $hash))
                ->class('nowrap'),
            'kut_dt' => (new Text('td', Html::escapeHTML(Date::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->strField('kut_dt'), $tz))))
                ->class('nowrap'),
            'kut_service' => (new Text('td', $type))
                ->class('nowrap'),
        ]);

        $this->userColumns(My::id(), $cols);

        return
        (new Para('p' . $this->rs->strField('kut_id'), 'tr'))
            ->class('line')
            ->items(iterator_to_array($cols));
    }
}
