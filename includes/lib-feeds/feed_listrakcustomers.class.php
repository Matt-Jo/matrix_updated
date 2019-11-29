<?php
class feed_listrakcustomers extends data_feed {

	public function __construct() {
		mb_internal_encoding('UTF-8');
		$feed_namespace = 'listrakcustomers__';
		$this->remove_old_feeds($this->file_repository, $feed_namespace, 2);

		$this->child_called = TRUE;
		$this->local_filename = $feed_namespace.date('Y-m-d__h-i-s-a').'.txt';

		$this->ftp_server = "ftp.listrakbi.com";
		$this->ftp_user = "FAUser_CableandKits";
		$this->ftp_pass = "rlo075Vt8hL00ks";
		$this->ftp_path = "";
		$this->destination_filename = $feed_namespace.date('Y-m-d__h-i-s-a').'.txt';

		parent::__construct(self::OUTPUT_FTP, self::DELIM_TAB, self::FILE_CSV);

		$this->category_depth = 0;
		$this->category_hierarchy = TRUE;
		$this->needs_attributes = TRUE;
	}

	public function __destruct() {
		parent::__destruct(); //write the file
	}

	private static $failure_stmt = NULL;
	private static $failed_loop = FALSE;
	
	private static function loop_has_failed($status=NULL) {
		if (!empty($status)) return self::$failed_loop = TRUE;
		elseif (!empty(self::$failed_loop)) {
			self::$failed_loop = FALSE;
			return TRUE;
		}
		return FALSE;
	}

	public function build() {

		$customers = prepared_query::fetch('SELECT c.customers_id, c.customers_gender, c.customers_firstname, c.customers_lastname, c.customers_dob, c.customers_email_address, c.customers_telephone, c.vendor_id, cs.segment, cs.segment_code FROM customers c LEFT JOIN customer_segments cs ON c.customer_segment_id = cs.customer_segment_id WHERE c.sent_to_listrak = 0 ORDER BY c.customers_id ASC', cardinality::SET);

		if (empty($customers)) echo 'All customers have been marked as \'sent_to_listrak\'';
		
		/** Listrak required data: email **/
		$this->header = [
			'email',
			'first_name',
			'last_name',
			'address1',
			'address2',
			'address3',
			'city',
			'zip',
			'country',
			'home_phone',
			'mobile_phone',
			'preferred_store_number', //preferred brick & mortar store location
			'gender',
			'birthday',
			'customer_number',
			'registered', //does the customer have an account
			'meta1', //is customer a vendor 1 = yes 0 = no
			'meta2',
			'meta3',
			'meta4',
			'meta5'
		];

		echo count($customers).'<br>';

		$start = time();

		foreach ($customers as $idx => $customer) {
			
			$this->data[] = [
				'email' => $customer['customers_email_address'],
				'first_name' => $customer['customers_firstname'],
				'last_name' => $customer['customers_lastname'],
				'address1' => NULL,
				'address2' => NULL,
				'address3' => NULL,
				'city' => NULL,
				'zip' => NULL,
				'country' => NULL,
				'home_phone' => NULL,
				'mobile_phone' => NULL,
				'preferred_store_number' => NULL,
				'gender' => $customer['customers_gender'],
				'birthday' => NULL,
				'customer_number' => $customer['customers_telephone'],
				'registered' => NULL,
				'meta1' => $customer['vendor_id']>0?1:0, //is customer a vendor 1 = yes 0 = no
				'meta2' => $customer['segment_code'],
				'meta3' => NULL,
				'meta4' => NULL,
				'meta5' => NULL
			];
			
			prepared_query::execute('UPDATE customers SET sent_to_listrak = 1 WHERE customers_id = :customers_id', [':customers_id' => $customer['customers_id']]);

			if ($idx % 10000 == 0) echo 'checkpoint ('.$idx.')<br> Time elapsed: '.(time() - $start).' seconds<br>';
			flush();
		}
		echo '<br><span style=\'color:red\'>Completed in: '.(time() - $start).' seconds<br></span>'; 
	}
} ?>