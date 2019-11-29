<?php
class ck_quote extends ck_archetype {

	protected static $skeleton_type = 'ck_quote_type';

	protected static $query_keys = [];

	protected static $queries = [
		'quote_header_count_proto' => [
			'proto_qry' => [
				'data_operation' => 'SELECT COUNT(DISTINCT cq.customer_quote_id)',

				'from' => 'FROM customer_quote cq LEFT JOIN admin a ON cq.admin_id = a.admin_id',

				'where' => 'WHERE' // will fail if we don't provide our own
			],
			'proto_opts' => [
				':customer_quote_id' => 'cq.customer_quote_id = :customer_quote_id',
				':status' => 'cq.status = :status',
				':admin_id' => 'a.admin_id = :admin_id',
				':admin_email' => 'a.admin_email_address = :admin_email',
				':customer_email' => 'ck.customer_email = :customer_email'
			],
			'cardinality' => cardinality::SINGLE,
			'stmt' => NULL
		],

		'quote_header_list_proto' => [
			'proto_qry' => [
				'data_operation' => 'SELECT DISTINCT cq.customer_quote_id, cq.customers_id, cq.customers_extra_logins_id, cq.account_manager_id, cq.sales_team_id, cq.status, a.admin_id, a.admin_email_address, CONCAT_WS(\' \', a.admin_firstname, a.admin_lastname) as admin_name, cq.admin_ext, cq.notes, cq.expiration_date, cq.url_hash, cq.customer_email, cq.email_signature, cq.send_from_admin, cq.created, cq.order_id, cq.locked, cq.released, cq.active, cq.prepared_by',

				'from' => 'FROM customer_quote cq LEFT JOIN admin a ON cq.admin_id = a.admin_id',

				'where' => 'WHERE' // will fail if we don't provide our own
			],
			'proto_opts' => [
				':customer_quote_id' => 'cq.customer_quote_id = :customer_quote_id',
				':customers_id' => 'cq.customers_id = :customers_id',
				':no_customers_id' => 'cq.customers_id IS NULL',
				':customers_extra_logins_id' => 'cq.customers_extra_logins_id = :customers_extra_logins_id',
				':no_customers_extra_logins_id' => 'cq.customers_extra_logins_id IS NULL',
				':status' => 'cq.status = :status',
				':admin_id' => 'a.admin_id = :admin_id',
				':admin_email' => 'a.admin_email_address = :admin_email',
				':customer_email' => 'cq.customer_email = :customer_email',
				':key' => 'cq.url_hash = :key',
				':released' => 'cq.released = :released',
				':active' => 'cq.active = :active'
			],
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'products' => [
			'qry' => 'SELECT customer_quote_product_id, product_id as products_id, parent_products_id, option_type, price, quantity, locked FROM customer_quote_products WHERE customer_quote_id = :customer_quote_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'history' => [
			'qry' => 'SELECT customer_quote_history_id, user_id as admin_id, type as action, timestamp as action_date FROM customer_quote_history WHERE customer_quote_id = :customer_quote_id ORDER BY timestamp ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		]
	];

	public function __construct($customer_quote_id, ck_quote_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($customer_quote_id);

		if (!$this->skeleton->built('customer_quote_id')) $this->skeleton->load('customer_quote_id', $customer_quote_id);

		self::register($customer_quote_id, $this->skeleton);
	}

	private static function primary_lookup() {
		if (empty(self::$query_keys['primary'])) {
			$query_adds = ['where' => '', 'order_by' => '', 'limit' => '', 'cardinality' => cardinality::ROW];
			$clauses = self::$queries['quote_header_list_proto']['proto_opts'];
			$query_adds['where'] = $clauses[':customer_quote_id'];

			self::$query_keys['primary'] = self::modify_query('quote_header_list_proto', $query_adds);
		}

		return self::$query_keys['primary'];
	}

	public function id() {
		return $this->skeleton->get('customer_quote_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function build_header() {
		$header = self::fetch(self::primary_lookup(), [':customer_quote_id' => $this->id()]);

		if (!empty($header)) {
			if (!($header['expiration_date'] instanceof DateTime)) $header['expiration_date'] = new DateTime($header['expiration_date']);
			if (!($header['created'] instanceof DateTime)) $header['created'] = new DateTime($header['created']);
		}
		else $header = [];

		$this->skeleton->load('header', $header);
	}

	private function build_customer() {
		$customers_id = $this->get_header('customers_id');

		if (!empty($customers_id)) $this->skeleton->load('customer', new ck_customer2($customers_id));
		else $this->skeleton->load('customer', NULL);
	}

	private function build_account_manager() {
		if (!empty($this->get_header('account_manager_id'))) $this->skeleton->load('account_manager', new ck_admin($this->get_header('account_manager_id')));
		else {
			if ($this->has_customer()) $this->skeleton->load('account_manager', $this->get_customer()->get_account_manager());
			else $this->skeleton->load('account_manager', NULL);
		}
	}

	private function build_sales_team() {
		if ($this->has_customer()) $this->skeleton->load('sales_team', $this->get_customer()->get_sales_team());
		else {
			if (!empty($this->get_header('sales_team_id'))) {
				$this->skeleton->load('sales_team', new ck_team($this->get_header('sales_team_id')));
			}
			else $this->skeleton->load('sales_team', NULL);
		}
	}

	private function build_products() {
		$products = self::fetch('products', [':customer_quote_id' => $this->id()]);

		foreach ($products as &$product) {
			$product['listing'] = new ck_product_listing($product['products_id']);
		}

		$this->skeleton->load('products', $products);
	}

	private function build_history() {
		$history = self::fetch('history', [':customer_quote_id' => $this->id()]);

		foreach ($history as &$rec) {
			$rec['action_date'] = self::DateTime($rec['action_date']);
		}

		$this->skeleton->load('history', $history);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	public function has_customer() {
		if (!$this->skeleton->built('customer')) $this->build_customer();
		return $this->skeleton->has('customer');
	}

	public function get_customer() {
		if (!$this->has_customer()) return NULL;
		return $this->skeleton->get('customer');
	}

	public function has_direct_account_manager() {
		return !empty($this->get_header('account_manager_id'));
	}

	public function has_account_manager() {
		if (!$this->skeleton->built('account_manager')) $this->build_account_manager();
		return $this->skeleton->has('account_manager');
	}

	public function get_account_manager() {
		if (!$this->has_account_manager()) return NULL;
		return $this->skeleton->get('account_manager');
	}

	public function has_sales_team() {
		if (!$this->skeleton->built('sales_team')) $this->build_sales_team();
		return $this->skeleton->has('sales_team');
	}

	public function get_sales_team() {
		if (!$this->has_sales_team()) return NULL;
		return $this->skeleton->get('sales_team');
	}

	public function get_contact_phone($separator='.') {
		$default_number = array_filter(preg_split('/[^0-9]+/', ck_admin::$toll_free_sales_phone));
		if (!$this->has_sales_team() || !$this->get_sales_team()->has('phone_number')) return implode($separator, $default_number);
		else {
			$team_number = array_filter(preg_split('/[^0-9]+/', $this->get_sales_team()->get_header('phone_number')));
			return implode($separator, $team_number);
		}
	}

	public function get_contact_local_phone($separator='.') {
		$default_number = array_filter(preg_split('/[^0-9]+/', ck_admin::$local_sales_phone));
		if (!$this->has_sales_team() || !$this->get_sales_team()->has('local_phone_number')) return implode($separator, $default_number);
		else {
			$team_number = array_filter(preg_split('/[^0-9]+/', $this->get_sales_team()->get_header('local_phone_number')));
			return implode($separator, $team_number);
		}
	}

	public function get_contact_email() {
		$default_email = ck_sales::$email;
		if (!$this->has_sales_team() || !$this->get_sales_team()->has('email_address')) return $default_email;
		else {
			$team_email = $this->get_sales_team()->get_header('email_address');
			return $team_email;
		}
	}

	public function has_products() {
		if (!$this->skeleton->built('products')) $this->build_products();
		return $this->skeleton->has('products');
	}

	public function get_products($products_id=NULL, $parent_products_id=NULL) {
		if (!$this->has_products()) return [];

		$products = $this->skeleton->get('products');

		if (empty($products_id) && empty($parent_products_id)) return $products;
		elseif (!empty($products_id)) {
			foreach ($products as $product) {
				if ($product['products_id'] != $products_id) continue;
				if ($product['parent_products_id'] != $parent_products_id) continue;
				return $product;
			}
		}
		elseif (!empty($parent_products_id)) {
			$prods = [];
			foreach ($products as $product) {
				if ($product['parent_products_id'] != $parent_products_id) continue;
				$prods[] = $product;
			}
			return $prods;
		}
	}

	public function get_key() {
		return $this->get_header('url_hash');
	}

	public function get_total() {
		$total = 0;

		foreach ($this->get_products() as $product) {
			$total += $product['price'] * $product['quantity'];
		}

		return $total;
	}

	public static function get_legacy_url_hash() {
		// this is not truly safe, but probably safe enough for our purposes and volume so I'll leave it for now
		return md5(uniqid(mt_rand(), TRUE));
	}

	public static function get_quote_by_key($key, $released=NULL, $active=NULL) {
		$query_details = ['where' => '', 'cardinality' => cardinality::ROW];

		$clauses = self::$queries['quote_header_list_proto']['proto_opts'];

		$wheres = [$clauses[':key']];
		$data = [':key' => $key];

		if ($released === TRUE || $released === 1) {
			$wheres[] = $clauses[':released'];
			$data[':released'] = 1;
		}
		elseif ($released === FALSE || $released === 0) {
			$wheres[] = $clauses[':released'];
			$data[':released'] = 0;
		}

		if ($active === TRUE || $active === 1) {
			$wheres[] = $clauses[':active'];
			$data[':active'] = 1;
		}
		elseif ($active === FALSE || $active === 0) {
			$wheres[] = $clauses[':active'];
			$data[':active'] = 0;
		}

		$query_details['where'] = implode(' AND ', $wheres);

		$qry = self::modify_query('quote_header_list_proto', $query_details);

		if ($header = self::fetch($qry, $data)) {
			if (!($header['expiration_date'] instanceof DateTime)) $header['expiration_date'] = new DateTime($header['expiration_date']);
			if (!($header['created'] instanceof DateTime)) $header['created'] = new DateTime($header['created']);

			$skeleton = self::get_record($header['customer_quote_id']); // if we've already instantiated it, well, oh well
			if (!$skeleton->built('header')) $skeleton->load('header', $header);

			$quote = new self($header['customer_quote_id'], $skeleton);

			if ($quote->expired() && ($active === TRUE || $active === 1)) return NULL;

			return $quote;
		}
		else return NULL;
	}

	public static function get_quotes_by_customer($customers_id, $customers_extra_logins_id=NULL, $released=NULL, $active=NULL) {
		$query_details = ['where' => '', 'order_by' => ''];

		$clauses = self::$queries['quote_header_list_proto']['proto_opts'];

		$wheres = [$clauses[':customers_id']];
		$data = [':customers_id' => $customers_id];
		if (!empty($customers_extra_logins_id)) {
			$wheres[] = $clauses[':customers_extra_logins_id'];
			$data[':customers_extra_logins_id'] = $customers_extra_logins_id;
		}
		else $wheres[] = $clauses[':no_customers_extra_logins_id'];

		if ($released === TRUE || $released === 1) {
			$wheres[] = $clauses[':released'];
			$data[':released'] = 1;
		}
		elseif ($released === FALSE || $released === 0) {
			$wheres[] = $clauses[':released'];
			$data[':released'] = 0;
		}

		if ($active === TRUE || $active === 1) {
			$wheres[] = $clauses[':active'];
			$data[':active'] = 1;
		}
		elseif ($active === FALSE || $active === 0) {
			$wheres[] = $clauses[':active'];
			$data[':active'] = 0;
		}

		$query_details['where'] = implode(' AND ', $wheres);
		$query_details['order_by'] = 'ORDER BY cq.expiration_date ASC';

		$qry = self::modify_query('quote_header_list_proto', $query_details);

		//self::debug_query($qry, $data);

		if ($headers = self::fetch($qry, $data)) {
			$quotes = [];
			foreach ($headers as $header) {
				if (!($header['expiration_date'] instanceof DateTime)) $header['expiration_date'] = new DateTime($header['expiration_date']);
				if (!($header['created'] instanceof DateTime)) $header['created'] = new DateTime($header['created']);

				$skeleton = self::get_record($header['customer_quote_id']); // if we've already instantiated it, well, oh well
				if (!$skeleton->built('header')) $skeleton->load('header', $header);

				$quote = new self($header['customer_quote_id'], $skeleton);

				if ($quote->expired() && ($active === TRUE || $active === 1)) continue;

				$quotes[] = $quote;
			}
			return $quotes;
		}
		else return [];
	}

	public static function get_unassociated_quotes_by_email($email, $released=NULL, $active=NULL) {
		$query_details = ['where' => '', 'order_by' => ''];

		$clauses = self::$queries['quote_header_list_proto']['proto_opts'];

		$wheres = [$clauses[':customer_email'], $clauses[':no_customers_id'], $clauses[':no_customers_extra_logins_id']];
		$data = [':customer_email' => $email];

		if ($released === TRUE || $released === 1) {
			$wheres[] = $clauses[':released'];
			$data[':released'] = 1;
		}
		elseif ($released === FALSE || $released === 0) {
			$wheres[] = $clauses[':released'];
			$data[':released'] = 0;
		}

		if ($active === TRUE || $active === 1) {
			$wheres[] = $clauses[':active'];
			$data[':active'] = 1;
		}
		elseif ($active === FALSE || $active === 0) {
			$wheres[] = $clauses[':active'];
			$data[':active'] = 0;
		}

		$query_details['where'] = implode(' AND ', $wheres);
		$query_details['order_by'] = 'cq.expiration_date ASC';

		$qry = self::modify_query('quote_header_list_proto', $query_details);

		if ($headers = self::fetch($qry, $data)) {
			$quotes = [];
			foreach ($headers as $header) {
				if (!($header['expiration_date'] instanceof DateTime)) $header['expiration_date'] = new DateTime($header['expiration_date']);
				if (!($header['created'] instanceof DateTime)) $header['created'] = new DateTime($header['created']);

				$skeleton = self::get_record($header['customer_quote_id']); // if we've already instantiated it, well, oh well
				if (!$skeleton->built('header')) $skeleton->load('header', $header);

				$quote = new self($header['customer_quote_id'], $skeleton);

				if ($quote->expired() && ($active === TRUE || $active === 1)) continue;

				$quotes[] = $quote;
			}
			return $quotes;
		}
		else return [];
	}

	public static function get_quotes_by_match($fields, $page=1, $page_size=50, $order_by=[]) {
		$query_adds = ['where' => '', 'order_by' => [], 'limit' => ''];

		$clauses = self::$queries['quote_header_list_proto']['proto_opts'];

		$wheres = [];
		foreach ($fields as $field => $value) {
			if (isset($clauses[$field])) $wheres[] = $clauses[$field];
		}
		$query_adds['where'] = implode(' AND ', $wheres);

		if (!empty($order_by)) {
			$sort_whitelist = ['cq.created' => 'DESC', 'cq.expiration_date' => 'ASC'];
			foreach ($order_by as $field => $direction) {
				if (!in_array(strtoupper($direction), ['ASC', 'DESC'])) continue;
				if (empty($sort_whitelist[$field])) continue;
				$query_adds['order_by'][] = $field.' '.$direction;
			}
		}

		if (!empty($query_adds['order_by'])) $query_adds['order_by'] = 'ORDER BY '.implode(', ', $query_adds['order_by']);
		else $query_adds['order_by'] = 'ORDER BY c.customers_id DESC';

		self::$matched_total = self::fetch(self::modify_query('quote_header_count_proto', $query_adds), $fields);

		if (!is_numeric($page)) $page = 1;
		if (!is_numeric($page_size)) $page_size = 50;
		$page--; // limits are zero based

		$query_adds['limit'] = 'LIMIT '.($page*$page_size).', '.$page_size;

		// modify_query is necessary because we can't parameterize limits or ordering
		if ($headers = self::fetch(self::modify_query('quote_header_list_proto', $query_adds), $fields)) {
			$quotes = [];
			foreach ($headers as $header) {
				if (!($header['expiration_date'] instanceof DateTime)) $header['expiration_date'] = new DateTime($header['expiration_date']);
				if (!($header['created'] instanceof DateTime)) $header['created'] = new DateTime($header['created']);

				$skeleton = self::get_record($header['customer_quote_id']); // if we've already instantiated it, well, oh well
				if (!$skeleton->built('header')) $skeleton->load('header', $header);

				$quotes[] = new self($header['customer_quote_id'], $skeleton);
			}
			return $quotes;
		}
		else return [];
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public function change_account_manager($admin_id) {
		$savepoint = self::transaction_begin();

		try {
			if (!empty($admin_id)) {
				$account_manager = new ck_admin($admin_id);

				if (!$account_manager->is('account_manager')) throw new CKCustomerException('Cannot set account manager to admin who is not set up to be an account manager.');

				self::query_execute('UPDATE customer_quote SET account_manager_id = :account_manager_id, sales_team_id = :sales_team_id WHERE customer_quote_id = :customer_quote_id', cardinality::NONE, [':account_manager_id' => $account_manager->id(), ':sales_team_id' => $account_manager->has_sales_team()?$account_manager->get_sales_team()['team']->id():NULL, ':customer_quote_id' => $this->id()]);
			}
			else self::query_execute('UPDATE customer_quote SET account_manager_id = NULL WHERE customer_quote_id = :customer_quote_id', cardinality::NONE, [':customer_quote_id' => $this->id()]);

			self::transaction_commit($savepoint);
		}
		catch (CKCustomerException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKCustomerException('Failed to change account manager: '.$e->getMessage());
		}
	}

	public function change_sales_team($team_id) {
		$savepoint = self::transaction_begin();

		try {
			if (!empty($team_id)) {
				$sales_team = new ck_team($team_id);

				if (!$sales_team->is('sales_team')) throw new CKCustomerException('Cannot set sales team to team that is not set up to be a sales team.');

				if ($this->has_account_manager()) throw new CKCustomerException('Cannot set sales team separately from the account manager.');
			}

			self::query_execute('UPDATE customer_quote SET sales_team_id = :sales_team_id WHERE customer_quote_id = :customer_quote_id', cardinality::NONE, [':sales_team_id' => $team_id, ':customer_quote_id' => $this->id()]);

			self::transaction_commit($savepoint);
		}
		catch (CKCustomerException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKCustomerException('Failed to change sales team: '.$e->getMessage());
		}
	}

	public static function create_quote($data=[]) {
		// handle defaults, if we didn't pass the field in
		if (empty($data)) $data = [];
		if (!CK\fn::is_key_set($data, ':customers_id')) {
			$data[':customers_id'] = NULL;
			if (!empty($_SESSION['customer_id'])) $data[':customers_id'] = $_SESSION['customer_id'];
		}
		if (!CK\fn::is_key_set($data, ':customers_extra_logins_id')) {
			$data[':customers_extra_logins_id'] = NULL;
			if (!empty($_SESSION['customer_extra_login_id'])) $data[':customers_extra_logins_id'] = $_SESSION['customer_extra_logins_id'];
		}
		if ($data[':customers_extra_logins_id'] == 0) $data[':customers_extra_logins_id'] = NULL;
		if (!CK\fn::is_key_set($data, ':admin_id')) {
			if (!empty($_SESSION['login_id'])) $data[':admin_id'] = $_SESSION['login_id'];
			elseif (!empty($_SESSION['admin_login_id'])) $data[':admin_id'] = $_SESSION['admin_login_id'];
			else $data[':admin_id'] = 0;
		}
		if (!CK\fn::is_key_set($data, ':expiration_date')) {
			$default_expiration = new DateTime();
			$default_expiration->add(new DateInterval('P7D'));
			$data[':expiration_date'] = $default_expiration->format('Y-m-d H:i:s');
		}
		if (!CK\fn::is_key_set($data, ':url_hash')) $data[':url_hash'] = self::get_legacy_url_hash();
		if (!CK\fn::is_key_set($data, ':customer_email')) {
			$data[':customer_email'] = '';
			if (!empty($_SESSION['cart']) && $_SESSION['cart']->has_customer()) $data[':customer_email'] = $_SESSION['cart']->get_customer()->get_header('email_address');
		}

		// prepared_by defaults to NULL -- if it's null it was prepared by sales
		if (!CK\fn::is_key_set($data, ':prepared_by')) $data[':prepared_by'] = NULL;

		self::query_execute('INSERT INTO customer_quote (customers_id, customers_extra_logins_id, status, admin_id, expiration_date, url_hash, customer_email, created, locked, prepared_by) VALUES (:customers_id, :customers_extra_logins_id, 0, :admin_id, :expiration_date, :url_hash, :customer_email, NOW(), 1, :prepared_by)', cardinality::NONE, $data);

		$quote = new self(self::fetch_insert_id());
		$quote->add_history_record('created');

		return $quote;
	}

	public static function create_quote_copy($customer_quote_id) {
		$old_quote = new self($customer_quote_id);

		self::query_execute('INSERT INTO customer_quote (customers_id, customers_extra_logins_id, status, admin_id, expiration_date, url_hash, customer_email, created, locked, prepared_by) SELECT customers_id, customers_extra_logins_id, 0, :admin_id, CASE WHEN expiration_date < NOW() THEN DATE_ADD(NOW(), INTERVAL 7 DAY) ELSE expiration_date END, :url_hash, customer_email, NOW(), locked FROM customer_quote WHERE customer_quote_id = :customer_quote_id', cardinality::NONE, [':admin_id' => $_SESSION['login_id'], ':url_hash' => self::get_legacy_url_hash(), ':customer_quote_id' => $old_quote->id(), ':prepared_by']);

		$quote = new self(self::fetch_insert_id());
		$quote->add_history_record('created');

		self::query_execute('INSERT INTO customer_quote_products (customer_quote_id, product_id, parent_products_id, option_type, price, quantity, locked) SELECT :new_customer_quote_id, product_id, parent_products_id, option_type, price, quantity, locked FROM customer_quote_products WHERE customer_quote_id = :old_customer_quote_id', cardinality::NONE, [':new_customer_quote_id' => $quote->id(), ':old_customer_quote_id' => $customer_quote_id]);

		if ($old_quote->expired()) {
			foreach ($quote->get_products() as $product) {
				// refresh pricing
				$quote->update_product($product['listing'], $product['quantity'], NULL, TRUE, $product['parent_products_id'], $product['option_type']);
			}
		}

		return $quote;
	}

	public function update_quote($data=[]) {
		if (empty($data)) return;

		$fields = [];
		foreach ($data as $param => $value) {
			$field = ltrim($param, ':');
			if (!self::validate_query_identifier($field)) throw new CKQuoteException($field.' is not a valid query identifier');
			$fields[] = $field.' = '.$param; 
		}
		$fields = implode(', ', $fields);

		$data[':customer_quote_id'] = $this->id();

		self::query_execute('UPDATE customer_quote SET '.$fields.' WHERE customer_quote_id = :customer_quote_id', cardinality::NONE, $data);

		$this->add_history_record('updated');

		$this->skeleton->rebuild('header');
	}

	public function release() {
		$this->update_quote([':released' => 1]);
		$this->add_history_record('released');
	}

	public function lock_for_work() {
		$this->update_quote([':released' => 0]);
		$this->add_history_record('locked');
	}

	/*public function associate_to_account() {
		if (!empty($this->get_header('customers_id')) || empty($this->get_header('customer_email'))) return;

		$customer = ck_customer2::get_customer_by_email($this->get_header('customer_email'));
		if (empty($customer)) return;

		$data = [':customers_id' => $customer->id()];
		if ($this->get_header('customer_email') != $customer->get_header('email_address')) {
			$el = $customer->get_extra_logins($this->get_header('customer_email'));
			$data[':customers_extra_logins_id'] = $el['customers_extra_logins_id'];
		}

		$this->update_quote($data);
		$this->add_history_record('associated');
	}*/

	public function associate_to_account(ck_cart $cart, $force=FALSE) {
		if (!$cart->has_customer()) return TRUE; // if we're not logged in, nothing to associate

		$header = $this->get_header();

		$update = [];

		if (!empty($header['customers_id'])) {
			// if this quote is already associated with someone else's account, we can't break that association here
			if ($header['customers_id'] != $cart->get_header('customers_id')) {
				if (!$force) return FALSE;
				// if we're forcing it, that means we've deemed it OK to reassign this customer
				else $update[':customers_id'] = $cart->get_header('customers_id');
			}
		}
		else $update[':customers_id'] = $cart->get_header('customers_id');

		// it's OK to pass a quote between extra logins on the account
		if (!empty($cart->get_header('customers_extra_logins_id'))) $update[':customers_extra_logins_id'] = $cart->get_header('customers_extra_logins_id');

		if (!empty($update)) {
			$this->update_quote($update);
			$this->add_history_record('associated');
		}

		return TRUE;
	}

	public function add_history_record($action, $user_id=NULL) {
		if (empty($user_id)) $user_id = @$_SESSION['login_id'];
		self::query_execute("INSERT INTO customer_quote_history (customer_quote_id, user_id, type, data) VALUES (:customer_quote_id, :user_id, :action, '')", cardinality::NONE, [':customer_quote_id' => $this->id(), ':user_id' => $user_id, ':action' => $action]);
		$this->skeleton->rebuild('history');
	}

	public function close_won($orders_id=NULL) {
		$user_id = !empty($_SESSION['customer_id'])?$_SESSION['customer_id']:$_SESSION['login_id'];
		$this->update_quote([':status' => 4, ':order_id' => $orders_id, ':active' => 0]);
		$this->add_history_record('ordered', $user_id);
	}

	public function expired() {
		if (!$this->is('active')) return TRUE;

		if ($this->get_header('expiration_date') <= self::NOW()) {
			$this->update_quote([':active' => 0]);
			return TRUE;
		}

		return FALSE;
	}

	// recursive add to ensure all levels of included items get updated
	public function update_product($product, $qty, $unit_price=NULL, $is_total=TRUE, $parent_products_id=NULL, $option_type=NULL, $allow_discontinued=FALSE) {
		if (is_numeric($product)) $product = new ck_product_listing($product);

		if (!($product instanceof ck_product_listing) || !$product->found()) throw new CKQuoteException('Failed updating quote product; invalid product.');

		if (!$product->is_viewable()) return;

		// force this, and any subsequent recursive calls, to to be the total #

		$quote_product = $this->get_products($product->id(), $parent_products_id);

		if (!$is_total) {
			$qty += $quote_product['quantity'];
			$is_total = TRUE; // just make it explicit what we're doing
		}

		$inventory = $product->get_inventory();
		$prices = $product->get_price();

		// if the product is discontinued, never add more than the available qty
		if (!$allow_discontinued && $product->is('discontinued')) $qty = min($qty, $inventory['available']);

		if ($qty <= 0) {
			// we're removing this item from the quote
			// because of how we've set this up, we don't need to do a recursive remove, we can just do it all right here
			self::query_execute('DELETE FROM customer_quote_products WHERE customer_quote_id = :quote_id AND (product_id = :products_id OR parent_products_id = :products_id)', cardinality::NONE, [':quote_id' => $this->id(), ':products_id' => $product->id()]);
			$this->skeleton->rebuild('products');
			return;
		}

		if (empty($option_type)) $option_type = ck_cart::$option_types['NONE'];

		if (empty($unit_price)) {
			$unit_price = $prices['display'];
			if ($option_type == ck_cart::$option_types['INCLUDED']) $unit_price = 0;
			elseif (in_array($option_type, [ck_cart::$option_types['OPTIONAL'], ck_cart::$option_types['RECOMMENDED']])) {
				if (($parent = $this->get_products($parent_products_id)) && ($options = $parent['listing']->get_options('extra'))) {
					foreach ($options as $option) {
						if ($option['products_id'] != $product->id()) continue;
						$unit_price = $option['price'];
					}
				}
			}
		}

		// just insert, or take over, the qty
		if (!empty($quote_product)) {
			self::query_execute('UPDATE customer_quote_products SET quantity = :quantity, price = :unit_price, option_type = :option_type WHERE customer_quote_id = :quote_id AND product_id = :products_id AND ((:parent_products_id IS NULL AND parent_products_id IS NULL) OR parent_products_id = :parent_products_id)', cardinality::NONE, [':quote_id' => $this->id(), ':products_id' => $product->id(), ':quantity' => $qty, ':unit_price' => $unit_price, ':option_type' => $option_type, ':parent_products_id' => $parent_products_id]);

			$return_quote_product_id = $quote_product['customer_quote_product_id'];
		}
		else {
			self::query_execute('INSERT INTO customer_quote_products (customer_quote_id, product_id, quantity, price, option_type, parent_products_id) VALUES (:quote_id, :products_id, :quantity, :unit_price, :option_type, :parent_products_id) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity), price=VALUES(price), option_type=VALUES(option_type), parent_products_id=VALUES(parent_products_id)', cardinality::NONE, [':quote_id' => $this->id(), ':products_id' => $product->id(), ':quantity' => $qty, ':unit_price' => $unit_price, ':option_type' => $option_type, ':parent_products_id' => $parent_products_id]);

			$return_quote_product_id = self::fetch_insert_id();
		}

		// we manage included options along with parent items
		if ($options = $product->get_options('included')) {
			if (empty($parent_products_id)) $parent_products_id = $product->id();
			foreach ($options as $option) {
				// we need to deal with a potentially different qty if this is a bundle
				$option_qty = $qty;
				if ($product->is('is_bundle')) $option_qty *= $option['bundle_quantity'];

				// at this point, $qty is the total qty, $is_total is TRUE, $parent_products_id is either the current product or the top level product
				$this->update_product($option['listing'], $option_qty, 0, $is_total, $parent_products_id, ck_cart::$option_types['INCLUDED']);
			}
		}

		$this->skeleton->rebuild('products');

		return $return_quote_product_id;
	}

	public function update_quote_line($quote_product_id, $qty=NULL, $unit_price=NULL) {
		if (is_numeric($qty) && $qty <= 0) self::query_execute('DELETE cqp FROM customer_quote_products cqp LEFT JOIN customer_quote_products cqp1 ON cqp.customer_quote_id = cqp1.customer_quote_id AND cqp.option_type > :option_type AND cqp.parent_products_id = cqp1.product_id WHERE cqp.customer_quote_product_id = :customer_quote_product_id OR cqp1.customer_quote_product_id = :customer_quote_product_id', cardinality::NONE, [':customer_quote_product_id' => $quote_product_id, ':option_type' => ck_cart::$option_types['NONE']]);
		else {
			$params = [':customer_quote_product_id' => $quote_product_id];
			if (is_numeric($unit_price)) $params[':unit_price'] = $unit_price;
			if (is_numeric($qty)) $params[':quantity'] = $qty;

			if (is_numeric($unit_price) && is_numeric($qty)) self::query_execute('UPDATE customer_quote_products SET quantity = :quantity, price = :unit_price WHERE customer_quote_product_id = :customer_quote_product_id', cardinality::NONE, $params);
			elseif (is_numeric($qty)) self::query_execute('UPDATE customer_quote_products SET quantity = :quantity WHERE customer_quote_product_id = :customer_quote_product_id', cardinality::NONE, $params);
			elseif (is_numeric($unit_price)) self::query_execute('UPDATE customer_quote_products SET price = :unit_price WHERE customer_quote_product_id = :customer_quote_product_id', cardinality::NONE, $params);
		}

		$this->skeleton->rebuild('products');
	}

	public static function expire_quotes() {
		selse::query_execute('UPDATE customer_quote set active = 0 WHERE expiration_date <= NOW()');
	}

	public function update_prepared_by($admin) {
		prepared_query::execute('UPDATE customer_quote SET prepared_by = :prepared_by WHERE customer_quote_id = :customer_quote_id', [':prepared_by' => $admin->id(), ':customer_quote_id' => $this->id()]);
		return TRUE;
	}
}

class CKQuoteException extends Exception {
}
?>