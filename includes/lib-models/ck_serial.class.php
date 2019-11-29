<?php
class ck_serial extends ck_archetype {

	protected static $skeleton_type = 'ck_serial_type';

	protected static $queries = [
		'serial_header' => [
			'qry' => 'SELECT s.id as serial_id, s.serial as serial_number, s.status as status_code, ss.name as status, ss.weight as status_sort_order, s.ipn as stock_id FROM serials s JOIN serials_status ss ON s.status = ss.id WHERE (:serial_id IS NOT NULL AND s.id = :serial_id) OR (:serial_number IS NOT NULL AND s.serial LIKE :serial_number)',
			'cardinality' => cardinality::ROW,
		],

		'reservation' => [
			'qry' => 'SELECT serials_assignment_id, orders_products_id, admin_id, assignment_date FROM serials_assignments WHERE serial_id = :serial_id AND fulfilled = 0',
			'cardinality' => cardinality::ROW
		],

		'history' => [
			'qry' => 'SELECT sh.id as serial_history_id, sh.entered_date, sh.shipped_date, sh.conditions as condition_code, sc.text as `condition`, sh.order_id as orders_id, sh.order_product_id as orders_products_id, sh.po_number, sh.pors_id as purchase_order_receiving_sessions_id, sh.pop_id as purchase_order_products_id, sh.porp_id as purchase_order_received_products_id, pop.purchase_order_id, sh.dram, sh.flash, sh.image, sh.ios, sh.mac_address, sh.version, sh.cost, sh.transfer_price, sh.transfer_date, sh.show_version, sh.short_notes, sh.bin_location, sh.confirmation_date, sh.rma_id, sh.tester_admin_id FROM serials_history sh LEFT JOIN serials_configs sc ON sh.conditions = sc.id LEFT JOIN purchase_order_products pop ON sh.pop_id = pop.id WHERE sh.serial_id = ? ORDER BY sh.id DESC',
			'cardinality' => cardinality::SET,
		],

		'hold' => [
			'qry' => 'SELECT ih.id as inventory_hold_id, ih.quantity, ih.reason_id as hold_reason_id, ihr.description as hold_reason, `date` as hold_created_date, ih.notes, ih.creator_id FROM inventory_hold ih LEFT JOIN inventory_hold_reason ihr ON ih.reason_id = ihr.id WHERE ih.serial_id = :serial_id',
			'cardinality' => cardinality::ROW
		],

		'serial_header_list' => [
			'qry' => 'SELECT s.id as serial_id, s.serial as serial_number, s.status as status_code, ss.name as status, ss.weight as status_sort_order, s.ipn as stock_id FROM serials s JOIN serials_status ss ON s.status = ss.id WHERE s.ipn = :stock_id ORDER BY ss.weight ASC',
			'cardinality' => cardinality::SET,
		],

		'last_history_list' => [
			'qry' => 'SELECT sh.id as serial_history_id, sh.serial_id, sh.entered_date, sh.shipped_date, sh.conditions as condition_code, sc.text as `condition`, sh.order_id as orders_id, sh.order_product_id as orders_products_id, sh.po_number, sh.pors_id as purchase_order_receiving_sessions_id, sh.pop_id as purchase_order_products_id, sh.porp_id as purchase_order_received_products_id, pop.purchase_order_id, sh.dram, sh.flash, sh.image, sh.ios, sh.mac_address, sh.version, sh.cost, sh.transfer_price, sh.transfer_date, sh.show_version, sh.short_notes, sh.bin_location, sh.confirmation_date, sh.rma_id, sh.tester_admin_id FROM serials s JOIN ckv_latest_serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_configs sc ON sh.conditions = sc.id LEFT JOIN purchase_order_products pop ON sh.pop_id = pop.id WHERE s.ipn = :stock_id ORDER BY sh.entered_date ASC',
			'cardinality' => cardinality::SET,
		],
	];

	public static $statuses = [
		'RECEIVING' => 0,
		'RECEIVED' => 1, //not used anymore
		'INSTOCK' => 2,
		'ALLOCATED' => 3,
		'INVOICED' => 4,
		'HOLD' => 6,
		// the above is what's used in receiving, we still need to define the rest
	];

