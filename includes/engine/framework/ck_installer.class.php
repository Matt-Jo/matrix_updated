<?php
class ck_installer extends ck_archetype {

private $setup_status = NULL;

public function __construct() {
	if (!($db = static::get_db())) $this->setup_status = 0;
	else {
		$database = prepared_query::fetch('SELECT DATABASE()', cardinality::SINGLE);
		$tables = prepared_query::fetch('SHOW FULL TABLES IN $database', cardinality::SET);
		echo '<pre>';
		print_r($tables);
		echo '</pre>';
		exit();
		foreach (self::$internal_interface['tables'] as $table_name => $details) {
		}
		self::_setup(); // run the setup, which creates the tables if they don't yet exist
	}
}

// interface details
public static function init_interface() {
	if (file_exists(__DIR__.'/../config/framework.json')) {
		$json = file_get_contents(__DIR__.'/../config/framework.json');
		if (is_null((self::$internal_interface = @json_decode($json, TRUE)))) {
			// not valid json
			return FALSE;
		}
	}
}

protected static $internal_interface;

}
ck_installer::init_interface();
?>