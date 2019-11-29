<?php
require('includes/application_top.php');
require(DIR_WS_LANGUAGES.$_SESSION['language'].'/'.FILENAME_CREATE_VENDOR_ACCOUNT_PROCESS);

if (!@$_POST['action']) {
	CK\fn::redirect_and_exit('/admin/create_vendor_account.php');
}

$gender = @$_POST['gender'];
$firstname = @$_POST['firstname'];
$lastname = @$_POST['lastname'];
$dob = @$_POST['dob'];
$email_address = $_POST['email_address'];
$telephone = $_POST['telephone'];
$fax = $_POST['fax'];
$newsletter = @$_POST['newsletter'];
$password = @$_POST['password'];
$confirmation = @$_POST['confirmation'];
$street_address = $_POST['street_address'];
$vendors_company_name = @$_POST['vendors_company_name'];
$suburb = $_POST['suburb'];
$postcode = $_POST['postcode'];
$city = $_POST['city'];
$zone_id = @$_POST['zone_id'];
$state = $_POST['state'];
$country = $_POST['country'];
$aim_screenname = $_POST['aim_screenname'];
$msn_screenname = $_POST['msn_screenname'];
$company_account_contact_name = $_POST['company_account_contact_name'];
$company_account_contact_email = $_POST['company_account_contact_email'];
$company_account_contact_phone_number = $_POST['company_account_contact_phone_number'];
$company_web_address = $_POST['company_web_address'];
$company_payment_terms = $_POST['company_payment_terms'];
$vendors_notes = $_POST['vendors_notes'];

/////////////////	RAMDOMIZING SCRIPT BY PATRIC VEVERKA		\\\\\\\\\\\\\\\\\\
$t1 = date("mdy");
srand ((float) microtime() * 10000000);
$input = array ("A", "a", "B", "b", "C", "c", "D", "d", "E", "e", "F", "f", "G", "g", "H", "h", "I", "i", "J", "j", "K", "k", "L", "l", "M", "m", "N", "n", "O", "o", "P", "p", "Q", "q", "R", "r", "S", "s", "T", "t", "U", "u", "V", "v", "W", "w", "X", "x", "Y", "y", "Z", "z");
$rand_keys = array_rand ($input, 3);
$l1 = $input[$rand_keys[0]];
$r1 = rand(0,9);
$l2 = $input[$rand_keys[1]];
$l3 = $input[$rand_keys[2]];
$r2 = rand(0,9);

$password = $l1.$r1.$l2.$l3.$r2;
/////////////////	End of Randomizing Script	\\\\\\\\\\\\\\\\\\\

$error = false; // reset error flag

if (ACCOUNT_GENDER == 'true') {
	if (($gender == 'm') || ($gender == 'f')) {
		$entry_gender_error = false;
	}
	else {
		$error = true;
		$entry_gender_error = true;
	}
}

$entry_firstname_error = false;

$entry_lastname_error = false;

if (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
	$error = true;
	$entry_email_address_error = true;
}
else {
	$entry_email_address_error = false;
}

if (!service_locator::get_mail_service()::validate_address($email_address)) {
	$error = true;
	$entry_email_address_check_error = true;
}
else {
	$entry_email_address_check_error = false;
}

