<?php
class ck_modeltype_registry {
	protected static $registry;

	public static function record_exists($class, $key) {
		return !empty(self::$registry[$class][$key]);
	}

	public static function destroy_record($class, $key, $force=FALSE) {
		if ($force) self::$registry[$class][$key] = NULL;
		unset(self::$registry[$class][$key]);
	}

	public static function get_record($class, $key) {
		return self::$registry[$class][$key];
	}

	public static function register($class, $key, $record) {
		self::$registry[$class][$key] = $record;
	}
}

class CKModeltypeRegistryException extends Exception {
}
?>
