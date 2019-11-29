<?php
class ck_keys {

	private $cache;

	private $changed_keys = [];
	private $removed_keys = [];
	private $descriptions = [];

	private $memcached_servers = [
		['host' => '127.0.0.1', 'port' => 11211], // localhost
	];
	// because any changing value here goes through our class, which updates the cache (and updates are infrequent anyway) cache for up to an hour
	// stale data is unlikely
	private $memcached_timeout = 3600;

	private $db_stats = [
		'table' => 'ck_keys'
	];

	public function __construct($mc=NULL) {
		// this inits memcached or gets it externally (and, since this is to be our Memcached abstraction class, exports it to global)
		// so it can be used by Zend as well
		$this->init($mc);
		$this->connect();

		if (!empty($_SESSION['admin_login_id'])) $this->process();
	}

	private function init($mc) {
		if (!($mc = self::get_mc($mc))) {
			$mc = new Memcached;
			self::set_mc($mc);
		}

		if (!isset($GLOBALS['memcached'])) $GLOBALS['memcached'] = $mc;
	}

	private function connect() {
		$mc = self::get_mc();
		$servers = $mc->getServerList();

		foreach ($this->memcached_servers as $new_server) {
			if (!empty($servers)) {
				foreach ($servers as $server) {
					if ($server['host'] == $new_server['host'] && $server['port'] == $new_server['port']) continue 2;
				}
			}
			$mc->addServer($new_server['host'], $new_server['port']);
		}
	}

	private function process() {
		if (!empty($_REQUEST['global_action']) && $this->action_match($_REQUEST['global_action'], ['updateconfig', 'updatekeys'])) {
			foreach ($_REQUEST['site_keys'] as $key => $val) {
				$this->__set($key, $val);
			}
		}
	}

	private function action_match($key, $match, $method=0) {
		$key = strtolower(trim($key));
		if ($method == 0) { // attempt all matching styles
			$key1 = preg_replace('/\s+/', '', $key);
			$key2 = preg_replace('/\s+/', '_', $key);

			if (is_array($match)) return in_array($key, $match) || in_array($key1, $match) || in_array($key2, $match);
			else return $key == $match || $key1 == $match || $key2 == $match;
		}
		elseif ($method == 1) { // attempt to compress as much as possible
			$key1 = preg_replace('/\s+/', '', $key);

			if (is_array($match)) return in_array($key, $match) || in_array($key1, $match);
			else return $key == $match || $key1 == $match;
		}
		elseif ($method == 2) { // attempt to match only space to underscore
			$key2 = preg_replace('/\s+/', '_', $key);

			if (is_array($match)) return in_array($key, $match) || in_array($key2, $match);
			else return $key == $match || $key2 == $match;
		}
		else { // exact match only
			if (is_array($match)) return in_array($key, $match);
			else return $key == $match;
		}
	}

	public function __destruct() {
		$this->write();
	}

	public function write($db=NULL) {
		if (!empty($this->removed_keys)) {
			foreach ($this->removed_keys as $keys) {
				if (is_null($keys['subkey'])) prepared_query::execute('DELETE FROM ck_keys WHERE master_key = ? AND NULLIF(subkey, \'\') IS NULL', $keys['master_key']);
				else prepared_query::execute('DELETE FROM ck_keys WHERE master_key = ? AND subkey = ?', [$keys['master_key'], $keys['subkey']]);
			}
		}

		if (!empty($this->changed_keys)) {
			$params = [];
			$vals = [];

			$params_d = [];
			$vals_d = [];

			foreach ($this->changed_keys as $full_key => $keys) {
				if (isset($this->descriptions[$full_key])) {
					$params_d[] = '(?, ?, ?, ?, NOW())';
					$vals_d[] = $keys['master_key'];
					$vals_d[] = !is_null($keys['subkey'])?$keys['subkey']:'';
					$vals_d[] = json_encode($keys['keyval']); // we encode it everytime, because scalar values are handled intelligently
					$vals_d[] = $this->descriptions[$full_key];
				}
				else {
					$params[] = '(?, ?, ?, NOW())';
					$vals[] = $keys['master_key'];
					$vals[] = !is_null($keys['subkey'])?$keys['subkey']:'';
					$vals[] = json_encode($keys['keyval']); // we encode it everytime, because scalar values are handled intelligently
				}
			}

			if (!empty($params)) prepared_query::execute('INSERT INTO ck_keys (master_key, subkey, keyval, last_touch) VALUES '.implode(', ', $params).' ON DUPLICATE KEY UPDATE keyval=VALUES(keyval)', $vals);
			if (!empty($params_d)) prepared_query::execute('INSERT INTO ck_keys (master_key, subkey, keyval, description, last_touch) VALUES '.implode(', ', $params_d).' ON DUPLICATE KEY UPDATE keyval=VALUES(keyval), description=VALUES(description)', $vals_d);

			$this->changed_keys = [];
		}
	}

