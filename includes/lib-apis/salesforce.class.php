<?php
class salesforce extends rest {

	public static $debug = FALSE;

	const PRODUCTION = 'PRODUCTION';
	const SANDBOX = 'SANDBOX';

	private static $env;

	private static $creds = [
		self::PRODUCTION => [
			'auth_endpoint' => 'https://login.salesforce.com/services/oauth2/token',
			'consumer_key' => '3MVG9uudbyLbNPZMCmRU4AA3cgzX08CjjTIGELwyiDvsaB.xThMAkqeH8v7z9Si5GEFPoapQ80ohdwnwCckL.',
			'consumer_secret' => '1399115375064088826',
			'username' => 'jason.shinn@cablesandkits.com',
			'default-owner' => 'gary.epp@cablesandkits.com',
			// password is userpass.security_token
			'password' => '7biBD%4sFtYJp%%7m8BLY4XGP0Y46hZJ3Wi3c1Xv', //'fqZSnuR7HhmbLPnepRXG6cx2747ZFU9MOELsD'; //
			'endpoint' => 'services/data/v36.0',
		],
		self::SANDBOX => [
			'auth_endpoint' => 'https://test.salesforce.com/services/oauth2/token',
			'consumer_key' => '3MVG9uudbyLbNPZMCmRU4AA3cgzX08CjjTIGELwyiDvsaB.xThMAkqeH8v7z9Si5GEFPoapQ80ohdwnwCckL.',
			'consumer_secret' => '1399115375064088826',
			'username' => 'jason.shinn@cablesandkits.com.pc',
			'default-owner' => 'jason.shinn@cablesandkits.com.pc',
			// password is userpass.security_token
			'password' => '7biBD%4sFtYJp%%7m8BLY4XGP0Y46hZJ3Wi3c1Xv', //'fqZSnuR7HhmbLPnepRXG6cx2747ZFU9MOELsD'; //
			'endpoint' => 'services/data/v36.0',
		],
	];

	protected $customers_id;
	protected $customer;
	protected $customers_extra_login;
	protected $reload = FALSE;

	protected $sf_account_id;
	protected $sf_contact_id;

	protected static $instance;
	private static $access_token;

	private static $auth_failed = FALSE;

	protected static $request_options = [
		CURLOPT_HTTPHEADER => ['Accept: application/json']
	];

	// for the moment this is just to store info in the docs, don't know if it'll be useful in this structure
	private $matching_headers = [
		'If-Match',
		'If-None-Match',
		'If-Modified-Since',
		'If-Unmodified-Since'
	];

	/* there's no reason to have the customer access built into this class, but for now we'll leave it this way */	
	public function __construct($customers_id) {
		$this->customers_id = $customers_id;

		if (empty(self::$env)) {
			$config = service_locator::get_config_service();
			self::$env = !$config->is_production()?self::SANDBOX:self::PRODUCTION;
		}

		parent::__construct(/*[CURLOPT_HEADER => FALSE]*/);

		if (!self::authenticated()) self::perform_auth();

		//$this->opt(CURLOPT_HEADER, TRUE);
	}

