<?php
class ck_view_login extends ck_view {

	protected $url = '/login.php';

	protected $page_templates = [
		'login' => 'page-login.mustache.html',
	];

	public static $validation = [
		'first_name' => ['min_length' => 2, 'max_length' => 32],
		'last_name' => ['min_length' => 2, 'max_length' => 32],
		'email_address' => ['min_length' => 6, 'max_length' => 96],
		'address1' => ['min_length' => 5],
		'company_name' => ['min_length' => 2],
		'postcode' => ['min_length' => 4],
		'city' => ['min_length' => 3],
		'state' => ['min_length' => 2],
		'telephone' => ['min_length' => 3],
		'password' => ['min_length' => 5]
	];

	private static $default_page_target = '/shopping_cart.php';

	public function process_response() {
		if ($_SESSION['cart']->is_logged_in()) CK\fn::redirect_and_exit(self::$default_page_target);
		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;

		switch ($_REQUEST['action']) {
			case 'login':
				$login = ck_customer2::attempt_login($_POST['email_address'], $_POST['password'], TRUE);

				if ($login['status'] == ck_customer2::LOGIN_STATUS_FAIL) {
					$GLOBALS['messageStack']->add('login', 'That E-Mail Address/Password combination is not valid.');
				}
				elseif ($login['status'] == ck_customer2::LOGIN_STATUS_PASS) {
					service_locator::get_session_service()->regenerate_id();

					$account = $login['account'];

					$customer = new ck_customer2($account['account_id']);
					if (!empty($login['reencrypt'])) $customer->update_password($login['reencrypt'], $account['customers_extra_logins_id']);

					$address = $customer->get_addresses('default');
					if ($address) $address = $address->get_header();

					$_SESSION['customer_id'] = $customer->id();
					$_SESSION['customer_default_address_id'] = $account['customers_default_address_id'];
					$_SESSION['customer_first_name'] = $account['customers_firstname'];
					$_SESSION['customer_last_name'] = $account['customers_lastname'];
					$_SESSION['customer_country_id'] = @$address['countries_id'];
					$_SESSION['customer_zone_id'] = @$address['zone_id'];
					$_SESSION['customer_is_dealer'] = $customer->is('dealer');
					$_SESSION['customer_extra_login_id'] = $account['customers_extra_logins_id'];
					$_SESSION['braintree_customer_id'] = $account['braintree_customer_id'];

					if (isset($_COOKIE['osCAdminID2'])) {
						try {
							if ($admin_session_string = prepared_query::fetch('SELECT value FROM sessions WHERE sesskey = :sesskey AND (modified + expiry) > :exp', cardinality::SINGLE, [':sesskey' => $_COOKIE['osCAdminID2'], ':exp' => time()])) {
								ck_session::assign_admin_user_login($admin_session_string);
							}
						}
						catch (Exception $e) {
							// don't need to do anything
						}
					}

					self::query_execute('UPDATE customers_info SET customers_info_date_of_last_logon = NOW(), customers_info_number_of_logons = customers_info_number_of_logons + 1 WHERE customers_info_id = :customers_id', cardinality::NONE, [':customers_id' => $customer->id()]);

					// restore cart contents
					$_SESSION['cart']->rebuild_cart();

					if (!empty($_POST['target_page'])) $page = $_POST['target_page'];
					elseif (!empty($_SESSION['navigation']->snapshot)) $page = '/'.$_SESSION['navigation']->snapshot['page'].'?'.http_build_query($_SESSION['navigation']->snapshot['get']);
					else $page = self::$default_page_target;

					// don't trust the data from the client - chop off any included domain.
					// An attacker couldn't redirect an unsuspecting user through the form builder so this may be redundant, but no sense in leaving the hole unplugged.
					$target_page = parse_url($page);
					$page = $target_page['path'];
					if (!empty($target_page['query'])) $page .= '?'.$target_page['query'];
					if (!empty($target_page['fragment'])) $page .= '#'.$target_page['fragment'];

					if (!empty($_SESSION['navigation']->snapshot)) $_SESSION['navigation']->clear_snapshot();
				}
				break;
			case 'create-account':

				if (!service_locator::get_session_service()->session_exists()) CK\fn::redirect_and_exit('/cookie_usage.php');

				$__FLAG = request_flags::instance();

				$errors = [];

				foreach ($_POST as $key => $val) {
					if (is_scalar($val)) $_POST[$key] = trim($val);
				}

				try {
					$country_id = $_POST['country'];

					if (strlen($_POST['firstname']) < self::$validation['first_name']['min_length']) $errors[] = 'Your First Name must contain a minimum of '.self::$validation['first_name']['min_length'].' characters.';
					if (strlen($_POST['lastname']) < self::$validation['last_name']['min_length']) $errors[] = 'Your Last Name must contain a minimum of '.self::$validation['last_name']['min_length'].' characters.';

					if (strlen($_POST['email_address']) < self::$validation['email_address']['min_length']) $errors[] = 'Your Email Address must contain a minimum of '.self::$validation['email_address']['min_length'].' characters.';
					elseif (!filter_var($_POST['email_address'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Your Email Address does not appear to be valid - please make any necessary corrections.';
					elseif ($_POST['email_address'] !== $_POST['confirm_email_address']) $errors[] = 'The Email Address Confirmation must match your Email Address.';
					elseif (ck_customer2::email_exists($_POST['email_address'])) $errors[] = 'An Account has already been created using that E-Mail Address. If you do not remember your password please click "Password Forgotten" below.';

					if (strlen($_POST['street_address']) < self::$validation['address1']['min_length']) $errors[] = 'Your Street Address must contain a minimum of '.self::$validation['address1']['min_length'].' characters.';
					if (strlen($_POST['city']) < self::$validation['city']['min_length']) $errors[] = 'Your City must contain a minimum of '.self::$validation['city']['min_length'].' characters.';
					if (strlen($_POST['postcode']) < self::$validation['postcode']['min_length']) $errors[] = 'Your Zip Code must contain a minimum of '.self::$validation['postcode']['min_length'].' characters.';
					if (!is_numeric($country_id)) $errors[] = 'You must select a country from the Countries pull down menu.';
					if (empty($_POST['state'])) $errors[] = 'You must select or enter a State or Region.';
					else {
						try {
							if ($zone = ck_address2::get_zone($_POST['state'], $country_id, TRUE)) {
								$zone_id = $zone['zone_id'];
								$state = '';
							}
							elseif (strlen($_POST['state']) >= self::$validation['state']['min_length']) {
								$zone_id = 0;
								$state = $_POST['state'];
							}
							else $errors[] = 'Your State must contain a minimum of '.self::$validation['state']['min_length'].' characters.';
						}
						catch (CKAddressException $e) {
							$errors[] = 'Please select a state from the States pull down menu.';
						}
					}

					if (strlen($_POST['telephone']) < self::$validation['telephone']['min_length']) $errors[] = 'Your Telephone Number must contain a minimum of '.self::$validation['telephone']['min_length'].' characters.';
 					// if send_customer_password is set we aren't going to try to validate the password because there souldn't be one
					$send_customer_password = TRUE;
					if (!isset($_POST['send_customer_password'])) {
						$send_customer_password = FALSE;
						if (strlen($_POST['password']) < self::$validation['password']['min_length']) $errors[] = 'Your Password must contain a minimum of '.self::$validation['password']['min_length'].' characters.';
						elseif ($_POST['password'] !== $_POST['confirmation']) $errors[] = 'The Password Confirmation must match your Password.';
					}

					if (empty($_POST['customer_segment'])) $errors[] = 'Please indicate how you intend to use our products.';

					if (!empty($errors)) {
						foreach ($errors as $error) {
							$GLOBALS['messageStack']->add('create_account', $error);
						}
					}
					else {
						// if send_customer_password isset then we've made it this far without a password and will set it to 'NONE'. This is happening because an admin is creating the customer account
						$customer_password = 'NONE';
						// otherwise we do have a password and we'll run the encryption on the customer created password
						if (isset($_POST['password'])) $customer_password = ck_customer2::encrypt_password($_POST['password']);

						$data = [
							'header' => [
								'customers_firstname' => $_POST['firstname'],
								'customers_lastname' => $_POST['lastname'],
								'customers_email_address' => $_POST['email_address'],
								'customers_telephone' => $_POST['telephone'],
								'customers_fax' => NULL,
								'customers_newsletter' => $__FLAG['newsletter']&&($country_id!=38||$__FLAG['canada_opt_in'])?1:0,
								'customers_password' => $customer_password,
								'password_info' => 0,
								'customer_segment_id' => ck_customer2::$customer_segment_map[$_POST['customer_segment']],
							],
							'address' => [
								'entry_firstname' => $_POST['firstname'],
								'entry_lastname' => $_POST['lastname'],
								'entry_company' => $_POST['company'],
								'entry_street_address' => $_POST['street_address'],
								'entry_suburb' => $_POST['suburb'],
								'entry_postcode' => $_POST['postcode'],
								'entry_city' => $_POST['city'],
								'entry_country_id' => $country_id,
								'entry_telephone' => $_POST['telephone'],
								'entry_zone_id' => $zone_id,
								'entry_state' => $state,
							]
						];

						$customer = ck_customer2::create($data);

						$header = $customer->get_header();
						$address = $customer->get_addresses('default')->get_header();

						$_SESSION['customer_id'] = $customer->id();
						$_SESSION['login_email_address'] = $_POST['email_address'];
						$_SESSION['customer_default_address_id'] = $header['default_address_id'];
						$_SESSION['customer_first_name'] = $header['first_name'];
						$_SESSION['customer_last_name'] = $header['last_name'];
						$_SESSION['customer_country_id'] = $address['countries_id'];
						$_SESSION['customer_zone_id'] = $address['zone_id'];
						$_SESSION['customer_is_dealer'] = $customer->is('dealer');
						$_SESSION['customer_extra_login_id'] = 0;
						$_SESSION['braintree_customer_id'] = NULL;

						$_SESSION['cart']->rebuild_cart();

						// we're sending these welcome emails from hubspot now
						/*try {
							require_once(DIR_FS_CATALOG.'includes/engine/vendor/autoload.php');
							require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_content.class.php');
							require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_template.class.php');

							$content = new ck_content;

							$content->fqdn = 'https://'.FQDN;
							$content->media = 'https://media.cablesandkits.com';

							$cktpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
							$cktpl->buffer = TRUE;

							$user_list = '';
							$team_link = '';
							$return_link = '';

							$mailer = service_locator::get_mail_service();
							$mail = $mailer->create_mail()
								->set_subject('Welcome to CablesAndKits.com')
								->add_to($_POST['email_address'], $_POST['firstname'].' '.$_POST['lastname']);
							

							switch ($customer->get_header('sales_team_id')) {
								case 1:
									if (empty($user_list)) $user_list = 'Jonathan, Mandy, Sarah';
									if (empty($team_link)) $team_link = 'https://www.cablesandkits.com/resale-team-i-26.html';
									if (empty($return_link)) $return_link = 'https://www.cablesandkits.com/pi/returns-resale01';
								case 2:
									if (empty($user_list)) $user_list = 'Aaron and Shanyce';
									if (empty($team_link)) $team_link = 'https://www.cablesandkits.com/sales-team-02-i-27.html';
									if (empty($return_link)) $return_link = 'https://www.cablesandkits.com/pi/returns-sales02';
								case 3:
									if (empty($user_list)) $user_list = 'Martin and Kaitlyn';
									if (empty($team_link)) $team_link = 'https://www.cablesandkits.com/sales-team-03-i-28.html';
									if (empty($return_link)) $return_link = 'https://www.cablesandkits.com/pi/returns-sales03';

									$mail->set_from($customer->get_contact_email(), 'CablesAndKits.com Sales Team');
									$content->signature = '<strong style="font-size:18px; color:#555;">'.$user_list.'</strong><br><strong style="color:#e51937; font-style:italic;">Your Dedicated Sales Team</strong><br><a href="mailto:'.$customer->get_contact_email().'" style="font-weight:bold;">'.$customer->get_contact_email().'</a><br><span style="font-size:14px;">P: '.$customer->get_contact_phone().' |</span> <a href="https://www.cablesandkits.com" style="color:#888;">www.cablesandkits.com</a><br><strong style="color:#e51937; font-size:14px;"><a href="'.$team_link.'" style="color:#e51937; text-decoration:none;">Team Page</a> | <a href="'.$return_link.'" style="color:#e51937; text-decoration:none;">Returns</a> | <a href="https://www.cablesandkits.com/dow" style="color:#e51937; text-decoration:none;">Deal of the Week</a> | <a href="https://www.cablesandkits.com/custserv.php" style="color:#e51937; text-decoration:none;">FAQ</a></strong>';

									$content->contact_email = $customer->get_contact_email();

									break;
								default:
									$mail->set_from('sales@cablesandkits.com', 'CablesAndKits.com Sales Team');
									$content->contact_email = 'sales@cablesandkits.com';
									break;
							}

							$content->send_customer_password = $send_customer_password;

							$mail->set_body($cktpl->content(DIR_FS_CATALOG.'includes/templates/email-new_account.mustache.html', $content));
							$mailer->send($mail);
						}
						catch (Exception $e) {
							// if we can't send the email, that's not a fatal error, just let it go through
							// @todo: log this
						}*/

						// if we created the account for the customer, we need to send them a notice that they need to change their password
						if ($send_customer_password) {
							try {

								$customer->generate_forgot_password_code();
								$customer->set_account_setup();

								$code = $customer->generate_forgot_password_code();
								$email = $_POST['email_address'];

								require_once(DIR_FS_CATALOG.'includes/engine/vendor/autoload.php');
								require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_content.class.php');
								require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_template.class.php');

								$content = new ck_content;

								$content->fqdn = 'https://'.PRODUCTION_FQDN;
								$content->media = 'https://media.cablesandkits.com';

								$content->reset_code = $code;
								$content->email = $email;
								$content->customer_id = $customer->id();

								$cktpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
								$cktpl->buffer = TRUE;

								$mailer = service_locator::get_mail_service();
								$mail = $mailer->create_mail()
									->set_subject('CablesAndKits.com - Setup Your Account')
									->set_from('sales@cablesandkits.com', 'CablesAndKits.com Sales Team')
									->add_to($email, $customer->get_header('first_name').' '.$customer->get_header('last_name'))
									->set_body(
										$cktpl->content(DIR_FS_CATALOG.'includes/templates/email-customer-account-setup.mustache.html', $content)
									);

								$mailer->send($mail);
							} catch (Exception $e) {
								// if we can't send the email, that's not a fatal error, just let it go through
								// @todo: log this
							}
						}

						if (!empty($_POST['target_page'])) $page = $_POST['target_page'];
						elseif (!empty($_SESSION['navigation']->snapshot)) $page = '/'.$_SESSION['navigation']->snapshot['page'].'?'.http_build_query($_SESSION['navigation']->snapshot['get']);
						else $page = self::$default_page_target;

						// don't trust the data from the client - chop off any included domain.
						// An attacker couldn't redirect an unsuspecting user through the form builder so this may be redundant, but no sense in leaving the hole unplugged.
						$target_page = parse_url($page);
						$page = $target_page['path'];
						if (!empty($target_page['query'])) $page .= '?'.$target_page['query'];
						if (!empty($target_page['fragment'])) $page .= '#'.$target_page['fragment'];

						if (!empty($_SESSION['navigation']->snapshot)) $_SESSION['navigation']->clear_snapshot();
					}
				}
				catch (CKCustomerException $e) {
					$GLOBALS['messageStack']->add('create_account', $e->getMessage());
				}
				catch (Exception $e) {
					$GLOBALS['messageStack']->add('create_account', 'Unkown error creating account.  Please contact our CK Sales Team.');
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
		// there's nothing to do here

		echo json_encode(['errors' => []]);
		exit();
	}

	private function http_response() {
		$data = $this->data();

		$__FLAG = request_flags::instance();

		$data['validation'] = self::$validation;

		if ($GLOBALS['messageStack']->size('login') > 0) $data['login-error'] = $GLOBALS['messageStack']->output('login');
		if ($GLOBALS['messageStack']->size('create_account') > 0) $data['create-account-error'] = $GLOBALS['messageStack']->output('create_account');

		if (!empty($_REQUEST['target_page'])) $target_page = $_REQUEST['target_page'];
		elseif (!empty($_REQUEST['login_return_to'])) $target_page = $_REQUEST['login_return_to'];
		elseif (!empty($_SESSION['navigation']->snapshot)) $target_page = '/'.$_SESSION['navigation']->snapshot['page'].'?'.http_build_query($_SESSION['navigation']->snapshot['get']);
		elseif (!empty($_SERVER['HTTP_REFERER'])) $target_page = $_SERVER['HTTP_REFERER'];
		else $target_page = self::$default_page_target;

		// don't trust the data from the client - chop off any included domain
		$target_page = parse_url($target_page);
		$data['target_page'] = $target_page['path'];
		if (!empty($target_page['query'])) $data['target_page'] .= '?'.$target_page['query'];
		if (!empty($target_page['fragment'])) $data['target_page'] .= '#'.$target_page['fragment'];

		if (in_array($data['target_page'], ['/logoff.php', '/password_forgotten.php'])) $data['target_page'] = self::$default_page_target;

		$data['countries'] = ck_address2::get_countries();
		$country_id = !empty($_REQUEST['country'])?$_REQUEST['country']:ck_address2::DEFAULT_COUNTRY_ID; // 223/USA
		foreach ($data['countries'] as &$country) {
			if ($country['countries_id'] == $country_id) $country['selected?'] = 1;
		}

		$data['states'] = ck_address2::get_regions($country_id);
		if (!empty($_REQUEST['state']) && !empty($data['states'])) {
			foreach ($data['states'] as &$state) {
				if ($state['zone_name'] == $_REQUEST['state']) $state['selected?'] = 1;
			}
		}
		elseif (!empty($_REQUEST['state'])) $data['state'] = $_REQUEST['state'];

		if ($country_id == 38) $data['canadian?'] = 1;
		if ($__FLAG['canada_opt_in']) $data['canada_opt_in'] = 1;

		if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'login') {
			$data['login_email_address'] = $_REQUEST['email_address'];
		}

		if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'create-account') {
			$data['firstname'] = $_REQUEST['firstname'];
			$data['lastname'] = $_REQUEST['lastname'];
			$data['company'] = $_REQUEST['company'];
			$data['street_address'] = $_REQUEST['street_address'];
			$data['suburb'] = $_REQUEST['suburb'];
			$data['city'] = $_REQUEST['city'];
			$data['postcode'] = $_REQUEST['postcode'];
			$data['telephone'] = $_REQUEST['telephone'];
			$data['create_email_address'] = $_REQUEST['email_address'];
			$data[$_REQUEST['customer_segment'].'?'] = 1;
		}

		if (CK\fn::check_flag(@$_SESSION['admin'])) $data['admin?'] = 1;

		$this->render($this->page_templates['login'], $data);
		$this->flush();
	}
}

?>
