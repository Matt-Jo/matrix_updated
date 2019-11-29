<?php
class ck_admin_promos_view extends ck_view {

	protected $url = '/admin/promos';

	protected $page_templates = [];

	public function process_response() {
		$this->init([__DIR__.'/../../admin/includes/templates']);

		$__FLAG = request_flags::instance();

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;

		switch ($_REQUEST['action']) {
			case 'create_promo':
				$data = [
					'products_id' => $_REQUEST['product_id'],
					'promo_title' => $_REQUEST['promo_title']
				];
				ck_promo::create($data);
				$page = '/admin/promos';
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
		$response = [];

		switch ($_REQUEST['action']) {
			case 'get_rules':
				$promo = new ck_promo($_REQUEST['promo_id']);
				$response = $promo->get_rules();
				break;
			case 'create_rule':
				$data = [
					'promo_id' => $_REQUEST['promo_id'],
					'quantity' => $_REQUEST['quantity'],
					'measure' => $_REQUEST['measure'],
					'timeframe' => $_REQUEST['timeframe'],
					'creator_id' => $_SESSION['perms']['admin_id']
				];
				$promo = new ck_promo($_REQUEST['promo_id']);
				$response = $promo->create_rule($data);
				break;
			case 'add_dev_rule':
				$data = [
					'promo_id' => $_REQUEST['promo_id'],
					'dev_rule' => $_REQUEST['dev_rule']
				];
				$promo = new ck_promo($_REQUEST['promo_id']);
				$response = $promo->create_rule($data);
				break;
			case 'edit_rule':
				$data = [
					'promo_rule_id' => $_REQUEST['rule_id'],
					'quantity' => $_REQUEST['quantity'],
					'measure' => $_REQUEST['measure'],
					'timeframe' => $_REQUEST['timeframe'],
				];
				$promo = new ck_promo($_REQUEST['promo_id']);
				$response = $promo->update_rule($data);
				break;
			case 'get_rule_data':
				$promo = new ck_promo($_REQUEST['promo_id']);
				$response = $promo->get_rules($_REQUEST['rule_id']);
				break;
			case 'archive_rule':
				$promo = new ck_promo($_REQUEST['promo_id']);
				$response = $promo->archive_rule($_REQUEST['rule_id']);
				break;
			case 'delete_promo':
				$promo = new ck_promo($_REQUEST['promo_id']);
				$response['success'] = $promo->delete();
				break;
			case 'toggle_promo_active_state':
				$promo = new ck_promo($_REQUEST['promo_id']);
				$response['active'] = $promo->toggle_active_state();
				break;
			default:
				$response['err'] = ['The requested action ['.$_REQUEST['action'].'] was not recognized'];
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$data = $this->data();

		foreach (ck_promo::all() as $promo) {
			$promos = $promo->get_header();
			$promos['created_at'] = $promos['created_at']->format('M d, Y h:m:s');

			$promos['creator_email'] = $promo->get_creator()->get_header('email_address');
			$promos['products_model'] = $promo->get_product()->get_header('products_model');

			$promos['active_state'] = 0;
			if ($promo->get_header('active') == 1) {
				$promos['active'] = 1;
				$promos['active_state'] = 1;
			}

			$data['promos'][] = $promos;
		}

		$data['dev_rules'] = ck_promo::$dev_rules;
		$data['timeframes'] = ck_promo::$timeframes;

		$this->render('page-promos.mustache.html', $data);
		$this->flush();
	}
}
?>
