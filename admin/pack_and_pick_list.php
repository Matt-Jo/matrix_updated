<?php
require('includes/application_top.php');

ini_set('memory_limit', '1024M');
set_time_limit(0);

if (empty($_GET['orders_ids'])) $_GET['orders_ids'] = [$_GET['oID']];

prepared_query::execute('START TRANSACTION');

try {
	$cktpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::SLIM);
	$domain = $_SERVER['HTTP_HOST'];
	$cdn = '//media.cablesandkits.com';
	$static = $cdn.'/static';

	$content_map = new ck_content();
	$content_map->cdn = $cdn;
	$content_map->static_files = $static;
	$content_map->context = CONTEXT;

	$content_map->head = ['title' => 'Matrix - Orderlist'];
	$content_map->{'skip-logo?'} = 1;

	$cktpl->open($content_map);

	$content_map->orders = [];

	ck_ipn2::set_load_context(ck_ipn2::CONTEXT_LIST);

	foreach ($_GET['orders_ids'] as $idx => $orders_id) {
		$order = new ck_sales_order($orders_id);
		$customer = $order->get_customer();

		$header = $order->get_header();

		$ord = [];

		if ($order->has_sales_team()) {
			$sales_team = $order->get_sales_team();
			$ord['sales_team'] = $sales_team->get_header('label');
		}

		if ($idx == 0) $content_map->{'first_run?'} = 1;
		else unset($content_map->{'first_run?'});

		if (!$order->is('released')) $ord['unreleased?'] = 1;

		if ($order->is('dropship')) $ord['blind?'] = 1;
		if ($order->is('use_reclaimed_packaging')) $ord['eco?'] = 1;
		if ($header['orders_sub_status'] == 23) $ord['special_handling?'] = 1;
		if ($order->is('packing_slip')) $ord['packing_slip?'] = 1;

		$ord['order_number'] = $header['orders_id'];

		if (!empty($header['amazon_order_number']) || $header['payment_method_label'] == 'Amazon') $ord['amazon?'] = 1;

		if (!empty($header['amazon_order_number'])) $ord['amazon_order_number'] = $header['amazon_order_number'];

		if (!empty($header['net10_po'])) $ord['net_po_number'] = $header['net10_po'];
		elseif (!empty($header['net15_po'])) $ord['net_po_number'] = $header['net15_po'];
		elseif (!empty($header['net30_po'])) $ord['net_po_number'] = $header['net30_po'];
		elseif (!empty($header['net40_po'])) $ord['net_po_number'] = $header['net40_po'];

		$ord['customers_name'] = $header['customers_name'];
		if (!empty($header['extra_logins_firstname'])) $ord['customers_name'] = $header['extra_logins_firstname'].' '.$header['extra_logins_lastname'];

		if (!empty(trim($header['purchase_order_number']))) $ord['ref_po_number'] = trim($header['purchase_order_number']);

		$ord['order_date'] = $header['date_purchased']->format('m/d/Y');

		if ($header['payment_method_label'] != 'Amazon') $ord['payment_method'] = $header['payment_method_label'];

		if ($order->has_notes()) {
			$admin_notes = $order->get_notes();
			$ord['admin_notes'] = [];
			foreach ($admin_notes as $admin_note) {
				if ($admin_note['deleted'] == 1) continue;
				$note = ['date' => $admin_note['note_date']->format('m/d/Y g:i a'), 'user' => $admin_note['admin_first_name'].' '.$admin_note['admin_last_name'], 'note' => $admin_note['note_text']];
				if (CK\fn::check_flag($admin_note['shipping_notice'])) {
					$ord['has_shipping_notice?'] = 1;
					$note['shipping_notice?'] = 1;
				}
				$ord['admin_notes'][] = $note;
			}
		}

		$sold_to = $order->get_address();
		$ship_to = $order->get_shipping_address();
		$bill_to = $order->get_billing_address();
		$ord['customer'] = [
			'name' => $sold_to['name'],
			'address1' => $sold_to['street_address_1'],
			'city' => $sold_to['city'],
			'state' => $sold_to['state'],
			'postcode' => $sold_to['zip'],
			'country' => $sold_to['country'],
			'extra-telephone' => $sold_to['phone'],
			'extra-email_address' => !empty($header['extra_logins_email_address'])?$header['extra_logins_email_address']:$header['customers_email_address']
		];
		if (!empty($sold_to['company'])) $ord['customer']['company_name'] = $sold_to['company'];
		if (!empty($sold_to['street_address_2'])) $ord['customer']['address2'] = $sold_to['street_address_2'];

		$ord['delivery'] = [
			'name' => $ship_to['name'],
			'address1' => $ship_to['street_address_1'],
			'city' => $ship_to['city'],
			'state' => $ship_to['state'],
			'postcode' => $ship_to['zip'],
			'country' => $ship_to['country']
		];
		if (!empty($ship_to['company'])) $ord['delivery']['company_name'] = $ship_to['company'];
		if (!empty($ship_to['street_address_2'])) $ord['delivery']['address2'] = $ship_to['street_address_2'];

		// if the customer added a comment at the time the order was placed, that's what we want
		$first_status = $order->get_status_history()[0];
		$ord['comments'] = $first_status['comments'];

		$ord['products'] = [];
		$ord['bundles'] = [];
		$accessories = [];
		foreach ($order->get_products() as $product) {
			// we grab a separate count of included accessories
			if ($product['listing']->has_options('included')) {
				foreach ($product['listing']->get_included_options() as $accessory) {
					if (!isset($accessories[$accessory['listing']->get_header('products_model')])) $accessories[$accessory['listing']->get_header('products_model')] = 0;
					$accessories[$accessory['listing']->get_header('products_model')] += $product['listing']->is('is_bundle')?$product['quantity']*$accessory['bundle_quantity']:$product['quantity'];
				}
			}
			// we skip bundles on the pick/pack display
			if ($product['listing']->is('is_bundle')) {
				$ord['bundles'][] = $product['model'];
				continue;
			}

			$prod = [
				'quantity' => $product['quantity'],
				'ipn' => $product['ipn']->get_header('ipn'),
				'bin_numbers' => $product['ipn']->get_header('bin1'),
				'model_number' => $product['model'],
				'product_name' => $product['name']
			];

			if (!empty($product['ipn']->get_header('bin2'))) $prod['bin_numbers'] .= ', '.$product['ipn']->get_header('bin2');

			if ($product['ipn']->get_header('products_stock_control_category_id') != 90) $prod['pack_list_line?'] = 1;

			$prod['qty_span'] = 2;

			if ($product['ipn']->is('serialized')) {
				$prod['serialized?'] = 1;
				$prod['serials'] = [];

				$allocated_qty = count($product['allocated_serials']);
				$required_qty = $product['quantity'];

				if ($allocated_qty >= $required_qty) {
					$serials = $product['allocated_serials'];
					$fully_allocated = TRUE;
					$show_qty = $required_qty;
				}
				else {
					$serials = ck_serial::get_all_pickable_serials_by_stock_id_and_orders_id($product['ipn']->id(), $order->id());
					$fully_allocated = FALSE;
					$show_qty = $product['ipn']->get_inventory('allocated') + $product['ipn']->get_inventory('po_allocated');
				}

				if (!empty($serials)) {
					foreach ($serials as $idx => $serial) {
						if ($fully_allocated && $idx >= $required_qty) break;
						if (!$fully_allocated && $idx >= $show_qty) break;

						$history = $serial->get_current_history();

						$srl = ['serial' => $serial->get_header('serial_number'), 'bin_number' => $history['bin_location']];

						if ($history['orders_id'] == $order->id()) {
							$srl['bin_number'] = 'Allocated ['.$srl['bin_number'].']';
							$srl['allocated'] = 1;
						}
						elseif ($serial->has_reservation() && $serial->get_reservation('orders_products_id') == $product['orders_products_id']) {
							$srl['bin_number'] = 'RESERVED ['.$srl['bin_number'].']';
							$srl['reserved'] = 1;
						}

						$prod['serials'][] = $srl;
					}
				}

				usort($prod['serials'], function($a, $b) {
					if (empty($a['allocated']) != empty($b['allocated'])) return !empty($a['allocated'])?-1:1;
					elseif (empty($a['reserved']) != empty($b['reserved'])) return !empty($a['reserved'])?-1:1;
					else return strcasecmp($a['bin_number'], $b['bin_number']);
				});

				if ($required_qty > $allocated_qty) {
					$unclaimed_qty = $required_qty - $allocated_qty;
					foreach ($prod['serials'] as $idx => $srl) {
						if (!empty($srl['allocated'])) continue;
						$prod['serials'][$idx]['required'] = 1;
					}
				}

				$prod['qty_span'] += count($prod['serials']);
			}

			$ord['products'][] = $prod;
		}

		usort($ord['products'], function($a, $b) { return strcmp($a['bin_numbers'], $b['bin_numbers']); });

		$ord['accessories'] = [];
		foreach ($accessories as $model => $qty) {
			$ord['accessories'][] = ['model' => $model, 'qty' => $qty];
		}

		// there are double-checks in the template file to not display the packing list for blind orders, this constitutes a triple-check
		if (!$order->is('dropship')) {
			if (empty($ord['amazon?'])) {
				$ord['other_picks'] = [];

				$all_orders = $customer->get_order_references([6, 9], TRUE);
				$order_count = count($all_orders);

				if ($order->get_customer()->is('dealer') || $order->get_customer()->has_account_manager() || $order_count > 1) {
					$ord['other_picks'][] = [
						'quantity' => 1,
						'ipn' => 'FREE CANDY',
						'bin_numbers' => 'SHIP 1'
					];
				}
				else {
					$ord['other_picks'][] = [
						'quantity' => 1,
						'ipn' => 'FTB1',
						'bin_numbers' => 'SHIP 3'
					];
				}

				// this block is for mousepads
				if (!$order->get_customer()->get_header('has_received_mousepad')) {
					switch($order->get_customer()->get_header('sales_team_id')) {
						case 1: // resales 01
							$mousepad = ['ipn' => 'MOUSEPAD01', 'bin_numbers' => 'MERC-01'];
							break;
						case 2: // sales 04
						case 3: // sales 03
 							$mousepad = ['ipn' => 'MOUSEPAD03', 'bin_numbers' => 'MERC-02'];
							break;
						case 6: // resales 02
							$mousepad = ['ipn' => 'MOUSEPAD02', 'bin_numbers' => 'MERC-03'];
							break;
						default;
							$mousepad = [];
							break;
					}
 					if (!empty($mousepad)) {
					    $ord['other_picks'][] = [
						    'quantity' => 1,
						    'ipn' => $mousepad['ipn'],
						    'bin_numbers' => $mousepad['bin_numbers']
					    ];
				    }
				}
			}

			$ord['return_address'] = nl2br(STORE_NAME_ADDRESS);
			if (!empty($ord['amazon?'])) $ord['return_address'] = preg_replace('/\.com/', '', $ord['return_address']);

			$ord['customer']['format'.$header['customers_address_format_id']] = 1;
			$ord['delivery']['format'.$header['delivery_address_format_id']] = 1;
		}

		$ord['totals'] = [];
		foreach ($order->get_totals('consolidated') as $total) {
			$ttl = ['title' => $total['title'], 'total' => $total['display_value']];
			if ($total['class'] == 'shipping') $ttl['title'] = trim($order->get_shipping_method('carrier').' '.$order->get_shipping_method('method_name'));
			$ord['totals'][] = $ttl;
		}

		$ord['shipping_method'] = trim($order->get_shipping_method('carrier').' '.$order->get_shipping_method('method_name'));

		if ($header['billing_street_address'] != $header['delivery_street_address']) $ord['different_addresses?'] = 1;

		if ($order->is('dsm')) $ord['bill_to_customer?'] = 1;

		$content_map->orders = [$ord];

		$cktpl->content('../includes/templates/page-pickpack.mustache.html', $content_map);

		//no need to update all the orders if they are already in warehouse
		if (empty($_GET['status']) || $_GET['status'] != '7') {
			ck_sales_order::move_rtp_to_warehouse([$orders_id]);
		}
		ck_customer2::destroy_record($customer->get_header('customers_id'));
		ck_sales_order::destroy_record($orders_id);
	}

	$cktpl->close($content_map);

	prepared_query::execute('COMMIT');
}
catch (Exception $e) {
	prepared_query::execute('ROLLBACK');
	throw $e;
}
?>
