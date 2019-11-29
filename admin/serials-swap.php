<?php
require('includes/application_top.php');

if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'swap':
			$s1 = prepared_query::fetch('SELECT s.id as serial_id, s.ipn as stock_id, s.status, ss.name as status_name, sh.order_id, sh.order_product_id, sh.shipped_date, sh.id as serials_history_id FROM serials s JOIN serials_status ss ON s.status = ss.id JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id WHERE s.id = ? AND sh0.id IS NULL', cardinality::ROW, $_POST['input_serial_id']);
			$s2 = prepared_query::fetch('SELECT s.id as serial_id, s.ipn as stock_id, s.status, ss.name as status_name, sh.order_id, sh.order_product_id, sh.shipped_date, sh.id as serials_history_id FROM serials s JOIN serials_status ss ON s.status = ss.id JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id WHERE s.id = ? AND sh0.id IS NULL', cardinality::ROW, $_POST['output_serial_id']);

			if ($s1['stock_id'] != $s2['stock_id']) {
				var_dump($s1);
				var_dump($s2);
				throw new Exception('These two serials do not belong to the same IPN');
				break;
			}

			/*  if ($s1['status'] == $s2['status'] || !(empty($s1['order_id']) xor empty($s2['order_id']))) $errors = ['The serials in question are not suitable for automatic swapping, please perform this action manually'];
			else { */
				prepared_query::execute('UPDATE serials SET status = ? WHERE id = ?', [$s2['status'], $s1['serial_id']]);
				prepared_query::execute('UPDATE serials_history SET order_id = ?, order_product_id = ?, shipped_date = ? WHERE id = ?', [$s2['order_id'], $s2['order_product_id'], $s2['shipped_date'], $s1['serials_history_id']]);

				prepared_query::execute('UPDATE serials SET status = ? WHERE id = ?', [$s1['status'], $s2['serial_id']]);
				prepared_query::execute('UPDATE serials_history SET order_id = ?, order_product_id = ?, shipped_date = ? WHERE id = ?', [$s1['order_id'], $s1['order_product_id'], $s1['shipped_date'], $s2['serials_history_id']]);
			/* } */

			$s1_now = prepared_query::fetch('SELECT psc.stock_name, s.serial, s.status, ss.name as status_name, sh.entered_date, sh.shipped_date, sh.order_id, sh.order_product_id, op.products_model, o.date_purchased FROM serials s JOIN products_stock_control psc ON s.ipn = psc.stock_id JOIN serials_status ss ON s.status = ss.id JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id LEFT JOIN orders o ON sh.order_id = o.orders_id LEFT JOIN orders_products op ON sh.order_product_id = op.orders_products_id WHERE s.id = ? AND sh0.id IS NULL', cardinality::ROW, $_POST['input_serial_id']);
			$s2_now = prepared_query::fetch('SELECT psc.stock_name, s.serial, s.status, ss.name as status_name, sh.entered_date, sh.shipped_date, sh.order_id, sh.order_product_id, op.products_model, o.date_purchased FROM serials s JOIN products_stock_control psc ON s.ipn = psc.stock_id JOIN serials_status ss ON s.status = ss.id JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id LEFT JOIN orders o ON sh.order_id = o.orders_id LEFT JOIN orders_products op ON sh.order_product_id = op.orders_products_id WHERE s.id = ? AND sh0.id IS NULL', cardinality::ROW, $_POST['output_serial_id']);
			break;
		case 'get-serial-details':
			$details = prepared_query::fetch('SELECT psc.stock_name, s.status, ss.name as status_name, sh.entered_date, sh.shipped_date, sh.order_id, sh.order_product_id, op.products_model, o.date_purchased FROM serials s JOIN products_stock_control psc ON s.ipn = psc.stock_id JOIN serials_status ss ON s.status = ss.id JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id LEFT JOIN orders o ON sh.order_id = o.orders_id LEFT JOIN orders_products op ON sh.order_product_id = op.orders_products_id WHERE s.id = ? AND sh0.id IS NULL', cardinality::ROW, $_GET['serial_id']);

			echo '[IPN: '.$details['stock_name'].']<br>['.$details['status_name'].'('.$details['status'].')]<br>[History Record: '.$details['entered_date'].']<br>[Shipped: '.$details['shipped_date'].']<br>[Order Details: '.$details['order_id'].'<br>&nbsp;&nbsp;&nbsp;&nbsp;- '.$details['products_model'].'<br>&nbsp;&nbsp;&nbsp;&nbsp;- '.$details['date_purchased'].']';

			exit();
			break;
		default:
			break;
	}
} ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
		<table border="0" width="100%" cellspacing="2" cellpadding="2">
			<tr>
				<td width="<?php echo BOX_WIDTH; ?>" valign="top">
					<table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
						<!-- left_navigation //-->
						<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
						<!-- left_navigation_eof //-->
					</table>
				</td>
				<!-- body_text //-->
				<td width="100%" valign="top">
					<table border="0" width="800" cellspacing="0" cellpadding="2">
						<tr>
							<td>
								<?php if (!empty($errors)) {
									if ($errors) {
										echo "<br>ERRORS:<br>";
										echo implode("<br>", $errors);
									}
								} ?>
								<style>
								</style>
								<div style="border:1px solid #000; overflow:auto;">
									<form action="/admin/serials-swap.php" method="post">
										<input type="hidden" name="action" value="swap">
										<div style="float:left; padding:10px; border-right:1px solid #000; width:375px;">
											Serial #1: <input type="text" name="input-serial" id="autocomplete_input">
											<input type="hidden" name="input_serial_id" id="auto_input_serial_id">
											<div id="input-details">
												<?php if (@$_REQUEST['action'] == 'swap') { ?>
												[Serial #: <?= $s1_now['serial']; ?>]<br>
												[IPN: <?= $s1_now['stock_name']; ?>]<br>
												<span style="background-color:#fcc;">[<?= $s1['status_name']; ?>(<?= $s1['status']; ?>)]</span> -&gt; <span style="background-color:#cfc;">[<?= $s1_now['status_name']; ?>(<?= $s1_now['status']; ?>)]</span><br>
												[History Record: <?= $s1_now['entered_date']; ?>]<br>
												<span style="background-color:#fcc;">[Shipped: <?= $s1['shipped_date']; ?>]</span> -&gt; <span style="background-color:#cfc;">[Shipped: <?= $s1_now['shipped_date']; ?>]</span><br>
												[Order Details: <span style="background-color:#fcc;"><?= $s1['order_id']; ?></span> -&gt; <span style="background-color:#cfc;"><?= $s1_now['order_id']; ?></span><br>
												&nbsp;&nbsp;&nbsp;&nbsp;- <?= $s1_now['products_model']; ?><br>
												&nbsp;&nbsp;&nbsp;&nbsp;- <?= $s1_now['date_purchased']; ?>]
												<?php } ?>
											</div>
										</div>
										<div style="float:left; padding:10px; width:375px;">
											Serial #2: <input type="text" name="output-serial" id="autocomplete_output">
											<input type="hidden" name="output_serial_id" id="auto_output_serial_id">
											<div id="output-details">
												<?php if (@$_REQUEST['action'] == 'swap') { ?>
												[Serial #: <?= $s2_now['serial']; ?>]<br>
												[IPN: <?= $s2_now['stock_name']; ?>]<br>
												<span style="background-color:#fcc;">[<?= $s2['status_name']; ?>(<?= $s2['status']; ?>)]</span> -&gt; <span style="background-color:#cfc;">[<?= $s2_now['status_name']; ?>(<?= $s2_now['status']; ?>)]</span><br>
												[History Record: <?= $s2_now['entered_date']; ?>]<br>
												<span style="background-color:#fcc;">[Shipped: <?= $s2['shipped_date']; ?>]</span> -&gt; <span style="background-color:#cfc;">[Shipped: <?= $s2_now['shipped_date']; ?>]</span><br>
												[Order Details: <span style="background-color:#fcc;"><?= $s2['order_id']; ?></span> -&gt; <span style="background-color:#cfc;"><?= $s2_now['order_id']; ?></span><br>
												&nbsp;&nbsp;&nbsp;&nbsp;- <?= $s2_now['products_model']; ?><br>
												&nbsp;&nbsp;&nbsp;&nbsp;- <?= $s2_now['date_purchased']; ?>]
												<?php } ?>
											</div>
										</div>
										<div style="clear:both; margin-top:10px; border-top:1px solid #000; padding:10px;">
											<input type="submit" value="Swap">
										</div>
									</form>
								</div>
								<script>
									jQuery('#autocomplete_input').autocomplete({
										minChars: 3,
										source: function(request, response) {
											jQuery.ajax({
												minLength: 2,
												url: '/admin/serials_ajax.php',
												dataType: 'json',
												data: {
													action: 'generic_autocomplete',
													search_type: 'serial',
													term: request.term,
													search_all: 1
												},
												success: function(data) {
													response(jQuery.map(data, function(item) {
														return { misc: item.label, label: item.label, value: item.label, id: item.value };
													}));
												}
											});
										},
										select: function(event, ui) {
											console.log(ui);
											jQuery('#auto_input_serial_id').val(ui.item.id);
											jQuery.ajax({
												url: '/admin/serials-swap.php',
												dataType: 'text',
												data: {
													action: 'get-serial-details',
													serial_id: ui.item.id
												},
												success: function(data) {
													jQuery('#input-details').html(data);
												}
											});
										}
									});
									jQuery('#autocomplete_output').autocomplete({
										minChars: 3,
										source: function(request, response) {
											jQuery.ajax({
												minLength: 2,
												url: '/admin/serials_ajax.php',
												dataType: 'json',
												data: {
													action: 'generic_autocomplete',
													search_type: 'serial',
													term: request.term,
													search_all: 1
												},
												success: function(data) {
													response(jQuery.map(data, function(item) {
														return { misc: item.label, label: item.label, value: item.label, id: item.value };
													}));
												}
											});
										},
										select: function(event, ui) {
											console.log(ui);
											jQuery('#auto_output_serial_id').val(ui.item.id);
											jQuery.ajax({
												url: '/admin/serials-swap.php',
												dataType: 'text',
												data: {
													action: 'get-serial-details',
													serial_id: ui.item.id
												},
												success: function(data) {
													jQuery('#output-details').html(data);
												}
											});
										}
									});
								</script>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</body>
</html>
