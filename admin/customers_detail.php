<?php
ini_set('memory_limit', '256M');
require('includes/application_top.php');

require_once('includes/modules/accounting_notes.php');

if (empty($_GET['customers_id']) && empty($_POST['customers_id'])) {
	$customer = NULL;
}
elseif (!empty($_GET['customers_id'])) {
	$customer = new ck_customer2($_GET['customers_id']);
}
else {
	$customer = new ck_customer2($_POST['customers_id']);
}

$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$contact_manager_action = isset($_REQUEST['ccm_action'])?$_REQUEST['ccm_action']:'';
$category_discount_action = isset($_REQUEST['cdm_action'])?$_REQUEST['cdm_action']:'';

$admin_perms = prepared_query::fetch('SELECT * FROM admin WHERE admin_id = ?', cardinality::ROW, $_SESSION['login_id']);

if ($customer) {
	if ($action) {
		if (in_array($action, ['extra_login_add', 'extra_login_update', 'extra_login_delete', 'extra_login_search', 'extra_login_compare', 'extra_login_convert', 'extra_login_separate'])) {
			$customer->process_extra_login($action);
		}
		else {
			$customer->process_account($action);
		}
	}
	if ($contact_manager_action) $customer->process_contact($contact_manager_action);
	if ($category_discount_action) $customer->process_category_discount($category_discount_action);
}

$account_managers = ck_admin::get_account_managers(['ck_admin', 'sort_by_name']);
$sales_teams = ck_team::get_sales_teams();

