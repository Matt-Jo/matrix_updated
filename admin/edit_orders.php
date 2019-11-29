<?php
require('includes/application_top.php');

include(DIR_WS_CLASSES.'order.php');

# 334
require_once("../includes/classes/shipping_methods.php");
$sm = new shipping_methods;
$smArr = $sm->getShippingMethods();

require_once('includes/functions/po_alloc.php');

//MMD - if the order is shipped or canceled we cannot edit it
$oID = $_GET['oID'];
$order_status = prepared_query::fetch("select orders_status from orders where orders_id = :orders_id", cardinality::SINGLE, [':orders_id' => $oID]);

if ($order_status == 3 || $order_status == 6) die("You may not edit an order with a status of shipped or canceled.");

$orders_statuses = [];
$orders_status_array = [];
$all_orders_statuses = prepared_query::fetch('SELECT orders_status_id, orders_status_name FROM orders_status WHERE orders_status_id NOT IN (3, 6)', cardinality::SET);

foreach ($all_orders_statuses as $orders_status) {
	$orders_statuses[] = ['id' => $orders_status['orders_status_id'], 'text' => $orders_status['orders_status_name']];
	$orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
}

$non_selectable_orders_status_array = ['3' => 'Shipped', '6' => 'Canceled'];

$action = isset($_GET['action'])?$_GET['action']:'edit';
$sales_order = new ck_sales_order($_GET['oID']);

//$messageStack->add(sprintf(ERROR_ORDER_DOES_NOT_EXIST, $oID), 'error');
//exit();

$customer = $sales_order->get_customer();

// Update Inventory Quantity
if (!empty($action)) {
	switch ($action) {
		case 'update_order':
			$sales_order->delay_refiguring();

			$oID = $_GET['oID'];
			$status = $_POST['status'];
			$RunningSubTotal = 0;

			$data = [
				'customers_name' => $_POST['update_customer_name'],
				'customers_company' => $_POST['update_customer_company'],
				'customers_street_address' => $_POST['update_customer_street_address'],
				'customers_suburb' => $_POST['update_customer_suburb'],
				'purchase_order_number' => $_POST['purchase_order_number'],
				'customers_city' => $_POST['update_customer_city'],
				'customers_state' => $_POST['update_customer_state'],
				'customers_postcode' => $_POST['update_customer_postcode'],
				'customers_country' => $_POST['update_customer_country'],
				'customers_telephone' => $_POST['update_customer_telephone'],
				'customers_email_address' => $_POST['update_customer_email_address'],
				'dropship' => (@$_POST['update_order_dropship']=='on'?1:0),
				'billing_name' => $_POST['update_billing_name'],
				'billing_company' => $_POST['update_billing_company'],
				'billing_street_address' => $_POST['update_billing_street_address'],
				'billing_suburb' => $_POST['update_billing_suburb'],
				'billing_city' => $_POST['update_billing_city'],
				'billing_state' => $_POST['update_billing_state'],
				'billing_postcode' => $_POST['update_billing_postcode'],
				'billing_country' => $_POST['update_billing_country'],
				'delivery_name' => $_POST['update_delivery_name'],
				'delivery_company' => $_POST['update_delivery_company'],
				'delivery_street_address' => $_POST['update_delivery_street_address'],
				'delivery_suburb' => $_POST['update_delivery_suburb'],
				'delivery_city' => $_POST['update_delivery_city'],
				'delivery_state' => $_POST['update_delivery_state'],
				'delivery_postcode' => $_POST['update_delivery_postcode'],
				'delivery_country' => $_POST['update_delivery_country'],
				'payment_method_id' => $_POST['payment_method_id'],
				'orders_status' => $_POST['status']
			];

			if (!empty($_POST['update_info_net10_po'])) $data['net10_po'] = $_POST['update_info_net10_po'];
			if (!empty($_POST['update_info_net15_po'])) $data['net15_po'] = $_POST['update_info_net15_po'];
			if (!empty($_POST['update_info_net30_po'])) $data['net30_po'] = $_POST['update_info_net30_po'];
			if (!empty($_POST['update_info_net45_po'])) $data['net45_po'] = $_POST['update_info_net45_po'];

			$params = new ezparams($data);

			prepared_query::execute('UPDATE orders SET '.$params->update_cols.' WHERE orders_id = ?', $params->query_vals($_GET['oID']));
			$order_updated = TRUE;

			$check_status = prepared_query::fetch('SELECT customers_name, customers_email_address, orders_status, date_purchased FROM orders WHERE orders_id = :orders_id', cardinality::ROW, [':orders_id' => $_GET['oID']]);

			if (($check_status['orders_status'] != $status) || tep_not_null($_POST['comments'])) {
				// Notify Customer
				$orders_status_data = ['orders_status_id' => $_POST['status'], 'customer_notified' => $customer_notified, 'comments' => $_POST['comments']];
				$sales_order->update_order_status($orders_status_data);
			}

			if (is_array($_POST['update_products'])) {
				foreach ($_POST['update_products'] as $orders_products_id => $products_details) {
					$current_product_info = $sales_order->get_products($orders_products_id);

					$allocated_serial_count = NULL;
					if ($current_product_info['ipn']->is('serialized')) $allocated_serial_count = count($current_product_info['allocated_serials']);
					if (!empty($allocated_serial_count) && $products_details['qty'] < $allocated_serial_count) {
						$messageStack->add_session($products_details['model'].' quantity was not adjusted because serials are allocated to this order. Please unallocate and try again '.$allocated_serial_count, 'warning');
					}
					else {
						$update_product_data = [
							'products_quantity' => $products_details['qty'],
							'products_model' => $products_details['model'],
							'products_name' => $products_details['name'],
							'final_price' => $products_details['final_price']
						];
						$sales_order->update_product($orders_products_id, $update_product_data);
					}
				}
			}

			foreach ($_POST['update_totals'] as $ot) {
				switch ($ot['class']) {
					case 'ot_total':
					case 'ot_tax':
						// we can't manage total or tax directly
						break;
					case 'ot_subtotal':
						// shouldn't exist, if it does remove it
						$sales_order->remove_total($ot['total_id']);
						break;
					case 'ot_shipping':
						// we handle shipping a little different, since it can handle 0 values, and the title and external ID are handled differently
						if (!empty($ot['total_id'])) {
							if (!is_numeric($ot['title']) && $ot['value'] == 0) $sales_order->remove_total($ot['total_id']);
							else $sales_order->update_total($ot['total_id'], ['value' => $ot['value'], 'title' => $ot['title'], 'external_id' => $ot['title']]);
						}
						else $sales_order->create_totals([['class' => 'ot_shipping', 'value' => $ot['value'], 'title' => $ot['title'], 'external_id' => $ot['title']]]);
						break;
					default:
						// ot_custom, ot_coupon
						if (!empty($ot['total_id'])) {
							if ($ot['value'] != 0) $sales_order->update_total($ot['total_id'], ['value' => $ot['value'], 'title' => $ot['title']]);
							else $sales_order->remove_total($ot['total_id']);
						}
						elseif ($ot['value'] != 0) $sales_order->create_totals([['class' => 'ot_custom', 'value' => $ot['value'], 'title' => $ot['title']]]);
						break;
				}
			}

			if (!empty($order_updated)) $messageStack->add_session(SUCCESS_ORDER_UPDATED, 'success');

			$sales_order->refigure_totals();
			CK\fn::redirect_and_exit("/admin/edit_orders.php?oID=$oID&action=edit");
			break;
		case 'add_product':
			if ($_POST['step'] == 5) {
				$sales_order->delay_refiguring();
				$winop = !empty($_REQUEST['winop'])?$_REQUEST['winop']:'';

				$product_price = $_POST['add_product_price'];
				// find lowest price
				if ($customer->has_prices()) {
					if ($customer->get_prices($product_data['stock_id']) < $_POST['add_product_price'] && $customer->get_prices($product_data['stock_id']) > 0) {
						$product_price = $customer->get_prices($product_data['stock_id']);
					}
				}

				$product_info[] = [
					'products_id' => $_POST['add_product_products_id'],
					'products_quantity' => $_POST['add_product_quantity'],
					'final_price' => $product_price
				];

				//check to see if this product already exists on the order, if so, then we'll update the product instead
				foreach ($sales_order->get_products() as $order_product) {
					if ($order_product['products_id'] == $product_info[0]['products_id'] && $order_product['option_type'] == 0) {
						$product_data = $product_info[0];
						$product_data['products_quantity'] += $order_product['quantity'];
						$sales_order->update_product($order_product['orders_products_id'], $product_data);
						CK\fn::redirect_and_exit('/admin/edit_orders.php?oID='.$oID.'&winop='.$winop.'&action=edit');
					}
				}

				$sales_order->create_products($product_info);
				$sales_order->refigure_totals();
				CK\fn::redirect_and_exit('/admin/edit_orders.php?oID='.$oID.'&winop='.$winop.'&action=edit');
			}
			break;
		case 'delete_product':
			$sales_order->delay_refiguring();
			if ($sales_order->delete_product($_GET['orders_products_id'])) {
				$sales_order->refigure_totals();
				echo json_encode(['success' => 1, 'message' => NULL]);
			}
			else echo json_encode(['success' => 0, 'message' => 'Product did not delete due to error']);
			exit();
			break;
		case 'get_included_products':
			$parent_product = $sales_order->get_products($_GET['orders_products_id']);
			$included_products_results = [];
			if ($included_products = $sales_order->get_products_by_parent($parent_product['products_id'], TRUE)) {
				foreach ($included_products as $idx => $included) {
					$included_products_results[$idx]['orders_products_id'] = $included['orders_products_id'];
					$included_products_results[$idx]['stock_name'] = $included['model'];
				}
				echo json_encode($included_products_results);
				exit();
			}
			echo false;
			exit();
			break;
	}
} ?>

