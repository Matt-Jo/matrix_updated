<?php
class ck_suggested_buy_type extends ck_modeltype_archetype {
	protected function init() {}

	public static $controller_class = 'ck_suggested_buy';

	protected static $format = [
		'header' => [
			'cardinality' => cardinality::ROW,
			'columns' => [
				'purchase_order_suggested_buy_id' => [],
				'void' => ['format' => ['data_type' => data_types::BOOL_BOOL, 'coerce' => TRUE]],
				'suggested_buy_date' => ['format' => ['data_type' => data_types::TIME_DATETIME, 'coerce' => TRUE]],
			],
		],
		'buys' => [
			'cardinality' => cardinality::SET,
			'columns' => [
				'purchase_order_suggested_buy_vendor_id' => [],
				'vendors_id' => [],
				'handled' => [],
				'vendor' => ['format' => ['data_type' => data_types::OBJECT_OBJECT, 'class' => 'ck_vendor']],
				'ipns' => ['format' => ['data_type' => data_types::SET_SET]],
			],
			'keyed_set' => TRUE,
			'key_column' => 'vendors_id',
		],
	];
}
?>