$user = ck_admin::login_instance(ck_admin::CONTEXT_ADMIN); ?>
<!doctype html>
<html>
	<head>
		<title><?= TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<script language="javascript" src="includes/menu.js"></script>
		<script language="javascript" src="/includes/javascript/prototype.js"></script>
		<script language="javascript" src="/includes/javascript/scriptaculous/scriptaculous.js"></script>
		<link rel="stylesheet" type="text/css" href="/includes/javascript/scriptaculous/scriptaculous.css">
		<script language="javascript">
			function check_form() {
				var error = 0;
				var error_message = "Errors have occured during the process of your form!\nPlease make the following corrections:\n\n";

				var first_name = document.customers.first_name.value;
				var last_name = document.customers.last_name.value;
				var company_name = document.customers.company.value;
				var email_address = document.customers.email_address.value;
				var address1 = document.customers.address1.value;
				var postcode = document.customers.postcode.value;
				var city = document.customers.city.value;
				var telephone = document.customers.telephone.value;

				if (first_name == "" || first_name.length < jQuery('#entry_firstname-min_length').val()) {
					error_message = error_message + "* The 'First Name' entry must have at least "+jQuery('#entry_firstname-min_length').val()+" characters.\n";
					error = 1;
				}

				if (last_name == "" || last_name.length < jQuery('#entry_lastname-min_length').val()) {
					error_message = error_message + "* The 'Last Name' entry must have at least "+jQuery('#entry_lastname-min_length').val()+" characters.\n";
					error = 1;
				}

				if (email_address == "" || email_address.length < jQuery('#customers_email_address-min_length').val()) {
					error_message = error_message + "* The 'E-Mail Address' entry must have at least "+jQuery('#customers_email_address-min_length').val()+" characters.\n";
					error = 1;
				}

				if (address1 == "" || address1.length < jQuery('#entry_street_address-min_length').val()) {
					error_message = error_message + "* The 'Street Address' entry must have at least "+jQuery('#entry_street_address-min_length').val()+" characters.\n";
					error = 1;
				}

				if (postcode == "" || postcode.length < jQuery('#entry_postcode-min_length').val()) {
					error_message = error_message + "* The 'Post Code' entry must have at least "+jQuery('#entry_postcode-min_length').val()+" characters.\n";
					error = 1;
				}

				if (city == "" || city.length < jQuery('#entry_city-min_length').val()) {
					error_message = error_message + "* The 'City' entry must have at least "+jQuery('#entry_city-min_length').val()+" characters.\n";
					error = 1;
				}

				if (document.customers.elements['state'].type != "hidden") {
					if (document.customers.state.value == '' || document.customers.state.value.length < jQuery('#entry_state-min_length').val()) {
						error_message = error_message + "* The 'State' entry must be selected.\n";
						error = 1;
					}
				}

				if (document.customers.elements['countries_id'].type != "hidden") {
					if (document.customers.countries_id.value == 0) {
						error_message = error_message + "* The 'Country' value must be chosen.\n";
						error = 1;
					}
				}

				if ($('ups_account_number').present() && !$('ups_account_number').value.match(/^[A-Za-z0-9]{6}$/)) {
					error_message = error_message + "* A valid UPS account number has 6 letters and/or numbers.\n";
					error = 1;
				}

				if ($('fedex_account_number').present() && !$('fedex_account_number').value.match(/^[0-9]{9}$/)) {
					error_message = error_message + "* A valid FedEx account number has 9 digits.\n";
					error = 1;
				}

				if (error == 1) {
					alert(error_message);
					return false;
				}
				else {
					return true;
				}
			}
		</script>
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
		<?php foreach (ck_customer2::$validation as $field => $criteria) {
			foreach ($criteria as $option => $limit) { ?>
		<input type="hidden" id="<?= $field.'-'.$option; ?>" value="<?= $limit; ?>">
			<?php }
		} ?>
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
					<div style="width:1050px;">
						<style>
							#customer-header h2 { font-size:18px; color:#727272; display:inline; font-weight:bold; }
							#customer-header strong { font-size:14px; }
							.customer-data { margin-top:18px; }
							.customer-data h3 { font-size:12px; font-weight:bold; display:inline; }
							.customer-data-box { background-color:#f1f9fe; border:1px solid #7b9ebd; }
							#accounting-hold-message { text-align:center; font-size:24px; font-weight:bold; color:#ff0000; margin:15px; }
							#accounting-hold-message p { margin:0; }
							.foot-note { font-size:12px; }
						</style>
						<div id="customer-header">
							<?php if ($customer->is('fraud')) { ?>
							<div id="accounting-hold-message">
								<p>------Customer Is Set As Fraudulent------</p>
								<p class="foot-note"><i>Any orders placed by the customer or for the customer will not be fully processed and will be sent to accounting hold until this customer is no longer considered fraud</i></p>
								<?php if (in_array($_SESSION['perms']['admin_groups_id'], [1, 11, 31])) { ?>
								<button type="button" id="remove-fraud-flag-button" data-customers-id="<?= $customer->id(); ?>">Remove Fraud Flag</button>
								<?php } ?>
							</div>
							<?php } ?>
							<form method="get" action="/admin/customers_detail.php">
								<strong>Quick Customer ID Entry:</strong>
								<input type="text" name="customers_id" size="7">
								<input type="submit" value="Go">
							</form><br>
							<h2>Customers</h2>
							<?php if (!empty($customer)) { ?>
							<form action="/admin/customers_detail.php?customers_id=<?= $customer->get_header('customers_id'); ?>&action=update_crm" method="post"><input type="submit" value="Push To CRM"></form>
							<?php } ?>

							<?php if (!empty($customer) && $_SESSION['perms']['use_master_password'] == 1) { ?>
							<br><br><a href="/admin/customers_list.php?customers_id=<?= $customer->id(); ?>&action=login-frontend" target="_blank" class="button-link">Cart Log In <?= $customer->get_header('email_address'); ?></a>
							<?php } ?>
							<?php $crm_link = $customer->get_crm_link();
							if (!empty($crm_link)) { ?>
							<br>
							<a href="<?= $crm_link; ?>" target="_blank" class="button-link" style="margin-top:3px;">CRM Company &#8599;</a>
							<?php } ?>
							<?php if (in_array($_SESSION['perms']['admin_groups_id'], [1, 11, 31]) && !$customer->is('fraud')) { ?>
							<br><br>
							<button type="button" id="set-fraud-flag-button" data-customers-id="<?= $customer->id(); ?>">Set As Fraud</button>
							<?php } ?>
						</div>

						<?php if ($customer->has_password_reset()) { ?>
						<div class="customer-data">
							<h3>Requested Password Resets:</h3>
							<?php foreach ($customer->get_password_reset() as $pr) { ?>
							<div><small><?= !$pr['reset_code_active']?'!EXPIRED!':''; ?> <a class="reset-link" href="<?= $pr['reset_code_link']; ?>">[click to copy]</a> [Sent to: <?= $pr['email']; ?>]</small></div>
							<?php } ?>
							<script>
								jQuery('.reset-link').click(function(e) {
									e.preventDefault();
									navigator.clipboard.writeText(jQuery(this).attr('href'));
								});
							</script>
						</div>
						<?php } ?>

						<div class="customer-data">
							<h3>Extra Logins</h3>
							<div class="customer-data-box">
								<table cellspacing="2" cellpadding="2" border="0">
									<tr>
										<td class="main">Email / Login</td>
										<td class="main">Password</td>
										<td class="main">First Name</td>
										<td class="main">Last Name</td>
										<td class="main">Copy Account on Orders</th>
										<td class="main">Actions</td>
									</tr>
									<?php if ($active_logins = $customer->get_extra_logins('active')) {
										foreach ($active_logins as $extra_login) { ?>
									<tr>
										<td class="main"><?= $extra_login['email_address']; ?></td>
										<td class="main">*****</td>
										<td class="main"><?= $extra_login['first_name']; ?></td>
										<td class="main"><?= $extra_login['last_name']; ?></td>
										<td class="main"><?= $extra_login['copy_account'] == 1?'Yes':'No'; ?></td>
										<td class="main">
											<input type="button" value="Edit" onClick="$('el_row_<?= $extra_login['customers_extra_logins_id']; ?>').toggle();">
											<input type="button" value="Delete" onClick="if(confirm('Are you sure you want to delete this login?')) window.location='/admin/customers_detail.php?<?= CK\fn::qs(['action']); ?>&action=extra_login_delete&customers_extra_logins_id=<?= $extra_login['customers_extra_logins_id']; ?>';">
											<input type="button" value="Separate" onClick="if(confirm('Are you sure you want to separate this login? All order history will remain with the current customer account.')) window.location='/admin/customers_detail.php?<?= CK\fn::qs(['action']); ?>&action=extra_login_separate&customers_extra_logins_id=<?= $extra_login['customers_extra_logins_id']; ?>';">
											<?php if (!empty($customer) && $_SESSION['perms']['use_master_password'] == 1) { ?>
											<a href="/admin/customers_list.php?customers_id=<?= $customer->id(); ?>&customers_extra_logins_id=<?= $extra_login['customers_extra_logins_id']; ?>&action=login-frontend" target="_blank" class="button-link">Cart &#8599;</a>
											<?php } ?>
										</td>
									</tr>
											<?php if ($error = $customer->get_process_errors('extra_logins', $extra_login['customers_extra_logins_id'])) { ?>
									<tr>
										<td class="main" colspan="5" style="color:#a00;"><?= $error; ?></td>
									</tr>
									<tr id="el_row_<?= $extra_login['customers_extra_logins_id']; ?>">
										<form action="/admin/customers_detail.php?<?= CK\fn::qs(['action']); ?>&action=extra_login_update" method="post">
											<input type="hidden" name="customers_extra_logins_id" value="<?= $extra_login['customers_extra_logins_id']; ?>">
											<input type="hidden" name="customers_id" value="<?= $customer->get_header('customers_id'); ?>">
											<td class="main"><input type="text" name="customers_email_address" value="<?= $_POST['customers_email_address']; ?>"></td>
											<td class="main"><input type="text" name="customers_password" value="<?= $_POST['customers_password']; ?>"></td>
											<td class="main"><input type="text" name="first_name" value="<?= $_POST['first_name']; ?>"></td>
											<td class="main"><input type="text" name="last_name" value="<?= $_POST['last_name']; ?>"></td>
											<td class="main"><input type="checkbox" name="copy_account" checked></td>
											<td class="main"><input type="submit" value="Update"></td>
										</form>
									</tr>
											<?php }
											else { ?>
									<tr id="el_row_<?= $extra_login['customers_extra_logins_id']; ?>" style="display:none;">
										<form action="/admin/customers_detail.php?<?= CK\fn::qs(['action']); ?>&action=extra_login_update" method="post">
											<input type="hidden" name="customers_extra_logins_id" value="<?= $extra_login['customers_extra_logins_id']; ?>">
											<input type="hidden" name="customers_id" value="<?= $customer->get_header('customers_id'); ?>">
											<td class="main"><input type="text" name="customers_email_address" value="<?= $extra_login['email_address']; ?>"></td>
											<td class="main"><input type="text" name="customers_password" value="*****"></td>
											<td class="main"><input type="text" name="first_name" value="<?= $extra_login['first_name']; ?>"></td>
											<td class="main"><input type="text" name="last_name" value="<?= $extra_login['last_name']; ?>"></td>
											<td class="main"><input type="checkbox" name="copy_account" <?=($extra_login['copy_account'] == 1)?'checked':'';?>></td>
											<td class="main"><input type="submit" value="Update"></td>
										</form>
									</tr>
											<?php }
										}
									}

									if ($error = $customer->get_process_errors('extra_logins', 'new')) { ?>
									<tr>
										<td class="main" colspan="5" style="color:#a00;"><?= $error; ?></td>
									</tr>
									<?php } ?>
									<tr>
										<?php if ($active_logins = $customer->get_extra_logins('inactive')) {
											foreach ($active_logins as $extra_login) { ?>
										<input type="hidden" class="inactive_email" value="<?= $extra_login['email_address']; ?>">
											<?php }
										} ?>
										<form action="/admin/customers_detail.php?<?= CK\fn::qs(['action']); ?>&action=extra_login_add" method="post">
											<input type="hidden" name="customers_id" value="<?= $customer->get_header('customers_id'); ?>">
											<input type="hidden" name="reactivate" id="reactivate" value="0">
											<td class="main"><input type="text" id="add-new-cel-email" name="customers_email_address" value="<?= $action=='extra_login_add'?@$_POST['customers_email_address']:''; ?>" maxlength="96"></td>
											<td class="main"><input type="text" name="customers_password" value="<?= $action=='extra_login_add'?@$_POST['customers_password']:''; ?>"></td>
											<td class="main"><input type="text" name="first_name" value="<?= $action=='extra_login_add'?@$_POST['first_name']:''; ?>" maxlength="32"></td>
											<td class="main"><input type="text" name="last_name" value="<?= $action=='extra_login_add'?@$_POST['last_name']:''; ?>" maxlength="32"></td>
											<td class="main"><input type="checkbox" name="copy_account" checked></td>
											<td class="main"><input type="submit" value="Add" id="add-new-cel"></td>
											<script type="text/javascript">
												jQuery('#add-new-cel').click(function(e) {
													var email = jQuery('#add-new-cel-email').val().toLowerCase();
													var found = false;
													jQuery('.inactive_email').each(function() {
														if (email == jQuery(this).val().toLowerCase()) found = true;
													});

													if (!found) return true;
													else if (confirm("The email address provided belonged to a previous extra login for this account that has been deactivated. Click OK to reactivate this extra login with the values provided on this form. Otherwise click Cancel to go back and change the email address.")) {
														jQuery('#reactivate').val(1);
														return true;
													}

													// default to nothing happening
													e.preventDefault();
													return false;
												});
											</script>
										</form>
									</tr>
								</table>
							</div>
						</div>
						<div class="customer-data">
							<h3>Convert Accounts To Extra Logins</h3>
							<div class="customer-data-box">
								<table cellspacing="2" cellpadding="2" border="0">
									<tr>
										<td class="main">
											Select an account to convert to an additional login for this account:
											<?= $customer->get_header('email_address'); ?>
										</td>
									</tr>
									<tr>
										<td class="main">
											Search by email address or name: <input id="conversion_search" type="text" size="64">
											<div id="conversion_search_results" class="autocomplete"></div>
											<script type="text/javascript">
												new Ajax.Autocompleter("conversion_search", "conversion_search_results", "customers_detail.php", {
													method: 'get',
													minChars: 4,
													paramName: 'search',
													parameters: 'action=extra_login_search&customers_id=<?= $_GET['customers_id']; ?>',
													afterUpdateElement: function(text, li) {
														info=li.id.split(':');
														$('conversion_customers_id').value=info[0];
														$('conversion_text').update(info[1]);
														$('conversion_confirm').show();
													}
												});
											</script>
										</td>
									</tr>
									<tr id="conversion_confirm" style="display:none">
										<td class="main">
											<form name="extra_logins_convert" action="/admin/customers_detail.php?<?= CK\fn::qs(['action']); ?>&action=extra_login_convert" method="post">
												<input type="hidden" name="conversion_customers_id" value="" id="conversion_customers_id">
												<input type="hidden" name="customers_id" value="<?= $customer->get_header('customers_id'); ?>" id="master_customers_id">
												Are you sure you want to convert the account: <span id="conversion_text">&nbsp</span> to an extra login account?
												<input type="button" value="Convert" onClick="show_conversion_compare_table();">
												<div id="conversion_compare_table" style="display: none; background-color: #fff; padding: 20px; border: 1px solid #000;"></div>
												<script type="text/javascript">
													function show_conversion_compare_table() {
														$('conversion_compare_table').update('');
														new Ajax.Updater('conversion_compare_table', 'customers_detail.php',{
															method: 'get',
															parameters: {
																action: 'extra_login_compare',
																customers_id: $F('master_customers_id'),
																conversion_customers_id: $F('conversion_customers_id')
															},
															onComplete: function() {
																var elt = $('conversion_compare_table');
																var eltDims = elt.getDimensions();
																var browserDims = document.body.getDimensions();
																var x = (browserDims.width - eltDims.width) / 2;
																var styles = { position : 'absolute', left : x + 'px' };
																elt.setStyle(styles);
																$('conversion_compare_table').show();}
														});
													}
												</script>
											</form>
										</td>
									</tr>
								</table>
							</div>
						</div>
						<?php $error = NULL; ?>
						<h3 id="account-management" style="font-size:12px; margin-bottom:0; padding-bottom:0;">Account Management</h3>
						<style>
							#account-management-block div { padding-top:5px; font-size:12px; margin:5px 0; }
						</style>
						<div id="account-management-block" class="customer-data" style="border:1px solid #7b9ebd; background-color:#f1f9fe; padding:5px; margin-top:2px;">
							<div>
								<label for="account_manager_id">Account Manager:</label>
								<?php if (!($user->is_top_admin() || $user->has_sales_team()) || $customer->has_process_errors('header')) {
										$account_manager_id = $customer->has_process_errors('header')?$_POST['account_manager_id']:$customer->get_header('account_manager_id');
										if ($account_manager_id == 0) echo 'None';
										else {
											foreach ($account_managers as $account_manager) {
												if ($account_manager->id() != $account_manager_id) continue;
												echo $account_manager->get_name();
												break;
											}
										} ?>
									<?php } else { ?>
									<form action="/admin/customers_detail.php?customers_id=<?=$customer->id(); ?>&action=update-account-manager" method="post">
										<select name="account_manager_id" id="account_manager_id" onchange="this.form.submit()">
											<option value="0">None</option>
											<?php foreach ($account_managers as $account_manager) { ?>
											<option value="<?= $account_manager->id(); ?>" <?= $account_manager->id()==$customer->get_header('account_manager_id')?'selected':''; ?>><?= $account_manager->get_normalized_name(); ?></option>
											<?php } ?>
										</select>
									</form>
									<?php } ?>
							</div>
							<div>
								<label for="sales_team_id">Sales Team:</label>
								<?php if ($customer->has_account_manager() || !($user->is_top_admin() || $user->has_sales_team()) || $customer->has_process_errors('header')) {
									$sales_team_id = $customer->has_process_errors('header')?$_POST['sales_team_id']:$customer->get_header('sales_team_id');
									if (empty($sales_team_id)) echo 'None';
									else {
										foreach ($sales_teams as $sales_team) {
											if ($sales_team->id() != $sales_team_id) continue;
											echo $sales_team->get_header('label');
											break;
										}
									} ?>
								<?php } else { ?>
									<form action="/admin/customers_detail.php?customers_id=<?=$customer->id(); ?>&action=update-sales-team" method="post">
									<select name="sales_team_id" id="sales_team_id" onchange="this.form.submit()">
										<option value="">None</option>
										<?php foreach ($sales_teams as $sales_team) { ?>
										<option value="<?= $sales_team->id(); ?>" <?= $sales_team->id()==$customer->get_header('sales_team_id')?'selected':''; ?>>
											<?= $sales_team->get_header('label'); ?>
										</option>
										<?php } ?>
									</select>
								</form>
								<?php } ?>

							</div>
							<div>
								<label for="customer_segment_id">Customer Segment:</label>
								<?php if ($customer->has_process_errors('header')) {
									echo $_POST['customer_segment_id']==0?'Not Indicated':'';
									echo $_POST['customer_segment_id']==ck_customer2::$customer_segment_map['IN']?'Individual':'';
									echo $_POST['customer_segment_id']==ck_customer2::$customer_segment_map['EU']?'End User':'';
									echo $_POST['customer_segment_id']==ck_customer2::$customer_segment_map['RS']?'Reseller':'';
									echo $_POST['customer_segment_id']==ck_customer2::$customer_segment_map['BD']?'Broker Dealer':'';
									echo $_POST['customer_segment_id']==ck_customer2::$customer_segment_map['EU']?'Marketplace':'';
									echo $_POST['customer_segment_id']==ck_customer2::$customer_segment_map['ST']?'Student':''; ?>
								<input type="hidden" name="customer_segment_id" value="<?= $_POST['customer_segment_id']; ?>">
								<?php } else { ?>
								<form action="/admin/customers_detail.php?customers_id=<?=$customer->id(); ?>&action=update-customer-segment" method="post">
								<select id="customer_segment_id" name="customer_segment_id" onchange="this.form.submit()">
									<option value="">Not Indicated</option>
									<?php $customer_segments = prepared_query::fetch('SELECT * FROM customer_segments', cardinality::SET);
									foreach ($customer_segments as $segment) { ?>
									<option value="<?= $segment['customer_segment_id']; ?>" <?= $customer->get_header('customer_segment_id')==$segment['customer_segment_id']?'selected':''; ?>><?= ucwords($segment['segment']); ?></option>
									<?php } ?>
								</select>
								</form>
								<?php } ?>
							</div>
							<div>
								<label>Customer Type (Legacy):</label>
								<?php $customer_types = [0 => 'Normal Customer', 1 => 'Dealer'];
								echo $customer_types[$customer->get_header('customer_type')]; ?>
							</div>
							<div>
								<label for="customer_price_level_id">Price Level:</label>
								<?php $price_levels = prepared_query::keyed_set_value_fetch('SELECT customer_price_level_id, price_level FROM customer_price_levels WHERE active = 1', 'customer_price_level_id', 'price_level');
								if ($customer->has_process_errors('header')) {
									echo $price_levels[$_POST['customer_price_level_id']]; ?>
								<input type="hidden" name="customer_price_level_id" value="<?= $_POST['customer_price_level_id']; ?>">
								<?php } else { ?>
								<form action="/admin/customers_detail.php?customers_id=<?=$customer->id(); ?>&action=update-price-level" method="post">
									<select id="customer_price_level_id" name="customer_price_level_id" onchange="this.form.submit()">
										<?php foreach ($price_levels as $customer_price_level_id => $price_level) { ?>
										<option value="<?= $customer_price_level_id; ?>" <?= $customer->get_header('customer_price_level_id')==$customer_price_level_id?'selected':''; ?>>
											<?= $price_level; ?>
										</option>
										<?php } ?>
									</select>
								</form>
								<?php } ?>
							</div>
							<div>
								<form action="/admin/customers_detail.php?customers_id=<?=$customer->id(); ?>&action=update-has-recieved-mousepad" method="post">
									<label for="has-received-mousepad">Has Received Mousepad</label>
									<input type="checkbox" id="has-received-mousepad" name="has_received_mousepad" <?= $customer->get_header('has_received_mousepad')==1?'checked':'';?> onchange="this.form.submit()">
								</form>
							</div>
						</div>
						<form id="customers-main-form" name="customers" action="/admin/customers_detail.php?<?= CK\fn::qs(['action']); ?>&action=update" method="post">
							<input type="hidden" name="default_address_id" value="<?= $customer->get_header('default_address_id'); ?>">
							<div class="customer-data">
								<h3>Personal</h3>
								<div class="customer-data-box">
									<table border="0" cellspacing="2" cellpadding="2">
										<tr>
											<td class="main">First Name:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header') && !($error = $customer->get_process_errors('header', 'first_name'))) {
													echo $_POST['first_name']; ?>
												<input type="hidden" name="first_name" value="<?= $_POST['first_name']; ?>">
												<?php }
												elseif (!empty($error)) { ?>
												<input type="text" name="first_name" value="<?= $_POST['first_name']; ?>" maxlength="32">
												<?= $error; ?>
												<?php }
												else { ?>
												<input type="text" name="first_name" value="<?= $customer->get_header('first_name'); ?>" maxlength="32">
												<span class="fieldRequired">* Required</span>
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">Last Name:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header') && !($error = $customer->get_process_errors('header', 'last_name'))) {
													echo $_POST['last_name']; ?>
												<input type="hidden" name="last_name" value="<?= $_POST['last_name']; ?>">
												<?php }
												elseif (!empty($error)) { ?>
												<input type="text" name="last_name" value="<?= $_POST['last_name']; ?>" maxlength="32">
												<?= $error; ?>
												<?php }
												else { ?>
												<input type="text" name="last_name" value="<?= $customer->get_header('last_name'); ?>" maxlength="32">
												<span class="fieldRequired">* Required</span>
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">Email Address:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header') && !($error = $customer->get_process_errors('header', 'customers_email_address'))) {
													echo $_POST['customers_email_address']; ?>
												<input type="hidden" name="customers_email_address" value="<?= $_POST['customers_email_address']; ?>">
												<?php }
												elseif (!empty($error)) { ?>
												<input type="text" name="customers_email_address" value="<?= $_POST['customers_email_address']; ?>" maxlength="96" size="50">
												<?= is_array($error)?implode('<br>', $error):$error; ?>
												<?php }
												else { ?>
												<input type="text" name="customers_email_address" value="<?= $customer->get_header('email_address'); ?>" maxlength="96" size="50">
												<span class="fieldRequired">* Required</span>
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">FEDEX Account Number:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo $_POST['fedex_account_number']; ?>
												<input type="hidden" name="fedex_account_number" value="<?= $_POST['fedex_account_number']; ?>">
												<?php }
												else { ?>
												<input type="text" name="fedex_account_number" value="<?= $customer->get_header('fedex_account_number'); ?>" maxlength="9" id="fedex_account_number">
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">UPS Account Number:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo $_POST['ups_account_number']; ?>
												<input type="hidden" name="ups_account_number" value="<?= $_POST['ups_account_number']; ?>">
												<?php }
												else { ?>
												<input type="text" name="ups_account_number" value="<?= $customer->get_header('ups_account_number'); ?>" maxlength="6" id="ups_account_number">
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">Send Late Notices:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo $_POST['send_late_notice']==1?'Yes':'No'; ?>
												<input type="hidden" name="send_late_notice" value="<?= $_POST['send_late_notice']; ?>">
												<?php }
												else { ?>
												<select id="send_late_notice" name="send_late_notice">
													<option value="1" <?= $customer->is('send_late_notice')?'selected':''; ?>>Yes</option>
													<option value="0" <?= !$customer->is('send_late_notice')?'selected':''; ?>>No</option>
												</select>
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">Offer Direct FEDEX/UPS Charging:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo $_POST['own_shipping_account']==1?'Yes':'No'; ?>
												<input type="hidden" name="own_shipping_account" value="<?= $_POST['own_shipping_account']; ?>">
												<?php }
												else { ?>
												<select name="own_shipping_account" id="own_shipping_account">
													<option value="0" <?= !$customer->is('own_shipping_account')?'selected':''; ?>>No</option>
													<option value="1" <?= $customer->is('own_shipping_account')?'selected':''; ?>>Yes</option>
												</select>
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">Disable Standard Shipping:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo $customer->failover($_POST, 'disable_standard_shipping', 'header')==1?'Yes':'No'; ?>
												<input type="hidden" name="disable_standard_shipping" value="<?= $customer->failover($_POST, 'disable_standard_shipping', 'header'); ?>">
												<?php }
												else { ?>
												<input type="checkbox" name="disable_standard_shipping" <?= $customer->is('disable_standard_shipping')?'checked':''; ?>>
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">Amazon Account:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo $customer->failover($_POST, 'amazon_account', 'header')==1?'Yes':'No'; ?>
												<input type="hidden" name="amazon_account" value="<?= $customer->failover($_POST, 'amazon_account', 'header'); ?>">
												<?php }
												else { ?>
												<input type="checkbox" name="amazon_account" <?= $customer->is('amazon_account')?'checked':''; ?>>
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">AIM Screen Name:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo $_POST['aim_screenname']; ?>
												<input type="hidden" name="aim_screenname" value="<?= $_POST['aim_screenname']; ?>">
												<?php }
												else { ?>
												<input type="text" name="aim_screenname" value="<?= $customer->get_header('aim_screenname'); ?>" maxlength="32">
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">MSN Screen Name:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo $_POST['msn_screenname']; ?>
												<input type="hidden" name="msn_screenname" value="<?= $_POST['msn_screenname']; ?>">
												<?php }
												else { ?>
												<input type="text" name="msn_screenname" value="<?= $customer->get_header('msn_screenname'); ?>" maxlength="32">
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">Accounting Contact Name:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo $_POST['company_account_contact_name']; ?>
												<input type="hidden" name="company_account_contact_name" value="<?= $_POST['company_account_contact_name']; ?>">
												<?php }
												else { ?>
												<input type="text" name="company_account_contact_name" value="<?= $customer->get_accounting_contact('name'); ?>" maxlength="32">
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">Accounting Contact Email:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo $_POST['company_account_contact_email']; ?>
												<input type="hidden" name="company_account_contact_email" value="<?= $_POST['company_account_contact_email']; ?>">
												<?php }
												else { ?>
												<input type="text" name="company_account_contact_email" value="<?= $customer->get_accounting_contact('email'); ?>" maxlength="96">
												<?php } ?>
												<script type="text/javascript" src="/admin/includes/javascript/customer_contact_manager.js?v=1"></script>
												<a style="color:blue; cursor:pointer;" id="ccm_init">Manage Addl Contacts</a>
												<div id="ccm_modal" class="jqmWindow" style="width: 800px;">
													<a class="jqmClose" href="#" style="float: right; clear: both;">X</a>
													<div id="ccm_modal_content" style="max-height: 600px; overflow: auto;"></div>
												</div>
											</td>
										</tr>
										<tr>
											<td class="main">Accounting Contact Phone Number:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo $_POST['company_account_contact_phone_number']; ?>
												<input type="hidden" name="company_account_contact_phone_number" value="<?= $_POST['company_account_contact_phone_number']; ?>">
												<?php }
												else { ?>
												<input type="text" name="company_account_contact_phone_number" value="<?= $customer->get_accounting_contact('phone'); ?>" maxlength="32">
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">
												Linked Vendor Account
												<a href="javascript: void();" onclick="jQuery('#edit_vendor').show();">Edit</a>
												<a href="javascript: void();" onclick="update_vendor('0')">Clear</a>
											</td>
											<td class="main">
												<div id="vendor_name">
													<?= $customer->has('vendor_id')?prepared_query::fetch('SELECT vendors_company_name FROM vendors WHERE vendors_id = ?', cardinality::SINGLE, $customer->get_header('vendor_id')):'None'; ?>
												</div>
												<div id="edit_vendor" style="display:none;">
													<input type="text" id="vendor_lookup" size="40">
												</div>
												<script type="text/javascript">
													jQuery('#vendor_lookup').autocomplete({
														minChars: 3,
														source: function(request, response) {
															jQuery.ajax({
																minLength: 2,
																url: '/admin/serials_ajax.php?action=vendor_autocomplete',
																dataType: 'json',
																data: {
																	term: request.term,
																},
																success: response
															});
														},
														select: function(event, ui) {
															update_vendor(ui.item.vendor_id);
															jQuery('#vendor_lookup').val('');
															jQuery('#edit_vendor').hide();
															return false;
														}
													});

													function update_vendor(vendor_id) {
														jQuery.get('customers_detail.php',
															{
																action: 'update_vendor',
																customers_id: '<?= $_GET['customers_id']; ?>',
																vID: vendor_id
															},
															function (data) {
																jQuery('#vendor_name').html(data);
															}
														);
													}
												</script>
											</td>
										</tr>
									</table>
								</div>
							</div>
							<div class="customer-data">
								<h3>Tax Exemptions</h3>
								<div class="customer-data-box">
									<table border="0" cellspacing="2" cellpadding="2">
										<tr>
											<td class="main">
												<style>
													#tax-toggle #tax-hide { display:none; }
													#tax-toggle.show #tax-show { display:none; }
													#tax-toggle.show #tax-hide { display:inline; }
												</style>
												<button type="button" id="tax-toggle"><span id="tax-show">Show</span><span id="tax-hide">Hide</span> Tax Exemptions</button>
												<small>(Customer is exempt in <?= count($customer->get_tax_exemptions()); ?> jurisdictions)</small>
												<script>
													jQuery('#tax-toggle').click(function() {
														jQuery('.tax-exemption-status').toggle();
														jQuery(this).toggleClass('show');
													});
												</script>
											</td>
										</tr>
										<tr>
											<td>
												<style>
													.tax-exemption-status { border-collapse:collapse; font-size:12px; margin:10px; display:none; }
													.tax-exemption-status caption { text-align:left; padding-bottom:4px; font-weight:bold; }
													.tax-exemption-status thead th { border-style:solid; border-color:#000; border-width:2px 0px 2px 0px; padding:4px 25px 4px 6px; text-align:left; background-color:#ddd; }
													.tax-exemption-status tbody td { border-bottom:1px solid #000; padding:4px 6px; background-color:#fff; }
													/*.tax-exemption-status tbody tr:first-child td { background-color:#cff; }*/
												</style>
												<table class="tax-exemption-status">
													<thead>
														<tr>
															<th>State</th>
															<th>Country</th>
															<th>Status</th>
															<th>Change Date</th>
														</tr>
													</thead>
													<tbody>
														<?php $tax_liabilities = prepared_query::fetch('SELECT stl.countries_id, stl.zone_id, stl.sales_tax_liable, c.countries_iso_code_2, z.zone_code, z.zone_name FROM ck_sales_tax_liabilities stl JOIN countries c ON stl.countries_id = c.countries_id JOIN zones z ON stl.zone_id = z.zone_id ORDER BY c.countries_iso_code_2 ASC, z.zone_name ASC');
														foreach ($tax_liabilities as $tax_liability) {
															$te = $customer->get_tax_exemptions($tax_liability['zone_id']); ?>
														<tr>
															<td><?= $tax_liability['zone_name']; ?></td>
															<td><?= $tax_liability['countries_iso_code_2']; ?></td>
															<td>
																<?php if ($admin_perms['update_pay_tax'] == 0) {
																	if (!empty($te['tax_exempt'])) echo 'Tax Exempt';
																	else echo 'Tax Liable';
																}
																elseif (!empty($te)) { ?>
																<select name="tax_exemption[<?= $te['customer_tax_exemption_id']; ?>]">
																	<option value="1" <?= $te['tax_exempt']?'selected':''; ?>>Tax Exempt</option>
																	<option value="0" <?= !$te['tax_exempt']?'selected':''; ?>>Tax Liable</option>
																</select>
																<?php }
																else { ?>
																<select name="add_tax_exemption[<?= $tax_liability['zone_id']; ?>]">
																	<option value="1">Tax Exempt</option>
																	<option value="0" selected>Tax Liable</option>
																</select>
																<?php } ?>
															</td>
															<td><?= !empty($te)?$te['exemption_created']->format('m/d/Y'):''; ?></td>
														</tr>
														<?php } ?>
													</tbody>
												</table>
											</td>
										</tr>
									</table>
								</div>
							</div>
							<div class="customer-data">
								<h3>Credit</h3>
								<div class="customer-data-box">
									<table border="0" cellspacing="2" cellpadding="2">
										<tr>
											<td class="main">Credit Status:</td>
											<td class="main">
												<?php if ($admin_perms['update_net_terms'] == 0 || $customer->has_process_errors('header')) {
													$credit_status_id = $customer->has_process_errors('header')?$_POST['credit_status_id']:$customer->get_header('credit_status_id');
													echo ck_customer2::$credit_statuses[$credit_status_id]; ?>
												<input type="hidden" name="credit_status_id" value="<?= $credit_status_id; ?>">
												<?php }
												else { ?>
												<select name="credit_status_id" id="credit_status_id">
													<?php foreach (ck_customer2::$credit_statuses as $id => $label) { ?>
													<option value="<?= $id; ?>" <?= $customer->get_header('credit_status_id')==$id?'selected':''; ?>><?= $label; ?></option>
													<?php } ?>
												</select>
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main" colspan="2">
												Comment on Credit Status:<br>
												<textarea name="credit_status_comment" cols="60" rows="4"></textarea>
											</td>
										</tr>
										<tr>
											<td class="main">Credit Limit:</td>
											<td class="main">
												<?php if ($admin_perms['update_net_terms'] == 0 || $customer->has_process_errors('header')) {
													$credit_limit = $customer->has_process_errors('header')?$_POST['credit_limit']:$customer->get_header('credit_limit');
													echo CK\text::monetize($credit_limit); ?>
												<input type="hidden" name="credit_limit" value="<?= $credit_limit; ?>">
												<?php }
												else { ?>
												<input type="text" name="credit_limit" value="<?= $customer->get_header('credit_limit'); ?>">
												[<?= CK\text::monetize($customer->get_remaining_credit()); ?> Left]
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">Net Terms:</td>
											<td class="main">
												<?php if ($admin_perms['update_net_terms'] == 0 || $customer->has_process_errors('header')) {
													$legacy_dealer_pay_module = $customer->has_process_errors('header')?$_POST['legacy_dealer_pay_module']:$customer->get_header('legacy_dealer_pay_module');
													echo $legacy_dealer_pay_module==0?'None':'';
													echo $legacy_dealer_pay_module==1?'NET 10':'';
													echo $legacy_dealer_pay_module==2?'NET 15':'';
													echo $legacy_dealer_pay_module==3?'NET 30':'';
													echo $legacy_dealer_pay_module==4?'NET 45':'';
													echo $legacy_dealer_pay_module==4?'NET 60':'';?>
												<input type="hidden" name="legacy_dealer_pay_module" value="<?= $legacy_dealer_pay_module; ?>">
												<?php }
												else { ?>
												<select name="legacy_dealer_pay_module" id="legacy_dealer_pay_module">
													<option value="0">None</option>
													<option value="1" <?= $customer->get_header('legacy_dealer_pay_module')==1?'selected':''; ?>>NET 10</option>
													<option value="2" <?= $customer->get_header('legacy_dealer_pay_module')==2?'selected':''; ?>>NET 15</option>
													<option value="3" <?= $customer->get_header('legacy_dealer_pay_module')==3?'selected':''; ?>>NET 30</option>
													<option value="4" <?= $customer->get_header('legacy_dealer_pay_module')==4?'selected':''; ?>>NET 45</option>
													<option value="5" <?= $customer->get_header('legacy_dealer_pay_module')==5?'selected':''; ?>>NET 60</option>
												</select>
												<a style="color:blue; cursor:pointer;" onclick="aon_init('<?= $customer->get_header('customers_id'); ?>', ''); return false;">Accounting Notes</a>
												<script type="text/javascript" src="/admin/includes/javascript/accounting_notes.js"></script>
												<div id="aon_modal" class="jqmWindow" style="width: 800px;">
													<a class="jqmClose" href="#" style="float: right; clear: both;">X</a>
													<div id="aon_modal_content" style="max-height: 600px; overflow: auto;"></div>
												</div>
												<input type="hidden" id="aon_customers_id" value="">
												<input type="hidden" id="aon_order_id" value="">
													<?php /*}*/
												} ?>
											</td>
										</tr>
										<tr>
											<td colspan="2">
												<style>
													.credit-status-history { border-collapse:collapse; font-size:12px; margin-top:10px; }
													.credit-status-history caption { text-align:left; padding-bottom:4px; font-weight:bold; }
													.credit-status-history thead th { border-style:solid; border-color:#000; border-width:2px 0px 2px 0px; padding:4px 25px 4px 6px; text-align:left; background-color:#ddd; }
													.credit-status-history tbody td { border-bottom:1px solid #000; padding:4px 6px; background-color:#fff; }
													.credit-status-history tbody tr:first-child td { background-color:#cff; }
												</style>
												<table class="credit-status-history">
													<caption>Credit Status History</caption>
													<thead>
														<tr>
															<th>Status Date</th>
															<th>Status</th>
															<th>Terms</th>
															<th>Credit Limit</th>
															<th>Comments</th>
															<th>Admin</th>
														</tr>
													</thead>
													<tbody>
														<?php if ($customer->has_credit_status_history()) {
															foreach ($customer->get_credit_status_history() as $history) { ?>
														<tr>
															<td><?= $history['status_date']->format('m/d/Y<\b\r>H:i:s'); ?></td>
															<td><?= $history['credit_status']; ?></td>
															<td><?= $history['terms']; ?></td>
															<td><?= $history['credit_limit']; ?></td>
															<td><?= $history['comments']; ?></td>
															<td><?= !empty($history['admin'])?$history['admin']->get_name():'Not Recorded'; ?></td>
														</tr>
															<?php }
														}
														else { ?>
														<tr>
															<th colspan="6">No History</th>
														</tr>
														<?php } ?>
													</tbody>
												</table>
											</td>
										</tr>
									</table>
								</div>
							</div>
							<?php
							if (!($address = $customer->get_addresses('default'))) {
								$address = array(
									'address_book_id' => NULL,
									'customers_id' => NULL,
									'gender' => NULL,
									'company_name' => NULL,
									'first_name' => NULL,
									'last_name' => NULL,
									'address1' => NULL,
									'address2' => NULL,
									'postcode' => NULL,
									'city' => NULL,
									'state' => NULL,
									'countries_id' => NULL,
									'country' => NULL,
									'countries_iso_code_2' => NULL,
									'countries_iso_code_3' => NULL,
									'country_address_format_id' => NULL,
									'country_default_postcode' => NULL,
									'zone_id' => NULL,
									'state_region_code' => NULL,
									'state_region_name' => NULL,
									'region_country_match' => NULL,
									'telephone' => NULL,
									'website' => NULL,
									'default_address' => NULL
								);
							}
							else $address = $address->get_header();
							?>
							<div class="customer-data">
								<h3>Company</h3>
								<div class="customer-data-box">
									<table border="0" cellspacing="2" cellpadding="2">
										<tr>
											<td class="main">Company Name:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header') && !($error = $customer->get_process_errors('header', 'company_name'))) {
													echo $_POST['company_name']; ?>
												<input type="hidden" name="company_name" value="<?= $_POST['company_name']; ?>">
												<?php }
												elseif (!empty($error)) { ?>
												<input type="text" name="company_name" value="<?= $_POST['company_name']; ?>" maxlength="32">
												<?= $error; ?>
												<?php }
												else { ?>
												<input type="text" name="company_name" value="<?= $address['company_name']; ?>" maxlength="32">
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">Company Website</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo $_POST['website']; ?>
												<input type="hidden" name="website" value="<?= $_POST['website']; ?>">
												<?php }
												else { ?>
												<input type="text" name="website" value="<?= $address['website']; ?>" maxlength="128">
												<?php } ?>
											</td>
										</tr>
									</table>
								</div>
							</div>
							<div class="customer-data">
								<h3>Address</h3>
								<div class="customer-data-box">
									<table border="0" cellspacing="2" cellpadding="2">
										<tr>
											<td class="main">Street 1:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header') && !($error = $customer->get_process_errors('header', 'address1'))) {
													echo $_POST['address1']; ?>
												<input type="hidden" name="address1" value="<?= $_POST['address1']; ?>">
												<?php }
												elseif (!empty($error)) { ?>
												<input type="text" name="address1" value="<?= $_POST['address1']; ?>" maxlength="64">
												<?= $error; ?>
												<?php }
												else { ?>
												<input type="text" name="address1" value="<?= $address['address1']; ?>" maxlength="64">
												<span class="fieldRequired">* Required</span>
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">Street 2:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header') && !($error = $customer->get_process_errors('header', 'address2'))) {
													echo $_POST['address2']; ?>
												<input type="hidden" name="address2" value="<?= $_POST['address2']; ?>">
												<?php }
												elseif (!empty($error)) { ?>
												<input type="text" name="address2" value="<?= $_POST['address2']; ?>" maxlength="32">
												<?= $error; ?>
												<?php }
												else { ?>
												<input type="text" name="address2" value="<?= $address['address2']; ?>" maxlength="32">
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">Post Code:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header') && !($error = $customer->get_process_errors('header', 'postcode'))) {
													echo $_POST['postcode']; ?>
												<input type="hidden" name="postcode" value="<?= $_POST['postcode']; ?>">
												<?php }
												elseif (!empty($error)) { ?>
												<input type="text" name="postcode" value="<?= $_POST['postcode']; ?>" maxlength="8">
												<?= $error; ?>
												<?php }
												else { ?>
												<input type="text" name="postcode" value="<?= $address['postcode']; ?>" maxlength="8">
												<span class="fieldRequired">* Required</span>
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">City:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header') && !($error = $customer->get_process_errors('header', 'city'))) {
													echo $_POST['city']; ?>
												<input type="hidden" name="city" value="<?= $_POST['city']; ?>">
												<?php }
												elseif (!empty($error)) { ?>
												<input type="text" name="city" value="<?= $_POST['city']; ?>" maxlength="32">
												<?= $error; ?>
												<?php }
												else { ?>
												<input type="text" name="city" value="<?= $address['city']; ?>" maxlength="32">
												<span class="fieldRequired">* Required</span>
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">State:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header') && !($error = $customer->get_process_errors('header', 'state'))) {
													echo $_POST['state']; ?>
												<input type="hidden" name="state" value="<?= $_POST['state']; ?>">
												<?php }
												elseif (!empty($error)) {
													if ($states = ck_address2::get_regions($_POST['countries_id'])) { ?>
												<select name="state">
													<option>SELECT STATE</option>
													<?php foreach ($states as $state) { ?>
													<option value="<?= $state['zone_name']; ?>" <?= in_array(strtolower($_POST['state']), array(strtolower($state['zone_name']), strtolower($state['zone_code'])))?'selected':''; ?>><?= $state['zone_name']; ?></option>
													<?php } ?>
												</select>
													<?php }
													else { ?>
												<input type="text" name="state" value="<?= $_POST['state']; ?>">
													<?php } ?>
												<?= $error; ?>
												<?php }
												else {
													$state = !empty($address['state_region_name'])?$address['state_region_name']:$address['state']; ?>
												<input type="text" name="state" value="<?= $state; ?>">
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">Country:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header') && !($error = $customer->get_process_errors('header', 'countries_id'))) {
													echo ck_address2::get_country($_POST['countries_id'])['countries_name']; ?>
												<input type="hidden" name="countries_id" value="<?= $_POST['countries_id']; ?>">
												<?php }
												elseif (!empty($error)) { ?>
												<select name="countries_id">
													<option>SELECT COUNTRY</option>
													<?php foreach (ck_address2::get_countries() as $country) { ?>
													<option value="<?= $country['countries_id']; ?>" <?= $country['countries_id']==$_POST['countries_id']?'selected':''; ?>><?= $country['countries_name']; ?></option>
													<?php } ?>
												</select>
												<?= $error; ?>
												<?php }
												else { ?>
												<select name="countries_id">
													<option>SELECT COUNTRY</option>
													<?php foreach (ck_address2::get_countries() as $country) { ?>
													<option value="<?= $country['countries_id']; ?>" <?= $country['countries_id']==$address['countries_id']?'selected':''; ?>><?= $country['countries_name']; ?></option>
													<?php } ?>
												</select>
												<?php } ?>
											</td>
										</tr>
									</table>
								</div>
							</div>
							<div class="customer-data">
								<h3>Contact</h3>
								<div class="customer-data-box">
									<table border="0" cellspacing="2" cellpadding="2">
										<tr>
											<td class="main">Telephone Number:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header') && !($error = $customer->get_process_errors('header', 'telephone'))) {
													echo $_POST['telephone']; ?>
												<input type="hidden" name="telephone" value="<?= $_POST['telephone']; ?>">
												<?php }
												elseif (!empty($error)) { ?>
												<input type="text" name="telephone" value="<?= $_POST['telephone']; ?>" maxlength="32">
												<?= $error; ?>
												<?php }
												else { ?>
												<input type="text" name="telephone" value="<?= $customer->get_header('telephone'); ?>" maxlength="32">
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">Fax Number:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo $_POST['fax']; ?>
												<input type="hidden" name="fax" value="<?= $_POST['fax']; ?>">
												<?php }
												else { ?>
												<input type="text" name="fax" value="<?= $customer->get_header('fax'); ?>" maxlength="32">
												<?php } ?>
											</td>
										</tr>
									</table>
								</div>
							</div>
							<div class="customer-data">
								<h3>Options</h3>
								<div class="customer-data-box">
									<table border="0" cellspacing="2" cellpadding="2">
										<tr>
											<td class="main">Newsletter:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo CK\fn::check_flag(@$_POST['newsletter_subscribed'])?'Subscribed':'Unsubscribed'; ?>
												<input type="hidden" name="newsletter_subscribed" value="<?= $_POST['newsletter_subscribed']; ?>">
												<?php }
												else { ?>
												<select name="newsletter_subscribed">
													<option value="0" <?= !$customer->is('newsletter_subscribed')?'selected':''; ?>>Unsubscribed</option>
													<option value="1" <?= $customer->is('newsletter_subscribed')?'selected':''; ?>>Subscribed</option>
												</select>
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td valign="top" class="main">Master Order Notes:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo $_POST['notes']; ?>
												<input type="hidden" name="notes" value="<?= htmlspecialchars($_POST['notes']); ?>">
												<?php }
												else { ?>
												<textarea id="notes" name="notes" wrap="soft" cols="75" rows="5"><?= $customer->get_header('notes'); ?></textarea>
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td valign="top" class="main">Customer Notes:</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo $_POST['sales_rep_notes']; ?>
												<input type="hidden" name="sales_rep_notes" value="<?= htmlspecialchars($_POST['sales_rep_notes']); ?>">
												<?php }
												else { ?>
												<textarea id="sales_rep_notes" name="sales_rep_notes" wrap="soft" cols="75" rows="5"><?= $customer->get_header('sales_rep_notes'); ?></textarea>
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">Change Password:</td>
											<td class="main">
												<?php /*if ($customer->has_process_errors('header')) {
													echo $_POST['password']; ?>
												<input type="hidden" name="password" value="<?= $_POST['password']; ?>">
												<?php }
												else { ?>
												<input type="text" name="password" autocomplete="off">
												<?php }*/ ?>
												<strong>Advise customer to complete the "forgot your password" process</strong>
											</td>
										</tr>
										<tr>
											<td class="main">Access Vendor Portal Accessory Finder</td>
											<td class="main">
												<?php if ($customer->has_process_errors('header')) {
													echo $customer->failover($_POST, 'vendor_portal_accessory_finder', 'header')==1?'Yes':'No'; ?>
												<input type="hidden" name="vendor_portal_accessory_finder" value="<?= $customer->failover($_POST, 'vendor_portal_accessory_finder', 'header'); ?>">
												<?php }
												else { ?>
												<input type="checkbox" name="vendor_portal_accessory_finder" <?= $customer->is_allowed('vendor_portal.accessory_finder')?'checked':''; ?>>
												<?php } ?>
											</td>
										</tr>
										<tr>
											<td class="main">Use Reclaimed Packaging Materials</td>
											<td class="main">
												<input type="checkbox" name="use_reclaimed_packaging" <?= $customer->is('use_reclaimed_packaging')?'checked':''; ?>>
											</td>
										</tr>
									</table>
								</div>
							</div>
							<div style="text-align:right;padding-top:4px;">
								<input type="image" src="/admin/includes/languages/english/images/buttons/button_update.gif" alt="Update" title="Update">
								<a href="/admin/customers_detail.php?<?= CK\fn::qs(['action']); ?>"><img src="/admin/includes/languages/english/images/buttons/button_cancel.gif" alt="Cancel" title="Cancel"></a>
							</div>
						</form>
						<script>
							jQuery('#customers-main-form').submit(function(e) {
								if (!check_form()) {
									e.preventDefault();
									return false;
								}
								return true;
							});
						</script>

						<div class="customer-data">
							<h3>Customer Specific Pricing</h3>
							<div class="customer-data-box" style="padding:4px 6px;">
								<script src="/admin/includes/javascript/category_discount_manager.js?v=1"></script>
								<style>
									#cdm_content { font-size:12px; padding:10px; margin:10px 10px 40px 10px; border:1px solid #686868; }
									.manage-pricing-details { border-bottom:1px solid #000; margin-bottom:4px; font-size:12px; text-align:left; }
									.manage-pricing-details th { text-decoration:underline; vertical-align:bottom; font-size:12px; text-align:left; font-weight:normal; width:70px; }
									.manage-pricing-details td { width:70px; }
									.manage-pricing-details .pricing-ipn { width:340px; }
									.pricing-warn { background-color:#fcc; }
									.pricing-attn { background-color:#ffc; }
									.pricing-delete { text-align:center; }
								</style>

								<h3>Category Level Discounts</h3>
								<div id="cdm_content"></div>

								<form id="manage-pricing" name="pricing" action="/admin/customers_detail.php?<?= http_build_query(CK\fn::filter_request($_GET, ['action'])); ?>&action=pricing" method="post">
									<h3>Special Pricing</h3>
									<table border="0" cellspacing="2" cellpadding="2" class="manage-pricing-details">
										<thead>
											<tr>
												<th class="pricing-ipn">IPN</th>
												<th>Cost</th>
												<th>Retail $</th>
												<th>Reseller $</th>
												<th>Wholesale High $</th>
												<th>Wholesale Low $</th>
												<th>Current $</th>
												<th>New $</th>
												<th>[Delete]</th>
											</tr>
										</thead>
										<tbody>
											<?php if ($prices = $customer->get_prices()) {
												foreach ($prices as $price) {
													if ($price['managed_category']) continue;
													$ipn = new ck_ipn2($price['stock_id']); ?>
											<tr>
												<td><?= $ipn->get_header('ipn'); ?></td>
												<td><?= CK\text::monetize($ipn->get_header('average_cost')); ?></td>
												<td><?= CK\text::monetize($ipn->get_price('list')); ?></td>
												<td><?= CK\text::monetize($ipn->get_price('dealer')); ?></td>
												<td><?= CK\text::monetize($ipn->get_price('wholesale_high')); ?></td>
												<td><?= CK\text::monetize($ipn->get_price('wholesale_low')); ?></td>
												<td class="pricing-warn"><?= CK\text::monetize($price['price']); ?></td>
												<td><input type="text" name="p_price[<?= $ipn->id(); ?>]" value="<?= $price['price']; ?>" size="7"></td>
												<td class="pricing-delete">[<input type="checkbox" name="delete_special[<?= $ipn->id(); ?>]">]</td>
											</tr>
													<?php if ($listings = $ipn->get_listings()) {
														foreach ($listings as $listing) { ?>
											<tr>
												<td><?= $listing->get_header('products_name'); ?></td>
												<td colspan="8">Special Price: $<?= $listing->is('on_special')?number_format($listing->get_price('special'), 2):'-----'; ?></td>
											</tr>
														<?php }
													}
												}
											} ?>
										</tbody>
									</table>
									<div style="text-align:right">
										[<input type="checkbox" name="delete_pricing"> Delete All Custom Prices]<br>
										<input type="image" src="/admin/includes/languages/english/images/buttons/button_update.gif" alt="Update" title="Update">
										<a href="/admin/customers_detail.php?<?= CK\fn::qs(['action']); ?>"><img src="/admin/includes/languages/english/images/buttons/button_cancel.gif" alt="Cancel" title="Cancel"></a>
									</div>
								</form>
								<form id="ipn_pricing_add" action="/admin/customers_detail.php?<?= CK\fn::qs(['action']); ?>&action=ipn_add_pricing" method="post">
									<input type="hidden" id="ipn_add_stock_id" name="stock_id">
									<form id="manage-pricing" name="pricing" action="/admin/customers_detail.php?<?= http_build_query(CK\fn::filter_request($_GET, ['action'])); ?>&action=pricing" method="post">
									<table border="0" cellspacing="2" cellpadding="2" class="manage-pricing-details">
										<tbody>
											<tr id="ipn_add" style="display:none;">
												<td class="pricing-ipn"><span id="ipn_add_stock_name"></span></td>
												<td><span id="ipn_add_average_cost"></span></td>
												<td><span id="ipn_add_stock_price"></span></td>
												<td>-------</td>
												<td><input type="text" size="6" name="ipn_add_price"></td>
												<td><input type="submit" value="Add"></form></td>
											</tr>
											<tr>
												<td class="main" colspan="6">
													Add an IPN:
													<input id="ipn_search" type="text" size="32">
													<div id="ipn_search_results" class="autocomplete"></div>
													<script type="text/javascript">
														new Ajax.Autocompleter("ipn_search", "ipn_search_results", "customers_detail.php", {
															method: 'get',
															minChars: 4,
															paramName: 'search',
															parameters: 'action=ipn_search&customers_id=<?= $customer->get_header('customers_id'); ?>',
															afterUpdateElement: function(text, li) {
																info=li.id.split(':');
																$('ipn_add_stock_id').value=info[0]
																$('ipn_add_stock_name').update(info[1]);
																$('ipn_add_average_cost').update(parseFloat(info[2]).toFixed(2));
																$('ipn_add_stock_price').update(parseFloat(info[3]).toFixed(2));
																$('ipn_add').show();
															}
														});
													</script>
												</td>
											</tr>
										</tbody>
									</table>
								</form>
								<script>
									jQuery('#manage-pricing').submit(function(e) {
										if (!check_form()) {
											e.preventDefault();
											return false;
										}
										return true;
									});
								</script>
							</div>
						</div>
					</div>
				</td>
			</tr>
		</table>
		<!-- body_eof //-->
		<script>
			ck.button_links();

			jQuery('#set-fraud-flag-button').click(function () {
				var proceed = confirm("Are you sure you want to set this customer as fraud? \n By doing so you will also move all active orders to accounting hold!");
				if (proceed) {
					window.location.href='/admin/customers_detail.php?customers_id='+jQuery(this).attr('data-customers-id')+'&action=set-fraud-flag';
				}
			});

			jQuery('#remove-fraud-flag-button').click(function () {
				var proceed = confirm("Are you sure you want to remove the fraud flag? By doing so you will make all this customers orders shippable and allow the customer to place orders in the future.");
				if (proceed) {
					window.location.href='/admin/customers_detail.php?customers_id='+jQuery(this).attr('data-customers-id')+'&action=remove-fraud-flag';
				}
			});
		</script>
	</body>
</html>
