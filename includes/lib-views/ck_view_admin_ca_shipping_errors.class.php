<?php
class ck_view_admin_ca_shipping_errors extends ck_view {
	protected $url = '/ca-shipping-errors.php';
	
	protected $page_templates = [
		'ca_shipping_errors' => 'page-ca-shipping-errors.mustache.html'	
	];
	
	protected static $queries = [
		'mark_order_exported' => [
			'qry' => 'UPDATE orders SET ca_shipping_export_status = 1 WHERE orders_id = :orders_id',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],
		'clear_error' => [
			'qry' => 'UPDATE ck_ca_shipping_export_errors SET cleared = 1 WHERE ca_shipping_export_error_id = :ca_shipping_export_error_id',
			'cardinality' => cardinality::NONE
		],
		'add_ca_admin' => [
			'qry' => 'INSERT INTO ck_ca_config (config_key, description, config_val) VALUES (:config_key, :description, :config_val)',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		]
	];
	
	public function process_response() {
		$this->init([__DIR__.'/../../admin/includes/templates']);
		
		$__FLAG = request_flags::instance();
		
		// if we're responding to an ajax request, we can igrnore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}
	
	public function psuedo_controller() {
		$page = NULL;
		
		switch ($_REQUEST['action']) {
			case 'mark_order_exported':
				if (!empty($_GET['orders_id'])) {
					self::execute('mark_order_exported', [':orders_id' => $_GET['orders_id']]);
				}
				$page = '/admin/ca-shipping-errors.php';
				break;
			case 'clear-error':
				if (!empty($_GET['ca_shipping_export_error_id'])) {
					self::execute('clear_error', [':ca_shipping_export_error_id' => $_GET['ca_shipping_export_error_id']]);
				}
				break;
			case 'add_ca_admin':
				self::execute('add_ca_admin', [':description' => 'Channel Advisor Admin', ':config_key' => 'ca_admin', ':config_val' => $_POST['admin_id']]);
				$page = '/admin/ca-shipping-errors.php';
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
	
	private function http_response() {
		$data = $this->data();

		$data['ca_errors'] = self::query_fetch('SELECT ccsee.ca_shipping_export_error_id, ccsee.orders_id, ccsee.created_date, ccsee.error_message FROM ck_ca_shipping_export_errors ccsee LEFT JOIN orders o ON ccsee.orders_id = o.orders_id AND o.ca_shipping_export_status = 0 WHERE ccsee.cleared = 0 ORDER BY ccsee.created_date DESC', cardinality::SET, []);

		foreach ($data['ca_errors'] as &$err) {
			if (empty($err['orders_id'])) unset($err['orders_id']);
		}

		$data['admin_options'] = self::query_fetch('SELECT admin_id, admin_firstname, admin_lastname FROM admin WHERE status = 1', cardinality::SET, []);

		$data['current_ca_admins'] = self::query_fetch('SELECT a.admin_firstname, a.admin_lastname FROM ck_ca_config ccc LEFT JOIN admin a ON ccc.config_val = a.admin_id WHERE ccc.config_key = \'ca_admin\'', cardinality::SET, []);

		$this->render($this->page_templates['ca_shipping_errors'], $data);
		$this->flush();
	}
}
?>