<?php
class feed_hydrianconversions extends data_feed {

	public function __construct() {
		mb_internal_encoding('UTF-8'); // a stated requirement (the encoding, not the function call) of godatafeed
		$feed_namespace = 'hydrian_conversions__';
		$this->remove_old_feeds($this->file_repository, $feed_namespace, 2); // remove any feed older than 2 days

		$this->child_called = TRUE;
		$this->local_filename = $feed_namespace.date('Y-m-d__H-i-s').'.txt';

		$this->ftp_server = 'files.hydrian.com';
		$this->ftp_user = 'cablesandkits';
		$this->ftp_pass = 'qBT!ZmmVC!7kc%e7FMjz';
		$this->ftp_path = 'Daily_Data_Files/Cables_and_Kits/To_Hydrian';
		$this->destination_filename = 'hydrian-conversions.txt';
	
		parent::__construct(self::OUTPUT_SFTP, self::DELIM_TAB, self::FILE_TXT);

		$this->category_depth = 0;
	}

	public function __destruct() {
		parent::__destruct(); // write the file
	}

	private static $failed_loop = FALSE;

	private static function track_failure($stock_id, $reason) {
		self::loop_has_failed(TRUE);
		$insert = [':feed' => 'hydrian_conversions', ':stock_id' => $stock_id, ':reason' => $reason];
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
		debug_tools::mark('Load conversions');
		$conversions = prepared_query::fetch('SELECT MIN(ia.id) as conversion_id, ia.ipn_id as stock_id, psc.stock_name as ipn, ia.scrap_date as conversion_date, iar.description as adjustment_reason, iat.name as adjustment_type, ia.old_qty, ia.new_qty, ia.new_qty - ia.old_qty as change_qty FROM inventory_adjustment ia JOIN products_stock_control psc ON ia.ipn_id = psc.stock_id LEFT JOIN inventory_adjustment_reason iar ON ia.inventory_adjustment_reason_id = iar.id LEFT JOIN inventory_adjustment_type iat ON ia.inventory_adjustment_type_id = iat.id WHERE ia.scrap_date >= DATE(NOW()) - INTERVAL 1 YEAR AND ia.inventory_adjustment_reason_id = 9 AND ia.inventory_adjustment_type_id IN (5, 6) GROUP BY ia.ipn_id, ia.scrap_date, ia.inventory_adjustment_type_id, ia.old_qty, ia.new_qty ORDER BY ia.scrap_date ASC, ia.inventory_adjustment_type_id DESC, ia.ipn_id ASC', cardinality::SET);

		debug_tools::mark('Conversion Count: '.count($conversions));

		$this->header = [
			'export_date', // NOW()
			'stock_id',
			'ipn', // NOT REQUESTED - but I think it's useful
			'conversion_id', // the inventory adjustment ID
			'conversion_date', // when did the conversion occur
			'adjustment_reason', // we sent them all sorts of adjustments, we probably only want conversions or internal usage
			'adjustment_type', // this will allow us to match up negatives and positives
			'old_quantity', // this is in the adjustments table
			'new_quantity', // this is also in the adjustments table
			'qty_change', // this is figured from the previous entry
			'unit_of_measure', // always 1 for us, for now
			'uom_conversion_to_each', // always 1 for us, for now
			'context', // production or dev
		];

		$today = date('Y-m-d H:i:s');

		$config = service_locator::get_config_service();
		$context = service_locator::get_config_service()->is_production()?'PRODUCTION':'DEVELOPMENT';

		foreach ($conversions as $index => &$conversion) {
			if ($index%2000 == 0) debug_tools::mark('Iteration '.$index);

			if (!ck_master_archetype::date_is_empty($conversion['conversion_date'])) $conversion['conversion_date'] = ck_master_archetype::DateTime($conversion['conversion_date']);

			$row = [
				'export_date' => $today, // NOW()
				'stock_id' => $conversion['stock_id'],
				'ipn' => $conversion['ipn'], // NOT REQUESTED - but I think it's useful
				'conversion_id' => $conversion['conversion_id'], // the inventory adjustment ID
				'conversion_date' => $conversion['conversion_date']->format('Y-m-d H:i:s'), // when did the conversion occur
				'adjustment_reason' => $conversion['adjustment_reason'], // we sent them all sorts of adjustments, we probably only want conversions or internal usage
				'adjustment_type' => $conversion['adjustment_type'], // this will allow us to match up negatives and positives
				'old_quantity' => $conversion['old_qty'], // this is in the adjustments table
				'new_quantity' => $conversion['new_qty'], // this is also in the adjustments table
				'qty_change' => $conversion['new_qty'] - $conversion['old_qty'], // this is figured from the previous entry
				'unit_of_measure' => 1, // always 1 for us, for now
				'uom_conversion_to_each' => 1, // always 1 for us, for now
				'context' => $context, // production or dev
			];

			$this->data[] = $row;
		}

		debug_tools::mark('Finished building data.');
	}
} ?>
