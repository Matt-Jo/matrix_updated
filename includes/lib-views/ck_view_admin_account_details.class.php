<?php
class ck_view_admin_account_details extends ck_view {

	protected $url = '/admin/admin-account-details';
	protected $meta_title = 'Admin Account Details';

	protected static $queries = [];

	public function get_meta_title() {
		return $this->meta_title;
	}

	public function process_response() {
		$this->init([__DIR__.'/../../admin/includes/templates']);

		$__FLAG = request_flags::instance();

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	// messages
	protected $error = NULL;
	protected $success = NULL;

	private function psuedo_controller() {
		$page = '/admin/admin-account-details';
		switch ($_REQUEST['action']) {
			case 'update-password':
				$admin = ck_admin::login_instance(ck_admin::CONTEXT_ADMIN);
				if ($admin->revalidate_login($_POST['current_password'])) {
					if ($_POST['new_password'] === $_POST['new_password_confirmation']) {
						$update = TRUE;

						if (strlen($_POST['new_password']) < 7) {
							$this->error = 'Your password should be 8 or more characters';
							$update = FALSE;
						}
						elseif (preg_match('/\d/', $_POST['new_password']) == 0) {
							$this->error = 'Your Password should contain at least one number 0-9 letter';
							$update = FALSE;
						}
						elseif (preg_match('/[A-Z]/', $_POST['new_password']) == 0) {
							$this->error = 'Your Password should contain at least one uppercase letter';
							$update = FALSE;
						}
						elseif (preg_match('/\W/', $_POST['new_password']) == 0) {
							$this->error = 'Your Password should contain at least one special character';
							$update = FALSE;
						}

						if ($update && $admin->update_password($_POST['new_password'])) $this->success = 'Password Updated';
					}
					else $this->error = 'Password Mismatch';
				}
				else $this->error = 'Incorrect Password';

				if (!empty($this->error)) $page = NULL;
				break;
			case 'update-phone-number':
				$admin = ck_admin::login_instance(ck_admin::CONTEXT_ADMIN);
				if ($admin->update_phone_number($_POST['phone_number'])) $this->success = 'Phone Number Updated';
			default:
				break;
		}

		if (!empty($this->success)) {
			$_SESSION['success_message'] = $this->success;

			$mailer = service_locator::get_mail_service();
			$mail = $mailer->create_mail()
				->set_subject('Your Password Has Been Updated')
				->set_from('matrix@cablesandkits.com', 'Matrix')
				->add_to($admin->get_header('email_address'), $admin->get_header('first_name').' '.$admin->get_header('last_name'));

			$mail->set_body(sprintf('Hello %s,'."\n\n".'Your password has been changed. If this was done without your knowledge or consent please contact the administrator immediatly!'."\n\n".'Website : %s'."\n".'Username: %s'."\n".'Password: %s'."\n\n".'Thanks!'."\n".'%s'."\n\n".'This is a system automated response, please do not reply, as it would be unread!', $admin->get_header('first_name'), HTTP_SERVER.DIR_WS_ADMIN, $admin->get_header('email_address'), '*********', 'Your Friendly Neighborhood Matrix'));
			
			$mailer->send($mail);
		}

		if (!empty($page)) CK\fn::redirect_and_exit($page);

	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}

	private function ajax_response() {
		$response = [];

		switch ($_REQUEST['action']) {
			default:
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$data = $this->data();

		$admin = ck_admin::login_instance(ck_admin::CONTEXT_ADMIN);

		if (!empty($this->error)) $data['error'] = $this->error;

		if (!empty($_SESSION['success_message'])) $data['success'] = $_SESSION['success_message'];
		unset($_SESSION['success_message']);

		$data['first_name'] = $admin->get_header('first_name');
		$data['phone_number'] = $admin->get_header('phone_number');
		$data['name'] = $admin->get_header('first_name').' '.$admin->get_header('last_name');
		$data['email'] = $admin->get_header('email_address');
		$data['group'] = $admin->get_header('legacy_group');
		$data['account_created'] = $admin->get_header('date_created')->format('d-m-Y');
		$data['logins'] = $admin->get_header('login_counter');
		$data['last_access'] = $admin->get_header('last_login_date')->format('d-m-Y');
		$data['modified'] = $admin->get_header('last_modified_date')->format('d-m-Y');


		$this->render('page-admin-account-details.mustache.html', $data);
		$this->flush();
	}
}
?>
