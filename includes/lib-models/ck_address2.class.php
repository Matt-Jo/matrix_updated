<?php
class ck_address2 extends ck_archetype implements ck_address_interface {

	const COUNTRY_ID_USA = 223;
	const DEFAULT_COUNTRY_ID = self::COUNTRY_ID_USA;

	protected static $skeleton_type = 'ck_address_type';

	protected static $queries = [
		'address_header' => [
			'qry' => 'SELECT ab.address_book_id, ab.customers_id, ab.entry_gender as gender, ab.entry_company as company_name, ab.entry_firstname as first_name, ab.entry_lastname as last_name, ab.entry_street_address as address1, ab.entry_suburb as address2, ab.entry_postcode as postcode, ab.entry_city as city, ab.entry_state as state, ab.entry_country_id as countries_id, c.countries_name as country, c.countries_iso_code_2, c.countries_iso_code_3, c.address_format_id as country_address_format_id, c.default_postcode as country_default_postcode, ab.entry_zone_id as zone_id, z.zone_code as state_region_code, z.zone_name as state_region_name, CASE WHEN z.zone_country_id = ab.entry_country_id THEN 1 ELSE 0 END as region_country_match, ab.entry_telephone as telephone, ab.entry_company_website as website, CASE WHEN dc.customers_default_address_id IS NOT NULL THEN 1 ELSE 0 END as default_address FROM address_book ab LEFT JOIN countries c ON ab.entry_country_id = c.countries_id LEFT JOIN zones z ON ab.entry_zone_id = z.zone_id LEFT JOIN customers dc ON ab.address_book_id = dc.customers_default_address_id WHERE ab.address_book_id = :address_book_id',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		// we need a different one because they have different cardinalities
		'address_header_list' => [
			'qry' => 'SELECT ab.address_book_id, ab.customers_id, ab.entry_gender as gender, ab.entry_company as company_name, ab.entry_firstname as first_name, ab.entry_lastname as last_name, ab.entry_street_address as address1, ab.entry_suburb as address2, ab.entry_postcode as postcode, ab.entry_city as city, ab.entry_state as state, ab.entry_country_id as countries_id, c.countries_name as country, c.countries_iso_code_2, c.countries_iso_code_3, c.address_format_id as country_address_format_id, c.default_postcode as country_default_postcode, ab.entry_zone_id as zone_id, z.zone_code as state_region_code, z.zone_name as state_region_name, CASE WHEN z.zone_country_id = ab.entry_country_id THEN 1 ELSE 0 END as region_country_match, ab.entry_telephone as telephone, ab.entry_company_website as website, CASE WHEN dc.customers_default_address_id IS NOT NULL THEN 1 ELSE 0 END as default_address FROM address_book ab LEFT JOIN countries c ON ab.entry_country_id = c.countries_id LEFT JOIN zones z ON ab.entry_zone_id = z.zone_id LEFT JOIN customers dc ON ab.address_book_id = dc.customers_default_address_id WHERE ab.customers_id = ?',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'country_lookup' => [
			'qry' => 'SELECT * FROM countries WHERE countries_id = :country_lookup OR countries_name LIKE :country_lookup OR countries_iso_code_2 LIKE :country_lookup OR countries_iso_code_3 LIKE :country_lookup',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		'country_data' => [
			'qry' => 'SELECT * FROM countries WHERE countries_id = ?',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		'country_set' => [
			'qry' => 'SELECT * FROM countries ORDER BY countries_name ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'region_set' => [
			'qry' => 'SELECT * FROM zones WHERE :use_country != 1 OR zone_country_id = :countries_id ORDER BY zone_name ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		]
	];

	public function __construct($address_book_id=NULL, ck_address_type $skeleton=NULL) {
		$run_normalization = !empty($skeleton);

		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($address_book_id);

		if (!empty($address_book_id) && !$this->skeleton->built('address_book_id')) $this->skeleton->load('address_book_id', $address_book_id);

		if ($run_normalization) $this->normalize();

		if (!empty($address_book_id)) self::register($address_book_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('address_book_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize() {
		if ($this->skeleton->built('header')) $this->normalize_header();
	}

	private function build_header() {
		$header = self::fetch('address_header', [':address_book_id' => $this->id()]);
		$this->skeleton->load('header', $header);
		$this->normalize_header();
	}

	private function normalize_header() {
		$header = $this->get_header();

		$country_fields = ['countries_id', 'country', 'countries_iso_code_2', 'countries_iso_code_3'];
		$lookup = NULL;
		$full = TRUE;
		foreach ($country_fields as $cf) {
			if (empty($header[$cf])) $full = FALSE;
			elseif (empty($lookup)) $lookup = $header[$cf];
		}

		if (!empty($lookup) && !$full) {
			if ($country = self::get_country($lookup)) {
				$header['countries_id'] = $country['countries_id'];
				$header['country'] = $country['countries_name'];
				$header['countries_iso_code_2'] = $country['countries_iso_code_2'];
				$header['countries_iso_code_3'] = $country['countries_iso_code_3'];
				$header['country_address_format_id'] = $country['address_format_id'];
				$header['country_default_postcode'] = $country['default_postcode'];
			}
		}

		if (empty($header['state_region_code']) && !empty($header['state'])) {
			if ($region = prepared_query::fetch('SELECT * FROM zones WHERE zone_name LIKE :state', cardinality::ROW, [':state' => $header['state']])) {
				$header['zone_id'] = $region['zone_id'];
				$header['state_region_code'] = $region['zone_code'];
				$header['state_region_name'] = $region['zone_name'];
			}
		}

		$this->skeleton->rebuild('header');
		$this->skeleton->load('header', $header);
	}

	private function build_customer() {
		if (!empty($this->get_header('customers_id'))) $customer = new ck_customer2($this->get_header('customers_id'));
		else $customer = NULL;
		$this->skeleton->load('customer', $customer);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header',$key);
	}

	public function get_unique_id() {
		$key = json_encode($this->get_header());
		// this does not need to be cryptographically secure, just unique.  If sha1 is good enough for Git, it's good enough for us.
		return sha1($key);
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
		elseif ($this->has_customer() && !empty($this->get_customer()->get_header('first_name'))) return TRUE;
		else return FALSE;
	}

	public function get_name() {
		if (!$this->has_name()) return NULL;
		elseif (!empty($this->get_header('first_name'))) return $this->get_header('first_name').' '.$this->get_header('last_name');
		elseif ($this->has_customer() && !empty($this->get_customer()->get_header('first_name'))) return $this->get_customer()->get_header('first_name').' '.$this->get_customer()->get_header('last_name');
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

	public function get_address_format_template($fields=[]) {
		$header = $this->get_header();

		if (!empty($fields)) {
			$template = [];
			foreach ($fields as $field) {
				if ($field == 'company_name') {
					if (!empty($header['company_name'])) $template['company_name'] = $header['company_name'];
				}
				elseif ($field == 'name') {
					if (!empty($header['first_name'])) {
						$template['name'] = $header['first_name'];
						if (!empty($header['last_name'])) $template['name'] .= ' '.$header['last_name'];
					}
					elseif (!empty($header['company_name'])) {
						$template['name'] = $header['company_name'];
					}
				}
				elseif ($field == 'streets') {
					$template['address1'] = $header['address1'];
					if (!empty($header['address2'])) $template['address2'] = $header['address2'];
				}
				elseif ($field == 'state') {
					$template['state'] = $this->get_state();
				}
				elseif (!empty($header[$field])) $template[$field] = $header[$field];
			}
		}
		else {
			$template = [
				'name' => $header['first_name'].' '.$header['last_name'],
				'address1' => $header['address1'],
				'city' => $header['city'],
				'postcode' => $header['postcode'],
				'state' => $this->get_state(),
				'country' => $header['country'],
				'telephone' => $header['telephone']
			];

			if (empty($template['name'])) $template['name'] = $header['company_name'];

			if (!empty($header['address2'])) $template['address2'] = $header['address2'];

			if (!empty($header['company_name'])) $template['company_name'] = $header['company_name'];
		}

		return ['format'.$header['country_address_format_id'] => $template];
	}

	public function get_legacy_array() {
		$header = $this->get_header();

		$telephone = $header['telephone'];
		$dealer = 0;
		$email_address = '';

		if ($this->has_customer()) {
			$customer = $this->get_customer();

			$dealer = $customer->is('dealer')?1:0;
			$email_address = $customer->get_header('email_address');

			if (strlen($header['telephone']) < 10) $telephone = $customer->get_header('telephone');
		}

		return [
			'firstname' => $header['first_name'],
			'lastname' => $header['last_name'],
			'company' => $header['company_name'],
			'dealer' => $dealer,
			'street_address' => $header['address1'],
			'suburb' => $header['address2'],
			'city' => $header['city'],
			'postcode' => $header['postcode'],
			'telephone' => $telephone,
			'state' => $this->get_state(),
			'zone_id' => $header['zone_id'],
			'country' => [
				'id' => $header['countries_id'],
				'title' => $header['country'],
				'iso_code_2' => $header['countries_iso_code_2'],
				'iso_code_3' => $header['countries_iso_code_3']
			],
			'country_id' => $header['countries_id'],
			'format_id' => $header['country_address_format_id'],
			'email_address' => $email_address,
		];
	}

	public function has_customer() {
		if (!$this->skeleton->built('customer')) $this->build_customer();
		return $this->skeleton->has('customer');
	}

	public function get_customer() {
		if (!$this->has_customer()) return NULL;
		return $this->skeleton->get('customer');
	}

	public function is_international() {
		return $this->get_header('countries_iso_code_2') != 'US';
	}

	public static function get_addresses_by_customer($customers_id) {
		if ($customers_id && ($headers = self::fetch('address_header_list', [$customers_id]))) {
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

	public static function get_country($country_lookup) {
		return self::fetch('country_lookup', [':country_lookup' => $country_lookup]);
	}

	public static function get_countries() {
		// don't know if this one will actually work with an empty parameter set, or if we need to omit it all together
		// no need for it as of writing this, so we'll see when the time comes
		return self::fetch('country_set', []);
	}

	public static function get_regions($countries_id=NULL) {
		return self::fetch('region_set', [':countries_id' => $countries_id, ':use_country' => empty($countries_id)?0:1]);
	}

	public static function get_zone($zone_lookup, $countries_id=NULL, $strict=FALSE) {
		$zone = NULL;

		if (empty($countries_id)) return $zone;

		if ($regions = self::get_regions($countries_id)) {
			foreach ($regions as $region) {
				if (in_array(strtoupper(trim($zone_lookup)), [$region['zone_id'], strtoupper($region['zone_name']), strtoupper($region['zone_code'])])) {
					$zone = $region;
					break;
				}
			}

			if (empty($zone) && $strict) throw new CKAddressException('No zone match found ['.$zone_lookup.']');
		}

		return $zone;
	}

	public static function legacy_get_country_field($countries_id, $field) {
		$country = self::get_country($countries_id);

		if (empty($country) || !array_key_exists($field, $country)) return $countries_id;
		else return $country[$field];
	}

	public static function legacy_get_zone_field($countries_id, $zone_id, $fallback, $field) {
		$zone = self::get_zone($zone_id, $countries_id);

		if (empty($zone) || !array_key_exists($field, $zone)) return $fallback;
		else return $zone[$field];
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public function normalize_phone() {
		$header = $this->get_header();

		require_once(__DIR__.'/../../admin/includes/classes/address_check.php');
		$ac = new address_check;

		$header['telephone'] = $ac->cleanPhone($header['telephone']);

		$this->skeleton->rebuild('header');
		$this->skeleton->load('header', $header);
	}
}

class CKAddressException extends CKMasterArchetypeException {
}
?>
