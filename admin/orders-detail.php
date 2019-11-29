<?php require_once('includes/classes/address_check.php');
require_once('includes/functions/po_alloc.php');

$customer = $sales_order->get_customer();

$orders_statuses = [];
$orders_status_array = [];
$order_status_results = prepared_query::fetch('SELECT orders_status_id, orders_status_name, obsolete FROM orders_status ORDER BY sort_order ASC', cardinality::SET);
foreach ($order_status_results as $order_status) {
	$orders_status_array[$order_status['orders_status_id']] = $order_status['orders_status_name'];
	if ($order_status['obsolete'] == 1) continue;

	$orders_statuses[] = array('id' => $order_status['orders_status_id'], 'text' => $order_status['orders_status_name']);
}

$orders_sub_statuses = [];
$orders_sub_status_array = [];
$order_sub_status_results = prepared_query::fetch('SELECT orders_sub_status_id, orders_status_id, orders_sub_status_name, obsolete FROM orders_sub_status ORDER BY sort_order ASC', cardinality::SET);
foreach ($order_sub_status_results as $order_sub_status) {
	$orders_sub_status_array[$order_sub_status['orders_sub_status_id']] = $order_sub_status['orders_sub_status_name'];
	if ($order_sub_status['obsolete'] == 1) continue;

	$orders_sub_statuses[] = $order_sub_status;
}

$account_managers = ck_admin::get_account_managers(['ck_admin', 'sort_by_name']);
$sales_teams = ck_team::get_sales_teams();

