<?php require('includes/application_top.php'); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<script language="javascript" src="includes/menu.js"></script>
		<script language="javascript" src="includes/general.js"></script>
		<script type="text/javascript" src="../includes/javascript/prototype.js"></script>
		<script type="text/javascript" src="../includes/javascript/scriptaculous/scriptaculous.js"></script>
		<script type="text/javascript" src="../includes/javascript/jquery-1.4.2.min.js"></script>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#buyers').change(function() {
					window.location.href = 'urgent_order_list.php?selected_box=purchasing&rep='+($(this).val());
				});
			});
		</script>
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<!-- header //-->
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
		<!-- header_eof //-->

		<!-- body //-->
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
					<table border="0" width="100%" cellspacing="0" cellpadding="0">
						<tr>
							<td class="pageHeading">Urgent Order List</td>
						</tr>
						<tr>
							<td>
								<select name="buyers" id="buyers">
									<option value="All">All</option>
									<?php $buyers = prepared_query::fetch('SELECT DISTINCT a.* FROM vendors v JOIN admin a ON v.pm_to_admin_id = a.admin_id ORDER BY a.admin_firstname ASC');
									foreach ($buyers as $buyer) { ?>
									<option value="<?= $buyer['admin_id']; ?>" <?= !empty($_GET['rep'])&&$_GET['rep']==$buyer['admin_id']?'selected':''; ?>><?= $buyer['admin_firstname'].' '.$buyer['admin_lastname']; ?></option>
									<?php } ?>
								</select>
								<div id="items_needed" style="padding-top:10px;">
									<input type="button" value="Create RFQ/WTB" id="createrfq">
									<?php
									//prepare some data
									//Grab the qty needed
									//Get the rep if available
									$product_manager_id = !empty($_GET['rep'])?$_GET['rep']:NULL;
									$intval_total = 0;
									$allocated_array = ck_ipn2::get_legacy_allocated_ipns();
									$products = prepared_query::fetch('SELECT psc.stock_id, psc.stock_name, psc.on_order, psc.vendor_to_stock_item_id as preferred_vendor_id, v.vendors_id, v.vendors_company_name, CASE WHEN psc.serialized = 1 THEN COUNT(s.id) ELSE psc.stock_quantity END as products_quantity, ih.quantity as quarantine_qty FROM products_stock_control psc LEFT JOIN vendors_to_stock_item vtsi ON psc.vendor_to_stock_item_id = vtsi.id LEFT JOIN vendors v ON vtsi.vendors_id = v.vendors_id LEFT JOIN serials s ON psc.stock_id = s.ipn AND s.status IN (2, 3, 6) LEFT JOIN (SELECT stock_id, SUM(quantity) as quantity FROM inventory_hold GROUP BY stock_id) ih ON psc.stock_id = ih.stock_id WHERE psc.is_bundle = 0 AND (:admin_id IS NULL OR v.pm_to_admin_id = :admin_id) GROUP BY psc.stock_id, psc.stock_name, psc.on_order, psc.vendor_to_stock_item_id, v.vendors_company_name ORDER BY v.vendors_company_name, psc.stock_name ASC', cardinality::SET, [':admin_id' => $product_manager_id]);

									if (!empty($products)) { ?>
									<style>
										.urgent-list th, .urgent-list td { font-family:verdana; font-size:11px; text-align:left; }
										.urgent-list th { font-size:14px; text-decoration:underline; padding-top:35px; border-top:1px solid #000; }
										.urgent-list td { border-bottom:1px dotted #999; }
										.urgent-list .required { color:#f00; font-weight:bold; }
										.urgent-list .old td { background-color:#ff0; }
										.urgent-list tr:not(.vendor-header):hover td { background-color:#ffc; }
									</style>
									<table cellpadding="5px" cellspacing="0" border="0" class="urgent-list">
										<?php foreach ($products as $product) {
											if (!empty($allocated_array[$product['stock_id']]) && (int)$allocated_array[$product['stock_id']] > ($product['products_quantity'] - $product['quarantine_qty'])) {
												$class = '';
												if (!isset($allocated_array[$product['stock_id']])) $allocated_array[$product['stock_id']] = 0;

												$orders = prepared_query::fetch('SELECT o.orders_id, o.promised_ship_date, os.orders_status_name, oss.orders_sub_status_name, op.expected_ship_date, (SELECT DATEDIFF(CURDATE(), o.date_purchased) as intval FROM orders_status_history osh WHERE osh.orders_id = o.orders_id ORDER BY osh.orders_status_history_id DESC LIMIT 1) as intval FROM orders o LEFT JOIN orders_status os ON (o.orders_status = os.orders_status_id) LEFT JOIN orders_sub_status oss ON (o.orders_sub_status = oss.orders_sub_status_id), orders_products op, products_stock_control psc, products p WHERE psc.stock_id = :stock_id AND psc.stock_id = p.stock_id AND (op.products_id = p.products_id OR ((op.products_id - p.products_id) = 0)) AND op.orders_id = o.orders_id AND o.orders_status IN (1, 2, 5, 7, 8, 10, 11, 12) AND IFNULL((SELECT SUM(po2oa.quantity) FROM purchase_order_to_order_allocations po2oa WHERE po2oa.order_product_id = op.orders_products_id), 0) < op.products_quantity GROUP BY o.orders_id', cardinality::SET, [':stock_id' => $product['stock_id']]);

												$orders_list = [];
												$expected_ship_date = NULL;
												foreach ($orders as $all_orders) {
													$status_text = $all_orders['orders_status_name'];
													if ($status_text == 'Customer service' && $all_orders['orders_sub_status_name'] != NULL) {
														$status_text = 'C.S. - '.$all_orders['orders_sub_status_name'].' - '.$all_orders['intval'].' Days';
														if ($all_orders['intval'] > 2) {
															$intval_total++;
															$class = 'old';
														}
													}

													$ord = '<a href="orders_new.php?selected_box=orders&oID='.$all_orders['orders_id'].'&action=edit" style="white-space:nowrap;">'.$all_orders['orders_id'].' ('.$status_text.')</a>';

													$promised_ship_date = NULL;
													if (!empty($all_orders['promised_ship_date'])) $promised_ship_date = new DateTime($all_orders['promised_ship_date']);

													$expected_ship_date = ck_datetime::datify($all_orders['expected_ship_date']);
													if ($expected_ship_date->format('Y-m-d') == '2099-01-01') $expected_ship_date = 'None/Call';
													elseif (empty($expected_ship_date)) $expected_ship_date = 'N/A';
													else $expected_ship_date = $expected_ship_date->format('Y-m-d');

													//if (!empty($orders_list)) {
														//MMD - move the vendor/table header display inside the if block so it does
														//not display if there are no products to show for that vendor
														if (empty($vendor) || $vendor != $product['vendors_company_name']) {
															//MMD - also, only set the vendor variable if we are actually going to be
															//displaying that vendor
															$vendor = $product['vendors_company_name']; ?>
											<tr class="vendor-header">
												<th colspan="6"><?= $product['vendors_company_name']; ?> <a href="/admin/stock_reorder_report.php?vendor_id=<?= $product['vendors_id']; ?>&action=Build+Report" title="View items for this vendor on the SRL" target="_blank">[SRL]</a></th>
											</tr>
											<tr class="vendor-header">
												<td>IPN</td>
												<td>Order #</td>
												<td>Expected Ship Date</td>
												<td>Promised Ship Date</td>
												<td class="required">Qty Reqd</td>
												<td>Salable</td>
												<td>Allocated</td>
												<td>On Order</td>
											</tr>
														<?php } ?>
											<tr class="<?= $class; ?>">
												<td>
													<input type="checkbox" name="add_po[<?= $product['stock_id']; ?>]" value="<?= $product['stock_id']; ?>" class="add_ipn">
													<a href="ipn_editor.php?ipnId=<?= urlencode($product['stock_name']); ?>" target="_blank" ><?= $product['stock_name']; ?></a>
												</td>
												<td><?= $ord; ?></td>
												<td><?= $expected_ship_date; ?></td>
												<td>
													<?php if (!empty($promised_ship_date)) echo $promised_ship_date->format('Y-m-d');
													else echo 'N/A'; ?>
												</td>
												<td class="required" id="qty_reqd_<?= $product['stock_id']; ?>" qty="<?php echo (($product['products_quantity'] - $allocated_array[$product['stock_id']] - $product['quarantine_qty']) * -1); ?>"><?= (($product['products_quantity'] - $allocated_array[$product['stock_id']] - $product['quarantine_qty']) * -1); ?></td>
												<td><?= ($product['products_quantity'] - $product['quarantine_qty']); ?></td>
												<td><?= $allocated_array[$product['stock_id']]; ?></td>
												<td><?= $product['on_order']; ?></td>
											</tr>
													<?php // }
												}
											}
										} ?>
										<tr>
											<td colspan="8" height="20">&nbsp;</td>
										</tr>
										<tr>
											<td colspan="8"><b>Total # IPN's Over 2 days Old: <?= $intval_total; ?></b></td>
										</tr>
									</table>
									<?php } ?>
								</div>
								<script type="text/javascript">
									function submit_as_form(action, method, data) {
										var $form = jQuery('<form action="'+action+'" method="'+method.toLowerCase()+'" target="_BLANK"></form>');
										for (var key in data) {
											if (data.hasOwnProperty(key)) {
												if (jQuery.isArray(data[key])) {
													for (var i=0; i<data[key].length; i++) {
														$form.append('<input type="hidden" name="'+key+'['+i+']" value="'+data[key][i]+'">');
													}
												}
												else {
													$form.append('<input type="hidden" name="'+key+'" value="'+data[key]+'">');
												}
											}
										}
										jQuery('body').append($form);
										$form.submit();
									}

									jQuery('#createrfq').click(function(e) {
										e.preventDefault();
										var data = { action: 'create-from-srl', stock_id: [], quantity: [] };
										var ctr = 0;
										jQuery('.add_ipn:checked').each(function() {
											if (data.stock_id.length > 0) {
												var found = false;
												var idx;

												for (var i = 0; i < data.stock_id.length; i++) {
													if (data.stock_id[i] == jQuery(this).val()) {
														found = true;
														idx = i;
													}
												}

												if (found) {
													data.quantity[idx] = parseInt(data.quantity[idx]) + parseInt(jQuery('#qty_reqd_'+jQuery(this).val()).attr('qty'));
												}
												else {
													data.stock_id[ctr] = jQuery(this).val();
													data.quantity[ctr] = jQuery('#qty_reqd_'+jQuery(this).val()).attr('qty');
													ctr++;
												}
											}
											else {
												data.stock_id[ctr] = jQuery(this).val();
												data.quantity[ctr] = jQuery('#qty_reqd_'+jQuery(this).val()).attr('qty');
												ctr++;
											}
										});

										submit_as_form('/admin/rfq_detail.php', 'post', data);
										return false;
									});
								</script>
							</td>
						</tr>
						<!-- EO adding code for items needed for orders -->
					</table>
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
</html>
