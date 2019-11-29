<?php
require('includes/application_top.php');

if (empty($_SESSION['customer_id'])) {
	$_SESSION['navigation']->set_snapshot();
	CK\fn::redirect_and_exit('/login.php');
}

if (isset($_GET['action']) && ($_GET['action'] == 'deleteconfirm') && isset($_GET['delete']) && is_numeric($_GET['delete'])) {
	prepared_query::execute('DELETE FROM address_book WHERE address_book_id = :address_book_id AND customers_id = :customers_id', [':address_book_id' => $_GET['delete'], ':customers_id' => $_SESSION['customer_id']]);

	$messageStack->add_session('addressbook', 'The selected address has been successfully removed from your address book.', 'success');
	
	CK\fn::redirect_and_exit('/address_book.php');
}

$process = FALSE;
$refresh = FALSE;

$error = FALSE;

function record_error($msg) {
	$GLOBALS['error'] = TRUE;
	$GLOBALS['messageStack']->add('addressbook', $msg);
}

if (isset($_POST['action']) && in_array($_POST['action'], ['process', 'update', 'refresh'])) {
	if ($_POST['action'] != 'refresh') $process = TRUE; 
	else $refresh = TRUE;

	$error = FALSE;

	$company = $_POST['company'];

	$firstname = $_POST['firstname'];
	$lastname = $_POST['lastname'];
	$street_address = $_POST['street_address'];

	$suburb = $_POST['suburb'];

	$postcode = $_POST['postcode'];
	$city = $_POST['city'];
	$country = $_POST['country'];
	$telephone = $_POST['telephone'];

	if (isset($_POST['zone_id'])) $zone_id = $_POST['zone_id'];
	else $zone_id = false;
	$state = $_POST['state'];

	if ($process) {
		if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) record_error('Your First Name must contain a minimum of '.ENTRY_FIRST_NAME_MIN_LENGTH.' characters.');
		if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) record_error('Your Last Name must contain a minimum of '.ENTRY_LAST_NAME_MIN_LENGTH.' characters.');
		if (strlen($street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) record_error('Your Street Address must contain a minimum of '.ENTRY_STREET_ADDRESS_MIN_LENGTH.' characters.');
		if (strlen($postcode) < ENTRY_POSTCODE_MIN_LENGTH) record_error('Your Zip Code must contain a minimum of '.ENTRY_POSTCODE_MIN_LENGTH.' characters.');
		if (strlen($city) < ENTRY_CITY_MIN_LENGTH) record_error('Your City must contain a minimum of '.ENTRY_CITY_MIN_LENGTH.' characters.');
		if (!is_numeric($country)) record_error('You must select a country from the Countries pull down menu.');
		if (strlen($telephone) < ENTRY_TELEPHONE_MIN_LENGTH) record_error('Your Telephone Number must contain a minimum of '.ENTRY_TELEPHONE_MIN_LENGTH.' characters.');

		if ($zone_id == 0) {
			if (strlen($state) < ENTRY_STATE_MIN_LENGTH) record_error('Your State must contain a minimum of '.ENTRY_STATE_MIN_LENGTH.' characters.');
		}

		if (!$error) {
			$sql_data_array = [
				'entry_firstname' => $firstname,
				'entry_lastname' => $lastname,
				'entry_company' => $company,
				'entry_street_address' => $street_address,
				'entry_suburb' => $suburb,
				'entry_postcode' => $postcode,
				'entry_city' => $city,
				'entry_country_id' => (int)$country,
				'entry_telephone' => $telephone,
			];

			if ($zone_id > 0) {
				$sql_data_array['entry_zone_id'] = (int)$zone_id;
				$sql_data_array['entry_state'] = '';
			}
			else {
				$sql_data_array['entry_zone_id'] = '0';
				$sql_data_array['entry_state'] = $state;
			}

			if ($_POST['action'] == 'update') {
				$updates = new prepared_fields($sql_data_array, prepared_fields::UPDATE_QUERY);
				$id = new prepared_fields(['address_book_id' => $_GET['edit'], 'customers_id' => $_SESSION['customer_id']]);

				prepared_query::execute('UPDATE address_book SET '.$updates->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($updates, $id));

				if ($__FLAG['primary']) {
					$_SESSION['customer_first_name'] = $firstname;
					$_SESSION['customer_country_id'] = $country_id;
					$_SESSION['customer_zone_id'] = (int)$zone_id;
					$_SESSION['customer_default_address_id'] = (int)$_GET['edit'];

					prepared_query::execute('UPDATE customers SET customers_firstname = :firstname, customers_lastname = :lastname, customers_default_address_id = :address_book_id WHERE customers_id = :customers_id', [':firstname' => $firstname, ':lastname' => $lastname, ':address_book_id' => $_GET['edit'], ':customers_id' => $_SESSION['customer_id']]);
				}
			}
			else {
				$sql_data_array['customers_id'] = (int)$_SESSION['customer_id'];
				$insert = new prepared_fields($sql_data_array, prepared_fields::INSERT_QUERY);
				$new_address_book_id = prepared_query::insert('INSERT INTO address_book ('.$insert->insert_fields().') VALUES ('.$insert->insert_values().')', $insert->insert_parameters());

				if ($__FLAG['primary']) {
					$_SESSION['customer_first_name'] = $firstname;
					$_SESSION['customer_country_id'] = $country_id;
					$_SESSION['customer_zone_id'] = (int)$zone_id;
					$_SESSION['customer_default_address_id'] = $new_address_book_id;

					$sql_data_array = ['customers_firstname' => $firstname, 'customers_lastname' => $lastname, 'customers_default_address_id' => $new_address_book_id];

					$updates = new prepared_fields($sql_data_array, prepared_fields::UPDATE_QUERY);
					$id = new prepared_fields(['customers_id' => $_SESSION['customer_id']]);

					prepared_query::execute('UPDATE customers SET '.$updates->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($updates, $id));
				}
			}

			$messageStack->add_session('addressbook', 'Your address book has been successfully updated.', 'success');

			$customer = new ck_customer2($_SESSION['customer_id']);
			try {
				$hubspot = new api_hubspot;
				$hubspot->update_company($customer);
			}
			catch (Exception $e) {
				// fail silently - we don't need to alert the customer that a hubspot update failed
			}

			CK\fn::redirect_and_exit('/address_book.php');
		}
	}
}