$user = ck_admin::login_instance(ck_admin::CONTEXT_ADMIN);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<script src="serials/serials.js?v=4"></script>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<link rel="stylesheet" type="text/css" href="serials/serials.css">
	<link rel="stylesheet" type="text/css" href="/includes/javascript/scriptaculous/scriptaculous.css">
	<link rel="stylesheet" type="text/css" href="css/shipaddrtrack.css">
	<link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-Bx4pytHkyTDy3aJKjGkGoHPt3tvv6zlwwjc3iqN7ktaiEMLDPqLSZYts2OjKcBx1" crossorigin="anonymous">
	<style>
		#orddtl { visibility: hidden; border: 1px #000 solid; padding: 5px; font: 10px Verdana; }

		.special-payment-method { font-weight: bold; color: #ff0000; }
		.status_tab_container { margin: 0px; padding: 0px; height: 26px; width: 100%; border-top: solid #000 1px; border-left: solid #000 1px; border-right: solid #000 1px; border-bottom: solid #9BBDCA 10px; background-color: #0F4B96; }
		.status_tabs { margin: 0px; padding: 0px; font-family: arial; font-size: small; font-weight: bold; color: #FFF; line-height: 26px; white-space: nowrap; }
		.status_tab{ display: inline; }
		.status_tab_selected{ display: inline; }
		.status_tab a { text-decoration: none; font-weight: bold !important; padding: 7px 7px; color: #FFF !important; }
		.status_tab a:hover { font-weight: bold; color: #FFF; background-color: #9BBDCA; }
		.status_tab_selected a { text-decoration: none; padding: 7px 10px; color: #FFF; font-weight: bold; background-color: #9BBDCA }

		input.active { outline: 1px solid #27AD3E; }

		.bold { font-weight: bold; }
	</style>
	<script src="/images/static/js/ck-styleset.js"></script>
	<script src="/images/static/js/ck-ajaxify.max.js"></script>
	<script src="/images/static/js/ck-button-links.max.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<div id="orddtl"></div>
	<div id="modal" class="jqmWindow" style="width: 800px;">
		<a class="jqmClose" href="#" style="float: right; clear: both;">X</a>
		<div id="modal-content" style="max-height: 600px; overflow: auto;"></div>
	</div>
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<!-- header_eof //-->
	<script type="text/javascript" src="serials/serials.js?v=3"></script>
	<script type="text/javascript" src="includes/javascript/order.js?d=<?= date('mdy'); ?>&v=1.0.8"></script>
	<script type="text/javascript">
		function clearFollowUpDate( id ) {
			var elem = document.getElementById( id );
			elem.value = '';
		}
	</script>
	<!-- body //-->

	<div id="serials_dialog_container">
		<div id="serials_dialog" style="display:none">
			<style>
				.add-serial-error { background-color:#fcc; border:1px solid #c00; padding:10px; margin:5px; border-radius:5px; text-align:left; }
			</style>
			<div id="serials_diaglog_titlebar">
				<span style="float:left; margin: 3px 0 0 10px;">Previously Entered Serials</span>
				<img src="/admin/images/serials/title-bar-close.png" id="serials_menu_close" class="jqmClose" style="float: right; margin-right: 3px;" onClick="$('serials_dialog').hide();">
			</div>

			<div id="previously_entered_serials"></div>
			<p class="serials_dialog_title">Enter Serials</p>
			<input type="hidden" name="order_id" id="order_id">
			<input type="hidden" name="order_product_id" id="orders_products_id">
			<input type="hidden" name="product_id" id="product_id">
			<input type="hidden" name="ipn_id" id="ipn_id">
			<input type="hidden" name="qty" id="qty">
			<table>
				<tr>
					<td>Enter Serial for product: </td>
					<td id="product_name"></td>
					<td>
						<input type="text" name="serial_autocomplete" id="serial_autocomplete">
						<input type="hidden" name="serial_id" id="serial_id">
						<input type="button" name="submit" value="ok" id="add_serial_button">
						<input type="button" name="done" value="done" class="jqmClose" onClick="$('serials_dialog').hide();">
					</td>
				</tr>
				<tr><td></td></tr>
				<tr>
					<td colspan="2">Serials Needed: <input type="text" name="serials_needed" id="serials_needed" disabled size="3" style="background-color:#ffffff; color:#000000; border:1px solid #ffffff;" ></td>
					<td>Serials Remaining: <input type="text" name="serials_remaining" id="serials_remaining" disabled size="3" style="background-color:#ffffff; color:#000000; border:1px solid #ffffff;" ></td>
				</tr>
			</table>

			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$('#serial_autocomplete').autocomplete({
						delay: 600,
						source: function(request, callback) {
							autocompleteHelper(request.term, callback);
						},
						select: function(event, ui) {
							event.preventDefault();
							setTimeout(function() {
								$('#add_serial_button').focus();
							}, 100);
							$('#serial_autocomplete').val(ui.item.label);
							$('#serial_id').val(ui.item.value);
						},
						focus: function(event, ui) {
							event.preventDefault();
						}
					}).keypress(function(event) {
						if ((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {
							//$('#add_serial_button').focus();
							autocompleteHelper($('#serial_autocomplete').val(), function(data) {
								$('#serial_autocomplete').val(data[0].label);
								$('#serial_id').val(data[0].value),
								$('#add_serial_button').focus();
								$('#serial_autocomplete').autocomplete('close');
							});
						}
					});

					$('#add_serial_button').click(function(event) {
						add_serial_to_order(
							$('#serial_id').val(),
							$('#ipn_id').val(),
							$('#order_id').val(),
							$('#serial_autocomplete').val(),
							$('#orders_products_id').val()
						);
					});
				});

				function autocompleteHelper(term, callback) {
					if (jQuery('#serial_autocomplete').val() == '') return 0;

					jQuery.ajax({
						url: '/admin/orders_new.php',
						dataType: 'json',
						data: {
							action: 'serial-allocate-lookup',
							ajax: 1,
							orders_id: jQuery('#orders_id').val(),
							orders_products_id: jQuery('#orders_products_id').val(),
							stock_id: jQuery('#ipn_id').val(),
							serial_number: term
						},
						success: function(data) {
							callback(data);
						},
					});
				}
			</script>
		</div>
	</div>
	<div id="serials_release_dialog_container">
		<div id="serials_release_dialog" style="display: none;">
		Would you like to release the allocation of serialized items on this order?<br/><br/>
		<input type="button" value="Yes" onClick="deallocate_serials(<?= $sales_order->id(); ?>);">
		<input type="button" value="No" onClick="document.forms['order_status'].submit();">
		</div>
	</div>

	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?= BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top" class="order-details-body">
				<style>
					.sep { margin-bottom:10px; }
					.cen { text-align:center; }
					.act { text-align:right; padding-right:60px; padding-bottom:20px; }
				</style>
				<div>
					<table border="0" width="100%" cellspacing="0" cellpadding="0">
						<tr>
							<td class="pageHeading">Order: <span style="color: blue;"><?= $sales_order->id(); ?></span></td>
							<td class="pageHeading">Order Placed: <?= $sales_order->get_header('date_purchased')->format('m/d/Y h:i a'); ?></td>
							<td class="pageHeading" align="right"><?= tep_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
							<td class="pageHeading" align="right">
								<?php if ($sales_order->is_open()) { ?>
								<a href="#" id="quick-order-control" class="button-link" style="font-size:16px;">Quick Order Control</a> |
								<?php } ?>
								<a href="<?= tep_href_link('orders_new.php', tep_get_all_get_params(['action', 'referer'])); ?>" class="button-link" style="font-size:16px;">Back</a>
							</td>
						</tr>
						<?php if ($sales_order->is_open()) { ?>
						<tr>
							<td colspan="4" style="text-align:right;">
								<style>
									#quick-order-control-block { display:none; text-align:right; border-top:1px dashed #999; margin-top:3px; padding-top:3px; }
									#quick-order-control-block.on { display:block; }
								</style>
								<div id="quick-order-control-block" class="<?= !empty($_SESSION['quick-order-control'])?'on':''; ?>">
									<div style="margin:10px; float:left;">
										<form action="/admin/orders_new.php?status=2&oID=<?= $sales_order->id(); ?>&action=edit" method="post">
											<input type="hidden" name="orders_id" value="<?= $sales_order->id(); ?>">
											<label for="move-to-pack-queue" style="font-weight:bold;">Move To:</label>
											<input style="font-size:30px; cursor:pointer;" type="submit" name="move-to-pack-queue" id="move-to-pack-queue" value="Pack Queue">
										</form>
									</div>
									<div style="margin:10px; float:right;">
										<a href="#" class="button-link" id="print-pick-pack" style="font-size:30px;">Pick/Pack List</a> |
										<a href="#" class="button-link" id="add-packages" style="font-size:30px;">Add Packages</a> |
										<a href="#" class="button-link" id="charge-and-ship" style="font-size:30px;"><?= $sales_order->is_cc_capture_needed()?'Charge &amp; Ship':'Ship'; ?></a>
									</div>
								</div>
								<script>
									jQuery('#quick-order-control').click(function(e) {
										e.preventDefault();
										jQuery('#quick-order-control-block').toggleClass('on');
										jQuery.ajax({
											data: { ajax: 1, action: 'set-quick-order-control', 'quick-order-control': jQuery('#quick-order-control-block').hasClass('on')?1:0 }
										});
									});
									jQuery('#print-pick-pack').click(function(e) {
										e.preventDefault();
										popupWindow('/admin/pack_and_pick_list.php?oID='+jQuery('#orders_id').val(), 860);
									});
									jQuery('#add-packages').click(function(e) {
										e.preventDefault();
										dispEditPack(jQuery('#orders_id').val(), 0, '');
									});
									jQuery('#charge-and-ship').click(function(e) {
										e.preventDefault();
										jQuery('#process-ship-button').click();
									});
								</script>
							</td>
						</tr>
						<?php } ?>
					</table>
				</div>
				<div class="sep">
					<hr>
					<style>
						.order-header {  }
						.order-info-block { background-color:#eee; }
						.order-info-block button { font-size:11px; float:right; }
						.order-shipping-block { }
						.order-billing-block { }
						.order-address { padding-left:15px; padding-right:15px; width:260px; }

						.account-manager-select { display:none; }
						.sales-team-select { display:none; }
					</style>
					<input type="hidden" id="orders_id" value="<?= $sales_order->id(); ?>">
					<table border="0" cellspacing="2" cellpadding="2" class="order-header">
						<tr>
							<td valign="top" class="order-info-block">
								<table border="0" cellspacing="0" cellpadding="2">
									<tr>
										<td class="main" valign="top">
											<a href="/admin/customers_detail.php?customers_id=<?= $customer->id(); ?>" target="_blank" style="font-weight: bold; font-size: 12px; color: blue; text-decoration: underline;">Customer</a><br>
											<?= $customer->id(); ?>
											<?php if ($_SESSION['perms']['use_master_password'] == 1) { ?>
											<br>
											<a href="/admin/customers_list.php?customers_id=<?= $sales_order->get_header('customers_id'); ?>&customers_extra_logins_id=<?= $sales_order->get_header('customers_extra_logins_id'); ?>&action=login-frontend" target="_blank" class="button-link">Cart Log In &#8599;</a>
											<?php }
											if (in_array($_SESSION['perms']['admin_groups_id'], [1, 20, 30, 31])) {
												$crm_link = $customer->get_crm_link();
												if (!empty($crm_link)) { ?>
											<br>
											<a href="<?= $crm_link; ?>" target="_blank" class="button-link" style="margin-top:3px;">CRM Company &#8599;</a>
												<?php }
											} ?>
										</td>
										<td class="main">
											<strong>Customer Address:</strong><br>
											<hr>
											<?= tep_address_format($sales_order->get_header('customers_address_format_id'), $sales_order->get_address('legacy'), 1, '', '<br>'); ?>
										</td>
									</tr>
									<tr>
										<td colspan="2"><hr></td>
									</tr>
									<tr>
										<td class="main"><strong>Telephone Number:</strong></td>
										<td class="main"><?= $customer->get_header('telephone'); ?></td>
									</tr>
									<tr>
										<td class="main"><strong>FAX #:</strong></td>
										<td class="main"><?= $customer->get_header('fax'); ?></td>
									</tr>
									<tr>
										<td class="main"><strong>Email Address:</strong></td>
										<td class="main"><a href="mailto:<?= $customer->get_header('email_address'); ?>"><u><?= $customer->get_header('email_address'); ?></u></a></td>
									</tr>
									<tr>
										<td class="main"><strong>Dealer:</strong></td>
										<td class="main"><?= $customer->is('dealer')?'Yes':'No'; ?></td>
									</tr>
									<tr>
										<td class="main"><strong>Customer Segment:</strong></td>
										<td class="main"><?= ucwords($customer->get_header('segment')); ?></td>
									</tr>
									<tr>
										<td class="main"><strong>Customer Account Manager:</strong></td>
										<td class="main"><?= $customer->has_account_manager()?$customer->get_account_manager()->get_name():''; ?></td>
									</tr>
									<tr>
										<td class="main"><strong>Customer Sales Team:</strong></td>
										<td class="main"><?= $customer->has_sales_team()?$customer->get_sales_team()->get_header('label'):''; ?></td>
									</tr>
									<tr>
										<td class="main"><strong>Total Orders:</strong></td>
										<td class="main"><?= $customer->get_order_count(); ?> <a href="/admin/orders_new.php?customers_id=<?= $customer->id(); ?>">[show orders]</a></td>
									</tr>

									<tr>
										<td class="main" colspan="2"><hr></td>
									</tr>

									<tr>
										<td class="main"><strong>Order Representative of Record:</strong></td>
										<td class="main">
											<span id="assigned-account-manager"><?= $sales_order->has_account_manager()?$sales_order->get_account_manager()->get_name():'None'; ?></span>
											<?php /*if ($user->is_top_admin() || (!$sales_order->has_account_manager() && $user->has_sales_team()) || ($sales_order->has_account_manager() && $sales_order->get_account_manager()->id() == $user->id())) {*/
											if ($user->is_top_admin() || $user->has_sales_team()) { ?>
											<button class="assign-account-manager">Assign Rep</button>
											<select class="account-manager-select">
												<option value="">None</option>
												<?php foreach ($account_managers as $account_manager) { ?>
												<option value="<?= $account_manager->id(); ?>" <?= $account_manager->id()==$sales_order->get_header('orders_sales_rep_id')?'selected':''; ?>><?= $account_manager->get_normalized_name(); ?></option>
												<?php } ?>
											</select>
											<button class="account-manager-select">Submit</button>
											<script>
												jQuery('.assign-account-manager').click(function() {
													jQuery('.account-manager-select').show();
													jQuery(this).hide();
												});
												jQuery('button.account-manager-select').click(function() {
													var admin_id = jQuery('select.account-manager-select').val();

													jQuery.ajax({
														url: '/admin/orders_new.php',
														method: 'post',
														dataType: 'json',
														data: { action: 'assign-account-manager', ajax: 1, orders_id: jQuery('#orders_id').val(), admin_id: admin_id },
														success: function(data) {
															if (data.success) window.location.reload();
															else if (data.error) alert(data.error);
															else alert('There was a problem changing assigned order rep.');

														}
													});
												});
											</script>
											<?php } ?>
										</td>
									</tr>
									<tr>
										<td class="main"><strong>Order Sales Team of Record:</strong></td>
										<td class="main">
											<span id="assigned-sales-team"><?= $sales_order->has_sales_team()?$sales_order->get_sales_team()->get_header('label'):'None'; ?></span>
											<?php /*if (!$sales_order->has_account_manager() && ($user->is_top_admin() || (!$sales_order->has_sales_team() && $user->has_sales_team()) || ($sales_order->has_sales_team() && $user->has_sales_team() && $sales_order->get_sales_team()->id() == $user->get_sales_team()['team']->id()))) {*/
											if (!$sales_order->has_account_manager() && ($user->is_top_admin() || $user->has_sales_team())) { ?>
											<button class="assign-sales-team">Assign Sales Team</button>
											<select class="sales-team-select">
												<option value="">None</option>
												<?php foreach ($sales_teams as $sales_team) { ?>
												<option value="<?= $sales_team->id(); ?>" <?= $sales_team->id()==$sales_order->get_header('sales_team_id')?'selected':''; ?>><?= $sales_team->get_header('label'); ?></option>
												<?php } ?>
											</select>
											<button class="sales-team-select">Submit</button>
											<script>
												jQuery('.assign-sales-team').click(function() {
													jQuery('.sales-team-select').show();
													jQuery(this).hide();
												});
												jQuery('button.sales-team-select').click(function() {
													var sales_team_id = jQuery('select.sales-team-select').val();

													jQuery.ajax({
														url: '/admin/orders_new.php',
														method: 'post',
														dataType: 'json',
														data: { action: 'assign-sales-team', ajax: 1, orders_id: jQuery('#orders_id').val(), sales_team_id: sales_team_id },
														success: function(data) {
															if (data.success) window.location.reload();
															else if (data.error) alert(data.error);
															else alert('There was a problem changing assigned sales team.');
														}
													});
												});
											</script>
											<?php } ?>
										</td>
									</tr>

									<?php $prime_contact = $sales_order->get_prime_contact(); ?>
									<tr>
										<td class="main"><strong>Order Placed By:</strong></td>
										<td class="main"><?= $prime_contact['fullname']; ?> (<?= $prime_contact['email']; ?>)</td>
									</tr>

									<?php if ($sales_order->has('channel')) { ?>
									<tr>
										<td class="main"><strong>Channel:</strong></td>
										<td class="main">
											<?= ucwords($sales_order->get_header('channel')); ?>
											<?php if ($sales_order->has_service_rep()) {
												$service_rep = $sales_order->get_service_rep(); ?>
											(Entered By: <a href="mailto:<?= $service_rep->get_header('email_address'); ?>"><?= $service_rep->get_name(); ?> &lt;<?= $service_rep->get_header('email_address'); ?>&gt;</a>)
											<?php }
											elseif ($sales_order->get_header('channel') == 'phone') { ?>
											The Ghost of CK Past
											<?php } ?>
										</td>
									</tr>
									<?php } ?>
									<tr>
										<td class="main"><strong>Source / Medium:</strong></td>
										<td class="main"><?= $sales_order->has('source')?$sales_order->get_header('source'):'---'; ?> / <?= $sales_order->has('medium')?$sales_order->get_header('medium'):'---'; ?></td>
									</tr>
									<tr>
										<td class="main"><strong>Campaign:</strong></td>
										<td class="main"><?= $sales_order->get_header('campaign'); ?></td>
									</tr>
									<tr>
										<td class="main"><strong>Adgroup:</strong></td>
										<td class="main"><?= $sales_order->get_header('content'); ?></td>
									</tr>
									<?php if ($customer->has('aim_screenname')) { ?>
									<tr>
										<td class="main"><strong>AIM:</strong></td>
										<td class="main"><?= $customer->get_header('aim_screenname'); ?></td>
									</tr>
									<?php }

									if ($customer->has('msn_screenname')) { ?>
									<tr>
										<td class="main"><strong>MSN:</strong></td>
										<td class="main"><?= $customer->get_header('msn_screenname'); ?></td>
									</tr>
									<?php }

									if ($customer->has('company_account_contact_name')) { ?>
									<tr>
										<td class="main"><strong>Account Contact:</strong></td>
										<td class="main"><?= $customer->get_header('company_account_contact_name'); ?></td>
									</tr>
									<?php }

									if ($customer->has('company_account_contact_email')) { ?>
									<tr>
										<td class="main"><strong>Account Email:</strong></td>
										<td class="main"><?= $customer->get_header('company_account_contact_email'); ?></td>
									</tr>
									<?php }

									if ($customer->has('company_account_contact_phone_number')) { ?>
									<tr>
										<td class="main"><strong>Account Phone:</strong></td>
										<td class="main"><?= $customer->get_header('company_account_contact_phone_number'); ?></td>
									</tr>
									<?php } ?>
								</table>
							</td>
							<td valign="top" class="order-shipping-block">
								<table border="0" cellspacing="0" cellpadding="2">
									<tr>
										<td class="main order-address" colspan="2">
											<strong>Shipping Address:</strong><br>
											<hr>
											<?php $ad = new address_check(); ?>
											<div id="addrbookDiv"></div>
											<?= $ad->disp_ship_address(array_merge($sales_order->get_shipping_address('legacy'), ['orders_id' => $sales_order->id()])); ?>
										</td>
									</tr>
									<?php if ($sales_order->is('dropship')) { ?>
									<tr>
										<td colspan="2">&nbsp;</td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td class="main" valign="top"><font color="#ff0000"><strong>Blind Shipment</strong></font></td>
									</tr>
									<?php }

									if ($sales_order->is('packing_slip')) { ?>
									<tr>
										<td colspan="2">&nbsp;</td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td class="main" valign="top"><font color="#ff0000"><strong>Include Packing Slip</strong></font></td>
									</tr>
									<?php }

									if ($sales_order->has('purchase_order_number')) { ?>
									<tr>
										<td colspan="2">&nbsp;</td>
									</tr>
									<tr>
										<td class="main"><strong>PO/Reference Number:</strong></td>
										<td class="main" valign="top"><?= $sales_order->get_header('purchase_order_number'); ?></td>
									</tr>
									<?php }

									if ($sales_order->is_shipping_on_account()) { ?>
									<tr>
										<td colspan="2">&nbsp;</td>
									</tr>
										<?php if ($sales_order->get_shipping_method('carrier') == 'FedEx') { ?>
									<tr>
										<td class="main"><strong>Bill Customer Account:</strong></td>
										<td class="main" valign="top">Fedex: <?= $sales_order->get_header('customers_fedex'); ?></td>
									</tr>
										<?php }
										elseif ($sales_order->get_shipping_method('carrier') == 'UPS') { ?>
									<tr>
										<td class="main"><strong>Bill Customer Account:</strong></td>
										<td class="main" valign="top">UPS: <?= $sales_order->get_header('customers_ups'); ?></td>
									</tr>
										<?php }
									} ?>
								</table>
							</td>
							<td valign="top" class="order-billing-block">
								<table border="0" cellspacing="0" cellpadding="2">
									<tr>
										<td class="main order-address" colspan="2">
											<strong>Billing Address:</strong><br>
											<hr>
											<?= tep_address_format($sales_order->get_header('billing_address_format_id'), $sales_order->get_billing_address('legacy'), 1, '', '<br>'); ?>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>
				<div class="sep">
					<div style="float:left; padding-right:100px;">
						<table id="payment-info" border="0" cellspacing="0" cellpadding="2">
							<tr>
								<td class="main bold" style="color:#FF0000;">Master Order Notes:</td>
								<td class="main" style="background-color:#FFDDE0;">&nbsp;<?= $customer->get_header('notes'); ?></td>
							</tr>
							<tr><td height="3">&nbsp;</td></tr>
							<tr>
								<td class="main bold" style="color:#FF0000;">Customer Notes:</td>
								<td class="main" style="background-color:#FFDDE0;">&nbsp;<?= $customer->get_header('sales_rep_notes'); ?></td>
							</tr>
							<?php if ($customer->has_credit()) { ?>
							<tr><td height="3">&nbsp;</td></tr>
							<tr>
								<td class="main bold">Credit Status:</td>
								<td class="main">
									<?php if ($customer->cannot_place_any_order()) { ?>
									<strong style="color:#f00;">HOLD ALL ORDERS!</strong>
									<?php }
									elseif ($customer->get_remaining_credit() < 0) { ?>
									<strong style="color:#f00;">Exceeded Credit Limit</strong>
									<?php }
									elseif (!$customer->can_place_credit_order()) { ?>
									<strong style="color:#f00;">Prepaid Only</strong>
									<?php }
									else { ?>
									Normal
									<?php } ?>
								</td>
							</tr>
							<?php } ?>
							<tr><td height="3">&nbsp;</td></tr>
							<tr>
								<td class="main bold" style="color:#FF0000;"><?php insert_accounting_notes_manager($customer->id(), $sales_order->id()); ?></td>
							</tr>
							<tr><td height="3">&nbsp;</td></tr>
							<tr>
								<td class="main bold">Payment Method:</td>
								<td class="main">
									<?php if ($sales_order->is_cc()) { ?>
									<?= $sales_order->get_header('payment_method_label'); ?>
									<a id="<?= $sales_order->id(); ?>" class="toggle-cc-details" href="#">[show/hide details]</a>
									<img id="cc-info-throbber" style="display:none;" src="images/icons/throbber.gif" alt="Working">
										<?php if ($sales_order->is_open()) { ?>
									<a href="#" id="change-cc">[enter new charge]</a>
										<?php }
									}
									else echo $sales_order->get_header('payment_method_label'); ?>
								</td>
							</tr>
						</table>
					</div>
					<div style="float:left; padding:5px; width:300px; border:1px solid #cccccc;">
						<table cellpadding="2" cellspacing="0" border="0">
							<tr>
								<td class="main"><br> <strong>Invoices</strong> </td>
							</tr>
							<tr>
								<td>
									<table>
										<?php $invoice = prepared_query::fetch('SELECT * FROM acc_invoices WHERE inv_order_id = :orders_id GROUP BY invoice_id ORDER BY invoice_id', cardinality::SET, [':orders_id' => $sales_order->id()]);
										$invoice_counter = 0;
										$credited_invoices = [];
										$invoices = [];
										foreach ($invoice as $invoice_link) {
											if ($invoice_link['credit_memo'] == 1) {
												$invoice_counter--;
												$invoice_link['inv_title'] = 'Credit Memo';
												$invoice_link['inv_notes'] = '<i><font style="font-size:9px;">CM for Inv# '.$invoice_link['original_invoice'].'</font></i>';
												$credited_invoices[] = $invoice_link['original_invoice'];
											}
											else {
												$invoice_counter++;
												$invoice_link['inv_title'] = 'Invoice #';
												$invoice_link['inv_notes'] = '';
											}
											$invoices[] = $invoice_link;
										}

										foreach ($invoices as $idx => $invoice_link) { ?>
										<tr>
											<td width="65"><a href= "/admin/invoice.php?oID=<?= $sales_order->id(); ?>&invId=<?= $invoice_link['invoice_id']; ?>" target="_blank"><?= $invoice_link['inv_title']; ?></a></td>
											<td><a href= "/admin/invoice.php?oID=<?= $sales_order->id(); ?>&invId=<?= $invoice_link['invoice_id']; ?>" target="_blank"><?= $invoice_link['invoice_id']; ?></a></td>
											<td>
												<?php if (!empty($invoice_link['inv_notes'])) { ?>
												<a href= "/admin/invoice.php?oID=<?= $sales_order->id(); ?>&invId=<?= $invoice_link['invoice_id']; ?>" target="_blank"><?= $invoice_link['inv_notes']; ?></a>
												<?php }
												elseif ($idx > 0 && $invoice_link['credit_memo'] != 1 && $invoice_counter > 1 && !in_array($invoice_link['invoice_id'], $credited_invoices) && in_array($_SESSION['perms']['admin_groups_id'], [1, 11])) { ?>
												<a href="#<?= $invoice_link['invoice_id']; ?>" class="cancel-invoice">[CANCEL?]</a>
												<?php } ?>
											</td>
											<td>
												<form action="/admin/orders_new.php?action=email-invoice" method="post"
												      onsubmit="return confirm('Are you sure you want to email this invoice to all the contacts on this order?');"
												>
													<input type="hidden" name="oID" value="<?= $sales_order->id(); ?>">
													<input type="hidden" name="invoice_id" value="<?= $invoice_link['invoice_id']; ?>">
													<input type="hidden" name="action" value="email-invoice">
													<button type="submit" id="email-invoice" style="padding:5px; box-shadow:none; border:.5px solid grey; border-radius:5px; font-size:12px; cursor:pointer;" title="Email Invoice to Order Contacts">
														Email
													</button>
												</form>
											</td>
										</tr>
										<?php } ?>
										<script>
											jQuery('.cancel-invoice').click(function(event) {
												event.preventDefault();
												invoice_number = jQuery(this).attr('href').replace(/#/, '');
												if (confirm('If you want to enter a credit invoice to cancel invoice # '+invoice_number+', hit OK')) {
													jQuery.ajax({
														url: '/admin/orders_new.php?action=cancel-invoice',
														type: 'POST',
														dataType: 'text',
														data: {invoice_id: invoice_number},
														timeout: 10000,
														success: function(data) {
															if (data == '1') window.location.reload(true);
															else alert('There was a database error cancelling this invoice, please contact support.');
														},
														error: function() {
															alert('There was a communication error. Please wait at least 1 minute and reload the screen to see if it went through, and try again if necessary.');
														}
													});
												}
												return false;
											});
										</script>
									</table>
								</td>
							</tr>
						</table>
					</div>
					<div style="clear:both;"></div>
					<table>
						<?php if (in_array($_SESSION['perms']['admin_groups_id'], [1, 11, 19, 20, 29, 31]) && $sales_order->is_open()) { ?>
						<tr>
							<td class="main" colspan="2">
								<select id="payment-method-id">
									<?php $payment_methods = prepared_query::fetch('SELECT id as payment_method_id, code as payment_method_code, label as payment_method_label FROM payment_method WHERE legacy = 0 ORDER BY label ASC');
									foreach ($payment_methods as $payment_method) { ?>
									<option value="<?= $payment_method['payment_method_id']; ?>"><?= $payment_method['payment_method_label']; ?></option>
									<?php } ?>
								</select>
								<input id="add-payment-method" type="button" value="Add">
							</td>
						</tr>
						<?php }

						if ($sales_order->has_payments()) {
							foreach ($sales_order->get_payments() as $payment) { ?>
						<tr class="main bold">
							<td><?= $payment['payment_method_label']; ?>:</td>
							<td style="text-align: right;"><?= CK\text::monetize($payment['pmt_applied_amount']); ?></td>
							<?php if (in_array($_SESSION['perms']['admin_groups_id'], [1, 11, 19, 20, 29, 31]) && $sales_order->is_open()) { ?>
							<td><a href="#" class="remove-payment-allocation" id="<?= $payment['pmt_applied_id']; ?>">Remove</a></td>
							<?php } ?>
						</tr>
							<?php } ?>
						<tr class="main bold">
							<td style="border-top: 1px solid black; text-align: right;">Total Payments Allocated:</td>
							<td style="text-align: right; border-top: 1px solid black;"><?= CK\text::monetize($sales_order->get_allocated_total()); ?></td>
						</tr>
						<?php } ?>

						<?php if ($sales_order->is_shipped() && $sales_order->has_invoices()) {
							$invoice = $sales_order->get_latest_invoice('instance');
							foreach ($invoice->get_payments() as $payment) { ?>
						<tr class="main bold">
							<td style="background-color:#ccd;"><?= $payment['payment_method_label']; ?>:</td>
							<td style="text-align: right;background-color:#ccd;"><?= CK\text::monetize($payment['credit_amount']); ?></td>
						</tr>
							<?php } ?>
						<tr class="main bold">
							<td style="border-top: 1px solid black; text-align: right;background-color:#ccd;">Total Payments Allocated to Invoice:</td>
							<td style="text-align: right; border-top: 1px solid black;background-color:#ccd;"><?= CK\text::monetize($invoice->get_allocated_total()); ?></td>
						</tr>
						<?php } ?>
					</table>
					<div id="cc-details" style="display:none; padding: 5px;"></div>
					<table>
						<?php if ($sales_order->is('legacy_order')) {
							// if legacy order show below
							if ($sales_order->is_pp()) {
								$paypal_record = prepared_query::fetch('SELECT payment_status, txn_id, date_added, payment_date, mc_currency, mc_gross, mc_fee FROM paypal WHERE paypal_id = :payment_id', cardinality::ROW, [':payment_id' => $sales_order->get_header('payment_id')]);
								$paypal_history = NULL;
								if (!empty($paypal_record['txn_id'])) $paypal_history = prepared_query::fetch('SELECT txn_id, payment_status, mc_gross, mc_fee, mc_currency, date_added, payment_date FROM paypal WHERE parent_txn_id = :txn_id ORDER BY date_added DESC', cardinality::SET, [':txn_id' => $paypal_record['txn_id']]); ?>
						<tr>
							<td colspan="2">
								<table border="0" width="100%" cellspacing="0" cellpadding="2">
									<tr valign="top">
										<td colspan="2" style="padding-bottom:0px;">Legacy PayPal Transaction Details</td>
									</tr>
									<tr valign="top">
										<td class="main">
											<style type="text/css">
												.Txns { font-family:Verdana; font-size:10px; color:#000000; background-color:#aaaaaa; }
												.Txns td { padding:2px 4px; }
												.TxnsTitle td { color:#000000; font-weight:bold; font-size:13px; }
												.TxnsSTitle td { background-color:#ccddee; color:#000000; font-weight:bold; }
											</style>
											<script>
												function openWindow(url, name, args) {
													if (url == null || url == '') exit;
													if (name == null || name == '') name = 'popupWin';
													if (args == null || args == '') args = 'toolbar,status,scrollbars,resizable,width=640,height=480,left=50,top=50';
													popupWin = window.open(url, name, args);
													popupWin.focus();
												}
											</script>
											<table cellspacing="1" cellpadding="1" border="0" class="Txns">
												<tr>
													<td colspan="7" bgcolor="#EEEEEE">&nbsp;<strong>Transaction Activity</strong></td>
												</tr>
												<tr class="TxnsSTitle">
													<td nowrap>&nbsp;Date&nbsp;</td>
													<td nowrap>&nbsp;Status&nbsp;</td>
													<td nowrap>&nbsp;Details&nbsp;</td>
													<td nowrap>&nbsp;Action&nbsp;</td>
													<td nowrap align="right">&nbsp;Gross&nbsp;</td>
													<td nowrap align="right">&nbsp;Fee&nbsp;</td>
													<td nowrap align="right">&nbsp;Net Amount&nbsp;</td>
												</tr>
												<?php if (!empty($paypal_record['txn_id'])) {
													if (!empty($paypal_history)) {
														$phCount = 1;
														foreach ($paypal_history as $pp_hist) {
															$dt = new DateTime($pp_hist['payment_date']);
															$phColor = (($phCount/2) == floor($phCount/2))?'#FFFFFF':'#EEEEEE'; ?>
												<tr bgcolor="<?= $phColor; ?>">
													<td nowrap>&nbsp;<?= $dt->format('M. d, Y'); ?>&nbsp;</td>
													<td nowrap>&nbsp;<?= $pp_hist['payment_status']; ?>&nbsp;</td>
													<td nowrap>&nbsp;<?= $pp_hist['txn_id']; ?>&nbsp;</td>
													<td nowrap>&nbsp;</td>
													<td nowrap align="right">&nbsp;<?= CK\text::monetize($pp_hist['mc_gross']); ?>&nbsp;</td>
													<td nowrap align="right">&nbsp;<?= CK\text::monetize($pp_hist['mc_fee']); ?>&nbsp;</td>
													<td nowrap align="right">&nbsp;<?= CK\text::monetize($pp_hist['mc_gross']-$paypal_history['mc_fee']); ?>&nbsp;</td>
												</tr>
															<?php $phCount++;
														}
													}
													
													$dt = new DateTime($paypal_record['payment_date']); ?>
												<tr bgcolor="#FFFFFF">
													<td nowrap>&nbsp;<?= $dt->format('M. d, Y'); ?>&nbsp;</td>
													<td nowrap>&nbsp;<?= $paypal_record['payment_status']; ?>&nbsp;</td>
													<td nowrap>&nbsp;<?= $paypal_record['txn_id']; ?>&nbsp;</td>
													<td nowrap>&nbsp;&nbsp;</td>
													<td align="right" nowrap>&nbsp;<?= CK\text::monetize($paypal_record['mc_gross']); ?>&nbsp;</td>
													<td align="right" nowrap>&nbsp;<?= CK\text::monetize($paypal_record['mc_fee']); ?>&nbsp;</td>
													<td align="right" nowrap>&nbsp;<?= CK\text::monetize($paypal_record['mc_gross']-$paypal_record['mc_fee']); ?>&nbsp;</td>
												</tr>
												<?php }
												else { ?>
												<tr bgcolor="#FFFFFF">
													<td colspan="7" nowrap>&nbsp;<?= sprintf('No PayPal Transaction Information Available (%s)', prepared_query::fetch('SELECT txn_signature FROM orders_session_info WHERE orders_id = :orders_id', cardinality::SINGLE, [':orders_id' => $sales_order->id()])); ?>&nbsp;</td>
												</tr>
												<?php } ?>
											</table>
										</td>
										<td></td>
									</tr>
								</table>
							</td>
						</tr>
							<?php }
						}
						else {
							if ($sales_order->is_pp()) { ?>
						<tr>
							<td></td>
							<td>
								<style>
									.Txns { font-family:Verdana; font-size:10px; color:#000000; background-color:#aaaaaa; }
									.Txns td { padding:2px 4px; }
									.TxnsTitle td { color:#000000; font-weight:bold; font-size:13px; }
									.TxnsSTitle td { background-color:#ccddee; color:#000000; font-weight:bold; }
								</style>
								<table border="0" width="100%" cellspacing="0" cellpadding="2">
									<tr valign="top">
										<td colspan="2" style="padding-bottom:0px;"><img src="/images/static/img/paypal_logo.gif" border="0" alt="PayPal" title=" PayPal "></td>
									</tr>
									<tr valign="top">
										<td class="main">
											<table cellspacing="1" cellpadding="1" border="0" class="Txns">
												<tr>
													<td colspan="7" bgcolor="#EEEEEE">&nbsp;<strong>Transaction Activity</strong></td>
												</tr>
												<tr class="TxnsSTitle">
													<td nowrap>&nbsp;Date&nbsp;</td>
													<td nowrap>&nbsp;Payment Status&nbsp;</td>
													<td nowrap>&nbsp;Details&nbsp;</td>
													<td nowrap>&nbsp;Action&nbsp;</td>
													<td nowrap align="right">&nbsp;Gross&nbsp;</td>
													<td nowrap align="right">&nbsp;Fee&nbsp;</td>
													<td nowrap align="right">&nbsp;Net Amount&nbsp;</td>
												</tr>

												<?php $paymentSvcApi = new PaymentSvcApi();

												$paymentSvcid = $sales_order->get_payment_service_id();
												$res = $paymentSvcApi->getTransactionDetails($paymentSvcid);
												$transaction = json_decode($res, true);

												if (!empty($transaction['result'])) {
													//var_dump($transaction['result']);
													$created_date = new DateTime($transaction['result']['created_at']['date']); ?>
												<tr bgcolor="#FFFFFF">
													<td nowrap>&nbsp;<?= $created_date->format('m-d-Y'); ?>&nbsp;</td>
													<td nowrap>&nbsp;<?= $transaction['result']['status']; ?>&nbsp;</td>
													<td nowrap><?= $paymentSvcid; ?></td>
													<td nowrap>&nbsp;&nbsp;</td>
													<td align="right" nowrap>&nbsp;<?= CK\text::monetize($transaction['result']['amount']); ?>&nbsp;</td>
													<td align="right" nowrap>&nbsp;<?= CK\text::monetize($transaction['result']['service_fee']); ?>&nbsp;</td>
													<td align="right" nowrap>&nbsp;<?= CK\text::monetize($transaction['result']['amount']-$transaction['result']['service_fee']); ?>&nbsp;</td>
												</tr>
												<?php } ?>
											</table>
										</td>
										<td></td>
									</tr>
								</table>
								<script>
									function openWindow(url,name,args) {
										if (url == null || url == '') exit;
										if (name == null || name == '') name = 'popupWin';
										if (args == null || args == '') args = 'toolbar,status,scrollbars,resizable,width=640,height=480,left=50,top=50';
										popupWin = window.open(url, name, args);
										popupWin.focus();
									}
								</script>
							</td>
						</tr>
							<?php }
						} ?>
						<tr>
							<?php if ($sales_order->has_fraud_score()) { ?>
							<td class="main"><strong>Fraud Score:</strong></td>
							<td class="main">
								<strong><font color="red"><?= (int) $sales_order->get_fraud_score('score'); ?></font> <?= $sales_order->get_fraud_risk(); ?></strong>
								<a id="maxMindLink" href="#">[show/hide]</a>
							</td>
							<?php }
							else { ?>
							<td class="main"><strong>Fraud Score</strong></td><td class="main"><strong><font color="red">No Fraud Check Data Found</font></strong></td>
							<?php } ?>
						</tr>
					</table>

					<?php if ($sales_order->has_fraud_score()) {
						$fraud_score = $sales_order->get_fraud_score(); ?>
					<div id="maxmind-info" style="display:none; padding: 5px;">
						<table width="100%" cellpadding="2" cellspacing="0" border="0">
							<tr class="dataTableRow">
								<td width="14%" class="dataTableContent">Country Match:</td>
								<td width="18%" class="dataTableContent"><strong><?= $fraud_score['country_match']; ?></strong></td>
								<td width="14%" class="dataTableContent">Country Code:</td>
								<td width="18%" class="dataTableContent"><strong><?= $fraud_score['country_code']; ?></strong></td>
								<td width="14%" class="dataTableContent">High Risk Country:</td>
								<td width="18%" class="dataTableContent"><strong><?= $fraud_score['hi_risk']; ?></strong></td>
							</tr>
							<tr>
								<td width="14%" class="dataTableContent">Bin Match:</td>
								<td width="18%" class="dataTableContent"><strong><?= $fraud_score['bin_match']; ?></strong></td>
								<td width="14%" class="dataTableContent">Bin Country:</td>
								<td width="18%" class="dataTableContent"><strong><?= $fraud_score['bin_country']; ?></strong></td>
								<td width="14%" class="dataTableContent">Bin Name:</td>
								<td width="18%" class="dataTableContent"><strong><?= $fraud_score['bin_name']; ?></strong></td>
							</tr>
							<tr class="dataTableRow">
								<td width="14%" class="dataTableContent">ISP:</td>
								<td width="18%" class="dataTableContent"><strong><?= $fraud_score['ip_isp']; ?></strong></td>
								<td width="14%" class="dataTableContent">ISP Org:</td>
								<td width="18%" class="dataTableContent"><strong><?= $fraud_score['ip_org']; ?></strong></td>
								<td width="14%" class="dataTableContent">Distance:</td>
								<td width="18%" class="dataTableContent"><strong><?= $fraud_score['distance']; ?></strong></td>
							</tr>
							<tr>
								<td width="14%" class="dataTableContent">Anonymous Proxy:</td>
								<td width="18%" class="dataTableContent"><strong><?= $fraud_score['anonymous_proxy']; ?></strong></td>
								<td width="14%" class="dataTableContent">Proxy Score:</td>
								<td width="18%" class="dataTableContent"><strong><?= $fraud_score['proxy_score']; ?></strong></td>
								<td width="14%" class="dataTableContent">Spam Score:</td>
								<td width="18%" class="dataTableContent"><strong><?= $fraud_score['spam_score']; ?></strong></td>
							</tr>
							<tr class="dataTableRow">
								<td width="14%" class="dataTableContent">Free Email:</td>
								<td width="18%" class="dataTableContent"><strong><?= $fraud_score['free_mail']; ?></strong></td>
								<td width="14%" class="dataTableContent">Phone Match:</td>
								<td width="18%" class="dataTableContent"><a href="http://www.whitepages.com/search/Reverse_Phone?phone=<?= $customer->get_header('telephone'); ?>" target="_blank"><strong><?= $fraud_score['cust_phone']; ?></strong></td>
								<td width="14%" class="dataTableContent">Error:</td>
								<td width="18%" class="dataTableContent"><strong><?= $fraud_score['err']; ?></strong></td>
							</tr>
							<tr>
								<td width="14%" class="dataTableContent">*City: <strong><?= $fraud_score['ip_city']; ?></strong></td>
								<td width="18%" class="dataTableContent">*Region: <strong><?= $fraud_score['ip_region']; ?></strong></td>
								<td width="14%" class="dataTableContent">*Latitude: <strong><?= $fraud_score['ip_latitude']; ?></strong></td>
								<td width="18%" class="dataTableContent">*Longitude: <strong><?= $fraud_score['ip_longitude']; ?></strong></td>
								<td></td>
								<td></td>
							</tr>
						</table>
					</div>
					<?php } ?>

					<table width="600" cellpadding="2" cellspacing="0" border="0">
						<?php if ($sales_order->has_terms_po_number()) { ?>
						<tr>
							<td class="main"><strong>NET <?= $sales_order->get_terms(); ?> PO:</strong></td>
							<td class="main"><?= $sales_order->get_terms_po_number(); ?></td>
						</tr>
						<?php }

						if ($sales_order->has('amazon_order_number')) { ?>
						<tr>
							<td class="main"><strong>Amazon Order Number:</strong></td>
							<td class="main"><?= $sales_order->get_header('amazon_order_number'); ?></td>
						</tr>
						<?php }

						if ($sales_order->has('ebay_order_id')) { ?>
						<tr>
							<td class="main"><strong>eBay Order Number:</strong></td>
							<td class="main"><?= $sales_order->get_header('ebay_order_id'); ?></td>
						</tr>
						<?php }

						if ($sales_order->has('ca_order_id')) { ?>
						<tr>
							<td class="main"><strong>Channel Advisor Order:</strong></td>
							<td class="main"><?= $sales_order->get_header('ca_order_id'); ?></td>
						</tr>
						<?php } ?>
						<tr>
							<td><?= tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
						</tr>
						<?php if ($sales_order->is_shipping_on_account()) { ?>
						<tr>
							<td class="main"><font color="#ff0000"><strong>Shipping Account:</strong></font></td>
							<td class="main"><font color="#ff0000"><strong>YES</strong></font></td>
						</tr>
						<tr>
							<td class="main">FEDEX Account Number:</td>
							<td class="main"><?= $sales_order->get_header('customers_fedex'); ?></td>
						</tr>
						<tr>
							<td class="main">UPS Account Number:</td>
							<td class="main"><?= $sales_order->get_header('customers_ups'); ?></td>
						</tr>
						<?php } ?>
						<tr>
							<td class="main"><strong>Include packing slip:</strong></td>
							<?php if ($sales_order->is('packing_slip')) { ?>
							<td class="main"><strong><u>Yes</u></strong></td>
							<?php }
							else { ?>
							<td class="main">No</td>
							<?php } ?>
						</tr>
						<tr>
							<td><?= tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
						</tr>
						<?php
						if ($sales_order->has('split_order')) { ?>
						<tr>
							<td class="main" style="font-weight: bold;">Customer Requested Order Split:</td><td class="main"><?= $sales_order->get_header('split_order')==2?'No':'Yes'; ?></td>
						</tr>
						<tr>
							<td><?= tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
						</tr>
						<?php }

						if ($sales_order->has('parent_orders_id')) { ?>
						<tr>
							<td class="main" colspan="2" style="font-weight: bold;">This order was split from <a href="orders_new.php?oID=<?= $sales_order->get_header('parent_orders_id'); ?>&action=edit" target="_BLANK">Order #<?= $sales_order->get_header('parent_orders_id'); ?></a>.</td>
						</tr>
						<tr>
							<td><?= tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
						</tr>
						<?php }

						if ($sales_order->has_child_orders()) { ?>
						<tr>
							<td class="main" style="font-weight: bold;">Split Onto Orders:</td>
							<td class="main">
								<?= implode(', ', array_map(function($child_order) {
									return '<a href="/admin/orders_new.php?oID='.$child_order->id().'&action=edit" target="_BLANK">Order #'.$child_order->id().'</a>';
								}, $sales_order->get_child_orders())); ?>
							</td>
						</tr>
						<tr>
							<td><?= tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
						</tr>
						<?php } ?>
						<tr>
							<td class="main">Calculated Order Weight:</td>
							<td class="main"><?= $sales_order->get_estimated_shipped_weight(); ?>&nbsp;(Product weight <?= $sales_order->get_estimated_product_weight(); ?>)</td>
							<?php if ($sales_order->get_estimated_shipped_weight() > 250 || $sales_order->has_freight_products()) { ?>
							<td class="main" colspan="2" style="color: red; font-weight: bold; text-align: center;">
								Needs Loading Dock Info
							</td>
							<?php } ?>
						</tr>

						<tr>
							<td class="main">Weight Customer Charged For:</td>
							<td class="main"><?= number_format($sales_order->get_header('orders_weight'), 1); ?></td>
						</tr>

						<?php if (in_array($sales_order->get_shipping_method('carrier'), ['FedEx', 'UPS']) || in_array($sales_order->get_shipping_method('shipping_code'), [48, 49, 51])) {
							// arrays for signature services
							$signature_type = [];
							$signature_type[0] = 'None Required';
							$signature_type[2] = 'Anyone can sign (res only)';
							$signature_type[3] = 'Signature Required';
							$signature_type[4] = 'Adult Signature';

							$billTypeArr = [];
							$billTypeArr[1] = 'Bill Sender (Prepaid)';
							$billTypeArr[3] = 'Bill Third Party';
							// FedEx/UPS Ground or Ground Int
							if (in_array($sales_order->get_shipping_method('shipping_code'),  [9, 15, 23, 29])) {
								$billTypeArr[2] = '';
								$billTypeArr[5] = 'Bill Recipient';
							}
							else {
								$billTypeArr[2] = 'Bill Recipient';
								$billTypeArr[5] = '';
							}

							if ($sales_order->get_shipping_method('carrier') == 'FedEx') {
								$carrier = 'FedEx';
								$account_num = $sales_order->get_header('fedex_account_number');
							}
							else {
								$carrier = 'UPS';
								$account_num = $sales_order->get_header('ups_account_number');
							} ?>
						<tr>
							<td><?= tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
						</tr>
						<tr>
							<td class="main" colspan="2">
								<strong><?= $carrier; ?> Label Options</strong>
								<a href="javascript:void(0);" id="flo_edit_link" onClick="fedexLabelOptionsEdit();">edit</a>
								<a href="javascript:void(0);" id="flo_save_link" style="display:none;" onClick="fedexLabelOptionsSave();">SAVE</a>
							</td>
						</tr>
						<tr>
							<td class="main">Payment Type:</td>
							<td class="main">
								<span id="flo_payment_type_display"><?= @$billTypeArr[$sales_order->get_header('fedex_bill_type')]; ?></span>
								<select id="flo_payment_type_edit" style="display:none;">
									<?php foreach ($billTypeArr as $id => $text) { ?>
									<option value="<?= $id; ?>" <?= $id==$sales_order->get_header('fedex_bill_type')?'selected':''; ?>><?= $text; ?></option>
									<?php } ?>
								</select>
							</td>
						</tr>
						<tr>
							<td class="main">Account Number</td>
							<td class="main">
								<span id="flo_account_number_display"><?= $account_num; ?></span>
								<input type="text" id="flo_account_number_edit" style="display:none;" value="<?= $account_num; ?>">
							</td>
						</tr>
						<tr>
							<td class="main">Signature Type:</td>
							<td class="main">
								<span id="flo_signature_type_display"><?= $signature_type[$sales_order->get_header('fedex_signature_type')];?></span>
								<select id="flo_signature_type_edit" style="display:none;">
									<?php foreach ($signature_type as $id => $text) { ?>
									<option value="<?= $id; ?>" <?= $id==$sales_order->get_header('fedex_signature_type')?'selected':''; ?>><?= $text; ?></option>
									<?php } ?>
								</select>
							</td>
						</tr>
						<script type="text/javascript">
							var pay_type_array = ['', '<?= $billTypeArr[1]; ?>', '<?= $billTypeArr[2]; ?>', '<?= $billTypeArr[3]; ?>', '', '<?= $billTypeArr[5]; ?>'];
							var sig_type_array =['<?= $signature_type[0]; ?>', '', '<?= $signature_type[2]; ?>', '<?= $signature_type[3]; ?>', '<?= $signature_type[4]; ?>'];

							function fedexLabelOptionsEdit() {
								$('flo_payment_type_display', 'flo_account_number_display', 'flo_signature_type_display', 'flo_edit_link').invoke('hide');
								$('flo_payment_type_edit', 'flo_account_number_edit', 'flo_signature_type_edit', 'flo_save_link').invoke('show');
							}

							function fedexLabelOptionsSave() {
								new Ajax.Request('/admin/new_ship_fedex.php', {
									method: 'get',
									parameters: {
										action: 'ship_label_options',
										oid: '<?= $sales_order->id(); ?>',
										payment_type: $('flo_payment_type_edit').value,
										account_number: $('flo_account_number_edit').value,
										signature_type: $('flo_signature_type_edit').value
									},
									onSuccess: function (transport) {
										$('flo_payment_type_display').innerHTML = pay_type_array[$('flo_payment_type_edit').value];
										$('flo_account_number_display').innerHTML = $('flo_account_number_edit').value;
										$('flo_signature_type_display').innerHTML = sig_type_array[$('flo_signature_type_edit').value];
										$('flo_payment_type_display', 'flo_account_number_display', 'flo_signature_type_display', 'flo_edit_link').invoke('show');
										$('flo_payment_type_edit', 'flo_account_number_edit', 'flo_signature_type_edit', 'flo_save_link').invoke('hide');
									}
								});
							}
						</script>
						<?php } ?>
					</table>
				</div>
				<div style="text-align:right; overflow:auto; padding:10px 0px;">
					<?php /* MMD - JS variable below for use by order.js to determine whether or not to enable 'ship fedex' button */ ?>
					<script type="text/javascript">
						var shippingMethodId = <?= $sales_order->get_shipping_method('shipping_code'); ?>;
					</script>
					<style>
						.specials_excess { text-decoration:underline; color:#15489e; }
					</style>
					<?php if ($sales_order->is_shipped()) {
						if ($sales_order->has_rmas()) { ?>
					<ul style="list-style-type: none; width: 100px; margin: 0; padding: 0;">
						<?php foreach ($sales_order->get_rmas() as $rma) { ?>
						<li style="margin-bottom: 5px;"><a href="/admin/rma-detail.php?id=<?= $rma->id(); ?>" target="_blank">RMA #<?= $rma->id(); ?></a></li>
						<?php } ?>
					</ul>
						<?php } ?>
					<input id="create-rma" type="button" value="Create RMA" disabled="disabled" style="float:left;">
					<?php } ?>

					<div style="float:right; white-space:nowrap;">
						<?php // Show text 'Non fedex' instead of button 'Fedex Shipping' if shipping method is not Fedex or Standard Shipping
						if ($sales_order->get_shipping_method('carrier') == 'FedEx') { ?>
						<input id="new_ship_fedex" type="button" value="Ship FedEx" onclick="return dispNewFedexShip('<?= $sales_order->id(); ?>','<?= count($sales_order->get_packages()); ?>');" <?= !$sales_order->has_packages()?'disabled':''; ?>>
						<?php }
						/*elseif ($sales_order->get_shipping_method('carrier') == 'UPS' || $sales_order->get_shipping_method('method_name') == 'CK Standard Shipping') { ? >
						<button id="ship-ups" type="button" <?= !$sales_order->has_packages()?'disabled':''; ?>>Ship UPS</button>
						<?php }*/
						else { ?>
						<span style="color: #FF0000; font-size: 9px;width: 185px; padding: 5px;">Non Fedex <!-- /UPS --></span>
						<?php }

						if (in_array($_SESSION['perms']['admin_groups_id'], [1, 11, 31]) || in_array($_SESSION['perms']['admin_id'], [152])) {
							if ($sales_order->is('released')) { ?>
								<a href="/admin/orders_new.php?oID=<?= $sales_order->id(); ?>&action=place-accounting-hold"><button>Place Order On Accounting Hold</button></a>
							<?php }
							elseif ($customer->is('fraud') && !$sales_order->is('released')) { ?>
								<button id="can-not-release-fraudulent-customer-order">Remove Order From Accounting Hold</button>
							<?php }
							else { ?>
								<a href="/admin/orders_new.php?oID=<?= $sales_order->id(); ?>&action=release-accounting-hold"><button>Remove Order From Accounting Hold</button></a>
						<?php }
						} ?>

						<input type="button" value="Pick<?= !$sales_order->is('dropship')?'/Pack':''; ?> List" onClick="<?php if ($sales_order->get_header('orders_status') == 2) { ?>jQuery('#current_logical_status').html('Warehouse');<?php } ?> popupWindow('/admin/pack_and_pick_list.php?<?= tep_get_all_get_params(['oID']); ?>oID=<?= $sales_order->id(); ?>', 860);">

						<input type="button" value="Invoice" onClick="javascript:popupWindow('/admin/invoice.php?oID=<?= $sales_order->id(); ?>')">
						<span style="border: 2px solid black; width: 185px; padding: 5px;">
							<input type="button" value="Split order" onClick="window.location='/admin/orders_split.php?order_id=<?= $sales_order->id(); ?>'" <?= $sales_order->is_closed()?'disabled':''; ?>>
							<input type="button" value="Edit order" onClick="window.location='/admin/edit_orders.php?oID=<?= $sales_order->id(); ?>'" <?= $sales_order->is_closed()?'disabled':''; ?>>
							<input type="button" value="Cancel order" onClick="chooseCancelReason();" <?= $sales_order->is_closed()?'disabled':''; ?>>
						</span>
					</div>
				</div>
				<div class="sep">
					<style>
						.order-line-header td { background-color:#c9c9c9; color:#fff; font-weight:bold; font-size:10px; }
						.order-line td { background-color:#f0f1f1; font-size:10px; vertical-align:top; }
						.order-line.serial td .serial-manage { font-size:20px; cursor:pointer; /*transform:scale(0.5, 1); -ms-transform:scale(0.5, 1); -webkit-transform:scale(0.5, 1); -moz-transform:scale(0.5, 1);*/ line-height:20px; vertical-align:middle; font-style:normal; }
						.order-line.serial.unallocated td { background-color:#efcbcb; }
						.order-line.serial.unallocated td .serial-manage { color:#ce0316; }
						.order-line.serial.allocated td { background-color:#d6e8d0; }
						.order-line.serial.allocated td .serial-manage { color:#089c0b; }
						.order-line.included td { color:#777; font-style:italic; }
						.order-line.bundle td { background-color:#e5e5f3; }
						.order-line.insufficient td { color:#c00; background-color:#f3e5e5; }
						.order-line.insufficient td a { color:#c00; }
						.order-line.supplies td { background-color:#777; color:#fff; }
						.order-line.supplies td a { color:#fff; }

						.reserved-serials td { padding:0px 0px 3px 0px; border:1px solid #ccc; }
						.reserved-serials.closed td { cursor:pointer; padding-top:3px; }
						.reserved-serials.closed .serial-reservation { display:none; }
						.reserved-serials .serial-reservation { font-size:12px; padding:5px; }
						h4.reserve-serials { border-bottom:1px solid #aaa; background-color:#eee; margin:0px; padding:3px; font-size:12px; cursor:pointer; }
						.reserved-serials.closed h4.reserve-serials { display:none; }
						.remove-serial-reservation { font-size:10px; }
					</style>
					<table border="0" width="100%" cellspacing="0" cellpadding="2">
						<tr class="order-line-header">
							<?php if ($sales_order->is_shipped()) { ?>
							<td>RMA?</td>
							<td>RMA Count</td>
							<?php } ?>
							<td>S/N</td>
							<td colspan="2">Products</td>
							<td>Model</td>
							<td>IPN</td>
							<td>Salable</td>
							<td>Allocated</td>
							<td>PO Allocated</td>
							<td>Available</td>
							<td>On Order</td>
							<td>Specials Deficit</td>
							<td>Exclude&nbsp;<input type="checkbox" onclick="if (this.checked) { jQuery('.exclude_forecast').attr('checked', 'checked'); jQuery('.exclude_forecast').trigger('change'); } else {jQuery('.exclude_forecast').removeAttr('checked'); jQuery('.exclude_forecast').trigger('change'); }"></td>
							<td align="right">Price each</td>
							<td align="right">Total price</td>
						</tr>
						<?php $rma_qtys = [];
						if ($sales_order->has_rmas()) {
							foreach ($sales_order->get_rmas() as $rma) {
								foreach ($rma->get_consolidated_products() as $orders_products_id => $product) {
									if (empty($rma_qtys[$orders_products_id])) $rma_qtys[$orders_products_id] = 0;
									$rma_qtys[$orders_products_id] += $product['quantity'];
								}
							}
						}

						foreach ($sales_order->get_products() as $product) {
							$rma_qty = !empty($rma_qtys[$product['orders_products_id']])?$rma_qtys[$product['orders_products_id']]:0;

							$inventory = $product['ipn']->get_inventory();

							$classes = [];
							if ($product['option_type'] == ck_cart::$option_types['INCLUDED']) $classes[] = 'included';
							if ($product['ipn']->is('serialized')) {
								$classes[] = 'serial';
								$classes[] = count($product['allocated_serials'])!=$product['quantity']?'unallocated':'allocated';
							}
							if ($product['ipn']->is('is_bundle')) $classes[] = 'bundle';
							if (!$product['is_shippable']) $classes[] = 'insufficient';
							if ($product['ipn']->is_supplies()) $classes[] = 'supplies';
							?>
						<tr class="order-line <?= implode(' ', $classes); ?>" id="table_row_<?= $product['orders_products_id']; ?>">
							<?php if ($sales_order->is_shipped()) { ?>
							<td>
								<input type="checkbox" class="rma-product-id" name="rma_order_products[]" value="<?= $product['orders_products_id']; ?>" <?= $product['quantity']<=$rma_qty?'disabled':''; ?>>
							</td>
							<td style="width: 40px;"><?= $rma_qty; ?></td>
							<?php } ?>

							<td align="right">
								<?php if ($product['ipn']->is('serialized')) { ?>
								<div class="serial-manage" onClick="open_serials_order_dialog(<?= $product['products_id']; ?>, <?= $product['quantity']; ?>, <?= $product['ipn']->id(); ?>, <?= $sales_order->id(); ?>, <?= $product['orders_products_id']; ?>);">&#10148;<!--&#10145;--></div>
								<?php } ?>
							</td>
							<td align="right"><?= $product['quantity']; ?>&nbsp;x</td>
							<td width="35%">
								<?php $expected_ship_date = '';
								if (!empty($product['expected_ship_date'])) {
									$expected_ship_date = $product['expected_ship_date']->format('Y-m-d')=='2099-01-01'?'Site said "CALL"':$product['expected_ship_date']->format('Y-m-d');
								} ?>
								<a href="<?= $product['listing']->get_url(); ?>" target="_blank" title="Customer's Expected Ship Date: <?= $expected_ship_date; ?>"><?= ($product['ipn']->is('is_bundle')?'BUNDLE - ':'').$product['name']; ?> [&#8599;]</a>
								<?php if (!$product['is_shippable']) { ?>
								<br><span style="color: #ff0000;">&nbsp;&nbsp;&nbsp;&nbsp;Customer's Expected Ship Date: <?= $expected_ship_date; ?></span>
								<?php } ?>

								<?= !empty($product['is_quote'])?tep_image('/admin/images/icons/icon_status_green_light.gif', 'Part of quote'):''; ?>

								<?php if (!$product['ipn']->is('is_bundle')) po_alloc_op_markup($product['orders_products_id'], $sales_order->get_header('orders_status')); ?>
							</td>
							<td><?= (new item_popup($product['model'], service_locator::get_db_service(), ['products_id' => $product['products_id']])); ?></td>
							<td><a href="/admin/ipn_editor.php?ipnId=<?= urlencode($product['ipn']->get_header('ipn')); ?>" target="_blank"><?= $product['ipn']->get_header('ipn'); ?></a></td>
							<td><?= !$product['ipn']->is('is_bundle')?$inventory['salable']:''; ?></td>
							<td><?= $inventory['allocated']; ?></td>
							<td><?= !$product['ipn']->is('is_bundle')?$inventory['po_allocated']:''; ?></td>
							<td>
								<?php if (!$product['ipn']->is('is_bundle')) { ?>
								<a href="/admin/ipn_editor.php?ipnId=<?= urlencode($product['ipn']->get_header('ipn')); ?>" target="_blank"><?= $inventory['available']; ?></a>
									<?php if ($inventory['available'] == 0) { ?>
								<span title="Customer may have purchased all remaining stock">*</span>
									<?php }
									/*if (!empty($inventory['vendor_stock']['vendor_qty'])) {
										?><span title="This item has stock available at an approved vendor">&dagger;</span><?php
									}*/
								} ?>
							</td>
							<td>
								<?php if (!$product['ipn']->is('is_bundle')) { ?>
								<a href="/admin/ipn_editor.php?ipnId=<?= urlencode($product['ipn']->get_header('ipn')); ?>" target="_blank"><?= $inventory['on_order']; ?></a>
								<?php } ?>
							</td>
							<td align="center" valign="middle" <?= !empty($product['specials_excess'])?'style="background-color:#ccf;" title="The customer purchased more than the qty on sale by ['.abs($product['specials_excess']).'] units"':''; ?>>
								<?= !empty($product['specials_excess'])?'<a class="specials_excess">'.$product['specials_excess'].'</a>':''; ?>
							</td>
							<td align="center">
								<input type="checkbox" class="exclude_forecast" name="exclude_op[<?= $product['orders_products_id']; ?>]" <?= $product['exclude_forecast']?'checked':''; ?>>
							</td>
							<td align="right"><strong title="CK Unit Revenue: <?= CK\text::monetize($product['revenue']); ?>"><?= CK\text::monetize($product['final_price']); ?></strong></td>
							<td align="right"><strong title="CK Line Revenue: <?= CK\text::monetize($product['revenue'] * $product['quantity']); ?>"><?= CK\text::monetize($product['final_price'] * $product['quantity']); ?></strong></td>
						</tr>
							<?php if ($product['ipn']->is('serialized')) { ?>
						<tr class="reserved-serials closed">
							<td colspan="14">
								<h4 class="reserve-serials">Reserve Serials (prior to allocation)</h4> 
								<div class="serial-reservation">
									<?php if ($sales_order->is_open()) { ?>
									Serial #: <input type="text" name="serial_number" class="serial-lookup" data-stock-id="<?= $product['stock_id']; ?>" data-orders-products-id="<?= $product['orders_products_id']; ?>"><br>
									<?php } ?>
									<div class="reserved-serial-numbers rs-order-line-<?= $product['orders_products_id']; ?>">
										<?php foreach (ck_serial::get_all_claimed_serials_by_orders_products_id($product['orders_products_id']) as $serial) {
											// if it's allocated, skip it
											if ($serial->get_header('status_code') == ck_serial::$statuses['ALLOCATED']) continue; ?>
										<div class="reserved-serial as-<?= $serial->id(); ?>"><?= $serial->get_header('serial_number'); ?> [Reserved By: <?= !empty($serial->get_reservation('admin'))?$serial->get_reservation('admin')->get_name():'SYSTEM'; ?>] <a href="#" class="remove-serial-reservation" data-orders-products-id="<?= $product['orders_products_id']; ?>" data-serial-id="<?= $serial->id(); ?>">[REMOVE]</a></div>
										<?php } ?>
									</div>
								</div>
							</td>
						</tr>
							<?php }
						} ?>
						<tr>
							<?php if ($sales_order->is_shipped()) { ?>
							<td colspan="2"></td>
							<?php } ?>
							<td></td>
							<td align="right" colspan="13">
								<table border="0" cellspacing="0" cellpadding="2">
									<?php foreach ($sales_order->get_totals('consolidated') as $total) { ?>
									<tr>
										<td align="right" class="smallText">
											<?php if ($total['class'] == 'shipping' && !in_array($total['shipping_method_id'], [0, 50])) echo $sales_order->get_shipping_method_display('short');
											else echo $total['title']; ?>
										</td>
										<td align="right" class="smallText"><?= $total['display_value']; ?></td>
									</tr>
									<?php }

									if (!$sales_order->is_shipped()) { ?>
									<tr>
										<td align="right" class="smallText">ESTIMATED PRODUCT MARGIN:</td>
										<td align="right" class="smallText"><?= $sales_order->get_estimated_margin_dollars('product', TRUE); ?> (<?= $sales_order->get_estimated_margin_pct('product', TRUE); ?>)</td>
									</tr>
									<?php }
									else { ?>
									<tr>
										<td align="right" class="smallText">FINAL PRODUCT MARGIN:</td>
										<td align="right" class="smallText"><?= $sales_order->get_final_margin_dollars('product', TRUE); ?> (<?= $sales_order->get_final_margin_pct('product', TRUE); ?>)</td>
									</tr>
									<?php } ?>
								</table>
							</td>
						</tr>
					</table>
					<script src="//cdnjs.cloudflare.com/ajax/libs/mustache.js/3.0.1/mustache.js"></script>
					<script src="/images/static/js/ck-autocomplete.max.js?v=0.26"></script>
					<script>
						jQuery('.reserved-serials').click(function() {
							jQuery(this).toggleClass('closed');
						});
						jQuery('.reserved-serials .serial-reservation').click(function(e) {
							e.stopPropagation();
						});

						let serial_ac = new ck.autocomplete('/admin/orders_new.php', {
							$fields: [jQuery('.serial-lookup')],
							minimum_length: 0,
							autocomplete_action: 'serial-reservation-lookup',
							auto_select_single: true,
							request_onclick: true,
							results_template: '<table class="autocomplete-results-table"><tbody>{{#results}}<tr class="table-entry" id="{{result_id}}"><td>{{serial_number}}</td><td>{{owner}}</td><td>{{status}}</td><td>{{notes}}</td></tr>{{/results}}</tbody></table>',
							process_additional_fields: function(data) {
								data.stock_id = jQuery(this).attr('data-stock-id');
								data.orders_products_id = jQuery(this).attr('data-orders-products-id');
								return data;
							},
							select_result: function(result) {
								jQuery('.serial-lookup').val('');

								jQuery.ajax({
									url: '/admin/orders_new.php',
									dataType: 'json',
									data: {
										action: 'reserve-serial',
										ajax: 1,
										orders_products_id: result.orders_products_id,
										serial_id: result.serial_id
									},
									success: function(data) {
										if (data.success != 1) alert(data.message);
										else {
											let $srl = jQuery('<div class="reserved-serial as-'+result.serial_id+'">'+result.serial_number+' ['+data.admin+'] <a href="#" class="remove-serial-reservation rml-'+result.serial_id+'" data-orders-products-id="'+result.orders_products_id+'" data-serial-id="'+result.serial_id+'">[REMOVE]</a></div>');
											jQuery('.reserved-serial-numbers.rs-order-line-'+result.orders_products_id).append($srl);

											jQuery('.rml-'+result.serial_id).click(function(e) {
												e.preventDefault();
												remove_serial_reservation(jQuery(this));
											});
										}
									}
								});
							}
						});

						jQuery('.remove-serial-reservation').click(function(e) {
							e.preventDefault();
							remove_serial_reservation(jQuery(this));
						});

						function remove_serial_reservation($srl) {
							jQuery.ajax({
								url: '/admin/orders_new.php',
								dataType: 'json',
								data: {
									action: 'unreserve-serial',
									ajax: 1,
									orders_products_id: $srl.attr('data-orders-products-id'),
									serial_id: $srl.attr('data-serial-id'),
								},
								success: function(data) {
									if (data.success != 1) alert(data.message);
									else {
										jQuery('.as-'+$srl.attr('data-serial-id')).remove();
									}
								}
							});
						}
					</script>
				</div>

				<?php if ($sales_order->has_freight_lines()) {
					$vendors = [];
					$freight_lines = $sales_order->get_freight_lines();
					foreach ($freight_lines['products'] as $product) {
						if (!isset($vendors[$product['location_vendor_id']])) $vendors[$product['location_vendor_id']] = 0;
						$vendors[$product['location_vendor_id']]++;
					} ?>
				<div>
					<style>
						.freight_details th, .freight_details td { border-style:solid; border-color:#000; padding:3px 4px; }
						.freight_details thead th { border-width:1px 0px 0px 1px; }
						.freight_details thead tr:first-child th { background-color:#ccf; }
						.freight_details thead tr:last-child th { border-bottom-width:1px; }
						.freight_details tbody th { border-width:0px 0px 1px 1px; }
						.freight_details th:last-child { border-right-width:1px; }
						.freight_details tbody td { border-width:0px 1px 1px 1px; }
					</style>
					<table class="freight_details" cellpadding="0" cellspacing="0">
						<thead>
							<tr>
								<th colspan="4">FREIGHT SHIPMENT DETAILS</th>
							</tr>
							<tr>
								<th style="background-color:<?= $freight_lines['residential']?'#cfc':'#fcc'; ?>">Residential: <?= $freight_lines['residential']?'Yes':'No'; ?></th>
								<th style="background-color:<?= $freight_lines['liftgate']?'#cfc':'#fcc'; ?>">Liftgate: <?= $freight_lines['liftgate']?($freight_lines['residential']?'Included':'Yes'):'No'; ?></th>
								<th style="background-color:<?= $freight_lines['inside']?'#cfc':'#fcc'; ?>">Inside Delivery: <?= $freight_lines['inside']?'Yes':'No'; ?></th>
								<th style="background-color:<?= $freight_lines['limitaccess']?'#cfc':'#fcc'; ?>">Limited Access: <?= $freight_lines['limitaccess']?'Yes':'No'; ?></th>
							</tr>
							<tr>
								<th colspan="2">Location</th>
								<th colspan="2">IPN</th>
							</tr>
						</thead>
						<tbody>
							<?php $last_vendor_id = -1;
							foreach ($freight_lines['products'] as $freight_item) { ?>
							<tr>
								<?php if ($freight_item['location_vendor_id'] != $last_vendor_id) {
									$last_vendor_id = $freight_item['location_vendor_id']; ?>
								<th colspan="2" rowspan="<?= $vendors[$freight_item['location_vendor_id']]; ?>"><?= $freight_item['vendors_company_name']?$freight_item['vendors_company_name']:'CK'; ?></th>
								<?php } ?>
								<td colspan="2"><?= $freight_item['ipn']; ?></td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
				<?php }

				if (!$sales_order->is_stock_shippable()) { ?>
				<div class="sep cen main">
					<font color="#ff0000"><strong>*** This Order Cannot Be Shipped Due To Insufficient Stock Levels ***</strong></font>
				</div>
				<?php } ?>
				<form method="post" id="order_status" action="/admin/orders_new.php?action=update_order&<?= tep_get_all_get_params(['action', 'need_serials']); ?>" name="order_status" style="display:block; min-width:980px;">
					<input type="hidden" name="cancel_reason_id" id="cancel_reason_id" value="1">
					<input type="hidden" name="purchase_order_products" value="<?= $sales_order->get_ref_po_number(); ?>">

					<div style="float:left;">
						<div class="main">
							<style>
								.orders_history { font-size:10px; }
								.orders_history td { text-align:center; }
							</style>
							<table border="1" cellspacing="0" cellpadding="5" class="orders_history">
								<thead>
									<tr>
										<th>Admin</th>
										<th>Date Added</th>
										<th>Customer Notified</th>
										<th>Status</th>
										<th>Comments</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($sales_order->get_status_history() as $status) { ?>
									<tr>
										<td><?= !empty($status['admin'])?$status['admin']->get_name():'N/A'; ?></td>
										<td><?= $status['status_date']->format('m/d/Y h:i a'); ?></td>
										<td>
											<?php if ($status['customer_notified']) echo tep_image(DIR_WS_ICONS.'tick.gif', ICON_TICK);
											else echo tep_image(DIR_WS_ICONS.'cross.gif', ICON_CROSS); ?>
										</td>
										<td style="text-align:left;">
											<?= $status['orders_status']; ?>
											<?= $status['orders_substatus']; ?>
										</td>
										<td style="text-align:left;"><?= nl2br(htmlspecialchars($status['comments'])); ?></td>
									</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>

						<div class="sep main">
							<br><strong>Comments</strong>
						</div>

						<div class="main sep">
							<textarea id="comments" name="comments" wrap="soft" cols="60" rows="5"></textarea>
						</div>

						<div class="sep">
							<?php if ($sales_order->is_open()) { ?>
							<div id="addPackagesDiv">
								<a class="shiplnk" href="#" onclick="return dispEditPack('<?= $sales_order->id(); ?>', 0, '');">Add Packages</a>&nbsp;
								<a class="shiplnk" href="#" onclick="return runPackage('<?= $sales_order->id(); ?>', '', '', 'package_list', 'editPackagesDiv');">Refresh List</a>&nbsp;
								<a class="shiplnk" href="#" onclick="window.open('/admin/shipping_estimator.php?oid=<?= $sales_order->id(); ?>','shipping_estimator','width=600,height=600' ); return false;">View Shipping Rates</a>
							</div>
							<div id="editpackDiv"></div>
							<?php } ?>
							<div id="editPackagesDiv"></div>
							<input type="button" id="grab_package_tracking_ups" value="Update Tracking from Worldship" onclick="return runPackageTracking('<?= $sales_order->id(); ?>', '', '', 'package_list', 'editPackagesDiv');">
						</div>

						<div class="main">
							<br>Followup Date:
							<input id="followup_date" type="text" value="<?= $sales_order->has('followup_date')?$sales_order->get_header('followup_date')->format('Y-m-d'):''; ?>" name="followup_date">
							<a href="javascript:clearFollowUpDate('followup_date');">Clear Date</a>
							<input id="old_folloup_date" name="old_followup_date" type="hidden" value="<?= $sales_order->has('followup_date')?$sales_order->get_header('followup_date')->format('Y-m-d'):''; ?>">
							<br><br>
						</div>

						<div class="main">
							Promised Ship Date:
							<input id="promised_ship_date" type="text" value="<?= $sales_order->has('promised_ship_date')?$sales_order->get_header('promised_ship_date')->format('Y-m-d'):''; ?>" name="promised_ship_date">
							<a href="javascript:clearFollowUpDate('promised_ship_date');">Clear Date</a>
							<input id="old_promised_ship_date" name="old_promised_ship_date" type="hidden" value="<?= $sales_order->has('promised_ship_date')?$sales_order->get_header('promised_ship_date')->format('Y-m-d'):''; ?>">
							<br><br>
						</div>

						<div class="main">
							<br><strong>Admin Notes:</strong><br><br>
						</div>

						<div class="sep main">
							<table border="1" cellpadding="5" cellspacing="0">
								<tr>
									<td class="smallText" align="center"><strong>Date Added</strong></td>
									<td class="smallText" align="center"><strong>Username</strong></td>
									<td class="smallText" align="center"><strong>Comments</strong></td>
									<td class="smallText"><strong>Picking</strong></td>
									<td class="smallText" align="center">&nbsp;</td>
								</tr>
								<?php if ($sales_order->has_notes()) {
									foreach ($sales_order->get_notes() as $note) {
										$editable = in_array($_SESSION['login_id'], [1, $note['admin_id']]);
										if (!empty($_GET['subaction']) && $_GET['subaction'] == 'order_note_edit' && $editable && $_GET['orders_note_id'] == $note['orders_note_id']) { ?>
								<tr>
									<td class="smallText" align="center">
										<input type="hidden" name="orders_note_id" value="<?= $note['orders_note_id']; ?>">
										<?= $note['note_date']->format('m/d/Y h:i a'); ?>
									</td>
									<td class="smallText" ><?= $note['admin_first_name'].' '.$note['admin_last_name']; ?></td>
									<td class="smallText" ><textarea name="orders_note_text" wrap="soft" cols="60" rows="5"><?= stripslashes($note['note_text']); ?></textarea></td>
									<td class="smallText" style="text-align:center;"><input type="checkbox" name="shipping_notice" <?= $note['shipping_notice']?'checked':''; ?>></td>
									<td class="smallText">
										<?= tep_image_submit('button_update.gif', IMAGE_UPDATE, "onclick=document.order_status.action='/admin/orders_new.php?".tep_get_all_get_params(['action', 'subaction', 'order_note_edit', 'orders_note_id'])."action=edit&subaction=order_note_update'; document.order_status.submit();"); ?>
										<?= tep_image_submit('button_cancel.gif', IMAGE_CANCEL, "onclick=document.order_status.action='/admin/orders_new.php?".tep_get_all_get_params(['action', 'subaction', 'order_note_edit', 'orders_note_id'])."action=edit'; document.order_status.submit()"); ?>
									</td>
								</tr>
										<?php }
										else { ?>
								<tr>
									<td class="smallText" align="center"><?= $note['note_date']->format('m/d/Y h:i a'); ?></td>
									<td class="smallText" ><?= $note['admin_first_name'].' '.$note['admin_last_name']; ?></td>
									<td class="smallText" width="300"><?= nl2br(stripslashes($note['note_text'])); ?></td>
									<td class="smallText" style="text-align:center;"><?= $note['shipping_notice']?'ALERT':''; ?></td>
									<td class="smallText">
										<?php if ($editable) { ?>
										<a href="/admin/orders_new.php?<?= tep_get_all_get_params(['action', 'subaction', 'order_note_edit', 'orders_note_id']); ?>action=edit&subaction=order_note_edit&orders_note_id=<?= $note['orders_note_id']; ?>">Edit</a> |
										<a href="/admin/orders_new.php?<?= tep_get_all_get_params(['action', 'subaction', 'order_note_edit', 'order_note_delete', 'orders_note_id']); ?>action=edit&subaction=order_note_delete&orders_note_id=<?= $note['orders_note_id']; ?>">Delete</a>
										<?php } ?>
									</td>
								</tr>
										<?php }
									}
								}
								else { ?>
								<tr>
									<td colspan="5">No notes found</td>
								</tr>
								<?php } ?>
							</table>
							<?php if (empty($_GET['subaction']) || $_GET['subaction'] != 'order_note_edit') { ?>
							<br>
							<span class="smallText">Add Comment</span><br>
							<textarea id="orders_note_text" name="orders_note_text" wrap="soft" cols="60" rows="5"></textarea>
							<br>
							<input type="checkbox" name="shipping_notice"> Alert Shipping on Pick List
							<?php } ?>
						</div>

						<div>
							<table border="0" cellspacing="0" cellpadding="2">
								<tr>
									<td>
										<table border="0" cellspacing="0" cellpadding="2">
											<tr>
												<td class="main">
													<strong>Current Logical Status:</strong>
													<span id="current_logical_status"><?= $sales_order->get_header('orders_status_name'); ?></span>
												</td>
											</tr>
											<tr>
												<td class="main">
													<style>
														.show-status {}
														.hide-status { display:none; }
													</style>
													<strong>New Logical Status:</strong>
													<?php if (!$sales_order->is('released')) { ?>
													<input type="hidden" name="status" id="status" value="<?= $sales_order->get_header('orders_status'); ?>">
													<input type="hidden" name="sub_status" id="sub_status" value="<?= $sales_order->get_header('orders_sub_status'); ?>">
													<?php if ($admin_perms['update_net_terms'] == 0) { ?>
													<strong>This order must be released by accounting</strong>
														<?php }
														else { ?>
													<input type="submit" name="form-submit" value="Accounting Release">
														<?php }
													}
													elseif ($sales_order->is_open()) { ?>
													<select name="status" id="status">
														<option value="0">Please select...</option>
														<?php foreach ($orders_statuses as $orders_status) {
															//remove the shipped, canceled, "current", warehouse (unless currently RTP), and RTP (if not all items are in stock) statuses from the dropdown
															if (in_array($orders_status['id'], [3, 6, $sales_order->get_header('orders_status')])) continue;
															if ($orders_status['id'] == 7 && $sales_order->get_header('orders_status') != 2) continue;
															if ($orders_status['id'] == 2 && !$sales_order->is_stock_shippable()) continue; ?>
														<option value="<?= $orders_status['id']; ?>"><?= $orders_status['text']; ?></option>
														<?php } ?>
													</select>
														<?php if (!$sales_order->is_stock_shippable()) { ?>
													<br>
													<em>The "Ready to Pick" status is unavailable due to insufficient stock levels.</em>
														<?php } ?>
													<br><br>
													<strong>Sub Status:</strong>

													<select name="sub_status" id="sub_status">
														<?php foreach ($orders_sub_statuses as $substatus) { ?>
														<option value="<?= $substatus['orders_sub_status_id']; ?>" class="status-sub-for-<?= $substatus['orders_status_id']; ?> <?= $substatus['orders_status_id']==$sales_order->get_header('orders_status')?'status-sub-for-0':''; ?>" <?= $substatus['orders_sub_status_id']==$sales_order->get_header('orders_sub_status')?'selected':''; ?>><?= $substatus['orders_sub_status_name']; ?></option>
														<?php } ?>
													</select>
													<script>
														var selected_sub_statuses = [];
														var previous_status = jQuery('#status').val();

														function manage_sub_statuses(e) {
															var status = jQuery('#status').val();

															// if we're moving to warehouse status, confirm
															if (status == 7 && !confirm('Are you sure you want to move this to the Warehouse status? The Warehouse status should only be used by warehouse staff.')) {
																e&&e.preventDefault();
																//jQuery('#status').val(0);
																return false;
															}

															// get currently selected value for the status we're moving away from, so we can reset it if we move back
															if (jQuery('#sub_status option.show-status').length > 0) {
																selected_sub_statuses[previous_status] = jQuery('#sub_status').val();
															}

															// hide previous substatus options, show new substatus options
															jQuery('#sub_status option').removeClass('show-status').addClass('hide-status');
															jQuery('#sub_status option.status-sub-for-'+status).removeClass('hide-status').addClass('show-status');

															// if we don't have any available substatuses, disable the dropdown, otherwise enable it
															if (jQuery('#sub_status option.show-status').length == 0) {
																jQuery('#sub_status').attr('disabled', true);
															}
															else {
																jQuery('#sub_status').attr('disabled', false);
															}

															// if this is the first status on page load, don't reset the substatus
															if (status != previous_status) {
																// if we have a previously selected substatus for this status, select it again, otherwise select the first available substatus
																if (selected_sub_statuses[status] != undefined) {
																	jQuery('#sub_status').val(selected_sub_statuses[status]);
																}
																else {
																	jQuery('#sub_status').val(jQuery('#sub_status option.show-status').first().val());
																}
															}

															// record the current substatus so we know what we're dealing with when we change
															previous_status = status;
														}

														jQuery('#status').change(function(e) {
															manage_sub_statuses(e);
														});

														manage_sub_statuses();
													</script>
													<?php }
													elseif ($sales_order->is_canceled()) { ?>
													Canceled <input type="hidden" name="status" value="<?= ck_sales_order::STATUS_CANCELED; ?>">
													<?php }
													// added lock_order_history field to indicate that the order history shouldn't be updated if a note is added
													elseif ($sales_order->is_shipped()) { ?>
													Shipped <input type="hidden" name="status" id="status" value="<?= ck_sales_order::STATUS_SHIPPED; ?>">
													<input type="hidden" name="sub_status" id="sub_status">
													<input type="hidden" name="lock_order_history" value="t">
													<?php } ?>
													<!--br>
													<span style="font-style: italic; font-size:10px; color: #686868;">*** Moving an order into or out of the Shipped <br> status will affect quantity levels in the store.</span-->
												</td>
											</tr>
											<tr>
												<td class="main">
													<strong>Notify Customer:</strong>
													<?php if (in_array($_SESSION['perms']['admin_groups_id'], [8])) $notify_checked = true;
													else $notify_checked = false;
													echo tep_draw_checkbox_field('notify', '', $notify_checked); ?>
												</td>
												<td class="main" style="display: none;"><strong>Append Comments:</strong> <?= tep_draw_checkbox_field('notify_comments', '', true); ?></td>
											</tr>
											<tr>
												<td>
													<div>
														<hr>
														<h6 style="padding:0; margin:5px 0 10px 0;">Additional Notification Recipients</h6>
														<input type="email" name="recipient_email" id="additional-recipient-email" style="padding:5px; border:.5px solid grey; margin-top:5px; border-radius:3px; font-size:12px; width:300px; outline:none;" placeholder="Search.. (Recipient Email)">
														<input type="text" name="recipient_name" id="additional-recipient-name" style="padding:5px; border:.5px solid grey; margin-top:5px; border-radius:3px; font-size:12px; width:300px; outline:none;" placeholder="Recipient Name (Optional)">
														<input type="hidden" id="recipient-order-id" value="<?= $sales_order->id(); ?>">
														<button type="button" id="add-recipient-button" style="padding:5px; box-shadow:none; border:.5px solid grey; border-radius:5px; font-size:12px; cursor:pointer;">
															Add Recipient
														</button>
														<script>
															let orders_id = jQuery('#recipient-order-id').val();

															jQuery('#additional-recipient-email').autocomplete({
																minChars: 3,
																delay: 600,
																source: function (request, callback) {

																	jQuery.get('/admin/serials_ajax.php',
																		{
																			action: 'generic_autocomplete',
																			term: request.term,
																			orders_id: orders_id,
																			search_type: 'order_recipients'
																		},
																		function (data) {
																			if (data == null) return false;

																			callback(jQuery.map(data, function (item) {
																				if (item.data_display != null) return {misc: item.value, label: item.data_display, value: item.label};
																				else return item;
																			}));
																		}, "json");
																},
																select: function (e, ui) {
																	if (ui != null) {
																		e.preventDefault();
																		jQuery('#additional-recipient-email').val(ui.item.value.email);
																		jQuery('#additional-recipient-name').val(ui.item.value.name);
																	}
																},
																focus: function (e, ui) {
																	e.preventDefault();
																}
															})
															.keyup(function(e) {
																var key = e.keyCode || e.which;
																if (key != 13) return;

																jQuery.ajax({
																	url: '/admin/serials_ajax.php',
																	dataType: 'json',
																	data: {
																		term: jQuery('#additional-recipient-email').val(),
																		search_type: 'order_recipients',
																		action: 'generic_autocomplete',
																		limit: '1',
																	},
																	success: function (data) {
																		if (data != null) {
																			jQuery('#additional-recipient-email').val((data[0]));
																		}
																	}
																});
															});

															jQuery('#add-recipient-button').click(function () {
																jQuery.ajax({
																	url: '/admin/orders_new.php',
																	dataType: 'json',
																	data: {
																		action: 'add-recipient',
																		ajax: 1,
																		orders_id: orders_id,
																		recipient_email: jQuery('#additional-recipient-email').val(),
																		recipient_name: jQuery('#additional-recipient-name').val()
																	},
																	success: function (data) {
																		window.location = '/admin/orders_new.php?oID=' + orders_id + '&action=edit';
																	}
																});
															});

															jQuery('.delete-recipient').live('click', function () {
																let recipient_id = jQuery(this).attr('data-id');
																jQuery.ajax({
																	url: '/admin/orders_new.php',
																	dataType: 'json',
																	data: {
																		action: 'delete-recipient',
																		ajax: 1,
																		recipient_id: recipient_id,
																		orders_id: orders_id
																	},
																	success: function (data) {
																		jQuery('#recipient-tr-'+recipient_id).remove();
																	}
																});
															});
														</script>
														<?php if ($sales_order->has_recipients()) { ?>
															<style>
																#additional-recipients-table { font-size:12px; width:100%; margin:5px 0; }
																#additional-recipients-table td { padding:5px; }
																#additional-recipients-table td i { color:red; cursor:pointer; font-size:16px; }
															</style>
															<table id="additional-recipients-table">
																<thead>
																<tr>
																	<th>Name</th>
																	<th>Email</th>
																	<th></th>
																</tr>
																</thead>
																<tbody>
																<?php foreach ($sales_order->get_recipients() as $recipient) { ?>
																	<tr id="recipient-tr-<?= $recipient['orders_notification_recipient_id']; ?>">
																		<td><?= $recipient['name']; ?></td>
																		<td><?= $recipient['email']; ?></td>
																		<td>
																			<i class="fas fa-trash delete-recipient" data-id="<?= $recipient['orders_notification_recipient_id']; ?>"></i>
																		</td>
																	</tr>
																<?php } ?>
																</tbody>
															</table>
														<?php } ?>
													</div>
												</td>
											</tr>
											<?php //***************************************************************************************************************************************** ?>
											<tr>
												<td colspan="2">
													<?php foreach ($sales_order->get_products() as $product) {
														if ($product['ipn']->is('serialized')) { ?>
													<textarea name="serial_<?= $product['products_id']; ?>" id="serial_<?= $product['products_id']; ?>" style="display:none;"></textarea>
													<input type="text" name="porp_<?= $product['products_id']; ?>" id="porp_<?= $product['products_id']; ?>" style="display:none;">
														<?php }
													} ?>
													<input type="text" id="order_id_hidden" name="order_id" value="<?= $sales_order->id(); ?>" style="display:none;">
												</td>
											</tr>
											<?php //**************************************************************************************************************************************** ?>
										</table>
									</td>
									<td valign="top" class="smallText" nowrap>
										<button type="button" onClick="open_serials_release_dialog();">Update</button>
									</td>
								</tr>
							</table>
						</div>
					</div>

					<?php $today = new DateTime(); ?>
					<div class="main" style="float:left; margin-left:150px;">
						<style>
							.allocation-details { border-collapse:collapse; margin-top:5px; }
							.allocation-details th, .allocation-details td { border:1px solid #000; padding:5px 8px; }
							.allocation-details th { background-color:#eee; }
							.allocation-details tfoot td { text-align:right; border-width:0px; }
							.allocation-details .late-alloc td { background-color:#fcc; }
							.allocation-details .late-recpt td { background-color:#ffd; }
							.allocation-details.non-manager .allocation-manage { display:none; }
							.allocation-details.order-locked .allocation-add { display:none; }
						</style>
						<strong>Allocation Expected Dates</strong>
						<table border="0" cellpadding="0" cellspacing="0" class="allocation-details <?= !in_array($_SESSION['login_groups_id'], [1, 5, 7, 8, 9, 10, 17, 18, 20, 22, 29, 30, 31])?'non-manager':''; ?> <?= $sales_order->is_closed()?'order-locked':''; ?>">
							<thead>
								<tr>
									<th>IPN</th>
									<th>PO</th>
									<th>Qty</th>
									<th>Expected Date</th>
									<th class="allocation-manage">[X]</th>
								</tr>
							</thead>
							<tfoot class="allocation-manage allocation-add">
								<tr>
									<td colspan="5">
										<a href="/admin/orders_new.php?oID=<?= $sales_order->id(); ?>&action=auto-allocate" class="button-link auto-allocate" data-method="post">Auto-Allocate</a>
									</td>
								</tr>
							</tfoot>
							<tbody>
								<?php if ($sales_order->has_po_allocations()) {
									foreach ($sales_order->get_po_allocations() as $allocation) {
										$expected_date_class = '';
										if ($sales_order->has('promised_ship_date')) {
											if (!empty($allocation['expected_date']) && $today > $allocation['expected_date']) $expected_date_class = 'late-recpt';
											if (empty($allocation['expected_date']) || $allocation['expected_date'] > $sales_order->get_header('promised_ship_date')) $expected_date_class = 'late-alloc';
										} ?>
								<tr class="<?= $expected_date_class; ?>">
									<td><a href="/admin/ipn_editor.php?ipnId=<?= $allocation['ipn']; ?>" target="_blank"><?= $allocation['ipn']; ?></a></td>
									<td><a href="/admin/po_viewer.php?poId=<?= $allocation['po_id']; ?>" target="_blank"><?= $allocation['purchase_order_number']; ?></a></td>
									<td><?= $allocation['quantity']; ?></td>
									<td><?= !empty($allocation['expected_date'])?$allocation['expected_date']->format('m/d/Y'):'NONE'; ?></td>
									<td class="allocation-manage"><a href="/admin/orders_new.php?oID=<?= $sales_order->id(); ?>&action=de-allocate&allocation_id=<?= $allocation['allocation_id']; ?>" class="button-link auto-allocate" data-method="post">Remove</a></td>
								</tr>
									<?php }
								}
								else { ?>
								<tr>
									<td colspan="5" class="cen">No Allocations from Purchase Orders</td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
						<script>
							ck.ajaxify.link(jQuery('.auto-allocate'), function() {
								window.location.reload(true);
							});
						</script>
					</div>

					<div style="clear:both;"></div>

					<div class="act">
						Station:
						<select id="station_select">
							<option value="pick1" <?php if ($_SESSION['station'] == 'pick1') { ?> selected <?php } ?>>pick1</option>
							<option value="pack1" <?php if ($_SESSION['station'] == 'pack1') { ?> selected <?php } ?>>pack1</option>
							<option value="pack2" <?php if ($_SESSION['station'] == 'pack2') { ?> selected <?php } ?>>pack2</option>
						</select>
						<script>
							jQuery('#station_select').change(function() {
								jQuery.get('/admin/orders_new.php?action=update_station&station='+jQuery(this).val());
							});
							<?php if (empty($_SESSION['station'])) { ?>jQuery.get('/admin/orders_new.php?action=update_station&station='+jQuery(this).val());<?php } ?>
						</script>
					</div>

					<style>
						.order-submit-button { margin-right:30px; width:200px; height:50px; background-color:green; color:white; font-size:15pt; }
						.order-submit-button[disabled] { background-color:buttonface; }
					</style>

					<?php if ($sales_order->is('released')) { ?>
					<div class="act main">
						<?php if ((in_array($_SESSION['perms']['admin_groups_id'], [1]) || in_array($_SESSION['perms']['admin_id'], [4, 65, 61, 81, 48, 20, 72, 52, 145, 128])) && $sales_order->is_unshippable()) { ?>
						<input type="submit" class="order-submit-button" id="unship-button" name="form-submit" style="margin-right:10px;" value="Unship">
						<?php }
						elseif (in_array($_SESSION['perms']['admin_groups_id'], [1]) && $sales_order->is_unshippable(TRUE)) { ?>
						<input type="submit" class="order-submit-button" id="unship-button" name="form-submit" style="margin-right:10px; background-color:red;" value="Unship">
						<?php }

						if ((in_array($_SESSION['perms']['admin_groups_id'], [1, 11]) || in_array($_SESSION['perms']['admin_id'], [4, 65, 61, 81, 48, 20, 72, 52, 145, 128])) && $sales_order->is_shipped()) { ?>
						<input type="submit" class="order-submit-button" id="edit_invoice-button" name="form-submit" style="margin-right:10px;" value="Edit Invoice" <?= $sales_order->has_rmas()?'disabled':''; ?> onClick="javascript:popupWindow('/admin/invoice.php?oID=<?= $sales_order->id(); ?>&edit=invoice'); return false;">
						<?php } ?>

						<input type="submit" class="order-submit-button" id="process-ship-button" name="form-submit" <?= $sales_order->is_shipped()||$sales_order->is_canceled()||!$sales_order->is_stock_shippable()?'disabled':''; ?> style="margin-right:30px;" value="<?= $sales_order->is_cc_capture_needed()?"Charge &amp; Ship":"Ship"; ?>">
						<?php if (!$sales_order->is_stock_shippable()) { ?>
						<script>
							jQuery('#charge-and-ship').remove();
						</script>
						<?php } ?>
						<script>
							jQuery(document).ready(function() {
								jQuery('#order_status').submit(function(e) {
									jQuery('#charge-and-ship, #process-ship-button').click(function(e) {
										e.preventDefault();
										return false;
									});
								});
							});
						</script>

						<?php if ($sales_order->is_canceled() && in_array($_SESSION['perms']['admin_groups_id'], [1])) { ?>
						<input type="submit" id="uncancel" name="form-submit" style="margin-right:30px; width:200px; height:50px; background-color:green; color:white; font-size:15pt; cursor:pointer;" value="Uncancel">
						<?php } ?>
					</div>
					<?php } ?>
				</form>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>

	<div>
		<div id="cancel_reason_dialog" style="font-family: 11px;">
			<p>Please choose one of the following reasons for cancelling the order:</p>
			<ul style="list-style-type: none;">
				<?php $cancel_reasons = prepared_query::fetch('SELECT * FROM orders_canceled_reasons');
				foreach ($cancel_reasons as $cancel_reason) { ?>
				<li>
					<input type="radio" name="cancel_reason_id" onClick="updateCancelReasonId('<?= $cancel_reason['id']; ?>');" value="<?= $cancel_reason['id']; ?>" <?= $cancel_reason['id']==1?'checked':''; ?>>
					<?= $cancel_reason['text']; ?>
				</li>
				<?php } ?>
			</ul>
		</div>
		<script>
			function updateCancelReasonId(value) {
				jQuery('#cancel_reason_id').val(value);
			}

			jQuery(document).ready(function($) {
				jQuery( "#cancel_reason_dialog" ).dialog({
					autoOpen: false,
					height: 400,
					width: 400,
					modal: true,
					title: 'Choose a Cancellation Reason',
					buttons: {
						'Cancel Order': function() {
							cancelOrder();
							jQuery( this ).dialog( "close" );
						},
						'Go Back': function() {
							jQuery( this ).dialog( "close" );
						}
					},
					close: function() {}
				});
			});
		</script>
	</div>

	<!-- body_eof //-->
	<?php if (CK\fn::check_flag(@$_SESSION['need_serials'])) {
		$_SESSION['need_serials'] = NULL;
		unset($_SESSION['need_serials']); ?>
	<script>
		alert('Your order was not updated. Please enter serials before shipping order.');
	</script>
	<?php } ?>
	<script>
		ck.button_links();

		jQuery('#can-not-release-fraudulent-customer-order').click(function () {
			alert('You can not release a fraudulent customers order. The fraud flag must be removed from the customer to release this order');
		});
	</script>
</body>
</html>
