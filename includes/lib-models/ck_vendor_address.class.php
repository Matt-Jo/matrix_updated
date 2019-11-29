<?php
class ck_vendor_address extends ck_archetype implements ck_address_interface {

	protected static $skeleton_type = 'ck_vendor_address_type';

	protected static $queries = [
		'address_header' => [
			'qry' => 'SELECT abv.address_book_id, abv.vendors_id, abv.entry_company as company_name, abv.entry_firstname as first_name, abv.entry_lastname as last_name, abv.entry_street_address as address1, abv.entry_suburb as address2, abv.entry_postcode as postcode, abv.entry_city as city, abv.entry_state as state, abv.entry_country_id as countries_id, c.countries_name as country, c.countries_iso_code_2, c.countries_iso_code_3, c.address_format_id as country_address_format_id, c.default_postcode as country_default_postcode, abv.entry_zone_id as zone_id, z.zone_code as state_region_code, z.zone_name as state_region_name, CASE WHEN z.zone_country_id = abv.entry_country_id THEN 1 ELSE 0 END as region_country_match, abv.entry_telephone as telephone, CASE WHEN dv.vendors_default_address_id IS NOT NULL THEN 1 ELSE 0 END as default_address FROM address_book_vendors abv LEFT JOIN countries c ON abv.entry_country_id = c.countries_id LEFT JOIN zones z ON abv.entry_zone_id = z.zone_id LEFT JOIN vendors dv ON abv.address_book_id = dv.vendors_default_address_id WHERE abv.address_book_id = :address_book_id',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		// we need a different one because they have different cardinalities
		'address_header_list' => [
			'qry' => 'SELECT abv.address_book_id, abv.vendors_id, abv.entry_company as company_name, abv.entry_firstname as first_name, abv.entry_lastname as last_name, abv.entry_street_address as address1, abv.entry_suburb as address2, abv.entry_postcode as postcode, abv.entry_city as city, abv.entry_state as state, abv.entry_country_id as countries_id, c.countries_name as country, c.countries_iso_code_2, c.countries_iso_code_3, c.address_format_id as country_address_format_id, c.default_postcode as country_default_postcode, abv.entry_zone_id as zone_id, z.zone_code as state_region_code, z.zone_name as state_region_name, CASE WHEN z.zone_country_id = abv.entry_country_id THEN 1 ELSE 0 END as region_country_match, abv.entry_telephone as telephone, CASE WHEN dv.vendors_default_address_id IS NOT NULL THEN 1 ELSE 0 END as default_address FROM address_book_vendors abv LEFT JOIN countries c ON abv.entry_country_id = c.countries_id LEFT JOIN zones z ON abv.entry_zone_id = z.zone_id LEFT JOIN vendors dv ON abv.address_book_id = dv.vendors_default_address_id WHERE abv.vendors_id = :vendors_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		]
	];

	public function __construct($address_book_id=NULL, ck_vendor_address_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($address_book_id);

		if (!empty($address_book_id) && !$this->skeleton->built('address_book_id')) $this->skeleton->load('address_book_id', $address_book_id);

		if (!empty($address_book_id)) self::register($address_book_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('address_book_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function build_header() {
		$header = self::fetch('address_header', [':address_book_id' => $this->id()]);
		$this->skeleton->load('header', $header);
	}

	private function build_vendor() {
		if (!empty($this->get_header('vendors_id'))) $vendor = new ck_vendor($this->get_header('vendors_id'));
		else $vendor = NULL;
		$this->skeleton->load('vendor', $vendor);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	public function has_company_name() {
		return !empty($this->get_header('company_name'));
	}

	public function get_company_name() {
		if (!$this->has_company_name()) return NULL;
		else return $this->get_header('company_name');
	}

	public function has_name() {
		if (!empty($this->get_header('first_name'))) return TRUE;
		elseif ($this->has_vendor() && !empty($this->get_vendor()->get_header('first_name'))) return TRUE;
		else return FALSE;
	}

	public function get_name() {
		if (!$this->has_name()) return NULL;
		elseif (!empty($this->get_header('first_name'))) return $this->get_header('first_name').' '.$this->get_header('last_name');
		elseif ($this->has_vendor() && !empty($this->get_vendor()->get_header('first_name'))) return $this->get_vendor()->get_header('first_name').' '.$this->get_vendor()->get_header('last_name');
		else return NULL;
	}

	public function get_state() {
		if (!empty($this->get_header('state_region_code'))) return $this->get_header('state_region_code');
		else return $this->get_header('state');
	}

	public function get_address_line_template($fields=[], $line_end="\n") {
		$header = $this->get_header();

		if (!empty($fields)) {
			$template = [];
			foreach ($fields as $field) {
				if ($field == 'company_name') {
					if (!empty($header['company_name'])) $template['company_name'] = $header['company_name'];
				}
				elseif ($field == 'name') {
					if (!empty($header['first_name'])) {
						$template['first_name'] = $header['first_name'];
						if (!empty($header['last_name'])) $template['last_name'] = $header['last_name'];
					}
					elseif (!empty($header['company_name'])) {
						$template['first_name'] = $header['company_name'];
					}
				}
				elseif ($field == 'streets') {
					$template['streets'] = $header['address1'];
					if (!empty($header['address2'])) $template['streets'] .= $line_end.$header['address2'];
				}
				elseif ($field == 'state') {
					$template['state'] = $this->get_state();
				}
				elseif (!empty($header[$field])) $template[$field] = $header[$field];
			}
		}
		else {
			$template = [
				'first_name' => $header['first_name'],
				'last_name' => $header['last_name'],
				'streets' => $header['address1'],
				'city' => $header['city'],
				'postcode' => $header['postcode'],
				'state' => $this->get_state(),
				'country' => $header['country'],
				'telephone' => $header['telephone']
			];

			if (empty($template['first_name'])) $template['first_name'] = $header['company_name'];

			if (!empty($header['address2'])) $template['streets'] .= $line_end.$header['address2'];

			if (!empty($header['company_name'])) $template['company_name'] = $header['company_name'];
		}

		$template['line_end'] = $line_end;

		if (strip_tags($line_end) != $line_end) $template['hr'] = '<hr>';
		else $template['hr'] = $template['line_end'].'----------------------------------------'.$template['line_end'];

		return ['format'.$header['country_address_format_id'] => $template];
	}

	public function has_vendor() {
		if (!$this->skeleton->built('vendor')) $this->build_vendor();
		return $this->skeleton->has('vendor');
	}

	public function get_vendor() {
		if (!$this->has_vendor()) return NULL;
		return $this->skeleton->get('vendor');
	}

	public static function get_addresses_by_vendor($vendors_id) {
		if ($vendors_id && ($headers = self::fetch('address_header_list', [':vendors_id' => $vendors_id]))) {
			$addresses = [];
			foreach ($headers as $header) {
				$skeleton = self::get_record($header['address_book_id']); // if we've already instantiated it, well, oh well
				if (!$skeleton->built('header')) $skeleton->load('header', $header);

				$addresses[] = new self($header['address_book_id'], $skeleton);
			}
			return $addresses;
		}
		else return [];
	}

	/*-------------------------------
	// change data
	-------------------------------*/
}

class CKVendorAddressException extends CKMasterArchetypeException {
}
?>
