<?php
require('includes/application_top.php');

if (empty($_SESSION['customer_id'])) {
	$_SESSION['navigation']->set_snapshot();
	CK\fn::redirect_and_exit('/login.php');
}

$customer = new ck_customer2($_SESSION['customer_id']);

// needs to be included earlier to set the success message in the messageStack
require(DIR_WS_LANGUAGES.$_SESSION['language'].'/account_edit.php');

if (isset($_POST['action']) && ($_POST['action'] == 'process')) {
	$firstname = $_POST['firstname'];
	$lastname = $_POST['lastname'];
	$email_address = $_POST['email_address'];
	$telephone = $_POST['telephone'];
	$fax = $_POST['fax'];

	$error = false;

	if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
		$error = true;
		$messageStack->add('account_edit', 'Your First Name must contain a minimum of '.ENTRY_FIRST_NAME_MIN_LENGTH.' characters.');
	}

	if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
		$error = true;
		$messageStack->add('account_edit', 'Your Last Name must contain a minimum of '.ENTRY_LAST_NAME_MIN_LENGTH.' characters.');
	}

	if (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
		$error = true;
		$messageStack->add('account_edit', 'Your E-Mail Address must contain a minimum of '.ENTRY_EMAIL_ADDRESS_MIN_LENGTH.' characters.');
	}

	if (!service_locator::get_mail_service()::validate_address($email_address)) {
		$error = true;
		$messageStack->add('account_edit', 'Your E-Mail Address does not appear to be valid - please make any necessary corrections.');
	}

	if (ck_customer2::email_exists($email_address, $_SESSION['customer_id'], $_SESSION['customer_extra_login_id'])) {
		$error = true;
		$messageStack->add('account_edit', 'An Account has already been created using that E-Mail Address. If you do not remember your password please click "Password Forgotten" below.');
	}

	if (!isset($_SESSION['customer_extra_login_id'])) {
		if (strlen($telephone) < ENTRY_TELEPHONE_MIN_LENGTH) {
			$error = true;
			$messageStack->add('account_edit', 'Your Telephone Number must contain a minimum of '.ENTRY_TELEPHONE_MIN_LENGTH.' characters.');
		}
	}

	if ($error == false) {
		if (isset($_SESSION['customer_extra_login_id']) && $_SESSION['customer_extra_login_id'] > 0) {
			prepared_query::execute('UPDATE customers_extra_logins SET customers_firstname = :customers_firstname, customers_lastname = :customers_lastname, customers_emailaddress = :customers_emailaddress WHERE customers_extra_logins_id = :customers_extra_logins_id', [':customers_firstname' => $firstname, ':customers_lastname' => $lastname, ':customers_emailaddress' => $email_address, ':customers_extra_logins_id' => $_SESSION['customer_extra_login_id']]);
			$customer_first_name = $firstname;
		}
		else {
			if ($customer->get_header('email_address') != $email_address && $customer->has_account_manager()) {
                $accManager = $customer->get_account_manager();
                
                $mailer = service_locator::get_mail_service();
                $mail = $mailer->create_mail()
                    ->set_subject('Email Address Update for Customer ID '.$_SESSION['customer_id'])
                    ->set_from('webmaster@cablesandkits.com', 'CK Webmaster')
                    ->add_to($accManager->get_header('email_address'), $accManager->get_name())
                    ->set_body(null,'This customer changed their primary email address from '.$customer->get_header('email_address').' to '.$email_address);
                $mailer->send($mail);
                
			}

			$sql_data_array = [
				'customers_firstname' => $firstname,
				'customers_lastname' => $lastname,
				'customers_email_address' => $email_address,
				'customers_telephone' => $telephone,
				'customers_fax' => $fax
			];

			$ea = explode('@', $email_address);
			$sql_data_array['email_domain'] = strtolower(trim(end($ea)));

			if (isset($_POST['customer_segment_id'])) $sql_data_array['customer_segment_id'] = $_POST['customer_segment_id'];

			$updates = new prepared_fields($sql_data_array, prepared_fields::UPDATE_QUERY);
			$id = new prepared_fields(['customers_id' => $_SESSION['customer_id']]);

			prepared_query::execute('UPDATE customers SET '.$updates->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($updates, $id));
			prepared_query::execute('UPDATE customers_info SET customers_info_date_account_last_modified = NOW() WHERE customers_info_id = :customers_id', [':customers_id' => $_SESSION['customer_id']]);

			$sql_data_array = [
				'entry_firstname' => $firstname,
				'entry_lastname' => $lastname
			];

			prepared_query::execute('UPDATE address_book ab JOIN customers c ON ab.customers_id = c.customers_id AND ab.address_book_id = c.customers_default_address_id SET ab.entry_firstname = :firstname, ab.entry_lastname = :lastname WHERE c.customers_id = :customers_id', [':firstname' => $firstname, ':lastname' => $lastname, ':customers_id' => $_SESSION['customer_id']]);

			// reset the session variables
			$customer_first_name = $firstname;
		}

		$messageStack->add_session('account', SUCCESS_ACCOUNT_UPDATED, 'success');
		CK\fn::redirect_and_exit('/account.php');
	}
}

if (isset($_SESSION['customer_extra_login_id']) && $_SESSION['customer_extra_login_id'] > 0) {
	$account = prepared_query::fetch('SELECT customers_firstname, customers_lastname, customers_emailaddress as customers_email_address FROM customers_extra_logins WHERE customers_extra_logins_id = :customers_extra_logins_id', cardinality::ROW, [':customers_extra_logins_id' => $_SESSION['customer_extra_login_id']]);
}
else {
	$account = prepared_query::fetch('SELECT c.customers_firstname, c.customers_lastname, c.customers_email_address, c.customers_telephone, c.customers_fax, cs.segment_code as customer_segment FROM customers c LEFT JOIN customer_segments cs ON c.customer_segment_id = cs.customer_segment_id WHERE customers_id = :customers_id', cardinality::ROW, [':customers_id' => $_SESSION['customer_id']]);
}

$breadcrumb->add('Customer Service', '/custserv.php');
$breadcrumb->add('My Account', '/account.php');
$breadcrumb->add('Edit Account', '/account_edit.php');

$content = 'account_edit';
$javascript = 'form_check.js.php';

require('templates/Pixame_v1/main_page.tpl.php');
?>
