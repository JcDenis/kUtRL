<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief       kUtRL manage class.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Manage
{
    use TraitProcess;

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
            $url  = trim(App::db()->con()->escapeStr((string) $_POST['str']));
            $hash = empty($_POST['custom']) ? null : $_POST['custom'];

            if (empty($url)) {
                throw new Exception(__('There is nothing to shorten.'));
            }
            if (!$kut->testService()) {
                throw new Exception(__('Service is not well configured.'));
            }
            if (null !== $hash && !$kut->get('allow_custom_hash')) {
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
            if (!$kut->get('allow_external_url') && !$kut->isBlogUrl($url)) {
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
                $new_url = $kut->get('url_base') . $rs->hash;

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
                    $new_url = $kut->get('url_base') . $rs->hash;

                    Notices::addSuccessNotice(sprintf(
                        __('Short link for %s is %s'),
                        '<strong>' . Html::escapeHTML($url) . '</strong>',
                        '<a href="' . $new_url . '">' . $new_url . '</a>'
                    ));

                    # ex: Send new url to messengers
                    if (!$rs->isEmpty()) {
                        App::behavior()->callBehavior('adminAfterKutrlCreate', $rs, __('New short URL'));
                    }
                }
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
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

        if (null === $kut) {
            echo (new Text('p', __('You must set an admin service.')))
                ->render();
        } else {
            $fields = [];

            if ($kut->get('allow_custom_hash')) {
                $fields[] = (new Para())
                    ->items([
                        (new Label(__('Custom short link:'), Label::OUTSIDE_LABEL_BEFORE))
                            ->for('custom'),
                        (new Input('custom'))
                            ->size(50)
                            ->maxlength(32)
                            ->value(''),
                    ]);
                $fields[] = (new Note())
                    ->class('form-note')
                    ->text(__('Only if you want a custom short link.'));

                if ($kut->get('admin_service') == 'local') {
                    $fields[] = (new Note())
                        ->class('form-note')
                        ->text(__('You can use "bob!!" if you want a semi-custom link, it starts with "bob" and "!!" will be replaced by an increment value.'));
                }
            }

            echo (new Div())
                ->items([
                    (new Text('h4', sprintf(__('Shorten link using service "%s"'), $kut->get('name')))),
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
                                        ->maxlength(255)
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
