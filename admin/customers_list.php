<?php
require('includes/application_top.php');

$action = isset($_GET['action'])?$_GET['action']:'';

if (!empty($_GET['customers_id']) && !empty($action)) {
	if ($action == 'deleteconfirm') {
		// we don't even attempt to process the action unless we know it's the one thing we can do on this page
		$customer = new ck_customer2($_GET['customers_id']);
		$customer->process_account($action);
	}
	elseif ($action == 'quicksearch') {
		$customer = new ck_customer2($_GET['customers_id']);
	}
	elseif ($action == 'login-frontend') {
		$customer = new ck_customer2($_GET['customers_id']);
		$header = $customer->get_header();
		$address = $customer->get_addresses('default');
		$_SESSION['customer_id'] = $customer->id();
		$_SESSION['customer_default_address_id'] = $header['default_address_id'];
		$_SESSION['customer_first_name'] = $header['first_name'];
		$_SESSION['customer_last_name'] = $header['last_name'];
		$_SESSION['customer_country_id'] = !empty($address)?$address->get_header('countries_id'):'';
		$_SESSION['customer_zone_id'] = !empty($address)?$address->get_header('zone_id'):'';
		$_SESSION['customer_is_dealer'] = $customer->is('dealer')?1:0;
		$_SESSION['customer_extra_login_id'] = !empty($_GET['customers_extra_logins_id'])?$_GET['customers_extra_logins_id']:NULL;
		$_SESSION['admin_as_user'] = TRUE;
		$_SESSION['admin_id'] = $_SESSION['login_id'];
		$_SESSION['set_admin_as_user'] = TRUE;

		CK\fn::redirect_and_exit('/');
	}
}

