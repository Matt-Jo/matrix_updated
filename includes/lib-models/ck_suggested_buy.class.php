<?php
class ck_suggested_buy extends ck_model_archetype {
	protected static $queries = [
		'suggested_buy_header_proto' => [
			'proto_qry' => [
				'data_operation' => 'SELECT posb.purchase_order_suggested_buy_id, posb.void, posb.suggested_buy_date',

				'from' => 'FROM purchase_order_suggested_buys posb',

				'where' => 'WHERE', // will fail if we don't provide our own
			],
			'proto_opts' => [
				':purchase_order_suggested_buy_id' => 'posb.purchase_order_suggested_buy_id = :purchase_order_suggested_buy_id',
			],
			'proto_defaults' => [
				'where' => 'posb.purchase_order_suggested_buy_id = :purchase_order_suggested_buy_id',
			],
			'proto_count_clause' => 'COUNT(posb.purchase_order_suggested_buy_id)',
			'cardinality' => cardinality::ROW,
		],

		'vendors' => [
			'qry' => 'SELECT posbv.purchase_order_suggested_buy_vendor_id, posbv.vendors_id, posbv.handled FROM purchase_order_suggested_buy_vendors posbv WHERE posbv.purchase_order_suggested_buy_id = :purchase_order_suggested_buy_id',
			'cardinality' => cardinality::SET,
		],

		'ipns' => [
			'qry' => 'SELECT posbi.purchase_order_suggested_buy_ipn_id, posbi.purchase_order_suggested_buy_vendor_id, posbi.stock_id, posbi.quantity, posbi.unit_of_measure, posbi.handled FROM purchase_order_suggested_buy_ipns posbi WHERE posbi.purchase_order_suggested_buy_id = :purchase_order_suggested_buy_id',
			'cardinality' => cardinality::SET,
		],
	];

	protected function init() {
	}

	protected static function get_instance($purchase_order_suggested_buy_id=NULL) {
		return ck_suggested_buy_type::instance($purchase_order_suggested_buy_id);
	}

