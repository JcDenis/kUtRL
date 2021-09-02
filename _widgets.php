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

if (!defined('DC_RC_PATH')) {
    return null;
}

$core->addBehavior('initWidgets', ['widgetKutrl', 'adminShorten']);
$core->addBehavior('initWidgets', ['widgetKutrl', 'adminRank']);

class widgetKutrl
{
    public static function adminShorten($w)
    {
        $w
            ->create(
                'shortenkutrl',
                __('Links shortener'),
                ['widgetKutrl', 'publicShorten']
            )
            ->addTitle(__('Shorten link'))
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }

    public static function adminRank($w)
    {
        $w
            ->create(
                'rankkutrl',
                __('Top of short links'),
                ['widgetKutrl', 'publicRank']
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
                'combo',[
                    __('All') => '-',
                    __('Mini URL') => 'localnormal',
                    __('Custom URL') => 'localcustom',
                    __('Semi-custom') => 'localmix'
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
                    __('Hash') => 'kut_hash'
                ]
            )
            ->setting(
                'sort',
                __('Sort:'),
                'desc',
                'combo',
                [
                    __('Ascending') => 'asc',
                    __('Descending') => 'desc'
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

    public static function publicShorten($w)
    {
        global $core;
        $s = $core->blog->settings->kUtRL;

        if (!$s->kutrl_active 
         || !$s->kutrl_srv_local_public 
         || ($w->homeonly == 1 && !$core->url->isHome($core->url->type)) || ($w->homeonly == 2 && $core->url->isHome($core->url->type))
         || $core->url->type == 'kutrl') {
            return null;
        }

        $hmf = hmfKutrl::create();
        $hmfp = hmfKutrl::protect($hmf);

        return $w->renderDiv(
            $w->content_only,
            'shortenkutrlwidget ' . $w->class,
            '',
            ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
                '<form name="shortenkutrlwidget" method="post" action="' .
                 $core->blog->url . $core->url->getBase('kutrl') . '">' .
                '<p><label>' .
                 __('Long link:') . '<br />' .
                 form::field('longurl', 20, 255, '') .
                '</label></p>' .
                '<p><label>' .
                 sprintf(__('Rewrite "%s" in next field to show that you are not a robot:'), $hmf) . '<br />' .
                 form::field('hmf',20,255,'') .
                '</label></p>' .
                '<p><input class="submit" type="submit" name="submiturl" value="' . __('Shorten') . '" />' .
                form::hidden('hmfp',$hmfp) .
                $core->formNonce() .
                '</p>' .
                '</form>'
        );
    }

    public static function publicRank($w)
    {
        global $core;
        $s = $core->blog->settings->kUtRL;

        if (!$s->kutrl_active 
         || ($w->homeonly == 1 && !$core->url->isHome($core->url->type)) || ($w->homeonly == 2 && $core->url->isHome($core->url->type))) {
            return null;
        }

        $type = in_array($w->type, ['localnormal', 'localmix', 'localcustom']) ?
            "AND kut_type ='" . $w->type . "' " :
            "AND kut_type " . $core->con->in(['localnormal', 'localmix', 'localcustom']) . " ";

        $hide = (boolean) $w->hideempty ? 'AND kut_counter > 0 ' : '';

        $more = '';
        if ($w->type == 'localmix' && '' != $w->mixprefix) {
            $more = "AND kut_hash LIKE '" . $core->con->escape($w->mixprefix) . "%' ";
        }

        $order = ($w->sortby && in_array($w->sortby, ['kut_dt', 'kut_counter', 'kut_hash'])) ? 
            $w->sortby : 'kut_dt';

        $order .= $w->sort == 'desc' ? ' DESC' : ' ASC';

        $limit = $core->con->limit(abs((integer) $w->limit));

        $rs = $core->con->select(
            'SELECT kut_counter, kut_hash ' .
            "FROM " . $core->prefix . "kutrl " .
            "WHERE blog_id='" . $core->con->escape($core->blog->id) . "' " .
            "AND kut_service = 'local' " .
            $type . $hide . $more . 'ORDER BY ' . $order . $limit
        );

        if ($rs->isEmpty()) {
            return null;
        }

        $content = '';
        $i = 0;
        while($rs->fetch()) {
            $i++;
            $rank = '<span class="rankkutrl-rank">' . $i . '</span>';

            $hash = $rs->kut_hash;
            $url = $core->blog->url . $core->url->getBase('kutrl') . '/' . $hash;
            $cut_len = - abs((integer) $w->urllen);

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

            $content .= 
                '<li><a href="' .
                $core->blog->url . $core->url->getBase('kutrl') . '/' . $rs->kut_hash .
                '">' .
                str_replace(
                    ['%rank%', '%hash%', '%url%', '%count%', '%counttext%'],
                    [$rank, $hash, $url, $rs->kut_counter, $counttext],
                    $w->text
                ) .
                '</a></li>';
        }

        if (empty($content)) {
            return null;
        }

        return $w->renderDiv(
            $w->content_only,
            'lastblogupdate ' . $w->class,
            '',
            ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
                sprintf('<ul>%s</ul>', $content)
        );        

    }
}