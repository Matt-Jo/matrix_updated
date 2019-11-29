<?php
class ck_view_admin_bundle_included_product_import extends ck_view {

	protected $url = '/admin/bundle-included-product-import';

	protected static $queries = [];
	protected static $import_errors = [];
	protected static $message;
	protected static $output;

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
			case 'bundle-included-product-import-upload':
				$columns = [];
				$import_errors = [];
				$number_added = $number_failed = 0;
				$savepoint = self::transaction_begin();
				try {
					$bundle_product_id_list = [];
					$message = '';
					$output = '';

					$spreadsheet = new spreadsheet_import($_FILES['bundle_included_product_upload_file']);

					foreach ($spreadsheet as $column_idx => $field) {
						if ($column_idx == 1) foreach ($field as $idx => $header) $columns[$header] = $idx;
					}

					foreach ($spreadsheet as $row_idx => $row) {
						if ($row_idx == 1) continue;
						$bundle_product_id = @$row[$columns['bundle_product_id']];
						$included_product_id = @$row[$columns['included_product_id']];
						$bundle_quantity = @$row[$columns['bundle_quantity']];
						$included_product_title = @$row[$columns['included_product_title']];

						if (in_array($bundle_product_id, $bundle_product_id_list)) {
							$import_errors[] = 'The bundle product id '.$bundle_product_id.' was in the spreadsheet more than once.';
							continue;
						}
						elseif (!empty($bundle_product_id)) $bundle_product_id_list[] = $bundle_product_id;

						if (!ck_product_listing::is_valid_product_id($bundle_product_id)) {
							$import_errors[] = 'The bundle product id '.$bundle_product_id.' is not a valid product id.';
							continue;
						}
						if (!ck_product_listing::is_valid_product_id($included_product_id)) {
							$import_errors[] = 'The included product id '.$included_product_id.' is not a valid product id.';
							continue;
						}

						$product = new ck_product_listing($bundle_product_id);
						if (!$product->is('is_bundle')) {
							$import_errors[] = 'The bundle product id '.$bundle_product_id.' is not a bundle and was skipped in this import.';
							continue;
						}
						if ($product->has_options('included')) {
							$import_errors[] = 'The bundle product id ' . $bundle_product_id . ' already has included products and has been skipped in this import. You\'ll need to manually add these to the product';
							continue;
						}

						$product->add_included_product(['included_product_id' => $included_product_id, 'bundle_quantity' => $bundle_quantity, 'custom_title' => $included_product_title]);
						$output .= ''.$included_product_id.' was added as an included product to '.$bundle_product_id.'<br>';
					}

					if (!empty($import_errors)) {
						self::transaction_commit($savepoint);
						$message .= 'There were errors in the import. No changes were committed. Please see the report below for errors.<br>';
					}
					else {
						self::transaction_rollback($savepoint);
						$message .= 'The import was processed successfully!<br>';
					}
				}
				catch (Exception $e) {
					//self::transaction_rollback($savepoint);
					$import_errors[] = 'There were errors in the import.  No changes were committed.  Please see the report below for errors. ';
					throw $e;
				}
				self::$import_errors = $import_errors;
				self::$message = $message;
				self::$output = $output;
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
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$data = $this->data();
		if (!empty(self::$message)) $data['$message'] = self::$message;
		if (!empty(self::$import_errors)) $data['import_errors'] = self::$import_errors;
		if (!empty(self::$output)) $data['output'] = self::$output;
		$this->render('page-bundle-included-product-import.mustache.html', $data);
		$this->flush();
	}
}
?>
