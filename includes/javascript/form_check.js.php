<script>
	var form = "";
	var submitted = false;
	var error = false;
	var error_message = "";

	function check_input(field_name, field_size, message) {
		if (form.elements[field_name] && (form.elements[field_name].type != "hidden")) {
			var field_value = form.elements[field_name].value;

			if (field_value == '' || field_value.length < field_size) {
				error_message = error_message + "* " + message + "\n";
				error = true;
			}
		}
	}

	function check_postcode() {
		check_input("postcode", <?= ENTRY_POSTCODE_MIN_LENGTH; ?>, "Your Zip Code must contain a minimum of <?= ENTRY_POSTCODE_MIN_LENGTH; ?> characters.");

		var valid = "0123456789-";
		var hyphencount = 0;
		var country = form.elements['country'].value;
		if (country == '223') {
			var field = form.elements['postcode'].value;

			if (field.length!=5 && field.length!=10) {
				error_message = error_message + "* " + "Please enter your 5 digit or 5 digit+4 zip code.\n";
				error = true;
				return false;
			}

			for (var i=0; i < field.length; i++) {
				temp = "" + field.substring(i, i+1);
				if (temp == "-") hyphencount++;

				if (valid.indexOf(temp) == "-1") {
					error_message = error_message + "* " + "Invalid characters in your zip code. Please try again.\n";
					error = true;
					return false;
				}

				if ((hyphencount > 1) || ((field.length==10) && ""+field.charAt(5)!="-")) {
					error_message = error_message + "* " + "The hyphen character should be used with a properly formatted 5 digit+four zip code, like '12345-6789'. Please try again.\n";
					error = true;
					return false;
				}
			}
		}
	}

	function check_radio(field_name, message) {
		var isChecked = false;

		if (form.elements[field_name] && (form.elements[field_name].type != "hidden")) {
			var radio = form.elements[field_name];

			for (var i=0; i<radio.length; i++) {
				if (radio[i].checked == true) {
					isChecked = true;
					break;
				}
			}

			if (isChecked == false) {
				error_message = error_message + "* " + message + "\n";
				error = true;
			}
		}
	}

	function check_select(field_name, field_default, message) {
		if (form.elements[field_name] && (form.elements[field_name].type != "hidden")) {
			var field_value = form.elements[field_name].value;

			if (field_value == field_default) {
				error_message = error_message + "* " + message + "\n";
				error = true;
			}
		}
	}

	function check_password(field_name_1, field_name_2, field_size, message_1, message_2) {
		if (form.elements[field_name_1] && (form.elements[field_name_1].type != "hidden")) {
			var password = form.elements[field_name_1].value;
			var confirmation = form.elements[field_name_2].value;

			if (password == '' || password.length < field_size) {
				error_message = error_message + "* " + message_1 + "\n";
				error = true;
			}
			else if (password != confirmation) {
				error_message = error_message + "* " + message_2 + "\n";
				error = true;
			}
		}
	}

	function check_password_new(field_name_1, field_name_2, field_name_3, field_size, message_1, message_2, message_3) {
		if (form.elements[field_name_1] && (form.elements[field_name_1].type != "hidden")) {
			var password_current = form.elements[field_name_1].value;
			var password_new = form.elements[field_name_2].value;
			var password_confirmation = form.elements[field_name_3].value;

			if (password_current == '' || password_current.length < field_size) {
				error_message = error_message + "* " + message_1 + "\n";
				error = true;
			}
			else if (password_new == '' || password_new.length < field_size) {
				error_message = error_message + "* " + message_2 + "\n";
				error = true;
			}
			else if (password_new != password_confirmation) {
				error_message = error_message + "* " + message_3 + "\n";
				error = true;
			}
		}
	}

	function check_form(form_name) {
		if (submitted == true) {
			alert("This form has already been submitted. Please press Ok and wait for this process to be completed.");
			return false;
		}

		error = false;
		form = form_name;
		error_message = "Errors have occured during the process of your form.\n\nPlease make the following corrections:\n\n";

		check_input("firstname", <?= ENTRY_FIRST_NAME_MIN_LENGTH; ?>, "Your First Name must contain a minimum of <?= ENTRY_FIRST_NAME_MIN_LENGTH; ?> characters.");
		check_input("lastname", <?= ENTRY_LAST_NAME_MIN_LENGTH; ?>, "Your Last Name must contain a minimum of <?= ENTRY_LAST_NAME_MIN_LENGTH; ?> characters.");

		check_input("email_address", <?= ENTRY_EMAIL_ADDRESS_MIN_LENGTH; ?>, "Your E-Mail Address must contain a minimum of <?= ENTRY_EMAIL_ADDRESS_MIN_LENGTH; ?> characters.");
		check_password("email_address", "confirm_email_address", 0, "Please enter an email address.", "The email addresses you entered do not match");
		check_input("street_address", <?= ENTRY_STREET_ADDRESS_MIN_LENGTH; ?>, "Your Street Address must contain a minimum of <?= ENTRY_STREET_ADDRESS_MIN_LENGTH; ?> characters.");
		check_postcode();
		check_input("city", <?= ENTRY_CITY_MIN_LENGTH; ?>, "Your City must contain a minimum of <?= ENTRY_CITY_MIN_LENGTH; ?> characters.");
		check_input("state", <?= ENTRY_STATE_MIN_LENGTH; ?>, "Please select a state.");

		check_select("country", "", "You must select a country from the Countries pull down menu.");

		check_input("telephone", <?= ENTRY_TELEPHONE_MIN_LENGTH; ?>, "Your Telephone Number must contain a minimum of <?= ENTRY_TELEPHONE_MIN_LENGTH; ?> characters.");

		check_password("password", "confirmation", <?= ENTRY_PASSWORD_MIN_LENGTH; ?>, "Your Password must contain a minimum of <?= ENTRY_PASSWORD_MIN_LENGTH; ?> characters.", "The Password Confirmation must match your Password.");
		check_password_new("password_current", "password_new", "password_confirmation", <?= ENTRY_PASSWORD_MIN_LENGTH; ?>, "Your Password must contain a minimum of <?= ENTRY_PASSWORD_MIN_LENGTH; ?> characters.", "Your new Password must contain a minimum of <?= ENTRY_PASSWORD_MIN_LENGTH; ?> characters.", "The Password Confirmation must match your new Password.");

		<?php if (!empty($dealer_check)) { ?>
		if (!$('#customers_ups').val().match(/^[A-Za-z0-9]{6}$/)&& $('#customers_ups').length) {
			error_message = error_message + "* Please enter a valid UPS account number.\n";
			error = 1;
		}

		if (!$('#customers_fedex').val().match(/^[0-9]{9}$/) && $('#customers_fedex').length) {
			error_message = error_message + "* Please enter a valid FedEx account number.\n";
			error = 1;
		}
		<?php } ?>

		if (jQuery("#customers_business_type_id").prop("disabled") == false && jQuery("#customers_business_type_id").val() == "0") {
			error_message = error_message + "* Please specify your business type.\n";
			error = 1;
		}

		if (error == true) {
			alert(error_message);
			return false;
		}
		else {
			submitted = true;
			return true;
		}
	}

	function refresh_form(form_name) {
		form_name.action.value = 'refresh';
		form_name.submit();
		return true;
	}
</script>
