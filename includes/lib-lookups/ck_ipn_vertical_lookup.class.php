<?php
class ck_ipn_vertical_lookup extends ck_lookup {
	protected $lookup_name = 'IPN Vertical';

	protected $lookup_table = 'products_stock_control_verticals';

	protected $table_key = 'id';

	protected $direct_key = 'ipn_vertical_id';
	protected $reverse_key = 'vertical';

	protected static $queries = [
		'lookup' => [
			'qry' => 'SELECT id as ipn_vertical_id, name as vertical FROM products_stock_control_verticals ORDER BY name ASC',
			'cardinality' => cardinality::SET
		]
	];
}

class CKIPNVerticalException extends CKMasterArchetypeException {
}
?>
