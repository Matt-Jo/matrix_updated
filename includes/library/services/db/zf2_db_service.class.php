<?php
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Driver\ConnectionInterface;
use Zend\Db\Adapter\Platform;
use Zend\Db\Adapter\Profiler;
use Zend\Db\ResultSet;

class zf2_db_service implements db_service_interface {

    private $vendor;

    public function __construct($driver, Platform\PlatformInterface $platform = null, ResultSet\ResultSetInterface $queryResultPrototype = null, Profiler\ProfilerInterface $profiler = null)
    {
        $this->vendor = new Adapter($driver, $platform, $queryResultPrototype, $profiler);
    }

	public function get_adapter() {
		return $this->vendor;
	}

	public function create_statement(string $sql) {
		$stmt = $this->vendor->createStatement($sql);
		$stmt->prepare();
		return $stmt;
	}

    public function query(string $sql, $bind = array())
    {
        return $this->vendor->query($sql, $bind);
    }

    public function begin_transaction(): ConnectionInterface
    {
        return $this->vendor->getDriver()->getConnection()->beginTransaction();
    }

    public function commit(): ConnectionInterface
    {
        return $this->vendor->getDriver()->getConnection()->commit();
    }

    public function insert(string $table, array $bind)
    {
        $sqlUpdate = "INSERT INTO ".$table." SET ";

        $sqlAssignments = implode(" , ", array_map(function($col, $newValue) {
            return $col."=". $newValue;
        }, $bind));

        $sql = $sqlUpdate." ".$sqlAssignments;
        $this->vendor->query($sql, array_values($bind));
    }

    public function update(string $table, array $bind, string $where = '', array $whereParams = [])
    {
        $sqlInsertInto = "UPDATE ".$table." ";

        $sqlCols = " ( ". implode(',', array_keys($bind)). " ) ";
        $sqlPreparedValues = implode(',', array_fill(0,count($bind), "?"));

        $sql = $sqlInsertInto." ( ".$sqlCols." ) VALUES ( ".$sqlPreparedValues." )";
        $this->vendor->query($sql, array_values($bind));
    }

    public function delete(string $table, $where = '')
    {
        $sql = "DELETE ".$table." WHERE ".$where;
        $this->vendor->query($sql);
    }

    public function select()
    {
        // TODO: Implement select() method.
        throw new \Exception("not implemented");
    }


    /**
     * @param string $sql
     * @param array $bind
     * @param null $fetchMode
     * @return array
     */
    public function fetch_all(string $sql, $bind = array(), $fetchMode = null): array
    {
        $bind = is_array($bind) ? $bind : [$bind];
        return $this->vendor->query($sql, $bind)->toArray();
    }

    /**
     * @deprecated use fetchAll instead with "LIMIT 1"
     * @param $sql
     * @param array $bind
     * @param null $fetchMode
     * @return array
     */
    public function fetch_row(string $sql, $bind = array(), $fetchMode = null): array
    {
        $result = $this->fetch_all($sql, $bind, $fetchMode);
        return empty($result) ? $result : current($result);
    }

    /**
     * @deprecated use fetchAll instead
     * @param $sql
     * @param array $bind
     * @return array
     */
    public function fetch_assoc(string $sql, $bind = array()) : array
    {
        return $this->fetch_all($sql, $bind);
    }

    /**
     * @deprecated use fetchAll instead
     * @param $sql
     * @param array $bind
     * @return array
     */
    public function fetch_col(string $sql, $bind = array()): array
    {
        return $this->fetch_all($sql, $bind);
    }

    /**
     * @deprecated use fetchAll instead
     * @param $sql
     * @param array $bind
     * @return string|null
     */
    public function fetch_one(string $sql, $bind = array()): ?string
    {
        $rows = $this->fetch_all($sql, $bind);
        if(!empty($rows)) {
            // strip everything except by the first value in the first col of the first row
            $result = array_values(current($rows))[0];
        } else {
            $result = null;
        }
        return $result;
    }

    public function quote($value, $type = null): string
    {
        return $this->vendor->getPlatform()->quoteValue($value);
    }


    public function quote_into(string $text, $value, $type = null, int $count = null): string
    {
        if ($count === null) {
            return str_replace('?', $this->quote($value, $type), $text);
        } else {
            while ($count > 0) {
                if (strpos($text, '?') !== false) {
                    $text = substr_replace($text, $this->quote($value, $type), strpos($text, '?'), 1);
                }
                --$count;
            }
            return $text;
        }
    }


    public function close_connection(): ConnectionInterface
    {
        return $this->vendor->getDriver()->getConnection()->disconnect();
    }


    public function last_insert_id(string $tableName = null, $primaryKey = null): string
    {
        return $this->vendor->getDriver()->getConnection()->getLastGeneratedValue($tableName);
    }
}
