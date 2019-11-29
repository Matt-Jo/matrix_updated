<?php
class api_channel_advisor extends ck_master_api {
	use rest_service;

	const CHECKOUT_NOTVISITED = 'NotVisited';
	const CHECKOUT_COMPLETED = 'Completed';
	const CHECKOUT_VISITED = 'Visited';
	const CHECKOUT_DISABLED = 'Disabled';
	const CHECKOUT_COMPLETEDANDVISITED = 'CompletedAndVisited';
	const CHECKOUT_COMPLETEDOFFLINE = 'CompletedOffline';
	const CHECKOUT_ONHOLD = 'OnHold';

	protected static $checkout_closed = [
		'NotVisited' => FALSE,
		'Completed' => TRUE,
		'Visited' => FALSE,
		'Disabled' => TRUE,
		'CompletedAndVisited' => TRUE,
		'CompletedOffline' => TRUE,
		'OnHold' => FALSE
	];

	const PAYMENT_NOTYETSUBMITTED = 'NotYetSubmitted';
	const PAYMENT_CLEARED = 'Cleared';
	const PAYMENT_SUBMITTED = 'Submitted';
	const PAYMENT_FAILED = 'Failed';
	const PAYMENT_DEPOSITED = 'Deposited';

	protected static $payment_finished = [
		'NotYetSubmitted' => FALSE,
		'Cleared' => TRUE,
		'Submitted' => FALSE,
		'Failed' => TRUE,
		'Deposited' => FALSE
	];

	const SHIPPING_UNSHIPPED = 'Unshipped';
	const SHIPPING_SHIPPED = 'Shipped';
	const SHIPPING_PARTIALLYSHIPPED = 'PartiallyShipped';
	const SHIPPING_PENDINGSHIPMENT = 'PendingShipment';
	const SHIPPING_CANCELED = 'Canceled';
	const SHIPPING_THIRDPARTYMANAGED = 'ThirdPartyManaged';

	protected static $fulfillment_completed = [
		'Unshipped' => FALSE,
		'Shipped' => TRUE,
		'PartiallyShipped' => FALSE,
		'PendingShipment' => FALSE,
		'Canceled' => TRUE,
		'ThirdPartyManaged' => TRUE
	];

	const LOCAL_WAREHOUSE = 'SellerManaged';
	const REMOTE_WAREHOUSE = 'ExternallyManaged';
	const MIXED_WAREHOUSE = 'Mixed';

	protected static $old_marketplace_map = [
		'AMAZON_MARKETPLACE' => 'amazon',
		'AMAZON_US' => 'amazon',
		'EBAY_STORES' => 'ebay',
		'EBAY_US' => 'ebay',
		'NEWEGG' => 'newegg',
		'NEWEGG_BUSINESS' => 'newegg_business',
		'WALMART_MARKETPLACE' => 'walmart'
	];

	//https://developer.channeladvisor.com/working-with-orders/order-site-id-site-name-values
	// I've copied specifically the entries related to the US or All Regions that I believe we might use, and commented out some sub-marketplaces I don't think we use
	// old SOAP list is here: https://developer.channeladvisor.com/soap-api-documentation/order-services/order-service/getorderlist/orderresponseitem/ordercart/orderlineitemitem/sitetoken
	// the only one I can't find in the new list is an equivalent for AMAZON_MARKETPLACE
	protected static $marketplace_map = [
		640 => 'amazon', // Amazon Seller Central - US // Amazon
		//1129 => 'amazon', // Amazon Vendor // Amazon 1P
		//1144 => 'amazon', // Amazon Drop Ship // Amazon 1P
		1 => 'ebay', // eBay // eBay Auctions
		//568 => 'ebay' => // eBay Motors // eBay Motors
		576 => 'ebay', // eBay Fixed Price // eBay Fixed Price (site name will differ by region)
		//930 => 'jet', // Jet // Jet
		//1130 => 'jet', // Jet Drop Ship // Jet 1P
		665 => 'newegg', // Newegg // Newegg
		926 => 'newegg_business', // Newegg Business // Newegg Business
		//826 => 'walmart', // Walmart // Walmart
		996 => 'walmart', // Walmart Marketplace // Walmart
	];

	protected static $marketplace_default_user = [
		'amazon' => 96285,
		'newegg' => 127213,
		'newegg_business' => 130509,
		'walmart' => 152080,
		'ebay' => 161001,
	];

	protected static $marketplace_default_payment_method = [
		'ebay' => 2,
		'amazon' => 14,
		'newegg' => 14,
		'newegg_business' => 14,
		'walmart' => 14
	];

	const CK_STANDARD_FREE = 48;

	// UPS
	/*protected static $shipping_method_map = [
		'Expedited' => 22,
		'NextDay' => 19,
		'FedExStandardOvernight' => 19,
		'Standard Overnight' => 19,
		'SecondDay' => 21,
		'FedEx2Day' => 21,
		'2 Day' => 21,
		'ShippingMethodStandard' => self::CK_STANDARD_FREE,
		'USPSPriorityMailInternational' => 43,
		'Priority Mail International' => 43,
		'Standard' => 23,
		'UPSGround' => 23,
		'Ground' => 23,
		'Standard Shipping (5-7 business days)' => 23,
		'First-Class Mail' => 33,
		'Express Mail' => 31,
	];*/

	// FedEx
	protected static $shipping_method_map = [
		'Expedited' => 5,
		'NextDay' => 3,
		'FedExStandardOvernight' => 3,
		'Standard Overnight' => 3,
		'SecondDay' => 4,
		'FedEx2Day' => 4,
		'2 Day' => 4,
		'ShippingMethodStandard' => self::CK_STANDARD_FREE,
		'USPSPriorityMailInternational' => 43,
		'Priority Mail International' => 43,
		'Standard' => 9,
		'UPSGround' => 23,
		'Ground' => 9,
		'Standard Shipping (5-7 business days)' => 9,
		'First-Class Mail' => 33,
		'Express Mail' => 31,
	];

