<?php
class ck_admin_wholesale_price_worksheet_view extends ck_view {

	protected $url = '/admin/wholesale-price-worksheet';

	protected $page_templates = [];
	protected static $start_range = NULL;
	protected static $end_range = NULL;

	public function process_response() {
		ini_set('memory_limit', '1024M');
		$this->init([__DIR__.'/../../admin/includes/templates']);

		self::$start_range = new DateTime('today');
		self::$end_range = new DateTime('today -30 days');

		$__FLAG = request_flags::instance();

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;

		switch ($_REQUEST['action']) {
			default:
				break;
		}

		if (!empty($page)) CK\fn::redirect_and_exit($page);
	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}

	private function ajax_response() {
		$response = [];
		switch ($_REQUEST['action']) {
			case 'search':
				$extra_joins = $where_clause = ' ';

				if (!empty($_REQUEST['range'])) self::$end_range = new DateTime('today -'.$_REQUEST['range'].' days');

				$params = [':end_range' => self::$end_range->format('y-m-d')];

				if ($_REQUEST['key'] !== '^update' && !empty($_REQUEST['key'])) {
					$where_clause .= 'psc.stock_name LIKE :stock_name AND ';
					$params +=  [':stock_name' => $_REQUEST['key'].'%'];
				}

				if (!empty($_REQUEST['vertical_id'])) {
					$extra_joins .= 'LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id ';
					$where_clause .= 'pscc.vertical_id = :vertical_id AND ';
					$params += [':vertical_id' => $_REQUEST['vertical_id']];
				}

				ck_ipn2::set_load_context(ck_ipn2::CONTEXT_LIST);

				$stock_ids = prepared_query::fetch('SELECT DISTINCT psc.stock_id, psc.wholesale_high_price, psc.wholesale_low_price FROM products_stock_control psc'.$extra_joins.'WHERE'.$where_clause.'((psc.last_wholesale_high_price_confirmation IS NULL OR psc.last_wholesale_low_price_confirmation IS NULL) OR (psc.last_wholesale_high_price_confirmation <= :end_range OR psc.last_wholesale_low_price_confirmation <= :end_range)) ORDER BY psc.last_wholesale_low_price_confirmation, psc.last_wholesale_high_price_confirmation DESC', cardinality::SET, $params);

				ck_ipn2::load_ipn_set($stock_ids);

				$response['total_results'] = count($stock_ids);

				$results_per_page = $_REQUEST['results_per_page'];
				if (empty($_REQUEST['results_per_page'])) $results_per_page = $response['total_results'];

				if (!empty($stock_ids)) {
					for ($i = 0; $i < $results_per_page; $i++) $response['results'][] = $this->get_single_result($stock_ids[$i]);
					$response['displayed_results'] = $i;
				}
				else {
					$response['displayed_results'] = 0;
				}

				break;
			case 'set-high-price':
				$ipn = new ck_ipn2($_REQUEST['stock_id']);
				$today = new DateTime('today');
				$ipn->update(['wholesale_high_price' => $_REQUEST['wholesale_high_price'], 'last_wholesale_high_price_confirmation' => $today->format('y-m-d')]);
				$response['success'] = TRUE;
				break;
			case 'set-low-price':
				$ipn = new ck_ipn2($_REQUEST['stock_id']);
				$today = new DateTime('today');
				$ipn->update(['wholesale_low_price' => $_REQUEST['wholesale_low_price'], 'last_wholesale_low_price_confirmation' => $today->format('y-m-d')]);
				$response['success'] = TRUE;
				break;
			default:
				$response['err'] = ['The requested action ['.$_REQUEST['action'].'] was not recognized'];
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function get_single_result($stock_id) {
		$ipn = new ck_ipn2($stock_id['stock_id']);

		// calculate total quoted in range
		$rfqs = 0;
		$rfq_average = 0;
		foreach ($ipn->get_rfq_history_range(self::$start_range, self::$end_range) as $rfq) {
			$rfq_average += ($rfq['price'] * $rfq['quantity']);
			$rfqs += $rfq['quantity'];
		}
		if ($rfqs > 0) $rfq_average = $rfq_average / $rfqs;

		// calculate total purchase in range
		$sold = 0;
		$sold_average = 0;
		foreach ($ipn->get_sales_history_range(self::$start_range, self::$end_range) as $sales_order) {
			$sold_average += ($sales_order['final_price'] * $sales_order['products_quantity']);
			$sold += $sales_order['products_quantity'];
		}
		if ($sold > 0) $sold_average = $sold_average / $sold;

		// calculate total sales in range
		$purchased = 0;
		$purchased_average = 0;
		foreach ($ipn->get_purchase_history_range(self::$start_range, self::$end_range) as $purchase_order) {
			$purchased_average += ($purchase_order['cost'] * $purchase_order['quantity']);
			$purchased += $purchase_order['quantity'];
		}
		if ($purchased > 0) $purchased_average = $purchased_average / $purchased;

		$wholesale_high_price = $stock_id['wholesale_high_price'];
		if (!empty($wholesale_high_price)) $wholesale_high_price = number_format($wholesale_high_price, 2);

		$wholesale_low_price = $stock_id['wholesale_low_price'];
		if (!empty($wholesale_low_price)) $wholesale_low_price = number_format($stock_id['wholesale_low_price'], 2);

		$data = [
			'stock_id' => $ipn->id(),
			'ipn' => $ipn->get_header('ipn'),
			'category' => $ipn->get_header('ipn_category'),
			'vertical' => $ipn->get_header('ipn_vertical'),
			'on_hand' => $ipn->get_inventory('on_hand'),
			'available' => $ipn->get_inventory('available'),
			'discontinued' => $ipn->is('discontinued')?'Yep':'Nope',
			'last_wholesale_high_price_confirmation' => $ipn->get_header('last_wholesale_high_price_confirmation'),
			'last_wholesale_low_price_confirmation' => $ipn->get_header('last_wholesale_low_price_confirmation'),
			'wholesale_high_price' => $wholesale_high_price,
			'wholesale_low_price' => $wholesale_low_price,
			'sold' => $sold,
			'sold_average' => CK\text::monetize($sold_average),
			'purchased' => $purchased,
			'purchased_average' => CK\text::monetize($purchased_average),
			'rfqs' => $rfqs,
			'rfq_average' => CK\text::monetize($rfq_average)
		];
		return $data;
	}

	private function http_response() {
		$data = $this->data();

		$vertical_id = null;
		if (!empty($_REQUEST['vertical_id'])) $vertical_id = $_REQUEST['vertical_id'];

		$results_per_page = 50;
		if (!empty($_REQUEST['results_per_page'])) $results_per_page = $_REQUEST['results_per_page'];

		$page = 1;
		if (!empty($_REQUEST['page'])) $page = $_REQUEST['page'];

		$date_range = self::$start_range->diff(self::$end_range);
		if (!empty($_REQUEST['range'])) self::$end_range = new DateTime('today -'.$_REQUEST['range'].' days');

		ck_ipn2::set_load_context(ck_ipn2::CONTEXT_LIST);

		$stock_ids = prepared_query::fetch('SELECT DISTINCT psc.stock_id, psc.wholesale_high_price, psc.wholesale_low_price FROM products_stock_control psc WHERE (psc.last_wholesale_high_price_confirmation IS NULL OR psc.last_wholesale_low_price_confirmation IS NULL) OR (psc.last_wholesale_high_price_confirmation <= :end_range OR psc.last_wholesale_low_price_confirmation <= :end_range) ORDER BY psc.last_wholesale_low_price_confirmation, psc.last_wholesale_high_price_confirmation DESC', cardinality::SET, [':end_range' => self::$end_range->format('y-m-d')]);

		ck_ipn2::load_ipn_set($stock_ids);

		$data['total_results'] = count($stock_ids);

		for ($i = 0; $i < $results_per_page; $i ++) $data['wholesale_ipns'][] = $this->get_single_result($stock_ids[$i]);
		$data['displayed_results'] = $i;

		$ipn_verticals = prepared_query::fetch('SELECT id, name FROM products_stock_control_verticals ORDER BY name DESC', cardinality::SET, []);

		if (empty($_REQUEST['vertical_id'])) $data['verticals'][] = ['selected' => 1, 'id' => NULL, 'name' => 'All'];
		else $data['verticals'][] = ['id' => NULL, 'name' => 'All'];

		foreach ($ipn_verticals as $vertical) {
			if (!empty($_REQUEST['vertical_id']) && $_REQUEST['vertical_id'] == $vertical['id']) {
				$data['verticals'][] = ['selected' => 1, 'id' => $vertical['id'], 'name' => $vertical['name']];
			}
			else $data['verticals'][] = ['id' => $vertical['id'], 'name' => $vertical['name']];
		}

		$data['ranges'] = [
			['selected' => 1, 'value' => 30, 'name' => '30 days'],
			['value' => 60, 'name' => '60 days'],
			['value' => 90, 'name' => '90 days']
		];

		$data['results'] = [
			['selected' => 1, 'value' => 50, 'name' => '50'],
			['value' => 100, 'name' => '100'],
			['value' => 200, 'name' => '200'],
			['value' => '', 'name' => 'All'],
		];

		$this->render('page-wholesale-price-worksheet.mustache.html', $data);
		$this->flush();
	}
}
?>