if (strlen($street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
	$error = true;
	$entry_street_address_error = true;
}
else {
	$entry_street_address_error = false;
}

 if (strlen($postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
	$error = true;
	$entry_post_code_error = true;
}
else {
	$entry_post_code_error = false;
}

 if (strlen($city) < ENTRY_CITY_MIN_LENGTH) {
	$error = true;
	$entry_city_error = true;
}
else {
	$entry_city_error = false;
}

 if (empty($country)) {
	$error = true;
	$entry_country_error = true;
}
else {
	$entry_country_error = false;
}

if (ACCOUNT_STATE == 'true') {
	if (!empty($entry_country_error)) {
		$entry_state_error = true;
	}
	else {
		$zone_id = 0;
		$entry_state_error = false;
		$check_value = prepared_query::fetch("select count(*) as total from zones where zone_country_id = :country_id", cardinality::SINGLE, [':country_id' => $country]);
		$entry_state_has_zones = ($check_value > 0);
		if (!empty($entry_state_has_zones)) {
			$zone_ids = prepared_query::fetch("select zone_id from zones where zone_country_id = :country_id and zone_name = :state", cardinality::COLUMN, [':country_id' => $country, ':state' => $state]);

			if (count($zone_ids) == 1) {
				$zone_id = $zone_ids[0];
			}
			else {
				$zone_ids = prepared_query::fetch("select zone_id from zones where zone_country_id = :country_id and zone_code = :state", cardinality::COLUMN, [':country_id' => $country, ':state' => $state]);

				if (count($zone_ids) == 1) {
					$zone_id = $zone_ids[0];
				}
				else {
					$error = true;
					$entry_state_error = true;
				}
			}
		}
		else {
			if (empty($state)) {
				$error = true;
				$entry_state_error = true;
			}
		}
	}
}

if (strlen($telephone) < ENTRY_TELEPHONE_MIN_LENGTH) {
	$error = true;
	$entry_telephone_error = true;
}
else {
	$entry_telephone_error = false;
}

$check_email = prepared_query::fetch("select vendors_email_address from vendors where vendors_email_address = :email", cardinality::COLUMN, [':email' => $email_address]);
if (!empty($check_email)) {
	$error = true;
	$entry_email_address_exists = true;
}
else {
	$entry_email_address_exists = false;
}

if ($error == true) {
	$processed = true; ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE ?></title>
	<base href="<?php echo (getenv('HTTPS') == 'on' ? HTTPS_SERVER : HTTP_SERVER).DIR_WS_ADMIN; ?>">
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<?php require('includes/form_check.js.php'); ?>
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
				<form name="account_edit" method="post" action="/admin/create_vendor_account_process.php" onSubmit="return check_form();">
					<input type="hidden" name="action" value="process">
					<table border="0" width="100%" cellspacing="0" cellpadding="0">
						<tr>
							<td>
								<table border="0" width="100%" cellspacing="0" cellpadding="0">
									<tr>
										<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
						</tr>
						<tr>
							<td>
								<?php
								$account['entry_country_id'] = STORE_COUNTRY;
								require(DIR_WS_MODULES.'account_details_vendors.php'); ?>
							</td>
						</tr>
						<tr>
							<td align="right" class="main"><br><?php echo tep_image_submit('button_confirm.gif', 'continue'); ?></td>
						</tr>
					</table>
				</form>
			</td>
			<!-- body_text_eof //-->
			<td width="<?php echo BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2"></table>
			</td>
		</tr>
	</table>
	<!-- body_eof //-->
</body>
</html>
<?php
}
else {
	$sql_data_array = [
		':vendors_company_name' => $_REQUEST['vendors_company_name'],
		':vendors_email_address' => $email_address,
		':vendors_telephone' => $telephone,
		':vendors_fax' => $fax,
		':vendors_notes' => $vendors_notes, // START \ END Admin Notes
		':aim_screenname' => $aim_screenname,
		':msn_screenname' => $msn_screenname,
		':company_account_contact_name' => $company_account_contact_name,
		':company_account_contact_email' => $company_account_contact_email,
		':company_account_contact_phone_number' => $company_account_contact_phone_number,
		':company_web_address' => $company_web_address,
		':company_payment_terms' => $company_payment_terms
	];

	$vendors_id = prepared_query::insert('INSERT INTO vendors (vendors_company_name, vendors_email_address, vendors_telephone, vendors_fax, vendors_notes, aim_screenname, msn_screenname, company_account_contact_name, company_account_contact_email, company_account_contact_phone_number, company_web_address, company_payment_terms, vendors_default_address_id) VALUES (:vendors_company_name, :vendors_email_address, :vendors_telephone, :vendors_fax, :vendors_notes, :aim_screenname, :msn_screenname, :company_account_contact_name, :company_account_contact_email, :company_account_contact_phone_number, :company_web_address, :company_payment_terms, 1)', $sql_data_array);

	$sql_data_array = array(
		':vendors_id' => $vendors_id,
		':entry_firstname' => $_REQUEST['vendors_company_name'],
		':entry_lastname' => '',
		':entry_street_address' => $street_address,
		':entry_postcode' => $postcode,
		':entry_city' => $city,
		':entry_country_id' => $country,
		':entry_state' => $state,
		':entry_company' => $_REQUEST['vendors_company_name'],
		':entry_suburb' => $suburb,
	);

	$address_id = prepared_query::insert('INSERT INTO address_book_vendors (vendors_id, entry_firstname, entry_lastname, entry_street_address, entry_postcode, entry_city, entry_country_id, entry_state, entry_company, entry_suburb) VALUES (:vendors_id, :entry_firstname, :entry_lastname, :entry_street_address, :entry_postcode, :entry_city, :entry_country_id, :entry_state, :entry_company, :entry_suburb)', $sql_data_array);

	prepared_query::execute("update vendors set vendors_default_address_id = :address_id where vendors_id = :vendors_id", [':address_id' => $address_id, ':vendors_id' => $vendors_id]);

	prepared_query::execute("insert into vendors_info (vendors_info_id, vendors_info_number_of_logons, vendors_info_date_account_created) values (:vendors_id, '0', now())", [':vendors_id' => $vendors_id]);

	CK\fn::redirect_and_exit('/admin/vendors.php?vendors_id='.$vendors_id);
 }
?>