	const DC_BUFORD = 0;
	const DC_AMAZONFBA = -2;

	public static $distribution_center_map = [
		0 => 'BUFORD',
		-2 => 'Amazon FBA US'
	];

	protected static $distribution_center_cache = [];

	protected static $errors = [];

	private $rest;

	/*private static $developer_key = '3ee82074-ea2b-41f3-83a5-ee891953c79e';
	private static $password = 'Ch@nne1Ad';
	private static $account_id = '9f8c963d-e1d4-4e0b-b808-808e9cd7d502';*/

	private static $application_id = '9vbbqqq8qsieu1ruanbnsk54k31n5oly';
	private static $shared_secret = 'LBUWqb824UiJjgM0XEEvlw';
	private static $access_token; // = 'vz8kkWUir06_ZTnKkfoRbqznKxTLpMYthl6DZzbDR0k-14781';
	private static $refresh_token = 'd13iLdH69sr5oNKobFynn4eleFgak8onOB8ezuqd60k';

	private static $url = 'https://api.channeladvisor.com';

	// CA doesn't provide us with a sandbox, so we'll have to manage our actions here
	private static $sandbox = TRUE;

	public function __construct() {
		if (!self::is_authorized()) $this->authorize();

		$config = service_locator::get_config_service();
		if ($config->is_production()) self::$sandbox = FALSE;
	}

	public function __destruct() {
		if ($this->has_api_errors()) $this->alert_api_errors();
	}

	private static $rate_limit_counter = 0;

	private static function rate_limiter() {
		if ((++self::$rate_limit_counter)%10 == 0) {
			flush();
			usleep(400000); // 2/5 second
			// 2 seconds every 50 calls
			// 20 seconds every 500 calls
			// 1 minute every 1500 calls
			// this gets us in line with the API limits of 2000 calls per minute (which we'll probably never approach again)
			// https://developer.channeladvisor.com/rest-api-core-concepts/rest-api-request-limits
		}
	}

