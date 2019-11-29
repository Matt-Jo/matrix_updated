<?php
class ck_view_forgot_password extends ck_view {

	protected $url = '/password_forgotten.php';

	protected $page_templates = [
		'forgot-password' => 'page-forgot-password.mustache.html',
	];

	private static $expiration_limit = 3600; // 1 hour

	public function process_response() {
		if ($_SESSION['cart']->is_logged_in()) CK\fn::redirect_and_exit('/account_password.php');
		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;

		switch ($_REQUEST['action']) {
			case 'process':
				$found = FALSE;
				$email = strtolower(trim($_POST['email_address']));
				if ($customers_id = self::query_fetch('SELECT customers_id FROM customers WHERE customers_email_address LIKE :email_address', cardinality::SINGLE, [':email_address' => $email])) {
					$found = TRUE;

					$customer = new ck_customer2($customers_id);

					$code = $customer->generate_forgot_password_code();

					$_SESSION['forgot_password_details'] = [
						'stage' => 1,
						'customers_id' => $customer->id(),
						'customers_extra_logins_id' => NULL,
						'email' => $email,
					];

					require_once(DIR_FS_CATALOG.'includes/engine/vendor/autoload.php');
					require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_content.class.php');
					require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_template.class.php');

					$content = new ck_content;

					$content->fqdn = 'https://'.FQDN;
					$content->media = 'https://media.cablesandkits.com';

					$content->reset_code = $code;
					$content->email = $email;
					$content->ip_address = $_SERVER['REMOTE_ADDR'];

					$cktpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
					$cktpl->buffer = TRUE;

					$mailer = service_locator::get_mail_service();
					$mail = $mailer->create_mail()
						->set_subject('CablesAndKits.com - Reset Your Password')
						->set_from('sales@cablesandkits.com', 'CablesAndKits.com Sales Team')
						->add_to($email, $customer->get_header('first_name').' '.$customer->get_header('last_name'));

					$mail->set_body($cktpl->content(DIR_FS_CATALOG.'includes/templates/email-confirm-password-reset.mustache.html', $content));
					
					try {
						$mailer->send($mail);
						$page = '/password_forgotten.php?code_sent=1';
					} catch( mail_service_exception $e) {
						$GLOBALS['messageStack']->add('password_forgotten', 'Error: We were unable to successfully send the confirmation code to your email address.');
					}
				}
				elseif ($cel = self::query_fetch('SELECT * FROM customers_extra_logins WHERE customers_emailaddress LIKE :email_address', cardinality::ROW, [':email_address' => $email])) {
					$found = TRUE;

					$customer = new ck_customer2($cel['customers_id']);

					$code = $customer->generate_forgot_password_code($cel['customers_extra_logins_id']);

					$_SESSION['forgot_password_details'] = [
						'stage' => 1,
						'customers_id' => $customer->id(),
						'customers_extra_logins_id' => $cel['customers_extra_logins_id'],
						'email' => $email
					];

					require_once(DIR_FS_CATALOG.'includes/engine/vendor/autoload.php');
					require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_content.class.php');
					require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_template.class.php');

					$content = new ck_content;

					$content->fqdn = 'https://'.FQDN;
					$content->media = 'https://media.cablesandkits.com';

					$content->reset_code = $code;
					$content->email = $email;
					$content->ip_address = $_SERVER['REMOTE_ADDR'];

					$cktpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
					$cktpl->buffer = TRUE;

					$mailer = service_locator::get_mail_service();
					$mail = $mailer->create_mail()
						->set_subject('CablesAndKits.com - Reset Your Password')
						->set_from('sales@cablesandkits.com', 'CablesAndKits.com Sales Team')
						->add_to($email, $cel['customers_firstname'].' '.$cel['customers_lastname'])
						->set_body(
							$cktpl->content(DIR_FS_CATALOG.'includes/templates/email-confirm-password-reset.mustache.html', $content)
						);

					try {
						$mailer->send($mail);
						$page = '/password_forgotten.php?code_sent=1';
					} catch(mail_service_exception $e) {
						$GLOBALS['messageStack']->add('password_forgotten', 'Error: We were unable to successfully send the confirmation code to your email address.');
					}
				}
				break;
			case 'confirm':
				if (empty($_SESSION['forgot_password_details'])) $GLOBALS['messageStack']->add('password_forgotten', 'Error: There was an error attempting to reset your password. Please try again.');
				elseif (empty($_SESSION['forgot_password_details']['customers_extra_logins_id'])) {
					$reset_code = self::query_fetch('SELECT reset_code FROM customers WHERE customers_id LIKE :customers_id AND reset_code IS NOT NULL AND TIMESTAMPDIFF(SECOND, reset_code_timestamp, NOW()) < :expiration_limit', cardinality::SINGLE, [':customers_id' => $_SESSION['forgot_password_details']['customers_id'], ':expiration_limit' => self::$expiration_limit]);

					if (empty($reset_code)) $GLOBALS['messageStack']->add('password_forgotten', 'Error: There was an error attempting to reset your password. Please try again.');
					elseif ($reset_code != trim($_REQUEST['confirmation_code'])) $GLOBALS['messageStack']->add('password_forgotten', 'Error: There was an error attempting to reset your password. Please try again.');
					else {
						$_SESSION['forgot_password_details']['confirmation_code'] = trim($_REQUEST['confirmation_code']);
						$_SESSION['forgot_password_details']['stage'] = 2;
						$page = '/password_forgotten.php?new_password=1';
					}
				}
				else {
					$reset_code = self::query_fetch('SELECT reset_code FROM customers_extra_logins WHERE customers_extra_logins_id LIKE :customers_extra_logins_id AND reset_code IS NOT NULL AND TIMESTAMPDIFF(SECOND, reset_code_timestamp, NOW()) < :expiration_limit', cardinality::SINGLE, [':customers_extra_logins_id' => $_SESSION['forgot_password_details']['customers_extra_logins_id'], ':expiration_limit' => self::$expiration_limit]);

					if (empty($reset_code)) $GLOBALS['messageStack']->add('password_forgotten', 'Error: There was an error attempting to reset your password. Please try again.');
					elseif ($reset_code != trim($_REQUEST['confirmation_code'])) $GLOBALS['messageStack']->add('password_forgotten', 'Error: There was an error attempting to reset your password. Please try again.');
					else {
						$_SESSION['forgot_password_details']['confirmation_code'] = trim($_REQUEST['confirmation_code']);
						$_SESSION['forgot_password_details']['stage'] = 2;
						$page = '/password_forgotten.php?new_password=1';
					}
				}
				break;
			case 'change-password':
				if (empty($_SESSION['forgot_password_details'])) $GLOBALS['messageStack']->add('password_forgotten', 'Error: There was an error attempting to reset your password. Please try again.');
				elseif (empty($_SESSION['forgot_password_details']['confirmation_code'])) $GLOBALS['messageStack']->add('password_forgotten', 'Error: There was an error attempting to reset your password. Please try again.');
				elseif ($_SESSION['forgot_password_details']['stage'] != 2) $GLOBALS['messageStack']->add('password_forgotten', 'Error: There was an error attempting to reset your password. Please try again.');
				else {
					if (empty($_SESSION['forgot_password_details']['customers_extra_logins_id'])) {
						$reset_code = self::query_fetch('SELECT reset_code FROM customers WHERE customers_id LIKE :customers_id AND reset_code IS NOT NULL AND TIMESTAMPDIFF(SECOND, reset_code_timestamp, NOW()) < :expiration_limit', cardinality::SINGLE, [':customers_id' => $_SESSION['forgot_password_details']['customers_id'], ':expiration_limit' => self::$expiration_limit]);

						if (empty($reset_code)) $GLOBALS['messageStack']->add('password_forgotten', 'Error: There was an error attempting to reset your password. Please try again.');
						elseif ($reset_code != $_SESSION['forgot_password_details']['confirmation_code']) $GLOBALS['messageStack']->add('password_forgotten', 'Error: There was an error attempting to reset your password. Please try again.');
						elseif (strlen($_POST['password']) < ck_customer2::$validation['password']['min_length']) $GLOBALS['messageStack']->add('password_forgotten', 'Error: Your Password must contain a minimum of '.ck_customer2::$validation['password']['min_length'].' characters.');
						elseif ($_POST['password'] != $_POST['password-confirm']) $GLOBALS['messageStack']->add('password_forgotten', 'Error: Your passwords do not match!');
						else {
							$customer = new ck_customer2($_SESSION['forgot_password_details']['customers_id']);

							$customer->update_password($_POST['password']);

							require_once(DIR_FS_CATALOG.'includes/engine/vendor/autoload.php');
							require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_content.class.php');
							require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_template.class.php');

							$content = new ck_content;

							$content->fqdn = 'https://'.FQDN;
							$content->media = 'https://media.cablesandkits.com';

							$content->contact_email = $customer->get_contact_email();

							$content->ip_address = $_SERVER['REMOTE_ADDR'];

							$cktpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
							$cktpl->buffer = TRUE;

							$mailer = service_locator::get_mail_service();
							$mail = $mailer->create_mail()
								->set_subject('CablesAndKits.com - Your Password Has Been Reset')
								->set_from($customer->get_contact_email(), 'CablesAndKits.com Sales Team')
								->add_to($customer->get_header('email_address'), $customer->get_header('first_name').' '.$customer->get_header('last_name'))
								->set_body(
									$cktpl->content(DIR_FS_CATALOG.'includes/templates/email-password-was-reset.mustache.html', $content)
								);

							$mailer->send($mail);

							$GLOBALS['messageStack']->add_session('login', "You've successfully reset your password.", 'success');
							$_SESSION['forgot_password_details'] = NULL;
							unset($_SESSION['forgot_password_details']);
							$page = '/login.php';
						}
					}
					else {
						$reset_code = self::query_fetch('SELECT reset_code FROM customers_extra_logins WHERE customers_extra_logins_id LIKE :customers_extra_logins_id AND reset_code IS NOT NULL AND TIMESTAMPDIFF(SECOND, reset_code_timestamp, NOW()) < :expiration_limit', cardinality::SINGLE, [':customers_extra_logins_id' => $_SESSION['forgot_password_details']['customers_extra_logins_id'], ':expiration_limit' => self::$expiration_limit]);

						if (empty($reset_code)) $GLOBALS['messageStack']->add('password_forgotten', 'Error: There was an error attempting to reset your password. Please try again.');
						elseif ($reset_code != $_SESSION['forgot_password_details']['confirmation_code']) $GLOBALS['messageStack']->add('password_forgotten', 'Error: There was an error attempting to reset your password. Please try again.');
						elseif (strlen($_POST['password']) < ck_customer2::$validation['password']['min_length']) $GLOBALS['messageStack']->add('password_forgotten', 'Error: Your Password must contain a minimum of '.ck_customer2::$validation['password']['min_length'].' characters.');
						elseif ($_POST['password'] != $_POST['password-confirm']) $GLOBALS['messageStack']->add('password_forgotten', 'Error: Your passwords do not match!');
						else {
							$customer = new ck_customer2($_SESSION['forgot_password_details']['customers_id']);

							$customer->update_password($_POST['password'], $_SESSION['forgot_password_details']['customers_extra_logins_id']);

							$cel = self::query_fetch('SELECT customers_firstname, customers_lastname, customers_emailaddress FROM customers_extra_logins WHERE customers_extra_logins_id = :customers_extra_logins_id', cardinality::ROW, [':customers_extra_logins_id' => $_SESSION['forgot_password_details']['customers_extra_logins_id']]);

							require_once(DIR_FS_CATALOG.'includes/engine/vendor/autoload.php');
							require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_content.class.php');
							require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_template.class.php');

							$content = new ck_content;

							$content->fqdn = 'https://'.FQDN;
							$content->media = 'https://media.cablesandkits.com';

							$content->ip_address = $_SERVER['REMOTE_ADDR'];

							$cktpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
							$cktpl->buffer = TRUE;

							$mailer = service_locator::get_mail_service();
							$mail = $mailer->create_mail()
								->set_subject('CablesAndKits.com - Your Password Has Been Reset')
								->set_from($customer->get_contact_email(), 'CablesAndKits.com Sales Team')
								->add_to($cel['customers_emailaddress'], $cel['customers_firstname'].' '.$cel['customers_lastname'])
								->set_body(
									$cktpl->content(DIR_FS_CATALOG.'includes/templates/email-password-was-reset.mustache.html', $content)
								);

							$mailer->send($mail);

							$GLOBALS['messageStack']->add_session('login', "You've successfully reset your password.", 'success');
							$_SESSION['forgot_password_details'] = NULL;
							unset($_SESSION['forgot_password_details']);
							$page = '/login.php';
						}
					}
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

		$GLOBALS['breadcrumb']->add('Login', '/login.php');
		$GLOBALS['breadcrumb']->add('Password Forgotten', '/password_forgotten.php');

		$data['breadcrumbs'] = $GLOBALS['breadcrumb']->trail();

		if (!empty($_GET['email']) && empty($_SESSION['forgot_password_details'])) {
			$email = strtolower(trim($_GET['email']));
			if ($customers_id = self::query_fetch('SELECT customers_id FROM customers WHERE customers_email_address LIKE :email_address AND reset_code IS NOT NULL AND TIMESTAMPDIFF(SECOND, reset_code_timestamp, NOW()) < :expiration_limit', cardinality::SINGLE, [':email_address' => $email, ':expiration_limit' => self::$expiration_limit])) {
				if (empty($_SESSION['forgot_password_details'])) $_SESSION['forgot_password_details'] = [];
				$_SESSION['forgot_password_details']['customers_id'] = $customers_id;
				$_SESSION['forgot_password_details']['email'] = $email;

				$customer = new ck_customer2($customers_id);
				$data['contact_email'] = $customer->get_contact_email();
			}
			elseif ($cel = self::query_fetch('SELECT * FROM customers_extra_logins WHERE customers_emailaddress LIKE :email_address AND reset_code IS NOT NULL AND TIMESTAMPDIFF(SECOND, reset_code_timestamp, NOW()) < :expiration_limit', cardinality::ROW, [':email_address' => $email, ':expiration_limit' => self::$expiration_limit])) {
				if (empty($_SESSION['forgot_password_details'])) $_SESSION['forgot_password_details'] = [];
				$_SESSION['forgot_password_details']['customers_id'] = $cel['customers_id'];
				$_SESSION['forgot_password_details']['customers_extra_logins_id'] = $cel['customers_extra_logins_id'];
				$_SESSION['forgot_password_details']['email'] = $email;

				$customer = new ck_customer2($cel['customers_id']);
				$data['contact_email'] = $customer->get_contact_email();
			}
		}

		if ($__FLAG['code_sent']) $data['code_sent?'] = 1;
		elseif ($__FLAG['confirm']) $data['confirm?'] = 1;
		elseif ($__FLAG['new_password']) $data['new_password?'] = 1;

		if ($GLOBALS['messageStack']->size('password_forgotten') > 0) $data['password_forgotten-error'] = $GLOBALS['messageStack']->output('password_forgotten');

		$this->render($this->page_templates['forgot-password'], $data);
		$this->flush();
	}
}


