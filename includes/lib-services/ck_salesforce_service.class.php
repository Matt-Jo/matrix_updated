<?php
class ck_salesforce_service extends ck_service {

	private $soap_server;
	private $soap_authenticated = NULL;

	public function __construct() {
		$this->set_service_type(self::SOAP);

		//prepared_query::execute('INSERT INTO ck_soap_api_tracker (service, method, args) VALUES (:service, :method, :args)', [':service' => 'salesforce', ':method' => 'init', ':args' => NULL]);

		$this->soap_server = new SoapServer(__DIR__.'/salesforce-1.0.wsdl');
		$this->soap_server->setObject($this);
	}

	protected function _authenticate() {
		return TRUE;
		//return is_null($this->soap_authenticated)?self::AUTH_DEFER:$this->soap_authenticated; // defer auth until we get to the header, and then re-run it
	}

	// understand what is being requested
	protected function _process_request() {
		// any pre-processing can be done here - remember that authentication has been deferred until soap_server->handle() is called
	}

	// perform any necessary actions
	protected function _act() {
		// this does everything - process_response and respond do nothing
		// for SOAP services, those methods are vestigial but could be utilized for pre- and pos-processing if necessary
		$this->soap_server->handle();
	}

	// craft and send the response	
	protected function _respond() {
		// this is what we're intending to respond with - it's handled by the `handle()` method, any required cleanup can be done here
		/*$response = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://soap.sforce.com/2005/09/outbound"><SOAP-ENV:Body><ns1:notificationsResponse><Ack>true</Ack></ns1:notificationsResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>';*/
	}

	/*----------------------------
	// soap methods below
	----------------------------*/

	/*public function AuthHeader($header) {
		$this->soap_authenticated = TRUE;
		$this->authenticate(); // re-run authentication, which has been deferred until this point
	}*/

	public function notifications($notification=NULL) {
		$sfcustomer = $notification->Notification->sObject;
		$customer = new ck_customer2($sfcustomer->Customer_ID__c);

		ob_start();

		echo '[owner id: '.$sfcustomer->OwnerId.']';
		$sf = $customer->get_sf();
		$admin_email = $sf->get_owner_email($sfcustomer->OwnerId);
		echo '[admin email: '.$admin_email.']';
		$admin = ck_admin::get_admin_by_email($admin_email);

		$business_unit_to_segment_map = [
			'' => NULL,
			'EU' => ck_customer2::$customer_segment_map['EU'],
			'RS' => ck_customer2::$customer_segment_map['RS'],
			'Other' => ck_customer2::$customer_segment_map['IN'],
			'UNEDA' => ck_customer2::$customer_segment_map['BD'],
			'MP' => ck_customer2::$customer_segment_map['MP'],
			'ST' => ck_customer2::$customer_segment_map['ST']
		];

		$sales_teams = ck_team::get_sales_teams();
		$team = [];

		foreach ($sales_teams as $sales_team) {
			$team[$sales_team->get_header('salesforce_key')] = $sales_team->id();
		}

		if (!empty($admin)) {
			// the new owner is an account manager, set the account manager, which will automatically put it on their sales team
			if ($admin->is('account_manager')) {
				echo '[set account manager]';
				$customer->change_account_manager($admin->id());
			}
			// the new owner is not an account manager, but they are on a sales team - remove account manager, but change the sales team
			elseif ($admin->has_sales_team()) {
				echo '[set sales team from owner, remove mgr]';
				$customer->change_account_manager(NULL);
				$customer->change_sales_team($admin->get_sales_team()['team']->id());
			}
			// the new owner is not an account manager and not on a sales team - remove the account manager, and change the sales team to whatever is currently set
			else {
				echo '[remove mgr, set sales team from sf]';
				$customer->change_account_manager(NULL);
				$customer->change_sales_team($team[$sfcustomer->Sales_Team__c]);
			}
		}
		else {
			echo '[set sales team from sf]';
			$customer->change_sales_team($team[$sfcustomer->Sales_Team__c]);
		}

		$customer->update(['customer_segment_id' => $business_unit_to_segment_map[$sfcustomer->Customer_Segment__c]]);

		$args = ob_get_clean();

		prepared_query::execute('INSERT INTO ck_soap_api_tracker (service, method, args) VALUES (:service, :method, :args)', [':service' => 'salesforce', ':method' => 'notifications', ':args' => $args]);

		$resp = new SoapVar('<ns1:notificationsResponse><Ack>true</Ack></ns1:notificationsResponse>', XSD_ANYXML); 
		return $resp;
	}

	/*public function __call($method, $args=[]) {
		prepared_query::execute('INSERT INTO ck_soap_api_tracker (service, method, args) VALUES (:service, :method, :args)', [':service' => 'salesforce', ':method' => '__call('.$method.')', ':args' => json_encode($args)]);
		return TRUE;
	}*/
}

class CKSalesforceServiceException extends CKServiceException {
}
?>
