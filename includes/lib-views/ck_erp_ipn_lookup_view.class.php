<?php
class ck_erp_ipn_lookup_view extends ck_view {

	protected $url = '/erp/ipn-lookup';

	protected $page_templates = [];

	public function process_response() {
		if ($_SESSION['admin'] != 'true') CK\fn::redirect_and_exit('/');

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_POST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;

		switch ($_REQUEST['action']) {
			case 'update-quote':
				break;
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

		$__FLAG = request_flags::instance();

		switch ($_REQUEST['action']) {
			case 'part-lookup':
				$response['results'] = [];
				$part_number = trim($_GET['item_lookup']);
				if (empty($part_number)) exit();

				ck_ipn2::set_load_context(ck_ipn2::CONTEXT_LIST);

				$filter = [
					'discontinued' => $__FLAG['filter_discontinued']?1:NULL,
					'none_available' => $__FLAG['filter_none_available'],
				];

				if ($stock_ids = prepared_query::fetch('SELECT DISTINCT psc.stock_id FROM products_stock_control psc JOIN products p ON psc.stock_id = p.stock_id WHERE (psc.stock_name LIKE :part_number OR p.products_model LIKE :part_number) AND (:discontinued IS NULL OR psc.discontinued = 0) ORDER BY psc.stock_name = :exact_match DESC, p.products_model = :exact_match DESC, psc.stock_name ASC', cardinality::COLUMN, [':part_number' => $part_number.'%', ':exact_match' => $part_number, ':discontinued' => $filter['discontinued']])) {

					ck_ipn2::load_ipn_set($stock_ids);

					foreach ($stock_ids as $stock_id) {
						$ipn = new ck_ipn2($stock_id);

						$inventory = $ipn->get_inventory();

						if ($filter['none_available'] && $inventory['available'] <= 0) continue;

						$result = [
							'stock_id' => $ipn->id(),
							'ipn' => $ipn->get_header('ipn'),
							'safe_ipn' => urlencode($ipn->get_header('ipn')),
							'condition' => $ipn->get_condition(),
							'average_cost' => CK\text::monetize($ipn->get_avg_cost()),
							'info' => [],
							'products' => [],
							'full_availability' => '[On Hand: '.$inventory['on_hand'].'] [On Hold: '.$inventory['on_hold'].'] [Allocated: '.$inventory['allocated'].']',
							'on_hand' => $inventory['on_hand'],
							'available' => $inventory['available'],
							'on_hold' => $inventory['on_hold'],
							'prices' => [],
						];

						if ($ipn->is('discontinued')) $result['info'][] = '<span title="Discontinued">[D]</span>';
						if ($ipn->is('is_bundle')) $result['info'][] = '<span title="Bundle">[B]</span>';
						if ($ipn->is('drop_ship')) $result['info'][] = '<span title="Drop Ship">[DS]</span>';
						if ($ipn->is('non_stock')) $result['info'][] = '<span title="Non-Stock">[NS]</span>';
						if ($ipn->is('freight')) $result['info'][] = '<span title="Freight">[F]</span>';

						$result['info'] = implode(' ', $result['info']);

						foreach ($ipn->get_listings() as $product) {
							if (!$product->is_cartable()) continue;

							$p = [
								'products_id' => $product->id(),
								'model_number' => $product->get_header('products_model'),
								'product_name' => $product->get_header('products_name'),
								'link' => $product->get_url(),
							];

							if ($product->get_viewable_state() <= 0) $p['inactive'] = 1;
							$result['products'][] = $p;
						}

						if (count($result['products']) > 1) $result['multi-products'] = 1;

						$price_list = ['original', 'dealer', 'wholesale_high', 'wholesale_low', 'special', 'customer'];
						$prices = $product->get_price(); // this is specifically using the last defined product listing from the foreach loop above, since they'll all be the same
						foreach ($price_list as $price_reason) {
							if (!empty($prices[$price_reason]) && $prices[$price_reason] > 0) {
								$price = [
									'reason' => $price_reason,
									'friendly_reason' => ucwords(preg_replace('/_/', ' ', $price_reason)),
									'price' => CK\text::monetize($prices[$price_reason]),
								];

								if ($price['friendly_reason'] == 'Original') $price['friendly_reason'] = 'Retail';
								elseif ($price['friendly_reason'] == 'Dealer') $price['friendly_reason'] = 'Reseller';

								if ($price_reason == $prices['reason']) $price['sel'] = 1;

								$result['prices'][] = $price;
							}
						}

						if (!$ipn->has_active_listings()) $result['no_active'] = 1;

						$response['results'][] = $result;
					}
				}

				break;
			case 'get-serials':
				$ipn = new ck_ipn2($_GET['stock_id']);

				$response['serials'] = [];

				if ($ipn->is('serialized')) {
					if ($is_serials = $ipn->get_serials(ck_serial::$statuses['INSTOCK'])) {
						foreach ($is_serials as $serial) {
							$srl = [
								'serial' => $serial->get_header('serial_number'),
								'status' => 'In Stock',
								'cost' => CK\text::monetize($serial->get_current_history('cost')),
								'notes' => $serial->get_current_history('short_notes'),
								'owner' => $serial->has_last_po()?$serial->get_last_po()->get_header('owner')->get_name():'NO PO',
							];

							if (empty($srl['notes'])) $srl['notes'] = '';

							$response['serials'][] = $srl;
						}
					}

					if ($h_serials = $ipn->get_serials(ck_serial::$statuses['HOLD'])) {
						foreach ($h_serials as $serial) {
							$srl = [
								'serial' => $serial->get_header('serial_number'),
								'status' => 'On Hold',
								'cost' => CK\text::monetize($serial->get_current_history('cost')),
								'notes' => $serial->get_current_history('short_notes'),
								'owner' => $serial->has_last_po()?$serial->get_last_po()->get_header('owner')->get_name():'NO PO',
							];

							if (empty($srl['notes'])) $srl['notes'] = '';

							$response['serials'][] = $srl;
						}
					}
				}
				break;
			default:
				$response['errors'] = ['The requested action ['.$_REQUEST['action'].'] was not recognized'];
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$data = $this->data();

		$data['base_url'] = $this->url;
		if (!empty($_REQUEST['params'])) {
			$params = $_REQUEST['params'];
			$data['term'] = $params['term'];
			if (isset($params['show_unavailable'])) $data['show_unavailable'] = 1;
			if (isset($params['show_discontinued'])) $data['show_discontinued'] = 1;
		}

		$this->render('page-erp-ipn-lookup.mustache.html', $data);
		$this->flush();
	}
}
?>
