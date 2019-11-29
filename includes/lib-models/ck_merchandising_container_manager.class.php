<?php
class ck_merchandising_container_manager extends ck_archetype {

	protected static $queries = [
		'container_types' => [
			'qry' => 'SELECT container_type_id, name, table_name, class_name, context, active FROM ck_merchandising_container_types',
			'cardinality' => cardinality::SET
		]
	];

	private static $container_type_map = [];

	public static function init($reinit=FALSE) {
		if ($reinit) self::$container_type_map = [];

		if (empty(self::$container_type_map)) {
			$types = self::fetch('container_types', []);

			foreach ($types as $type) {
				self::$container_type_map[$type['container_type_id']] = $type;
			}
		}
	}

	public static function instantiate($container_type_id, $container_id) {
		self::init();

		return new self::$container_type_map[$container_type_id]['class_name']($container_id);
	}

	public static function get_container_class($container_type_id) {
		self::init();

		return self::$container_type_map[$container_type_id]['class_name'];
	}

	public static function get_container_table($container_type_id) {
		self::init();

		return self::$container_type_map[$container_type_id]['table_name'];
	}
}
?>