	public static function get_with_instance($purchase_order_suggested_buy_id=NULL) {
		return self::init_with_model(self::get_instance($purchase_order_suggested_buy_id));
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function build_header() {
		$qry = self::modify_query('suggested_buy_header_proto');
		$header = self::fetch($qry, [':purchase_order_suggested_buy_id' => $this->id()]);
		$this->load('header', $header?:NULL);
	}

	private function build_buys() {
		$vendors = prepared_query::fetch(self::$queries['vendors']['qry'], self::$queries['vendors']['cardinality'], [':purchase_order_suggested_buy_id' => $this->id()]);
		$ipns = prepared_query::keyed_set_group_fetch(self::$queries['ipns']['qry'], 'purchase_order_suggested_buy_vendor_id', [':purchase_order_suggested_buy_id' => $this->id()]);

		$buys = array_map(function($vendor) use ($ipns) {
			$vendor['vendor'] = new ck_vendor($vendor['vendors_id']);
			$vendor['ipns'] = array_map(function($ipn) {
				$ipn['handled'] = CK\fn::check_flag($ipn['handled']);
				$ipn['ipn'] = new ck_ipn2($ipn['stock_id']);

				return $ipn;
			}, $ipns[$vendor['purchase_order_suggested_buy_vendor_id']]);

			return $vendor;
		}, $vendors);

		$this->load('buys', $buys);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function has_header($key=NULL) {
		if (!$this->is_loaded('header')) $this->build_header();
		return $this->has($key, 'header');
	}

	public function get_header($key=NULL) {
		if (!$this->has_header($key)) return NULL;
		return $this->get($key, 'header');
	}

	public function has_buys($key=NULL) {
		if (!$this->is_loaded('buys')) $this->build_buys();
		return $this->has($key, 'buys');
	}

	public function get_buys($key=NULL) {
		if (!$this->has_buys($key)) return [];
		return $this->get($key, 'buys');
	}

	public static function get_unhandled_suggestions() {
		$suggestions = [];

		if ($suggestion_ids = prepared_query::fetch('SELECT DISTINCT posb.purchase_order_suggested_buy_id FROM purchase_order_suggested_buy_vendors posbv JOIN purchase_order_suggested_buys posb ON posbv.purchase_order_suggested_buy_id = posb.purchase_order_suggested_buy_id WHERE posb.void = 0 AND posbv.handled = 0 ORDER BY posb.suggested_buy_date', cardinality::COLUMN)) {
			foreach ($suggestion_ids as $suggestion_id) {
				$suggestions[$suggestion_id] = self::get_instance($suggestion_id);
			}
		}

		return $suggestions;
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public static function create_suggestion(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$purchase_order_suggested_buy_id = prepared_query::insert('INSERT INTO purchase_order_suggested_buys VALUES ()');

			$vendors = [];

			foreach ($data as $ipn) {
				if (empty($vendors[$ipn['vendors_id']])) $vendors[$ipn['vendors_id']] = prepared_query::insert('INSERT INTO purchase_order_suggested_buy_vendors (purchase_order_suggested_buy_id, vendors_id) VALUES (:purchase_order_suggested_buy_id, :vendors_id)', [':purchase_order_suggested_buy_id' => $purchase_order_suggested_buy_id, ':vendors_id' => $ipn['vendors_id']]);

				if (!empty($ipn['stock_id'])) prepared_query::execute('INSERT INTO purchase_order_suggested_buy_ipns (purchase_order_suggested_buy_vendor_id, purchase_order_suggested_buy_id, stock_id, quantity, unit_of_measure) VALUES (:purchase_order_suggested_buy_vendor_id, :purchase_order_suggested_buy_id, :stock_id, :quantity, 1)', [':purchase_order_suggested_buy_vendor_id' => $vendors[$ipn['vendors_id']], ':purchase_order_suggested_buy_id' => $purchase_order_suggested_buy_id, ':stock_id' => $ipn['stock_id'], ':quantity' => $ipn['qty']]);
				elseif (!empty($ipn['ipn'])) prepared_query::execute('INSERT INTO purchase_order_suggested_buy_ipns (purchase_order_suggested_buy_vendor_id, purchase_order_suggested_buy_id, stock_id, quantity, unit_of_measure) SELECT :purchase_order_suggested_buy_vendor_id, :purchase_order_suggested_buy_id, stock_id, :quantity, 1 FROM products_stock_control WHERE stock_name = :ipn', [':purchase_order_suggested_buy_vendor_id' => $vendors[$ipn['vendors_id']], ':purchase_order_suggested_buy_id' => $purchase_order_suggested_buy_id, ':ipn' => $ipn['ipn'], ':quantity' => $ipn['qty']]);
			}

			$suggestion = self::get_instance($purchase_order_suggested_buy_id);
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKSuggestedBuyException('Failed to create suggestion.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return $suggestion;
	}

	protected function commit_changes($changes) {}

	public function void() {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('UPDATE purchase_order_suggested_buys SET void = 1 WHERE purchase_order_suggested_buy_id = :purchase_order_suggested_buy_id', [':purchase_order_suggested_buy_id' => $this->id()]);
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKSuggestedBuyException('Failed to void suggestion.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function unvoid() {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('UPDATE purchase_order_suggested_buys SET void = 0 WHERE purchase_order_suggested_buy_id = :purchase_order_suggested_buy_id', [':purchase_order_suggested_buy_id' => $this->id()]);
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKSuggestedBuyException('Failed to void suggestion.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function create_po($purchase_order_suggested_buy_vendor_id) {
		$savepoint_id = prepared_query::transaction_begin();

		$user = ck_admin::login_instance(ck_admin::CONTEXT_ADMIN);

		try {
			$purchase_order_id = prepared_query::insert("INSERT INTO purchase_orders (purchase_order_number, status, creator, administrator_admin_id, owner_admin_id, vendor, shipping_method, terms, creation_date, notes, show_vendor_pn) SELECT 'UNDEFINED', 1, :admin_id, :admin_id, :admin_id, v.vendors_id, 1, v.company_payment_terms, NOW(), '', 1 FROM purchase_order_suggested_buy_vendors posbv JOIN vendors v ON posbv.vendors_id = v.vendors_id WHERE posbv.purchase_order_suggested_buy_vendor_id = :purchase_order_suggested_buy_vendor_id", [':admin_id' => $user->id(), ':purchase_order_suggested_buy_vendor_id' => $purchase_order_suggested_buy_vendor_id]);

			prepared_query::execute('UPDATE purchase_orders SET purchase_order_number = :purchase_order_id WHERE id = :purchase_order_id', [':purchase_order_id' => $purchase_order_id]);

			prepared_query::execute("INSERT INTO purchase_order_products (purchase_order_id, ipn_id, quantity, cost, description) SELECT :purchase_order_id, psc.stock_id, posbi.quantity, vtsi.vendors_price, psc.stock_description FROM purchase_order_suggested_buy_vendors posbv JOIN purchase_order_suggested_buy_ipns posbi ON posbv.purchase_order_suggested_buy_vendor_id = posbi.purchase_order_suggested_buy_vendor_id JOIN products_stock_control psc ON posbi.stock_id = psc.stock_id LEFT JOIN vendors_to_stock_item vtsi ON psc.stock_id = vtsi.stock_id AND posbv.vendors_id = vtsi.vendors_id WHERE posbv.purchase_order_suggested_buy_vendor_id = :purchase_order_suggested_buy_vendor_id", [':purchase_order_id' => $purchase_order_id, ':purchase_order_suggested_buy_vendor_id' => $purchase_order_suggested_buy_vendor_id]);

			prepared_query::execute('UPDATE products_stock_control psc JOIN purchase_order_suggested_buy_ipns posbi ON psc.stock_id = posbi.stock_id SET psc.on_order = psc.on_order + posbi.quantity WHERE posbi.purchase_order_suggested_buy_vendor_id = :purchase_order_suggested_buy_vendor_id', [':purchase_order_suggested_buy_vendor_id' => $purchase_order_suggested_buy_vendor_id]);

			prepared_query::execute('UPDATE purchase_order_suggested_buy_vendors posbv JOIN purchase_order_suggested_buy_ipns posbi ON posbv.purchase_order_suggested_buy_vendor_id = posbi.purchase_order_suggested_buy_vendor_id SET posbv.handled = 1, posbi.handled = 1 WHERE posbv.purchase_order_suggested_buy_vendor_id = :purchase_order_suggested_buy_vendor_id', [':purchase_order_suggested_buy_vendor_id' => $purchase_order_suggested_buy_vendor_id]);
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKSuggestedBuyException('Failed to create PO.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return $purchase_order_id;
	}

	public function create_rfq($purchase_order_suggested_buy_vendor_id) {
		$savepoint_id = prepared_query::transaction_begin();

		$user = ck_admin::login_instance(ck_admin::CONTEXT_ADMIN);

		try {
			$rfq_ids = [];

			$categories = prepared_query::fetch('SELECT DISTINCT psc.products_stock_control_category_id, pscc.name as category FROM purchase_order_suggested_buy_ipns posbi JOIN products_stock_control psc ON posbi.stock_id = psc.stock_id JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id WHERE posbi.purchase_order_suggested_buy_vendor_id = :purchase_order_suggested_buy_vendor_id', cardinality::SET, [':purchase_order_suggested_buy_vendor_id' => $purchase_order_suggested_buy_vendor_id]);

			foreach ($categories as $category) {
				$rfq_id = prepared_query::insert("INSERT INTO ck_rfqs (nickname, admin_id, request_type, subject_line, created_date) SELECT DISTINCT :nickname, :admin_id, 'WTB', 'WTB: ', NOW() FROM purchase_order_suggested_buy_vendors posbv WHERE posbv.purchase_order_suggested_buy_vendor_id = :purchase_order_suggested_buy_vendor_id", [':nickname' => $category['category'], ':admin_id' => $user->id(), ':purchase_order_suggested_buy_vendor_id' => $purchase_order_suggested_buy_vendor_id]);

				prepared_query::execute("INSERT INTO ck_rfq_products (rfq_id, stock_id, model_alias, conditions_id, quantity) SELECT :rfq_id, posbi.stock_id, IFNULL(p.products_model, psc.stock_name), psc.conditions, posbi.quantity FROM purchase_order_suggested_buy_ipns posbi JOIN products_stock_control psc ON posbi.stock_id = psc.stock_id AND psc.products_stock_control_category_id = :category_id LEFT JOIN products p ON psc.stock_id = p.stock_id AND p.products_status = 1 LEFT JOIN products p0 ON p.stock_id = p0.stock_id AND p0.products_status = 1 AND p.products_id > p0.products_id WHERE posbi.purchase_order_suggested_buy_vendor_id = :purchase_order_suggested_buy_vendor_id AND p0.products_id IS NULL", [':rfq_id' => $rfq_id, ':category_id' => $category['products_stock_control_category_id'], ':purchase_order_suggested_buy_vendor_id' => $purchase_order_suggested_buy_vendor_id]);

				$rfq_ids[] = $rfq_id;
			}

			prepared_query::execute('UPDATE purchase_order_suggested_buy_vendors posbv JOIN purchase_order_suggested_buy_ipns posbi ON posbv.purchase_order_suggested_buy_vendor_id = posbi.purchase_order_suggested_buy_vendor_id SET posbv.handled = 1, posbi.handled = 1 WHERE posbv.purchase_order_suggested_buy_vendor_id = :purchase_order_suggested_buy_vendor_id', [':purchase_order_suggested_buy_vendor_id' => $purchase_order_suggested_buy_vendor_id]);
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKSuggestedBuyException('Failed to create PO.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return $rfq_ids;
	}

	public function ignore($purchase_order_suggested_buy_vendor_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('UPDATE purchase_order_suggested_buy_vendors posbv JOIN purchase_order_suggested_buy_ipns posbi ON posbv.purchase_order_suggested_buy_vendor_id = posbi.purchase_order_suggested_buy_vendor_id SET posbv.handled = 1, posbi.handled = 1 WHERE posbv.purchase_order_suggested_buy_vendor_id = :purchase_order_suggested_buy_vendor_id', [':purchase_order_suggested_buy_vendor_id' => $purchase_order_suggested_buy_vendor_id]);
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKSuggestedBuyException('Failed to void suggestion.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	/*-------------------------------
	// other
	-------------------------------*/
}

class CKSuggestedBuyException extends CKMasterArchetypeException {
}
?>