<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title>Matrix - Edit Order</title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<style>
		.subtitle { font-family:Verdana, Arial, Helvetica, sans-serif; font-size:11px; font-weight:bold; color:#FF6600; }
		.ordered-products-table tr td { border:1px solid #fff; }
		#additem { border:#bbb 2px solid; background-color:#fff; width:560px; height:190px; padding:0px; visibility:hidden; }
		#moreoptions { display:block; visibility:hidden; }
		#addbutton { height:22px; position:relative; top:-22px; left:405px; float:left; z-index:1000; }
		#ipn_search_input { width:14em;font-family:verdana;font-size:11px; }
		#ipn_search_container { position:absolute;z-index:9050; }
		#ipn_search_container ul {padding:3px 0;width:475px;background-color:#fff;border:1px #000 solid;overflow:hidden;}
		#ipn_search_container li {padding:0 5px;cursor:default;white-space:nowrap;}
		div.autocompletenew ul { list-style-type:none; margin:0px; padding:0px; }
		div.autocompletenew ul li.selected { background-color: #00ccff; }
		div.autocompletenew ul li { font-family:verdana; font-size:11px; list-style-type:none; display:block; margin:0; cursor:pointer; }
		.button-group { display:inline-block; }
		.button-group a { float:right; margin:5px; }
	</style>
</head>
<body <?= isset($winop)&&$winop?'onload="showAddOrder(\'additem\');"':''; ?>>
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<script>
		function showAddOrder (id) {
			var screenT = (window.innerHeight) ? window.innerHeight : screen.height;
			var screenL = (window.innerWidth) ? window.innerWidth : screen.width;
			screenT = parseInt(((screenT - 210) / 2), 10) + 250;
			screenL = parseInt(((screenL - 560) / 2), 10);
			document.body.style.backgroundColor = '#bbb';
			document.getElementById(id).style.position = 'absolute';
			document.getElementById(id).style.top = screenT;
			document.getElementById(id).style.left = screenL;
			document.getElementById(id).style.visibility = 'visible';
			document.getElementById('ipn_search_input').select();
			if (document.getElementById('ipn_search_input').value=="") document.getElementById('addtoorder').disabled = 1;
			return false;
		}

		function closeMod (id) {
			document.edit_order.action = 'edit_orders.php?oID=<?= $oID; ?>&action=update_order';
			document.edit_order.submit();
			return false;
		}

		function addSubmit () {
			var redigit=/\d$/;
			if (!redigit.test(document.getElementById("add_product_quantity").value) || parseInt(document.getElementById("add_product_quantity").value,10)<=0) {
				alert('Please, enter a valid quantity.');
				document.getElementById('add_product_quantity').select();
			}
			else if (!redigit.test(document.getElementById("add_product_price").value) || parseInt(document.getElementById("add_product_price").value,10)<0) {
				alert('Please, enter a valid price.');
				document.getElementById('add_product_price').select();
			}
			else if (!document.getElementById("add_product_price").value) {
				alert('Select an IPN to add to the order.');
				document.getElementById('ipn_search_input').select();
			}
			else if (document.getElementById('addonvallist').value!="") {
				var addonlstArr = document.getElementById('addonvallist').value.split("^");
				var oksubmit = 1;
				var errmsg = "";
				var selid = "";
				for (k=0;k<addonlstArr.length;k++) {
					if (addonlstArr[k]!="" && document.getElementById(addonlstArr[k]).checked==1) {
						if (!redigit.test(document.getElementById("addon_qty_"+addonlstArr[k]).value) || parseInt(document.getElementById("addon_qty_"+addonlstArr[k]).value,10)<=0) {
							oksubmit = 0;
							errmsg = 'Please, enter a valid quantity.';
							selid = "addon_qty_"+addonlstArr[k];
							break;
						}
						else if (!redigit.test(document.getElementById("addon_price_"+addonlstArr[k]).value) || parseInt(document.getElementById("addon_price_"+addonlstArr[k]).value,10)<0) {
							oksubmit = 0;
							errmsg = 'Please, enter a valid price.';
							selid = "addon_price_"+addonlstArr[k];
							break;
						}
					}
				}
				if (oksubmit!=0) {
					document.ipnForm.action += '&winop=1#cusdata';
					document.ipnForm.submit();
				}
				else {
					window.alert(errmsg);
					document.getElementById(selid).select();
					return false;
				}
			}
			else {
				document.ipnForm.action += '&winop=1#cusdata';
				document.ipnForm.submit();
			}
			return false;
		}

		function catchEnter (evt) {
			var key = (window.event) ? window.event.keyCode : ((evt.which) ? evt.which : evt.charCode);
			if (key == 13) {
				addSubmit();
			};
		}

		function disableButton(element) {
			element.disabled = true;
		}

		jQuery(document).ready(function() {
			jQuery('#edit_order').submit(function(event) {
				jQuery('input[type=image]').attr('disabled', true);
			});

			jQuery('.delete-product').click(function (e) {
				e.preventDefault(); //stops the page from scrolling to top
				var orders_products_id = jQuery(this).attr('data-orders-products-id');
				var orders_id = jQuery(this).attr('data-order-number');
				jQuery.ajax({
					type: "GET",
					url: "/admin/edit_orders.php?action=get_included_products",
					data: { orders_products_id: orders_products_id, oID: orders_id },
					dataType: "json",
					success: function (data) {
						var confirmation = "Are you sure you want to delete this product?";
						if (data != null) {
							confirmation = "Are you sure you want to delete this product with the following included options?\n";
							for (var i = 0; i < data.length; i++) {
								confirmation +=  "- " + data[i].stock_name + "\n";
							}
						}
						if (confirm(confirmation)) {
							jQuery.ajax({
								type: "GET",
								url: "/admin/edit_orders.php?action=delete_product",
								data: { orders_products_id: orders_products_id, oID: orders_id },
								dataType: "json",
								success: function (response) {
									if (response.success == 1) {
										jQuery('#product-row-' + orders_products_id).remove();
										if (data != null) {
											for (var i = 0; i < data.length; i++) {
												jQuery('#product-row-' + data[i].orders_products_id).remove();
											}
										}
									}
									else {
										alert(response.message);
									}
								},
								error: function (jqXHR, textStatus, errorThrown) {
									console.log(jqXHR, textStatus, errorThrown);
								}
							});
						}
					},
					error: function (jqXHR, textStatus, errorThrown) {
						console.log(textStatus, jqXHR, errorThrown);
					}
				});
			});
		});
	</script>
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?php echo BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top">
				<?php if (($action == 'edit')) {
					if ($sales_order->has_terms_po_number()) {
						$po_value = $sales_order->get_terms_po_number();
						$show_po = TRUE;
						$input_name = $sales_order->get_header('payment_method_code').'_po';
					}
					else {
						$po_value = '';
						$show_po = FALSE;
					}

					$payment_methods = prepared_query::fetch('SELECT * FROM payment_method WHERE legacy = 0 ORDER BY label ASC', cardinality::SET); ?>

					<h3 style="display:inline;">Edit Order # <?= $_GET['oID']; ?> @ <?= $sales_order->get_header('date_purchased')->format('m/d/Y H:i:s'); ?></h3>
					<div class="button-group" style="display:inline; text-align:right; ">
						<a href="/admin/pack_and_pick_list.php?oID=<?= $_GET['oID']; ?>" target="_blank"><button>Packing Slip</button></a>
						<a href="/admin/invoice.php?oID=<?= $_GET['oID']; ?>" target="_blank"><button>Invoice</button></a>
						<a href="/admin/orders_new.php?oID=<?= $_GET['oID']; ?>&action=edit"><button>Details</button></a>
					</div>
					<h6><i>Please edit all parts as desired and click on the "Update" button below</i></h6>

					<form method="post" action="/admin/edit_orders.php?action=update_order&oID=<?= $oID;?>" name="edit_order" id="edit_order">
						<table width="100%" border="0" cellpadding="2" cellspacing="1">
							<tr>
								<td class="main" bgcolor="#FAEDDE">Please click on "Update" to save all changes.</td>
								<td class="main" bgcolor="#FBE2C8" width="10">&nbsp;</td>
								<td class="main" bgcolor="#FFCC99" width="10">&nbsp;</td>
								<td class="main" bgcolor="#F8B061" width="10">&nbsp;</td>
								<td class="main" bgcolor="#FF9933" width="120" align="center"><button type="submit">update</button></td>
							</tr>
						</table>

						<h5 class="subtitle">1. Customer Data</h5>

						<table border="0" class="dataTableRow" cellpadding="2" cellspacing="0">
							<thead>
								<tr class="dataTableHeadingRow">
									<th class="dataTableHeadingContent" width="80"></th>
									<th class="dataTableHeadingContent" width="150">Customer Address</th>
									<th class="dataTableHeadingContent" width="150">Shipping Address</th>
									<th class="dataTableHeadingContent" width="150">Billing Address</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<th class="main">Company:</th>
									<td><input name="update_customer_company" size="25" value="<?= htmlspecialchars($sales_order->get_header('customers_company'), ENT_QUOTES); ?>"></td>
									<td><input name="update_delivery_company" size="25" value="<?= htmlspecialchars($sales_order->get_header('delivery_company'), ENT_QUOTES); ?>"></td>
									<td><input name="update_billing_company" size="25" value="<?= htmlspecialchars($sales_order->get_header('billing_company'), ENT_QUOTES); ?>"></td>
								</tr>
								<tr>
									<th class="main">Name:</th>
									<td><input name="update_customer_name" size="25" value="<?= htmlspecialchars($sales_order->get_header('customers_name'), ENT_QUOTES); ?>"></td>
									<td><input name="update_delivery_name" size="25" value="<?= htmlspecialchars($sales_order->get_header('delivery_name'), ENT_QUOTES); ?>"></td>
									<td><input name="update_billing_name" size="25" value="<?= htmlspecialchars($sales_order->get_header('billing_name'), ENT_QUOTES); ?>"></td>
								</tr>
								<tr>
									<th class="main">Address:</th>
									<td><input name="update_customer_street_address" size="25" value="<?= htmlspecialchars($sales_order->get_header('customers_street_address'), ENT_QUOTES); ?>"></td>
									<td><input name="update_delivery_street_address" size="25" value="<?= htmlspecialchars($sales_order->get_header('delivery_street_address'), ENT_QUOTES); ?>"></td>
									<td><input name="update_billing_street_address" size="25" value="<?= htmlspecialchars($sales_order->get_header('billing_street_address'), ENT_QUOTES); ?>"></td>
								</tr>
								<tr>
									<th class="main">Suburb:</th>
									<td><input name="update_customer_suburb" size="25" value="<?= htmlspecialchars($sales_order->get_header('customers_suburb'), ENT_QUOTES); ?>"></td>
									<td><input name="update_delivery_suburb" size="25" value="<?= htmlspecialchars($sales_order->get_header('delivery_suburb'), ENT_QUOTES); ?>"></td>
									<td><input name="update_billing_suburb" size="25" value="<?= htmlspecialchars($sales_order->get_header('billing_suburb'), ENT_QUOTES); ?>"></td>
								</tr>
								<tr>
									<th class="main">City:</th>
									<td><input name="update_customer_city" size="25" value="<?= htmlspecialchars($sales_order->get_header('customers_city'), ENT_QUOTES); ?>"></td>
									<td><input name="update_delivery_city" size="25" value="<?= htmlspecialchars($sales_order->get_header('delivery_city'), ENT_QUOTES); ?>"></td>
									<td><input name="update_billing_city" size="25" value="<?= htmlspecialchars($sales_order->get_header('billing_city'), ENT_QUOTES); ?>"></td>
								</tr>
								<tr>
									<th class="main">State:</th>
									<td><input name="update_customer_state" size="25" value="<?= htmlspecialchars($sales_order->get_header('customers_state'), ENT_QUOTES); ?>"></td>
									<td><input name="update_delivery_state" size="25" value="<?= htmlspecialchars($sales_order->get_header('delivery_state'), ENT_QUOTES); ?>"></td>
									<td><input name="update_billing_state" size="25" value="<?= htmlspecialchars($sales_order->get_header('billing_state'), ENT_QUOTES); ?>"></td>
								</tr>
								<tr>
									<th class="main">Postcode:</th>
									<td><input name="update_customer_postcode" size="25" value="<?= $sales_order->get_header('customers_postcode'); ?>"></td>
									<td><input name="update_delivery_postcode" size="25" value="<?= $sales_order->get_header('delivery_postcode'); ?>"></td>
									<td><input name="update_billing_postcode" size="25" value="<?= $sales_order->get_header('billing_postcode'); ?>"></td>
								</tr>
								<tr>
									<th class="main">Country:</th>
									<td><input name="update_customer_country" size="25" value="<?= htmlspecialchars($sales_order->get_header('customers_country'), ENT_QUOTES); ?>"></td>
									<td><input name="update_delivery_country" size="25" value="<?= htmlspecialchars($sales_order->get_header('delivery_country'), ENT_QUOTES); ?>"></td>
									<td><input name="update_billing_country" size="25" value="<?= htmlspecialchars($sales_order->get_header('billing_country'), ENT_QUOTES); ?>"></td>
								</tr>
								<tr>
									<th class="main">Phone:</th>
									<td><input name="update_customer_telephone" size="25" value="<?= $sales_order->get_header('customers_telephone'); ?>"></td>
								</tr>
								<tr>
									<th class="main">Email:</th>
									<td><input name="update_customer_email_address" size="25" value="<?= $sales_order->get_header('customers_email_address'); ?>"></td>
								</tr>
								<tr>
									<th class="main" colspan="2" style="text-align:left;">PO/Reference Number:</th>
									<td><input name="purchase_order_number" size="25" value="<?= $sales_order->get_ref_po_number(); ?>"></td>
								</tr>
								<tr>
									<th class="main" colspan="2" style="text-align:left;">Blind Ship:</th>
									<td><input type="checkbox" name="update_order_dropship" <?= $sales_order->is('dropship')?"checked":''; ?>></td>
								</tr>
							</tbody>
						</table>

						<h5 class="subtitle">2. Payment Method</h5>
						<table border="0" cellspacing="0" cellpadding="2" class="dataTableRow">
							<thead>
								<tr class="dataTableHeadingRow">
									<td colspan="2" class="dataTableHeadingContent">Payment Method:</td>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td colspan="2" class="main">
										<select name="payment_method_id">
											<?php foreach ($payment_methods as $payment_method) {
												// if this is terms, not net 10, and the customer does not have these terms set, skip it
												if (!empty($payment_method['orders']) && $payment_method['id'] != ck_customer2::$cpmi[ck_customer2::NET10] && (!$customer->has_terms() || $customer->get_terms('payment_method_id') != $payment_method['id'])) continue; ?>
											<option value="<?= $payment_method['id']; ?>" <?= $payment_method['id'] == $sales_order->get_header('payment_method_id')?'selected':''; ?>><?= $payment_method['label']; ?></option>
											<?php } ?>
										</select>
									</td>
								</tr>
								<?php if ($show_po) { ?>
								<tr class="dataTableHeadingRow">
									<td colspan="2" class="dataTableHeadingContent">Payment PO#:</td>
								</tr>
								<tr>
									<td colspan="2" class="main"><input name="update_info_<?= $input_name; ?>" size="35" value="<?= $po_value; ?>"></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>

						<h5 class="subtitle">3. Ordered Products</h5>
						<p class="smalltext"><span style="color:red;">Hint:</span> Any included items quantity will adjust accorindgly with its parent - you can delete it and if neccessary add it back in to the order.</p>
						<table class="ordered-products-table" border="0" width="100%" cellspacing="0" cellpadding="2">
							<thead>
								<tr class="dataTableHeadingRow">
									<td class="dataTableHeadingContent">Delete</td>
									<td class="dataTableHeadingContent">Quantity</td>
									<td class="dataTableHeadingContent">IPN</td>
									<td class="dataTableHeadingContent">Model</td>
									<td class="dataTableHeadingContent">Product</td>
									<td class="dataTableHeadingContent" align="right">Price (excl.)</td>
									<td class="dataTableHeadingContent" align="right">Total (excl.)</td>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($sales_order->get_products() as $prod) {
									$RowStyle = "dataTableContent"; ?>
								<tr class="dataTableRow" id="product-row-<?= $prod['orders_products_id']; ?>" style="height:30px;">
									<td class="<?= $RowStyle; ?>" valign="top">
										<?php $is_bundle = FALSE;
										if (!empty($prod['parent_products_id'])) {
											$parent_product = new ck_product_listing($prod['parent_products_id']);
											if ($parent_product->get_ipn()->is('is_bundle')) $is_bundle = TRUE;
										}

										if (!$is_bundle) { ?>
										<a href="#" class="delete-product" data-quantity="<?= $prod['quantity']; ?>" data-order-number="<?= $sales_order->id(); ?>" data-orders-products-id="<?= $prod['orders_products_id']; ?>" style="font-size:20px; color:red; margin-left:15px;">X</a>
										<?php } ?>
									</td>
									<td class="<?= $RowStyle; ?>" align="center" valign="top">
										<?php if ($prod['option_type'] == 3) echo $prod['quantity'];
										else { ?>
										<input name="update_products[<?= $prod['orders_products_id']; ?>][qty]" id="update_products-<?= $prod['orders_products_id']; ?>-qty" size="2" value="<?= $prod['quantity']; ?>">
										<?php } ?>
									</td>
									<td class="<?= $RowStyle; ?>" valign="top"><?= $prod['ipn']->get_header('ipn'); ?></td>
									<td class="<?= $RowStyle; ?>" valign="top">
										<?php if ($prod['option_type'] == 3) echo $prod['model'].' <span style="color:green;">(included item)</span>';
										else { ?>
										<input name="update_products[<?= $prod['orders_products_id']; ?>][model]" size="12" value="<?= $prod['model']; ?>">
										<?php } ?>
									</td>
									<td class="<?= $RowStyle; ?>" valign="top">
										<?php if ($prod['option_type'] == 3) echo $prod['name'];
										else { ?>
										<input name="update_products[<?= $prod['orders_products_id']; ?>][name]" size="35" value="<?= htmlentities($prod['name'], ENT_QUOTES); ?>">
										<?php } ?>
									</td>
									<td class="<?= $RowStyle; ?>" align="right" valign="top">
										<?php if ($prod['option_type'] == 3) echo CK\text::monetize($prod['final_price']);
										else { ?>
										<input name="update_products[<?= $prod['orders_products_id']; ?>][final_price]" size="6" value="<?= number_format($prod['final_price'], 2, '.', ''); ?>">
										<?php } ?>
									</td>
									<td class="<?= $RowStyle; ?>" align="right" valign="top"><?= CK\text::monetize($prod['final_price'] * $prod['quantity']); ?></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
						<p class="smalltext"><span style="color:red;">Hint:</span> If you edit the price associated with a product attribute, you have to calculate the new item cost manually.</p>
						<a href="#" onclick="return showAddOrder('additem');" style="float:right;">Add a product to this order</a>

						<table width="100%" border="0" cellpadding="2" cellspacing="1">
							<tr>
								<td class="main" bgcolor="#FAEDDE">Please click on "Update" to save all changes.</td>
								<td class="main" bgcolor="#FBE2C8" width="10">&nbsp;</td>
								<td class="main" bgcolor="#FFCC99" width="10">&nbsp;</td>
								<td class="main" bgcolor="#F8B061" width="10">&nbsp;</td>
								<td class="main" bgcolor="#FF9933" width="120" align="center"><button type="submit">update</button></td>
							</tr>
						</table>

						<h5 class="subtitle">4. Discount, Shipping and Total</h5>
						<a href="javascript:void(0);" onclick="window.open('shipping_estimator.php?oid=<?= $oID; ?>','shipping_estimator','width=600,height=600' );">View Shipping Rates</a>

						<table border="0" cellspacing="0" cellpadding="2" class="dataTableRow">
							<thead>
								<tr class="dataTableHeadingRow">
									<td class="dataTableHeadingContent">Total Price Component</td>
									<td class="dataTableHeadingContent">Amount</td>
								</tr>
							</thead>
							<tbody>
							<?php
							$shipping_methods = prepared_query::fetch('SELECT ot.*, sm.name, sm.carrier FROM orders_total ot LEFT JOIN shipping_methods sm ON sm.shipping_code = ot.external_id WHERE ot.orders_id = :orders_id ORDER BY sort_order', cardinality::SET, [':orders_id' => $oID]);
							$order_totals = [];
							foreach ($shipping_methods as $shipping_method) {
								$order_totals[] = ['title' => $shipping_method['title'], 'text' => $shipping_method['text'], 'class' => $shipping_method['class'], 'value' => $shipping_method['value'], 'orders_total_id' => $shipping_method['orders_total_id'], 'shipping_method_id' => $shipping_method['external_id']];
							}

							// START OF MAKING ALL INPUT FIELDS THE SAME LENGTH
							$max_length = max(400, $sm->sm_max_length());
							$TotalsLengthArray = [];

							for ($i=0; $i<sizeof($order_totals); $i++) {
								if ($order_totals[$i]['class']=='ot_shipping') {
									$TotalsLengthArray[] = ["Name" => $order_totals[$i]['title'], 'length' => strlen($sm->sm_details('length', $order_totals[$i]['shipping_method_id'])), 'shipping_method_id' => $order_totals[$i]['shipping_method_id']];
								}
								else $TotalsLengthArray[] = ["Name" => $order_totals[$i]['title'], 'length' => strlen($order_totals[$i]['title'])];
							}
							reset($TotalsLengthArray);

							foreach ($TotalsLengthArray as $TotalIndex => $TotalDetails) {
								$max_length = max($max_length, $TotalDetails['length']);
							}

							$TotalsArray = [];
							for ($i=0; $i<sizeof($order_totals); $i++) {
								$TotalsArray[] = ["Name" => $order_totals[$i]['title'], "Price" => number_format($order_totals[$i]['value'], 2, '.', ''), "Class" => $order_totals[$i]['class'], "TotalID" => $order_totals[$i]['orders_total_id'], "shipping_method_id" => $order_totals[$i]['shipping_method_id']];
								if ($order_totals[$i]['class'] == 'ot_shipping') {
									$hasShipping = true;
								}
							}

							if (empty($hasShipping)) {
								array_unshift($TotalsArray, ["Name" => "", "Price" => "", "Class" => "ot_shipping", "TotalID" => "0"]);
							}

							array_unshift($TotalsArray, ["Name" => "", "Price" => "", "Class" => "ot_custom", "TotalID" => "0"]);
							foreach ($TotalsArray as $TotalIndex => $TotalDetails) {
								$TotalStyle = "smallText";
								if ($TotalDetails['Class'] == "ot_shipping") {
									if (!empty($TotalDetails['shipping_method_id']) || empty($TotalDetails['TotalID'])) { ?>
							<tr>
								<td align="right" class="<?= $TotalStyle; ?>">
									<select name="update_totals[<?= $TotalIndex; ?>][title]" style="width:<?php echo ($max_length-2); ?>px;">
										<option value="">--Please Select--</option>
										<?php if (is_array($smArr)) {
											foreach ($smArr as $shipping_code => $shipMethodArr) { ?>
										<option value="<?= $shipping_code; ?>" <?php echo @$TotalDetails['shipping_method_id']==$shipping_code?'selected':''; ?>><?= $shipMethodArr['shortdescription']; ?></option>
											<?php }
										} ?>
									</select>
								</td>
								<td align="right" class="<?= $TotalStyle; ?>">
									<input name="update_totals[<?= $TotalIndex; ?>][value]" size="6" value="<?= $TotalDetails['Price']; ?>">
									<input name="update_totals[<?= $TotalIndex; ?>][class]" type="hidden" value="<?= $TotalDetails['Class']; ?>">
									<input type="hidden" name="update_totals[<?= $TotalIndex; ?>][total_id]" value="<?= $TotalDetails['TotalID']; ?>">
								</td>
							</tr>
									<?php }
									else { ?>
							<tr>
								<td align="right" class="<?= $TotalStyle; ?>">
									<input name="update_totals[<?= $TotalIndex; ?>][title]" style="width:<?= $max_length; ?>px;" value="<?=htmlspecialchars($TotalDetails['Name'], ENT_QUOTES); ?>">
								</td>
								<td align="right" class="<?= $TotalStyle; ?>">
									<input name="update_totals[<?= $TotalIndex; ?>][value]" size="6" value="<?= $TotalDetails['Price']; ?>">
									<input name="update_totals[<?= $TotalIndex; ?>][class]" type="hidden" value="<?= $TotalDetails['Class']; ?>">
									<input type="hidden" name="update_totals[<?= $TotalIndex; ?>][total_id]" value="<?= $TotalDetails['TotalID']; ?>">
								</td>
								<td><small><strong>[-1 to remove]</strong></small></td>
							</tr>
									<?php }
								}
								# 334
								elseif ($TotalDetails['Class'] == "ot_total") { ?>
							<tr>
								<td align="right" class="<?= $TotalStyle; ?>"><b><?= $TotalDetails['Name']; ?></b></td>
								<td align="right" class="<?= $TotalStyle; ?>">
									<b><?= CK\text::monetize($TotalDetails['Price']); ?></b>
									<input name="update_totals[<?= $TotalIndex; ?>][title]" type="hidden" value="<?= trim($TotalDetails['Name']); ?>" size="<?= strlen($TotalDetails['Name']); ?>">
									<input name="update_totals[<?= $TotalIndex; ?>][value]" type="hidden" value="<?= $TotalDetails['Price']; ?>" size="6">
									<input name="update_totals[<?= $TotalIndex; ?>][class]" type="hidden" value="<?= $TotalDetails['Class']; ?>">
									<input type="hidden" name="update_totals[<?= $TotalIndex; ?>][total_id]" value="<?= $TotalDetails['TotalID']; ?>">
								</td>
							</tr>
								<?php }
								elseif ($TotalDetails['Class'] == "ot_subtotal") { ?>
							<tr>
								<td align="right" class="<?= $TotalStyle; ?>"><b><?= $TotalDetails['Name']; ?></b></td>
								<td align="right" class="<?= $TotalStyle; ?>">
									<b><?= CK\text::monetize($TotalDetails['Price']); ?></b>
									<input name="update_totals[<?= $TotalIndex; ?>][title]" type="hidden" value="<?= trim($TotalDetails['Name']); ?>" size="<?= strlen($TotalDetails['Name']); ?>">
									<input name="update_totals[<?= $TotalIndex; ?>][value]" type="hidden" value="<?= $TotalDetails['Price']; ?>" size="6">
									<input name="update_totals[<?= $TotalIndex; ?>][class]" type="hidden" value="<?= $TotalDetails['Class']; ?>">
									<input type="hidden" name="update_totals[<?= $TotalIndex; ?>][total_id]" value="<?= $TotalDetails['TotalID']; ?>">
								</td>
							</tr>
								<?php }
								elseif ($TotalDetails['Class'] == "ot_tax") { ?>
							<tr>
								<td align="right" class="<?= $TotalStyle; ?>"><b><?= trim($TotalDetails['Name']); ?></b><input name="update_totals[<?= $TotalIndex; ?>][title]" type="hidden" style="width:<?= $max_length; ?>px;" value="<?= trim($TotalDetails['Name']); ?>"></td>
								<td align="right" class="<?= $TotalStyle; ?>">
									<b><?= CK\text::monetize($TotalDetails['Price']); ?></b>
									<input name="update_totals[<?= $TotalIndex; ?>][value]" type="hidden" value="<?= $TotalDetails['Price']; ?>" size="6">
									<input name="update_totals[<?= $TotalIndex; ?>][class]" type="hidden" value="<?= $TotalDetails['Class']; ?>">
									<input type="hidden" name="update_totals[<?= $TotalIndex; ?>][total_id]" value="<?= $TotalDetails['TotalID']; ?>">
								</td>
							</tr>
								<?php }
								elseif ($TotalDetails['Class'] == "ot_coupon") {
									//quick fix to stop coupons adding to total instead of discounting as coupon modul stores positive value in DB not negative
									if ($TotalDetails['Price'] > 0) $TotalDetails['Price'] = number_format(-$TotalDetails['Price'], 2, '.', ''); ?>
							<tr>
								<td align="right" class="<?= $TotalStyle; ?>"><input name="update_totals[<?= $TotalIndex; ?>][title]" style="width:<?= $max_length; ?>px;" value="<?= htmlspecialchars($TotalDetails['Name'], ENT_QUOTES); ?>"></td>
								<td align="right" class="<?= $TotalStyle; ?>">
									<input name="update_totals[<?= $TotalIndex; ?>][value]" size="6" value="<?= $TotalDetails['Price']; ?>">
									<input type="hidden" name="update_totals[<?= $TotalIndex; ?>][class]" value="<?= $TotalDetails['Class']; ?>">
									<input type="hidden" name="update_totals[<?= $TotalIndex; ?>][total_id]" value="<?= $TotalDetails['TotalID']; ?>">
								</td>
							</tr>
								<?php }
								else { ?>
							<tr>
								<td align="right" class="<?= $TotalStyle; ?>"><input name="update_totals[<?= $TotalIndex; ?>][title]" style="width:<?= $max_length; ?>px;" value="<?= htmlspecialchars($TotalDetails['Name'], ENT_QUOTES); ?>"></td>
								<td align="right" class="<?= $TotalStyle; ?>">
									<input name="update_totals[<?= $TotalIndex; ?>][value]" size="6" value="<?= $TotalDetails['Price']; ?>">
									<input type="hidden" name="update_totals[<?= $TotalIndex; ?>][class]" value="<?= $TotalDetails['Class']; ?>">
									<input type="hidden" name="update_totals[<?= $TotalIndex; ?>][total_id]" value="<?= $TotalDetails['TotalID']; ?>">
								</td>
							</tr>
								<?php }
							} ?>
						</table>

						<p class="smalltext"><span style="color:red;">Hint:</span> Fields with "0" values are deleted when updating the order (exception: shipping)</p>
						<h5 class="subtitle">5. Status and Notification</h5>
						<table border="0" cellspacing="0" cellpadding="2" class="dataTableRow">
							<tr class="dataTableHeadingRow">
								<td class="dataTableHeadingContent" align="left">Entry Date</td>
								<td class="dataTableHeadingContent" align="left" width="10">&nbsp;</td>
								<td class="dataTableHeadingContent" align="center">Customer Notified</td>
								<td class="dataTableHeadingContent" align="left" width="10">&nbsp;</td>
								<td class="dataTableHeadingContent" align="left">Status</td>
								<td class="dataTableHeadingContent" align="left" width="10">&nbsp;</td>
								<td class="dataTableHeadingContent" align="left">Comment</td>
							</tr>
							<?php
							if ($orders_status_history = prepared_query::fetch('SELECT * FROM orders_status_history WHERE orders_id = :orders_id ORDER BY date_added', cardinality::SET, [':orders_id' => $oID])) {
								foreach ($orders_status_history as $orders_history) {
									$status_date = new DateTime($orders_history['date_added']); ?>
							<tr>
								<td class="smallText" align="center"><?= $status_date->format('m/d/Y H:i:s'); ?></td>
								<td class="dataTableHeadingContent" align="left" width="10">&nbsp;</td>
								<td class="smallText" align="center">
									<?= $orders_history['customer_notified']=='1'?tep_image(DIR_WS_ICONS.'tick.gif', ICON_TICK):tep_image(DIR_WS_ICONS.'cross.gif', ICON_CROSS); ?>
								</td>
								<td class="dataTableHeadingContent" align="left" width="10">&nbsp;</td>
								<td class="smallText" align="left"><?= !empty($orders_status_array[$orders_history['orders_status_id']])?$orders_status_array[$orders_history['orders_status_id']]:@$non_selectable_orders_status_array[$orders_history['orders_status_id']]; ?></td>
								<td class="dataTableHeadingContent" align="left" width="10">&nbsp;</td>
								<td class="smallText" align="left"><?= nl2br(htmlspecialchars($orders_history['comments']), ENT_QUOTES); ?>&nbsp;</td>
							</tr>
								<?php }
							}
							else { ?>
							<tr>
								<td class="smallText" colspan="5">No order history</td>
							</tr>
							<?php } ?>
						</table>
						<br>
						<table border="0" cellspacing="0" cellpadding="2" class="dataTableRow">
							<tr class="dataTableHeadingRow">
								<td class="dataTableHeadingContent" align="left">New Status</td>
								<td class="dataTableHeadingContent" align="left">Comment</td>
							</tr>
							<tr>
								<td>
									<table border="0" cellspacing="0" cellpadding="2">
										<tr>
											<th class="main"><b>Order Status:</b></th>
											<td class="main" align="right">
												<?php $orders_statuses = prepared_query::fetch('SELECT orders_status_id, orders_status_name FROM orders_status WHERE orders_status_id NOT IN (3, 6)'); ?>
												<select name="status" id="status">
													<?php foreach ($orders_statuses as $status) { ?>
													<option value="<?= $status['orders_status_id']; ?>" id="<?= $status['orders_status_id']; ?>" <?= $sales_order->get_header('orders_status')==$status['orders_status_id']?'selected':''; ?>>
														<?= $status['orders_status_name']; ?>
													</option>
													<?php } ?>
												</select>
											</td>
										</tr>
										<tr>
											<th class="main">Notify customer:</th>
											<td class="main" align="right"><input type="checkbox" name="notify" id="notify"></td>
										</tr>
										<tr>
											<th class="main">Send comments:</th>
											<td class="main" align="right"><input type="checkbox" name="notify_comments" id="notify_comments"></td>
										</tr>
									</table>
								</td>
								<td class="main"><textarea name="comments" id="comments"></textarea></td>
							</tr>
						</table>

						<h5 class="subtitle">6. Update Data</h5>
						<table width="100%" border="0" cellpadding="2" cellspacing="1">
							<tr>
								<td class="main" bgcolor="#FAEDDE">Please click on "Update" to save all changes.</td>
								<td class="main" bgcolor="#FBE2C8" width="10">&nbsp;</td>
								<td class="main" bgcolor="#FFCC99" width="10">&nbsp;</td>
								<td class="main" bgcolor="#F8B061" width="10">&nbsp;</td>
								<td class="main" bgcolor="#FF9933" width="120" align="center"><button type="submit">update</button></td>
							</tr>
						</table>
					</form>
				<?php }

				if ($action == "add_product") { ?>
					<table border="0" width="100%" cellspacing="0" cellpadding="0">
						<tr>
							<td class="pageHeading"><?php echo ADDING_TITLE; ?> (No. <?= $oID; ?>)</td>
							<td class="pageHeading" align="right"><a href="/admin/edit_orders.php<?php tep_get_all_get_params(array('action')); ?>"><?= tep_image_button('button_back.gif', IMAGE_BACK); ?></a></td>
						</tr>
					</table>
					<?php $results = prepared_query::fetch("SELECT products_name, p.products_id, categories_name, ptc.categories_id FROM products p LEFT JOIN products_description pd ON pd.products_id=p.products_id LEFT JOIN products_to_categories ptc ON ptc.products_id=p.products_id LEFT JOIN categories_description cd ON cd.categories_id=ptc.categories_id where pd.language_id = 1 ORDER BY categories_name", cardinality::SET);

					foreach ($results as $row) {
						$ProductList[$row['categories_id']][$row['products_id']] = $row['products_name'];
						$CategoryList[$row['categories_id']] = $row['categories_name'];
						$LastCategory = $row['categories_name'];
					} ?>

					<table border="0">
						<?php // Set Defaults
						if (!isset($_POST['add_product_categories_id'])) $add_product_categories_id = 0;
						if (!isset($_POST['add_product_products_id'])) $add_product_products_id = 0;

						// Step 1: Choose Category
						?>
						<tr class="dataTableRow">
							<form action="<?= $_SERVER['PHP_SELF'].'?oID='.$_GET['oID'].'&action='.$_GET['action']; ?>" method="POST">
								<td class="dataTableContent" align="right"><b><?= ADDPRODUCT_TEXT_STEP; ?> 1:</b></td>
								<td class="dataTableContent" valign="top">
									<?php if (isset($_POST['add_product_categories_id'])) $current_category_id = $_POST['add_product_categories_id'];
									echo tep_draw_pull_down_menu('add_product_categories_id', tep_get_category_tree(), $current_category_id, 'onChange="this.form.submit();"'); ?>
									<input type="hidden" name="step" value="2">
								</td>
								<td class="dataTableContent"><?= ADDPRODUCT_TEXT_STEP1; ?></td>
							</form>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<?php // Step 2: Choose Product
						if (($_POST['step'] > 1) && ($_POST['add_product_categories_id'] > 0)) { ?>
						<tr class="dataTableRow">
							<form action="<?= $_SERVER['PHP_SELF'].'?oID='.$_GET['oID'].'&action='.$_GET['action']; ?>" method="POST">
								<td class="dataTableContent" align="right"><b><?= ADDPRODUCT_TEXT_STEP; ?> 2: </b></td>
								<td class="dataTableContent" valign="top">
									<select name="add_product_products_id" onChange="this.form.submit();">
										<option value="0"><?= ADDPRODUCT_TEXT_SELECT_PRODUCT; ?></option>
										<?php asort($ProductList[$_POST['add_product_categories_id']]);
										foreach ($ProductList[$_POST['add_product_categories_id']] as $ProductID => $ProductName) { ?>
										<option value="<?= $ProductID; ?>" <?= !empty($_POST['add_product_products_id'])&&$ProductID==$_POST['add_product_products_id']?'selected':''; ?>><?= $ProductName; ?></option>
										<?php } ?>
									</select>
								</td>
								<input type="hidden" name="add_product_categories_id" value="<?= $_POST['add_product_categories_id']; ?>">
								<input type="hidden" name="step" value="3">
								<td class="dataTableContent"><?= ADDPRODUCT_TEXT_STEP2p; ?></td>
							</form>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<?php }

						// Step 3: Choose Options
						if (($_POST['step'] > 2) && ($_POST['add_product_products_id'] > 0)) { ?>
						<tr class="dataTableRow">
							<td class="dataTableContent" align="right"><b><?= ADDPRODUCT_TEXT_STEP; ?> 3: </b></td>
							<td class="dataTableContent" valign="top" colspan="2"><i><?= ADDPRODUCT_TEXT_OPTIONS_NOTEXIST; ?></i></td>
						</tr>
							<?php $_POST['step'] = 4; ?>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<?php }

						// Step 4: Confirm
						if ($_POST['step'] > 3) { ?>
						<tr class="dataTableRow">
							<form action="<?= $_SERVER['PHP_SELF'].'?oID='.$_GET['oID'].'&action='.$_GET['action']; ?>" method="POST">
								<td class="dataTableContent" align="right"><b><?= ADDPRODUCT_TEXT_STEP; ?> 4: </b></td>
								<td class="dataTableContent" valign="top"><input name="add_product_quantity" size="2" value="1"><?= ADDPRODUCT_TEXT_CONFIRM_QUANTITY; ?></td>
								<td class="dataTableContent" align="center">
									<input type="submit" value="<?= ADDPRODUCT_TEXT_CONFIRM_ADDNOW; ?>">
									<?php if (is_array($_POST['add_product_options'])) {
										foreach ($_POST['add_product_options'] as $option_id => $option_value_id) { ?>
									<input type="hidden" name="add_product_options[<?= $option_id; ?>]" value="<?= $option_value_id; ?>">
										<?php }
									} ?>
									<input type="hidden" name="add_product_categories_id" value="<?= $_POST['add_product_categories_id']; ?>">
									<input type="hidden" name="add_product_products_id" value="<?= $_POST['add_product_products_id']; ?>">
									<input type="hidden" name="step" value="5">
								</td>
							</form>
						</tr>
						<?php } ?>
					</table>
				<?php } ?>
			</td>
		</tr>
	</table>
	
	<?php $ipnId = isset($_GET['ipnId'])?$_GET['ipnId']:NULL; ?>
	<div id="additem">
		<style>
			#moreoptions table { font-size:12px; }
			.price-level { font-weight:bold; }
		</style>
		<form name="ipnForm" action="/admin/edit_orders.php?oID=<?= $sales_order->id(); ?>&action=add_product" method="post" onsubmit="return false;">
			<table width="560" border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td colspan="4" align="right" style="background-color:#ddd;"><a href="" onclick="return closeMod('additem');">X</a> </td>
				</tr>
				<tr>
					<td width="80" align="right" style="height:40px;"><label class="main">IPN Lookup:</label></td>
					<td colspan="3">
						<input id="ipn_search_input" name="ipn_search_input" value="<?= !empty($ipnId)?$ipnId:''; ?>">
						<div id="ipn_search_container" class="autocompletenew"></div>
					</td>
				</tr>
			</table>
			<div id="moreoptions">
				<table width="100%" border="0" cellpadding="2" cellspacing="0">
					<tr>
						<td>Stock Name:</td>
						<td id="stockname"></td>
						<td>Product Model:</td>
						<td id="productmodel"></td>
					</tr>
					<tr>
						<td>On Hand:</td>
						<td id="qtyonhand"></td>
						<td class="<?= $customer->get_header('customer_price_level_id')==1?'price-level':''; ?>">Retail:</td>
						<td id="stockprice"></td>
					</tr>
					<tr>
						<td>Allocated:</td>
						<td id="qtyalloc"></td>
						<td class="<?= $customer->get_header('customer_price_level_id')==2?'price-level':''; ?>">Reseller:</td>
						<td id="dealerprice"></td>
					</tr>
					<tr>
						<td><strong>Available:</strong></td>
						<td id="qtyavail"></td>
						<td class="<?= $customer->get_header('customer_price_level_id')==3?'price-level':''; ?>">Wholesale High:</td>
						<td id="wshighprice"></td>
					</tr>
					<tr>
						<td colspan="2"></td>
						<td class="<?= $customer->get_header('customer_price_level_id')==4?'price-level':''; ?>">Wholesale Low:</td>
						<td id="wslowprice"></td>
					</tr>
					<tr>
						<td colspan="2"></td>
						<td>Average Cost:</td>
						<td id="averagecost"></td>
					</tr>
				</table>
				<div id="addons"></div>
				<table width="100%" border="0" cellpadding="2" cellspacing="0">
					<tr>
						<td colspan="4">&nbsp;</td>
					</tr>
					<tr>
						<td width="50" align="right"><label class="main">Qty:</label></td>
						<td width="100"><input type="text" id="add_product_quantity" name="add_product_quantity" value="" onkeydown="catchEnter(event);" style="width:100px;" /></td>
						<td width="75" align="right"><label class="main">Price:</label></td>
						<td><input type="text" id="add_product_price" name="add_product_price" value="" onkeydown="catchEnter(event);" style="width:100px;" /></td>
					</tr>
				</table>
			</div>
			<div id="addbutton">
				<input type="button" name="addtoorder" id="addtoorder" value="Add Item" onclick="addSubmit();" />
				<input type="button" name="closemod" value="Cancel" onclick="return closeMod('additem');" />
			</div>
			<input type="hidden" id="add_product_products_id" name="add_product_products_id" value="">
			<input type="hidden" name="step" value="5">
			<input type="hidden" id="winop" name="winop" value="">
			<input type="hidden" id="addeditm" name="addeditm" value="<?= !empty($winop)?$winop:''; ?>">
			<input type="hidden" id="customer_price_level_id" name="customer_price_level_id" value="<?= $customer->get_header('customer_price_level_id'); ?>">
			<input type="hidden" name="addonvallist" id="addonvallist" value="">
		</form>
		<script type="text/javascript">
			new Ajax.Autocompleter("ipn_search_input", "ipn_search_container", "ipn_getListWithProducts.php", {
				method: 'get',
				callback: function(value) {
					return "value="+ value.value;
				},
				afterUpdateElement: function(input, li) {
					$("add_product_products_id").value = li.id;
					var itemArr = $("item_"+li.id).value.split("^");
					var addonArr = new Array();

					if (itemArr[10]!="") {
						this.addonDisp(itemArr[10],li.id);
					}
					else {
						$("addonvallist").value = "";
						$("additem").style.height = "190px";
						$("addons").innerHTML = "";
					}

					// If customer is dealer, suggest dealer price instead of stock one
					let price_level_idx = parseInt(jQuery('#customer_price_level_id').val())-1;
					while (!itemArr[price_level_idx] && price_level_idx > 0) price_level_idx--;
					$("add_product_price").value = itemArr[price_level_idx];
					$("add_product_price").value = !parseFloat(jQuery('#add_product_price').val())?'0.00':jQuery('#add_product_price').val();
					$("stockprice").innerHTML = itemArr[0];
					$("dealerprice").innerHTML = itemArr[1];
					jQuery('#wshighprice').html(itemArr[2]);
					jQuery('#wslowprice').html(itemArr[3]);
					$("averagecost").innerHTML = itemArr[4];
					$("qtyalloc").innerHTML = itemArr[5];
					$("qtyonhand").innerHTML = itemArr[6];
					$("qtyavail").innerHTML = itemArr[7];
					$("stockname").innerHTML = itemArr[8];
					$("productmodel").innerHTML = itemArr[9];
					$("add_product_quantity").value = (!parseInt($("add_product_quantity").value,10)&&$("add_product_quantity").value<=0) ? 1 : $("add_product_quantity").value;
					$("addtoorder").disabled = 0;
					$("moreoptions").style.visibility = 'visible';
					$("ipn_search_input").value = '';
					$("add_product_quantity").select();
				},

				addonDisp: function(addonlist,parentid) {
					var jhtml = "";
					var hght = 270;
					var addonitemid = 0;
					var addonvallist = "";
					addonArr = addonlist.split("|");
					$("addons").style.display = "block";
					jhtml += '<table border="0" width="97%" align="center">';
					jhtml += '<tr>';
					jhtml += '<td colspan="5" class="main" style="height:30px;vertical-align:bottom;"><b>Included Items</b>:</td>';
					jhtml += '</tr>';

					for (k=0;k<addonArr.length;k++) {
						addonlnArr = addonArr[k].split("~");
						addonitemid = addonlnArr[8];
						addonvallist += addonitemid + "^";
						jhtml += '<tr>';
						jhtml += '<td valign="top" style="width:20px;"><input type="checkbox" name="addonitem[]" id="'+addonitemid+'" value="'+addonitemid+'" checked="checked" /></td>';
						jhtml += '<td class="main" style="width:220px;"><label for="addonitem_'+addonitemid+'">';
						jhtml += '<span class="itemDescription"><b>'+addonlnArr[1]+'</b></span><input type="hidden" name="addon_desc_'+addonitemid+'" value="'+addonlnArr[1]+'" /><br />';
						jhtml += '<span style="font-size: 11px; color: #777777; font-style: italic;">'+addonlnArr[2]+'</span>';
						jhtml += '</label><input type="hidden" name="addon_model_'+addonitemid+'" value="'+addonlnArr[4]+'" /></td>';
						jhtml += '</td>';
						jhtml += '<td class="main" style="vertical-align:top;font-size:11px;white-space:nowrap;"><div style="height:25px;line-height:20px;">Avail.:&nbsp;'+addonlnArr[7]+'</div></td>';
						jhtml += '<td class="main" style="vertical-align:top;font-size:11px;white-space:nowrap;">Qty:&nbsp;<input type="text" id="addon_qty_'+addonitemid+'" name="addon_qty_'+addonitemid+'" value="1" onkeydown="catchEnter(event);" style="width:30px;" /></td>';
						jhtml += '<td class="main" style="vertical-align:top;font-size:11px;white-space:nowrap;">Price:&nbsp;<input type="text" id="addon_price_'+addonitemid+'" name="addon_price_'+addonitemid+'" value="'+addonlnArr[3]+'" onkeydown="catchEnter(event);" style="width:50px;" /></td>';
						jhtml += '</tr>';
						hght += 60;
					}
					jhtml += '</table>\n';

					$("addonvallist").value = addonvallist;
					$("additem").style.height = hght+"px";
					$("addons").innerHTML = jhtml;
				}
			});
		</script>
	</div>
</body>
</html>
				}