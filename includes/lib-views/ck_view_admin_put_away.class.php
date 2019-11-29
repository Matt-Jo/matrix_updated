<?php
class ck_view_admin_put_away extends ck_view {

	protected $url = '/put-away.php';

	protected $page_templates = [
		'put_away' => 'page-put-away.mustache.html',
	];

	protected static $queries = [
	];

	public function process_response() {
		$this->init([__DIR__.'/../../admin/includes/templates']);

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
			/*case 'prefill':
				$ipns = self::query_fetch('SELECT DISTINCT UPPER(stock_name) as ipn FROM products_stock_control ORDER BY stock_name ASC', cardinality::COLUMN, []);
				$response['ipns'] = array_count_values($ipns);

				$serials = self::query_fetch('SELECT DISTINCT UPPER(s.serial) as serial, UPPER(psc.stock_name) as ipn FROM serials s JOIN products_stock_control psc ON s.ipn = psc.stock_id WHERE s.status IN (2,3,6)', cardinality::SET, []);
				$response['serials'] = [];
				foreach ($serials as $serial) {
					$response['serials'][$serial['serial']] = $serial['ipn'];
				}
				break;*/
			case 'bin-lookup':
				$bin_number = trim($_REQUEST['bin_number']);

				// need to make sure it shows stock levels for non-serialized
				$response['results'] = self::query_fetch("SELECT 1 as lookup, :clear_bin_number as bin, psc.stock_name as ipn, CASE WHEN psc.serialized = 0 AND psc.stock_quantity > 0 THEN CONCAT(psc.stock_quantity, ': UNSERIALIZED') WHEN psc.serialized = 0 AND psc.stock_quantity <= 0 THEN '[[0: UNSERIALIZED]]' ELSE IFNULL(CONCAT('1: ', s.serial), '[[0: SERIALIZED]]') END as serial, CASE WHEN sh.bin_location LIKE :bin_number THEN 'Serial Bin' WHEN psce.stock_location LIKE :bin_number THEN 'IPN Bin 1' WHEN psce.stock_location_2 LIKE :bin_number THEN 'IPN Bin 2' END as assignment FROM products_stock_control psc LEFT JOIN products_stock_control_extra psce ON psc.stock_id = psce.stock_id LEFT JOIN serials s ON psc.stock_id = s.ipn AND s.status IN (2, 3, 6) LEFT JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh0 ON s.id = sh0.serial_id AND sh.id < sh0.id WHERE sh0.id IS NULL AND (sh.bin_location LIKE :bin_number OR ((sh.bin_location IS NULL OR sh.bin_location = '') AND (psce.stock_location LIKE :bin_number OR psce.stock_location_2 LIKE :bin_number)))", cardinality::SET, [':clear_bin_number' => $bin_number, ':bin_number' => $bin_number]);

				if (empty($response['results'])) $response['results'] = [['lookup' => 1, 'bin' => $bin_number, 'ipn' => '[[EMPTY]]', 'serial' => '[[EMPTY]]', 'assignment' => '[[EMPTY]]']];
				break;
			case 'ipn-lookup':
				$ipn = trim($_REQUEST['ipn']);

				if (!self::query_fetch('SELECT stock_id FROM products_stock_control WHERE stock_name LIKE :ipn', cardinality::SINGLE, [':ipn' => $ipn])) $response['err'] = 'This IPN could not be located';
				break;
			case 'serial-lookup':
				$serial = trim($_REQUEST['serial']);

				if (!self::query_fetch('SELECT serial_id FROM serials WHERE serial LIKE :serial', cardinality::SINGLE, [':serial' => $serial])) $response['err'] = 'This Serial could not be located';
				break;
			default:
				$response['err'] = ['The requested action ['.$_REQUEST['action'].'] was not recognized'];
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$data = $this->data();

		$this->render($this->page_templates['put_away'], $data);
		$this->flush();
	}
}
?>
