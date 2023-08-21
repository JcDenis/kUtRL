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

use dcCore;
use Dotclear\Helper\Html\Form\{
    Form,
    Hidden,
    Input,
    Label,
    Para,
    Submit
};
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsStack;
use Dotclear\Plugin\widgets\WidgetsElement;

class Widgets
{
    public static function initShorten(WidgetsStack $w): void
    {
        $w
            ->create(
                'shortenkutrl',
                My::name(),
                [self::class, 'parseShorten']
            )
            ->addTitle(__('Shorten link'))
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }

    public static function initRank(WidgetsStack $w): void
    {
        $w
            ->create(
                'rankkutrl',
                __('Top of short links'),
                [self::class, 'parseRank']
            )
            ->addTitle(__('Top of short links'))
            ->setting(
                'text',
                __('Text: (Use wildcard %rank%, %hash%, %url%, %count%, %counttext%)'),
                '%rank% - %url% - %counttext%',
                'text'
            )
            ->setting(
                'urllen',
                __('URL length (if truncate)'),
                20,
                'text'
            )
            ->setting(
                'type',
                __('Type:'),
                'all',
                'combo',
                [
                    __('All')         => '-',
                    __('Mini URL')    => 'localnormal',
                    __('Custom URL')  => 'localcustom',
                    __('Semi-custom') => 'localmix',
                ]
            )
            ->setting(
                'mixprefix',
                __('Semi-custom prefix: (only if you want limit to a particular prefix)'),
                '',
                'text'
            )
            ->setting(
                'sortby',
                __('Sort by:'),
                'kut_counter',
                'combo',
                [
                    __('Date') => 'kut_dt',
                    __('Rank') => 'kut_counter',
                    __('Hash') => 'kut_hash',
                ]
            )
            ->setting(
                'sort',
                __('Sort:'),
                'desc',
                'combo',
                [
                    __('Ascending')  => 'asc',
                    __('Descending') => 'desc',
                ]
            )
            ->setting(
                'limit',
                __('Limit:'),
                '10',
                'text'
            )
            ->setting(
                'hideempty',
                __('Hide no followed links'),
                0,
                'check'
            )
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }

    public static function parseShorten(WidgetsElement $w): string
    {
        $s = My::settings();

        if (!$s->get('active')
         || !$s->get('srv_local_public')
         || !$w->checkHomeOnly(dcCore::app()->url->type)
         || dcCore::app()->url->type == 'kutrl') {
            return '';
        }

        $hmf  = FrontendUtils::create();
        $hmfp = FrontendUtils::protect($hmf);

        return $w->renderDiv(
            (bool) $w->content_only,
            'shortenkutrlwidget ' . $w->class,
            '',
            ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) : '') .
            (new Form(['shortenkutrlwidget']))
                ->method('post')
                ->action(dcCore::app()->blog->url . dcCore::app()->url->getBase('kutrl'))
                ->fields([
                    (new Para())
                        ->items([
                            (new Label(__('Long link:'), Label::OUTSIDE_LABEL_BEFORE))
                                ->for('longurl'),
                            (new Input('longurl'))
                                ->size(20)
                                ->maxlenght(255)
                                ->value(''),
                        ]),
                    (new Para())
                        ->items([
                            (new Label(sprintf(__('Rewrite "%s" in next field to show that you are not a robot:'), $hmf), Label::OUTSIDE_LABEL_BEFORE))
                                ->for('hmf'),
                            (new Input('hmf'))
                                ->size(20)
                                ->maxlenght(255)
                                ->value(''),
                        ]),
                    (new Para())
                        ->items([
                            (new Submit('submiturl'))
                                ->value(__('Shorten')),
                            (new Hidden('hmfp', $hmfp)),
                            dcCore::app()->formNonce(false),
                        ]),
                ])
                ->render()
        );
    }

    public static function parseRank(WidgetsElement $w): string
    {
        $s = My::settings();

        if (!$s->get('active') || !$w->checkHomeOnly(dcCore::app()->url->type)) {
            return '';
        }

        $type = in_array($w->type, ['localnormal', 'localmix', 'localcustom']) ?
            "AND kut_type ='" . $w->type . "' " :
            'AND kut_type ' . dcCore::app()->con->in(['localnormal', 'localmix', 'localcustom']) . ' ';

        $hide = (bool) $w->hideempty ? 'AND kut_counter > 0 ' : '';

        $more = '';
        if ($w->type == 'localmix' && '' != $w->mixprefix) {
            $more = "AND kut_hash LIKE '" . dcCore::app()->con->escapeStr((string) $w->mixprefix) . "%' ";
        }

        $order = ($w->sortby && in_array($w->sortby, ['kut_dt', 'kut_counter', 'kut_hash'])) ?
            $w->sortby : 'kut_dt';

        $order .= $w->sort == 'desc' ? ' DESC' : ' ASC';

        $limit = dcCore::app()->con->limit(abs((int) $w->limit));

        $rs = dcCore::app()->con->select(
            'SELECT kut_counter, kut_hash ' .
            'FROM ' . dcCore::app()->prefix . My::TABLE_NAME . ' ' .
            "WHERE blog_id='" . dcCore::app()->con->escapeStr((string) dcCore::app()->blog->id) . "' " .
            "AND kut_service = 'local' " .
            $type . $hide . $more . 'ORDER BY ' . $order . $limit
        );

        if ($rs->isEmpty()) {
            return '';
        }

        $content = '';
        $i       = 0;
        while ($rs->fetch()) {
            $i++;
            $rank = '<span class="rankkutrl-rank">' . $i . '</span>';

            $hash    = $rs->kut_hash;
            $url     = dcCore::app()->blog->url . dcCore::app()->url->getBase('kutrl') . '/' . $hash;
            $cut_len = - abs((int) $w->urllen);

            if (strlen($url) > $cut_len) {
                $url = '...' . substr($url, $cut_len);
            }
            /*
                        if (strlen($hash) > $cut_len) {
                            $url = '...'.substr($hash, $cut_len);
                        }
            //*/
            if ($rs->kut_counter == 0) {
                $counttext = __('never followed');
            } elseif ($rs->kut_counter == 1) {
                $counttext = __('followed one time');
            } else {
                $counttext = sprintf(__('followed %s times'), $rs->kut_counter);
            }

            $content .= '<li><a href="' .
                dcCore::app()->blog->url . dcCore::app()->url->getBase('kutrl') . '/' . $rs->kut_hash .
                '">' .
                str_replace(
                    ['%rank%', '%hash%', '%url%', '%count%', '%counttext%'],
                    [$rank, $hash, $url, $rs->kut_counter, $counttext],
                    $w->text
                ) .
                '</a></li>';
        }

        if (empty($content)) {
            return '';
        }

        return $w->renderDiv(
            (bool) $w->content_only,
            'lastblogupdate ' . $w->class,
            '',
            ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) : '') .
                sprintf('<ul>%s</ul>', $content)
        );
    }
}
