<?php
require_once('includes/application_top.php');

require_once(DIR_FS_CATALOG.'/includes/classes/shipping.php');
require_once(DIR_FS_CATALOG.'/includes/classes/order.php');
require_once(DIR_FS_CATALOG.'includes/functions/shipping_rates.php');

if (isset($_GET['action'])) {
	switch ($_GET['action']) {
		case 're-quote':
			$return = ['ship_cost' => number_format($_GET['remaining_ship_total']['requoted'], 2, '.', '')];

			// loop through each product to set expected shipping values
			$total_weight = 0;
			$products = [];
			foreach ($_GET['op'] as $orders_products_id => $remove_qty) {
				//as with the order, get two instances of the order products
				$op = prepared_query::fetch('SELECT op.products_quantity, psc.stock_weight, op.products_id FROM orders_products op JOIN products p ON op.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE orders_products_id = :orders_products_id', cardinality::ROW, [':orders_products_id' => $orders_products_id]);
				$remaining_qty = max($op['products_quantity'] - $remove_qty, 0);
				$weight = $op['stock_weight'];

				if (!empty($remaining_qty)) {
					$products[] = ['id' => $op['products_id'], 'weight' => $weight];
					$total_weight += $remaining_qty * $weight;
				}
			}

			if (50 == ($shipping_method_id = prepared_query::fetch("SELECT external_id FROM orders_total WHERE class = 'ot_shipping' AND orders_id = ?", cardinality::SINGLE, [$_GET['original_order_id']]))) {
				// freight... let's handle it differently
				// fill global values expected by the freight module
				$order = new order($_GET['original_order_id']);

				$order->delivery['country'] = prepared_query::fetch('SELECT countries_id as id, countries_iso_code_2 as iso_code_2, countries_iso_code_3 as iso_code_3, countries_name as name FROM countries WHERE countries_name LIKE ?', cardinality::ROW, [$order->delivery['country']]);

				$force_freight = TRUE; // force the freight module to remain enabled even if there are no longer any freight items on the order
				$shipping = new shipping;
				$quote = $shipping->quote(NULL, 'fedexfreight');
				$return['ship_cost'] = number_format($quote[0]['methods'][0]['cost'], 2, '.', '');
			}
			else {
				// we'll use the original method used below in the actual split process for everything else. If we can't figure it out, we'll just return the original amount
				// move some of the weight processing around, otherwise this is pretty much verbatim

				$originalOrder = new ck_sales_order($_GET['original_order_id']);
				$shippingCarrier = prepared_query::fetch('SELECT carrier FROM shipping_methods WHERE shipping_code = :shipping_method_id', cardinality::SINGLE, [':shipping_method_id' => $shipping_method_id]);
				$customer = new ck_customer2($_GET['customers_id']);
				//we only recalculate for FedEx and USPS because all UPS shipping charges should be '0'
				$number_of_boxes = ceil($total_weight / 50);

				if (in_array($shippingCarrier, ['FedEx', 'USPS'])) {
					$fedex_rates = [];
					if ($customer->get_header('own_shipping_account') == 0 || trim($customer->get_header('fedex_account_number')) == '') {
						$fedex_rates = get_fedex_rates($originalOrder->get_ship_address(), round($total_weight/$number_of_boxes, 1), $number_of_boxes);
					}
					$usps_rates = get_usps_rates($originalOrder->get_ship_address(), round($total_weight/$number_of_boxes, 1), $number_of_boxes);
					$rates = array_merge($fedex_rates, $usps_rates);
					foreach ($rates as $unused => $rate) {
						if ($rate['shipping_method_id'] == $shipping_method_id) {
							$return['ship_cost'] = number_format($rate['cost'], 2, '.', '');
							break;
						}
					}
				}
			}

			echo json_encode($return);
			exit();
			break;
		default:
			// do nothing
			break;
	}
}