	// the __get() method does *not* return by reference, because doing so would allow cached values to be changed
	// while bypassing the __set() method, thus breaking the cache-save
	public function __get($key) {
		if (empty($key)) return NULL;

		if (strpos($key, '.', 1) !== FALSE) list($key, $subkey) = explode('.', $key, 2);
		else $subkey = NULL;

		$mc = self::get_mc();

		$cachetry = FALSE;
		if (!isset($this->cache[$key])) {
			$this->cache[$key] = $mc->get($key);
			$cachetry = TRUE;
		}
		if ($this->cache[$key] === FALSE && ($cachetry && $mc->getResultCode() !== Memcached::RES_SUCCESS)) {
			unset($this->cache[$key]); // undo our attempt to set it from memcached
			$db = self::get_db();

			// I'm not using table stats for this, just hard coding the query directly, not entirely sure how to make this benefit from a little dynamism.
			if (!empty($db) && $keys = prepared_query::fetch('SELECT * FROM ck_keys WHERE master_key = :key', cardinality::SET, [':key' => $key])) {
				$this->cache[$key] = [];
				foreach ($keys as $keyvals) {
					if (!empty($keyvals['subkey'])) $this->cache[$key][$keyvals['subkey']] = json_decode($keyvals['keyval']);
					else $this->cache[$key] = json_decode($keyvals['keyval']);

					if (json_last_error() !== JSON_ERROR_NONE) {
						// a few keys have been stored *not* in json format, in advance of pushing this code live we'll have to fall back to using those values directly
						if (!empty($keyvals['subkey'])) $this->cache[$key][$keyvals['subkey']] = $keyvals['keyval'];
						else $this->cache[$key] = $keyvals['keyval'];
					}
				}

				$mc->set($key, $this->cache[$key], $this->memcached_timeout);
			}
		}

		if (empty($subkey)) return isset($this->cache[$key])?$this->cache[$key]:NULL;
		else return isset($this->cache[$key][$subkey])?$this->cache[$key][$subkey]:NULL;
	}

	public function __set($key, $val) {
		if (empty($key)) return NULL;

		$full_key = $key;

		if (strpos($key, '.', 1) !== FALSE) list($key, $subkey) = explode('.', $key, 2);
		else $subkey = '';

		$mc = self::get_mc();

		$set = FALSE;

		if (!empty($subkey)) {
			// we're setting a specific subkey, so we need to make sure the whole key is populated
			$this->__get($key);
			$set = isset($this->cache[$key]);
			$this->cache[$key][$subkey] = $val;
		}
		else {
			// we're setting the whole key, so we don't need to worry about the current value of the key
			$set = isset($this->cache[$key]);
			$this->cache[$key] = $val;
		}

		if ($set) {
			// if our local copy is set, assume it's in the cache and attempt to replace it
			if (!$mc->replace($key, $this->cache[$key], $this->memcached_timeout) && $mc->getResultCode() === Memcached::RES_NOTSTORED) {
				$mc->add($key, $this->cache[$key], $this->memcached_timeout);
			}
		}
		else {
			// if our local copy is not set, assume it's not in the cache and attempt to add it
			if (!$mc->add($key, $this->cache[$key], $this->memcached_timeout) && $mc->getResultCode() === Memcached::RES_NOTSTORED) {
				$mc->replace($key, $this->cache[$key], $this->memcached_timeout);
			}
		}

		$this->changed_keys[$full_key] = ['master_key' => $key, 'subkey' => $subkey, 'keyval' => $val];

		return $val;
	}

