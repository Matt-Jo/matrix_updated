<?php
class prepared_db {
	public static function legacy_init($cacheKey='ckstore', $params=[], $adapter=NULL) {
		try {
			$db = Zend_Registry::get($cacheKey);
		}
		catch (Zend_Exception $e) {
			// needed until MySQL 5.1
			$params['driver_options'] = [PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE];

			$config = Zend_Registry::get('config');

			// set utf8 for all connections
			$params['charset'] = 'utf8';

			$params = array_merge($config->database->params->toArray(), $params);

			if (!isset($adapter)) $adapter = $config->database->adapter;

			$db = Zend_Db::factory($adapter, $params);

			Zend_Registry::set($cacheKey, $db);
		}

		return $db;
	}
}
?>
