<?php
class ck_admin_site_constants_view extends ck_view {

	protected $url = '/admin/site-constants';

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

		switch ($_REQUEST['action']) {
			case '':
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
			case 'update-key':
				if (empty($_REQUEST['key_id'])) $response['errors'] = ['Key ID was not found'];
				elseif (empty($_REQUEST['master_key'])) $response['errors'] = ['Key name cannot be empty'];
				else {
					$old = prepared_query::fetch('SELECT * FROM ck_keys WHERE key_id = :key_id', cardinality::ROW, [':key_id' => $_REQUEST['key_id']]);

					$old_key = $old['master_key'];
					if (!empty($old['subkey'])) $old_key .= '.'.$old['subkey'];

					$new_key = $_REQUEST['master_key'];
					if (!empty($_REQUEST['subkey'])) $new_key .= '.'.$_REQUEST['subkey'];

					if ($old_key != $new_key) {
						$GLOBALS['ck_keys']->__unset($old_key);
						$response['key_id'] = 'reload';
					}
					else {
						$response['key_id'] = $_REQUEST['key_id'];
					}

					$keyval = json_decode($_REQUEST['keyval']);
					if (is_null($keyval)) $keyval = $_REQUEST['keyval'];
					$GLOBALS['ck_keys']->__set($new_key, $keyval);
					$GLOBALS['ck_keys']->describe($new_key, $_REQUEST['description']);

					//prepared_query::execute('UPDATE ck_keys SET description = :description WHERE key_id = :key_id', [':description' => $_REQUEST['description'], ':key_id' => $_REQUEST['key_id']]);
				}
				break;
			case 'delete-key':
				if (empty($_REQUEST['key_id'])) $response['errors'] = ['Key ID was not found'];
				else {
					$old = prepared_query::fetch('SELECT * FROM ck_keys WHERE key_id = :key_id', cardinality::ROW, [':key_id' => $_REQUEST['key_id']]);

					$old_key = $old['master_key'];
					if (!empty($old['subkey'])) $old_key .= '.'.$old['subkey'];

					$GLOBALS['ck_keys']->__unset($old_key);

					$response['key_id'] = $_REQUEST['key_id'];
				}
				break;
			case 'add-key':
				if ($keys = prepared_query::fetch('SELECT * FROM ck_keys WHERE master_key = :master_key', cardinality::SET, [':master_key' => $_REQUEST['master_key']])) {
					foreach ($keys as $key) {
						if (!empty($key['subkey']) && $key['subkey'] == $_REQUEST['subkey']) $response['errors'] = ['The requested key already exists.'];
					}
				}

				$new_key = $_REQUEST['master_key'];
				if (!empty($_REQUEST['subkey'])) $new_key .= '.'.$_REQUEST['subkey'];

				$keyval = json_decode($_REQUEST['keyval']);
				if (is_null($keyval)) $keyval = $_REQUEST['keyval'];

				$GLOBALS['ck_keys']->__set($new_key, $keyval);
				$GLOBALS['ck_keys']->describe($new_key, $_REQUEST['description']);

				$response['key_id'] = 'reload';

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

		$keys = prepared_query::fetch('SELECT * FROM ck_keys ORDER BY master_key ASC, subkey ASC');

		$data['keys'] = [];

		foreach ($keys as $key) {
			if ($key['master_key'] == 'master_password') continue;
			$keyval = json_decode($key['keyval']);
			if (is_scalar($keyval)) $key['safe_keyval'] = $keyval;
			else $key['safe_keyval'] = $key['keyval'];
			$data['keys'][] = $key;
		}

		$this->render('page-site-constants.mustache.html', $data);
		$this->flush();
	}
}
?>