	private static function perform_auth() {
		$rest = new rest();
		$rest->clear();

		$auth = ['grant_type' => 'password', 'client_id' => self::$creds[self::$env]['consumer_key'], 'client_secret' => self::$creds[self::$env]['consumer_secret'], 'username' => self::$creds[self::$env]['username'], 'password' => self::$creds[self::$env]['password']];

		$rest->post(self::$creds[self::$env]['auth_endpoint'], $auth); // set the access token
		$response = $rest->parse_response();
		$response = json_decode($response);

		if (json_last_error() !== JSON_ERROR_NONE) {
			//var_dump($response, json_last_error());
			self::$auth_failed = TRUE;
		}
		else {
			self::$instance = $response->instance_url;

			self::$access_token = $response->access_token;
			self::$request_options[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer '.self::$access_token;
		}

		// the ID data holds enough info to use oauth for SOAP login - some actions are only available via the SOAP API, not REST
		// https://developer.salesforce.com/blogs/developer-relations/2011/03/oauth-and-the-soap-api.html
		//$ID = json_decode($rest->send('GET', self::$request_options, $response->id));
		//var_dump($ID);
	}

	public static function auth_failed() {
		return self::$auth_failed;
	}

	public static function authenticated() {
		return self::$debug?self::$access_token:!empty(self::$access_token);
	}

	protected function get_owner_id($input_email_address=NULL) {
		if (self::auth_failed()) return FALSE;

		if (empty($input_email_address)) {
			$email_address = self::$creds[self::$env]['default-owner'];
			if (self::$env == self::SANDBOX) $email_address = preg_replace('/\.pc$/', '', $email_address);
		}
		else $email_address = $input_email_address;

		$options = self::$request_options;
		$options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';

		$query = "SELECT Id, Email FROM User WHERE Email='".$email_address."' AND IsActive=TRUE";
		$resp = json_decode($this->send('GET', self::$request_options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/query?q='.urlencode($query)));

		if (!$this->statusOK()) {
			error_log('SalesForce SOQL Error: ['.$this->status().']');
			return FALSE;
		}

		if ($resp->totalSize > 0) {
			// this account already exists in SalesForce
			return $resp->records[0]->Id;
		}
		elseif (!empty($input_email_address)) return $this->get_owner_id();
	}

	public function get_owner_email($salesforce_owner_id) {
		if (self::auth_failed()) return FALSE;

		$options = self::$request_options;
		$options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';

		$query = "SELECT Id, Email FROM User WHERE Id='".$salesforce_owner_id."'";
		$resp = json_decode($this->send('GET', self::$request_options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/query?q='.urlencode($query)));

		if (!$this->statusOK()) {
			error_log('SalesForce SOQL Error: ['.$this->status().']');
			return FALSE;
		}

		if ($resp->totalSize > 0) {
			// this account already exists in SalesForce
			return $resp->records[0]->Email;
		}
		else return NULL;
	}

	public function load_customer($customer) {
		$this->customers_id = $customer['customers_id'];
		$this->sf_account_id = NULL;
		$this->customer = $customer;
	}

	public function reload_customer() {
		$this->reload = TRUE;
	}

	protected function get_customer($db=NULL) {
		if (empty($this->customer) || $this->reload) {

			$this->customer = prepared_query::fetch('SELECT c.customers_id, c.customers_firstname, c.customers_lastname, c.customers_dob, c.customers_telephone, c.customers_fax, c.account_manager_id, c.customers_email_address, c.company_account_contact_email, c.customer_type, ci.customers_info_date_account_created as date_entered, ci.customers_info_date_account_last_modified as date_modified, ab.address_book_id, ab.entry_company, ab.entry_street_address, ab.entry_city, ab.entry_state, ab.entry_postcode, ab.entry_country_id, co.countries_iso_code_3 as country_name, z.zone_code as state_name, c.msn_screenname, c.aim_screenname, SUM(ot.value) as lifetimevalue_c, COUNT(o.orders_id) as lifetimeorders_c, DATE(MAX(o.date_purchased)) as lastorderdate_c, c.dealer_pay_module as terms, c.customer_segment_id, c.account_manager_id, a.admin_email_address as account_manager_email_address, c.sales_team_id FROM customers c LEFT JOIN customers_info ci ON c.customers_id = ci.customers_info_id LEFT JOIN address_book ab ON c.customers_default_address_id = ab.address_book_id LEFT JOIN countries co ON ab.entry_country_id = co.countries_id LEFT JOIN zones z ON ab.entry_zone_id = z.zone_id LEFT JOIN orders o ON c.customers_id = o.customers_id AND o.orders_status != 6 LEFT JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = \'ot_total\' LEFT JOIN admin a ON c.account_manager_id = a.admin_id WHERE c.customers_id = ?', cardinality::ROW, $this->customers_id);

			$terms_map = [
				0 => 'None',
				1 => 'NET 10',
				2 => 'NET 15',
				3 => 'NET 30',
				4 => 'NET 45'
			];

			if (isset($terms_map[$this->customer['terms']])) $this->customer['terms'] = $terms_map[$this->customer['terms']];
			else $this->customer['terms'] = $terms_map[0];

			$this->reload = FALSE;
		}
	}

	public function load_customers_extra_login($cel) {
		$this->customers_id = $cel['customers_id'];
		$this->customers_extra_login = $cel;
	}

	protected function get_customers_extra_login($customers_extra_logins_id, $db=NULL) {
		if (empty($this->customers_extra_login)) {

			$this->customers_extra_login = prepared_query::fetch('SELECT cel.customers_id, cel.customers_extra_logins_id, cel.customers_emailaddress, cel.customers_firstname, cel.customers_lastname, cel.customers_id, c.customer_type FROM customers_extra_logins cel LEFT JOIN customers c ON cel.customers_id = c.customers_id WHERE cel.customers_id = ? AND cel.customers_extra_logins_id = ?', cardinality::ROW, [$this->customers_id, $customers_extra_logins_id]);
		}
	}

	public function get_customer_link($db=NULL) {
		if (self::auth_failed()) return FALSE;

		$this->get_customer();

		$options = self::$request_options;
		$options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';

		$query = "SELECT Id FROM Account WHERE Customer_ID__c='".$this->customers_id."'";
		$resp = json_decode($this->send('GET', self::$request_options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/query?q='.urlencode($query)));

		if (!$this->statusOK()) {
			error_log('SalesForce SOQL Error: ['.$this->status().']');
			return FALSE;
		}

		if ($resp->totalSize == 0) {
			error_log('SalesForce SOQL Error: [No Account Found]');
			return FALSE;
		}
		else {
			$this->sf_account_id = $resp->records[0]->Id;

			$link = self::$instance.'/'.$this->sf_account_id;

			return $link;
		}
	}

	public function connect_new_account() {
		if (self::auth_failed()) return FALSE;

		if (empty($this->customers_id)) return NULL;
		$this->get_customer();

		/*$query = "SELECT Owner.Email, Owner.ProfileId, Owner.Profile.Name FROM Lead WHERE Email='".$this->customer['customers_email_address']."'";
		$resp = json_decode($this->send('GET', self::$request_options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/query?q='.urlencode($query)));

		if (!$this->statusOK()) {
			error_log('SalesForce SOQL Error: ['.$this->status().']');
			return FALSE;
		}

		if ($resp->totalSize > 0) {
			return FALSE;
		}*/

		$query = "SELECT Account.Id, Account.Customer_ID__c FROM Contact WHERE Email='".$this->customer['customers_email_address']."'";
		$resp = json_decode($this->send('GET', self::$request_options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/query?q='.urlencode($query)));

		if (!$this->statusOK()) {
			error_log('SalesForce SOQL Error: ['.$this->status().']');
			return FALSE;
		}

		$options = self::$request_options;
		$options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
		
		if ($resp->totalSize == 0) return FALSE;
		else {
			// this account already exists in SalesForce
			foreach ($resp->records as $record) {
				if (!empty($record->Account->Customer_ID__c)) continue;
				$resp = json_decode($this->send('PATCH', $options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/sobjects/Account/'.$record->Account->Id, json_encode(['Customer_ID__c' => $this->customers_id])));

				if (!$this->statusOK()) {
					error_log('SalesForce Update Error: ['.$this->status().']');
					return FALSE;
				}

				break;
			}

			return TRUE;
		}
	}

	public function push_customer($db=NULL) {
		if (self::auth_failed()) return FALSE;

		$this->get_customer();

		$segment_to_business_unit_map = [
			'' => '',
			ck_customer2::$customer_segment_map['EU'] => 'EU',
			ck_customer2::$customer_segment_map['RS'] => 'RS',
			ck_customer2::$customer_segment_map['IN'] => 'Other',
			ck_customer2::$customer_segment_map['BD'] => 'UNEDA',
			ck_customer2::$customer_segment_map['MP'] => 'MP',
			ck_customer2::$customer_segment_map['ST'] => 'Student'
		];

		$sales_teams = ck_team::get_sales_teams();
		$team = [];

		foreach ($sales_teams as $sales_team) {
			$team[$sales_team->id()] = $sales_team->get_header('salesforce_key');
		}

		$account_fields = [
			'Name' => !empty($this->customer['entry_company'])?$this->customer['entry_company']:$this->customer['customers_firstname'].' '.$this->customer['customers_lastname'],
			'Customer_Type__c' => $this->customer['customer_type']==0?'Regular':'Dealer',
			'BillingStreet' => $this->customer['entry_street_address'],
			'BillingCity' => $this->customer['entry_city'],
			'BillingState' => $this->customer['state_name'],
			'BillingPostalCode' => $this->customer['entry_postcode'],
			'BillingCountry' => $this->customer['country_name'],
			'ShippingStreet' => $this->customer['entry_street_address'],
			'ShippingCity' => $this->customer['entry_city'],
			'ShippingState' => $this->customer['state_name'],
			'ShippingPostalCode' => $this->customer['entry_postcode'],
			'ShippingCountry' => $this->customer['country_name'],
			'Net_Terms__c' => $this->customer['terms'],
			//'Lifetime_AOV__c' => ($this->customer['lifetimevalue_c'] / $this->customer['lifetimeorders_c']),
			'Lifetime_Value__c' => $this->customer['lifetimevalue_c'],
			'Lifetime_Orders__c' => $this->customer['lifetimeorders_c'],
			'Last_Order_Date__c' => $this->customer['lastorderdate_c'],
			'Customer_ID__c' => $this->customers_id,
			'Customer_Segment__c' => $segment_to_business_unit_map[$this->customer['customer_segment_id']],
			'OwnerId' => $this->get_owner_id($this->customer['account_manager_email_address']),
			'Sales_Team__c' => !empty($team[$this->customer['sales_team_id']])?$team[$this->customer['sales_team_id']]:'SalesUnset',
		];

		$options = self::$request_options;
		$options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';

		$query = "SELECT Id FROM Account WHERE Customer_ID__c='".$this->customers_id."'";
		$resp = json_decode($this->send('GET', self::$request_options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/query?q='.urlencode($query)));

		if (!$this->statusOK()) {
			error_log('SalesForce SOQL Error: ['.$this->status().']');
			return FALSE;
		}

		if ($resp->totalSize == 0) {
			$resp = json_decode($this->send('POST', $options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/sobjects/Account', json_encode($account_fields)));

			if (!$this->statusOK()) {
				error_log('SalesForce Create Error: ['.$this->status().']');
				return FALSE;
			}

			$this->sf_account_id = $resp->id;
		}
		else {
			// this account already exists in SalesForce
			$this->sf_account_id = $resp->records[0]->Id;

			$resp = json_decode($this->send('PATCH', $options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/sobjects/Account/'.$this->sf_account_id, json_encode($account_fields)));

			if (!$this->statusOK()) {
				error_log('SalesForce Update Error: ['.$this->status().']');
				return FALSE;
			}
		}

		$this->push_contact();
	}

	public function push_contact($customers_extra_logins_id=NULL, $db=NULL) {
		if (self::auth_failed()) return FALSE;

		if (empty($customers_extra_logins_id)) {
			$this->get_customer();

			$contact_fields = [
				'LastName' => $this->customer['customers_lastname'],
				'FirstName' => $this->customer['customers_firstname'],
				//'Name' => $this->customer['customers_firstname'].' '.$this->customer['customers_lastname'],
				'Phone' => $this->customer['customers_telephone'],
				'Fax' => $this->customer['customers_fax'],
				'Email' => $this->customer['customers_email_address']
			];

			$query = "SELECT Id FROM Contact WHERE AccountId='".$this->sf_account_id."' AND Email='".$this->customer['customers_email_address']."'";
		}
		else {
			$this->get_customers_extra_login($customers_extra_logins_id);

			if (empty($this->sf_account_id)) $this->get_customer_link();

			$contact_fields = [
				'LastName' => $this->customers_extra_login['customers_lastname'],
				'FirstName' => $this->customers_extra_login['customers_firstname'],
				'Email' => $this->customers_extra_login['customers_emailaddress']
			];

			$query = "SELECT Id FROM Contact WHERE AccountId='".$this->sf_account_id."' AND Email='".$this->customers_extra_login['customers_emailaddress']."'";
		}

		$resp = json_decode($this->send('GET', self::$request_options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/query?q='.urlencode($query)));

		if (!$this->statusOK()) {
			error_log('SalesForce SOQL Error: ['.$this->status().']');
			return FALSE;
		}

		$options = self::$request_options;
		$options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
		
		if ($resp->totalSize == 0) {
			$contact_fields['AccountId'] = $this->sf_account_id;
			$resp = json_decode($this->send('POST', $options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/sobjects/Contact', json_encode($contact_fields)));

			if (!$this->statusOK()) {
				error_log('SalesForce Create Error: ['.$this->status().']');
				return FALSE;
			}

			$this->sf_contact_id = $resp->id;
		}
		else {
			// this account already exists in SalesForce
			foreach ($resp->records as $record) {
				$this->sf_contact_id = $record->Id;

				$resp = json_decode($this->send('PATCH', $options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/sobjects/Contact/'.$this->sf_contact_id, json_encode($contact_fields)));

				if (!$this->statusOK()) {
					error_log('SalesForce Update Error: ['.$this->status().']');
					//return FALSE;
				}
			}
		}
	}

	public function push_order(ck_sales_order $order) {
		if (self::auth_failed()) return FALSE;

		$query = "SELECT Id FROM Account WHERE Customer_ID__c='".$order->get_header('customers_id')."'";
		$resp = json_decode($this->send('GET', self::$request_options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/query?q='.urlencode($query)));

		if (!$this->statusOK()) {
			error_log('SalesForce SOQL Error: ['.$this->status().']');
			return FALSE;
		}

		if ($resp->totalSize == 0) return NULL;

		$this->sf_account_id = $resp->records[0]->Id;

		$order_fields = [
			'Account__c' => $this->sf_account_id,
			'Name' => $order->id(),
			'Transaction_Type__c' => 'Order',
			'Transaction_Date__c' => $order->get_header('date_purchased')->format('c'),
			'Transaction_Total__c' => $order->get_simple_totals('total'),
		];

		switch ($order->get_header('channel')) {
			case 'phone':
				$order_fields['Entry_Channel__c'] = 'phone';
				break;
			case 'web':
				$order_fields['Entry_Channel__c'] = 'web';
				break;
		}

		$options = self::$request_options;
		$options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';

		$resp = json_decode($this->send('POST', $options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/sobjects/CK_Transaction__c', json_encode($order_fields)));

		if (!$this->statusOK()) {
			error_log('SalesForce Create Error: ['.$this->status().']');
			return FALSE;
		}
	}

	public function push_rma(ck_rma2 $rma) {
		if (self::auth_failed()) return FALSE;

		$query = "SELECT Id FROM Account WHERE Customer_ID__c='".$rma->get_header('customers_id')."'";
		$resp = json_decode($this->send('GET', self::$request_options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/query?q='.urlencode($query)));

		if (!$this->statusOK()) {
			error_log('SalesForce SOQL Error: ['.$this->status().']');
			return FALSE;
		}

		if ($resp->totalSize == 0) return NULL;

		$this->sf_account_id = $resp->records[0]->Id;

		$order_fields = [
			'Account__c' => $this->sf_account_id,
			'Name' => $rma->id(),
			'Transaction_Type__c' => 'RMA',
			'Transaction_Date__c' => $rma->get_header('created_on')->format('c'),
			'Transaction_Total__c' => $rma->get_rma_total(),
			'Reference_Number__c' => $rma->get_header('orders_id'),
		];

		$options = self::$request_options;
		$options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';

		$resp = json_decode($this->send('POST', $options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/sobjects/CK_Transaction__c', json_encode($order_fields)));

		if (!$this->statusOK()) {
			error_log('SalesForce Create Error: ['.$this->status().']');
			return FALSE;
		}
	}

	public static function push_all_customers($batch_size=NULL) {

		$start = 0;

		$terms_map = [
			0 => 'None',
			1 => 'NET 10',
			2 => 'NET 15',
			3 => 'NET 30',
			4 => 'NET 45'
		];

		$sf = new self(NULL);

		$batch_size = (int) $batch_size;

		while ($customers = prepared_query::fetch('SELECT c.customers_id, c.customers_firstname, c.customers_lastname, c.customers_dob, c.customers_telephone, c.customers_fax, c.account_manager_id, c.customers_email_address, c.company_account_contact_email, c.customer_type, ci.customers_info_date_account_created as date_entered, ci.customers_info_date_account_last_modified as date_modified, ab.address_book_id, ab.entry_company, ab.entry_street_address, ab.entry_city, ab.entry_state, ab.entry_postcode, ab.entry_country_id, co.countries_iso_code_3 as country_name, z.zone_code as state_name, c.msn_screenname, c.aim_screenname, SUM(ot.value) as lifetimevalue_c, COUNT(o.orders_id) as lifetimeorders_c, DATE(MAX(o.date_purchased)) as lastorderdate_c, c.dealer_pay_module as terms, c.customer_segment_id, c.account_manager_id, a.admin_email_address as account_manager_email_address, c.sales_team_id FROM customers c LEFT JOIN customers_info ci ON c.customers_id = ci.customers_info_id LEFT JOIN address_book ab ON c.customers_default_address_id = ab.address_book_id LEFT JOIN countries co ON ab.entry_country_id = co.countries_id LEFT JOIN zones z ON ab.entry_zone_id = z.zone_id LEFT JOIN orders o ON c.customers_id = o.customers_id AND o.orders_status != 6 LEFT JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = \'ot_total\' LEFT JOIN admin a ON c.account_manager_id = a.admin_id WHERE sent_to_salesforce = 0 GROUP BY c.customers_id ORDER BY customers_id DESC LIMIT 0, '.$batch_size, cardinality::SET)) {
			debug_tools::mark('Start batch # ['.$start.']');
			foreach ($customers as &$customer) {
				if (isset($terms_map[$customer['terms']])) $customer['terms'] = $terms_map[$customer['terms']];
				else $customer['terms'] = $terms_map[0];

				$sf->load_customer($customer);
				$sf->push_customer();

				if ($customers_extra_logins = prepared_query::fetch('SELECT cel.customers_id, cel.customers_extra_logins_id, cel.customers_emailaddress, cel.customers_firstname, cel.customers_lastname, cel.customers_id, c.customer_type FROM customers_extra_logins cel LEFT JOIN customers c ON cel.customers_id = c.customers_id WHERE cel.customers_id = :customers_id', cardinality::SET, [':customers_id' => $customer['customers_id']])) {
					foreach ($customers_extra_logins as $cel) {
						$sf->load_customers_extra_login($cel);
						$sf->push_contact($cel['customers_extra_logins_id']);
					}
				}

				prepared_query::execute('UPDATE customers SET sent_to_salesforce = 1 WHERE customers_id = :customers_id', [':customers_id' => $customer['customers_id']]);
			}

			debug_tools::mark('Done with ['.(($start*$batch_size)+$batch_size).'] records');
			sleep(120);
			$start++;
		}
	}

	public static function pull_all_changed_customers(DateTime $start) {
		if (!self::authenticated()) self::perform_auth();

		if (self::auth_failed()) return FALSE;

		$rest = new rest();

		/*$start = new DateTime();
		$start->sub(new DateInterval('P5DT1H'));*/

		echo $start->format('Y-m-d\TH:i:s+05:00');

		$query = 'SELECT Customer_ID__c, Customer_Segment__c, Owner.Email, Owner.ProfileId, Owner.Profile.Name FROM Account WHERE LastModifiedDate>='.$start->format('Y-m-d\TH:i:s+05:00');
		$request = self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/query?q='.urlencode($query);

		$accounts = [];

		do {
			$response = json_decode($rest->send('GET', self::$request_options, $request));

			if (!$rest->statusOK()) {
				error_log('SalesForce SOQL Error: ['.$rest->status().']');
				return FALSE;
			}

			$accounts = array_merge($accounts, $response->records);

			$next_url = !empty($response->nextRecordsUrl)?$response->nextRecordsUrl:NULL;
			$request = self::$instance.$next_url;
		}
		while (!$response->done);

		echo "\n\n".count($accounts)."\n\n";

		return $accounts;
	}

	public function pull_customer() {
		if (self::auth_failed()) return FALSE;

		$query = 'SELECT Customer_Segment__c, Sales_Team__c, Owner.Email, Owner.ProfileId, Owner.Profile.Name FROM Account WHERE Customer_ID__c=\''.$this->customers_id.'\'';
		$accounts = json_decode($this->send('GET', self::$request_options, self::$instance.'/'.self::$creds[self::$env]['endpoint'].'/query?q='.urlencode($query)));

		if (!$this->statusOK()) {
			error_log('SalesForce SOQL Error: ['.$this->status().']');
			return FALSE;
		}

		if ($accounts->totalSize == 0) return NULL;

		return $accounts->records[0];
	}

	// wrangle the database, if we want to set it explicitly for the call or the class (otherwise, fall back to the global instance)
	protected static $db = NULL;
	public static function set_db($db) {
		static::$db = $db;
	}
	// this allows us to use dependancy injection without requiring it
	protected static function get_db($db=NULL) {
		return $db ?? self::$db ?? service_locator::get_db_service() ?? NULL;
	}
}
?>
