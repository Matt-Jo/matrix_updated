<?php
class ck_admin_suggested_buy_view extends ck_view {

	const STAGE1 = 'stage-1';
	const STAGE2 = 'stage-2';
	const STAGE3 = 'stage-3';
	const STAGE4 = 'stage-4';

	private $current_stage = self::STAGE1;

	protected $url = '/admin/upload-suggested-buys';

	protected $page_templates = [];

	public function process_response() {
		$this->init([__DIR__.'/../../admin/includes/templates']);

		$__FLAG = request_flags::instance();

		if (!empty($_REQUEST['stage'])) $this->current_stage = $_REQUEST['stage'];

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;

		switch ($_REQUEST['action']) {
			case 'upload-stage-1':
				// do nothing, this is all straight to the view
				break;
			case 'upload-stage-2':
				$columns = [];
				foreach ($_POST['spreadsheet_column'] as $column_idx => $field) {
					if ($field == '0') continue;
					$columns[$field] = $column_idx;
				}

				if (!isset($columns['vendors_id']) || !isset($columns['qty']) || (!isset($columns['stock_id']) && !isset($columns['ipn']))) {
					echo 'HARD HALT - required columns are missing!';
					exit();
				}

				$suggestion = array_filter(array_map(function($row) use ($columns) {
					$sug = [];

					if ($row[$columns['qty']] <= 0) return NULL;

					foreach ($columns as $field => $column_idx) {
						$sug[$field] = $row[$column_idx];
					}

					return $sug;
				}, $_POST['spreadsheet_field']));

				ck_suggested_buy::create_suggestion($suggestion);

				$page = $this->url.'?stage='.self::STAGE3;

				break;
			case 'void':
				ck_suggested_buy::get_with_instance($_POST['purchase_order_suggested_buy_id'])->void();

				$page = $this->url.'?stage='.self::STAGE3;
				break;
			case 'execute-suggestion':
				switch ($_POST['execute-action']) {
					case 'po':
						$purchase_order_id = ck_suggested_buy::get_with_instance($_POST['purchase_order_suggested_buy_id'])->create_po($_POST['purchase_order_suggested_buy_vendor_id']);
						$page = $this->url.'?purchase_order_ids[]='.$purchase_order_id.'&stage='.self::STAGE4;
						break;
					case 'rfq':
						$rfq_ids = ck_suggested_buy::get_with_instance($_POST['purchase_order_suggested_buy_id'])->create_rfq($_POST['purchase_order_suggested_buy_vendor_id']);
						$page = $this->url.'?stage='.self::STAGE4.'&rfq_ids[]='.implode('&rfq_ids[]=', $rfq_ids);
						break;
					case 'ignore':
						ck_suggested_buy::get_with_instance($_POST['purchase_order_suggested_buy_id'])->ignore($_POST['purchase_order_suggested_buy_vendor_id']);
						$page = $this->url.'?stage='.self::STAGE3;
						break;
				}

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

		switch ($_REQUEST['action']) {
			default:
				$response['err'] = ['The requested action ['.$_REQUEST['action'].'] was not recognized'];
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$data = $this->data();

		if (!empty($_FILES['suggestion_upload'])) {
			$data['upload'] = [];
			$upload_status_map = [
				UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
				UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
				UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
				UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
				UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
				UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
				UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
			];
			if ($_FILES['suggestion_upload']['error'] !== UPLOAD_ERR_OK) {
				$data['upload']['err'] = $upload_status_map[$_FILES['suggestion_upload']['error']];
			}
			else {
				$opts = [];
				if (!empty($_POST['worksheet_index'])) {
					if (is_numeric($_POST['worksheet_index'])) $opts['worksheet_index'] = $_POST['worksheet_index']-1; // zero based
					else $opts['worksheet_name'] = $_POST['worksheet_index'];
				}

				$spreadsheet = new spreadsheet_import($_FILES['suggestion_upload'], $opts);

				$columns = 0;
				$data['upload']['data'] = [];
				foreach ($spreadsheet as $idx => $row) {
					if (empty($columns)) $columns = count($row);
					$data['upload']['data'][$idx-1] = [];
					for ($i=0; $i<$columns; $i++) {
						$data['upload']['data'][$idx-1][] = @$row[$i];
					}
				}

				//var_dump($data['upload']['data']);
			}

			$this->current_stage = self::STAGE2;
		}

		if ($open_suggestions = ck_suggested_buy::get_unhandled_suggestions()) {
			$data['open_suggestions'] = [];

			foreach ($open_suggestions as $suggestion) {
				$sc = $suggestion->get_active_controller();

				$sug = [];
				$sug['purchase_order_suggested_buy_id'] = $suggestion->id();
				$sug['suggested_buy_date'] = $sc->get_header('suggested_buy_date')->format('Y-m-d');

				$sug['suggestions'] = [];

				foreach ($sc->get_buys() as $buy) {
					if ($buy['handled']) continue;
					$sb = [];
					$sb['vendor'] = $buy['vendor']->get_header('company_name');
					$sb['purchase_order_suggested_buy_vendor_id'] = $buy['purchase_order_suggested_buy_vendor_id'];
					$sb['ipns'] = array_map(function($ipn) {
						return ['ipn' => $ipn['ipn']->get_header('ipn'), 'qty' => $ipn['quantity']];
					}, $buy['ipns']);

					$sug['suggestions'][] = $sb;
				}

				$data['open_suggestions'][] = $sug;
			}
		}

		if (!empty($_GET['purchase_order_ids'])) $data['purchase_order_ids'] = $_GET['purchase_order_ids'];

		if (!empty($_GET['rfq_ids'])) $data['rfqs'] = array_map(function($rfq_id) {
			$rfq = new ck_vendor_rfq($rfq_id);
			return ['rfq_id' => $rfq->id(), 'nickname' => $rfq->get_header('nickname')];
		}, $_GET['rfq_ids']);

		$data['stage-tab-idx'] = 0;

		if ($this->current_stage == self::STAGE2) $data['stage-tab-idx'] = 1;
		if ($this->current_stage == self::STAGE3) $data['stage-tab-idx'] = 2;
		if ($this->current_stage == self::STAGE4) $data['stage-tab-idx'] = 3;

		$data[$this->current_stage] = 1;

		$this->render('page-suggested_buy.mustache.html', $data);
		$this->flush();
	}
}
?>
