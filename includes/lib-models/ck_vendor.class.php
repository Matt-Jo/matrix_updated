<?php
class ck_vendor extends ck_archetype {
	protected static $skeleton_type = 'ck_vendor_type';

	protected static $queries = [
		'vendor_header' => [
			'qry' => 'SELECT v.vendors_id, v.vendors_company_name as company_name, v.vendors_lastname as last_name, v.vendors_email_address as email_address, v.vendors_default_address_id as default_address_id, v.vendors_telephone as telephone, v.vendors_fax as fax, vendors_notes as account_notes, v.vendor_type, v.vendors_fedex as fedex_account_number, v.vendors_ups as ups_account_number, v.aim_screenname, v.msn_screenname, v.company_account_contact_name, v.company_account_contact_email, v.company_account_contact_phone_number, v.company_web_address, v.company_payment_terms as payment_terms, v.pm_to_admin_id as account_manager_id, a.admin_firstname as account_manager_firstname, a.admin_lastname as account_manager_lastname, a.admin_email_address as account_manager_email, vi.vendors_info_date_of_last_logon as last_login_date, vi.vendors_info_number_of_logons as number_of_logins, vi.vendors_info_date_account_created as date_account_created, vi.vendors_info_date_account_last_modified as last_modified FROM vendors v LEFT JOIN admin a ON v.pm_to_admin_id = a.admin_id LEFT JOIN vendors_info vi ON v.vendors_id = vi.vendors_info_id WHERE v.vendors_id = :vendors_id',
			'cardinality' => cardinality::ROW
		],

		'ipn_relationships' => [
			'qry' => 'SELECT id as vendors_to_stock_item_id, stock_id, vendors_price, vendors_pn as vendors_part_number, case_qty, always_avail as always_available, lead_time, notes, IFNULL(preferred, 0) as primary, IFNULL(secondary, 0) as secondary FROM vendors_to_stock_item WHERE vendors_id = :vendors_id',
			'cardinality' => cardinality::SET
		]
	];

	public function __construct($vendors_id, ck_vendor_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($vendors_id);

		if (!$this->skeleton->built('vendors_id')) $this->skeleton->load('vendors_id', $vendors_id);

		self::register($vendors_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('vendors_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$header = self::fetch('vendor_header', [':vendors_id' => $this->id()]);
		}
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		foreach (['date_account_created', 'last_modified', 'last_login_date'] as $field) {
			$header[$field] = ck_datetime::datify($header[$field]);
		}

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$this->skeleton->load('header', self::fetch('vendor_header', [':vendors_id' => $this->id()]));
		$this->normalize_header();
	}

	private function build_addresses() {
		$this->skeleton->load('addresses', ck_vendor_address::get_addresses_by_vendor($this->id()));
	}

	private function build_ipn_relationships() {
		$ipn_relationships = self::fetch('ipn_relationships', [':vendors_id' => $this->id()]);

		foreach ($ipn_relationships as &$ipn) {
			$ipn['ipn'] = new ck_ipn2($ipn['stock_id']);
		}

		$this->skeleton->load('ipn_relationships', $ipn_relationships);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	// we *probably* always have addresses, but we don't like to assume
	public function has_addresses() {
		if (!$this->skeleton->built('addresses')) $this->build_addresses();
		return $this->skeleton->has('addresses');
	}

	public function get_addresses($key=NULL) {
		if (!$this->has_addresses()) return NULL;
		if (empty($key)) return $this->skeleton->get('addresses');
		elseif ($key == 'default') return $this->get_default_address();
		else {
			$addresses = $this->skeleton->get('addresses');

			foreach ($addresses as $address) {
				if (is_numeric($key) && $address->get_header('address_book_id') == $key) return $address;
			}

			return NULL;
		}
	}

	public function get_default_address() {
		if (!$this->has_addresses()) return NULL;

		$addresses = $this->skeleton->get('addresses');
		foreach ($addresses as $address) {
			if ($address->is('default_address')) return $address;
		}

		// if we get here, just set the default as the first one in the list and return it
		$this->set_default_address($addresses[0]->id());
		return $address[0];
	}

	public function has_ipn_relationships($key=NULL) {
		if (!$this->skeleton->built('ipn_relationships')) $this->build_ipn_relationships();
		if (empty($key)) return $this->skeleton->has('ipn_relationships');
		else {
			$ipn_relationships = $this->skeleton->get('ipn_relationships');
			foreach ($ipn_relationships as $ipn) {
				if ($key == $ipn['stock_id']) return TRUE;
			}

			return FALSE;
		}
	}

	public function get_ipn_relationships($key=NULL) {
		if (!$this->has_ipn_relationships($key)) return NULL;
		if (empty($key)) return $this->skeleton->get('ipn_relationships');
		else {
			$ipn_relationships = $this->skeleton->get('ipn_relationships');
			foreach ($ipn_relationships as $ipn) {
				if ($key == $ipn['stock_id']) return $ipn;
			}

			return NULL;
		}
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public function set_default_address($address_book_id) {
		$savepoint = self::transaction_begin();

		try {
			self::query_execute('UPDATE vendors SET vendors_default_address_id = :address_book_id WHERE vendors_id = :vendors_id', cardinality::NONE, [':address_book_id' => $address_book_id, ':vendors_id' => $this->id()]);

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKVendorException('Failed to set default address: '.$e->getMessage());
		}
	}
}

class CkVendorException extends CKMasterArchetypeException {
}