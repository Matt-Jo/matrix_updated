<?php
// when created this feed was an exact replica of the brokerbin feed, but requires a different subject line
class feed_atlas extends data_feed {

	public function __construct() {
		// first things first, let's clear out old files, so we don't get them backed up (no need to create a separate cron for this, just delete them where we create them)
		// anything over 5 days old should be removed
		// we want to keep some old files, just in case there's a problem we need to research
		$feed_namespace = 'atlas__';
		$this->remove_old_feeds($this->file_repository, $feed_namespace, 1);

		$this->child_called = TRUE;
		$this->local_filename = $feed_namespace.date('Y-m-d__h-i-s-a').'.csv';
		$this->destination_filename = 'atlas.csv';


		$this->email_address[] = 'atlas@remarketer.com';
		$this->email_address[] = 'ryan.mathias@cablesandkits.com';

		$this->email_from = 'ryan.mathias@cablesandkits.com';
		$this->email_subject = 'Atlas Inventory Upload ('.date('n-j-y H:i:s').')';
		$this->email_body = '-attached';

		parent::__construct(self::OUTPUT_EMAIL, self::DELIM_COMMA);

	}

	public function __destruct() {
		parent::__destruct(); // write the file
	}

	public function build() {
		//retreive product ids from product table
		//category_id 90 is the shipping supply category, we do not want to send this to listrak, so we are excluding it
		$products_ids = prepared_query::fetch('SELECT p.products_id FROM products p LEFT JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE (p.products_status = 1 OR p.broker_status = 1) AND psc.dlao_product = 0 AND psc.products_stock_control_category_id != 90 AND (:stock_id IS NULL OR psc.stock_id = :stock_id) ORDER BY p.products_id ASC', cardinality::COLUMN, [':stock_id' => NULL]);

		$this->header = [
			'part_number', // the product model number, with an incremented counter to keep it unique
			'manufacturer', // manufacturer's name
			'condition', // mapped condition
			'quantity', //
		];

		$product_count = [];

		$data = [];

		$ipn_model = [];

		foreach ($products_ids as $products_id) {
			$product = new ck_product_listing($products_id);

			$stock_id = $product->get_header('stock_id');
			// format condition appropriately, from our internal condition to brokerbin accepted condition
			$condition = $product->get_condition('brokerbin');
			$model_number = $product->get_header('products_model');
			$part_key = $model_number.'-'.$condition;

			// make sure we're not consolidating inventory and duplicating the qty of a single IPN on itself
			if (!isset($ipn_model[$stock_id])) $ipn_model[$stock_id] = [];
			if (isset($ipn_model[$stock_id][$model_number])) continue;
			$ipn_model[$stock_id][$model_number] = TRUE;

			// we figure quantity first, because if we don't have any we'll just skip it
			$quantity = max(0, $product->get_inventory('display_available_num'));
			$on_backorder = FALSE;
			// this could conceivably set the qty to zero if a special should be turned off but isn't, based on a sell down qty - based on the old logic, this would still be fed to brokerbin
			if ($product->has_special()) $quantity = max(0, min($quantity, $product->get_inventory('on_special')));

			if (isset($data[$part_key])) {
				// if we already have this model in this condition, just add to the overall qty and move on
				$data[$part_key]['quantity'] += $quantity;
				continue;
			}

			// If we have more than one of the same part number, say in different conditions, then add an incremented count to the end of the part number to keep it unique
			// we put this after the qty check so we don't increment unless we're actually uploading it
			$part_number = '';
			if (isset($product_count[$model_number])) {
				$part_number = $model_number.'-'.$product_count[$model_number];
				$product_count[$model_number]++;
			}
			else {
				$part_number = $model_number;
				$product_count[$model_number] = 1;
			}

			// manufacturer
			$manufacturer = $product->get_manufacturer();


			$row = [
				'part_number' => $part_number,
				'manufacturer' => $manufacturer,
				'condition' => $condition,
				'quantity' => $quantity,
			];

			$data[$part_key] = $row;
		}

		$this->data = array_values($data);
	}

}
?>