	private function authorize() {
		$opts = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Basic '.base64_encode(self::$application_id.':'.self::$shared_secret),
				'Content-Type: application/x-www-form-urlencoded',
				'Cache-Control: no-cache'
			]
		];

		$rest = $this->new_rest_session($opts);

		$auth = [
			'grant_type' => 'refresh_token',
			'refresh_token' => self::$refresh_token
		];

		// we'll see if this is needed for the other calls, but for the OAuth call we need to post the data formatted as a querystring
		$auth = http_build_query($auth);

		$response = json_decode($rest->send('POST', [], self::$url.'/oauth2/token', $auth));

		if (json_last_error() !== JSON_ERROR_NONE) {
			// handle it
		}
		else {
			self::$access_token = $response->access_token;
		}
	}

	public static function is_authorized() {
		return !empty(self::$access_token);
	}

	public function create_product(ck_product_listing $product) {
		$opts = [
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Authorization: Bearer '.self::$access_token
			],
			CURLINFO_HEADER_OUT => TRUE
		];

		$rest = $this->new_rest_session($opts);

		// since we're not using this in production, this isn't yet intended to be comprehensive.
		$ca_product = [];
		$ca_product['Sku'] = $product->id();
		$ca_product['Title'] = $product->get_header('products_name');
		//$ca_product['UPC'] = $product->get_upc_number();
		//$ca_product['RetailPrice'] = $product->get_price('display');

		if (self::$sandbox) {
			$ca_product['IsBlocked'] = TRUE;
			$ca_product['IsExternalQuantityBlocked'] = TRUE;
			$ca_product['BlockComment'] = 'This is a test item';
		}

		$url = self::$url.'/v1/Products';

		$result = $rest->send('POST', [], $url, $ca_product);

		if ($rest->statusOK()) return json_decode($result);
		else return NULL;
	}

	public function get_product_id(ck_product_listing $product) {
		$opts = [
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Authorization: Bearer '.self::$access_token
			],
			CURLINFO_HEADER_OUT => TRUE
		];

		$rest = $this->new_rest_session($opts);

		$product_query = [];
		$product_query['$filter'] = "Sku eq '".$product->id()."'";
		$product_query['$select'] = 'ID';

		$url = self::$url.'/v1/Products';

		$result = $rest->send('GET', [], $url, $product_query);

		if ($rest->statusOK()) {
			$result = json_decode($result);
			if (empty($result->value)) return NULL;
			return $result->value[0]->ID;
		}
		else return NULL;
	}

	public function delete_product($ca_product_id) {
		$opts = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.self::$access_token
			],
			CURLINFO_HEADER_OUT => TRUE
		];

		$rest = $this->new_rest_session($opts);

		$result = $rest->send('DELETE', [], self::$url.'/v1/Products('.$ca_product_id.')', []);

		return $rest->statusOK();
	}

	public function get_distribution_center_id($code) {
		if (isset(self::$distribution_center_cache[$code])) return self::$distribution_center_cache[$code];

		$opts = [
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Authorization: Bearer '.self::$access_token
			],
			CURLINFO_HEADER_OUT => TRUE
		];

		$rest = $this->new_rest_session($opts);

		$dc_query = [];
		$dc_query['$filter'] = "Code eq '".$code."'";
		$dc_query['$select'] = 'ID';

		$url = self::$url.'/v1/DistributionCenters';

		$result = $rest->send('GET', [], $url, $dc_query);

		if ($rest->statusOK()) {
			$result = json_decode($result);
			if (empty($result->value)) return NULL;
			return self::$distribution_center_cache[$code] = $result->value[0]->ID;
		}
		else return NULL;
	}

	public function get_distribution_center_details($dc_id) {
		$opts = [
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Authorization: Bearer '.self::$access_token
			],
			CURLINFO_HEADER_OUT => TRUE
		];

		$rest = $this->new_rest_session($opts);

		$url = self::$url.'/v1/DistributionCenters('.$dc_id.')';

		$result = $rest->send('GET', [], $url, NULL);

		if ($rest->statusOK()) {
			$result = json_decode($result);
			if (empty($result->ID)) return NULL;
			return $result;
		}
		else return NULL;
	}

	public function update_quantity(ck_ipn2 $ipn) {
		$opts = [
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Authorization: Bearer '.self::$access_token
			],
			CURLINFO_HEADER_OUT => TRUE
		];

		$rest = $this->new_rest_session($opts);

		$products = $ipn->get_listings();

		/*$QuantityUpdateType = [
			'Absolute' => 0, // no figuring
			'Relative' => 1, // just modify
			'Available' => 2, // deprecated
			'InStock' => 3, // take this number and remove qtys ordered but not yet imported
			'UnShipped' => 4 // take this number and remove qtys ordered but not yet imported or shipped
		];*/

		// we don't currently manage Amazon FBA inventory from within Matrix, so we'll just deal with Buford Distribution Center for now
		$dc_id = $this->get_distribution_center_id('BUFORD');

		$failed = [];

		foreach ($products as $product) {
			$qty_update = ['Value' => []];
			$qty_update['Value']['UpdateType'] = 'InStock';
			$qty_update['Value']['Updates'] = [['DistributionCenterID' => $dc_id, 'Quantity' => $ipn->get_inventory('available')]];

			$ca_product_id = $this->get_product_id($product);

			$url = self::$url.'/v1/Products('.$ca_product_id.')/UpdateQuantity';

			if (!self::$sandbox) {
				$result = $rest->send('POST', [], $url, $qty_update);
				if (!$rest->statusOK()) $failed[] = $product;
			}
		}

		// we could do debugging of failed here...

		return empty($failed);
	}

	public function mark_order_exported(Array $ca_order_ids) {
		$opts = [
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Authorization: Bearer '.self::$access_token
			],
			CURLINFO_HEADER_OUT => TRUE
		];

		$rest = $this->new_rest_session($opts);

		foreach ($ca_order_ids as $orders_id => $ca_order_id) {
			if (self::$sandbox) echo 'idx ['.self::$rate_limit_counter.'] Marked CA Order ['.$ca_order_id.'] Matrix Order ['.$orders_id.'] as exported<br>';
			$result = $rest->send('POST', [], self::$url.'/v1/Orders('.$ca_order_id.')/Export', []);
			self::rate_limiter();

			if (!$rest->statusOK()) {
				var_dump($result);
				return FALSE;
			}
		}

		return TRUE;
	}

	public function mark_order_unexported($ca_order_id) {
		$opts = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.self::$access_token
			],
			CURLINFO_HEADER_OUT => TRUE
		];

		$rest = $this->new_rest_session($opts);

		$result = $rest->send('DELETE', [], self::$url.'/v1/Orders('.$ca_order_id.')/Export', []);

		return $rest->statusOK();
	}

	public function update_orders($orders) {
		$opts = [
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Authorization: Bearer '.self::$access_token
			],
			CURLINFO_HEADER_OUT => TRUE
		];

		$rest = $this->new_rest_session($opts);

		$failed = [];

		foreach ($orders as $ca_order_id => $order) {
			$url = self::$url.'/v1/Orders('.$ca_order_id.')';

			$result = $rest->send('PATCH', [], $url, $order);

			self::rate_limiter();

			if (!$rest->statusOK()) $failed[] = $order;
		}

		// we could do debugging of failed here...

		return empty($failed);
	}

	public function import_orders($limit=NULL) {
		$opts = [
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Authorization: Bearer '.self::$access_token
			],
			CURLINFO_HEADER_OUT => TRUE
		];

		$rest = $this->new_rest_session($opts);

		$order_query = [];
		$order_query['exported'] = 'false';

		$filter = [];
		$filter[] = "CheckoutStatus ne '".self::CHECKOUT_DISABLED."'";
		$filter[] = "ShippingStatus ne '".self::SHIPPING_CANCELED."'";
		$filter[] = "PaymentStatus ne '".self::PAYMENT_FAILED."'";
		//$filter[] = "DistributionCenterTypeRollup ne '".self::LOCAL_WAREHOUSE."'";
		if (!empty($filter)) $order_query['$filter'] = implode(' and ', $filter);

		$expand = [];
		$expand[] = 'Items($expand=Promotions,FulfillmentItems)';
		$expand[] = 'Fulfillments';
		//$expand[] = 'Promotions';
		if (!empty($expand)) $order_query['$expand'] = implode(',', $expand);

		$order_query['$count'] = 'true';

		$url = self::$url.'/v1/Orders';

		ck_ipn2::reset_local_channel_advisor_allocated_qty();

		$call_count = 0;
		$order_count = 0;

		$exported = [];
		$order_updates = [];

		do {
			$call_count++;

			$result_raw = $rest->send('GET', [], $url, $order_query);
			$result = json_decode($result_raw);

			if ($call_count == 1 && self::$sandbox) echo 'Count: '.$result->{'@odata.count'}.'<br>';

			if ($rest->statusOK()) {
				foreach ($result->value as $idx => $ca_order) {
					if (!empty($limit) && ((($call_count - 1) * 20) + $order_count + 1) > $limit) break 2;
					try {
						if (self::$sandbox && $ca_order->DistributionCenterTypeRollup == self::LOCAL_WAREHOUSE && $ca_order->ShippingStatus == self::SHIPPING_SHIPPED && !empty($ca_order->SellerOrderID)) {
							// this is literally just a dev action to get the data caught back up on orders that, for whatever reason, weren't marked exported
							$exported[$ca_order->SellerOrderID] = $ca_order->ID;
							continue;
						}

						if (!empty($ca_order->{'Items@odata.nextLink'})) {
							$result = $rest->send('GET', [], self::$url.'/v1/Orders('.$ca_order->ID.')/Items', ['$expand' => 'Promotions,FulfillmentItems']);
							if ($rest->statusOK()) {
								$result = json_decode($result);
								$ca_items = $result->value;
							}
							else {
								$this->record_api_error('Failed to retreive order items in excess of 20 lines', NULL, [$ca_order, $result]);
								continue;
							}
						}
						else $ca_items = $ca_order->Items;

						if (!self::$checkout_closed[$ca_order->CheckoutStatus] || !self::$payment_finished[$ca_order->PaymentStatus]) {
							if (self::$sandbox) echo '#'.((($call_count - 1) * 20) + $idx + 1).' Not Ready Yet CA: ['.$ca_order->ID.']<br>';
							foreach ($ca_items as $item) {
								$product = new ck_product_listing($item->Sku);
								$product->get_ipn()->update_local_channel_advisor_allocated_qty($item->Quantity);
							}
							continue;
						}

						$ca_order_id = $ca_order->ID;
						if ($so = ck_sales_order::get_order_by_ca_order_id($ca_order_id)) {
							if (self::$sandbox) echo '#'.((($call_count - 1) * 20) + $idx + 1).' Already Exists by CA: ['.$ca_order_id.'] '.$so->id().'<br>';
							if (!self::$sandbox) $exported[] = $ca_order_id;
							continue; // this order already exists
						}

						$orders_id = $ca_order->SellerOrderID;
						if (!empty($orders_id) && !self::$sandbox) {
							$so = new ck_sales_order($orders_id);
							if ($so->found()) {
								if (self::$sandbox) echo '#'.((($call_count - 1) * 20) + $idx + 1).' Already Exists by Order ID: ['.$ca_order_id.'] '.$so->id().'<br>';
								if (!self::$sandbox) $exported[] = $ca_order_id;
								continue; // this order already exists
							}
						}

						if (empty(self::$marketplace_map[$ca_order->SiteID])) {
							$this->record_api_error('Channel Advisor Order Import Failed: could not determine marketplace', $orders_id, [$ca_order]);
							continue;
						}

						$channel = self::$marketplace_map[$ca_order->SiteID];

						$customers_email_address = $ca_order->BuyerEmailAddress;

						if (empty(self::$marketplace_default_user[$channel])) {
							if ($customer = ck_customer2::get_customer_by_email($customers_email_address)) {
								$customers_id = $customer->id();
							}
							else {
								$country = ck_address2::get_country($ca_order->ShippingCountry);
								$zone = ck_address2::get_zone($ca_order->ShippingStateOrProvince, @$country['countries_id']);

								$zone_id = !empty($zone)?$zone['zone_id']:0;

								$cust = [
									'header' => [
										'customers_firstname' => $ca_order->ShippingFirstName,
										'customers_lastname' => $ca_order->ShippingLastName,
										'customers_email_address' => $customers_email_address,
										'customers_telephone' => '',
										'customers_fax' => NULL,
										'customers_newsletter' => 1,
										'customers_password' => 'NONE',
										'password_info' => -1,
										'customer_segment_id' => ck_customer2::$customer_segment_map['MP'], // marketplace
									],
									'address' => [
										'entry_firstname' => $ca_order->ShippingFirstName,
										'entry_lastname' => $ca_order->ShippingLastName,
										'entry_company' => NULL,
										'entry_street_address' => !empty($ca_order->ShippingAddressLine1)?$ca_order->ShippingAddressLine1:'',
										'entry_suburb' => $ca_order->ShippingAddressLine2,
										'entry_postcode' => $ca_order->ShippingPostalCode,
										'entry_city' => $ca_order->ShippingCity,
										'entry_country_id' => !empty($country)?$country['countries_id']:0,
										'entry_telephone' => '',
										'entry_zone_id' => $zone_id,
										'entry_state' => $ca_order->ShippingStateOrProvince,
									]
								];

								$customer = ck_customer2::create($cust);
								$customers_id = $customer->id();
							}
						}
						else {
							$customers_id = self::$marketplace_default_user[$channel];
							$customer = new ck_customer2($customers_id);
						}

						$created_date = new DateTime($ca_order->CreatedDateUtc);
						$created_date->setTimezone(new DateTimeZone('America/New_York'));
						$payment_method = $ca_order->PaymentMethod;
						$payment_transaction_id = $ca_order->PaymentTransactionID;

						// default to check/money order - shouldn't ever happen
						$payment_method_id = !empty(self::$marketplace_default_payment_method[$channel])?self::$marketplace_default_payment_method[$channel]:3;

						$marketplace_order_id = $ca_order->SiteOrderID!=$ca_order_id?$ca_order->SiteOrderID:$ca_order->SecondarySiteOrderID;

						$shipping = [
							'first_name' => !empty($ca_order->ShippingFirstName)?$ca_order->ShippingFirstName:'',
							'last_name' => !empty($ca_order->ShippingLastName)?$ca_order->ShippingLastName:'',
							'company' => $ca_order->ShippingCompanyName,
							'phone' => !empty($ca_order->ShippingDaytimePhone)?$ca_order->ShippingDaytimePhone:'',
							'street1' => !empty($ca_order->ShippingAddressLine1)?$ca_order->ShippingAddressLine1:'',
							'street2' => $ca_order->ShippingAddressLine2,
							'city' => !empty($ca_order->ShippingCity)?$ca_order->ShippingCity:'',
							'state' => $ca_order->ShippingStateOrProvince,
							'postal_code' => !empty($ca_order->ShippingPostalCode)?$ca_order->ShippingPostalCode:'',
							'country' => !empty($ca_order->ShippingCountry)?$ca_order->ShippingCountry:''
						];

						$billing = [
							'first_name' => !empty($ca_order->BillingFirstName)?$ca_order->BillingFirstName:'',
							'last_name' => !empty($ca_order->BillingLastName)?$ca_order->BillingLastName:'',
							'company' => $ca_order->BillingCompanyName,
							'phone' => !empty($ca_order->BillingDaytimePhone)?$ca_order->BillingDaytimePhone:'',
							'street1' => !empty($ca_order->BillingAddressLine1)?$ca_order->BillingAddressLine1:'',
							'street2' => $ca_order->BillingAddressLine2,
							'city' => !empty($ca_order->BillingCity)?$ca_order->BillingCity:'',
							'state' => $ca_order->BillingStateOrProvince,
							'postal_code' => !empty($ca_order->BillingPostalCode)?$ca_order->BillingPostalCode:'',
							'country' => !empty($ca_order->BillingCountry)?$ca_order->BillingCountry:''
						];

						$order = [
							'header' => [
								'customers_id' => $customers_id,
								'customers_extra_logins_id' => NULL,
								'customers_name' => $shipping['first_name'].' '.$shipping['last_name'],
								'customers_company' => $shipping['company'],
								'customers_street_address' => $shipping['street1'],
								'customers_suburb' => $shipping['street2'],
								'customers_city' => $shipping['city'],
								'customers_postcode' => $shipping['postal_code'],
								'customers_state' => $shipping['state'],
								'customers_country' => $shipping['country'],
								'customers_telephone' => $shipping['phone'],
								'customers_email_address' => $customers_email_address,
								'customers_address_format_id' => 2,
								'delivery_name' => $shipping['first_name'].' '.$shipping['last_name'],
								'delivery_company' => $shipping['company'],
								'delivery_street_address' => $shipping['street1'],
								'delivery_suburb' => $shipping['street2'],
								'delivery_city' => $shipping['city'],
								'delivery_postcode' => $shipping['postal_code'],
								'delivery_state' => $shipping['state'],
								'delivery_country' => $shipping['country'],
								'delivery_telephone' => $shipping['phone'],
								'delivery_address_format_id' => 2,
								'billing_name' => $billing['first_name'].' '.$billing['last_name'],
								'billing_company' => $billing['company'],
								'billing_street_address' => $billing['street1'],
								'billing_suburb' => $billing['street2'],
								'billing_city' => $billing['city'],
								'billing_postcode' => $billing['postal_code'],
								'billing_state' => $billing['state'],
								'billing_country' => $billing['country'],
								'billing_address_format_id' => 2,
								'payment_method_id' => $payment_method_id,
								'date_purchased' => $created_date->format('Y-m-d H:i:s'),
								'last_modified' => date('Y-m-d H:i:s'),
								'orders_status' => 11,
								'orders_sub_status' => 1,
								'legacy_order' => FALSE,
								'channel' => $channel,
								'currency' => 'USD',
								'currency_value' => 1,
								'ca_order_id' => $ca_order_id,
								'marketplace_order_id' => $marketplace_order_id,
								'amazon_order_number' => $channel=='amazon'?$marketplace_order_id:NULL,
								'ebay_order_id' => $channel=='ebay'?$marketplace_order_id:NULL,
								'paymentsvc_id' => NULL,
							],
							'payment' => [
								'payment_method_id' => $payment_method_id,
								'payment_method' => $payment_method,
								'payment_transaction_id' => $payment_transaction_id,
								'amount' => $ca_order->TotalPrice,
								'marketplace' => TRUE
							],
							'products' => [],
							'totals' => [],
							'customer_notes' => NULL,
							'admin_notes' => [],
							'check_fraud' => FALSE,
							'notify' => FALSE
						];

						$packages = [];

						if (!empty($ca_order->SpecialInstructions)) $order['customer_notes'] = $ca_order->SpecialInstructions;

						$order['admin_notes'][] = [
							'orders_note_user' => ck_sales_order::$solutionsteam_id,
							'orders_note_text' => 'This order was automatically imported from Channel Advisor.',
						];

						if ($payment_method_id == 2) {
							$order['admin_notes'][] = [
								'orders_note_user' => ck_sales_order::$solutionsteam_id,
								'orders_note_text' => 'Paypal Transaction ID: '.$payment_transaction_id
							];
						}

						$distribution_type = $ca_order->DistributionCenterTypeRollup;

						// if this order is completely fulfilled externally or internally, we can handle all of the items, shipping & totals together
						// otherwise we're gonna split the order and need to deal with the items, shipping & totals differently
						if (in_array($distribution_type, [self::LOCAL_WAREHOUSE, self::REMOTE_WAREHOUSE])) {
							foreach ($ca_items as $item) {
								$product = new ck_product_listing($item->Sku);

								if (!empty($order['products'][$product->id()])) {
									// items from Walmart come in as single units only, we need to roll them up
									/* // the price should always be the same, this code is intended to deal with unit price differences within the same unit
									// for now I've commented it out because I don't want it creating any unintended consequences.
									if ($order['products'][$product->id()]['final_price'] != $item->UnitPrice) {
										$line_price = $order['products'][$product->id()]['products_quantity'] * $order['products'][$product->id()]['final_price'];
										$line_price += $item->Quantity * $item->UnitPrice;
										$unit_price = $line_price / ($order['products'][$product->id()]['products_quantity'] + $item->Quantity);

										$order['products'][$product->id()]['products_price'] = $unit_price;
										$order['products'][$product->id()]['final_price'] = $unit_price;
										$order['products'][$product->id()]['display_price'] = $unit_price;
									}*/
									$order['products'][$product->id()]['products_quantity'] += $item->Quantity;
								}
								else {
									$price_reason = ck_cart::$price_reasons_key['original'];
									if ($product->get_price('reason') == 'special') $price_reason = ck_cart::$price_reasons_key['special'];
									$order['products'][$product->id()] = [
										'products_id' => $product->id(),
										'products_model' => $product->get_header('products_model'),
										'products_name' => $item->Title,
										'products_quantity' => $item->Quantity,
										'products_price' => $item->UnitPrice,
										'final_price' => $item->UnitPrice,
										'display_price' => $item->UnitPrice,
										'products_tax' => 0,
										'price_reason' => $price_reason,
										'option_type' => ck_cart::$option_types['NONE'],
									];
								}

								if (!empty($item->Promotions)) {
									foreach ($item->Promotions as $promo) {
										if (!empty($promo->Amount)) $order['totals'][] = ['value' => -1*abs($promo->Amount), 'class' => 'ot_coupon', 'title' => 'Discount: '.$promo->Code];
										if (!empty($promo->ShippingAmount)) $order['totals'][] = ['value' => -1*abs($promo->ShippingAmount), 'class' => 'ot_coupon', 'title' => 'Discount: '.$promo->Code];
									}
								}
							}

							$shipping_method = '';
							$shipping_method_id = 0;

							foreach ($ca_order->Fulfillments as $fulfillment) {
								$shipping_method = $fulfillment->ShippingClass;
								if (!empty(self::$shipping_method_map[$shipping_method])) $shipping_method_id = self::$shipping_method_map[$shipping_method];

								$order['header']['distribution_center_id'] = $fulfillment->DistributionCenterID;

								// grab tracking for packages on orders fulfilled remotely
								if ($distribution_type == self::REMOTE_WAREHOUSE) {
									$created = new DateTime($fulfillment->CreatedDateUtc);
									$created->setTimezone(new DateTimeZone('America/New_York'));

									$packages[] = [
										'package' => [
											'order_product_id' => 0,
											'package_type_id' => 1,
											'order_package_length' => 0,
											'order_package_width' => 0,
											'order_package_height' => 0,
											'scale_weight' => 0,
											'date_time_created' => $created->format('Y-m-d H:i:s')
										],
										'tracking' => [
											'tracking_num' => $fulfillment->TrackingNumber,
											'shipping_method_id' => !empty($shipping_method_id)?$shipping_method_id:23,
											'cost' => 0,
											'invoiced_cost' => 0,
											'invoiced_weight' => 0,
											'date_time_created' => $created->format('Y-m-d H:i:s'),
										]
									];
								}
							}

							if (empty($shipping_method_id)) {
								$shipping_method_id = self::CK_STANDARD_FREE;
								$order['admin_notes'][] = [
									'orders_note_user' => ck_sales_order::$solutionsteam_id,
									'orders_note_text' => 'A shipping method could not be matched for this order. Please contact CK Technology Team.',
								];
							}

							if (!empty($ca_order->AdditionalCostOrDiscount)) $order['totals'][] = ['value' => $ca_order->AdditionalCostOrDiscount, 'class' => 'ot_custom'];
							$order['totals'][] = ['value' => $ca_order->TotalShippingPrice, 'class' => 'ot_shipping', 'title' => $shipping_method, 'external_id' => $shipping_method_id];
							if (!empty($ca_order->PromotionAmount)) $order['totals'][] = ['value' => -1*abs($ca_order->PromotionAmount), 'class' => 'ot_coupon', 'title' => !empty($ca_order->PromotionCode)?$ca_order->PromotionCode:'CA Promo'];
							if (!empty($ca_order->TotalTaxPrice)) $order['totals'][] = ['value' => $ca_order->TotalTaxPrice, 'class' => 'ot_tax'];
							$order['totals'][] = ['value' => $ca_order->TotalPrice, 'class' => 'ot_total'];

							$order_count++;

							// create sales order
							$sales_order = ck_sales_order::create($order);
							$orders_id = $sales_order->id();

							if (self::$sandbox) echo 'Order ID '.$orders_id.'<br>';

							if ($distribution_type == self::REMOTE_WAREHOUSE) {
								// create packages
								foreach ($packages as $package) {
									$sales_order->create_package($package);
								}

								// ship it
								$messages = $sales_order->ship();

								$sales_order->create_admin_note(['orders_note_user' => ck_sales_order::$solutionsteam_id, 'orders_note_text' => 'This order was automatically shipped from the marketplace']);
							}

							if (!self::$sandbox) {
								$exported[] = $ca_order_id;
								$order_updates[$ca_order_id] = ['SellerOrderID' => $orders_id];
							}
						}
						elseif ($distribution_type == self::MIXED_WAREHOUSE) {
							$orders = [];
							$packages = [];

							$multi_shipment_total = 0;

							foreach ($ca_order->Fulfillments as $fulfillment) {
								if (empty($orders[$fulfillment->DistributionCenterID])) $orders[$fulfillment->DistributionCenterID] = $order;

								$orders[$fulfillment->DistributionCenterID]['header']['distribution_center_id'] = $fulfillment->DistributionCenterID;

								// deal with items
								foreach ($ca_items as $idx => $item) {
									$fulfillment_qty = 0;
									foreach ($item->FulfillmentItems as $fi) {
										if ($fi->FulfillmentID == $fulfillment->ID) $fulfillment_qty += $fi->Quantity;
									}
									if ($fulfillment_qty <= 0) continue;

									$product = new ck_product_listing($item->Sku);

									if (!empty($orders[$fulfillment->DistributionCenterID]['products'][$product->id()])) {
										// items from Walmart come in as single units only, we need to roll them up
										/* // the price should always be the same, this code is intended to deal with unit price differences within the same unit
										// for now I've commented it out because I don't want it creating any unintended consequences.
										if ($orders[$fulfillment->DistributionCenterID]['products'][$product->id()]['final_price'] != $item->UnitPrice) {
											$line_price = $orders[$fulfillment->DistributionCenterID]['products'][$product->id()]['products_quantity'] * $orders[$fulfillment->DistributionCenterID]['products'][$product->id()]['final_price'];
											$line_price += $fulfillment_qty * $item->UnitPrice;
											$unit_price = $line_price / ($orders[$fulfillment->DistributionCenterID]['products'][$product->id()]['products_quantity'] + $fulfillment_qty);

											$orders[$fulfillment->DistributionCenterID]['products'][$product->id()]['products_price'] = $unit_price;
											$orders[$fulfillment->DistributionCenterID]['products'][$product->id()]['final_price'] = $unit_price;
											$orders[$fulfillment->DistributionCenterID]['products'][$product->id()]['display_price'] = $unit_price;
										}*/
										$orders[$fulfillment->DistributionCenterID]['products'][$product->id()]['products_quantity'] += $fulfillment_qty;
									}
									else {
										$price_reason = ck_cart::$price_reasons_key['original'];
										if ($product->get_price('reason') == 'special') $price_reason = ck_cart::$price_reasons_key['special'];
										$orders[$fulfillment->DistributionCenterID]['products'][$product->id()] = [
											'products_id' => $product->id(),
											'products_model' => $product->get_header('products_model'),
											'products_name' => $item->Title,
											'products_quantity' => $fulfillment_qty,
											'products_price' => $item->UnitPrice,
											'final_price' => $item->UnitPrice,
											'display_price' => $item->UnitPrice,
											'products_tax' => 0,
											'price_reason' => $price_reason,
											'option_type' => ck_cart::$option_types['NONE'],
										];
									}

									if (!empty($item->Promotions)) {
										foreach ($item->Promotions as $promo) {
											if (!empty($promo->Amount)) {
												$orders[$fulfillment->DistributionCenterID]['totals'][] = ['value' => -1*abs($promo->Amount), 'class' => 'ot_coupon', 'title' => 'Discount: '.$promo->Code];
												$subtotal -= abs($promo->Amount);
											}
											if (!empty($promo->ShippingAmount)) {
												$orders[$fulfillment->DistributionCenterID]['totals'][] = ['value' => -1*abs($promo->ShippingAmount), 'class' => 'ot_coupon', 'title' => 'Discount: '.$promo->Code];
												$subtotal -= abs($promo->ShippingAmount);
											}
										}

										unset($ca_items[$idx]->Promotions); // we only want to deal with a promo once, even if the item has multiple fulfillments
									}
								}

								$subtotal = 0;
								foreach ($orders[$fulfillment->DistributionCenterID]['products'] as $prod) {
									$subtotal += $prod['final_price'] * $prod['products_quantity'];
								}

								// deal with shipping method
								$shipping_method = '';
								$shipping_method_id = 0;

								$shipping_method = $fulfillment->ShippingClass;
								if (!empty(self::$shipping_method_map[$shipping_method])) $shipping_method_id = self::$shipping_method_map[$shipping_method];

								if (empty($shipping_method_id)) {
									$shipping_method_id = self::CK_STANDARD_FREE;
									$orders[$fulfillment->DistributionCenterID]['admin_notes'][] = [
										'orders_note_user' => ck_sales_order::$solutionsteam_id,
										'orders_note_text' => 'A shipping method could not be matched for this order. Please contact CK Technology Team.',
									];
								}

								// deal with totals
								// only add additional lines and costs to the order sourced from us
								$overall_total = $subtotal;
								if ($fulfillment->DistributionCenterID == $this->get_distribution_center_id('BUFORD')) {
									if (!empty($ca_order->AdditionalCostOrDiscount)) {
										$orders[$fulfillment->DistributionCenterID]['totals'][] = ['value' => $ca_order->AdditionalCostOrDiscount, 'class' => 'ot_custom'];
										$overall_total += $ca_order->AdditionalCostOrDiscount;
									}

									$orders[$fulfillment->DistributionCenterID]['totals'][] = ['value' => $ca_order->TotalShippingPrice, 'class' => 'ot_shipping', 'title' => $shipping_method, 'external_id' => $shipping_method_id];
									$overall_total += $ca_order->TotalShippingPrice;

									if (!empty($ca_order->PromotionAmount)) {
										$orders[$fulfillment->DistributionCenterID]['totals'][] = ['value' => -1*abs($ca_order->PromotionAmount), 'class' => 'ot_coupon', 'title' => !empty($ca_order->PromotionCode)?$ca_order->PromotionCode:'CA Promo'];
										$overall_total -= abs($ca_order->PromotionAmount);
									}

									if (!empty($ca_order->TotalTaxPrice)) {
										$orders[$fulfillment->DistributionCenterID]['totals'][] = ['value' => $ca_order->TotalTaxPrice, 'class' => 'ot_tax'];
										$overall += $ca_order->TotalTaxPrice;
									}
								}
								else {
									$orders[$fulfillment->DistributionCenterID]['totals'][] = ['value' => 0, 'class' => 'ot_shipping', 'title' => $shipping_method, 'external_id' => $shipping_method_id];
								}
								$orders[$fulfillment->DistributionCenterID]['totals'][] = ['value' => $overall_total, 'class' => 'ot_total'];
								$multi_shipment_total += $overall_total;

								// deal with packages
								$created = new DateTime($fulfillment->CreatedDateUtc);
								$created->setTimezone(new DateTimeZone('America/New_York'));

								$packages[] = [
									'package' => [
										'order_product_id' => 0,
										'package_type_id' => 1,
										'order_package_length' => 0,
										'order_package_width' => 0,
										'order_package_height' => 0,
										'scale_weight' => 0,
										'date_time_created' => $created->format('Y-m-d H:i:s')
									],
									'tracking' => [
										'tracking_num' => $fulfillment->TrackingNumber,
										'shipping_method_id' => $shipping_method_id,
										'cost' => 0,
										'invoiced_cost' => 0,
										'invoiced_weight' => 0,
										'date_time_created' => $created->format('Y-m-d H:i:s'),
									]
								];
							}

							if ($multi_shipment_total != $ca_order->TotalPrice) {
								$this->record_api_error('Figured Total did not match supplied total for multi-sourced order.', NULL, [$ca_order, $orders]);
								continue;
							}

							foreach ($orders as $distribution_center_id => &$order) {
								// create sales order
								if (!empty($parent_orders_id)) $order['header']['parent_orders_id'] = $parent_orders_id;

								$sales_order = ck_sales_order::create($order);
								$orders_id = $sales_order->id();

								if (empty($parent_orders_id)) $parent_orders_id = $orders_id;

								if ($distribution_center_id != $this->get_distribution_center_id('BUFORD')) {
									// create packages
									foreach ($packages as $package) {
										$sales_order->create_package($package);
									}

									// ship it
									$sales_order->ship();
								}
							}

							if (!self::$sandbox) {
								$exported[] = $ca_order_id;
								$order_updates[$ca_order_id] = ['SellerOrderID' => $parent_orders_id];
							}
						}
					}
					catch (CKCustomerException $e) {
						$this->record_api_error('Failed to create customer for order', $orders_id, [$ca_order, $e->xdebug_message, $e]);
						//throw $e;
					}
					catch (CKSalesOrderException $e) {
						$this->record_api_error('Failed to create order', $orders_id, [$ca_order, $e->xdebug_message, $e]);
						//throw $e;
					}
					catch (Exception $e) {
						$this->record_api_error('Failed to create order', $orders_id, [$ca_order, $e->xdebug_message, $e]);
						//throw $e;
					}
				}
				
				$url = !empty($result->{'@odata.nextLink'})?$result->{'@odata.nextLink'}:NULL;
				$order_query = NULL;
			}
			else {
				if (json_last_error() == JSON_ERROR_NONE) $debug = [$result];
				else $debug = [$result_raw];
				$this->record_api_error('Failed to retreive orders', NULL, $debug);
				break;
			}
		}
		while (!empty($url));

		if (!empty($exported)) $this->mark_order_exported($exported);
		if (!empty($order_updates)) $this->update_orders($order_updates);
	}

	public function export_shipped_orders(ck_sales_order $order=NULL) {
		$opts = [
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Authorization: Bearer '.self::$access_token
			],
			CURLINFO_HEADER_OUT => TRUE
		];

		try {
			if (empty($order)) $orders = ck_sales_order::get_shipped_ca_orders();
			else $orders = [$order];

			foreach ($orders as $order) {
				$rest = $this->new_rest_session($opts); // seems to fail if I don't set it up per call

				$url = self::$url.'/v1/Orders('.$order->get_header('ca_order_id').')/Ship';

				// by definition in the get_shipped_ca_orders() method, this order has invoices
				$invoice = $order->get_invoices()[0];
				$invoice['invoice_date']->setTimezone(new DateTimeZone('UTC'));

				// by definition in the get_shipped_ca_orders() method, this order has packages
				$packages = $order->get_packages();
				$tracking_number = NULL;
				foreach ($packages as $package) {
					if (!empty($package['void'])) continue;
					if (!empty($package['tracking_void'])) continue;
					if (empty($package['tracking_num'])) continue;

					$tracking_number = $package['tracking_num'];

					$shipping_method_id = $package['shipping_method_id'];
					$shipping_method = self::query_fetch('SELECT carrier, name as shipping_class FROM shipping_methods WHERE shipping_code = :shipping_method_id', cardinality::ROW, [':shipping_method_id' => $shipping_method_id]);
					$shipping_carrier = !empty($shipping_method['carrier'])?$shipping_method['carrier']:'UPS';

					$shipping_class = NULL;
					switch ($shipping_method_id) {
						case 48: $shipping_class = 'GROUND'; break;
						case 3: $shipping_class = 'OVERNIGHT'; break;
						case 43: $shipping_class = 'IPRIORITY'; break;
						default: $shipping_class = $shipping_method['shipping_class'];
					}
					break;
				}

				if (empty($tracking_number)) {
					$this->record_api_error('Order is missing tracking number or package', $order->id(), [$packages]);
					continue;
				}

				$shipment = [
					'Value' => [
						'ShippedDateUtc' => $invoice['invoice_date']->format('c'),
						'TrackingNumber' => $tracking_number,
						//'SellerFulfillmentID' => ,
						'DistributionCenterID' => $this->get_distribution_center_id('BUFORD'),
						'DeliveryStatus' => 'Complete',
						'ShippingCarrier' => $shipping_carrier,
						'ShippingClass' => $shipping_class
					]
				];

				$result = $rest->send('POST', [], $url, $shipment);

				self::rate_limiter();

                if (!$rest->statusOK()) {
                    $order->mark_channel_advisor_shipment_exported(FALSE);
                    $failed[$order->id()] = [$result];
                }
                else {
                    $order->mark_channel_advisor_shipment_exported(TRUE);
                }
            }
		}
		catch (Exception $e) {
			if (!empty($order)) {
				$failed[$order->id()] = 'Fatal error occurred: '.$e->getMessage();
			}
			else {
				$this->record_api_error('Fatal error retrieving shipped orders to export: '.$e->getMessage());
			}
		}

		if (!empty($failed)) {
			foreach ($failed as $orders_id => $details) {
				$this->record_api_error('Order shipment failed to export:<br>', $orders_id, [$details]);
			}
		}
	}

	private function record_api_error($msg, $orders_id=NULL, Array $variables=NULL) {
		$savepoint = self::transaction_begin();

		try {
			ob_start();
			var_dump($variables);
			$msg .= "<br>\n".ob_get_clean();

			self::query_execute('INSERT INTO ck_ca_shipping_export_errors (orders_id, error_message) VALUES (:orders_id, :error_message)', cardinality::NONE, [':orders_id' => $orders_id, ':error_message' => $msg]);
			self::$errors[] = ['orders_id' => $orders_id, 'msg' => $msg];
		}
		catch (Exception $e) {
			var_dump($e);
			// fail gracefully, I suppose
		}
	}

	public function has_api_errors() {
		return !empty(self::$errors);
	}

	public function alert_api_errors() {
        $mailer = service_locator::get_mail_service();
        $email = $mailer->create_mail();

		$admins = self::query_fetch("SELECT a.admin_email_address FROM ck_ca_config cc JOIN admin a ON cc.config_key = 'ca_admin' AND cc.config_val = a.admin_id", cardinality::COLUMN, []);

		if (empty($admins)) $admins = ['webmaster@cablesandkits.com'];
		foreach ($admins as $admin) {
			$email->add_to($admin);
		}

		$email->set_from('webmaster@cablesandkits.com');

		$email->set_subject('Channel Advisor API Error');

		$body = '';
		foreach (self::$errors as $err) {
			$body .= '[Order ID: '.$err['orders_id'].']<br>'.$err['msg'].'<br>----------------------------------------<br><br>';
		}

		$email->set_body($body);

		$mailer->send($mail);
	}

	public function debug_errors() {
		$this->record_api_error('Test Error', NULL, ['blah' => 'blah']);
	}
}

class ChannelAdvisorException extends CKMasterArchetypeException {
}
?>