	// using the generic ck_type for type hinting allows for some limited use of duck typing
	public function __construct($serial_id, ck_serial_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($serial_id);

		if (!$this->skeleton->built('serial_id')) $this->skeleton->load('serial_id', $serial_id);

		self::register($serial_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('serial_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function build_header() {
		$this->skeleton->load('header', self::fetch('serial_header', [':serial_id' => $this->id(), ':serial_number' => NULL]));
	}

	private function build_ipn() {
		// because load() ignores keys that don't fit the format of the target type, we can
		// load the whole header in since that's where the data is coming from
		$this->skeleton->load('ipn', new ck_ipn2($this->get_header('stock_id')));
	}

	private function build_reservation() {
		$reservation = self::fetch('reservation', [':serial_id' => $this->id()]);
		if (!empty($reservation)) {
			$reservation['order'] = ck_sales_order::get_order_by_orders_products_id($reservation['orders_products_id']);

			if (empty($reservation['order'])) {
				$this->unreserve($reservation['orders_products_id']);
				$reservation = NULL;
			}
			else {
				$reservation['assignment_date'] = ck_datetime::datify($reservation['assignment_date']);
				if (!empty($reservation['admin_id'])) $reservation['admin'] = new ck_admin($reservation['admin_id']);
			}
		}
		$this->skeleton->load('reservation', $reservation);
	}

	private function build_history() {
		$history = self::fetch('history', [$this->id()]);

		foreach ($history as &$record) {
			if (!self::date_is_empty($record['entered_date'])) $record['entered_date'] = self::DateTime($record['entered_date']);
			else $record['entered_date'] = NULL;
			if (!self::date_is_empty($record['shipped_date'])) $record['shipped_date'] = self::DateTime($record['shipped_date']);
			else $record['shipped_date'] = NULL;
			if (!self::date_is_empty($record['confirmation_date'])) $record['confirmation_date'] = self::DateTime($record['confirmation_date']);
			else $record['confirmation_date'] = NULL;
		}

		$this->skeleton->load('history', $history);
	}

	private function build_hold() {
		$hold = self::fetch('hold', [':serial_id' => $this->id()]);
		if (!empty($hold)) $hold['creator'] = new ck_admin($hold['creator_id']);

		$this->skeleton->load('hold', $hold);
	}

	private function build_last_po() {
		$po_id = $this->get_current_history('purchase_order_id');
		$po = NULL;

		if (!empty($po_id)) $po = new ck_purchase_order($po_id);

		$this->skeleton->load('last_po', $po);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	public function get_ipn() {
		if (!$this->skeleton->built('ipn')) $this->build_ipn();
		return $this->skeleton->get('ipn');
	}

	public function has_reservation() {
		if (!$this->skeleton->built('reservation')) $this->build_reservation();
		return $this->skeleton->has('reservation');
	}

	public function get_reservation($key=NULL) {
		if (!$this->has_reservation()) return NULL;
		elseif (empty($key)) return $this->skeleton->get('reservation');
		else return $this->skeleton->get('reservation', $key);
	}

	public function is_reserved_to($orders_products_id) {
		if (!$this->has_reservation()) return FALSE;
		else return $this->get_reservation('orders_products_id') == $orders_products_id;
	}

	// all serials should have history unless we're deleting it
	public function has_history() {
		if (!$this->skeleton->built('history')) $this->build_history();
		return $this->skeleton->has('history');
	}

	public function get_history($key=NULL) {
		if (!$this->has_history()) return NULL;
		if (empty($key)) return $this->skeleton->get('history');
		elseif ($key == 'current') return $this->skeleton->get('history')[0];
		else {
			foreach ($this->skeleton->get('history') as $hr) {
				if ($key == $hr['serial_history_id']) return $hr;
			}
		}
		return NULL;
	}

	public function get_current_history($key=NULL) {
		if (!$this->has_history()) return NULL;
		elseif (is_null($key)) return $this->get_history('current');
		else {
			$current_history = $this->get_history('current');
			return $current_history[$key];
		}
	}

	public function has_hold() {
		if (!$this->skeleton->built('hold')) $this->build_hold();
		return $this->skeleton->has('hold');
	}

	public function get_hold($key=NULL) {
		if (!$this->has_hold()) return NULL;
		if (empty($key)) return $this->skeleton->get('hold');
		else return $this->skeleton->get('hold', $key);
	}

	public function get_invoiced_cost($orders_products_id) {
		$histories = $this->get_history();
		foreach ($histories as $history) {
			if ($history['orders_products_id'] == $orders_products_id) return $history['cost'];
		}
	}

	public function has_last_po() {
		if (!$this->skeleton->built('last_po')) $this->build_last_po();
		return $this->skeleton->has('last_po');
	}

	public function get_last_po() {
		if (!$this->has_last_po()) return NULL;
		return $this->skeleton->get('last_po');
	}

	public static function get_serials_by_stock_id($stock_id) {
		if ($stock_id && ($headers = self::fetch('serial_header_list', [':stock_id' => $stock_id]))) {

			$hists = [];

			if ($histories = self::fetch('last_history_list', [':stock_id' => $stock_id])) {
				foreach ($histories as $record) {
					$record['entered_date'] = ck_datetime::datify($record['entered_date']);
					$record['shipped_date'] = ck_datetime::datify($record['shipped_date']);
					$record['confirmation_date'] = ck_datetime::datify($record['confirmation_date']);
					$hists[$record['serial_id']] = $record;
				}
				unset($histories);
			}

			$serials = [];
			foreach ($headers as $idx => $header) {
				$skeleton = self::get_record($header['serial_id']); // if we've already instantiated it, well, oh well
				if (!$skeleton->built('header')) $skeleton->load('header', $header);
				if (!$skeleton->built('history') && !empty($hists[$header['serial_id']])) $skeleton->load('history', [$hists[$header['serial_id']]]);
				unset($hists[$header['serial_id']]);

				$serials[$header['serial_id']] = new self($header['serial_id'], $skeleton);
				unset($headers[$idx]);
			}

			return $serials;
		}
		else return [];
	}

	public static function get_serial_by_serial($serial_number) {
		if ($serial_number && ($header = self::fetch('serial_header', [':serial_id' => NULL, ':serial_number' => $serial_number]))) {
			$skeleton = self::get_record($header['serial_id']); // if we've already instantiated it, well, oh well
			if (!$skeleton->built('header')) $skeleton->load('header', $header);

			return new self($header['serial_id'], $skeleton);
		}
		else return NULL;
	}

	public static function get_serials_by_serial_match($serial_number, $stock_id=NULL) {
		$serials = [];

		if ($serial_number && ($serial_ids = prepared_query::fetch('SELECT id FROM serials WHERE serial LIKE :serial_number AND (:stock_id IS NULL OR ipn = :stock_id)', cardinality::COLUMN, [':serial_number' => $serial_number.'%', ':stock_id' => $stock_id]))) {
			foreach ($serial_ids as $serial_id) {
				$serials[] = new self($serial_id);
			}
		}

		return $serials;
	}

	public static function get_reservable_serials_by_serial_match($serial_number, $stock_id=NULL) {
		$serials = [];

		if ($serial_ids = prepared_query::fetch('SELECT s.id FROM serials s LEFT JOIN serials_assignments sa ON s.id = sa.serial_id WHERE s.status IN (0, 2, 6) AND sa.serials_assignment_id IS NULL AND s.serial LIKE :serial_number AND (:stock_id IS NULL OR s.ipn = :stock_id)', cardinality::COLUMN, [':serial_number' => $serial_number.'%', ':stock_id' => $stock_id])) {
			foreach ($serial_ids as $serial_id) {
				$serials[] = new self($serial_id);
			}
		}

		return $serials;
	}

	public static function get_serial_by_history_id($serial_history_id) {
		if (!empty($serial_history_id) && ($serial_id = self::query_fetch('SELECT serial_id FROM serials_history WHERE id = :serial_history_id', cardinality::SINGLE, [':serial_history_id' => $serial_history_id]))) {
			return new self($serial_id);
		}
		else return NULL;
	}

	public static function get_all_serials_with_history_by_orders_products_id($orders_products_id) {
		$serials = [];

		// in this case, we want a serial with only one specific history record, determined by the order product it's attached to
		if ($serial_histories = prepared_query::fetch('SELECT sh.id as serial_history_id, sh.serial_id, sh.entered_date, sh.shipped_date, sh.conditions as condition_code, sc.text as `condition`, sh.order_id as orders_id, sh.order_product_id as orders_products_id, sh.po_number, sh.pors_id as purchase_order_receiving_sessions_id, sh.pop_id as purchase_order_products_id, sh.porp_id as purchase_order_received_products_id, sh.dram, sh.flash, sh.image, sh.ios, sh.mac_address, sh.version, sh.cost, sh.transfer_price, sh.transfer_date, sh.show_version, sh.short_notes, sh.bin_location, sh.confirmation_date, sh.rma_id, sh.tester_admin_id FROM serials_history sh LEFT JOIN serials_configs sc ON sh.conditions = sc.id WHERE sh.order_product_id = :orders_products_id', cardinality::SET, [':orders_products_id' => $orders_products_id])) {
			foreach ($serial_histories as $sh) {
				$srl = self::get_record($sh['serial_id']);

				if (!$srl->built('history')) {
					if (!self::date_is_empty($sh['entered_date'])) $sh['entered_date'] = self::DateTime($sh['entered_date']);
					else $sh['entered_date'] = NULL;

					if (!self::date_is_empty($sh['shipped_date'])) $sh['shipped_date'] = self::DateTime($sh['shipped_date']);
					else $sh['shipped_date'] = NULL;

					if (!self::date_is_empty($sh['confirmation_date'])) $sh['confirmation_date'] = self::DateTime($sh['confirmation_date']);
					else $sh['confirmation_date'] = NULL;

					$srl->load('history', [$sh]);
				}

				$serials[$sh['serial_id']] = new self($sh['serial_id'], $srl);
			}
		}

		return $serials;
	}

	public static function get_all_pickable_serials_by_stock_id_and_orders_id($stock_id, $orders_id) {
		$serials = [];

		if ($serial_ids = prepared_query::fetch('SELECT DISTINCT s.id FROM serials s LEFT JOIN ckv_latest_serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_assignments sa ON s.id = sa.serial_id AND sa.fulfilled = 0 LEFT JOIN orders_products op ON sa.orders_products_id = op.orders_products_id WHERE s.ipn = :stock_id AND (s.status = :instock OR (s.status = :allocated AND sh.order_id = :orders_id) OR (s.status NOT IN (:instock, :allocated) AND op.orders_id = :orders_id)) AND (sh.order_id IS NULL OR sh.order_id = :orders_id) AND (sa.serials_assignment_id IS NULL OR op.orders_id = :orders_id)', cardinality::COLUMN, [':stock_id' => $stock_id, ':orders_id' => $orders_id, ':instock' => self::$statuses['INSTOCK'], ':allocated' => self::$statuses['ALLOCATED']])) {
			foreach ($serial_ids as $serial_id) {
				$serials[] = new self($serial_id);
			}

			usort($serials, ['ck_serial', 'sort_picking_serials']);
		}

		return $serials;
	}

	public static function get_allocated_serials_by_orders_id($orders_id, $stock_id) {
		$serials = [];

		// in this case, we want a serial with only one specific history record, determined by the order product it's attached to
		if ($serial_ids = prepared_query::fetch('SELECT sh.serial_id FROM serials s JOIN ckv_latest_serials_history sh ON sh.serial_id = s.id WHERE (:stock_id IS NULL OR s.ipn = :stock_id) AND sh.order_id = :orders_id', cardinality::COLUMN, [':stock_id' => $stock_id, ':orders_id' => $orders_id])) {
			foreach ($serial_ids as $serial_id) {
				$serials[] = new self($serial_id);
			}
		}

		return $serials;
	}

	public static function get_all_claimed_serials_by_orders_products_id($orders_products_id) {
		$serials = [];

		if ($serial_ids = prepared_query::fetch('SELECT DISTINCT s.id FROM serials s LEFT JOIN ckv_latest_serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_assignments sa ON s.id = sa.serial_id AND sa.fulfilled = 0 WHERE (sh.order_product_id = :orders_products_id OR sa.orders_products_id = :orders_products_id)', cardinality::COLUMN, [':orders_products_id' => $orders_products_id])) {
			foreach ($serial_ids as $serial_id) {
				$serials[] = new self($serial_id);
			}
		}

		return $serials;
	}

	/*-------------------------------
	// modify
	-------------------------------*/

	public static function create(Array $data, $fail_on_duplicate=FALSE) {
		$savepoint = self::transaction_begin();

		try {
			$header = $data['header'];

			if (!isset($header['status'])) $header['status'] = self::$statuses['RECEIVING'];
			if (empty($header['ipn'])) throw new CKSerialException('You must give serial # ['.$header['serial'].'] an IPN.');
			if ($serial = self::get_serial_by_serial($header['serial'])) {
				if ($fail_on_duplicate) throw new CKSerialException('Serial # ['.$header['serial'].'] already exists.');
				elseif ($serial->get_header('stock_id') != $header['ipn']) throw new CKSerialException('Serial # ['.$header['serial'].'] already belongs to a different IPN ['.$serial->get_ipn()->get_header('ipn').'].');

				$serial_id = $serial->id();
				$serial->update_serial_status($header['status']);

				if (!empty($data['history'])) $serial->create_history_record($data['history']);
			}
			else {
				$params = new ezparams($header);
				self::query_execute('INSERT INTO serials ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, $params->query_vals(NULL, TRUE));
				$serial_id = self::fetch_insert_id();

				$serial = new self($serial_id);

				if (empty($data['history'])) $serial->create_history_record(['short_notes' => 'Automated History Record - No Details Available']);
				else $serial->create_history_record($data['history']);
			}

			self::transaction_commit($savepoint);
			return $serial;
		}
		catch (CKSerialException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKSerialException('Failed to create serial: '.$e->getMessage());
		}
	}

	public function remove() {
		$savepoint_id = self::transaction_begin();

		try {
			if ($this->has_history()) throw new CKSerialException('Cannot remove a serial number that still has history records associated.');
			//if ($this->has_hold()) throw new CKSerialException('Cannot remove a serial number that still has hold records associated.');
			if ($this->has_hold()) self::query_execute('DELETE FROM inventory_hold WHERE serial_id = :serial_id', cardinality::NONE, [':serial_id' => $this->id()]);

			self::query_execute('DELETE FROM serials WHERE id = :serial_id', cardinality::NONE, [':serial_id' => $this->id()]);

			$this->skeleton->rebuild();

			self::transaction_commit($savepoint_id);
		}
		catch (CKSerialException $e) {
			self::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint_id);
			throw new CKSerialException('Could not remove serial: '.$e->getMessage());
		}
	}

	private function update(Array $data) {
		$savepoint = self::transaction_begin();

		try {
			$params = new ezparams($data);
			self::query_execute('UPDATE serials SET '.$params->update_cols(TRUE).' WHERE id = :serial_id', cardinality::NONE, $params->query_vals(['serial_id' => $this->id()], TRUE));

			$this->skeleton->rebuild();

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKSerialException('Error updating purchase order: '.$e->getMessage());
		}
	}

	public function create_history_record(Array $data) {
		$savepoint_id = self::transaction_begin();

		try {
			$data['serial_id'] = $this->id();

			$current_history = $this->get_current_history();

			if (empty($data['rma_id']) && !empty($current_history) && $data['pop_id'] == $current_history['purchase_order_products_id']) {
				throw new CKSerialException('Cannot create duplicate history record for this Serial ['.$this->get_header('serial_number').'] on this PO Product');
			}

			if (empty($data['entered_date'])) $data['entered_date'] = self::NOW()->format('Y-m-d H:i:s');
			if (empty($data['conditions'])) $data['conditions'] = 41; // default to Preowned
			if (empty($data['bin_location'])) $data['bin_location'] = $this->get_ipn()->get_header('bin1');

			$params = new ezparams($data);
			self::query_execute('INSERT INTO serials_history ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, $params->query_vals([], TRUE));

			$this->skeleton->rebuild();

			self::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (CKSerialException $e) {
			self::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint_id);
			throw new CKSerialException('Failed to create serial history record: '.$e->getMessage());
		}
	}

	public function remove_history_record($serial_history_id) {
		$savepoint_id = self::transaction_begin();

		try {
			self::query_execute('DELETE FROM serials_history WHERE id = :serial_history_id', cardinality::NONE, [':serial_history_id' => $serial_history_id]);

			$this->skeleton->rebuild('history');

			self::transaction_commit($savepoint_id);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint_id);
			throw new CKSerialException('Could not remove serial history record: '.$e->getMessage());
		}
	}

	public function update_history_record($serial_history_id, Array $data) {
		$savepoint_id = self::transaction_begin();

		try {
			// whitelist fields
			$data = self::filter_fields($data, ['shipped_date', 'conditions', 'order_id', 'order_product_id', 'pors_id', 'dram', 'flash', 'mac_address', 'image', 'ios', 'version', 'cost', 'transfer_price', 'transfer_date', 'show_version', 'short_notes', 'bin_location', 'confirmation_date', 'tester_admin_id']);
			$params = new ezparams($data);
			self::query_execute('UPDATE serials_history SET '.$params->update_cols(TRUE).' WHERE id = :serial_history_id', cardinality::NONE, $params->query_vals(['serial_history_id' => $serial_history_id], TRUE));

			$this->skeleton->rebuild();

			self::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint_id);
			throw new CKSerialException('Failed to update serial history record: '.$e->getMessage());
		}
	}

	public function receive($receiving_session_id, $review_product) {
		$savepoint_id = self::transaction_begin();

		try {
			if ($this->get_header('status_code') == self::$statuses['INVOICED']) {} // do nothing - this shouldn't happen, though
			elseif ($this->has_hold()) $this->update_serial_status(self::$statuses['HOLD']);
			else $this->update_serial_status(self::$statuses['INSTOCK']);

			foreach ($this->get_history() as $history) {
				if ($history['purchase_order_received_products_id'] != $review_product['po_review_product_id']) continue;
				if ($history['purchase_order_products_id'] != $review_product['po_product_id']) continue;
				if (!empty($history['purchase_order_receiving_sessions_id'])) continue;

				$this->update_history_record($history['serial_history_id'], ['pors_id' => $receiving_session_id]);
				break;
			}

			self::transaction_commit($savepoint_id);
		}
		catch (CKSerialException $e) {
			self::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint_id);
			throw new CKSerialException('Could not receive serial: '.$e->getMessage());
		}
	}

	public function invoice() {
		$savepoint_id = self::transaction_begin();

		try {
			self::query_execute('UPDATE serials SET status = :invoiced WHERE id = :serial_id', cardinality::NONE, [':invoiced' => self::$statuses['INVOICED'], ':serial_id' => $this->id()]);

			$ch = $this->get_current_history();

			$updates = ['bin_location' => ''];
			if (empty($ch['transfer_price'])) {
				$updates['transfer_price'] = $this->get_ipn()->get_transfer_price();
				$updates['transfer_date'] = prepared_expression::NOW();
			}

			$updates = new prepared_fields($updates, prepared_fields::UPDATE_QUERY);
			$id = new prepared_fields(['id' => $ch['serial_history_id']]);

			// we just set all of the history records, because none of them have a bin - overkill in a properly designed system, but this is what I'm carrying forward from how it used to be
			self::query_execute("UPDATE serials_history SET ".$updates->update_sets()." WHERE ".$id->where_clause(), cardinality::NONE, prepared_fields::consolidate_parameters($updates, $id));

			$this->skeleton->rebuild();

			self::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint_id);
			throw new CKSerialException('Failed invoicing serial number: '.$e->getMessage());
		}
	}

