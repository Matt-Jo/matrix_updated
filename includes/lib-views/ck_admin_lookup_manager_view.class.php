<?php
class ck_admin_lookup_manager_view extends ck_view {

	protected $url = '/admin/lookup-manager';

	protected $page_templates = [
	];

	public function process_response() {
		$this->init([__DIR__.'/../../admin/includes/templates']);

		$__FLAG = request_flags::instance();

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_POST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;

		$__FLAG = request_flags::instance();

		$_SESSION['expand'] = $_REQUEST['lookup'];

		$updates = [];
		$add = [];

		switch ($_REQUEST['action']) {
			case 'update-ipn_verticals':
				$instance = ck_ipn_vertical_lookup::instance();

				if (!empty($_REQUEST['updates'])) {
					foreach ($_REQUEST['updates'] as $id => $details) {
						$updates[$id] = ['name' => $details['vertical']];
					}
				}

				if ($__FLAG['submit-new']) {
					$new = $_REQUEST['new'];
					if (!empty($new['vertical'])) $add = ['name' => $new['vertical']];
				}

				break;
			case 'update-ipn_categories':
				$instance = ck_ipn_category_lookup::instance();

				if (!empty($_REQUEST['updates'])) {
					foreach ($_REQUEST['updates'] as $id => $details) {
						$updates[$id] = ['name' => $details['category'], 'vertical_id' => $details['ipn_vertical_id'], 'pricing_review' => $details['pricing_review'], 'sort_order' => $details['sort_order']];
					}
				}

				if ($__FLAG['submit-new']) {
					$new = $_REQUEST['new'];
					if (!empty($new['category']) && !empty($new['ipn_vertical_id']) && !empty($new['pricing_review'])) $add = ['name' => $new['category'], 'vertical_id' => $new['ipn_vertical_id'], 'pricing_review' => $new['pricing_review'], 'sort_order' => $new['sort_order']];
				}

				break;
			case 'update-hold_reasons':
				$instance = ck_hold_reason_lookup::instance();

				if (!empty($_REQUEST['updates'])) {
					foreach ($_REQUEST['updates'] as $id => $details) {
						$updates[$id] = ['description' => $details['reason'], 'active' => CK\fn::check_flag(@$details['active'])];
					}
				}

				if ($__FLAG['submit-new']) {
					$new = $_REQUEST['new'];
					if (!empty($new['reason'])) $add = ['description' => $new['reason']];
				}

				break;
			case 'update-hold_intentions':
				$instance = ck_hold_intention_lookup::instance();

				if (!empty($_REQUEST['updates'])) {
					foreach ($_REQUEST['updates'] as $id => $details) {
						$updates[$id] = ['intention' => $details['intention'], 'active' => CK\fn::check_flag(@$details['active'])];
					}
				}

				if ($__FLAG['submit-new']) {
					$new = $_REQUEST['new'];
					if (!empty($new['intention'])) $add = ['intention' => $new['intention'], 'active' => CK\fn::check_flag(@$new['active'])];
				}

				break;
			case 'update-warehouse_processes':
				$instance = ck_warehouse_process_lookup::instance();

				if (!empty($_REQUEST['updates'])) {
					foreach ($_REQUEST['updates'] as $id => $details) {
						$updates[$id] = ['warehouse_process' => $details['warehouse_process'], 'process_code' => $details['process_code'], 'active' => CK\fn::check_flag(@$details['active'])];
					}
				}

				if ($__FLAG['submit-new']) {
					$new = $_REQUEST['new'];
					if (!empty($new['warehouse_process']) && !empty($new['process_code'])) $add = ['warehouse_process' => $new['warehouse_process'], 'process_code' => $new['process_code'], 'active' => CK\fn::check_flag(@$new['active'])];
				}

				break;
			case 'update-disposition_transaction_types':
				$instance = ck_dispo_transaction_type_lookup::instance();

				if (!empty($_REQUEST['updates'])) {
					foreach ($_REQUEST['updates'] as $id => $details) {
						$updates[$id] = ['transaction_type' => $details['transaction_type'], 'type_code' => $details['type_code'], 'active' => CK\fn::check_flag(@$details['active'])];
					}
				}

				if ($__FLAG['submit-new']) {
					$new = $_REQUEST['new'];
					if (!empty($new['transaction_type']) && !empty($new['type_code'])) $add = ['transaction_type' => $new['transaction_type'], 'type_code' => $new['type_code'], 'active' => CK\fn::check_flag(@$new['active'])];
				}

				break;
			case 'update-disposition_action_types':
				$instance = ck_dispo_action_type_lookup::instance();

				if (!empty($_REQUEST['updates'])) {
					foreach ($_REQUEST['updates'] as $id => $details) {
						$updates[$id] = ['action_type' => $details['action_type'], 'action_code' => $details['action_code'], 'active' => CK\fn::check_flag(@$details['active'])];
					}
				}

				if ($__FLAG['submit-new']) {
					$new = $_REQUEST['new'];
					if (!empty($new['action_type']) && !empty($new['action_code'])) $add = ['action_type' => $new['action_type'], 'action_code' => $new['action_code'], 'active' => CK\fn::check_flag(@$new['active'])];
				}

				break;
			case 'update-payment_methods':
				$instance = ck_payment_method_lookup::instance();

				if (!empty($_REQUEST['updates'])) {
					foreach ($_REQUEST['updates'] as $id => $details) {
						$updates[$id] = ['label' => $details['method_label'], 'code' => $details['method_code'], 'legacy' => CK\fn::check_flag($details['active'])?0:1];
					}
				}

				if ($__FLAG['submit-new']) {
					$new = $_REQUEST['new'];
					if (!empty($new['method_label']) && !empty($new['method_code'])) $add = ['label' => $new['method_label'], 'code' => $new['method_code'], 'legacy' => CK\fn::check_flag(@$new['active'])?0:1];
				}

				break;
			case 'update-sales_incentive_tiers':
				$instance = ck_sales_incentive_tier_lookup::instance();

				if (!empty($_REQUEST['updates'])) {
					foreach ($_REQUEST['updates'] as $id => $details) {
						$updates[$id] = ['incentive_base' => $details['incentive_base'], 'incentive_percentage' => $details['incentive_percentage'], 'active' => CK\fn::check_flag($details['active'])?0:1];
					}
				}

				if ($__FLAG['submit-new']) {
					$new = $_REQUEST['new'];
					if (!empty($new['incentive_percentage'])) $add = ['incentive_base' => $new['incentive_base'], 'incentive_percentage' => $new['incentive_percentage'], 'active' => CK\fn::check_flag(@$new['active'])?0:1];
				}

				break;
			case 'update-distribution_centers':
				$instance = ck_distribution_center_lookup::instance();

				if (!empty($_REQUEST['updates'])) {
					foreach ($_REQUEST['updates'] as $id => $details) {
						$updates[$id] = ['dc' => $details['dc'], 'code' => $details['code'], 'active' => CK\fn::check_flag($details['active'])?1:0];
					}
				}

				if ($__FLAG['submit-new']) {
					$new = $_REQUEST['new'];
					if (!empty($new['dc']) && !empty($new['code'])) $add = ['dc' => $new['dc'], 'code' => $new['code'], 'active' => CK\fn::check_flag(@$new['active'])?1:0];
				}

				break;
			default:
				break;
		}

		if (!empty($updates)) {
			foreach ($updates as $id => $data) {
				$instance->update_value($id, $data);
			}
		}

		if (!empty($add)) {
			$instance->add_value($add);
		}

		$page = '/admin/lookup-manager';

		if (!empty($page)) CK\fn::redirect_and_exit($page);
	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}

