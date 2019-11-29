<?php
//use GuzzleHttp\Client;
//use GuzzleHttp\Exception\ClientException;

class PaymentSvcApi extends ck_master_api {
	use rest_service;

	private $env_url = '';

	private $url = [
		config_service_interface::ENV_DEVELOPMENT => '10.2.0.13:8080', //'10.2.0.15:80', //
		config_service_interface::ENV_PRODUCTION => '10.2.0.15:80'
	];

	//private $client;
	public static $request_options = [ // protected
		CURLOPT_HTTPHEADER => [
			'Accept: application/json',
			//'Content-Type: application/json'
		]
	];

	public static $avs_postal_response_codes = [
		'M' => 'Matches',
		'N' => 'Does not match',
		'U' => 'Not verified',
		'I' => 'Not provided',
		'A' => 'Not applicable'
	];

	public static $avs_street_response_codes = [
		'M' => 'Matches',
		'N' => 'Does not match',
		'U' => 'Not verified',
		'I' => 'Not provided',
		'A' => 'Not applicable'
	];

	public static $cvv_response_codes = [
		'M' => 'Matches',
		'N' => 'Does not match',
		'U' => 'Not verified',
		'I' => 'Not provided',
		'S' => 'Issuer does not participate',
		'A' => 'Not applicable'
	];

	public function __construct() {
		$config = service_locator::get_config_service();
		/*$connect = ['base_uri' => $this->url[$config->get_env()]]; //config_service_interface::ENV_PRODUCTION]]; //
		//var_dump($connect);
		$this->client = new Client($connect);*/

		$this->env_url = $this->url[$config->get_env()];
		$this->rest = $this->new_rest_session(self::$request_options);
	}

	private function request($target, $data=[], $method='GET') {
		try {
			//$response = $this->client->request($method, $target, $data);
			$response = $this->rest->send($method, [], $this->env_url.$target, http_build_query($data)); //$data); //

			//echo $response;

			// we don't really differentiate between success and failure if the request/response happens successfully, but we might in the future
			if ($this->rest->statusOK()) return $response; //->getBody()->getContents();
			else return $response; //->getBody()->getContents();
		}
		/*catch (ClientException $e) {
			return $e->getResponse()->getBody()->getContents();
		}*/
		catch (Exception $e) {
			return $e->getMessage(); //getResponse()->getBody()->getContents();
		} 
	}

	public function getToken() {
		return $this->request('/gettoken');
	}

	/*-----------------------------
	// retrieve information
	-----------------------------*/

	public function findToken($paymentsvcId) {
		return $this->request('/transaction/'.$paymentsvcId);
	}

	public function getCustomer($customerId) {
		$c = $this->request('/customer/'.$customerId);
		/*$this->rest->debug(TRUE);
		var_dump($c);*/
		return $c;
	}

	public function getCustomerCards($customerId) {
		return $this->request('/cards/'.$customerId);
	}

	public function getPrivateCards($customerId) {
		return $this->request('/privatecards/'.$customerId);
	}

	public function findCard($token) {
		return $this->request('/card/'.$token);
	}

	public function getTransactionDetails($transactionId) {
		return $this->request('/transactiondetails/'.$transactionId);
	}

	public function checkTransactionStatus($transactionId) {
		return $this->request('/status/'.$transactionId);
	}

	/*-----------------------------
	// perform actions
	-----------------------------*/

	public function migrateData($data) {
		$request = ['form_params' => $data];
		return $this->request('/migrate', $request, 'POST');
	}

	public function createCustomer($customer) {
		$customer['FirstName'] = $customer['firstname'];
		$customer['LastName'] = $customer['lastname'];
		$customer['Email'] = $customer['email'];
		$customer['nonceFromClient'] = $customer['token'];

		return $this->request('/customer', $customer, 'POST');
	}

	public function addNewCardToCustomer($card) {
		/*$request = [
			'owner' => $card['owner'],
			'customerId' => $card['customerId'],
			'token' => $card['token'],
			'Name' => $card['cardholderName'],
			'privateCard' => $card['privateCard'],
		];*/

		return $this->request('/card', $card, 'POST');
	}

	public function unhideCard($data) {
		$request = [
			'brainTreeId' => $data["braintreeCustId"],
			'cardToken' => $data["token"]
		];

		return $this->request('/unhidecards/', $request, 'POST');
	}

	public function authorizeCCTransaction($transaction) {
		$request = $transaction;

		return $this->request('/charge/cc', $request, 'POST');

		//if ($response->getStatusCode() == '201') return $response->getBody()->getContents();
		//else return $response->getBody()->getContents();
	}

	public function authorizeMoTransaction($transaction) {
		$request = [
			'orderId' => $transaction['orderId'],
			'amount' => $transaction['amount'],
			'customerId' => $transaction['customerId'],
			'token' => 'AAA',
			'authorization' => true
		];

		return $this->request('/charge/mo', $request, 'POST');
	}

	public function authorizeMiscTransaction($transaction) {
		$request = [
			'orderId' => $transaction['orderId'],
			'amount' => $transaction['amount'],
			'customerId' => $transaction['customerId'],
			'token' => $transaction['token'],
			'methodType' => $transaction['methodType'],
			'authorization' => true,
		];

		return $this->request('/charge/misc', $request, 'POST');

		//if ($response->getStatusCode() == '201') return $response->getBody()->getContents();
		//else return $response->getBody()->getContents();
	}

	public function authorizeAccCreditTransaction($transaction) {
		$request = [
			'orderId' => $transaction['orderId'],
			'amount' => $transaction['amount'],
			'customerId' => $transaction['customerId'],
			'token' => 'AAA',
			'authorization' => true,
		];

		return $this->request('/charge/acc', $request, 'POST');

		//if ($response->getStatusCode() == '201') return $response->getBody()->getContents();
		//else return $response->getBody()->getContents();
	}

	public function authorizePaypalTransaction($transaction) {
		$request = $transaction;

		return $this->request('/charge/paypal', $request, 'POST');

		//if ($response->getStatusCode() == '201') return $response->getBody()->getContents();
		//else return $response->getBody()->getContents();
	}

	public function settleTransaction($data) {
		if (is_array($data)) {
			$request = [
				'transactionId' => $data['transactionId'],
				'amount' => $data['amount'],
				'orderId' => $data['orderId']
			];
		}
		else {
			$request = [
				'transactionId' => $data,
			];
		}

		return $this->request('/settletransaction', $request, 'POST');
	}

	public function partialSettlement($data) {
		$request = [
			'transactionId' => $data['transactionId'],
			'amount' => $data['amount'],
			'orderId' => $data['orderId']
		];

		return $this->request('/partialsettlement', $request, 'POST');
	}

	public function refundTransaction($transactionId) {
		return $this->request('/refundtransaction/'.$transactionId, [], 'POST');
	}

	public function voidTransaction($transactionId) {
		return $this->request('/voidtransaction/'.$transactionId, [], 'POST');
	}

	public function deleteCard($token) {
		return $this->request('/card/delete/'.$token, [], 'POST');
	}
}