	public function uninvoice() {
		$savepoint_id = self::transaction_begin();

		try {
			self::query_execute('UPDATE serials SET status = :allocated WHERE id = :serial_id', cardinality::NONE, [':allocated' => self::$statuses['ALLOCATED'], ':serial_id' => $this->id()]);

			// we don't manage bin, because we don't know where it should go anyway

			$this->skeleton->rebuild();

			self::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint_id);
			throw new CKSerialException('Failed uninvoicing serial number: '.$e->getMessage());
		}
	}

	public function unallocate() {
		$savepoint = self::transaction_begin();
		try {
			$status = self::$statuses['INSTOCK'];
			if (self::query_fetch('SELECT serial_id FROM inventory_hold WHERE serial_id = :serial_id', cardinality::SINGLE, [':serial_id' => $this->id()])) $status = self::$statuses['HOLD'];

			self::query_execute('UPDATE serials SET status = :status WHERE id = :serial_id', cardinality::NONE, [':status' => $status, ':serial_id' => $this->id()]);

			self::query_execute('UPDATE serials_history SET order_id = NULL, order_product_id = NULL WHERE id = :id', cardinality::NONE, [':id' => $this->get_current_history()['serial_history_id']]);

			$this->skeleton->rebuild();
			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKSerialException('Failed to unallocate serial: '.$e->getMessage());
		}
	}

	public function allocate($orders_id, $orders_products_id) {
		$savepoint = self::transaction_begin();
		try {
			if ($this->has_reservation() && !$this->is_reserved_to($orders_products_id)) {
				$rsrv = $this->get_reservation();
				if (empty($rsrv['order'])) $this->unreserve($orders_products_id);
				else throw new CKSerialException('Cannot allocate a serial that has already been reserved to another order line on order # '.$rsrv['order']->id());
			}

			self::query_execute('UPDATE serials SET status = :allocated WHERE id = :serial_id', cardinality::NONE, [':allocated' => self::$statuses['ALLOCATED'], ':serial_id' => $this->id()]);

			self::query_execute('UPDATE serials_history SET order_id = :order_id, order_product_id = :order_product_id WHERE id = :id', cardinality::NONE, [':order_id' => $orders_id, ':order_product_id' => $orders_products_id, ':id' => $this->get_current_history()['serial_history_id']]);

			$this->skeleton->rebuild();
			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKSerialException('Failed to allocate serial: '.$e->getMessage());
		}
	}

	public function reserve($orders_products_id, $admin_id=NULL) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if ($this->has_reservation()) throw new CKSerialException('Cannot reserve a serial that has already been reserved.');

			prepared_query::execute('INSERT INTO serials_assignments (serial_id, orders_products_id, admin_id) VALUES (:serial_id, :orders_products_id, :admin_id)', [':serial_id' => $this->id(), ':orders_products_id' => $orders_products_id, ':admin_id' => $admin_id]);

			$this->skeleton->rebuild('reservation');
		}
		catch (CKSerialException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKSerialException('Failed to assign serial.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function unreserve($orders_products_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('DELETE FROM serials_assignments WHERE serial_id = :serial_id AND orders_products_id = :orders_products_id AND fulfilled = 0', [':serial_id' => $this->id(), ':orders_products_id' => $orders_products_id]);

			$this->skeleton->rebuild('reservation');
		}
		catch (CKSerialException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKSerialException('Failed to unassign serial.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function fulfill_reservation($orders_products_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('UPDATE serials_assignments SET fulfilled = 1 WHERE serial_id = :serial_id AND orders_products_id = :orders_products_id AND fulfilled = 0', [':serial_id' => $this->id(), ':orders_products_id' => $orders_products_id]);

			$this->skeleton->rebuild('reservation');
		}
		catch (CKSerialException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKSerialException('Failed to fulfill reservation.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function update_serial_status($new_status) {
		$savepoint = self::transaction_begin();
		try {
			self::query_execute('UPDATE serials SET status = :new_status WHERE id = :serial_id', cardinality::NONE, [':new_status' => $new_status, ':serial_id' => $this->id()]);

			$this->skeleton->rebuild();
			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKSerialException('Failed changing serial number status: '.$e->getMessage());
		}
	}

	// sorting serials for picking - first allocated (picked), then assigned (unpicked), then highest cost then oldest receipt
	public static function sort_picking_serials($a, $b) {
		$ahistory = $a->get_current_history();
		$bhistory = $b->get_current_history();
		$areservation = $a->get_reservation();
		$breservation = $b->get_reservation();

		// we already know these serials are in context for picking, which means if they're allocated, we can assume they're allocated to the right order

		if ($ahistory['orders_id'] != $bhistory['orders_id']) return !empty($ahistory['orders_id'])?-1:1;
		elseif ($areservation != $breservation) return !empty($areservation)?-1:1;
		elseif ($ahistory['cost'] != $bhistory['cost']) return $ahistory['cost']>$bhistory['cost']?-1:1;
		elseif ($ahistory['entered_date'] != $bhistory['entered_date']) return $ahistory['entered_date']<$bhistory['entered_date']?-1:1;
		else return 0;
	}

	// open (unallocated) serials should be sorted by highest cost, then oldest receipt
	// we're only interested in the latest history record
	public static function sort_serials($a, $b) {
		$ahistory = $a->get_history()[0];
		$bhistory = $b->get_history()[0];

		if ($ahistory['cost'] != $bhistory['cost']) return $ahistory['cost']>$bhistory['cost']?-1:1;
		elseif ($ahistory['entered_date'] != $bhistory['entered_date']) return $ahistory['entered_date']<$bhistory['entered_date']?-1:1;
		else return 0;
	}

	// for display serials in the ipn editor, serials should be sorted by status, then oldest receipt
	// there should be only one history record, the latest, in this scenario
	public static function sort_display_serials($a, $b) {
		$asort = $a->get_header('status_sort_order');
		$bsort = $b->get_header('status_sort_order');
		$aage = $a->get_current_history('entered_date');
		$bage = $b->get_current_history('entered_date');

		if ($asort != $bsort) return $asort<$bsort?-1:1;
		elseif ($aage != $bage) return $aage<$bage?-1:1;
		else return 0;
	}
}

class CKSerialException extends CKMasterArchetypeException {
}
?>
