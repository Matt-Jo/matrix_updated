<?php
class feed_hydrianinventory extends data_feed {

	public function __construct() {
		mb_internal_encoding('UTF-8'); // a stated requirement (the encoding, not the function call) of godatafeed
		$feed_namespace = 'hydrian_inventory__';
		$this->remove_old_feeds($this->file_repository, $feed_namespace, 2); // remove any feed older than 2 days

		$this->child_called = TRUE;
		$this->local_filename = $feed_namespace.date('Y-m-d__H-i-s').'.txt';

		$this->ftp_server = 'files.hydrian.com';
		$this->ftp_user = 'cablesandkits';
		$this->ftp_pass = 'qBT!ZmmVC!7kc%e7FMjz';
		$this->ftp_path = 'Daily_Data_Files/Cables_and_Kits/To_Hydrian';
		$this->destination_filename = 'hydrian-items.txt';
	
		parent::__construct(self::OUTPUT_SFTP, self::DELIM_TAB, self::FILE_TXT);

		$this->category_depth = 0;
	}

	public function __destruct() {
		parent::__destruct(); // write the file
	}

	private static $failed_loop = FALSE;

	private static function track_failure($stock_id, $reason) {
		self::loop_has_failed(TRUE);
		$insert = [':feed' => 'hydrian_inventory', ':stock_id' => $stock_id, ':reason' => $reason];
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
		debug_tools::mark('Load inventory');
		$inventory = prepared_query::keyed_set_fetch('SELECT * FROM ckv_legacy_inventory', 'stock_id');

		debug_tools::mark('Load IPN list');
		$stock_ids = prepared_query::fetch('SELECT psc.stock_id FROM products_stock_control psc WHERE psc.is_bundle = 0 ORDER BY psc.stock_id ASC', cardinality::COLUMN);

		$this->header = [
			'export_date', // NOW()
			'stock_id',
			'ipn', // NOT REQUESTED - but I think it's useful
			'distribution_center', // BUFORD
			'qty_available', // positive availability
			'qty_on_order', // open PO qty
			'qty_backordered', // negative availability
			'qty_allocated_from_po', // open PO qty allocated to open sales orders
			'qty_in_conditioning', // on hold qty that is in conditioning/will be available
			'unit_cost', // this is our expected vendor price, if we have one, or the figured AVG cost if we don't
			'avg_cost', // this is our historical average cost
			'vendor_id', // preferred vendor record
			'expected_lead_time', // preferred vendor lead time
			'min_qty', // QUESTION
			'safety_stock_qty', // QUESTION
			'target_qty', // they have listed as max, but defined the way we define target // QUESTION
			'lot_size', // if we have for preferred vendor, I assume this is case qty?
			'qty_on_hand', // our on hand qty
			'unit_weight', // stored weight - do we trust this? what if we don't?
			'category', // IPN category, not merchandising category.  Is vertical useful?
			'group', // IPN group
			'materials', // is this a materials item or not
			'item_status', // conglomeration of discontinued, non-stock, dropship only, has any active products - their keys don't match up 1 to 1 with ours
			'secondary_vendor_id', // secondary vendor record
			'secondary_vendor_price', // secondary vendor price, if any
			'secondary_vendor_lot_size', // secondary vendor case qty if any
			'item_description', // IPN description
			'unit_of_measure', // always 1 for us, for now
			'uom_conversion_to_each', // always 1 for us, for now
			'ipn_creation_date', // date IPN was created
			'flag_discontinued', // is our discontinued flag set
			'flag_dropship', // is our dropship flag set
			'flag_nonstock', // is our nonstock flag set
			'flag_oversize', // is our freight flag set
			'context', // production or dev
		];

		debug_tools::mark('IPN Count: '.count($stock_ids));

		ck_ipn2::cache(FALSE);

		$today = date('Y-m-d H:i:s');

		function get_hydrian_status($ipn) {
			if ($ipn->is('discontinued')) return 'Discontinued';
			elseif ($ipn->is('drop_ship') || $ipn->is('non_stock')) return 'Non-Stock';
			elseif ($ipn->has_active_listings()) return 'Active';
			else return 'Inactive';
		}

		$config = service_locator::get_config_service();
		$context = service_locator::get_config_service()->is_production()?'PRODUCTION':'DEVELOPMENT';

		foreach ($stock_ids as $index => $stock_id) {
			if ($index%2000 == 0) debug_tools::mark('Iteration '.$index);
			$ipn = new ck_ipn2($stock_id);

			if (!$ipn->found()) {
				self::track_failure($stock_id, 'Basic Completeness Fail - could not instantiate IPN');
				self::loop_has_failed();
				continue;
			}

			$qty_available = 0;
			$qty_backordered = 0;
			if ($inventory[$stock_id]['available'] > 0) $qty_available = $inventory[$stock_id]['available'];
			else $qty_backordered = abs($inventory[$stock_id]['available']);

			$row = [
				'export_date' => $today, // NOW()
				'stock_id' => $stock_id,
				'ipn' => $ipn->get_header('ipn'), // NOT REQUESTED - but I think it's useful
				'distribution_center' => 'BUFORD', // BUFORD
				'qty_available' => $qty_available, // positive availability
				'qty_on_order' => $inventory[$stock_id]['on_order'], // open PO qty
				'qty_backordered' => $qty_backordered, // negative availability
				'qty_allocated_from_po' => $inventory[$stock_id]['po_allocated'], // open PO qty allocated to open sales orders
				'qty_in_conditioning' => $inventory[$stock_id]['in_conditioning'], // on hold qty that is in conditioning/will be available
				'unit_cost' => $ipn->get_expected_cost(), // this is our expected vendor price, if we have one, or the figured AVG cost if we don't
				'avg_cost' => $ipn->get_avg_cost(), // this is our historical average cost
				'vendor_id' => $ipn->get_header('vendors_id'), // preferred vendor record
				'expected_lead_time' => $ipn->get_header('lead_time'), // preferred vendor lead time
				'min_qty' => $ipn->get_header('min_inventory_level'), // QUESTION
				'safety_stock_qty' => NULL, // QUESTION
				'target_qty' => $ipn->get_header('target_inventory_level'), // they have listed as max, but defined the way we define target // QUESTION
				'lot_size' => $ipn->get_header('case_qty'), // if we have for preferred vendor, I assume this is case qty?
				'qty_on_hand' => $inventory[$stock_id]['on_hand'], // our on hand qty
				'unit_weight' => $ipn->get_header('stock_weight'), // stored weight - do we trust this? what if we don't?
				'category' => $ipn->get_header('ipn_category'), // IPN category, not merchandising category.  Is vertical useful?
				'group' => $ipn->get_header('ipn_group'), // IPN group
				'materials' => $ipn->get_header('products_stock_control_category_id')==90?1:0, // is this a materials item or not
				'item_status' => get_hydrian_status($ipn), // conglomeration of discontinued, non-stock, dropship only, has any active products - their keys don't match up 1 to 1 with ours
				'secondary_vendor_id' => NULL, // secondary vendor record
				'secondary_vendor_price' => NULL, // secondary vendor price, if any
				'secondary_vendor_lot_size' => NULL, // secondary vendor case qty if any
				'item_description' => preg_replace('/\s+/', ' ', $ipn->get_header('stock_description')), // IPN description
				'unit_of_measure' => 1, // always 1 for us, for now
				'uom_conversion_to_each' => 1, // always 1 for us, for now
				'ipn_creation_date' => $ipn->get_header('date_added')->format('Y-m-d'), // date IPN was created
				'flag_discontinued' => $ipn->is('discontinued')?1:0,
				'flag_dropship' => $ipn->is('drop_ship')?1:0,
				'flag_nonstock' => $ipn->is('non_stock')?1:0,
				'flag_oversize' => $ipn->is('freight')?1:0,
				'context' => $context,
			];

			if ($secondary_vendors = $ipn->get_vendors('secondary')) {
				$row['secondary_vendor_id'] = $secondary_vendors[0]['vendors_id'];
				$row['secondary_vendor_price'] = $secondary_vendors[0]['price'];
				$row['secondary_vendor_lot_size'] = $secondary_vendors[0]['case_qty'];
			}

			$this->data[] = $row;
		}

		debug_tools::mark('Finished building data.');
	}
} ?>