//the only time we post to this page is when we are actually splitting the order
if (!empty($_POST['original_order_id'])) {
	//get two instances of the original order
	$savepoint_id = prepared_query::transaction_begin();

	try {
		$source_order = new ck_sales_order($_POST['original_order_id']);

		//prepare included accessories to be split with their parent product
		if ($__FLAG['split_accessories']) {
			foreach ($_POST['op'] as $orders_products_id => $quantity) {
				if ($quantity > 0 && $included_accessories = prepared_query::fetch('SELECT op.orders_products_id, op.products_quantity, op.products_quantity / pop.products_quantity as option_multiple FROM orders_products op JOIN orders_products pop ON op.parent_products_id = pop.products_id WHERE pop.orders_products_id = :orders_products_id AND op.option_type = :included AND op.orders_id = :orders_id', cardinality::SET, [':orders_products_id' => $orders_products_id, ':included' => ck_cart::$option_types['INCLUDED'], ':orders_id' => $_POST['original_order_id']])) {
					foreach ($included_accessories as $accessory) {
						// if the split quantity is zero we'll proceed otherwise we'll go with what the user selected
						if (empty($_POST['op'][$accessory['orders_products_id']])) $_POST['op'][$accessory['orders_products_id']] = $quantity * $accessory['option_multiple'];
					}
				}
			}
		}

		// create new order
		$new_order_id = prepared_query::insert('INSERT INTO orders (released, customers_id, customers_name, customers_company, customers_street_address, customers_suburb, customers_city, customers_postcode, customers_state, customers_country, customers_telephone, customers_email_address, customers_address_format_id, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_address_format_id, billing_name, billing_company, billing_street_address, billing_suburb, billing_city, billing_postcode, billing_state, billing_country, billing_address_format_id, payment_method_id, last_modified, date_purchased, orders_status, currency, currency_value, customers_referer_url, customers_fedex, customers_ups, dpm, dsm, dropship, purchase_order_number, packing_slip, net10_po, net15_po, net30_po, net45_po, orders_weight, all_items_in_stock, delivery_telephone, orders_sales_rep_id, sales_team_id, customers_extra_logins_id, orders_sub_status, followup_date, promised_ship_date, customer_type, ups_account_number, fedex_signature_type, fedex_account_number, fedex_bill_type, admin_id, channel, source, medium, campaign, keyword, content, utmz, parent_orders_id, amazon_order_number, orders_canceled_reason_id, ca_order_id, ebay_order_id, marketplace_order_id, distribution_center_id, gtm_data_sent, use_reclaimed_packaging, ca_shipping_export_status, sent_to_listrak) SELECT released, customers_id, customers_name, customers_company, customers_street_address, customers_suburb, customers_city, customers_postcode, customers_state, customers_country, customers_telephone, customers_email_address, customers_address_format_id, :delivery_name, :delivery_company, :delivery_street_address, :delivery_suburb, :delivery_city, :delivery_postcode, :delivery_state, :delivery_country, delivery_address_format_id, billing_name, billing_company, billing_street_address, billing_suburb, billing_city, billing_postcode, billing_state, billing_country, billing_address_format_id, payment_method_id, NOW(), date_purchased, orders_status, currency, currency_value, customers_referer_url, customers_fedex, customers_ups, dpm, dsm, dropship, purchase_order_number, packing_slip, net10_po, net15_po, net30_po, net45_po, orders_weight, all_items_in_stock, delivery_telephone, orders_sales_rep_id, sales_team_id, customers_extra_logins_id, orders_sub_status, followup_date, promised_ship_date, customer_type, ups_account_number, fedex_signature_type, fedex_account_number, fedex_bill_type, admin_id, channel, source, medium, campaign, keyword, content, utmz, :orders_id, amazon_order_number, orders_canceled_reason_id, ca_order_id, ebay_order_id, marketplace_order_id, distribution_center_id, gtm_data_sent, use_reclaimed_packaging, ca_shipping_export_status, sent_to_listrak FROM orders WHERE orders_id = :orders_id', [':orders_id' => $source_order->id(), ':delivery_name' => $_POST['delivery_name'], ':delivery_company' => $_POST['delivery_company'], ':delivery_street_address' => $_POST['delivery_street_address'], ':delivery_suburb' => $_POST['delivery_suburb'], ':delivery_city' => $_POST['delivery_city'], ':delivery_postcode' => $_POST['delivery_postcode'], ':delivery_state' => $_POST['delivery_state'], ':delivery_country' => $_POST['delivery_country']]);

		$new_order = new ck_sales_order($new_order_id);
		$new_order->update_order_status(['customer_notified' => 0, 'comments' => 'Order split by admin']);

		$new_order->create_totals([
			['class' => 'ot_shipping', 'title' => $_POST['shipping_method'], 'value' => $_POST['shipping_price'], 'external_id' => $_POST['shipping_method']],
			['class' => 'ot_total', 'value' => 0]
		]);

		//now we loop through each product, updating the database appropriately and keeping track of the subtotals
		foreach ($_POST['op'] as $orders_products_id => $new_order_quantity) {
			if ($new_order_quantity <= 0) continue;

			$product = $source_order->get_products($orders_products_id);
			empty($NEWFREIGHT)?$NEWFREIGHT=$product['ipn']->is('freight'):NULL;

			$source_order->move_product_to_other_order($orders_products_id, $new_order, $new_order_quantity);
		}

		$stotals = $source_order->get_totals('shipping');
		foreach ($stotals as $total) {
			if (!empty($total['shipping_method_id'])) $source_order->update_total($total['orders_total_id'], ['value' => $_POST['remaining_ship_total'][$_POST['ship_total_remaining']]]);
		}

		// handle extra freight lines, if necessary
		if (!empty($NEWFREIGHT)) {
			prepared_query::execute("INSERT INTO orders_total (orders_id, title, text, value, class, sort_order, actual_shipping_cost, external_id) SELECT :new_orders_id, title, text, value, class, sort_order, actual_shipping_cost, external_id FROM orders_total WHERE orders_id = :source_orders_id AND class = 'ot_shipping' AND external_id = 0", [':new_orders_id' => $new_order->id(), ':source_orders_id' => $source_order->id()]);
		}

		$source_order->refigure_totals();
		$new_order->refigure_totals();

		$admin = new ck_admin($_SESSION['login_id']);

		if (trim($_POST['new_order_notes']) != '') $new_order->create_admin_note(['orders_note_user' => $_SESSION['login_id'], 'orders_note_text' => $_POST['new_order_notes']]);
		$new_order->create_admin_note(['orders_note_user' => ck_sales_order::$ckcorporate_id, 'orders_note_text' => 'This order was split from order #'.$source_order->id().' by '.$admin->get_name()]);

		if (trim($_POST['original_order_notes']) != '') $source_order->create_admin_note(['orders_note_user' => $_SESSION['login_id'], 'orders_note_text' => $_POST['original_order_notes']]);
		$source_order->create_admin_note(['orders_note_user' => ck_sales_order::$ckcorporate_id, 'orders_note_text' => 'This order was split and order #'.$new_order->id().' was created by '.$admin->get_name()."\n".'Shipping for this order was automatically recalculated.']);

		//now we handle credit card payments appropriately
		if ($source_order->get_header('payment_method_id') == 1) {
			if ($_POST['cc_payment_goes'] == 'child') {
				$new_order->update(['paymentsvc_id' => $source_order->get_header('paymentsvc_id')]);
				$source_order->update(['paymentsvc_id' => NULL]);
			}
		}

		if ($apps = $source_order->get_payments()) {
			$source_total = $source_order->get_simple_totals('total');
			$new_total = $new_order->get_simple_totals('total');

			foreach ($apps as $app) {
				$remaining_amount = $alloc['pmt_applied_amount'];

				if ($source_total >= $remaining_amount) {
					$source_total -= min($remaining_amount, $source_total);
					$remaining_amount = 0;
					continue;
				}
				elseif ($source_total > 0) {
					$remaining_amount -= $source_total;
					prepared_query::execute('UPDATE acc_payments_to_orders SET amount = :source_total WHERE id = :pmt_applied_id', [':source_total' => $source_total, ':pmt_applied_id' => $app['pmt_applied_id']]);
					$source_total = 0;
				}

				if ($remaining_amount <= 0) continue;

				if ($new_total > 0) {
					prepared_query::execute('INSERT INTO acc_payments_to_orders (payment_id, order_id, amount) VALUES (:payment_id, :new_orders_id, :amount)', [':payment_id' => $app['payment_id'], ':new_orders_id' => $new_order->id(), ':amount' => min($remaining_amount, $new_total)]);
					$new_total -= min($remaining_amount, $new_total);
				}
			}
		}

		prepared_query::transaction_commit($savepoint_id);

		//we have completed the split order - redirect to the 'split order completed' screen
		CK\fn::redirect_and_exit('/admin/orders_split.php?original_order_id='.$source_order->id().'&new_order_id='.$new_order->id().'&shipping_recalculated=1&cc_payment_goes='.$_POST['cc_payment_goes']);
	}
	catch (Exception $e) {
		prepared_query::transaction_rollback($savepoint_id);
		throw $e;
	}
}

//select the appropriate view
if (!isset($_GET['order_id']) && !(isset($_GET['new_order_id']) && isset($_GET['original_order_id']))) $view = 'orders_split_view_no_order_id.php';
elseif (isset($_GET['new_order_id']) && isset($_GET['original_order_id'])) $view = 'orders_split_view_complete.php';
else {
	$view = 'orders_split_view_main.php';
	$order = new ck_sales_order($_GET['order_id']);
} ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
		<title><?= TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	</head>
	<body>
		<div id="modal" class="jqmWindow" style="width: 800px; height: 450px; overflow: scroll;">
			<a class="jqmClose" href="#" style="float: right; clear: both;">X</a>
			<div id="modal-content"></div>
		</div>
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
		<style type="text/css">
			.edit, .save { margin-left: 5px; }
			.edit img, .save img { border: none; vertical-align: middle; }
		</style>
		<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
		<div id="main">
			<?php include($view); ?>
		</div>
		<div style="clear: both;"></div>
	</body>
</html>
