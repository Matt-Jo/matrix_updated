<?php
class ck_customer_account_info_view extends ck_view {

	protected $url = '/my-account/info';
	protected $errors = [];

	public $secondary_partials = TRUE;

	public function page_title() {
		return 'Account Info';
	}

	public function process_response() {
		if (!$_SESSION['cart']->is_logged_in()) CK\fn::redirect_and_exit('/login.php');
		$this->template_set = self::TPL_FULLWIDTH_FRONTEND_CUSTOMER_ACCOUNT;

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;

		switch ($_REQUEST['action']) {
			case 'update-account-information':
				try {
					$customer = $_SESSION['cart']->get_customer();
					$errors = [];
					$data = [];

					//first name validation
					$first_name = trim($_POST['first_name']);
					if ($first_name != $customer->get_header('first_name')) {
						if (strlen($first_name) < ck_customer2::$validation['entry_firstname']['min_length']) $errors[] = ck_customer2::$validation['entry_firstname']['error'];
						else $data['customers_firstname'] = $first_name;
					}

					// last name validation
					$last_name = trim($_POST['last_name']);
					if ($last_name != $customer->get_header('last_name')) {
						if (strlen($last_name) < ck_customer2::$validation['entry_lastname']['min_length']) $errors[] = ck_customer2::$validation['entry_lastname']['error'];
						else $data['customers_lastname'] = $last_name;
					}

					// email address validation
					$email = trim($_POST['email']);
					if ($email != $customer->get_header('email_address')) {
						if (strlen($email) < ck_customer2::$validation['email']['min_length']) $errors[] = ck_customer2::$validation['email']['error'];
						elseif (ck_customer2::email_exists($email)) $errors[] = 'That email address is not available.';
						elseif (!service_locator::get_mail_service()::validate_address($email)) $errors[] =  'The email address doesn\'t appear to be valid!';
						else $data['customers_email_address'] = $email;
					}

					// phone number validation
					$phone_number = trim($_POST['phone_number']);
					if ($phone_number != $customer->get_header('telephone')) {
						if (strlen($phone_number) < ck_customer2::$validation['entry_telephone']['min_length']) $errors[] = ck_customer2::$validation['entry_telephone']['error'];
						else $data['customers_telephone'] = $phone_number;
					}

					//customer segment
					if (empty($_POST['customer_segment'])) $errors[] = 'Please indicate how you intend to use our products.';
					elseif (ck_customer2::$customer_segment_map[$_POST['customer_segment']] != $customer->get_header('customer_segment_id')) {
						$data['customer_segment_id'] = ck_customer2::$customer_segment_map[$_POST['customer_segment']];
					}

					// handle errors or process data
					if (!empty($errors)) foreach ($errors as $error) $GLOBALS['messageStack']->add('update_account', $error);
					elseif (!empty($data)) {
						$GLOBALS['messageStack']->add('update_account', 'Your account was successfully updated', 'success');
						$customer->update($data);
					}

				}
				catch (CKCustomerException $e) {
					$GLOBALS['messageStack']->add('update_account', $e->getMessage());
				}
				catch (Exception $e) {
					$GLOBALS['messageStack']->add('update_account', 'Unkown error updating account information. Please contact our CK Sales Team.');
				}
				break;
			case 'update-account-password':
				try {
					$password_current = $_POST['password_current'];
					$password_new = $_POST['password_new'];
					$password_confirmation = $_POST['password_confirmation'];

					$errors = [];

					if (strlen($password_new) < ck_customer2::$validation['password']['min_length']) $errors[] = ck_customer2::$validation['password']['error'];
					elseif ($password_new != $password_confirmation) $errors[] = 'The Password Confirmation must match your New Password.';
					else {
						$customer = $_SESSION['cart']->get_customer();

						if (!$customer->revalidate_login($password_current)) $errors[] = 'Your Current Password did not match the password in our records. Please try again.';
					}

					if (!empty($errors)) foreach ($errors as $error) $GLOBALS['messageStack']->add('update_account_password', $error);
					else {
						$customer->update_password($password_new, $_SESSION['customer_extra_login_id']);
						$GLOBALS['messageStack']->add('update_account_password', 'Your password has been successfully updated.', 'success');
					}
				}
				catch (CKCustomerException $e) {
					$GLOBALS['messageStack']->add('update_account_password', $e->getMessage());
				}
				catch (Exception $e) {
					$GLOBALS['messageStack']->add('update_account_password', 'Unkown error updating account.  Please contact our CK Sales Team.');
				}
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
		if (!empty($results)) echo json_encode($results);
		else echo json_encode(['success' => false]);
		exit();
	}

	private function http_response() {
		$data = $this->data();
		$customer = new ck_customer2($_SESSION['customer_id']);

		if ($GLOBALS['messageStack']->size('update_account') > 0) $data['info_message'] = $GLOBALS['messageStack']->output('update_account');
		if ($GLOBALS['messageStack']->size('update_account_password') > 0) $data['password_message'] = $GLOBALS['messageStack']->output('update_account_password');

		$data['first_name'] = $customer->get_header('first_name');
		$data['last_name'] = $customer->get_header('last_name');
		$data['email'] = $customer->get_header('email_address');
		$data['phone_number'] = $customer->get_header('telephone');

		$customer_segment_id = $customer->get_header('customer_segment_id');

		if ($customer_segment_id == 1) $data['individual'] = 1;
		elseif ($customer_segment_id == 2) $data['end_user'] = 1;
		elseif ($customer_segment_id == 3) $data['reseller'] = 1;
		elseif ($customer_segment_id == 6) $data['student'] = 1;

		$this->render('page-customer-account-info.mustache.html', $data);
		$this->flush();
	}
}
?>