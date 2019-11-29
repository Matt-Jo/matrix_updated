<?php
require('includes/application_top.php');

if (!empty($_GET['token']) || CK\fn::check_flag(@$_GET['re-token'])) {
	if (!empty($_GET['token'])) $_SESSION['direct_order_access_token'] = $_GET['token'];

	if (empty($_SESSION['customer_id'])) {
		// if we're not logged in, we gotta get logged in
		$_SESSION['previous_page'] = $_SERVER['PHP_SELF'].'?re-token=1';
		//$_SESSION['navigation']->set_snapshot();
		CK\fn::redirect_and_exit('/login.php');
	}

	unset($_SESSION['previous_page']);

	if ($orders_id = prepared_query::fetch('SELECT orders_id FROM orders WHERE order_access_token = :order_access_token', cardinality::SINGLE, [':order_access_token' => $_SESSION['direct_order_access_token']])) {
		CK\fn::redirect_and_exit('/account_history_info.php?order_id='.$orders_id);
	}
}
else {
	unset($_SESSION['direct_order_access_token']);
}

function build_page_template($content_map) { ?>
	<style>
		#fatal-error { font-size:1.3em; font-weight:bold; background-color:#fdd; border:2px solid #f00; margin:20px; padding:20px; -moz-border-radius: 10px; -webkit-border-radius: 10px; -khtml-border-radius: 10px; border-radius: 10px; }
		#timer { background-color:#ffc; }
		table.contact-us td { font-size:1.1em; padding:8px 0px; }
	</style>
	<div id="fatal-error">
		<p>
			<?php if (empty($_SESSION['direct_order_access_token'])) { ?>
			You must provide an order access token to be directed to your order.
			<?php }
			else { ?>
			The order access token you provided, <?= $_SESSION['direct_order_access_token']; ?>, could not be located in our system.  Please double check it and try again.
			<?php } ?>
		</p>
	</div>
<?php }

require('templates/Pixame_v1/main_page.tpl.php');
?>
