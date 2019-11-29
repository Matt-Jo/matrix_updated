<?php
require_once("includes/application_top.php");

$content = 'custom_quote';

if (isset($_GET['key'])) {
	// we're not really looping, but just using the do/while structure to allow us to break on error
	// probably an anti-pattern, but we'll use this in advance of more fully redesigning the
	// logic here.
	do {
		if (!($quote = ck_quote::get_quote_by_key($_GET['key'], NULL, TRUE))) $error = 'Key not found or quote has expired.';
		else {
			if (!$quote->associate_to_account($_SESSION['cart'])) {
				$error = 'The requested quote does not belong to the customer account currently logged in.';
				break;
			}

			if ($cart->has_quotes()) {
				foreach ($cart->get_quotes() as $quotes) {
					$cart->remove_cart_quote($quotes['quote_id']);
				}
			}
			$cart->add_cart_quote($quote->id());
			CK\fn::redirect_and_exit('/shopping_cart.php');
		}
	}
	while (FALSE);
}
else $error = 'Quote key was not set in url.';

require('templates/Pixame_v1/main_page.tpl.php');
?>
