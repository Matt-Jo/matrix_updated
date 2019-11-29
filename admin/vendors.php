<?php
require('includes/application_top.php');

function tep_rand($min = null, $max = null) {
	static $seeded;

	if (empty($seeded)) {
		mt_srand((double)microtime()*1000000);
		$seeded = true;
	}

	if (isset($min) && isset($max)) {
		if ($min >= $max) {
			return $min;
		} else {
			return mt_rand($min, $max);
		}
	} else {
		return mt_rand();
	}
}

function make_PW($plain) {
	$password = '';

	for ($i=0; $i<10; $i++) {
		$password .= tep_rand();
	}

	$salt = substr(md5($password), 0, 2);
	$password = md5($salt.$plain).':'.$salt;
	return $password;
}

$action = !empty($_GET['action'])?$_GET['action']:'';

$error = FALSE;
$processed = FALSE;

if ($action) {
	switch ($action) {
		case 'update':
			$aim_screenname = @$_POST['aim_screenname'];
			$msn_screenname = @$_POST['msn_screenname'];
			$company_account_contact_name = @$_POST['company_account_contact_name'];
			$company_account_contact_email = @$_POST['company_account_contact_email'];
			$company_account_contact_phone_number = @$_POST['company_account_contact_phone_number'];

			$company_web_address = @$_POST['company_web_address'];
			$company_payment_terms = @$_POST['company_payment_terms'];

			$vendors_id = @$_GET['vendors_id'];
			$tid = @$_POST['tid'];

			$vendors_fedex = @$_POST['vendors_fedex'];
			$vendors_ups = @$_POST['vendors_ups'];
			$carrier_preference = @$_POST['carrier_preference'];

			$vendor_type = @$_POST['vendor_type'];

			$dealer_pay_module = @$_POST['dealer_pay_module'];
			$dealer_shipping_module = @$_POST['dealer_shipping_module'];

			$vendors_company_name = @$_POST['vendors_company_name'];
			$vendors_lastname = @$_POST['vendors_lastname'];
			$vendors_email_address = @$_POST['vendors_email_address'];
			$vendors_telephone = @$_POST['vendors_telephone'];
			$vendors_fax = @$_POST['vendors_fax'];
			$vendors_newsletter = @$_POST['vendors_newsletter'];

			$vendors_password = @$_POST['vendors_password'];

			$vendors_gender = @$_POST['vendors_gender'];
			$vendors_dob = @$_POST['vendors_dob'];

			// START Admin Notes
			$vendors_notes = @$_POST['vendors_notes'];
			// END Admin Notes


			$default_address_id = @$_POST['default_address_id'];
			$entry_street_address = @$_POST['entry_street_address'];
			$entry_suburb = @$_POST['entry_suburb'];
			$entry_postcode = @$_POST['entry_postcode'];
			$entry_city = @$_POST['entry_city'];
			$entry_country_id = @$_POST['entry_country_id'];

			$entry_state = @$_POST['entry_state'];
			if (isset($_POST['entry_zone_id'])) $entry_zone_id = @$_POST['entry_zone_id'];

			$entry_firstname_error = false;
			$entry_lastname_error = false;

			if (strlen($vendors_email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
				$error = true;
				$entry_email_address_error = true;
			}
			else {
				$entry_email_address_error = false;
			}

			if (!empty($vendors_email_address) && !service_locator::get_mail_service()::validate_address($vendors_email_address)) {
				$error = true;
				$entry_email_address_check_error = true;
			}
			else {
				$entry_email_address_check_error = false;
			}

			if (strlen($entry_street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
				$error = true;
				$entry_street_address_error = true;
			}
			else {
				$entry_street_address_error = false;
			}

			if (strlen($entry_postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
				$error = true;
				$entry_post_code_error = true;
			}
			else {
				$entry_post_code_error = false;
			}

			if (strlen($entry_city) < ENTRY_CITY_MIN_LENGTH) {
				$error = true;
				$entry_city_error = true;
			}
			else {
				$entry_city_error = false;
			}

			if ($entry_country_id == false) {
				$error = true;
				$entry_country_error = true;
			}
			else {
				$entry_country_error = false;
			}

			if (ACCOUNT_STATE == 'true') {
				if ($entry_country_error == true) {
					$entry_state_error = true;
				}
				else {
					$zone_id = 0;
					$entry_state_error = false;
					$check_value = prepared_query::fetch('SELECT COUNT(*) as total FROM zones WHERE zone_country_id = ?', cardinality::ROW, $entry_country_id);
					$entry_state_has_zones = $check_value['total']>0;
					if ($entry_state_has_zones == true) {
						$zone_query = prepared_query::fetch('SELECT zone_id FROM zones WHERE zone_country_id = ? AND zone_name = ?', cardinality::SET, array($entry_country_id, $entry_state));
						if (count($zone_query) == 1) {
							$entry_zone_id = $zone_query[0]['zone_id'];
						}
						else {
							$error = true;
							$entry_state_error = true;
						}
					}
					else {
						if ($entry_state == false) {
							$error = true;
							$entry_state_error = true;
						}
					}
				}
			}

			if (strlen($vendors_telephone) < ENTRY_TELEPHONE_MIN_LENGTH) {
				$error = true;
				$entry_telephone_error = true;
			}
			else {
				$entry_telephone_error = false;
			}

			$check_email = prepared_query::fetch('SELECT vendors_email_address FROM vendors WHERE vendors_email_address = ? AND vendors_id != ?', cardinality::SET, array($vendors_email_address, $vendors_id));
			if (count($check_email)) {
				$error = true;
				$entry_email_address_exists = true;
			}
			else {
				$entry_email_address_exists = false;
			}

			if ($error == false) {
				$sql_data_array = array(
					'vendors_company_name' => $vendors_company_name,
					'vendors_lastname' => $vendors_lastname,
					'vendors_email_address' => $vendors_email_address,
					'vendors_telephone' => $vendors_telephone,
					'vendors_fax' => $vendors_fax,
					'vendors_notes' => $vendors_notes, // START \ END Admin Notes
					'vendors_newsletter' => $vendors_newsletter,
					'templates_id' => $tid,
					'vendor_type' => $vendor_type,
					'vendors_fedex' => $vendors_fedex,
					'vendors_ups' => $vendors_ups,
					'carrier_preference' => $carrier_preference,
					'dealer_pay_module' => $dealer_pay_module,
					'dealer_shipping_module' => $dealer_shipping_module,
					'aim_screenname' => $aim_screenname,
					'msn_screenname' => $msn_screenname,
					'company_web_address' => $company_web_address,
					'company_payment_terms' => $company_payment_terms,
					'company_account_contact_name' => $company_account_contact_name,
					'company_account_contact_email' => $company_account_contact_email,
					'company_account_contact_phone_number' => $company_account_contact_phone_number
				);

				if (ACCOUNT_GENDER == 'true') $sql_data_array['vendors_gender'] = $vendors_gender;

				$params = new ezparams($sql_data_array);

				prepared_query::execute('UPDATE vendors SET '.$params->update_cols.' WHERE vendors_id = ?', $params->query_vals($vendors_id));
				prepared_query::execute('UPDATE vendors_info SET vendors_info_date_account_last_modified = NOW() WHERE vendors_info_id = ?', $vendors_id);

				if ($vendors_password != '') {
					$vendors_password = make_PW($vendors_password);
					prepared_query::execute('UPDATE vendors SET vendors_password = ? WHERE vendors_id = ?', array($vendors_password, $vendors_id));
				}

				if ($entry_zone_id > 0) $entry_state = '';

				$sql_data_array = array(
					'entry_firstname' => $vendors_company_name,
					'entry_lastname' => $vendors_lastname,
					'entry_street_address' => $entry_street_address,
					'entry_postcode' => $entry_postcode,
					'entry_city' => $entry_city,
					'entry_country_id' => $entry_country_id
				);

				$sql_data_array['entry_company'] = $vendors_company_name;
				$sql_data_array['entry_suburb'] = $entry_suburb;

				if ($entry_zone_id > 0) {
					$sql_data_array['entry_zone_id'] = $entry_zone_id;
					$sql_data_array['entry_state'] = '';
				}
				else {
					$sql_data_array['entry_zone_id'] = '0';
					$sql_data_array['entry_state'] = $entry_state;
				}

				$params = new ezparams($sql_data_array);

				prepared_query::execute('UPDATE address_book_vendors SET '.$params->update_cols.' WHERE vendors_id = ? AND address_book_id = ?', $params->query_vals(array($vendors_id, $default_address_id)));

				CK\fn::redirect_and_exit('/admin/vendors.php?'.tep_get_all_get_params(array('vendors_id', 'action')).'vendors_id='.$vendors_id);

			}
			elseif ($error == true) {
				$cInfo = (object)$_POST;
				$processed = true;
			}

			break;
		case 'deleteconfirm':
			$vendors_id = $_GET['vendors_id'];

			prepared_query::execute("delete from address_book_vendors where vendors_id = :vendors_id", [':vendors_id' => $vendors_id]);
			prepared_query::execute("delete from vendors where vendors_id = :vendors_id", [':vendors_id' => $vendors_id]);
			prepared_query::execute("delete from vendors_info where vendors_info_id = :vendors_id", [':vendors_id' => $vendors_id]);
			CK\fn::redirect_and_exit('/admin/vendors.php?'.tep_get_all_get_params(array('vendors_id', 'action')));

			break;
		default:
			$vendors = prepared_query::fetch("select c.company_web_address, c.company_payment_terms, pot.text as vendor_terms, c.aim_screenname, c.msn_screenname, c.company_account_contact_name, c.company_account_contact_email, c.company_account_contact_phone_number, c.dealer_pay_module, c.dealer_shipping_module, c.vendors_fedex, c.vendors_ups, c.carrier_preference, c.vendor_type, c.templates_id, c.vendors_notes, c.vendors_id, c.vendors_gender, c.vendors_company_name, c.vendors_lastname, c.vendors_dob, c.vendors_email_address, a.entry_company, a.entry_street_address, a.entry_suburb, a.entry_postcode, a.entry_city, a.entry_state, a.entry_zone_id, a.entry_country_id, c.vendors_telephone, c.vendors_fax, c.vendors_newsletter, c.vendors_default_address_id from vendors c left join address_book_vendors a on c.vendors_default_address_id = a.address_book_id LEFT JOIN purchase_order_terms pot on c.company_payment_terms = pot.id where a.vendors_id = c.vendors_id and c.vendor_type = 0 and c.vendors_id = :vendors_id", cardinality::ROW, [':vendors_id' => $_GET['vendors_id']]);
			$cInfo = (object)$vendors;
	}
}

//$types_array = array(array('id' => '', 'text' => 'Not Applicable'));
$types_array[] = ['id' => '0', 'text' => 'Normal Customer'];
$types_array[] = ['id' => '1', 'text' => 'Dealer'];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<script language="javascript" src="includes/menu.js"></script>
		<script language="javascript" src="includes/general.js"></script>
		<?php if ($action == 'edit' || $action == 'update') { ?>
		<script language="javascript">
			function check_form() {
				var error = 0;
				var error_message = "<?php echo JS_ERROR; ?>";

				var vendors_firstname = document.vendors.vendors_firstname.value;
				var vendors_lastname = document.vendors.vendors_lastname.value;
				<?php if (ACCOUNT_COMPANY == 'true') echo 'var entry_company = document.vendors.entry_company.value;'."\n"; ?>
				<?php if (ACCOUNT_DOB == 'true') echo 'var vendors_dob = document.vendors.vendors_dob.value;'."\n"; ?>
				var vendors_email_address = document.vendors.vendors_email_address.value;
				var entry_street_address = document.vendors.entry_street_address.value;
				var entry_postcode = document.vendors.entry_postcode.value;
				var entry_city = document.vendors.entry_city.value;
				var vendors_telephone = document.vendors.vendors_telephone.value;

				<?php if (ACCOUNT_GENDER == 'true') { ?>
				if (document.vendors.vendors_gender[0].checked || document.vendors.vendors_gender[1].checked) {
				} else {
					error_message = error_message + "<?php echo JS_GENDER; ?>";
					error = 1;
				}
				<?php } ?>

				if (vendors_firstname == "" || vendors_firstname.length < <?php echo ENTRY_FIRST_NAME_MIN_LENGTH; ?>) {
					error_message = error_message + "<?php echo JS_FIRST_NAME; ?>";
					error = 1;
				}

				if (vendors_lastname == "" || vendors_lastname.length < <?php echo ENTRY_LAST_NAME_MIN_LENGTH; ?>) {
					error_message = error_message + "<?php echo JS_LAST_NAME; ?>";
					error = 1;
				}

				<?php if (ACCOUNT_DOB == 'true') { ?>
				if (vendors_dob == "" || vendors_dob.length < <?php echo ENTRY_DOB_MIN_LENGTH; ?>) {
					error_message = error_message + "<?php echo JS_DOB; ?>";
					error = 1;
				}
				<?php } ?>

				if (vendors_email_address == "" || vendors_email_address.length < <?php echo ENTRY_EMAIL_ADDRESS_MIN_LENGTH; ?>) {
					error_message = error_message + "<?php echo JS_EMAIL_ADDRESS; ?>";
					error = 1;
				}

				if (entry_street_address == "" || entry_street_address.length < <?php echo ENTRY_STREET_ADDRESS_MIN_LENGTH; ?>) {
					error_message = error_message + "<?php echo JS_ADDRESS; ?>";
					error = 1;
				}

				if (entry_postcode == "" || entry_postcode.length < <?php echo ENTRY_POSTCODE_MIN_LENGTH; ?>) {
					error_message = error_message + "<?php echo JS_POST_CODE; ?>";
					error = 1;
				}

				if (entry_city == "" || entry_city.length < <?php echo ENTRY_CITY_MIN_LENGTH; ?>) {
					error_message = error_message + "<?php echo JS_CITY; ?>";
					error = 1;
				}

				<?php
				if (ACCOUNT_STATE == 'true') {
					?>
					if (document.vendors.elements['entry_state'].type != "hidden") {
						if (document.vendors.entry_state.value == '' || document.vendors.entry_state.value.length < <?php echo ENTRY_STATE_MIN_LENGTH; ?> ) {
							error_message = error_message + "<?php echo JS_STATE; ?>";
							error = 1;
						}
					}
					<?php
				}
				?>

				if (document.vendors.elements['entry_country_id'].type != "hidden") {
					if (document.vendors.entry_country_id.value == 0) {
						error_message = error_message + "<?php echo JS_COUNTRY; ?>";
						error = 1;
					}
				}

				if (vendors_telephone == "" || vendors_telephone.length < <?php echo ENTRY_TELEPHONE_MIN_LENGTH; ?>) {
					error_message = error_message + "<?php echo JS_TELEPHONE; ?>";
					error = 1;
				}

				if (error == 1) {
					alert(error_message);
					return false;
				} else {
					return true;
				}
			}
		</script>
		<?php } ?>
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onLoad="SetFocus();">
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
					<table border="0" width="800" cellspacing="0" cellpadding="2">
						<?php if ($action == 'edit' || $action == 'update') {
							$newsletter_array = [
								['id' => '0', 'text' => ENTRY_NEWSLETTER_NO],
								['id' => '1', 'text' => ENTRY_NEWSLETTER_YES]
							];

							$tm_array = [
								['id' => '0', 'text' => 'No'],
								['id' => '1', 'text' => 'Yes']
							];

							$pay_array = [
								['id' => '0', 'text' => 'No'],
								['id' => '1', 'text' => 'Yes']
							];

							$shipping_array = [
								['id' => '0', 'text' => 'No'],
								['id' => '1', 'text' => 'Yes']
							]; ?>
						<tr>
							<td>
								<table border="0" width="100%" cellspacing="0" cellpadding="0">
									<tr>
										<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
										<td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
						</tr>
						<tr>
							<td>
								<?php echo tep_draw_form('vendors', FILENAME_VENDORS, tep_get_all_get_params(array('action')).'action=update', 'post', 'onSubmit="return check_form();"').tep_draw_hidden_field('default_address_id', @$cInfo->vendors_default_address_id); ?>
									<div class="formArea">
										<table border="0" cellspacing="2" cellpadding="2">
											<tr>
												<td class="formAreaTitle"><?php echo 'Company Information'; ?></td>
											</tr>
											<tr>
												<td class="formAreaTitle"><?php echo '&nbsp;'; ?></td>
											</tr>
											<tr>
												<td class="main">Company Name:</td>
												<td class="main">
													<?php if ($error == true) {
														echo $cInfo->vendors_company_name.tep_draw_hidden_field('vendors_company_name');
													}
													else {
														echo tep_draw_input_field('vendors_company_name', $cInfo->vendors_company_name, 'maxlength="32"');
													} ?>
												</td>
											</tr>
											<tr>
												<td class="main"><?php echo ENTRY_STREET_ADDRESS; ?></td>
												<td class="main">
													<?php if ($error == true) {
														if ($entry_street_address_error == true) {
															echo tep_draw_input_field('entry_street_address', $cInfo->entry_street_address, 'maxlength="64"').'&nbsp;'.ENTRY_STREET_ADDRESS_ERROR;
														}
														else {
															echo $cInfo->entry_street_address.tep_draw_hidden_field('entry_street_address');
														}
													}
													else {
														echo tep_draw_input_field('entry_street_address', $cInfo->entry_street_address, 'maxlength="64"', true);
													} ?>
												</td>
											</tr>
											<?php if (ACCOUNT_SUBURB == 'true') { ?>
											<tr>
												<td class="main"><?php echo ENTRY_SUBURB; ?></td>
												<td class="main">
													<?php if ($error == true) {
														echo $cInfo->entry_suburb.tep_draw_hidden_field('entry_suburb');
													}
													else {
														echo tep_draw_input_field('entry_suburb', $cInfo->entry_suburb, 'maxlength="32"');
													} ?>
												</td>
											</tr>
											<?php } ?>
											<tr>
												<td class="main"><?php echo ENTRY_POST_CODE; ?></td>
												<td class="main">
													<?php if ($error == true) {
														if ($entry_post_code_error == true) {
															echo tep_draw_input_field('entry_postcode', $cInfo->entry_postcode, 'maxlength="8"').'&nbsp;'.ENTRY_POST_CODE_ERROR;
														}
														else {
															echo $cInfo->entry_postcode.tep_draw_hidden_field('entry_postcode');
														}
													}
													else {
														echo tep_draw_input_field('entry_postcode', $cInfo->entry_postcode, 'maxlength="8"', true);
													} ?>
												</td>
											</tr>
											<tr>
												<td class="main"><?php echo ENTRY_CITY; ?></td>
												<td class="main">
													<?php if ($error == true) {
														if ($entry_city_error == true) {
															echo tep_draw_input_field('entry_city', $cInfo->entry_city, 'maxlength="32"').'&nbsp;'.ENTRY_CITY_ERROR;
														}
														else {
															echo $cInfo->entry_city.tep_draw_hidden_field('entry_city');
														}
													}
													else {
														echo tep_draw_input_field('entry_city', $cInfo->entry_city, 'maxlength="32"', true);
													} ?>
												</td>
											</tr>
											<?php if (ACCOUNT_STATE == 'true') { ?>
											<tr>
												<td class="main"><?php echo ENTRY_STATE; ?></td>
												<td class="main">
													<?php $entry_state = ck_address2::legacy_get_zone_field($cInfo->entry_country_id, @$cInfo->entry_zone_id, $cInfo->entry_state, 'zone_name');
													if ($error == true) {
														if ($entry_state_error == true) {
															if ($entry_state_has_zones == true) {
																$zones_array = array();
																$zones = prepared_query::fetch("select zone_name from zones where zone_country_id = :country_id order by zone_name", cardinality::COLUMN, [':country_id' => $cInfo->entry_country_id]);
																foreach ($zones as $zones_values) {
																	$zones_array[] = array('id' => $zones_values, 'text' => $zones_values);
																}
																echo tep_draw_pull_down_menu('entry_state', $zones_array).'&nbsp;'.ENTRY_STATE_ERROR;
															}
															else {
																echo tep_draw_input_field('entry_state', ck_address2::legacy_get_zone_field($cInfo->entry_country_id, $cInfo->entry_zone_id, $cInfo->entry_state, 'zone_name')).'&nbsp;'.ENTRY_STATE_ERROR;
															}
														}
														else {
															echo $entry_state.tep_draw_hidden_field('entry_zone_id').tep_draw_hidden_field('entry_state');
														}
													}
													else {
														echo tep_draw_input_field('entry_state', ck_address2::legacy_get_zone_field($cInfo->entry_country_id, $cInfo->entry_zone_id, $cInfo->entry_state, 'zone_name'));
													} ?>
												</td>
											</tr>
											<?php } ?>
											<tr>
												<td class="main"><?php echo ENTRY_COUNTRY; ?></td>
												<td class="main">
													<?php if ($error == true) {
														if ($entry_country_error == true) {
															echo tep_draw_pull_down_menu('entry_country_id', tep_get_countries(), $cInfo->entry_country_id).'&nbsp;'.ENTRY_COUNTRY_ERROR;
														}
														else {
															echo ck_address2::legacy_get_country_field($cInfo->entry_country_id, 'countries_name').tep_draw_hidden_field('entry_country_id');
														}
													}
													else {
														echo tep_draw_pull_down_menu('entry_country_id', tep_get_countries(), $cInfo->entry_country_id);
													} ?>
												</td>
											</tr>
											<tr>
												<td class="main"><?php echo ENTRY_TELEPHONE_NUMBER; ?></td>
												<td class="main">
													<?php if ($error == true) {
														if ($entry_telephone_error == true) {
															echo tep_draw_input_field('vendors_telephone', $cInfo->vendors_telephone, 'maxlength="32"').'&nbsp;'.ENTRY_TELEPHONE_NUMBER_ERROR;
														}
														else {
															echo $cInfo->vendors_telephone.tep_draw_hidden_field('vendors_telephone');
														}
													}
													else {
														echo tep_draw_input_field('vendors_telephone', $cInfo->vendors_telephone, 'maxlength="32"', true);
													} ?>
												</td>
											</tr>
											<tr>
												<td class="main"><?php echo ENTRY_FAX_NUMBER; ?></td>
												<td class="main">
													<?php if ($processed == true) {
														echo $cInfo->vendors_fax.tep_draw_hidden_field('vendors_fax');
													}
													else {
														echo tep_draw_input_field('vendors_fax', $cInfo->vendors_fax, 'maxlength="32"');
													} ?>
												</td>
											</tr>
											<tr>
												<td class="main"><?php echo 'Web Address:'; ?></td>
												<td class="main">
													<?php if ($processed == true) {
														echo $cInfo->company_web_address.tep_draw_hidden_field('company_web_address');
													}
													else {
														echo tep_draw_input_field('company_web_address', $cInfo->company_web_address, 'maxlength="96"');
													} ?>
												</td>
											</tr>
											<tr>
												<td class="main"><?php echo 'Email PO\'s to:'; ?></td>
												<td class="main">
													<?php if ($error == true) {
														if ($entry_email_address_error == true) {
															echo tep_draw_input_field('vendors_email_address', $cInfo->vendors_email_address, 'maxlength="96" size="50"').'&nbsp;'.ENTRY_EMAIL_ADDRESS_ERROR;
														}
														elseif ($entry_email_address_check_error == true) {
															echo tep_draw_input_field('vendors_email_address', $cInfo->vendors_email_address, 'maxlength="96" size="50"').'&nbsp;'.ENTRY_EMAIL_ADDRESS_CHECK_ERROR;
														}
														elseif ($entry_email_address_exists == true) {
															echo tep_draw_input_field('vendors_email_address', $cInfo->vendors_email_address, 'maxlength="96" size="50"').'&nbsp;'.ENTRY_EMAIL_ADDRESS_ERROR_EXISTS;
														}
														else {
															echo $vendors_email_address.tep_draw_hidden_field('vendors_email_address');
														}
													}
													else {
														echo tep_draw_input_field('vendors_email_address', $cInfo->vendors_email_address, 'maxlength="96" size="50"', true);
													} ?>
												</td>
											</tr>
											<tr>
												<td class="main"><?php echo 'Payment Terms:'; ?></td>
												<td class="main">
													<?php if ($processed == true) {
														echo $cInfo->company_payment_terms.tep_draw_hidden_field('company_payment_terms');
													}
													else {
														echo tep_draw_pull_down_menu('company_payment_terms', tep_get_po_terms(), $cInfo->company_payment_terms);
													} ?>
												</td>
											</tr>
											<tr>
												<td class="main">Carrier Preference:</td>
												<td class="main">
													<?php if (!empty($processed)) { ?>
														<?= $cInfo->carrier_preference; ?><input type="hidden" name="carrier_preference" value="<?= $cInfo->carrier_preference; ?>">
													<?php }
													else { ?>
													<select name="carrier_preference">
														<option value="">None</option>
														<option value="UPS" <?= $cInfo->carrier_preference=='UPS'?'selected':''; ?>>UPS</option>
														<option value="Fedex" <?= $cInfo->carrier_preference=='Fedex'?'selected':''; ?>>Fedex</option>
													</select>
													<?php } ?>
												</td>
											</tr>
											<tr>
												<td class="main">UPS Account #:</td>
												<td class="main">
													<?php if (!empty($processed)) { ?>
													<?= $cInfo->vendors_ups; ?><input type="hidden" name="vendors_ups" value="<?= $cInfo->vendors_ups; ?>">
													<?php }
													else { ?>
													<input type="text" name="vendors_ups" value="<?= $cInfo->vendors_ups; ?>">
													<?php } ?>
												</td>
											</tr>
											<tr>
												<td class="main">Fedex Account #:</td>
												<td class="main">
													<?php if (!empty($processed)) { ?>
													<?= $cInfo->vendors_fedex; ?><input type="hidden" name="vendors_fedex" value="<?= $cInfo->vendors_fedex; ?>">
													<?php }
													else { ?>
													<input type="text" name="vendors_fedex" value="<?= $cInfo->vendors_fedex; ?>">
													<?php } ?>
												</td>
											</tr>
											<tr>
												<td valign="top" class="main">Vendor Notes:</td>
												<td class="main">
													<?php if ($processed == true) {
														echo $cInfo->vendors_notes.tep_draw_hidden_field('vendors_notes');
													}
													else {
														echo tep_draw_textarea_field('vendors_notes', 'soft', '75', '5', ($cInfo->vendors_notes));
													} ?>
												</td>
											</tr>
											<tr>
												<td class="formAreaTitle"><?php echo '&nbsp;'; ?></td>
											</tr>
											<tr>
												<td class="formAreaTitle"><?php echo 'Contact Information'; ?></td>
											</tr>
											<tr>
												<td class="formAreaTitle"><?php echo '&nbsp;'; ?></td>
											</tr>
											<tr>
												<td class="main"><?php echo 'Contact Name:'; ?></td>
												<td class="main">
													<?php if ($processed == true) {
														echo $cInfo->company_account_contact_name.tep_draw_hidden_field('company_account_contact_name');
													}
													else {
														echo tep_draw_input_field('company_account_contact_name', $cInfo->company_account_contact_name, 'maxlength="32"');
													} ?>
												</td>
											</tr>
											<tr>
												<td class="main"><?php echo 'Contact Phone Number:'; ?></td>
												<td class="main">
													<?php if ($processed == true) {
														echo $cInfo->company_account_contact_phone_number.tep_draw_hidden_field('company_account_contact_phone_number');
													}
													else {
														echo tep_draw_input_field('company_account_contact_phone_number', $cInfo->company_account_contact_phone_number, 'maxlength="32"');
													} ?>
												</td>
											</tr>
											<tr>
												<td class="main"><?php echo 'Contact Email:'; ?></td>
												<td class="main">
													<?php if ($processed == true) {
														echo $cInfo->company_account_contact_email.tep_draw_hidden_field('company_account_contact_email');
													}
													else {
														echo tep_draw_input_field('company_account_contact_email', $cInfo->company_account_contact_email, 'maxlength="32"');
													} ?>
												</td>
											</tr>
											<tr>
												<td class="main"><?php echo 'AIM Screen Name:'; ?></td>
												<td class="main">
													<?php if ($processed == true) {
														echo $cInfo->aim_screenname.tep_draw_hidden_field('aim_screenname');
													}
													else {
														echo tep_draw_input_field('aim_screenname', $cInfo->aim_screenname, 'maxlength="32"');
													} ?>
												</td>
											</tr>
											<tr>
												<td class="main"><?php echo 'MSN Screen Name:'; ?></td>
												<td class="main">
													<?php if ($processed == true) {
														echo $cInfo->msn_screenname.tep_draw_hidden_field('msn_screenname');
													}
													else {
														echo tep_draw_input_field('msn_screenname', $cInfo->msn_screenname, 'maxlength="32"');
													} ?>
												</td>
											</tr>
										</table>
									</div>
									<div style="text-align:right; margin-top:5px;">

										<?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE).' <a href="/admin/vendors.php?'.tep_get_all_get_params(array('action')).'bulk_discount=0&chosen_template=0'.'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>'; ?>

									</div>
								</form>
							</td>
						</tr>
						<?php }
						else { ?>
						<tr>
							<td>
								<?php echo tep_draw_form('search', FILENAME_VENDORS, NULL, 'get'); ?>
									<table border="0" width="100%" cellspacing="0" cellpadding="0">
										<tr>
											<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
											<td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
											<td class="smallText" align="right"><?php echo HEADING_TITLE_SEARCH.' '.tep_draw_input_field('search'); ?></td>
										</tr>
									</table>
								</form>
							</td>
						</tr>
						<tr>
							<td>
								<table border="0" width="100%" cellspacing="0" cellpadding="0">
									<tr>
										<td valign="top">
											<table border="0" width="100%" cellspacing="0" cellpadding="2">
												<tr class="dataTableHeadingRow">
													<td class="dataTableHeadingContent">Vendor</td>
													<td class="dataTableHeadingContent">PO Options</td>
													<td class="dataTableHeadingContent" align="right">Action</td>
												</tr>
												<?php $search = NULL;
												if (isset($_GET['search']) && tep_not_null($_GET['search'])) {
													$search = '%'.$_GET['search'].'%';
												}

												$vendor_count = prepared_query::fetch('SELECT COUNT(vendors_id) FROM vendors WHERE vendor_type != 1 and (:search IS NULL OR vendors_lastname like :search or vendors_company_name like :search or vendors_email_address like :search)', cardinality::SINGLE, [':search' => $search]);

												$batch_size = 300;

												$page = !empty($_GET['page'])?$_GET['page']:1;
												$start_point = (($page-1)*$batch_size)+1;
												$end_point = min($page*$batch_size, $vendor_count);
												$max_page = ceil($vendor_count / $batch_size);

												$limit = new prepared_limit;
												$limit->set_batch_size($batch_size);
												$limit->set_start_point($start_point-1);

												$vendors_list = prepared_query::fetch('select c.vendor_type, c.dealer_pay_module, c.dealer_shipping_module, c.vendors_fedex, c.vendors_ups, c.carrier_preference, c.aim_screenname, c.company_web_address, c.company_payment_terms, pot.text as vendor_terms, c.msn_screenname, c.company_account_contact_name, c.company_account_contact_email, c.company_account_contact_phone_number, c.vendors_notes, c.vendors_id, c.vendors_lastname, c.vendors_company_name, c.vendors_email_address, a.entry_country_id from vendors c left join address_book_vendors a on c.vendors_id = a.vendors_id LEFT JOIN purchase_order_terms pot on c.company_payment_terms = pot.id and c.vendors_default_address_id = a.address_book_id where c.vendor_type != 1 and (:search IS NULL OR c.vendors_lastname like :search or c.vendors_company_name like :search or c.vendors_email_address like :search) order by c.vendors_company_name asc LIMIT '.$limit->limit(), cardinality::SET, [':search' => $search]);

												foreach ($vendors_list as $vendors) {
													$info = prepared_query::fetch("select vendors_info_date_account_created as date_account_created, vendors_info_date_account_last_modified as date_account_last_modified, vendors_info_date_of_last_logon as date_last_logon, vendors_info_number_of_logons as number_of_logons from vendors_info where vendors_info_id = :vendors_id", cardinality::ROW, [':vendors_id' => $vendors['vendors_id']]);

													if ((!isset($_GET['vendors_id']) || (isset($_GET['vendors_id']) && ($_GET['vendors_id'] == $vendors['vendors_id']))) && !isset($cInfo)) {
														$country = prepared_query::fetch("select countries_name from countries where countries_id = :countries_id", cardinality::ROW, [':countries_id' => $vendors['entry_country_id']]);

														$vendor_info = array_merge($country, $info);

														$cInfo_array = array_merge($vendors, $vendor_info);
														$cInfo = (object)$cInfo_array;
													}

													if (isset($cInfo) && is_object($cInfo) && ($vendors['vendors_id'] == $cInfo->vendors_id)) { ?>

												<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href='<?= '/admin/vendors.php?'.tep_get_all_get_params(array('vendors_id', 'action')).'vendors_id='.$cInfo->vendors_id.'&action=edit'; ?>'">
													<?php }
													else { ?>
												<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href='<?= '/admin/vendors.php?'.tep_get_all_get_params(array('vendors_id')).'vendors_id='.$vendors['vendors_id']; ?>'">
													<?php }

													if (!empty($_GET['show_pos']) && $_GET['show_pos'] == 'yes' && isset($cInfo) && is_object($cInfo) && ($vendors['vendors_id'] == $cInfo->vendors_id)) { ?>
													<td class="dataTableContent"><a href="<?= '/admin/vendors.php?vendors_id='.$vendors['vendors_id'].'&show_pos=no'; ?>">-</a>&nbsp;<?= $vendors['vendors_company_name']; ?></td>
													<?php }
													else { ?>
													<td class="dataTableContent"><a href="<?= '/admin/vendors.php?vendors_id='.$vendors['vendors_id'].'&show_pos=yes'; ?>">+</a>&nbsp;<?= $vendors['vendors_company_name']; ?></td>
													<?php } ?>

													<td class="dataTableContent"><a href="po_list.php?vendor_search=<?= $vendors['vendors_id']; ?>">View Purchase Orders</a></td>

													<td class="dataTableContent" align="right">
														<a href="<?= '/admin/vendors.php?vendors_id='.$vendors['vendors_id'].'&action=edit'; ?>" title="Edit Vendor"><?php echo tep_image(DIR_WS_ICONS.'preview.gif', ICON_PREVIEW); ?></a>&nbsp;<a href="<?= '/admin/vendors.php?vendors_id='.$vendors['vendors_id'].'&action=confirm'; ?>" title="Delete Vendor"><font color="#ff0000" size="2"><b>X</b></font></a>&nbsp;&nbsp;&nbsp;

													</td>
												</tr>
												<?php } ?>

												<tr>
													<td colspan="4">
														<table border="0" width="100%" cellspacing="0" cellpadding="2">
															<tr>
																<td class="smallText" valign="top">
																	Displaying <strong><?= $start_point; ?></strong> of <strong><?= $end_point; ?></strong> (of <strong><?= $vendor_count; ?></strong> vendors)
																</td>
																<td class="smallText" align="right">
																	<?php if ($start_point == 1) { ?>
																	&lt;&lt;
																	<?php }
																	else { ?>
																	<a href="/admin/vendors.php?page=<?= $page-1; ?><?= !empty($_GET['search'])?'&search='.$_GET['search']:''; ?>" class="splitPageLink">&lt;&lt;</a>
																	<?php } ?>

																	<form name="pages" action="/admin/vendors.php" method="get">
																		Page
																		<select id="page" name="page" onchange="this.form.submit();">
																			<?php for ($i=1; $i<= $max_page; $i++) { ?>
																			<option value="<?= $i; ?>" id="1" <?= $i==$page?'selected':''; ?>><?= $i; ?></option>
																			<?php } ?>
																		</select>
																	</form>

																	<?php if ($end_point == $vendor_count) { ?>
																	&gt;&gt;
																	<?php }
																	else { ?>
																	<a href="/admin/vendors.php?page=<?= $page+1; ?><?= !empty($_GET['search'])?'&search='.$_GET['search']:''; ?>" class="splitPageLink">&gt;&gt;</a>
																	<?php } ?>
																</td>
															</tr>
															<?php if (isset($_GET['search']) && tep_not_null($_GET['search'])) { ?>
															<tr>
																<td align="right" colspan="2"><?php echo '<a href="/admin/vendors.php">'.tep_image_button('button_reset.gif', IMAGE_RESET).'</a>'; ?></td>

															</tr>
															<?php } ?>
														</table>
													</td>
												</tr>
											</table>
										</td>
										<?php $heading = array();
										$contents = array();

										switch ($action) {
											case 'confirm':
												$heading[] = array('text' => '<b>'.TEXT_INFO_HEADING_DELETE_VENDOR.'</b>');

												$contents = array('form' => tep_draw_form('vendors', FILENAME_VENDORS, tep_get_all_get_params(array('vendors_id', 'action')).'vendors_id='.$cInfo->vendors_id.'&action=deleteconfirm'));
												$contents[] = array('text' => TEXT_DELETE_INTRO.'<br><br><b>'.$cInfo->vendors_firstname.' '.$cInfo->vendors_lastname.'</b>');
												if (isset($cInfo->number_of_reviews) && ($cInfo->number_of_reviews) > 0) $contents[] = array('text' => '<br>'.tep_draw_checkbox_field('delete_reviews', 'on', true).' '.sprintf(TEXT_DELETE_REVIEWS, $cInfo->number_of_reviews));
												$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_delete.gif', IMAGE_DELETE).' <a href="/admin/vendors.php?'.tep_get_all_get_params(array('vendors_id', 'action')).'vendors_id='.$cInfo->vendors_id.'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');
												break;
											default:

												break;
										}

										if ((tep_not_null($heading)) && (tep_not_null($contents))) { ?>
										<td width="35%" valign="top">
											<?php $box = new box;
											echo $box->infoBox($heading, $contents); ?>
										</td>
										<?php } ?>
									</tr>
								</table>
							</td>
						</tr>
						<?php } ?>
					</table>
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
</html>
