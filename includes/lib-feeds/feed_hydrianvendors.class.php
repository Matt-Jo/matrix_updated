<?php
class feed_hydrianvendors extends data_feed {

	public function __construct() {
		mb_internal_encoding('UTF-8'); // a stated requirement (the encoding, not the function call) of godatafeed
		$feed_namespace = 'hydrian_vendors__';
		$this->remove_old_feeds($this->file_repository, $feed_namespace, 2); // remove any feed older than 2 days

		$this->child_called = TRUE;
		$this->local_filename = $feed_namespace.date('Y-m-d__H-i-s').'.txt';

		$this->ftp_server = 'files.hydrian.com';
		$this->ftp_user = 'cablesandkits';
		$this->ftp_pass = 'qBT!ZmmVC!7kc%e7FMjz';
		$this->ftp_path = 'Daily_Data_Files/Cables_and_Kits/To_Hydrian';
		$this->destination_filename = 'hydrian-vendors.txt';

		parent::__construct(self::OUTPUT_SFTP, self::DELIM_TAB, self::FILE_TXT);

		$this->category_depth = 0;
	}

	public function __destruct() {
		parent::__destruct(); // write the file
	}

	private static $failed_loop = FALSE;

	private static function track_failure($stock_id, $reason) {
		self::loop_has_failed(TRUE);
		$insert = [':feed' => 'hydrian_vendors', ':stock_id' => $stock_id, ':reason' => $reason];
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
		debug_tools::mark('Load vendors');
		$vendors = prepared_query::fetch('SELECT vendors_id, vendors_company_name, vendors_email_address FROM vendors ORDER BY vendors_company_name ASC', cardinality::SET);

		debug_tools::mark('Vendor Count: '.count($vendors));

		$this->header = [
			'export_date', // NOW()
			'vendor_id',
			'vendor_name',
			'vendor_email',
			'context', // production or dev
		];

		$today = date('Y-m-d H:i:s');

		$config = service_locator::get_config_service();
		$context = service_locator::get_config_service()->is_production()?'PRODUCTION':'DEVELOPMENT';

		foreach ($vendors as $index => $vendor) {
			if ($index%2000 == 0) debug_tools::mark('Iteration '.$index);

			$row = [
				'export_date' => $today, // NOW()
				'vendor_id' => $vendor['vendors_id'],
				'vendor_name' => $vendor['vendors_company_name'],
				'vendor_email' => $vendor['vendors_email_address'],
				'context' => $context, // production or dev
			];

			$this->data[] = $row;
		}

		debug_tools::mark('Finished building data.');
	}
} ?>
