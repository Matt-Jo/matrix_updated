<?php
class ck_customer_account_addresses_view extends ck_view {

	protected $url = '/my-account/addresses';
	protected $errors = [];

	public $secondary_partials = TRUE;

	public function page_title() {
		return 'Addresses';
	}

	public function process_response() {
		if (!$_SESSION['cart']->is_logged_in()) CK\fn::redirect_and_exit('/login.php');
		$this->template_set = self::TPL_FULLWIDTH_FRONTEND_CUSTOMER_ACCOUNT;

		//if (!empty($_REQUEST['action'])) $this->psuedo_controller();
		//$this->respond();

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;

		$__FLAG = request_flags::instance();

		switch ($_REQUEST['action']) {
			case 'delete':
				$customer = $_SESSION['cart']->get_customer();
				$address = new ck_address2($_REQUEST['id']);
				if ($address->get_header('default_address') != 1) {
					$customer->delete_address($_REQUEST['id']);
					$GLOBALS['messageStack']->add('delete_address', 'You successfully deleted address', 'success');
				}
				else $GLOBALS['messageStack']->add('delete_address', 'You can not delete your default address');
				$page = '/my-account/addresses';
				break;
			case 'set-default':
				$customer = $_SESSION['cart']->get_customer();
				$customer->set_default_address($_REQUEST['id']);
				break;
			case 'edit-address':
				if (!empty($_POST['edit_address_id'])) {
					$customer = $_SESSION['cart']->get_customer();
					try {
						$address = $this->build_address_data();
						$customer->edit_address($_POST['edit_address_id'], $address);
					}
					catch (CKCustomerException $e) {
						$response['error'] = $e->getMessage();
					}
					catch (Exception $e) {
						$response['error'] = 'There was a problem saving this address.';
					}
				}
				$page = '/my-account/addresses';
				break;
			case 'create-address':
				$customer = $_SESSION['cart']->get_customer();
				try {
					$address = $this->build_address_data();
					$address_id = $customer->create_address($address);
					if (isset($_POST['default_address'])) $customer->set_default_address($address_id);
				}
				catch (CKCustomerException $e) {
					$response['error'] = $e->getMessage();
				}
				catch (Exception $e) {
					$response['error'] = 'There was a problem saving this address.';
				}
				$page = '/my-account/addresses';
				break;
			default:
				break;
		}

		if (!empty($page)) CK\fn::redirect_and_exit($page);
	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}

	private function ajax_response() {
		$response = NULL;
		switch($_REQUEST['action']) {
			case 'get-address-data':
				if (isset($_POST['address_id'])) {
					$address = new ck_address2($_REQUEST['address_id']);
					$response = [
						'first_name' => $address->get_header('first_name'),
						'last_name' => $address->get_header('last_name'),
						'company_name' => $address->get_header('company_name'),
						'address1' => $address->get_header('address1'),
						'address2' => $address->get_header('address2'),
						'postcode' => $address->get_header('postcode'),
						'country_id' => $address->get_header('countries_id'),
						'state_region_code' => $address->get_header('state_region_code'),
						'city' => $address->get_header('city'),
						'telephone' => $address->get_header('telephone'),
						'default_address' => $address->get_header('default_address'),
					];
				}
				break;
			case 'reload-zones':
				$response['states'] = ck_address2::get_regions($_GET['countries_id']);
				break;
			default:
				break;
		}

		if (!empty($response)) echo json_encode($response);
		else echo json_encode(['success' => false]);
		exit();
	}

	private function http_response() {
		$data = $this->data();
		$customer = new ck_customer2($_SESSION['customer_id']);
		$data['customer_addresses'] = [];

		if ($GLOBALS['messageStack']->size('delete_address') > 0) $data['address_message'] = $GLOBALS['messageStack']->output('delete_address');

		$default_address = $customer->get_default_address();
		$data['customer_addresses'][0] = [
			'id' => $default_address->id(),
			'name' => $default_address->get_header('first_name') . ' ' . $default_address->get_header('last_name'),
			'address_company' => $default_address->get_header('company_name'),
			'address1' => $default_address->get_header('address1'),
			'address2' => $default_address->get_header('address2'),
			'city' => $default_address->get_header('city'),
			'state' => $default_address->get_header('state_region_code'),
			'postcode' => $default_address->get_header('postcode'),
			'country' => $default_address->get_header('country'),
			'phone' => $default_address->get_header('telephone'),
			'default' =>  1
		];

		foreach ($customer->get_addresses() as $address) {
			if ($address->get_header('default_address') == 1) continue;
			$customer_address = [
				'id' => $address->id(),
				'name' => $address->get_header('first_name') . ' ' . $address->get_header('last_name'),
				'address_company' => $address->get_header('company_name'),
				'address1' => $address->get_header('address1'),
				'address2' => $address->get_header('address2'),
				'city' => $address->get_header('city'),
				'state' => $address->get_header('state_region_code'),
				'postcode' => $address->get_header('postcode'),
				'country' => $address->get_header('country'),
				'phone' => $address->get_header('telephone')
			];
			$data['customer_addresses'][] = $customer_address;
		}

		$data['validation'] = ck_customer2::$validation;

		$data['countries'] = ck_address2::get_countries();
		$data['default_country'] = !empty($default_address)?$default_address->get_header('countries_id'):ck_address2::DEFAULT_COUNTRY_ID; // 223/USA

		$this->render('page-customer-account-addresses.mustache.html', $data);
		$this->flush();
	}

	public function build_address_data() {
		try {
			$address = [
				'entry_firstname' => $_POST['first_name'],
				'entry_lastname' => $_POST['last_name'],
				'entry_company' => $_POST['company_name'],
				'entry_street_address' => $_POST['address1'],
				'entry_suburb' => $_POST['address2'],
				'entry_postcode' => $_POST['postcode'],
				'entry_city' => $_POST['city'],
				'entry_country_id' => $_POST['country_id'],
				'entry_telephone' => $_POST['telephone'],
			];
			if ($zone = ck_address2::get_zone($_POST['state'], $_POST['country_id'], TRUE)) {
				$address['entry_zone_id'] = $zone['zone_id'];
				$address['entry_state'] = '';
			}
			else {
				$address['entry_zone_id'] = 0;
				$address['entry_state'] = $_POST['state'];
			}
			return $address;
		}
		catch (Exception $e) {
		}
	}
}
?>