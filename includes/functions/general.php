<?php

function tep_output_string($string, $translate = false, $protected = false) {
	if ($protected == true) {
		return htmlspecialchars($string);
	} else {
		if ($translate == false) {
			return strtr(trim($string), array('"' => '&quot;'));
		} else {
			return strtr(trim($string), $translate);
		}
	}
}

function tep_output_string_protected($string) {
	return tep_output_string($string, false, true);
}

function tep_address_format($address_format_id, $address, $html, $boln, $eoln) {
	$address_format = prepared_query::fetch('SELECT address_format as format FROM address_format WHERE address_format_id = ?', cardinality::ROW, $address_format_id);

	$company = !empty($address['company'])?tep_output_string_protected($address['company']):NULL;

	if (!empty($address['firstname'])) {
		$firstname = tep_output_string_protected($address['firstname']);

		if (!empty($address['lastname'])) {
			$lastname = tep_output_string_protected($address['lastname']);
		}
	}
	elseif (!empty($address['name'])) {
		$firstname = tep_output_string_protected($address['name']);
		$lastname = '';
	}
	else {
		$firstname = '';
		$lastname = '';
	}

	$street = !empty($address['street_address'])?tep_output_string_protected($address['street_address']):NULL;
	$suburb = !empty($address['suburb'])?tep_output_string_protected($address['suburb']):NULL;
	$city = !empty($address['city'])?tep_output_string_protected($address['city']):NULL;
	$state = !empty($address['state'])?tep_output_string_protected($address['state']):NULL;

	if (!empty($address['country_id'])) {
		$country = prepared_query::fetch('SELECT countries_name FROM countries WHERE countries_id = :country_id', cardinality::SINGLE, [':country_id' => $address['country_id']]);

		if (!empty($address['zone_id'])) {
			$state = ck_address2::legacy_get_zone_field($address['country_id'], $address['zone_id'], @$state, 'zone_code');
		}
	}
	elseif (!empty($address['country'])) {
		$country = tep_output_string_protected(@$address['country']['title']);
	}
	else {
		$country = '';
	}

	$telephone = !empty($address['telephone'])?tep_output_string_protected($address['telephone']):NULL;
	$postcode = !empty($address['postcode'])?tep_output_string_protected($address['postcode']):NULL;

	$zip = $postcode;

	if (!empty($html)) {
		// HTML Mode
		$HR = '<hr>';
		$hr = '<hr>';
		if ($boln == '' && $eoln == "\n") { // Values not specified, use rational defaults
			$CR = '<br>';
			$cr = '<br>';
			$eoln = $cr;
		}
		else { // Use values supplied
			$CR = $eoln.$boln;
			$cr = $CR;
		}
	}
	else {
		// Text Mode
		$CR = $eoln;
		$cr = $CR;
		$HR = '----------------------------------------';
		$hr = '----------------------------------------';
	}

	$statecomma = '';
	$streets = $street;
	if ($suburb != '') $streets = $street.$cr.$suburb;

	if (!empty($address['country'])) {
		if ($country == '') $country = tep_output_string_protected(@$address['country']['title']);
	}

	if ($state != '') $statecomma = $state.', ';

	$fmt = $address_format['format'];
	eval("\$address = \"$fmt\";");

	if (ACCOUNT_COMPANY == 'true' && !empty($company)) {
		$address = $company.$cr.$address;
	}

	return $address;
}

function tep_not_null($value) {
	if (is_array($value)) {
		if (sizeof($value) > 0) {
			return true;
		} else {
			return false;
		}
	} else {
		if (($value != '') && (strtolower($value) != 'null') && (strlen(trim($value)) > 0)) {
			return true;
		} else {
			return false;
		}
	}
}

function send_invoice_email($orders_id, $resend=FALSE) {
	$_GET['oID'] = $orders_id;

    $mailer = service_locator::get_mail_service();
    $mail = $mailer->create_mail();
    $mail->set_from('accounting@cablesandkits.com', 'CablesAndKits.com');

	$to = [];
	$emails = prepared_query::fetch('SELECT o.customers_email_address, c.customers_id, c.company_account_contact_email, cel.customers_emailaddress as cel_email, o.net10_po, o.net15_po, o.net30_po, o.net45_po FROM orders o LEFT JOIN customers c ON o.customers_id = c.customers_id LEFT JOIN customers_extra_logins cel ON o.customers_extra_logins_id = cel.customers_extra_logins_id WHERE o.orders_id = :orders_id', cardinality::ROW, [':orders_id' => $orders_id]);

	$customer = new ck_customer2($emails['customers_id']);

	if (!empty($emails['customers_email_address'])) $to[] = $emails['customers_email_address'];
	if (!empty($emails['company_account_contact_email'])) $to[] = $emails['company_account_contact_email'];
	if (!empty($emails['cel_email'])) $to[] = $emails['cel_email'];

	if ($customer->has_contacts()) {
		$additional_contacts = $customer->get_contacts();
		foreach ($additional_contacts as $contact) {
			if (!in_array($contact['contact_type_id'], [1, 2])) continue; // Accounting & Invoice Recipient Contacts only
			if (!in_array($contact['email_address'], $to)) $to[] = $contact['email_address'];
		}
	}

	$po = NULL;
	if (!empty($emails['net10_po'])) $po = $emails['net10_po'];
	if (!empty($emails['net15_po'])) $po = $emails['net15_po'];
	if (!empty($emails['net30_po'])) $po = $emails['net30_po'];
	if (!empty($emails['net45_po'])) $po = $emails['net45_po'];

	$body = 'Please find attached the invoice for your order with CablesAndKits.com.<br><br>Thank you for your business!<br><br>The CablesAndKits.com Team';

	foreach ($to as $ea) {
		$mail->add_to($ea);
	}

	//send the email
	$presub = $resend?'Resend ':'';
	if (!empty($po)) $mail->set_subject($presub.'Invoice for PO# '.$po.' (CablesAndKits.com Order# '.$orders_id.')');
	else $mail->set_subject($presub.'Invoice for CablesAndKits.com Order# '.$orders_id);

	$pdf = ck_invoice::generate_pdf($orders_id);

	$mail->set_body($body)
	    ->add_bcc('accounting@cablesandkits.com')
	    ->create_attachment($pdf, 'CablesAndKits.com_Invoice_'.$orders_id.'.pdf');

    try {
        $mailer->send($mail);
    }
	catch (Exception $e) {
        debug_tools::mark('Failed to send invoices for Order #'.$orders_id);
        return;
    }

    prepared_query::execute('UPDATE acc_invoices SET sent = 1 WHERE inv_order_id = :orders_id AND sent = 0', [':orders_id' => $orders_id]);
	debug_tools::mark('Sent invoices for Order #'.$orders_id);
}