// list query and pagination
if (!empty($customer)) {
	$customers = array($customer);
	$page = 1;
	$pagestart = 1;
	$pageend = 1;
	$customers_count = 1;
	$total_pages = 1;
}
else {
	$order_by = [];
	if (!empty($_GET['sort'])) {
		$direction = !empty($_GET['order'])&&$_GET['order']=='ascending'?'ASC':'DESC';
		switch ($_GET['sort']) {
			case 'lastname':
				$order_by['c.customers_lastname'] = $direction;
				break;
			case 'firstname':
				$order_by['c.customers_firstname'] = $direction;
				break;
			case 'account_manager':
				$order_by['a.admin_firstname'] = $direction;
				break;
			case 'account_created':
				$order_by['ci.customers_info_date_account_created'] = $direction;
				break;
		}
	}

	$page = !empty($_GET['page'])&&(int)$_GET['page']?(int) $_GET['page']:1;
	$page_size = 50;

	$pagestart = (($page - 1) * $page_size) + 1;
	$pageend = $page * $page_size;

	$fields = [];

	if (!empty($_GET['last_name'])) $fields[':last_name'] = $_GET['last_name'].'%';
	if (!empty($_GET['email'])) $fields[':email'] = '%'.$_GET['email'].'%';

	$fields[':simple_result'] = 1;

	$customers = ck_customer2::get_customers_by_match($fields, $page, $page_size, $order_by);
	$customers_count = ck_customer2::$matched_total;
	$total_pages = ceil($customers_count / $page_size);
	$pageend = min($customers_count, $pageend);
}
?>
<!doctype html>
<html>
	<head>
		<title><?= TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<script language="javascript" src="includes/menu.js"></script>
		<script language="javascript" src="/includes/javascript/prototype.js"></script>
		<script language="javascript" src="/includes/javascript/scriptaculous/scriptaculous.js"></script>
		<link rel="stylesheet" type="text/css" href="/includes/javascript/scriptaculous/scriptaculous.css">
		<style>
			div.autocomplete { position:absolute; width:250px; background-color:white; border:1px solid #888; margin:0; padding:0; }
			div.autocomplete ul { list-style-type:none; margin:0; padding:0; }
			div.autocomplete ul li.selected { background-color: #ffb; }
			div.autocomplete ul li { list-style-type:none; display:block; margin:0; padding:2px; height:32px; cursor:pointer; }
		</style>
		<script src="/images/static/js/ck-styleset.js"></script>
		<script src="/images/static/js/ck-ajaxify.max.js"></script>
		<script src="/images/static/js/ck-button-links.max.js"></script>
	</head>
	<body>
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
					<table border="0" cellspacing="0" cellpadding="2">
						<tr>
							<td>
								<form method="get" action="/admin/customers_list.php">
									<strong>Quick Customer ID Entry:</strong>
									<input type="hidden" name="action" value="quicksearch">
									<input type="text" name="customers_id" size="7">
									<input type="submit" value="Go">
								</form>
							</td>
						</tr>
						<tr>
							<td>
								<form name="search" action="/admin/customers_list.php" method="get">
									<?php $search_params = CK\fn::filter_request($_GET, array('page', 'info', 'customers_id', 'action', 'last_name', 'email', 'type_only'));
									foreach ($search_params as $param => $value) { ?>
									<input type="hidden" name="<?= $param; ?>" value="<?= $value; ?>">
									<?php } ?>
									<table border="0" width="100%" cellspacing="0" cellpadding="0">
										<tr>
											<td class="pageHeading">
											</td>
											<td class="smallText" align="right">
												Last Name: <input type="text" name="last_name">
												Email: <input type="text" name="email">
												<input type="submit" name="submit" value="Search">
											</td>
										</tr>
									</table>
								</form>
							</td>
						</tr>
						<tr>
							<td>
								<table border="0" width="100%" cellspacing="0" cellpadding="0">
									<tr>
										<td style="vertical-align:top;">
											<table border="0" width="100%" cellspacing="0" cellpadding="2">
												<tr class="dataTableHeadingRow">
													<?php $sorting_fields = CK\fn::filter_request($_GET, array('sort', 'order', 'customers_id')); ?>
													<td class="dataTableHeadingContent">Email Address</td>
													<td class="dataTableHeadingContent">
														Last Name
														<a href="/admin/customers_list.php?<?= http_build_query($sorting_fields); ?>&sort=lastname&order=ascending"><img src="images/arrow_up.gif" border="0"></a>
														<a href="/admin/customers_list.php?<?= http_build_query($sorting_fields); ?>&sort=lastname&order=descending"><img src="images/arrow_down.gif" border="0"></a>
													</td>
													<td class="dataTableHeadingContent">
														First Name
														<a href="/admin/customers_list.php?<?= http_build_query($sorting_fields); ?>&sort=firstname&order=ascending"><img src="images/arrow_up.gif" border="0"></a>
														<a href="/admin/customers_list.php?<?= http_build_query($sorting_fields); ?>&sort=firstname&order=descending"><img src="images/arrow_down.gif" border="0"></a>
													</td>
													<td class="dataTableHeadingContent" align="right">
														Account Created
														<a href="/admin/customers_list.php?<?= http_build_query($sorting_fields); ?>&sort=account_created&order=ascending"><img src="images/arrow_up.gif" border="0"></a>
														<a href="/admin/customers_list.php?<?= http_build_query($sorting_fields); ?>&sort=account_created&order=descending"><img src="images/arrow_down.gif" border="0"></a>
													</td>
													<td class="dataTableHeadingContent" align="right">
														Account Manager
														<a href="/admin/customers_list.php?<?= http_build_query($sorting_fields); ?>&sort=account_manager&order=ascending"><img src="images/arrow_up.gif" border="0"></a>
														<a href="/admin/customers_list.php?<?= http_build_query($sorting_fields); ?>&sort=account_manager&order=descending"><img src="images/arrow_down.gif" border="0"></a>
													</td>
													<td class="dataTableHeadingContent">Type</td>
													<td class="dataTableHeadingContent" align="right">Referred By</td>
													<td class="dataTableHeadingContent" align="right">Action</td>
												</tr>
												<style>
													.customer-row { cursor:pointer; }
													.dataTableRow:hover td { background-color:#fff; }
												</style>
												<input type="hidden" name="rebuild_params" id="rebuild_params" value="<?= http_build_query(CK\fn::filter_request($_GET, array('customers_id', 'action'))); ?>">
												<?php foreach ($customers as $idx => $customer) {
													$row_id = '';
													$row_class = 'dataTableRow';
													$focusrow = FALSE;
													if ((empty($_GET['customers_id']) && $idx == 0) || (!empty($_GET['customers_id']) && $customer->get_header('customers_id') == $_GET['customers_id'])) {
														$row_id = 'defaultSelected';
														$row_class .= 'Selected';
														$focusrow = TRUE;
													} ?>
												<tr <?= $row_id; ?> class="<?= $row_class; ?> customer-row" data-customers-id="<?= $customer->get_header('customers_id'); ?>">
													<td class="dataTableContent"><?= $customer->get_header('email_address'); ?></td>
													<td class="dataTableContent"><?= $customer->get_header('last_name'); ?></td>
													<td class="dataTableContent"><?= $customer->get_header('first_name'); ?></td>
													<td class="dataTableContent" align="right"><?= $customer->get_header('date_account_created')->format('m/d/Y'); ?></td>
													<td class="dataTableContent" align="right"><?= $customer->has_account_manager()?$customer->get_account_manager()->get_name():''; ?></td>
													<td class="dataTableContent" align="right"><?= $customer->is('dealer')?'Dealer':'Normal'; ?></td>
													<td class="dataTableContent" align="right"><?= $customer->get_header('referral_source'); ?></td>
													<td class="dataTableContent" align="right">
														<?php if ($focusrow) { ?>
														<img src="images/icon_arrow_right.gif">
														<?php }
														else { ?>
														<img src="images/icon_info.gif">
														<?php } ?>
													</td>
												</tr>
													<?php if ($idx%9 == 0) flush();
												} ?>
												<script>
													jQuery('.customer-row').click(function() {
														console.log('win1');
														if (jQuery(this).hasClass('dataTableRowSelected')) {
															document.location.href = '/admin/customers_detail.php?customers_id='+jQuery(this).attr('data-customers-id');
														}
														else {
															document.location.href = '/admin/customers_list.php?'+jQuery('#rebuild_params').val()+'&customers_id='+jQuery(this).attr('data-customers-id');
														}
													});
												</script>
												<tr>
													<td colspan="5">
														<table border="0" width="100%" cellspacing="0" cellpadding="2">
															<tr>
																<td class="smallText" valign="top">Displaying <strong><?= $pagestart; ?></strong> to <strong><?= $pageend; ?></strong> (of <strong><?= $customers_count; ?></strong> customers)</td>
																<td class="smallText" align="right">
																	<form name="pages" action="/admin/customers_list.php" method="get">
																		<?php $pagination_params = CK\fn::filter_request($_GET, array('page', 'info', 'customers_id', 'action'));
																		foreach ($pagination_params as $param => $value) { ?>
																		<input type="hidden" name="<?= $param; ?>" value="<?= $value; ?>">
																		<?php }

																		if ($page != 1) { ?>
																		<a href="/admin/customers_list.php?<?= http_build_query($pagination_params); ?>&page=<?= $page-1; ?>">&lt;&lt;</a>
																		<?php } ?>

																		Page
																		<select id="page" name="page">
																			<?php for ($i=1; $i<=$total_pages; $i++) { ?>
																			<option value="<?= $i; ?>" <?= $i==$page?'selected':''; ?>><?= $i; ?></option>
																			<?php } ?>
																		</select>
																		of <?= $total_pages; ?>

																		<?php if ($page != $total_pages) { ?>
																		<a href="/admin/customers_list.php?<?= http_build_query($pagination_params); ?>&page=<?= $page+1; ?>">&gt;&gt;</a>
																		<?php } ?>
																	</form>
																</td>
															</tr>
														</table>
													</td>
												</tr>
											</table>
										</td>
										<style>
											.info-header { font-weight:bold; color:#fff; padding:3px 5px; background-color:#b3bac5; }
											.info-content { padding:3px 5px; background-color:#dee4e8; }
										</style>
										<td style="width:350px;font-size:10px;vertical-align:top;">
											<?php if (!empty($_GET['customers_id']) && !empty($_GET['action']) && $_GET['action'] == 'delete_whole_customer') {
												$customer = new ck_customer2($_GET['customers_id']); ?>
											<div class="info-header">
												Delete Customer?
											</div>
											<div class="info-content">
												<form name="customers" action="/admin/customers_list.php?<?= http_build_query(CK\fn::filter_request($_GET, array('customers_id', 'action'))); ?>&customers_id=<?= $customer->get_header('customers_id'); ?>&action=deleteconfirm" method="post">
													Are you sure you want to delete this customer?<br><br>
													<strong><?= $customer->get_header('first_name').' '.$customer->get_header('last_name'); ?></strong><br><br>
													<input type="image" src="/admin/includes/languages/english/images/buttons/button_delete.gif">
													<a href="/admin/customers_list.php?<?= http_build_query(CK\fn::filter_request(array('action'))); ?>"><img src="/admin/includes/languages/english/images/buttons/button_cancel.gif"></a>
												</form>
											</div>
											<?php }
											else {
												// because we're using a registry system in the back end, we can re-instantiate the object without running all of the DB queries again
												if (!empty($_GET['customers_id'])) $customer = new ck_customer2($_GET['customers_id']);
												elseif (!empty($customers)) $customer = $customers[0];
												
												if (empty($customer)) { ?>
											<div class="info-header">
												No Customer Selected
											</div>
											<div class="info-content">
												There is no customer selected
											</div>
												<?php }
												else {
													$header = $customer->get_header();
													$default_address = $customer->get_addresses('default'); ?>
											<div class="info-header">
												<?= $header['first_name'].' '.$header['last_name']; ?>
											</div>
											<div class="info-content">
												<a href="/admin/customers_detail.php?customers_id=<?= $header['customers_id']; ?>"><img src="/admin/includes/languages/english/images/buttons/button_edit.gif" border="0" alt="Edit" title=" Edit "></a>

												<!--Removing the ability to delete a customer--<a href="/admin/customers_list.php?<?= http_build_query(CK\fn::filter_request($_GET, array('customers_id', 'action'))); ?>&customers_id=<?= $header['customers_id']; ?>&action=delete_whole_customer"><img src="/admin/includes/languages/english/images/buttons/button_delete.gif" border="0" alt="Delete" title=" Delete "></a>-->

												<a href="/admin/orders_new.php?customers_id=<?= $header['customers_id']; ?>"><img src="/admin/includes/languages/english/images/buttons/button_orders.gif" border="0" alt="Orders" title=" Orders "></a>

												<a href="/admin/mail.php?selected_box=tools&amp;customer=<?= $header['email_address']; ?>"><img src="/admin/includes/languages/english/images/buttons/button_email.gif" border="0" alt="Email" title=" Email "></a>
												<br><br>

												<?php if (tep_admin_check_boxes('customer_account_history.php')) { ?>
												<a href="/admin/customer_account_history.php?customer_id=<?= $header['customers_id']; ?>" class="button-link">Account History</a>
												<?php } ?>

												<?php if (tep_admin_check_boxes('customer_quote_dashboard.php')) { ?>
												<a href="/admin/customer_quote_dashboard.php?customers_id=<?= $header['customers_id']; ?>" class="button-link">Quotes</a>
												<?php } ?>

												<?php if (tep_admin_check_boxes('customer_account_history.php') || tep_admin_check_boxes('customer_quote_dashboard.php')) { ?>
												<br><br>
												<?php } ?>

												<?php if ($_SESSION['perms']['use_master_password'] == 1) { ?>
												<a href="/admin/customers_list.php?customers_id=<?= $header['customers_id']; ?>&action=login-frontend" target="_blank" class="button-link">Cart Log In <?= $header['email_address']; ?></a><br><br>
												<?php } ?>

												Account Created: <?= !empty($header['date_account_created'])?$header['date_account_created']->format('m/d/Y'):''; ?><br><br>
												Last Modified: <?= !empty($header['date_account_last_modified'])?$header['date_account_last_modified']->format('m/d/Y'):''; ?><br><br>
												Last Logon: <?= !empty($header['date_last_logon'])?$header['date_last_logon']->format('m/d/Y'):''; ?><br><br>
												Number of Logons: <?= $header['number_of_logons']; ?><br><br>
												Country: <?= !empty($default_address)?$default_address->get_header('country'):''; ?><br>
												<hr><br>

												<strong>Has Tax Exemptions:</strong><br>
												<?= $customer->has_tax_exemptions()?'Yes':'No'; ?><br><br>

												<strong>Net Terms:</strong><br>
												<?= !empty($customer->get_header('terms_label'))?$customer->get_header('terms_label'):'N/A'; ?><br><br>

												<strong>Send Late Notice:</strong><br>
												<?= $customer->is('send_late_notice')?'Yes':'No'; ?><br><br>

												<strong>Offer Direct FEDEX/UPS Charging:</strong><br>
												<?php if ($customer->is('own_shipping_account')) { ?>
												Yes<br><br>
													<?php if ($customer->has('fedex_account_number')) { ?>
													<strong>Fedex Number:</strong><br>
													<?= $customer->get_header('fedex_account_number'); ?><br><br>
													<?php }

													if ($customer->has('ups_account_number')) { ?>
													<strong>UPS Number:</strong><br>
													<?= $customer->get_header('ups_account_number'); ?><br><br>
													<?php }
												}
												else { ?>
												No<br><br>
												<?php } ?>

												<strong>AIM Screen Name:</strong><br>
												<?= $customer->get_header('aim_screenname'); ?><br><br>

												<strong>MSN Screen Name:</strong><br>
												<?= $customer->get_header('msn_screenname'); ?><br><br>

												<strong>Company Accounting Contact:</strong><br>
												Name: <?= $customer->get_accounting_contact('name'); ?><br>
												Email: <?= $customer->get_accounting_contact('email'); ?><br>
												Phone #:<?= $customer->get_accounting_contact('phone'); ?><br>

												<hr>

												<strong>Notes:</strong><br>
												<?= $customer->get_header('notes'); ?>
											</div>
												<?php }
											} ?>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<!-- body_eof //-->
		<script>
			ck.button_links();
		</script>
	</body>
</html>
