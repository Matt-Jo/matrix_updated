<?php
class api_hubspot extends ck_master_api {
	private static $access = [
		self::RUNTIME_PRODUCTION => [
			'api-key' => '6d736bb8-83fd-46d1-af2a-7e90bf26746d',
		],
		self::RUNTIME_DEVELOPMENT => [
			//'user-id' => '7385578',
			'api-key' => '0fecd860-6a45-4928-afc9-577de488a8b8',
			//'api-key' => '6d736bb8-83fd-46d1-af2a-7e90bf26746d', // production - just testing gets
			//'url' => 'Matrix-dev-5464365.com',
		],
	];

	private static $configured = FALSE;
	private static $api_handle;

	private static $data_maps = [
		'customer_type' => [
			0 => 'Regular',
			1 => 'Dealer',
		],
		'customer_segment' => [
			'IN' => 'Other',
			'EU' => 'EU',
			'RS' => 'RS',
			'BD' => 'UNEDA',
			'MP' => 'MP',
			'ST' => 'Student',
		],
	];

	private static $debug_container = [];

	public function __construct() {
		self::setup();
	}

	public static function setup() {
		if (!self::$configured) {
			self::set_runtime_context();
			// self::$api_key = self::$access[self::get_runtime_context()]['api-key'];

			$stack = GuzzleHttp\HandlerStack::create();
			$stack->push(GuzzleHttp\Middleware::history(self::$debug_container));

			self::$api_handle = SevenShores\Hubspot\Factory::create(self::$access[self::get_runtime_context()]['api-key'], NULL, ['http_errors' => FALSE, 'debug' => FALSE, 'handler' => $stack]); //]); //

			self::$configured = TRUE;
		}
	}

	public static function rate_limit($slow=FALSE) {
		$time = 120000; // .12 seconds
		if ($slow) $time *= 10;
		usleep($time);
	}

	public function learn() {
		$customer = new ck_customer2(88797);

		$link = $this->get_hubspot_company_link($customer);
		echo '<a href="'.$link.'" target="_blank">'.$link.'</a>';
	}

	public function get_hubspot_company_link(ck_customer2 $customer) {
		$link = NULL;

		$record_ids = $this->get_hubspot_record_ids($customer);

		if (!empty($record_ids['hubspot_company_id'])) {
			$res = self::$api_handle->integration()->getAccountDetails();
			if ($res->getStatusCode() == 200) {
				$link = 'https://app.hubspot.com/contacts/'.$res->data->portalId.'/company/'.$record_ids['hubspot_company_id'].'/';
			}
		}

		return $link;
	}

	public function get_hubspot_owner_id(ck_customer2 $customer) {
		$hubspot_owner_id = NULL;

		if ($customer->has_account_manager()) {
			$res = self::$api_handle->owners()->all(['email' => $customer->get_account_manager()->get_header('email_address')]);
			if (!empty($res->data)) $hubspot_owner_id = $res->data[0]->ownerId;
		}

		return $hubspot_owner_id;
	}

	public function get_hubspot_record_ids(ck_customer2 $customer, $email=NULL) {
		$record_ids = [
			'hubspot_company_id' => NULL,
			'hubspot_contact_id' => NULL,
		];

		if (empty($email)) $email = $customer->get_header('email_address');

		$res = self::$api_handle->contacts()->getByEmail($email);
		if ($res->getStatusCode() == 200) {
			$record_ids['hubspot_contact_id'] = $res->data->vid;
			if (!empty($res->data->properties->associatedcompanyid)) $record_ids['hubspot_company_id'] = $res->data->properties->associatedcompanyid->value;
		}
		elseif ($res->getStatusCode() != 404) throw new CKHubspotApiException('Hubspot lookup failed: '.$res->data->message);
		// else email wasn't found - expected scenario

		return $record_ids;
	}

