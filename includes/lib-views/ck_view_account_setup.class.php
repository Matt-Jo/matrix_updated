<?php
class ck_view_account_setup extends ck_view {

	protected $url = '/account-setup';

	protected $page_templates = [
		'account-setup' => 'page-account-setup.mustache.html',
	];

	public function process_response() {
		$redirect = '';

		if ($_SESSION['cart']->is_logged_in()) $redirect = '/';
		else if (isset($_REQUEST['customers_id'])) {
			$customer = new ck_customer2($_REQUEST['customers_id']);
			if (!$customer->account_needs_setup()) $redirect = '/login.php';
		}

		if (!empty($redirect)) CK\fn::redirect_and_exit($redirect);

		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;

		switch ($_REQUEST['action']) {
			case 'account-setup':

				if (isset($_POST['customers_id']) && isset($_POST['confirmation_code'])) {

					$reset_code = $this->validate_reset_code($_POST['customers_id'], $_POST['confirmation_code']);

					$page = '/account-setup?customers_id='.$_REQUEST['customers_id'].'&confirmation_code='.$_REQUEST['confirmation_code'];

					if (empty($reset_code)) $GLOBALS['messageStack']->add('account_setup', 'Error: There was an issue attempting to complete your account setup. Please try again or contact our customer service team.');
					elseif ($reset_code != $_POST['confirmation_code']) $GLOBALS['messageStack']->add('account_setup', 'Error: There was an issue attempting to complete your account setup. Please try again or contact our customer service team.');
					elseif (strlen($_POST['password']) < ck_customer2::$validation['password']['min_length']) $GLOBALS['messageStack']->add('account_setup', 'Error: Your Password must contain a minimum of '.ck_customer2::$validation['password']['min_length'].' characters.');
					elseif ($_POST['password'] != $_POST['confirm_password']) $GLOBALS['messageStack']->add('account_setup', 'Error: Your passwords do not match!');
					else {
						$customer = new ck_customer2($_POST['customers_id']);
						$customer->update_password($_POST['password']);
						$page = '/login.php';					}
				}
				break;
			default:
				break;
		}

		if (!empty($page)) CK\fn::redirect_and_exit($page);
	}

	public function validate_reset_code($customers_id, $confirmation_code) {
		return self::query_fetch('SELECT reset_code FROM customers WHERE customers_id = :customers_id AND reset_code = :confirmation_code', cardinality::SINGLE, [':customers_id' => $customers_id, ':confirmation_code' => $confirmation_code]);
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

		if (isset($_REQUEST['customers_id']) && isset($_REQUEST['confirmation_code'])) {

			$reset_code = $this->validate_reset_code($_REQUEST['customers_id'], $_REQUEST['confirmation_code']);

			if (empty($reset_code)) {
				$GLOBALS['messageStack']->add('account_setup', 'Error: Something went wrong with setting your password. Try again!');
			}
			else {
				$data['valid'] = TRUE;
				$data['customers_id'] = $_REQUEST['customers_id'];
				$data['confirmation_code'] = $_REQUEST['confirmation_code'];
			}
		}
		else {
			$GLOBALS['messageStack']->add('account_setup', 'Error: There was an issue attempting to complete your account setup. Please try again or contact our customer service team.');
		}

		if ($GLOBALS['messageStack']->size('account_setup') > 0) $data['account_setup-error'] = $GLOBALS['messageStack']->output('account_setup');

		$this->render($this->page_templates['account-setup'], $data);
		$this->flush();
	}
}

?>