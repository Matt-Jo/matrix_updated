<?php
require_once(__DIR__.'/../includes/application_top.php');
ini_set('log_errors', 1);
ini_set('error_log', '/home/jase/logs/scheduled_split_orders.log');

$_SESSION['current_context'] = 'backend';

$split_orders = prepared_query::fetch('SELECT o.orders_id FROM orders o WHERE o.split_order = 1 AND o.orders_status NOT IN (3, 6) AND o.parent_orders_id IS NULL', cardinality::COLUMN);

foreach ($split_orders as $order_id) {
	$products_to_split = [];
	$source_order = new ck_sales_order($order_id);
	$products = $source_order->get_products();
	$nothing_can_ship_today = TRUE;

	foreach ($products as $op) {
		$inv_data = $op['ipn']->get_inventory();
		$saleable = $inv_data['available'] + $op['quantity'];

		if ($op['quantity'] > $saleable) {
			$products_to_split[$op['orders_products_id']] = $saleable>0?$op['quantity']-$saleable:$op['quantity'];

			if ($products_to_split[$op['orders_products_id']] < $op['quantity']) $nothing_can_ship_today = FALSE;
		}
		else {
			$products_to_split[$op['orders_products_id']] = 0;
			$nothing_can_ship_today = FALSE;
		}
	}

	if (empty($products_to_split) || $nothing_can_ship_today) continue; //don't need to split this order

	try {
		// create new order
		$new_order_id = prepared_query::insert('INSERT INTO orders (released, customers_id, customers_name, customers_company, customers_street_address, customers_suburb, customers_city, customers_postcode, customers_state, customers_country, customers_telephone, customers_email_address, customers_address_format_id, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_address_format_id, billing_name, billing_company, billing_street_address, billing_suburb, billing_city, billing_postcode, billing_state, billing_country, billing_address_format_id, payment_method_id, last_modified, date_purchased, orders_status, currency, currency_value, customers_referer_url, customers_fedex, customers_ups, dpm, dsm, dropship, purchase_order_number, packing_slip, net10_po, net15_po, net30_po, net45_po, orders_weight, all_items_in_stock, delivery_telephone, orders_sales_rep_id, sales_team_id, customers_extra_logins_id, orders_sub_status, followup_date, promised_ship_date, customer_type, ups_account_number, fedex_signature_type, fedex_account_number, fedex_bill_type, admin_id, channel, source, medium, campaign, keyword, content, utmz, parent_orders_id, amazon_order_number, orders_canceled_reason_id, ca_order_id, ebay_order_id, marketplace_order_id, distribution_center_id, split_order, gtm_data_sent, use_reclaimed_packaging, ca_shipping_export_status, sent_to_listrak) SELECT released, customers_id, customers_name, customers_company, customers_street_address, customers_suburb, customers_city, customers_postcode, customers_state, customers_country, customers_telephone, customers_email_address, customers_address_format_id, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_address_format_id, billing_name, billing_company, billing_street_address, billing_suburb, billing_city, billing_postcode, billing_state, billing_country, billing_address_format_id, payment_method_id, NOW(), date_purchased, orders_status, currency, currency_value, customers_referer_url, customers_fedex, customers_ups, dpm, dsm, dropship, purchase_order_number, packing_slip, net10_po, net15_po, net30_po, net45_po, orders_weight, all_items_in_stock, delivery_telephone, orders_sales_rep_id, sales_team_id, customers_extra_logins_id, orders_sub_status, followup_date, promised_ship_date, customer_type, ups_account_number, fedex_signature_type, fedex_account_number, fedex_bill_type, admin_id, channel, source, medium, campaign, keyword, content, utmz, :orders_id, amazon_order_number, orders_canceled_reason_id, ca_order_id, ebay_order_id, marketplace_order_id, distribution_center_id, 3, gtm_data_sent, use_reclaimed_packaging, ca_shipping_export_status, sent_to_listrak FROM orders WHERE orders_id = :orders_id', [':orders_id' => $source_order->id()]);

		$new_order = new ck_sales_order($new_order_id);
		$new_order->update_order_status(['customer_notified' => 0, 'comments' => 'Order split automatically']);

		$new_order->create_totals([
			['class' => 'ot_shipping', 'title' => 48, 'value' => 0, 'external_id' => 48],
			['class' => 'ot_total', 'value' => 0],
		]);

		// update source order
		if ($source_order->get_header('orders_status') != 11 || $source_order->get_header('orders_sub_status') != 9) { // don't move accounting orders
			$source_order->update_order_status(['orders_status_id' => ck_sales_order::STATUS_RTP, 'orders_sub_status_id' => ck_sales_order::$sub_status_map['RTP']['Uncat'], 'customer_notified' => 0, 'comments' => 'Order split automatically']);
		}
		else {
			$source_order->update_order_status(['customer_notified' => 0, 'comments' => 'Order split automatically']);
		}
		$source_order->update(['split_order' => 3]);

		foreach ($products_to_split as $orders_products_id => $new_order_quantity) {
			if ($new_order_quantity <= 0) continue;
			$source_order->move_product_to_other_order($orders_products_id, $new_order, $new_order_quantity);
		}

		$shipping_recalculated = FALSE;

		$sm = $source_order->get_shipping_method();
		// only bother with fedex orders where shipping is charged (shipping not charged indicates we are using their shipping account)
		if ($sm['carrier'] == 'FedEx' && $source_order->get_simple_totals('shipping') > 0) {
			require_once(DIR_FS_CATALOG.'includes/functions/shipping_rates.php');
			$number_of_boxes = ceil($source_order->get_estimated_product_weight() / 50);
			$fedex_rates = get_fedex_rates($source_order->get_ship_address(), round($source_order->get_estimated_product_weight()/$number_of_boxes, 1), $number_of_boxes);

			foreach ($fedex_rates as $rate) {
				if ($rate['shipping_method_id'] == $sm['shipping_code']) {
					$shipping_recalculated = TRUE;
					$stotals = $source_order->get_totals('shipping');
					foreach ($stotals as $total) {
						if ($total['shipping_method_id'] == $rate['shipping_method_id']) $source_order->update_total($total['orders_total_id'], ['value' => $rate['cost']]);
					}
				}
			}
		}

		$source_order->refigure_totals();
		$new_order->refigure_totals();

		$source_order->create_admin_note(['orders_note_user' => ck_sales_order::$ckcorporate_id, 'orders_note_text' => 'This order was split and order #'.$new_order->id().' was created based on shipping availability.'."\n\n".'Shipping for this order was '.($shipping_recalculated?'':'NOT ').'automatically recalculated.']);
		$new_order->create_admin_note(['orders_note_user' => ck_sales_order::$ckcorporate_id, 'orders_note_text' => 'This order was split from order #'.$source_order->id().' automatically based on shipping availability. Please follow up with the customer to determine the appropriate shipping method.'."\n\n".'Shipping for the original order was '.($shipping_recalculated?'':'NOT ').'automatically recalculated.']);

		// send email to notify split
		$mailer = service_locator::get_mail_service();
		$email = $mailer->create_mail();
		$email->add_to($source_order->get_contact_email(), 'CK Sales');
		$email->set_from('webmaster@cablesandkits.com', 'CK Webmaster');
		$email->set_subject('Order #'.$source_order->id().' Was Automatically Split');
		$email->set_body(null,'Order #'.$source_order->id().' was split and order #'.$new_order->id().' was created. '.$source_order->id().' should ship today and '.$new_order->id().' should not have sufficient inventory to ship. Please verify this and follow up with the customer about shipping options for '.$new_order->id().'.');
		$mailer->send($email);
	}
	catch (Exception $e) {
		// failed to split this order successfully, fail gracefully and continue the loop
	}
}
