<?php
class ck_admin_manage_tax_liabilities_view extends ck_view {

	protected $url = '/admin/manage-tax-liabilities';

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
			case 'update':
				$liabilities = prepared_query::fetch('SELECT stl.sales_tax_liability_id, stl.sales_tax_liable, c.countries_iso_code_2, z.zone_name FROM ck_sales_tax_liabilities stl JOIN countries c ON stl.countries_id = c.countries_id JOIN zones z ON stl.zone_id = z.zone_id', cardinality::SET);

				foreach ($liabilities as $liability) {
					if (CK\fn::check_flag($liability['sales_tax_liable']) xor !empty($_POST['tax_liability'][$liability['sales_tax_liability_id']])) {
						$liable = !empty($_POST['tax_liability'][$liability['sales_tax_liability_id']])?1:0;
						prepared_query::execute('UPDATE ck_sales_tax_liabilities SET sales_tax_liable = :liable WHERE sales_tax_liability_id = :sales_tax_liability_id', [':liable' => $liable, ':sales_tax_liability_id' => $liability['sales_tax_liability_id']]);
						prepared_query::execute('INSERT INTO ck_sales_tax_liability_history (sales_tax_liability_id, old_liability, new_liability) VALUES (:sales_tax_liability_id, :old_liability, :new_liability)', [':sales_tax_liability_id' => $liability['sales_tax_liability_id'], ':old_liability' => 1^$liable, ':new_liability' => $liable]);
					}
				}

				$page = '/admin/manage-tax-liabilities';
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
			default:
				$response['err'] = ['The requested action ['.$_REQUEST['action'].'] was not recognized'];
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$data = $this->data();

		$data['liabilities'] = prepared_query::fetch('SELECT stl.sales_tax_liability_id, stl.sales_tax_liable, c.countries_iso_code_2, z.zone_name FROM ck_sales_tax_liabilities stl JOIN countries c ON stl.countries_id = c.countries_id JOIN zones z ON stl.zone_id = z.zone_id', cardinality::SET);

		$data['liabilities'] = array_map(function($l) {
			if (!CK\fn::check_flag($l['sales_tax_liable'])) unset($l['sales_tax_liable']);
			return $l;
		}, $data['liabilities']);

		$this->render('page-manage-tax-liabilities.mustache.html', $data);
		$this->flush();
	}
}
?>
