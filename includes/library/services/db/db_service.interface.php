<?php
use Zend\Db\Adapter\Driver\ConnectionInterface;

/**
 * Common interface to every pluggable db handling service
 */
interface db_service_interface extends service_interface {

    /**
     * Server specific public methods
     *
     * @return mixed
     */
    public function query(string $sql, $bind = array());
    public function begin_transaction(): ConnectionInterface;
    public function commit(): ConnectionInterface;
    public function fetch_all(string $sql, $bind = array(), $fetchMode = null);
    public function fetch_row(string $sql, $bind = array(), $fetchMode = null): array;
    public function fetch_assoc(string $sql, $bind = array()): array;
    public function fetch_col(string $sql, $bind = array());
    public function fetch_one(string $sql, $bind = array());
    public function quote($value, $type = null): string;
    public function quote_into(string $text, $value, $type = null, int $count = null): string;
    public function close_connection(): ConnectionInterface;
    public function last_insert_id(string $tableName = null, $primaryKey = null): string;

    /**
     * @deprecated
     */
    public function insert(string $table, array $bind);
    /**
     * @deprecated
     */
    public function update(string $table, array $bind, string $where = '', array $whereParams = []);
    /**
     * @deprecated
     */
    public function delete(string $table, $where = '');
    /**
     * @deprecated
     */
    public function select();

}

