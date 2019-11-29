<?php
class ck_promo extends ck_archetype {

	protected static $skeleton_type = 'ck_promo_type';

	public static $dev_rules = [
		[
			'name' => 'Premium Fiber Promo',
			'method' => 'premium_fiber_promo_rule'
		]
	];

	public static $timeframes = ['lifetime', 'year', 'month', 'week', 'day'];

	protected static $queries = [
		'header' => [
			'qry' => 'SELECT cp.promo_id, cp.promo_title, cp.products_id, cp.creator_id, cp.created_at, cp.updated_at, cp.active, cp.archive FROM ck_promos cp WHERE cp.promo_id = :promo_id',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],
		'rules' => [
			'qry' => 'SELECT promo_rule_id, rule_met, quantity, timeframe, measure, dev_rule, creator_id, created_at, updated_at, archive FROM ck_promo_rules WHERE promo_id = :promo_id AND archive = 0',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		]
	];

	public function __construct($promo_id, ck_promo_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($promo_id);

		if (!$this->skeleton->built('promo_id')) $this->skeleton->load('promo_id', $promo_id);
		if ($this->skeleton->built('header')) $this->normalize_header();

		self::register($promo_id, $this->skeleton);
	}

	public static function create(Array $data) {
		if (empty($data['products_id'])) return FALSE;
		if (empty($data['promo_title'])) return FALSE;

		$savepoint_id = prepared_query::transaction_begin();

		try {

			$data['creator_id'] = ck_admin::current_id();
			$data['created_at'] = (new DateTime('now'))->format('Y-m-d h:m:s');
			$data['active'] = 0;

			$params = new ezparams($data);
			$promo_id = prepared_query::insert('INSERT INTO ck_promos ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', CK\fn::parameterize($data));

			$promo = new self($promo_id);

			prepared_query::transaction_commit($savepoint_id);
			return $promo;
		}
		catch (CKPromoException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKPromoException('Failed to create promo: '.$e->getMessage());
		}
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) $header = self::fetch('header', [$this->id()]);
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		if (!empty($header)) {
			$date_fields = ['created_at'];
			foreach ($date_fields as $field) {
				if (!self::date_is_empty($header[$field])) $header[$field] = self::DateTime($header[$field]);
				else $header[$field] = NULL;
			}
		}

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$this->skeleton->load('header', self::fetch('header', [':promo_id' => $this->id()]));
		$this->normalize_header();
	}

	private function build_rules() {
		$rules = self::fetch('rules', [':promo_id' => $this->id()]);
		$this->skeleton->load('rules', $rules);
	}

	private function build_product() {
		$this->skeleton->load('product', new ck_product_listing($this->get_header('products_id')));
	}

	private function build_creator() {
		$this->skeleton->load('creator', new ck_admin($this->get_header('creator_id')));
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function id() {
		return $this->skeleton->get('promo_id');
	}

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header')[$key];
	}

	public function has_product() {
		if (!$this->skeleton->built('product')) $this->build_product();
		return $this->skeleton->has('product');
	}

	public function get_product() {
		if (!$this->has_product()) return [];
		return $this->skeleton->get('product');
	}

	public function has_creator() {
		if (!$this->skeleton->built('creator')) $this->build_creator();
		return $this->skeleton->has('creator');
	}

	public function get_creator() {
		if (!$this->has_creator()) return [];
		return $this->skeleton->get('creator');
	}

	public function has_rules() {
		if (!$this->skeleton->built('rules')) $this->build_rules();
		return $this->skeleton->has('rules');
	}

	public function get_rules($key=NULL) {
		if (!$this->has_rules()) return [];
		if (!empty($key)) {
			if (is_int($key)) {
				foreach ($this->skeleton->get('rules') as $rule) if ($rule['promo_rule_id'] == $key) return $rule;
				return [];
			}
			elseif ($key == 'active dev_rules') {
				$results = [];
				foreach ($this->skeleton->get('rules') as $rule) {
					if ($rule['archive'] == 0 && !empty($rule['dev_rule'])) $results[] = $rule;
				}
				return $results;
			}
			elseif ($key == 'active') {
				$active = [];
				foreach ($this->skeleton->get('rules') as $rule) if ($rule['archive'] == 0) $active[] = $rule;
				return $active;
			}
		}
		return $this->skeleton->get('rules');
	}

	public function has_dev_rules() {
		if (!$this->has_rules()) return FALSE;
		foreach ($this->get_rules() as $rule) if ($rule['dev_rule']) return TRUE;
		return FALSE;
	}

	public function get_dev_rules() {
		if (!$this->has_dev_rules()) return [];
		$dev_rules = [];
		foreach ($this->get_rules() as $rule) if ($rule['dev_rule']) $dev_rules[] = $rule;
		return $dev_rules;
	}

	public static function all($key=NULL) {
		$promos = [];

		$promo_ids = self::query_fetch('SELECT promo_id FROM ck_promos WHERE archive = 0 ORDER BY promo_id DESC', cardinality::SET, []);

		if (!empty($key)) {
			if ($key == 'actionable') {
				foreach ($promo_ids as $promo) {
					$promo_temp = new self($promo['promo_id']);
					if ($promo_temp->has_rules() && $promo_temp->get_product()->get_inventory('available') >= 0) $promos[] = $promo_temp;
				}
				return $promos;
			}
		}

		foreach ($promo_ids as $promo) $promos[] = new self($promo['promo_id']);

		return $promos;
	}

	public function rule_exists(Array $data) {
		$params = new ezparams($data);

		$exists = self::query_fetch('SELECT EXISTS(SELECT 1 FROM ck_promo_rules WHERE archive = 0 AND '.$params->where_cols.' LIMIT 1)', cardinality::SINGLE, $params->query_vals());

		if ($exists) return TRUE;
		return FALSE;
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public function update_rule($data) {
		$savepoint = self::transaction_begin();

		try {
			if (!$data['promo_rule_id']) return FALSE;
			$rule_id = $data['promo_rule_id'];
			$params = new ezparams($data);
			self::query_execute('UPDATE ck_promo_rules SET '.$params->update_cols(TRUE).' WHERE promo_id = :promo_id AND promo_rule_id = :rule_id', cardinality::NONE, $params->query_vals(['promo_id' => $this->id(), 'rule_id' => $rule_id], TRUE));
			self::transaction_commit($savepoint);
		}
		catch (CKPromoException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKPromoException('Failed to update promo rule: '.$e->getMessage());
		}

		$this->skeleton->rebuild('rules');

		return TRUE;
	}

	public function create_rule($data) {
		if ($this->rule_exists($data)) return FALSE;

		$savepoint = self::transaction_begin();

		try {

			$data['creator_id'] = ck_admin::current_id();

			$params = new ezparams($data);
			self::query_execute('INSERT INTO ck_promo_rules ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, $params->query_vals([], TRUE));
			$rule_id = self::fetch_insert_id();

			self::transaction_commit($savepoint);

			return $rule_id;
		}
		catch (CKPromoException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKPromoException('Failed to create new promo rule: '.$e->getMessage());
		}
	}

	public function archive_rule($rule_id) {
		$savepoint = self::transaction_begin();

		try {
			self::query_execute('UPDATE ck_promo_rules SET archive = 1 WHERE promo_id = :promo_id AND promo_rule_id = :rule_id', cardinality::NONE, ['promo_id' => $this->id(), 'rule_id' => $rule_id]);
			self::transaction_commit($savepoint);
		}
		catch (CKPromoException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKPromoException('Failed to archive promo rule: '.$e->getMessage());
		}

		$this->skeleton->rebuild('rules');

		return TRUE;
	}

	public function toggle_active_state() {
		$savepoint = self::transaction_begin();

		try {
			$new_state = 0;

			if ($this->get_header('active') == 0) $new_state = 1;
			$this->update(['active' => $new_state]);

			self::transaction_commit($savepoint);
		}
		catch (CKPromoException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKPromoException('Failed to toggle promo active state: '.$e->getMessage());
		}

		$this->skeleton->rebuild('header');

		return $this->get_header('active');
	}

	public function update(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$data['updated_at'] = (new DateTime('now'))->format('Y-m-d h:m:s');
			$params = new ezparams($data);
			prepared_query::execute('UPDATE ck_promos SET '.$params->update_cols(TRUE).' WHERE promo_id = :promo_id', $params->query_vals(['promo_id' => $this->id()], TRUE));

			$this->skeleton->rebuild();

			prepared_query::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (CKPromoException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKPromoException('Error updating promo: '.$e->getMessage());
		}
	}

	public function increment_rule_counter($rule_id) {
		$savepoint_id = prepared_query::transaction_begin();
		try {
			prepared_query::execute('UPDATE ck_promo_rules SET rule_met = (rule_met + 1) WHERE promo_rule_id = :promo_rule_id', [':promo_rule_id' => $rule_id]);
		}
		catch (CKPromoException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKPromoException('Error incrementing rule counter: '.$e->getMessage());
		}
	}

	public function decrement_rule_counter($rule_id) {
		$savepoint_id = prepared_query::transaction_begin();
		try {
			prepared_query::execute('UPDATE ck_promo_rules SET rule_met = (rule_met - 1) WHERE promo_rule_id = :promo_rule_id', [':promo_rule_id' => $rule_id]);
		}
		catch (CKPromoException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKPromoException('Error decrementing rule counter: '.$e->getMessage());
		}
	}

	public function attach_customer_promo($rule_id, ck_sales_order $sales_order) {
		$customer = $sales_order->get_customer();
		$savepoint_id = prepared_query::transaction_begin();
		try {
			prepared_query::execute('INSERT INTO ck_customer_promos (promo_id, promo_rule_id, customers_id, orders_id) VALUES (:promo_id, :promo_rule_id, :customers_id, :orders_id)', [':promo_id' => $this->id(), ':promo_rule_id' => $rule_id, ':customers_id' => $customer->id(), ':orders_id' => $sales_order->id()]);
		}
		catch (CKPromoException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKPromoException('Error attaching promo and customer: '.$e->getMessage());
		}
	}

	public function detach_customer_promo($rule_id, ck_sales_order $sales_order) {
		$customer = $sales_order->get_customer();
		$savepoint_id = prepared_query::transaction_begin();
		try {
			prepared_query::execute('DELETE FROM ck_customer_promos WHERE promo_id = :promo_id AND rule_id = :rule_id AND customer_id = :customers_id AND orders_id = :orders_id', [':promo_id' => $this->id(), ':rule_id' => $rule_id, ':customers_id' => $customer->id(), ':orders_id' => $sales_order->id()]);
		}
		catch (CKPromoException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKPromoException('Error detaching promo and customer: '.$e->getMessage());
		}
	}

	public function delete() {
		$savepoint = self::transaction_begin();

		try {
			self::query_execute('UPDATE ck_promos SET archive = 1 WHERE promo_id = :promo_id', cardinality::NONE, ['promo_id' => $this->id()]);
			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (CKPromoException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKPromoException('Failed to archive promo: '.$e->getMessage());
		}
		return FALSE;
	}

	/*-------------------------------
	// other
	-------------------------------*/

	// we run promos on order creation
	public static function run_promos(ck_sales_order $sales_order) {
		if ($sales_order->get_header('dropship') == 1 || $sales_order->is_marketplace()) return FALSE;

		$qualified_promo_product = [];

		foreach (self::all('actionable') as $promo) {
			// currently dev_rules are all we are using, so no need to search for others, I'll just explicity get exactly what i need -- active dev rules
			foreach ($promo->get_rules('active dev_rules') as $rule) {
				if ($promo->{$rule['dev_rule']}($sales_order)) {
					$qualified_promo_product[] = ['products_id' => $promo->get_product()->id(), 'products_quantity' => 1, 'option_type' => 0, 'products_price' => 0, 'final_price' => 0, 'price_reason' => 9];

					$promo->increment_rule_counter($rule['promo_rule_id']);
					$promo->attach_customer_promo($rule['promo_rule_id'], $sales_order);
				}
			}
		}
		if (!empty($qualified_promo_product)) $sales_order->create_products($qualified_promo_product);
	}

	// we rerun promos when an order is mutated in anyway
	public static function rerun_promos(ck_sales_order $sales_order) {
		$order_promos = self::get_order_promos($sales_order);

		foreach ($order_promos as $promo) {
			foreach ($promo->get_rules('active dev_rules') as $rule) {
				if (!$promo->{$dev_rule['dev_rule']}($sales_order)) {
					foreach ($sales_order->get_products() as $order_product) {
						if ($order_product['price_reason'] == 9 && $order_product['products_id'] == $promo->get_product->id()) {
							$sales_order->delete_product($order_product['orders_products_id'], FALSE);
						}
					}

					$promo->decrement_rule_counter($rule['promo_rule_id']);
					$promo->detach_customer_promo($rule['promo_rule_id'], $sales_order);
				}
			}
		}
	}

	/*-------------------------------
	// dev rules
	-------------------------------*/

	public function premium_fiber_promo_rule(ck_sales_order $sales_order) {
		$category_id = 1360; // Premium Corning Fiber
		
		// is the order a domestic order?
		if ($sales_order->get_shipping_address('country') != 'United States') return FALSE;

		$customer = $sales_order->get_customer();

		// customer received this promo?
		$received_promo_check = self::query_fetch('SELECT EXISTS(SELECT 1 FROM ck_customer_promos WHERE promo_id = :promo_id AND customers_id = :customers_id)', cardinality::SINGLE, [':promo_id' => $this->id(), 'customers_id' => $customer->id()]);
		if ($received_promo_check) return FALSE;

		// customer received premium fiber before?
		$purchased_category_check = self::query_fetch('SELECT EXISTS(SELECT 1 FROM orders o LEFT JOIN orders_products op ON o.orders_id = op.orders_id LEFT JOIN products p ON op.products_id = p.products_id LEFT JOIN products_to_categories ptc ON p.products_id = ptc.products_id WHERE o.customers_id = :customers_id AND ptc.categories_id = :categories_id LIMIT 1)', cardinality::SINGLE, [':customers_id' => $customer->id(), ':categories_id' => $category_id]);
		if ($purchased_category_check) return FALSE;

		return TRUE;
	}

}
class CKPromoException extends Exception {
}
?>
