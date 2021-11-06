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
# This file takes records from plugin dcMiniUrl
# and inserts them into plugin kUtRL.

if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$miniurl_patch = new dcMiniUrl2kUtRL($core);
if ($miniurl_patch->parseRecords()) {
    try {
        $core->plugins->deactivateModule('dcMiniUrl');
    } catch (Exception $e) {
        //$core->error->add($e->getMessage());
    }
}

class dcMiniUrl2kUtRL
{
    public $core;

    public $k_tb;
    public $m_tb;

    public function __construct($core)
    {
        $this->core = $core;
        $this->con  = $core->con;
        $this->k_tb = $core->prefix . 'kutrl';
        $this->m_tb = $core->prefix . 'miniurl';
    }

    public function parseRecords()
    {
        $rs = $this->con->select(
            'SELECT * FROM ' . $this->m_tb . ' '
        );

        while ($rs->fetch()) {
            if ($rs->miniurl_type == 'customurl' || $rs->miniurl_type == 'miniurl') {
                if ($this->exists($rs)) {
                    continue;
                }

                $this->insertKutrl($rs);
                $this->insertLocal($rs);
            } else {
                $this->insertOther($rs);
            }
        }

        return true;
    }

    private function insertKutrl($rs)
    {
        $cur               = $this->common($rs);
        $cur->kut_service  = 'kutrl';
        $cur->kut_type     = 'local';
        $cur->kut_counter  = 0;
        $cur->kut_password = null;

        $cur->insert();
        $this->con->unlock();
    }

    private function insertLocal($rs)
    {
        $cur              = $this->common($rs);
        $cur->kut_service = 'local';
        $cur->kut_type    = $rs->miniurl_type == 'customurl' ?
            'localcustom' : 'localnormal';

        $cur->insert();
        $this->con->unlock();
    }

    private function insertOther($rs)
    {
        $cur = $this->common($rs);

        $cur->insert();
        $this->con->unlock();
    }

    private function common($rs)
    {
        $cur = $this->con->openCursor($this->k_tb);
        $this->con->writeLock($this->k_tb);
        $cur->kut_id       = $this->nextId();
        $cur->blog_id      = $rs->blog_id;
        $cur->kut_service  = 'unknow';
        $cur->kut_type     = $rs->miniurl_type;
        $cur->kut_hash     = $rs->miniurl_id;
        $cur->kut_url      = $rs->miniurl_str;
        $cur->kut_dt       = $rs->miniurl_dt;
        $cur->kut_counter  = $rs->miniurl_counter;
        $cur->kut_password = $rs->miniurl_password;

        return $cur;
    }

    private function exists($rs)
    {
        $chk = $this->con->select(
            'SELECT kut_hash FROM ' . $this->k_tb . ' ' .
            "WHERE blog_id = '" . $rs->blog_id . "' " .
            "AND kut_service = 'local' " .
            "AND kut_hash = '" . $rs->miniurl_id . "' "
        );

        return !$chk->isEmpty();
    }

    private function nextId()
    {
        return $this->con->select(
            'SELECT MAX(kut_id) FROM ' . $this->k_tb . ' '
        )->f(0) + 1;
    }
}
