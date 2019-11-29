<?php
class ck_admin_dynamic_lookup_manager_view extends ck_view {

	protected $url = '/admin/dynamic-lookup-manager';

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

		//$_SESSION['expand'] = $_REQUEST['lookup'];

		$updates = [];
		$add = [];

		switch ($_REQUEST['action']) {
			case 'update-bins':
				$instance = ck_bin_lookup::instance();

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

		$page = '/admin/dynamic-lookup-manager';

		if (!empty($page)) CK\fn::redirect_and_exit($page);
	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}

	private function ajax_response() {
		$response = [];

		switch ($_REQUEST['action']) {
			case 'fill-bins':
				$response['name'] = 'Bins';

				$response['header'] = [
					['name' => 'ID'],
					[
						'name' => 'Distribution Center',
						'field' => 'distribution_center_id',
						'select' => array_map(function($dc) {
							return ['key' => $dc['distribution_center_id'], 'name' => $dc['dc']];
						}, ck_distribution_center_lookup::instance()->get_list()),
					],
					['name' => 'Bin #', 'field' => 'bin'],
					['name' => 'Active', 'field' => 'active', 'checkbox' => 1]
				];

				$response['rows'] = array_map(function($bin) {
					$row = [];

					$row[] = ['value' => $bin['bin_id']];
					$row[] = [
						'id' => $bin['bin_id'],
						'field' => 'distribution_center_id',
						'select' => array_map(function($dc) use ($bin) {
							$opt = ['key' => $dc['distribution_center_id'], 'name' => $dc['dc']];
							if ($dc['distribution_center_id'] == $bin['distribution_center_id']) $opt['selected'] = 1;
							return $opt;
						}, ck_distribution_center_lookup::instance()->get_list()),
					];
					$row[] = ['id' => $bin['bin_id'], 'field' => 'bin', 'value' => $bin['bin'], 'text' => 1];
					$active = ['id' => $bin['bin_id'], 'field' => 'active', 'checkbox' => 1];
					if ($bin['active']) $active['checked'] = 1;
					$row[] = $active;

					return $row;
				}, ck_bin_lookup::instance()->get_list());
				break;
			case 'update-bins':
				$__FLAG = request_flags::instance();

				$updates = [];
				$add = [];

				$instance = ck_bin_lookup::instance();

				if (!empty($_REQUEST['updates'])) {
					foreach ($_REQUEST['updates'] as $id => $details) {
						$updates[$id] = ['distribution_center_id' => $details['distribution_center_id'], 'bin' => $details['bin'], 'active' => CK\fn::check_flag(@$details['active'])?1:0];
					}
				}

				if ($__FLAG['submit-new']) {
					$new = $_REQUEST['new'];
					if (!empty($new['distribution_center_id']) && !empty($new['bin'])) $add = ['distribution_center_id' => $new['distribution_center_id'], 'bin' => $new['bin'], 'active' => CK\fn::check_flag(@$new['active'])?1:0];
				}

				if (!empty($updates)) {
					foreach ($updates as $id => $data) {
						$instance->update_value($id, $data);
					}
				}

				if (!empty($add)) {
					$bin_id = $instance->add_value($add);

					$row = [];

					$row[] = ['value' => $bin_id];
					$row[] = [
						'id' => $bin_id,
						'field' => 'distribution_center_id',
						'select' => array_map(function($dc) use ($add) {
							$opt = ['key' => $dc['distribution_center_id'], 'name' => $dc['dc']];
							if ($dc['distribution_center_id'] == $add['distribution_center_id']) $opt['selected'] = 1;
							return $opt;
						}, ck_distribution_center_lookup::instance()->get_list()),
					];
					$row[] = ['id' => $bin_id, 'field' => 'bin', 'value' => $add['bin'], 'text' => 1];
					$active = ['id' => $bin_id, 'field' => 'active', 'checkbox' => 1];
					if ($add['active'] == 1) $active['checked'] = 1;
					$row[] = $active;

					$response['new_row'] = $row;
				}

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

		/*$expand = !empty($_SESSION['expand'])?$_SESSION['expand']:NULL;
		$_SESSION['expand'] = NULL;
		unset($_SESSION['expand']);*/

		$data['lookups'] = [
			['key' => 'bins', 'name' => 'Bins'],
		];

		$this->render('page-dynamic-lookup-manager.mustache.html', $data);
		$this->flush();
	}
}
?>
