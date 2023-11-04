<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL\Service;

use Dotclear\App;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Input,
    Label,
    Note,
    Para,
    Text,
    Textarea
};
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\kUtRL\Service;
use Exception;

/**
 * @brief       kUtRL local service class.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ServiceLocal extends Service
{
    protected function init(): void
    {
        $protocols = (string) $this->settings->get('srv_local_protocols');

        $this->config = [
            'id'   => 'local',
            'name' => 'kUtRL',
            'home' => 'https://github.com/JcDenis/kUtRL',

            'allow_custom_hash' => true,
            'allow_protocols'   => empty($protocols) ? [] : explode(',', $protocols),

            'url_base'    => App::blog()->url() . App::url()->getBase('kutrl') . '/',
            'url_min_len' => strlen(App::blog()->url() . App::url()->getBase('kutrl') . '/') + 2,
        ];
    }

    public function saveSettings(): void
    {
        $this->settings->put('srv_local_protocols', $_POST['kutrl_srv_local_protocols'], 'string');
        $this->settings->put('srv_local_public', isset($_POST['kutrl_srv_local_public']), 'boolean');
        $this->settings->put('srv_local_css', $_POST['kutrl_srv_local_css'], 'string');
        $this->settings->put('srv_local_404_active', isset($_POST['kutrl_srv_local_404_active']), 'boolean');
    }

    public function settingsForm(): Div
    {
        return (new Div())
            ->items([
                (new Div())
                    ->class('two-cols')
                    ->items([
                        (new Div())
                            ->class('col')
                            ->items([
                                (new Text('b', __('Settings:'))),
                                (new Para())
                                    ->items([
                                        (new Label(__('Allowed protocols:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for('kutrl_srv_local_protocols'),
                                        (new Input('kutrl_srv_local_protocols'))
                                            ->size(50)
                                            ->maxlength(255)
                                            ->value((string) $this->settings->get('srv_local_protocols')),
                                    ]),
                                (new Note())
                                    ->class('form-note')
                                    ->text(__('Use comma seperated list like: "http:,https:,ftp:"')),
                                (new Para())
                                    ->items([
                                        (new Checkbox('kutrl_srv_local_public', (bool) $this->settings->get('srv_local_public')))
                                            ->value(1),
                                        (new Label(__('Enable public page for visitors to shorten links'), Label::OUTSIDE_LABEL_AFTER))
                                            ->class('classic')
                                            ->for('kutrl_srv_local_public'),
                                    ]),
                                (new Para('style-area'))
                                    ->class('area')
                                    ->items([
                                        (new Label(__('CSS:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for('kutrl_srv_local_css'),
                                        (new Textarea('kutrl_srv_local_css', Html::escapeHTML($this->settings->get('srv_local_css'))))
                                            ->cols(50)
                                            ->rows(3),
                                    ]),
                                (new Note())
                                    ->class('form-note')
                                    ->text(__('You can add here special cascading style sheet. Body of page has class "dc-kutrl" and widgets have class "shortenkutrlwidget" and "rankkutrlwidget".')),
                                (new Para())
                                    ->items([
                                        (new Checkbox('kutrl_srv_local_404_active', (bool) $this->settings->get('srv_local_404_active')))
                                            ->value(1),
                                        (new Label(__('Enable special 404 error public page for unknow urls'), Label::OUTSIDE_LABEL_AFTER))
                                            ->class('classic')
                                            ->for('kutrl_srv_local_404_active'),
                                    ]),
                                (new Note())
                                    ->class('form-note')
                                    ->text(__('If this is not activated, the default 404 page of the theme will be display.')),
                            ]),

                        (new Div())
                            ->class('col')
                            ->items([
                                (new Text('b', __('Note:'))),
                                (new Text(
                                    'p',
                                    __('This service use your own Blog to shorten and serve URL.') . '<br />' .
                                    sprintf(__('This means that with this service short links start with "%s".'), $this->get('url_base'))
                                )),
                                (new Text(
                                    'p',
                                    __("You can use Dotclear's plugin called myUrlHandlers to change short links prefix on your blog.") .
                                    (
                                        preg_match('/index\.php/', $this->get('url_base')) ?
                                        ' ' .
                                        __("We recommand that you use a rewrite engine in order to remove 'index.php' from your blog's URL.") .
                                        '<br /><a href="http://fr.dotclear.org/documentation/2.0/usage/blog-parameters">' .
                                        __("You can find more about this on the Dotclear's documentation.") .
                                        '</a>'
                                        : ''
                                    )
                                )),
                                (new Text(
                                    'p',
                                    __('There are two templates delivered with kUtRL, if you do not use default theme, you may adapt them to yours.') . '<br />' .
                                    __('Files are in plugin directory /default-templates, just copy them into your theme and edit them.')
                                )),
                            ]),
                    ]),
                (new Text('br', ''))->class('clear'),
            ]);
    }

    public function testService(): bool
    {
        $ap = $this->get('allow_protocols');
        if (!empty($ap)) {
            return true;
        }
        $this->error->add(__('Service is not well configured.'));

        return false;
    }

    public function createHash(string $url, ?string $hash = null)
    {
        # Create response object
        $rs_hash = '';
        $rs_type = 'local';
        $rs_url  = $url;

        # Normal link
        if ($hash === null) {
            $type    = 'localnormal';
            $rs_hash = $this->next($this->last('localnormal'));

            # Mixed custom link
        } elseif (preg_match('/^([A-Za-z0-9]{2,})\!\!$/', $hash, $m)) {
            $type    = 'localmix';
            $rs_hash = $m[1] . $this->next('-1', $m[1]);

            # Custom link
        } elseif (preg_match('/^[A-Za-z0-9\.\-\_]{2,}$/', $hash)) {
            if (false !== $this->log->select(null, $hash, null, 'local')) {
                $this->error->add(__('Custom short link is already taken.'));

                return false;
            }
            $type    = 'localcustom';
            $rs_hash = $hash;

            # Wrong char in custom hash
        } else {
            $this->error->add(__('Custom short link is not valid.'));

            return false;
        }

        # Save link
        try {
            $this->log->insert($rs_url, $rs_hash, $type, $rs_type);

            return $this->fromValue(
                $rs_hash,
                $rs_url,
                $rs_type
            );
        } catch (Exception) {
            $this->error->add(__('Failed to save link.'));
        }

        return false;
    }

    protected function last(string $type): string
    {
        return false === ($rs = $this->log->select(null, null, $type, 'local')) ?
            '-1' : $rs->hash;
    }

    protected function next(string $last_id, string $prefix = ''): string
    {
        if ($last_id == '-1') {
            $next_id = '0';
        } else {
            for ($x = 1; $x <= strlen($last_id); $x++) {
                $pos = strlen($last_id) - $x;

                if ($last_id[$pos] != 'z') {
                    $next_id = $this->increment($last_id, $pos);

                    break;
                }
            }
            if (!isset($next_id)) {
                $next_id = $this->append($last_id);
            }
        }

        return false === $this->log->select(null, $prefix . $next_id, null, 'local') ?
            $next_id : $this->next($next_id, $prefix);
    }

    protected function append(string $id): string
    {
        $id = str_split($id);
        for ($x = 0; $x < count($id); $x++) {
            $id[$x] = 0;
        }

        return implode($id) . '0';
    }

    protected function increment(string $id, int $pos): string
    {
        $id   = str_split($id);
        $char = $id[$pos];

        if (is_numeric($char)) {
            $new_char = $char < 9 ? $char + 1 : 'a';
        } else {
            $new_char = chr(ord($char) + 1);
        }
        $id[$pos] = $new_char;

        if ($pos != (count($id) - 1)) {
            for ($x = ($pos + 1); $x < count($id); $x++) {
                $id[$x] = 0;
            }
        }

        return implode($id);
    }

    public function getUrl(string $hash): bool|string
    {
        if (false === ($rs = $this->log->select(null, $hash, null, 'local'))) {
            return false;
        }
        if (!$rs->url) { //previously removed url
            return false;
        }
        $this->log->counter((int) $rs->id, 'up');

        return $rs->url;
    }

    public function deleteUrl(string $url, bool $delete = false): bool
    {
        if (false === ($rs = $this->log->select($url, null, null, 'local'))) {
            return false;
        }
        if ($delete) {
            $this->log->delete((int) $rs->id);
        } else {
            $this->log->clear((int) $rs->id);
        }

        return true;
    }
}