	private function ajax_response() {
		$response = [];

		switch ($_REQUEST['action']) {
			case '':
				break;
			default:
				$response['errors'] = ['The requested action ['.$_REQUEST['action'].'] was not recognized'];
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$data = $this->data();

		$expand = !empty($_SESSION['expand'])?$_SESSION['expand']:NULL;
		$_SESSION['expand'] = NULL;
		unset($_SESSION['expand']);

		$data['ipn_verticals'] = [
			'name' => 'IPN Verticals',
			'id' => 'ipn_verticals',
			'list' => ck_ipn_vertical_lookup::instance()->get_list()
		];

		if ($expand == $data['ipn_verticals']['id']) $data['ipn_verticals']['expanded'] = 1;

		$data['ipn_categories'] = [
			'name' => 'IPN Categories',
			'id' => 'ipn_categories',
			'list' => array_map(function($c) {
				$c['verts'] = array_map(function($v) use ($c) {
					if ($c['ipn_vertical_id'] == $v['ipn_vertical_id']) $v['selected'] = 1;
					return $v;
				}, ck_ipn_vertical_lookup::instance()->get_list());
				return $c;
			}, ck_ipn_category_lookup::instance()->get_list())
		];

		if ($expand == $data['ipn_categories']['id']) $data['ipn_categories']['expanded'] = 1;

		$data['hold_reasons'] = [
			'name' => 'Hold Reasons',
			'id' => 'hold_reasons',
			'list' => ck_hold_reason_lookup::instance()->get_list()
		];

		if ($expand == $data['hold_reasons']['id']) $data['hold_reasons']['expanded'] = 1;

		/*$data['hold_intentions'] = [
			'name' => 'Hold Intentions',
			'id' => 'hold_intentions',
			'list' => array_map(function($hi) {
				if (!$hi['active']) unset($hi['active']);
				return $hi;
			}, ck_hold_intention_lookup::instance()->get_list())
		];

		if ($expand == $data['hold_intentions']['id']) $data['hold_intentions']['expanded'] = 1;

		$data['warehouse_processes'] = [
			'name' => 'Warehouse Processes',
			'id' => 'warehouse_processes',
			'list' => array_map(function($wp) {
				if (!$wp['active']) unset($wp['active']);
				return $wp;
			}, ck_warehouse_process_lookup::instance()->get_list())
		];

		if ($expand == $data['warehouse_processes']['id']) $data['warehouse_processes']['expanded'] = 1;

		$data['disposition_transaction_types'] = [
			'name' => 'Disposition Transaction Types',
			'id' => 'disposition_transaction_types',
			'list' => array_map(function($dtt) {
				if (!$dtt['active']) unset($dtt['active']);
				return $dtt;
			}, ck_dispo_transaction_type_lookup::instance()->get_list())
		];

		if ($expand == $data['disposition_transaction_types']['id']) $data['disposition_transaction_types']['expanded'] = 1;

		$data['disposition_action_types'] = [
			'name' => 'Disposition Action Types',
			'id' => 'disposition_action_types',
			'list' => array_map(function($dat) {
				if (!$dat['active']) unset($dat['active']);
				return $dat;
			}, ck_dispo_action_type_lookup::instance()->get_list())
		];

		if ($expand == $data['disposition_action_types']['id']) $data['disposition_action_types']['expanded'] = 1;*/

		$data['payment_methods'] = [
			'name' => 'Payment Methods',
			'id' => 'payment_methods',
			'list' => array_map(function($pm) {
				if (!$pm['is_legacy']) $pm['active'] = 1;
				return $pm;
			}, ck_payment_method_lookup::instance()->get_list())
		];

		if ($expand == $data['payment_methods']['id']) $data['payment_methods']['expanded'] = 1;

		$data['sales_incentive_tiers'] = [
			'name' => 'Sales Incentive Tiers',
			'id' => 'sales_incentive_tiers',
			'list' => array_map(function($t) {
				if (!$t['active']) unset($t['active']);
				return $t;
			}, ck_sales_incentive_tier_lookup::instance()->get_list())
		];

		if ($expand == $data['sales_incentive_tiers']['id']) $data['sales_incentive_tiers']['expanded'] = 1;

		/*$data['distribution_centers'] = [
			'name' => 'Distribution Centers',
			'id' => 'distribution_centers',
			'list' => array_map(function($dc) {
				if (!$dc['active']) unset($dc['active']);
				return $dc;
			}, ck_distribution_center_lookup::instance()->get_list())
		];

		if ($expand == $data['distribution_centers']['id']) $data['distribution_centers']['expanded'] = 1;*/

		$this->render('page-lookup-manager.mustache.html', $data);
		$this->flush();
	}
}
?>
