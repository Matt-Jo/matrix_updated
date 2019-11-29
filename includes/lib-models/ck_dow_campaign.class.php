<?php
class ck_dow_campaign extends ck_archetype {

	protected static $skeleton_type = 'ck_dow_campaign_type';

	protected static $queries = [
		'dow_campaign_header_proto' => [
			'proto_qry' => [
				'data_operation' => 'SELECT',

				'from' => 'FROM ck_dow_campaigns',

				//'where' => 'WHERE' // will *not* fail if we don't provide our own

				'order_by' => 'ORDER BY' // we provide a default below, if it's not otherwise provided
			],
			'proto_opts' => [
				':dow_campaign_id' => 'dow_campaign_id = :dow_campaign_id',
				':draft' => 'draft = :draft',
				':active' => 'active = :active', // we use the provided data so we don't have to explicitly remove it, it should always be 1
				':first_date' => 'DATE(first_date) = :first_date'
			],
			'proto_defaults' => [
				'data_operation' => 'dow_campaign_id, name, first_date, last_date, simultaneous_products, deal_length_days, deal_length_hours, deal_length_minutes, draft, active, created_date',
				'order_by' => 'first_date DESC',
				'limit' => 'LIMIT 1'
			],
			'proto_count_clause' => 'COUNT(dow_campaign_id)',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		'deals' => [
			'qry' => 'SELECT dow_deal_id, products_id, deal_start, deal_end, custom_description, custom_legalese, create_specials_price, draft, active, created_date FROM ck_dow_deals WHERE dow_campaign_id = :dow_campaign_id OR dow_deal_id = :dow_deal_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'product_recommendations' => [
			'qry' => 'SELECT dow_deal_recommendation_id, products_id, custom_name, sort_order, created_date FROM ck_dow_deal_recommendations WHERE dow_deal_id = :dow_deal_id ORDER BY sort_order ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'campaign_options' => [
			'qry' => 'SELECT dcot.dow_campaign_option_type_id, dcot.option_key, dcov.option_value FROM ck_dow_campaign_options dco JOIN ck_dow_campaign_option_types dcot ON dco.dow_campaign_option_type_id = dcot.dow_campaign_option_type_id JOIN ck_dow_campaign_option_values dcov ON dco.dow_campaign_option_value_id = dcov.dow_campaign_option_value_id WHERE dco.dow_campaign_id = :dow_campaign_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'update_campaign_proto' => [
			'proto_qry' => [
				'data_operation' => 'UPDATE ck_dow_campaigns',

				'set' => 'SET', // will fail if we don't provide our own

				'where' => 'WHERE', // will fail if we don't provide our own
			],
			'proto_opts' => [
				':name' => 'name = :name',
				':first_date' => 'first_date = :first_date',
				':last_date' => 'last_date = :last_date',
				':simultaneous_products' => 'simultaneous_products = :simultaneous_products',
				':deal_length_days' => 'deal_length_days = :deal_length_days',
				':deal_length_hours' => 'deal_length_hours = :deal_length_hours',
				':deal_length_minutes' => 'deal_length_minutes = :deal_length_minutes',
				':draft' => 'draft = :draft',
				':active' => 'active = :active',
				':dow_campaign_id' => 'dow_campaign_id = :dow_campaign_id',
				':not_dow_campaign_id' => 'dow_campaign_id != :not_dow_campaign_id'
			],
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		'update_deal_proto' => [
			'proto_qry' => [
				'data_operation' => 'UPDATE ck_dow_deals',

				'set' => 'SET', // will fail if we don't provide our own

				'where' => 'WHERE', // will fail if we don't provide our own
			],
			'proto_opts' => [
				':dow_campaign_id' => 'dow_campaign_id = :dow_campaign_id',
				':products_id' => 'products_id = :products_id',
				':deal_start' => 'deal_start = :deal_start',
				':deal_end' => 'deal_end = :deal_end',
				':custom_description' => 'custom_description = :custom_description',
				':custom_legalese' => 'custom_legalese = :custom_legalese',
				':create_specials_price' => 'create_specials_price = :create_specials_price',
				':draft' => 'draft = :draft',
				':active' => 'active = :active',
				':dow_deal_id' => 'dow_deal_id = :dow_deal_id',
				':not_dow_deal_id' => 'dow_deal_id != :not_dow_deal_id',
				':not_dow_campaign_id' => 'dow_campaign_id != :not_dow_campaign_id'
			],
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],
	];

	public function __construct($dow_campaign_id, ck_dow_campaign_type $skeleton=NULL) {
		if (empty($dow_campaign_id)) throw new CkArchetypeException('$dow_campaign_id cannot be empty');

		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($dow_campaign_id);
		if (!$this->skeleton->built('dow_campaign_id')) $this->skeleton->load('dow_campaign_id', $dow_campaign_id);
		self::register($dow_campaign_id, $this->skeleton);
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$clauses = self::$queries['dow_campaign_header_proto']['proto_opts'];
			$qry = self::modify_query('dow_campaign_header_proto', ['where' => 'WHERE '.$clauses[':dow_campaign_id']]);
			$header = self::fetch($qry, [':dow_campaign_id' => $this->skeleton->get('customers_id')]);
		}
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		if (!($header['first_date'] instanceof DateTime)) $header['first_date'] = new DateTime($header['first_date']);
		if (!($header['last_date'] instanceof DateTime)) $header['last_date'] = new DateTime($header['last_date']);
		if (!($header['created_date'] instanceof DateTime)) $header['created_date'] = new DateTime($header['created_date']);

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$clauses = self::$queries['dow_campaign_header_proto']['proto_opts'];
		$qry = self::modify_query('dow_campaign_header_proto', ['where' => 'WHERE '.$clauses[':dow_campaign_id']]);
		$header = self::fetch($qry, [':dow_campaign_id' => $this->skeleton->get('dow_campaign_id')]);

		if (empty($header)) throw new CkArchetypeException('Could not find header for DOW Campaign ID '.$this->skeleton->get('dow_campaign_id'));

		$this->skeleton->load('header', $header);
		$this->normalize_header();
	}

	private function build_deals() {
		$deals = self::fetch('deals', [':dow_campaign_id' => $this->skeleton->get('customers_id'), ':dow_deal_id' => NULL]);

		foreach ($deals as &$deal) {
			$deal['deal_start'] = new DateTime($deal['deal_start']);
			$deal['deal_end'] = new DateTime($deal['deal_end']);
			$deal['created_date'] = new DateTime($deal['created_date']);
			$deal['listing'] = new ck_product_listing($deal['products_id']);

			if ($recommendations = self::fetch('product_recommendations', [':dow_deal_id' => $deal['dow_deal_id']])) {
				$deal['recommended_products'] = [];
				foreach ($recommendations as &$recommendation) {
					$recommendation['created_date'] = new DateTime($deal['created_date']);
					$recommendation['listing'] = new ck_product_listing($recommendation['products_id']);
					$deal['recommended_products'][] = $recommendation;
				}
			}
		}

		$this->skeleton->load('deals', $deals);
	}

	private function build_options() {
		$options_raw = self::fetch('campaign_options', [':dow_campaign_id' => $this->skeleton->get('dow_campaign_id')]);

		$options = [];
		foreach ($options_raw as $option) {
			if (!isset($options[$option['dow_campaign_option_type_id']])) $options[$option['dow_campaign_option_type_id']] = [];
			$options[$option['dow_campaign_option_type_id']][] = $option['option_value'];
		}

		$this->skeleton->load('options', $options);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header',$key);
	}

	public function has_deals() {
		if (!$this->skeleton->built('deals')) $this->build_deals();
		return $this->skeleton->has('deals');
	}

	public function get_deals($key=NULL) {
		if (!$this->has_deals()) return [];
		if (empty($key)) return $this->skeleton->get('deals');
		else {
			$deals = [];
			if ($key == 'active') {
				foreach ($this->skeleton->get('deals') as $deal) {
					if (!empty($deal['active'])) $deals[] = $deal;
				}
			}
			else {
				// for now we'll treat anything else as a deal ID - and only return that one deal, rather than an array
				foreach ($this->skeleton->get('deals') as $deal) {
					if ($deal['dow_deal_id'] == $key) return $deal;
				}
			}
			return $deals;
		}
	}

	public function has_options($key=NULL) {
		if (!$this->skeleton->built('options')) $this->build_options();
		if (empty($key)) return $this->skeleton->has('options');
		else return !empty($this->skeleton->get('options',$key));
	}

	public function get_options($key=NULL) {
		if (!$this->has_options($key)) return [];
		if (empty($key)) return $this->skeleton->get('options');
		else return $this->skeleton->get('options',$key);
	}

	public static $matched_total = [];

	public static function get_all_campaigns($page=1, $page_size=25, $order_by=[]) {
		$proto = self::$queries['dow_campaign_header_proto'];
		// we don't *need* to include the 'where' clause, but we're explicitly making it blank to make it obvious we're after all of them
		$query_mods = ['data_operation' => $proto['proto_count_clause'], 'where' => '', 'limit' => '', 'cardinality' => cardinality::SINGLE];
		$qry = self::modify_query('dow_campaign_header_proto', $query_mods);

		// we're doing some limited caching of this result - next step is short-life persistent memory caching (memcached)
		if (empty(self::$matched_total[$qry])) self::$matched_total[$qry] = self::fetch($qry, []);

		unset($query_mods['data_operation']);

		if ($page_size != -1) {
			if (!is_numeric($page)) $page = 1;
			if (!is_numeric($page_size)) $page_size = 25;
			$page--; // limits are zero based
			$query_mods['limit'] = 'LIMIT '.($page*$page_size).', '.$page_size;
		}

		$query_mods['cardinality'] = cardinality::SET;

		// modify_query is necessary because we can't parameterize limits or ordering
		$qry = self::modify_query('dow_campaign_header_proto', $query_mods);

		if ($headers = self::fetch($qry, [])) {
			$campaigns = [];
			foreach ($headers as $header) {
				$skeleton = self::get_record($header['dow_campaign_id']); // if we've already instantiated it, well, oh well
				if (!$skeleton->built('header')) $skeleton->load('header', $header);

				$campaigns[] = new self($header['dow_campaign_id'], $skeleton);
			}
			return $campaigns;
		}
		else return [];
	}

	public static function get_active_campaign() {
		$proto = self::$queries['dow_campaign_header_proto'];
		$query_mods = ['where' => 'WHERE '.$proto['proto_opts'][':active']];

		// modify_query is necessary because we can't parameterize limits or ordering
		$qry = self::modify_query('dow_campaign_header_proto', $query_mods);

		if ($header = self::fetch($qry, [':active' => 1])) {
			$skeleton = self::get_record($header['dow_campaign_id']); // if we've already instantiated it, well, oh well
			if (!$skeleton->built('header')) $skeleton->load('header', $header);

			return new self($header['dow_campaign_id'], $skeleton);
			return $campaigns;
		}
		else {
			$campaigns = self::get_all_campaigns(1, -1);
			foreach ($campaigns as $campaign) {
				if ($campaign->is('draft')) continue;
				if ($campaign->get_header('start_date') > self::NOW()) continue;
				// the first time we get to a start date prior to today, use it and break

				self::set_active_campaign($campaign->get_header('dow_campaign_id'));

				return $campaign;
				break;
			}
		}
	}

	public static function get_next_campaign() {
		$proto = self::$queries['dow_campaign_header_proto'];
		$wheres = [$proto['proto_opts'][':first_date'], $proto['proto_opts'][':draft'], $proto['proto_opts'][':active']];
		$query_mods = ['where' => 'WHERE '.implode(' AND ', $wheres), 'order_by' => 'first_date ASC'];

		// modify_query is necessary because we can't parameterize limits or ordering
		$qry = self::modify_query('dow_campaign_header_proto', $query_mods);

		if ($header = self::fetch($qry, [':first_date' => self::NOW()->format('Y-m-d'), ':draft' => 0, ':active' => 0])) {
			$skeleton = self::get_record($header['dow_campaign_id']); // if we've already instantiated it, well, oh well
			if (!$skeleton->built('header')) $skeleton->load('header', $header);

			return new self($header['dow_campaign_id'], $skeleton);
			return $campaigns;
		}
		else return NULL;
	}

	private static $sort_ascending = 0;

	public static function sort_deals($a, $b) {
		if (!empty(self::$sort_ascending)) {
            return $a['deal_start'] > $b['deal_start'] ? 1 : -1;
        } elseif ($a['cost'] != $b['cost']) {
            return $a['cost'] > $b['cost'] ? -1 : 1;
        } elseif ($a['entered_date'] != $b['entered_date']) {
            return $a['entered_date']<$b['entered_date']?-1:1;
        } else {
		    return 0;
		};
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public function activate_campaign() {
		// since we only want one dow campaign active at a time, we use a transaction to batch the queries
		// probably overkill, but it better describes our intent
		$savepoint_id = self::transaction_begin();

		$proto = self::$queries['update_campaign_proto'];
		$deal_proto = self::$queries['update_deal_proto'];

		if (!$this->is('active')) {
			$query_mods = ['set' => $proto['proto_opts'][':active'], 'where' => $proto['proto_opts'][':dow_campaign_id']];
			$qry = self::modify_query('update_campaign_proto', $query_mods);

			self::execute($qry, [':active' => 1, ':dow_campaign_id' => $this->skeleton->get('dow_campaign_id')]);

			$query_mods = ['set' => $proto['proto_opts'][':active'], 'where' => $proto['proto_opts'][':not_dow_campaign_id']];
			$qry = self::modify_query('update_campaign_proto', $query_mods);

			self::execute($qry, [':active' => 0, ':not_dow_campaign_id' => $this->skeleton->get('dow_campaign_id')]);

			$query_mods = ['set' => $deal_proto['proto_opts'][':active'], 'where' => $proto['proto_opts'][':not_dow_campaign_id']];
			$qry = self::modify_query('update_deal_proto', $query_mods);

			self::execute($qry, [':active' => 0, ':not_dow_campaign_id' => $this->skeleton->get('dow_campaign_id')]);
		}

		foreach ($this->get_deals() as $deal) {
			if ($deal['draft'] == 1) continue; // we don't want draft deals

			$query_mods = ['set' => $deal_proto['proto_opts'][':active'], 'where' => $proto['proto_opts'][':dow_deal_id']];
			$qry = self::modify_query('update_deal_proto', $query_mods);

			if ($deal['deal_start'] >= self::NOW() && $deal['deal_end'] < self::NOW() && $deal['active'] == 0) {
				// if we're in the dow schedule period, but it's not active, activate it

				self::execute($qry, [':active' => 1, ':dow_deal_id' => $deal['dow_deal_id']]);

				if (!empty($deal['create_specials_price'])) {
				}
			}
			elseif ($deal['deal_start'] < self::NOW() && $deal['deal_end'] >= self::NOW() && $deal['active'] == 1) {
				// if we're not in the dow schedule period, but it's active, deactivate it

				self::execute($qry, [':active' => 0, ':dow_deal_id' => $deal['dow_deal_id']]);
			}
		}

		self::transaction_end(TRUE, $savepoint_id);
	}

	public function update_campaign($data) {
	}

	public function update_deal($data) {
	}

	public function update_product_recommendations($data) {
	}

	public static function run_schedule() {
		$campaign = self::get_active_campaign();

		if ($campaign->get_header('last_date') < self::NOW()) {
			if ($campaign = self::get_next_campaign() && self::set_active_campaign($campaign->get_header('dow_campaign_id'))) {
				return; // setting a new active campaign takes care of all deal wrangling
			}
		}

		$at_least_one_active = 0;
		foreach ($campaign->get_deals() as $deal) {
			if ($deal['active'] == 1 && ($deal['deal_start'] > self::NOW() || $deal['deal_end'] < self::NOW())) {
				// turn this deal off
			}
			elseif ($deal['active'] == 0 && $deal['deal_start'] <= self::NOW() && $deal['deal_end'] >= self::NOW()) {
				$at_least_one_active = 1;
				// turn this deal on
			}
			else {
				$at_least_one_active |= $deal['active'];
			}
		}
	}

	public static function setup_legacy_special($products_id, $specials_price=NULL) {
		require_once(__DIR__.'/../functions/inventory_functions.php');
		if (!empty($specials_price)) {
			if (self::query_fetch('SELECT specials_id FROM specials WHERE products_id = ?', [$products_id])) {
				insert_psc_change_history($new['stock_id'], 'Special Delete ['.$new['products_model'].']', 'Status Off', '');
				prepared_query::execute('DELETE FROM specials WHERE products_id = ?', $new['products_id']);
			}

			$expiration_date = new DateTime(prepared_query::fetch('SELECT DATE_SUB(start_date, INTERVAL 1 DAY) FROM ck_dow_schedule WHERE start_date > ? ORDER BY start_date ASC', cardinality::SINGLE, date('Y-m-d')));

			$special = [
				'status' => 1,
				'specials_qty' => NULL,
				'specials_new_products_price' => $new['specials_price'],
				'expires_date' => !empty($expiration_date)?$expiration_date->format('Y-m-d 23:59:59'):'',
				'active_criteria' => 1,
			];

			$listing = new ck_product_listing($products_id);
			$listing->set_special($special);
		}
		else {
			insert_psc_change_history($new['stock_id'], 'Special Update ['.$new['products_model'].']', 'Previous Status', 'Auto DOW On');
			prepared_query::execute('UPDATE specials SET status = 1 WHERE products_id = ?', $new['products_id']);
		}
	}
}
?>
