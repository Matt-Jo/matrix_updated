<?php
class feed_hydrianreceipts extends data_feed {

	public function __construct() {
		mb_internal_encoding('UTF-8'); // a stated requirement (the encoding, not the function call) of godatafeed
		$feed_namespace = 'hydrian_receipts__';
		$this->remove_old_feeds($this->file_repository, $feed_namespace, 2); // remove any feed older than 2 days

		$this->child_called = TRUE;
		$this->local_filename = $feed_namespace.date('Y-m-d__H-i-s').'.txt';

		$this->ftp_server = 'files.hydrian.com';
		$this->ftp_user = 'cablesandkits';
		$this->ftp_pass = 'qBT!ZmmVC!7kc%e7FMjz';
		$this->ftp_path = 'Daily_Data_Files/Cables_and_Kits/To_Hydrian';
		$this->destination_filename = 'hydrian-receipts.txt';

		parent::__construct(self::OUTPUT_SFTP, self::DELIM_TAB, self::FILE_TXT);

		$this->category_depth = 0;
	}

	public function __destruct() {
		parent::__destruct(); // write the file
	}

	private static $failed_loop = FALSE;

	private static function track_failure($stock_id, $reason) {
		self::loop_has_failed(TRUE);
		$insert = [':feed' => 'hydrian_receipts', ':stock_id' => $stock_id, ':reason' => $reason];
		prepared_query::execute('INSERT INTO ck_feed_failure_tracking (feed, stock_id, reason) VALUES (:feed, :stock_id, :reason)', $insert);
	}

	private static function loop_has_failed($status=NULL) {
		if (!empty($status)) return self::$failed_loop = TRUE;
		elseif (!empty(self::$failed_loop)) {
			self::$failed_loop = FALSE;
			return TRUE;
		}
		return FALSE;
	}

