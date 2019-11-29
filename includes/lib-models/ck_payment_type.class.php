<?php
class ck_payment_type extends ck_modeltype_archetype {
	protected function init() {}

	public static $controller_class = 'ck_payment';

	protected static $format = [
		'header' => [
			'cardinality' => cardinality::ROW,
			'columns' => [
				'payment_id' => [],
				'customers_id' => [],
				'customers_extra_logins_id' => [],
				'amount' => ['format' => ['data_type' => data_types::NUM_DECIMAL, 'coerce' => TRUE]],
				'payment_method_id' => [],
				'payment_method_code' => [],
				'payment_method_label' => [],
				'reference_number' => [],
				'payment_date' => ['format' => ['data_type' => data_types::TIME_DATETIME, 'coerce' => TRUE]],
			],
			'db_map' => [
				'customers_id' => 'customer_id',
				'amount' => 'payment_amount',
				'reference_number' => 'payment_ref',
			],
		],
		'customer' => [
			'cardinality' => cardinality::SINGLE,
			'data_type' => data_types::OBJECT_OBJECT,
			'class' => 'ck_customer2',
		],
		'order_applications' => [
			'cardinality' => cardinality::SET,
			'columns' => [
				'payment_application_id' => [],
				'orders_id' => [],
				'order' => ['format' => ['data_type' => data_types::OBJECT_OBJECT, 'class' => 'ck_sales_order']],
				'applied_amount' => ['format' => ['data_type' => data_types::NUM_DECIMAL, 'coerce' => TRUE]],
			],
			'keyed_set' => TRUE,
			'key_column' => 'payment_application_id',
			'db_map' => [
				'payment_application_id' => 'id',
				'orders_id' => 'order_id',
				'applied_amount' => 'amount',
			],
		],
		'invoice_applications' => [
			'cardinality' => cardinality::SET,
			'columns' => [
				'payment_application_id' => [],
				'invoice_id' => [],
				'invoice' => ['format' => ['data_type' => data_types::OBJECT_OBJECT, 'class' => 'ck_invoice']],
				'applied_amount' => ['format' => ['data_type' => data_types::NUM_DECIMAL, 'coerce' => TRUE]],
				'application_date' => ['format' => ['data_type' => data_types::TIME_DATETIME, 'coerce' => TRUE]],
			],
			'keyed_set' => TRUE,
			'key_column' => 'payment_application_id',
			'db_map' => [
				'payment_application_id' => 'payment_to_invoice_id',
				'applied_amount' => 'credit_amount',
				'application_date' => 'credit_date',
			],
		],
	];
}
?>
