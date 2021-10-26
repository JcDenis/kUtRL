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

class localKutrlService extends kutrlService
{
    protected $config = [
        'id'                => 'local',
        'name'              => 'kUtRL',
        'home'              => 'https://github.com/JcDenis/kUtRL',

        'allow_custom_hash' => true
    ];

    protected function init()
    {
        $protocols = (string) $this->settings->kutrl_srv_local_protocols;
        $this->config['allow_protocols'] = empty($protocols) ? [] : explode(',', $protocols);

        $this->config['url_base'] = $this->core->blog->url . $this->core->url->getBase('kutrl') . '/';
        $this->config['url_min_len'] = strlen($this->url_base) + 2;

    }

    public function saveSettings()
    {
        $this->settings->put('kutrl_srv_local_protocols', $_POST['kutrl_srv_local_protocols'], 'string');
        $this->settings->put('kutrl_srv_local_public', isset($_POST['kutrl_srv_local_public']), 'boolean');
        $this->settings->put('kutrl_srv_local_css', $_POST['kutrl_srv_local_css'], 'string');
        $this->settings->put('kutrl_srv_local_404_active', isset($_POST['kutrl_srv_local_404_active']), 'boolean');
    }

    public function settingsForm()
    {
        echo
        '<div class="two-cols"><div class="col">'  . 

        '<p><strong>' . __('Settings:') . '</strong></p>'  . 
        '<p><label class="classic">' . 
        __('Allowed protocols:') . '<br />' . 
        form::field(['kutrl_srv_local_protocols'], 50, 255, $this->settings->kutrl_srv_local_protocols) . 
        '</label></p>' . 

        '<p class="form-note">' . 
        __('Use comma seperated list like: "http:,https:,ftp:"') . 
        '</p>' . 

        '<p><label class="classic">' . 
        form::checkbox(['kutrl_srv_local_public'], '1', $this->settings->kutrl_srv_local_public) . ' ' . 
        __('Enable public page for visitors to shorten links') . 
        '</label></p>' . 

        '<p class="area" id="style-area"><label for="_style">' . __('CSS:') . '</label>' . 
        form::textarea('kutrl_srv_local_css',50,3,html::escapeHTML($this->settings->kutrl_srv_local_css), '', 2) . 
        '</p>' . 
        '<p class="form-note">' . __('You can add here special cascading style sheet. Body of page has class "dc-kutrl" and widgets have class "shortenkutrlwidget" and "rankkutrlwidget".') . '</p>' . 

        '<p><label class="classic">' . 
        form::checkbox(['kutrl_srv_local_404_active'], '1', $this->settings->kutrl_srv_local_404_active) . ' ' . 
        __('Enable special 404 error public page for unknow urls') . 
        '</label></p>' . 
        '<p class="form-note">' . __('If this is not activated, the default 404 page of the theme will be display.') . '</p>' . 

        '</div><div class="col">' . 

        '<p><strong>' . __('Note:') . '</strong></p>' . 
        '<p>' . 
        __('This service use your own Blog to shorten and serve URL.') . '<br />' . 
        sprintf(__('This means that with this service short links start with "%s".'), $this->url_base) . 
        '</p>' . 
        '<p>' . 
        __("You can use Dotclear's plugin called myUrlHandlers to change short links prefix on your blog.");

        if (preg_match('/index\.php/', $this->url_base)) {
            echo 
            '<p>' . 
            __("We recommand that you use a rewrite engine in order to remove 'index.php' from your blog's URL.") . 
            '<br /><a href="http://fr.dotclear.org/documentation/2.0/usage/blog-parameters">' . 
            __("You can find more about this on the Dotclear's documentation.") . 
            '</a></p>';
        }
        echo 
        '</p>' . 
        '<p>' . __('There are two templates delivered with kUtRL, if you do not use default theme, you may adapt them to yours.') . '<br />' . 
        __('Files are in plugin directory /default-templates, just copy them into your theme and edit them.') . '</p>' . 

        '</div></div><br class="clear"/>';
    }

    public function testService()
    {
        $ap = $this->allow_protocols;
        if (!empty($ap)) {

            return true;
        } else {
            $this->error->add(__('Service is not well configured.'));

            return false;
        }
    }

    public function createHash($url, $hash = null)
    {
        # Create response object
        $rs = new ArrayObject();
        $rs->type = 'local';
        $rs->url  = $url;

        # Normal link
        if ($hash === null) {
            $type = 'localnormal';
            $rs->hash = $this->next($this->last('localnormal'));

        # Mixed custom link
        } elseif (preg_match('/^([A-Za-z0-9]{2,})\!\!$/', $hash, $m)) {
            $type = 'localmix';
            $rs->hash = $m[1] . $this->next(-1, $m[1]);

        # Custom link
        } elseif (preg_match('/^[A-Za-z0-9\.\-\_]{2,}$/', $hash)) {
            if (false !== $this->log->select(null, $hash, null, 'local')) {
                $this->error->add(__('Custom short link is already taken.'));

                return false;
            }
            $type = 'localcustom';
            $rs->hash = $hash;

        # Wrong char in custom hash
        } else {
            $this->error->add(__('Custom short link is not valid.'));

            return false;
        }

        # Save link
        try {
            $this->log->insert($rs->url, $rs->hash, $type, $rs->type);

            return $rs;
        } catch (Exception $e) {
            $this->error->add(__('Failed to save link.'));
        }

        return false;
    }

    protected function last($type)
    {
        return false === ($rs = $this->log->select(null, null, $type, 'local')) ?
            -1 : $rs->hash;
    }

    protected function next($last_id, $prefix = '')
    {
        if ($last_id == -1) {
            $next_id = 0;
        } else {
            for($x = 1; $x <= strlen($last_id); $x++) {
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

        return false === $this->log->select(null,$prefix . $next_id, null, 'local') ?
            $next_id : $this->next($next_id, $prefix);
    }

    protected function append($id)
    {
        $id = str_split($id);
        for ($x = 0; $x < count($id); $x++) {
            $id[$x] = 0;
        }

        return implode($id) . '0';
    }

    protected function increment($id, $pos)
    {
        $id = str_split($id);
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

    public function getUrl($hash)
    {
        if (false === ($rs = $this->log->select(null, $hash, null, 'local'))) {

            return false;
        }
        if (!$rs->url) { //previously removed url

            return false;
        }
        $this->log->counter($rs->id, 'up');

        return $rs->url;
    }

    public function deleteUrl($url, $delete = false)
    {
        if (false === ($rs = $this->log->select($url, null, null, 'local'))) {

            return false;
        }
        if ($delete) {
            $this->log->delete($rs->id);
        } else {
            $this->log->clear($rs->id, '');
        }

        return true;
    }
}