	public function build() {
		debug_tools::mark('Load receipts');
		$po_lines = prepared_query::fetch('SELECT * FROM (SELECT porp.id as purchase_order_received_product_id, pop.id as purchase_order_product_id, pop.ipn_id as stock_id, psc.stock_name as ipn, po.vendor as vendors_id, v.vendors_company_name as vendor, po.id as purchase_order_id, pors.id as receiving_session_id, po.expected_date, po.creation_date, po.submit_date, pors.`date` as receipt_date, po.drop_ship, pop.quantity as original_po_qty, NULL as open_po_qty, porp.quantity_received FROM purchase_order_received_products porp JOIN purchase_order_receiving_sessions pors ON porp.receiving_session_id = pors.id AND pors.`date` >= (DATE(NOW()) - INTERVAL 1 YEAR) JOIN purchase_orders po ON pors.purchase_order_id = po.id JOIN purchase_order_products pop ON porp.purchase_order_product_id = pop.id JOIN products_stock_control psc ON pop.ipn_id = psc.stock_id JOIN vendors v ON po.vendor = v.vendors_id UNION SELECT NULL as purchase_order_received_product_id, pop.id as purchase_order_product_id, pop.ipn_id as stock_id, psc.stock_name as ipn, po.vendor as vendors_id, v.vendors_company_name as vendor, po.id as purchase_order_id, NULL as receiving_session_id, po.expected_date, po.creation_date, po.submit_date, NULL as receipt_date, po.drop_ship, pop.quantity as original_po_qty, pop.quantity - IFNULL(SUM(porp.quantity_received), 0) as open_po_qty, NULL as quantity_received FROM purchase_order_products pop JOIN purchase_orders po ON pop.purchase_order_id = po.id AND po.status IN (1, 2) AND po.creation_date >= (DATE(NOW()) - INTERVAL 1 YEAR) JOIN vendors v ON po.vendor = v.vendors_id JOIN products_stock_control psc ON pop.ipn_id = psc.stock_id LEFT JOIN purchase_order_received_products porp ON pop.id = porp.purchase_order_product_id GROUP BY pop.id HAVING open_po_qty > 0) p ORDER BY submit_date ASC', cardinality::SET);

		debug_tools::mark('PO Line Count: '.count($po_lines));

		$this->header = [
			'export_date', // NOW()
			'stock_id',
			'ipn', // NOT REQUESTED - but I think it's useful
			'vendor_id',
			//'vendor_name', // for reporting
			'purchase_order_id',
			'receiving_session_id', // may be useful
			'created_date', // date PO was created in our system
			'purchase_date', // date PO was sent to vendor
			'expected_date', // date PO is expected to arrive
			'receipt_date', // date of the receipt of this qty
			'distribution_center', // BUFORD
			'drop_shipment', // for us, if it was received the same day it was sent, it was a drop shipment
			'original_po_qty', // what we originally purchased
			'open_po_qty', // the amount we've not yet received - open and received will be on separate lines
			'received_qty', // the amount we've received - open and received will be on separate lines
			'unit_of_measure', // always 1 for us, for now
			'uom_conversion_to_each', // always 1 for us, for now
			'context', // production or dev
		];

		$today = date('Y-m-d H:i:s');

		$config = service_locator::get_config_service();
		$context = service_locator::get_config_service()->is_production()?'PRODUCTION':'DEVELOPMENT';

		foreach ($po_lines as $index => &$po_line) {
			if ($index%2000 == 0) debug_tools::mark('Iteration '.$index);

			if (!ck_master_archetype::date_is_empty($po_line['creation_date'])) $po_line['creation_date'] = ck_master_archetype::DateTime($po_line['creation_date']);
			if (!ck_master_archetype::date_is_empty($po_line['submit_date'])) $po_line['submit_date'] = ck_master_archetype::DateTime($po_line['submit_date']);
			if (!ck_master_archetype::date_is_empty($po_line['receipt_date'])) $po_line['receipt_date'] = ck_master_archetype::DateTime($po_line['receipt_date']);
			if (!ck_master_archetype::date_is_empty($po_line['expected_date'])) $po_line['expected_date'] = ck_master_archetype::DateTime($po_line['expected_date']);
			else $po_line['expected_date'] = NULL;

			$row = [
				'export_date' => $today, // NOW()
				'stock_id' => $po_line['stock_id'],
				'ipn' => $po_line['ipn'], // NOT REQUESTED - but I think it's useful
				'vendor_id' => $po_line['vendors_id'],
				//'vendor_name' => $po_line['vendor'], // for reporting
				'purchase_order_id' => $po_line['purchase_order_id'],
				'receiving_session_id' => $po_line['receiving_session_id'], // may be useful
				'created_date' => !empty($po_line['creation_date'])?$po_line['creation_date']->format('Y-m-d'):NULL, // date PO was created in our system
				'purchase_date' => !empty($po_line['submit_date'])?$po_line['submit_date']->format('Y-m-d'):NULL, // date PO was sent to vendor
				'expected_date' => !empty($po_line['expected_date'])?$po_line['expected_date']->format('Y-m-d'):NULL, // date PO is expected to arrive
				'receipt_date' => !empty($po_line['receipt_date'])?$po_line['receipt_date']->format('Y-m-d'):NULL, // date of the receipt of this qty
				'distribution_center' => 'BUFORD', // BUFORD
				'drop_shipment' => $po_line['drop_ship'], // for us, if it was received the same day it was sent, it was a drop shipment
				'original_po_qty' => $po_line['original_po_qty'], // what we originally purchased
				'open_po_qty' => $po_line['open_po_qty'], // the amount we've not yet received - open and received will be on separate lines
				'received_qty' => $po_line['quantity_received'], // the amount we've received - open and received will be on separate lines
				'unit_of_measure' => 1, // always 1 for us, for now
				'uom_conversion_to_each' => 1, // always 1 for us, for now
				'context' => $context, // production or dev
			];

			$this->data[] = $row;
		}

		debug_tools::mark('Finished building data.');
	}
} ?>