if ($refresh) {
	$entry = [
		'entry_firstname' => $firstname,
		'entry_lastname' => $lastname,
		'entry_street_address' => $street_address,
		'entry_postcode' => $postcode,
		'entry_city' => $city,
		'entry_state' => '',
		'entry_zone_id' => 0,
		'entry_country_id' => (int)$country,
		'entry_telephone' => $telephone,
		'entry_company' => $company,
		'entry_suburb' => $suburb,
	];
}
else {
	if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {

		$entry = prepared_query::fetch('SELECT entry_gender, entry_company, entry_firstname, entry_lastname, entry_street_address, entry_suburb, entry_postcode, entry_city, entry_state, entry_telephone, entry_zone_id, entry_country_id FROM address_book WHERE customers_id = :customers_id AND address_book_id = :address_book_id', cardinality::ROW, [':customers_id' => $_SESSION['customer_id'], ':address_book_id' => $_GET['edit']]);

		if (empty($entry)) {
			$messageStack->add_session('addressbook', 'The address book entry does not exist.');
			CK\fn::redirect_and_exit('/address_book.php');
		}
	}
	elseif (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
		if ($_GET['delete'] == @$_SESSION['customer_default_address_id']) {
			$messageStack->add_session('addressbook', 'The primary address cannot be deleted. Please set another address as the primary address and try again.', 'warning');
			CK\fn::redirect_and_exit('/address_book.php');
		}
		else {
			$check = prepared_query::fetch('SELECT COUNT(*) AS total FROM address_book WHERE address_book_id = :address_book_id AND customers_id = :customer_id', cardinality::SINGLE, [':address_book_id' => $_GET['delete'], ':customer_id' => $_SESSION['customer_id']]);

			if ($check < 1) {
				$messageStack->add_session('addressbook', 'The address book entry does not exist.');
				CK\fn::redirect_and_exit('/address_book.php');
			}
		}
	}
	else {
		$entry = [];
		if (!isset($country)) $country = ck_address2::DEFAULT_COUNTRY_ID;
		$entry['entry_country_id'] = $country;
	}
	$breadcrumb->add('Customer Service', '/custserv.php');
	$breadcrumb->add('My Account', '/account.php');
	$breadcrumb->add('Address Book', '/address_book.php');

	if (isset($_GET['edit']) && is_numeric($_GET['edit'])) $breadcrumb->add('Update Entry', '/address_book_process.php?edit='.$_GET['edit']);
	elseif (isset($_GET['delete']) && is_numeric($_GET['delete'])) $breadcrumb->add('Delete Entry', '/address_book_process.php?delete='.$_GET['delete']);
	else $breadcrumb->add('Add Entry', '/address_book_process.php');
}

$content = 'address_book_process';
$javascript = $content.'.php';

require('templates/Pixame_v1/main_page.tpl.php');
?>