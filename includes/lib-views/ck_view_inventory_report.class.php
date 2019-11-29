<?php
ini_set('memory_limit', '756M');
class ck_view_inventory_report extends ck_view {

	protected $url = '/inventory-report';

	protected $page_templates = [
		'inventory-report' => 'page-inventory-report.mustache.html',
	];

	public function process_response() {
		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;
		if (!empty($page)) CK\fn::redirect_and_exit($page);
	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}

	private function ajax_response() {
		// there's nothing to do here

		echo json_encode(['errors' => []]);
		exit();
	}

	private function http_response() {
		$data = $this->data();
		$data['verticals'] = [];

		$__FLAG = request_flags::instance();

		$ipns_raw = ck_ipn2::get_ipns_for_inventory_report();
		foreach ($ipns_raw as $ipn_raw) {
			/*$header = [
				'stock_id' => $ipn_raw['stock_id'],
				'ipn' => $ipn_raw['ipn'],
				'products_stock_control_category_id' => $ipn_raw['products_stock_control_category_id'],
				'stock_price' => $ipn_raw['stock_price'],
				'dealer_price' => $ipn_raw['dealer_price']
			];

			$inventory['salable'] = $ipn_raw['salable'];

			$skeleton = ck_ipn2::get_record($ipn_raw['stock_id']);
			if (!$skeleton->built('header')) $skeleton->load('header', $header);
			if (!$skeleton->built('inventory')) $skeleton->load('inventory', $inventory);

			$ipn = new ck_ipn2($ipn_raw['stock_id'], $skeleton);*/

			$data['ipn_list'][] = [
				'stock_id' => $ipn_raw['stock_id'],
				'ipn' => $ipn_raw['ipn'],
				'categories_id' => $ipn_raw['products_stock_control_category_id'],
				'stock_quantity' => $ipn_raw['salable'],
				'price' => CK\text::monetize($ipn_raw['stock_price']),
				'dealer_price' => CK\text::monetize($ipn_raw['dealer_price'])
			];
		}

		$verticals = prepared_query::fetch('SELECT id, name FROM products_stock_control_verticals WHERE id NOT IN (13)', cardinality::SET);

		$categories = prepared_query::fetch('SELECT pscc.categories_id, pscc.name, pscc.vertical_id FROM products_stock_control_categories pscc WHERE pscc.categories_id NOT IN (93,90, 66,58, 31) ORDER BY name ASC', cardinality::SET);

		foreach ($verticals as $vertical) {
			//if (!isset($data['verticals'][$vertical['name']]))
			$data['verticals'][] = ['name' => $vertical['name']];
			foreach ($categories as $category) {
				if ($vertical['id'] == $category['vertical_id']) {
					$data['verticals'][] = ['categories' => $category];
				}
			}
		}

		$this->render($this->page_templates['inventory-report'], $data);
		$this->flush();
	}
}

?>
