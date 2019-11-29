<?php
class ck_ipn_category_lookup extends ck_lookup {
	protected $lookup_name = 'IPN Category';

	protected $lookup_table = 'products_stock_control_categories';

	protected $table_key = 'categories_id';

	protected $direct_key = 'ipn_category_id';
	protected $reverse_key = 'category';

	protected static $queries = [
		'lookup' => [
			'qry' => 'SELECT categories_id as ipn_category_id, name as category, sort_order, vertical_id as ipn_vertical_id, pricing_review FROM products_stock_control_categories ORDER BY name ASC',
			'cardinality' => cardinality::SET
		]
	];
}

class CKIPNCategoryException extends CKMasterArchetypeException {
}
?>
