<?php
class ck_view_admin_physical_count extends ck_view {

	protected $url = '/physical-count.php';

	protected $page_templates = [
		'physical_count' => 'page-physical-count.mustache.html',
	];

	protected static $queries = [
		'update_qty_cost' => [
			'qry' => 'UPDATE products_stock_control SET stock_quantity = stock_quantity + :difference, average_cost = :average_cost WHERE stock_id = :stock_id',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],
		'add_ipn_change_history' => [
			'qry' => 'INSERT INTO ipn_change_history (type, record_id, admin_id, change_date, status, stock_id, qty) VALUES (:type, :stock_id, :admin_id, NOW(), :status, :stock_id, :quantity)',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],
		'insert_inventory_adjustment' => [
			'qry' => 'INSERT INTO inventory_adjustment (ipn_id, scrap_date, admin_id, inventory_adjustment_type_id, inventory_adjustment_reason_id, old_qty, new_qty, cost, old_avg_cost, new_avg_cost) VALUES (:stock_id, NOW(), :admin_id, :type_reason, :type_reason, :original_qty, :new_qty, :change_cost, :original_avg_cost, :new_avg_cost)',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],
		'insert_psc_change_history' => [
			'qry' => "INSERT INTO products_stock_control_change_history (stock_id, change_date, change_user, type_id, reference, old_value, new_value, ipn_import_id) VALUES (:stock_id, NOW(), :admin_email, :change_type_id, '', :old_qty, :new_qty, 0)",
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],
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
			case 'manage_count':
				if (empty($_FILES['count_upload']) && !empty($_POST['new_count_name'])) {
					$new_count_name = $_POST['new_count_name'];

					self::query_execute('INSERT INTO physical_counts (count_name) VALUES (:new_count_name)', cardinality::NONE, [':new_count_name' => $new_count_name]);

					$page = '/admin/physical-count.php';
				}
				break;
			case 'start':
				$physical_count_id = $_GET['physical_count_id'];
				self::query_execute('INSERT INTO physical_count_ipns (physical_count_id, stock_id, serial_id, system_count_at_start, system_bin1_at_start, system_bin2_at_start, system_binserial_at_start) SELECT :physical_count_id, psc.stock_id, s.id, CASE WHEN psc.serialized = 1 THEN 1 ELSE psc.stock_quantity END, psce.stock_location, psce.stock_location_2, sh.bin_location FROM products_stock_control psc LEFT JOIN products_stock_control_extra psce ON psc.stock_id = psce.stock_id LEFT JOIN serials s ON psc.stock_id = s.ipn AND s.status IN (2,3,6) LEFT JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id WHERE sh0.id IS NULL', cardinality::NONE, [':physical_count_id' => $physical_count_id]);

				self::query_execute("INSERT INTO physical_count_bins (physical_count_id, bin_number) SELECT DISTINCT :physical_count_id, TRIM(psce.stock_location) as bin_number FROM products_stock_control_extra psce WHERE TRIM(psce.stock_location) != '' UNION DISTINCT SELECT DISTINCT :physical_count_id, TRIM(psce.stock_location_2) as bin_number FROM products_stock_control_extra psce WHERE TRIM(psce.stock_location_2) != '' UNION DISTINCT SELECT DISTINCT :physical_count_id, TRIM(sh.bin_location) as bin_number FROM serials_history sh WHERE TRIM(sh.bin_location) != '' ORDER BY bin_number ASC", cardinality::NONE, [':physical_count_id' => $physical_count_id]);

				self::query_execute('UPDATE physical_counts SET start_date = NOW() WHERE physical_count_id = :physical_count_id', cardinality::NONE, [':physical_count_id' => $physical_count_id]);

				$page = '/admin/physical-count.php';
				break;
			case 'send-count':
				$physical_count_id = $_POST['physical_count_id'];
				$columns = [];
				foreach ($_POST['spreadsheet_column'] as $column_idx => $field) {
					if ($field == '0') continue;
					$columns[$field] = $column_idx;
				}

				if (!isset($columns['ipn']) || !isset($columns['qty']) || !isset($columns['bin'])) {
					echo 'HARD HALT - required columns are missing!';
					exit();
				}

				self::transaction_begin();

				$already_counteds = [];
				$missing_ipns = [];

				$serialized = [];

				try {
					$admin_email = self::query_fetch('SELECT admin_email_address FROM admin WHERE admin_id = :admin_id', cardinality::SINGLE, [':admin_id' => $_SESSION['login_id']]);

					foreach ($_POST['spreadsheet_field'] as $row_idx => $row) {
						$ipn = @$row[$columns['ipn']];
						$qty = preg_replace('/[^0-9-]/', '', !empty($row[$columns['qty']])?$row[$columns['qty']]:0);
						$bin_number = !empty($row[$columns['bin']])?$row[$columns['bin']]:'';

						$has_product = !empty($qty)?1:0;

						$ipn_identifier = $_POST['ipn_identifier'];
						$qty_type = $_POST['qty_type'];

						if ($physical_count_bin_id = self::query_fetch('SELECT physical_count_bin_id FROM physical_count_bins WHERE bin_number LIKE :bin_number', cardinality::SINGLE, [':bin_number' => $bin_number])) {
							self::query_execute('UPDATE physical_count_bins SET has_product = :has_product, counted = 1, count_date = NOW() WHERE physical_count_bin_id = :physical_count_bin_id', cardinality::NONE, [':physical_count_bin_id' => $physical_count_bin_id, ':has_product' => $has_product]);
						}
						else {
							self::query_execute('INSERT INTO physical_count_bins (physical_count_id, bin_number, has_product, counted, count_date) VALUES (:physical_count_id, :bin_number, :has_product, 1, NOW())', cardinality::NONE, [':physical_count_id' => $physical_count_id, ':bin_number' => $bin_number, ':has_product' => $has_product]);
						}

						if ($ipn_identifier == 'ipn') {
							$ipn_data = self::query_fetch('SELECT psc.stock_id, psc.stock_name as ipn, psc.serialized, psc.average_cost, psc.stock_quantity as quantity, psce.stock_location as bin1, psce.stock_location_2 as bin2, pci.physical_count_ipn_id, pci.counted FROM products_stock_control psc LEFT JOIN products_stock_control_extra psce ON psc.stock_id = psce.stock_id LEFT JOIN physical_count_ipns pci ON psc.stock_id = pci.stock_id WHERE psc.stock_name LIKE :ipn', cardinality::ROW, [':ipn' => $ipn]);
						}
						elseif ($ipn_identifier == 'stock_id') {
							$ipn_data = self::query_fetch('SELECT psc.stock_id, psc.stock_name as ipn, psc.serialized, psc.average_cost, psc.stock_quantity as quantity, psce.stock_location as bin1, psce.stock_location_2 as bin2, pci.physical_count_ipn_id, pci.counted FROM products_stock_control psc LEFT JOIN products_stock_control_extra psce ON psc.stock_id = psce.stock_id LEFT JOIN physical_count_ipns pci ON psc.stock_id = pci.stock_id WHERE psc.stock_id LIKE :stock_id', cardinality::ROW, [':stock_id' => $ipn]);
						}

						if ($ipn_data['serialized'] == 1) {
							$serialized[$row_idx] = $row;
							continue; // we're just straight up ignoring them for now
						}

						$ipn = $ipn_data['ipn'];
						$stock_id = $ipn_data['stock_id'];

						if ($qty_type == 'direct') {
							$count = $qty;
							$difference = $count - $ipn_data['quantity'];
						}
						elseif ($qty_type == 'difference') {
							$difference = $qty;
							$count = $ipn_data['quantity'] + $difference;
						}

						if (!empty($ipn_data['counted'])) {
							self::query_execute('UPDATE physical_count_ipns SET counted = counted + 1 WHERE physical_count_ipn_id = :physical_count_ipn_id', cardinality::NONE, [':physical_count_ipn_id' => $ipn_data['physical_count_ipn_id']]);
						}
						elseif (empty($ipn_data['stock_id'])) {
							$missing_ipns[] = $ipn;
						}
						else {
							if (!empty($ipn_data['physical_count_ipn_id'])) {
								self::query_execute('UPDATE physical_count_ipns SET system_count_at_entry = :quantity, count = :count, difference = :difference, system_bin1_at_entry = :bin1, system_bin2_at_entry = :bin2, bin_number = :bin_number, counted = 1, count_date = NOW() WHERE physical_count_ipn_id = :physical_count_ipn_id', cardinality::NONE, [':quantity' => $ipn_data['quantity'], ':count' => $count, ':difference' => $difference, ':bin1' => $ipn_data['bin1'], ':bin2' => $ipn_data['bin2'], ':bin_number' => $bin_number, ':physical_count_ipn_id' => $ipn_data['physical_count_ipn_id']]);
							}
							else {
								self::query_execute('INSERT INTO physical_count_ipns (physical_count_id, stock_id, system_count_at_start, system_count_at_entry, count, difference, system_bin1_at_start, system_bin2_at_start, system_bin1_at_entry, system_bin2_at_entry, bin_number, counted, count_date) VALUES (:physical_count_id, :stock_id, 0, :quantity, :count, :difference, NULL, NULL, :bin1, :bin2, :bin_number, 1, NOW())', cardinality::NONE, [':physical_count_id' => $physical_count_id, ':stock_id' => $stock_id, ':quantity' => $ipn_data['quantity'], ':count' => $count, ':difference' => $difference, ':bin1' => $ipn_data['bin1'], ':bin2' => $ipn_data['bin2'], ':bin_number' => $bin_number]);
							}

							$update_bins = FALSE;

							if (strtoupper($ipn_data['bin1']) != strtoupper($bin_number)) {
								$update_bins = TRUE;
								$bin1_change_type_id = 7;
								self::execute('insert_psc_change_history', [':stock_id' => $stock_id, ':admin_email' => $admin_email, ':change_type_id' => $bin1_change_type_id, ':old_qty' => $ipn_data['bin1'], ':new_qty' => $bin_number]);
							}
							if (!empty($ipn_data['bin2'])) {
								$update_bins = TRUE;
								$bin2_change_type_id = 10;
								self::execute('insert_psc_change_history', [':stock_id' => $stock_id, ':admin_email' => $admin_email, ':change_type_id' => $bin2_change_type_id, ':old_qty' => $ipn_data['bin2'], ':new_qty' => '']);
							}

							if ($update_bins) self::query_execute("UPDATE products_stock_control_extra SET stock_location = :bin1, stock_location_2 = '' WHERE stock_id = :stock_id", cardinality::NONE, [':bin1' => $bin_number, ':stock_id' => $stock_id]);

							if ($difference != 0) {
								$new_avg_cost = $this->refigure_avg_cost($ipn_data['average_cost'], $ipn_data['quantity'], $count);

								self::execute('update_qty_cost', [':difference' => $difference, ':average_cost' => $new_avg_cost, ':stock_id' => $stock_id]);

								if ($difference > 0) {
									// both type and reason are the same for gain/found
									$type_reason = 4;
									$ia_cost = '';
									$old_avg_cost = $ipn_data['average_cost'];
									// $new_avg_cost is already what it's supposed to be
								}
								else {
									// both type and reason are the same for lost
									$type_reason = 3;
									$ia_cost = $new_avg_cost;
									$old_avg_cost = '';
									$new_avg_cost = '';
								}

								self::execute('add_ipn_change_history', [':type' => 'pcount', ':stock_id' => $stock_id, ':admin_id' => $_SESSION['login_id'], ':status' => ($difference>0?1:0), ':quantity' => abs($difference)]);

								self::execute('insert_inventory_adjustment', [':stock_id' => $stock_id, ':admin_id' => $_SESSION['login_id'], ':type_reason' => $type_reason, ':original_qty' => $ipn_data['quantity'], ':new_qty' => $count, ':change_cost' => $ia_cost, ':original_avg_cost' => $old_avg_cost, ':new_avg_cost' => $new_avg_cost]);

								$quantity_change_type_id = 4;
								self::execute('insert_psc_change_history', [':stock_id' => $stock_id, ':admin_email' => $admin_email, ':change_type_id' => $quantity_change_type_id, ':old_qty' => $ipn_data['quantity'], ':new_qty' => $count]);
							}
							$quantity_confirmation_type_id = 5;
							self::execute('insert_psc_change_history', [':stock_id' => $stock_id, ':admin_email' => $admin_email, ':change_type_id' => $quantity_confirmation_type_id, ':old_qty' => $count, ':new_qty' => $count]);
						}
					}

					self::query_execute('DELETE FROM purchase_order_to_order_allocations WHERE purchase_order_product_id = 0');

					self::transaction_commit();
				}
				catch (Exception $e) {
					self::transaction_rollback();
					echo 'There was a database error - this count was not recorded: ['.$e->getMessage().']';
				}

				if (!empty($serialized)) var_dump($serialized);
				break;
			case 'finish':
				$physical_count_id = $_GET['physical_count_id'];

				self::query_execute("UPDATE products_stock_control_extra SET stock_location = '', stock_location_2 = '' WHERE stock_id IN (SELECT stock_id FROM products_stock_control WHERE serialized = 0 AND stock_quantity <= 0)");

				self::query_execute('UPDATE physical_counts SET start_date = NOW() WHERE physical_count_id = :physical_count_id', cardinality::NONE, [':physical_count_id' => $physical_count_id]);

				//$req = new request();
				//$req->get('www.cablesandkits.com/feed_service.php?s=cainventory');

				$page = '/admin/physical-count.php';
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
			case 'prefill':
				$ipns = self::query_fetch('SELECT DISTINCT UPPER(stock_name) as ipn FROM products_stock_control ORDER BY stock_name ASC', cardinality::COLUMN, []);
				$response['ipns'] = array_count_values($ipns);

				$serials = self::query_fetch('SELECT DISTINCT UPPER(s.serial) as serial, UPPER(psc.stock_name) as ipn FROM serials s JOIN products_stock_control psc ON s.ipn = psc.stock_id WHERE s.status IN (2,3,6)', cardinality::SET, []);
				$response['serials'] = [];
				foreach ($serials as $serial) {
					$response['serials'][$serial['serial']] = $serial['ipn'];
				}
				break;
			case 'add-complete':
			case 'add-next':
				$bin_number = $_POST['bin_number'];
				$ipn = !empty($_POST['ipn'])?$_POST['ipn']:NULL;
				$serial = $_POST['serial'];

				$physical_count_id = $_POST['physical_count_id'];

				self::transaction_begin();

				try {
					if ($serial == '[[EMPTY]]') {
						if ($physical_count_bin_id = self::query_fetch('SELECT physical_count_bin_id FROM physical_count_bins WHERE bin_number LIKE :bin_number', cardinality::SINGLE, [':bin_number' => $bin_number])) {
							self::query_execute('UPDATE physical_count_bins SET has_product = 0, counted = 1, count_date = NOW() WHERE physical_count_bin_id = :physical_count_bin_id', cardinality::NONE, [':physical_count_bin_id' => $physical_count_bin_id]);
						}
						else {
							self::query_execute('INSERT INTO physical_count_bins (physical_count_id, bin_number, has_product, counted, count_date) VALUES (:physical_count_id, :bin_number, 0, 1, NOW())', cardinality::NONE, [':physical_count_id' => $physical_count_id, ':bin_number' => $bin_number]);
						}
					}
					elseif (!empty($serial)) {
						if ($physical_count_bin_id = self::query_fetch('SELECT physical_count_bin_id FROM physical_count_bins WHERE bin_number LIKE :bin_number', cardinality::SINGLE, [':bin_number' => $bin_number])) {
							self::query_execute('UPDATE physical_count_bins SET has_product = 1, counted = 1, count_date = NOW() WHERE physical_count_bin_id = :physical_count_bin_id', cardinality::NONE, [':physical_count_bin_id' => $physical_count_bin_id]);
						}
						else {
							self::query_execute('INSERT INTO physical_count_bins (physical_count_id, bin_number, has_product, counted, count_date) VALUES (:physical_count_id, :bin_number, 1, 1, NOW())', cardinality::NONE, [':physical_count_id' => $physical_count_id, ':bin_number' => $bin_number]);
						}

						$ipn_data = self::query_fetch('SELECT s.id as serial_id, s.ipn as stock_id, CASE WHEN s.status IN (2,3,6) THEN 1 ELSE 0 END as quantity, sh.bin_location, psce.stock_location, psce.stock_location_2, pci.physical_count_ipn_id, pci.counted FROM serials s LEFT JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id LEFT JOIN products_stock_control_extra psce ON s.ipn = psce.stock_id LEFT JOIN physical_count_ipns pci ON s.id = pci.serial_id WHERE s.serial LIKE :serial AND sh0.id IS NULL', cardinality::ROW, [':serial' => $serial]);

						$stock_id = $ipn_data['stock_id'];

						if (!empty($ipn_data['counted'])) {
							$response['err'] = 'This serial number has already been counted';
						}
						else {
							if (!empty($ipn_data['physical_count_ipn_id'])) {
								self::query_execute('UPDATE physical_count_ipns SET system_count_at_entry = :quantity, count = 1, difference = 1 - :quantity, system_bin1_at_entry = :stock_location, system_bin2_at_entry = :stock_location_2, system_binserial_at_entry = :bin_location, bin_number = :bin_number, counted = 1, count_date = NOW() WHERE physical_count_ipn_id = :physical_count_ipn_id', cardinality::NONE, [':quantity' => $ipn_data['quantity'], ':stock_location' => $ipn_data['stock_location'], ':stock_location_2' => $ipn_data['stock_location_2'], ':bin_location' => $ipn_data['bin_location'], ':bin_number' => $bin_number, ':physical_count_ipn_id' => $ipn_data['physical_count_ipn_id']]);

								if ($ipn_data['quantity'] > 0) self::query_execute('UPDATE serials_history SET bin_location = :bin_number, confirmation_date = NOW() WHERE serial_id = :serial_id', cardinality::NONE, [':bin_number' => $bin_number, ':serial_id' => $ipn_data['serial_id']]);
								else {
									/*self::query_execute("INSERT INTO serials_history (serial_id, entered_date, pors_id, porp_id, bin_location, confirmation_date, cost, short_notes) VALUES (:serial_id, NOW(), 0, 0, :bin_number, NOW(), 0, 'Serial counted in Physical Count')", cardinality::NONE, [':serial_id' => $ipn_data['serial_id'], ':bin_number' => $bin_number]);
									self::query_execute('UPDATE serials SET status = 2 WHERE id = :serial_id', cardinality::NONE, [':serial_id' => $ipn_data['serial_id']]);*/
									$response['err'] = 'This serial needs to be received';
								}
							}
							elseif (!empty($ipn_data['serial_id'])) {
								self::query_execute('INSERT INTO physical_count_ipns (physical_count_id, stock_id, serial_id, system_count_at_start, system_count_at_entry, count, difference, system_bin1_at_start, system_bin2_at_start, system_binserial_at_start, system_bin1_at_entry, system_bin2_at_entry, system_binserial_at_entry, bin_number, counted, count_date) VALUES (:physical_count_id, :stock_id, :serial_id, 0, :quantity, 1, 1 - :quantity, NULL, NULL, NULL, :stock_location, :stock_location_2, :bin_location, :bin_number, 1, NOW())', cardinality::NONE, [':physical_count_id' => $physical_count_id, ':stock_id' => $ipn_data['stock_id'], ':serial_id' => $ipn_data['serial_id'], ':quantity' => $ipn_data['quantity'], ':stock_location' => $ipn_data['stock_location'], ':stock_location_2' => $ipn_data['stock_location_2'], ':bin_location' => $ipn_data['bin_location'], ':bin_number' => $bin_number]);

								if ($ipn_data['quantity'] > 0) self::query_execute('UPDATE serials_history SET bin_location = :bin_number, confirmation_date = NOW() WHERE serial_id = :serial_id', cardinality::NONE, [':bin_number' => $bin_number, ':serial_id' => $ipn_data['serial_id']]);
								else {
									/*self::query_execute("INSERT INTO serials_history (serial_id, entered_date, pors_id, porp_id, bin_location, confirmation_date, cost, short_notes) VALUES (:serial_id, NOW(), 0, 0, :bin_number, NOW(), 0, 'Serial counted in Physical Count')", cardinality::NONE, [':serial_id' => $ipn_data['serial_id'], ':bin_number' => $bin_number]);
									self::query_execute('UPDATE serials SET status = 2 WHERE id = :serial_id', cardinality::NONE, [':serial_id' => $ipn_data['serial_id']]);*/
									$response['err'] = 'This serial needs to be received';
								}
							}
							else {
								/*$stock_id = self::query_fetch('SELECT stock_id FROM products_stock_control WHERE stock_name LIKE :ipn', cardinality::SINGLE, [':ipn' => $ipn]);
								//$cost = self::query_fetch('SELECT sh.cost FROM serials s JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh0 ON s.id = sh0.serial_id AND sh.id < sh0.id WHERE s.ipn = :stock_id AND sh0.id IS NULL ORDER BY sh.entered_date DESC', cardinality::SINGLE, [':stock_id' => $stock_id]);

								self::query_execute('INSERT INTO serials (status, serial, ipn) VALUES (2, :serial, :stock_id)', cardinality::NONE, [':serial' => $serial, ':stock_id' => $stock_id]);
								$serial_id = self::fetch_insert_id();

								self::query_execute("INSERT INTO serials_history (serial_id, entered_date, pors_id, porp_id, bin_location, confirmation_date, cost, short_notes) VALUES (:serial_id, NOW(), 0, 0, :bin_number, NOW(), 0, 'Serial counted in Physical Count')", cardinality::NONE, [':serial_id' => $serial_id, ':bin_number' => $bin_number]);

								self::query_execute('INSERT INTO physical_count_ipns (physical_count_id, stock_id, serial_id, system_count_at_start, system_count_at_entry, count, difference, system_bin1_at_start, system_bin2_at_start, system_binserial_at_start, system_bin1_at_entry, system_bin2_at_entry, system_binserial_at_entry, bin_number, counted, count_date) VALUES (:physical_count_id, :stock_id, :serial_id, 0, 0, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, :bin_number, 1, NOW())', cardinality::NONE, [':physical_count_id' => $physical_count_id, ':stock_id' => $stock_id, ':serial_id' => $serial_id, ':bin_number' => $bin_number]);*/
								$response['err'] = 'This serial needs to be received';
							}

							//insert_psc_change_history($stock_id, 'Quantity Confirmation', 1, 1);
						}
					}

					$response['bin'] = $bin_number;
					$response['bin_style'] = $_POST['bin_style'];
					$response['ipn'] = $ipn;
					$response['serial'] = $serial;

					self::transaction_commit();
				}
				catch (Exception $e) {
					self::transaction_rollback();
					$response['err'] = 'There was a database error - this count was not recorded: ['.$e->getMessage().']';
				}
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

		$data['physical_counts'] = self::query_fetch('SELECT * FROM physical_counts ORDER BY physical_count_id DESC', cardinality::SET, []);

		$active_physical_count_id = NULL;

		foreach ($data['physical_counts'] as &$count) {
			if (empty($count['start_date'])) $count['startable'] = 1;
			if (!empty($count['start_date']) && empty($count['end_date'])) {
				$data['any_active?'] = 1;
				$count['active'] = 1;
				$active_physical_count_id = $count['physical_count_id'];
				$data['physical_count_id'] = $active_physical_count_id;
			}
		}

		if (!empty($_FILES['count_upload'])) {
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
			if ($_FILES['count_upload']['error'] !== UPLOAD_ERR_OK) {
				$data['upload']['err'] = $upload_status_map[$_FILES['count_upload']['error']];
			}
			else {
				$spreadsheet = new spreadsheet_import($_FILES['count_upload']);
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
		}

		if (!empty($active_physical_count_id)) {
			/*$data['ipns'] = self::query_fetch('SELECT pci.*, psc.stock_name as ipn, psc.serialized, s.serial FROM physical_count_ipns pci JOIN products_stock_control psc ON pci.stock_id = psc.stock_id LEFT JOIN serials s ON pci.serial_id = s.id WHERE pci.physical_count_id = :physical_count_id', cardinality::SET, [':physical_count_id' => $active_physical_count_id]);
			foreach ($data['ipns'] as &$ipn) {
				if ($ipn['serialized'] == 1) $ipn['serialized?'] = 1;
				if ($ipn['counted'] == 1) $ipn['counted?'] = 1;
			}*/

			/*$data['bins'] = self::query_fetch('SELECT * FROM physical_count_bins WHERE physical_count_id = :physical_count_id', cardinality::SET, [':physical_count_id' => $active_physical_count_id]);
			foreach ($data['bins'] as &$bin) {
				if ($bin['has_product'] == 1) $bin['has_product?'] = 1;
				if ($bin['counted'] == 1) $bin['counted?'] = 1;
			}*/
		}

		$this->render($this->page_templates['physical_count'], $data);
		$this->flush();
	}

	private function refigure_avg_cost($starting_avg, $old_qty, $new_qty) {
		if ($new_qty == 0 || $new_qty <= $old_qty) return $starting_avg;
		else return number_format((($starting_avg * $old_qty)/$new_qty), 2, '.', '');
	}
}
?>
