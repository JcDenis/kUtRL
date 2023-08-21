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
use Dotclear\Core\Backend\{
    Notices,
    Page
};
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\{
    Div,
    Form,
    Input,
    Label,
    Note,
    Para,
    Submit,
    Text,
};
use Dotclear\Helper\Html\Html;

class Manage extends Process
{
    public static function init(): bool
    {
        return self::status(($_REQUEST['part'] ?? 'links') === 'links' ? ManageLinks::init() : My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (($_REQUEST['part'] ?? 'links') === 'links') {
            return ManageLinks::process();
        }

        $kut = Utils::quickPlace('admin');

        if (empty($_POST['save'])) {
            return true;
        }

        try {
            if (null === $kut) {
                throw new Exception('Unknow service');
            }
            $url  = trim(dcCore::app()->con->escapeStr((string) $_POST['str']));
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
                $url     = $rs->url;
                $new_url = $kut->url_base . $rs->hash;

                Notices::addSuccessNotice(sprintf(
                    __('Short link for %s is %s'),
                    '<strong>' . Html::escapeHTML($url) . '</strong>',
                    '<a href="' . $new_url . '">' . $new_url . '</a>'
                ));
            } else {
                if (false === ($rs = $kut->hash($url, $hash))) {
                    if ($kut->error->flag()) {
                        throw new Exception($kut->error->toHTML());
                    }

                    throw new Exception(__('Failed to create short link. This could be caused by a service failure.'));
                } else {
                    $url     = $rs->url;
                    $new_url = $kut->url_base . $rs->hash;

                    Notices::addSuccessNotice(sprintf(
                        __('Short link for %s is %s'),
                        '<strong>' . Html::escapeHTML($url) . '</strong>',
                        '<a href="' . $new_url . '">' . $new_url . '</a>'
                    ));

                    # ex: Send new url to messengers
                    if (!empty($rs)) {
                        dcCore::app()->callBehavior('adminAfterKutrlCreate', $rs, __('New short URL'));
                    }
                }
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        if (($_REQUEST['part'] ?? 'links') === 'links') {
            ManageLinks::render();

            return;
        }

        $kut = Utils::quickPlace('admin');

        Page::openModule(My::id());

        echo
        Page::breadcrumb([
            __('Plugins')  => '',
            My::name()     => My::manageUrl(),
            __('New link') => '',
        ]) .
        Notices::getNotices();

        if (!isset($kut) || null === $kut) {
            echo (new Para())
                ->text(__('You must set an admin service.'))
                ->render();
        } else {
            $fields = [];

            if ($kut->allow_custom_hash) {
                $fields[] = (new Para())
                    ->items([
                        (new Label(__('Custom short link:'), Label::OUTSIDE_LABEL_BEFORE))
                            ->for('custom'),
                        (new Input('custom'))
                            ->size(50)
                            ->maxlenght(32)
                            ->value(''),
                    ]);
                $fields[] = (new Note())
                    ->class('form-note')
                    ->text(__('Only if you want a custom short link.'));

                if ($kut->admin_service == 'local') {
                    $fields[] = (new Note())
                        ->class('form-note')
                        ->text(__('You can use "bob!!" if you want a semi-custom link, it starts with "bob" and "!!" will be replaced by an increment value.'));
                }
            }

            echo (new Div())
                ->items([
                    (new Text('h4', sprintf(__('Shorten link using service "%s"'), $kut->name))),
                    (new Form('create-link'))
                        ->method('post')
                        ->action(My::manageUrl())
                        ->fields([
                            (new Para())
                                ->items([
                                    (new Label(__('Long link:'), Label::OUTSIDE_LABEL_BEFORE))
                                        ->for('str'),
                                    (new Input('str'))
                                        ->size(100)
                                        ->maxlenght(255)
                                        ->value(''),
                                ]),
                            ... $fields,
                            (new Submit('save'))
                                ->value(__('Save')),
                            ... My::hiddenFields([
                                'part' => 'link',
                            ]),
                        ]),
                ])
                ->render();
        }

        Page::helpBlock(My::id());

        Page::closeModule();
    }
}