	public function update_company(ck_customer2 $customer, $force_new=FALSE) {
		$summary = $customer->get_summary();

		$last_order_date = NULL;
		if (!empty($summary)) {
			$summary['last_order_booked_date']->setTimezone(new DateTimeZone('UTC'));
			$summary['last_order_booked_date']->remove_time();
			$last_order_date = $summary['last_order_booked_date']->format('U') * 1000; // milliseconds since epoch
		}

		$data = [
			['name' => 'name', 'value' => $customer->get_highest_name()],
			['name' => 'domain', 'value' => $customer->get_header('email_domain')],
			['name' => 'phone', 'value' => $customer->get_header('telephone')],
			['name' => 'customer_type__c', 'value' => self::$data_maps['customer_type'][$customer->get_header('customer_type')]],
			['name' => 'net_terms__c', 'value' => $customer->get_terms('label')],
			['name' => 'lifetime_value__c', 'value' => !empty($summary)?$summary['lifetime_order_value'] + $summary['pending_order_value']:NULL],
			['name' => 'lifetime_orders__c', 'value' => !empty($summary)?$summary['lifetime_order_count'] + $summary['pending_order_count']:NULL],
			['name' => 'last_order_date__c', 'value' => $last_order_date],
			['name' => 'customer_segment__c', 'value' => self::$data_maps['customer_segment'][$customer->get_header('segment_code')]],
			['name' => 'matrix_account_owner', 'value' => $this->get_hubspot_owner_id($customer)],
			['name' => 'sales_team__c', 'value' => $customer->has_sales_team()?$customer->get_sales_team()->get_header('salesforce_key'):NULL],
			['name' => 'customer_id__c', 'value' => $customer->id()],
		];

		$address = $customer->get_default_address();
		if (!empty($address)) {
			$data[] = ['name' => 'address', 'value' => $address->get_header('address1')];
			$data[] = ['name' => 'address2', 'value' => $address->get_header('address2')];
			$data[] = ['name' => 'city', 'value' => $address->get_header('city')];
			$data[] = ['name' => 'state', 'value' => $address->get_state()];
			$data[] = ['name' => 'zip', 'value' => $address->get_header('postcode')];
			$data[] = ['name' => 'country', 'value' => $address->get_header('countries_iso_code_2')];
		}

		$record_ids = $this->get_hubspot_record_ids($customer);

		if ($force_new || empty($record_ids['hubspot_company_id'])) {
			$res = self::$api_handle->companies()->create($data);
			if ($res->getStatusCode() != 200) throw new CKHubspotApiException('Could not create Hubspot company: '.$res->data->message);
			$record_ids['hubspot_company_id'] = $res->data->companyId;
		}
		else {
			$res = self::$api_handle->companies()->update($record_ids['hubspot_company_id'], $data);
			if ($res->getStatusCode() != 200) throw new CKHubspotApiException('Could not update Hubspot company: '.$res->data->message);
		}

		$customer->set_prop('hubspot_company_id', $record_ids['hubspot_company_id']);

		$this->update_contact($customer, $customer->get_header());
	}

	public function update_contact(ck_customer2 $customer, $contact) {
        if (!service_locator::get_mail_service()::validate_address($contact['email_address'])) return;

		$data = [
			['property' => 'firstname', 'value' => $contact['first_name']],
			['property' => 'lastname', 'value' => $contact['last_name']],
			['property' => 'email', 'value' => $contact['email_address']],
			//['property' => 'phone', 'value' => $customer->get_header('telephone')],
		];

		// get the company and contact ID for this email address, if they exist
		$record_ids = $this->get_hubspot_record_ids($customer, $contact['email_address']);

		if (empty($record_ids['hubspot_contact_id'])) {
			$res = self::$api_handle->contacts()->create($data);
			if ($res->getStatusCode() != 200) throw new CKHubspotApiException('Could not create Hubspot contact: '.$res->data->message);
			$record_ids['hubspot_contact_id'] = $res->data->vid;
		}
		else {
			$res = self::$api_handle->contacts()->update($record_ids['hubspot_contact_id'], $data);
			if ($res->getStatusCode() != 204) throw new CKHubspotApiException('Could not update Hubspot contact: '.$res->data->message);
		}

		// if the company was set on the customer, grab it from the customer
		if ($customer->isset_prop('hubspot_company_id')) $record_ids['hubspot_company_id'] = $customer->get_prop('hubspot_company_id');
		// if the company couldn't be found and it's *not* set on the customer, attempt to look up by the main contact
		elseif (empty($record_ids['hubspot_company_id'])) {
			$company_record_ids = $this->get_hubspot_record_ids($customer);
			$record_ids['hubspot_company_id'] = $company_record_ids['hubspot_company_id'];
		}

		if (empty($record_ids['hubspot_company_id'])) return; // if we haven't found a company record anywhere, don't attempt to attach contact to company

		$res = self::$api_handle->companies()->addContact($record_ids['hubspot_contact_id'], $record_ids['hubspot_company_id']);
		if ($res->getStatusCode() != 200) throw new CKHubspotApiException('Could not associate Hubspot contact to company: '.$res->data->message);
	}

	private static function debug_request($res, $show_body=FALSE) {
		if ($show_body) {
			$transaction = end(self::$debug_container);
			echo (string) $transaction['request']->getBody();
		}

		var_dump($res);
		echo "\n-----------------------------------\n-----------------------------------\n";
	}
}

class CKHubspotApiException extends CKApiException {
}
?>
