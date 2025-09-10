<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\{
    DeleteStatement,
    JoinStatement,
    SelectStatement,
    UpdateStatement
};
use Dotclear\Interface\Database\ConnectionInterface;
use Exception;

/**
 * @brief       kUtRL logs class.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Logs
{
    public string $table;
    public ConnectionInterface $con;

    public function __construct()
    {
        $this->table = App::db()->con()->prefix() . My::TABLE_NAME;
        $this->con   = App::db()->con();
    }

    public function nextId(): int
    {
        $sql = new SelectStatement();

        $rs = $sql
            ->column($sql->max('kut_id'))
            ->from($this->table)
            ->select();

        return is_null($rs) || $rs->isEmpty() ? 1 : (int) $rs->f(0) + 1;
    }

    /**
     * @return  false|array<string,int|string>
     */
    public function insert(string $url, string $hash, string $type, string $service = 'kutrl')
    {
        $cur = $this->con->openCursor($this->table);
        $this->con->writeLock($this->table);

        try {
            $cur->kut_id      = $this->nextId();
            $cur->blog_id     = App::blog()->id();
            $cur->kut_url     = (string) $url;
            $cur->kut_hash    = (string) $hash;
            $cur->kut_type    = (string) $type;
            $cur->kut_service = (string) $service;
            $cur->kut_dt      = date('Y-m-d H:i:s');
            $cur->kut_counter = 0;

            $cur->insert();
            $this->con->unlock();

            return [
                'id'       => $cur->kut_id,
                'url'      => $url,
                'hash'     => $hash,
                'type'     => $type,
                'service'  => $service,
                'counter ' => 0,
            ];
        } catch (Exception $e) {
            $this->con->unlock();

            throw $e;
        }
    }

    /**
     * @return  false|MetaRecord
     */
    public function select(?string $url = null, ?string $hash = null, ?string $type = null, string $service = 'kutrl')
    {
        $sql = new SelectStatement();
        $sql
            ->columns([
                $sql->as('kut_id', 'id'),
                $sql->as('kut_hash', 'hash'),
                $sql->as('kut_url', 'url'),
                $sql->as('kut_type', 'type'),
                $sql->as('kut_service', 'service'),
                $sql->as('kut_counter', 'counter'),
            ])
            ->from($this->table)
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('kut_service = ' . $sql->quote($service))
        ;

        if (null !== $url) {
            $sql->and('kut_url = ' . $sql->quote($url));
        }
        if (null !== $hash) {
            $sql->and('kut_hash = ' . $sql->quote($hash));
        }
        if (null !== $type) {
            $sql->and('kut_type = ' . $sql->quote($type));
        }

        $rs = $sql
            ->order('kut_dt DESC')
            ->limit(1)
            ->select();

        return is_null($rs) || $rs->isEmpty() ? false : $rs;
    }

    public function clear(int $id): bool
    {
        $cur = $this->con->openCursor($this->table);
        $this->con->writeLock($this->table);

        try {
            $cur->kut_url     = '';
            $cur->kut_dt      = date('Y-m-d H:i:s');
            $cur->kut_counter = 0;

            $cur->update(
                "WHERE blog_id='" . $this->con->escapeStr(App::blog()->id()) . "' " .
                "AND kut_id='" . $id . "' "
            );
            $this->con->unlock();

            return true;
        } catch (Exception $e) {
            $this->con->unlock();

            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $sql = new DeleteStatement();
        $sql
            ->from($this->table)
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('kut_id = ' . $id)
            ->delete();

        return true;
    }

    public function counter(int $id, string $do = 'get'): int
    {
        $sql = new SelectStatement();
        $rs  = $sql
            ->column('kut_counter')
            ->from($this->table)
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('kut_id = ' . $id)
            ->select();

        $counter = is_null($rs) || $rs->isEmpty() ? 0 : (int) $rs->kut_counter;

        if ('get' == $do) {
            return $counter;
        } elseif ('up' == $do) {
            $counter += 1;
        } elseif ('reset' == $do) {
            $counter = 0;
        } else {
            return 0;
        }

        $sql = new UpdateStatement();
        $ret = $sql->ref($this->table)
            ->column('kut_counter')
            ->value($counter)
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('kut_id = ' . $id)
            ->update();

        return $counter;
    }

    /**
     * @param   array<string, mixed>    $params
     */
    public function getLogs(array $params, bool $count_only = false): MetaRecord
    {
        $sql = new SelectStatement();

        if ($count_only) {
            $sql->column($sql->count($sql->unique('S.kut_id')));
        } else {
            if (!empty($params['columns']) && is_array($params['columns'])) {
                $sql->columns($params['columns']);
            }
            $sql->columns([
                'S.kut_id',
                'S.kut_type',
                'S.kut_hash',
                'S.kut_url',
                'S.kut_dt',
                'S.kut_counter',
            ]);
        }

        $sql->from($sql->as($this->table, 'S'));

        if (!empty($params['from'])) {
            $sql->from($params['from']);
        }

        $sql->where('S.blog_id = ' . $sql->quote(App::blog()->id()));

        if (isset($params['kut_service'])) {
            $sql->and('kut_service = ' . $sql->quote($params['kut_service']));
        } else {
            $sql->and("kut_service = 'kutrl' ");
        }
        if (isset($params['kut_type'])) {
            $sql->and('kut_type ' . $sql->in($params['kut_type']));
        }
        if (isset($params['kut_id'])) {
            $sql->and('kut_id ' . $sql->in($params['kut_id']));
        }
        if (isset($params['kut_hash'])) {
            $sql->and('kut_hash ' . $sql->in($params['kut_hash']));
        }
        if (isset($params['kut_url'])) {
            $sql->and('kut_url ' . $sql->in($params['kut_url']));
        }
        if (!empty($params['kut_year'])) {
            $sql->and($sql->dateFormat('kut_dt', '%Y') . ' = ' . $sql->quote(sprintf('%04d', $params['kut_year'])));
        }
        if (!empty($params['kut_month'])) {
            $sql->and($sql->dateFormat('kut_dt', '%m') . ' = ' . $sql->quote(sprintf('%02d', $params['kut_month'])));
        }
        if (!empty($params['kut_day'])) {
            $sql->and($sql->dateFormat('kut_dt', '%d') . ' = ' . $sql->quote(sprintf('%02d', $params['kut_day'])));
        }
        if (!empty($params['sql'])) {
            $sql->sql($params['sql']);
        }
        if (!$count_only) {
            if (!empty($params['order'])) {
                $sql->order($sql->escape($params['order']));
            } else {
                $sql->order('kut_dt DESC');
            }
        }

        if (!$count_only && !empty($params['limit'])) {
            $sql->limit($params['limit']);
        }

        return $sql->select() ?? MetaRecord::newFromArray([]);
    }
}
