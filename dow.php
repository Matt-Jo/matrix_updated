<?php
require('includes/application_top.php');

if (isset($_SESSION['admin']) && $_SESSION['admin'] == 'true' && !empty($_POST['dow_action'])) {
	switch ($_POST['dow_action']) {
		case 'edit':
			$edit_dow_id = $_POST['edit-dow-id'];

			if (isset($_POST['edit_product_desc'])) prepared_query::execute('UPDATE ck_dow_schedule SET custom_description = ? WHERE dow_schedule_id = ?', array($_POST['edit_product_desc'], $edit_dow_id));
			if (isset($_POST['edit_legalese'])) prepared_query::execute('UPDATE ck_dow_schedule SET legalese = ? WHERE dow_schedule_id = ?', array($_POST['edit_legalese'], $edit_dow_id));
			if (isset($_POST['edit_product_recommends'])) {
				prepared_query::execute('DELETE FROM ck_product_recommends WHERE dow_schedule_id = ? AND base_products_id = ?', array($edit_dow_id, $_POST['base_products_id']));
				foreach ($_POST['edit_product_recommends'] as $ordinal => $product) {
					echo '<pre>';
					echo 'INSERT INTO ck_product_recommends (base_products_id, recommend_products_id, dow_schedule_id, custom_name, ordinal, entered) VALUES (?, ?, ?, ?, ?, NOW())'."<br>\n";
					print_r(array($_POST['base_products_id'], $product['recommend_products_id'], $edit_dow_id, $product['custom_name'], $ordinal));
					echo '</pre>';
					prepared_query::execute('INSERT INTO ck_product_recommends (base_products_id, recommend_products_id, dow_schedule_id, custom_name, ordinal, entered) VALUES (?, ?, ?, ?, ?, NOW())', array($_POST['base_products_id'], $product['recommend_products_id'], $edit_dow_id, $product['custom_name'], $ordinal));
				}
			}

			echo '1';

			exit();
			break;
		default:
			break;
	}
}

if (isset($_SESSION['admin']) && $_SESSION['admin'] == 'true' && !empty($_GET['edit-dow-id'])) $dow = dow::get_dow($_GET['edit-dow-id']);
else $dow = dow::get_active_dow();

$product = new ck_product_listing($dow['products_id']);

$content = 'dow';

require('templates/Pixame_v1/main_page.tpl.php');
?>