	public function __isset($key) {
		if (empty($key)) return NULL;

		if (strpos($key, '.', 1) !== FALSE) list($key, $subkey) = explode('.', $key, 2);
		else $subkey = '';

		$mc = self::get_mc();

		$this->__get($key);

		if (!empty($subkey)) {
			// we're setting a specific subkey, so we need to make sure the whole key is populated
			return isset($this->cache[$key][$subkey]);
		}
		else {
			// we're setting the whole key, so we don't need to worry about the current value of the key
			return isset($this->cache[$key]);
		}
	}

	public function __unset($key) {
		if (empty($key)) return;

		$full_key = $key;

		if (strpos($key, '.', 1) !== FALSE) list($key, $subkey) = explode('.', $key, 2);
		else $subkey = NULL;

		$mc = self::get_mc();

		if (!empty($subkey)) {
			// we're deleting a specific subkey, so we need to make sure the whole key is populated
			$this->__get($key);
			if (isset($this->cache[$key][$subkey])) unset($this->cache[$key][$subkey]);
			if (!($set = !(empty($this->cache[$key]) && is_array($this->cache[$key])))) {
				$this->cache[$key] = NULL;
				//unset($this->cache[$key]);
			}

			// if our local copy is set, assume it's in the cache and attempt to replace it
			if (!$mc->replace($key, $this->cache[$key], $this->memcached_timeout) && $mc->getResultCode() === Memcached::RES_NOTSTORED) {
				$mc->add($key, $this->cache[$key], $this->memcached_timeout);
			}

			// we now have to add a new key for the master key without any subkeys or values
			if ($set) $this->changed_keys[$full_key] = ['master_key' => $key, 'subkey' => NULL, 'keyval' => NULL];
		}
		else {
			$mc->delete($key);
		}

		$this->removed_keys[$full_key] = ['master_key' => $key, 'subkey' => $subkey];
	}

	public function describe($key, $description=NULL) {
		if (empty($key)) return NULL;

		$full_key = $key;

		if (strpos($key, '.', 1) !== FALSE) list($key, $subkey) = explode('.', $key, 2);
		else $subkey = '';

		$this->changed_keys[$full_key] = ['master_key' => $key, 'subkey' => $subkey, 'keyval' => $this->__get($full_key)];
		$this->descriptions[$full_key] = $description;
	}

	// wrangle the database, if we want to set it explicitly for the call or the class (otherwise, fall back to the global instance)
	protected static $db = NULL;
	public static function set_db($db) {
		static::$db = $db;
	}
	// this allows us to use dependancy injection without requiring it
	protected static function get_db($db=NULL) {
		return $db ?? self::$db ?? service_locator::get_db_service() ?? NULL;
	}

	// wrangle Memcached
	protected static $mc = NULL;
	public static function set_mc($mc) {
		static::$mc = $mc;
	}
	// this allows us to use dependancy injection without requiring it
	protected static function get_mc($mc=NULL) {
		!$mc?(!empty(self::$mc)?$mc=self::$mc:$mc=(!empty($GLOBALS['memcached'])?$GLOBALS['memcached']:NULL)):NULL;
		return $mc;
	}
}

/*
CREATE TABLE ck_keys (
  key_id int(11) NOT NULL,
  master_key varchar(255) NOT NULL DEFAULT 'global',
  subkey varchar(255) DEFAULT NULL,
  keyval varchar(255) NOT NULL,
  description text DEFAULT NULL,
  entered timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_touch datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;

ALTER TABLE ck_keys
  ADD PRIMARY KEY (key_id),
  ADD UNIQUE KEY master_key (master_key,subkey);

ALTER TABLE ck_keys
  MODIFY key_id int(11) NOT NULL AUTO_INCREMENT;
*/
?>