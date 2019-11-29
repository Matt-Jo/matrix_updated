<?php
require('includes/application_top.php');

if (!empty($_GET)) {
	if (!empty($_GET['orders_id'])) {
		$result = prepared_query::fetch("SELECT o.orders_note_text as text, DATE_FORMAT(o.orders_note_created, '%m/%d/%Y %l:%i %p') as fdate, CONCAT(a.admin_firstname, ' ', a.admin_lastname) as name FROM orders_notes o LEFT JOIN admin a ON o.orders_note_user = a.admin_id WHERE orders_id = ?", cardinality::SET, $_GET['orders_id']); ?>
		<html>
		<body>
			<style>
				td { font-size: 12px; }
			</style>
			<center><h3>Admin Notes</h3></center>
			<table border=1 cellpadding=0 cellspacing=0>
				<tr>
					<td>Comment Date</td>
					<td>Admin's Name</td>
					<td>Comment</td>
				</tr>
				<?php foreach ($result as $row) { ?>
				<tr>
					<td><?= $row['fdate']; ?></td>
					<td><?= $row['name']; ?></td>
					<td><?= $row['text']; ?></td>
				</tr>
				<?php } ?>
			</table>
		</body>
		</html>
		<?php exit();
	}

	$args = array();
	$operators = array();
	$direct_clause = array();
	$direct_args = array();

	if (!empty($_GET['ipn'])) $args['psc.stock_name'] = $_GET['ipn'];
	if (!empty($_GET['model'])) $args['p.products_model'] = $_GET['model'];
	if (!empty($_GET['customer'])) $args['o.customers_name'] = $_GET['customer'];
	if (!empty($_GET['customer_id'])) $args['o.customers_id'] = $_GET['customer_id'];
	if (!empty($_GET['company'])) $args['o.customers_company'] = $_GET['company'];
	if (!empty($_GET['date_from'])) {
		$direct_clause[] = 'o.date_purchased > ?';
		$direct_args[] = $_GET['date_from'];
		//$operators['o.date_purchased'] = '>';
	}
	if (!empty($_GET['date_to'])) {
		$direct_clause[] = 'o.date_purchased < ?';
		$direct_args[] = $_GET['date_to'];
		//$operators['o.date_purchased'] = '<';
	}
	if (!empty($_GET['customer_price_level_id'])) $args['c.customer_price_level_id'] = $_GET['customer_price_level_id'];
	if (!empty($_GET['ipn_category'])) {
		$args['psc.products_stock_control_category_id'] = $_GET['ipn_category'];
		$ipn_select = $_GET['ipn_category'];
	}
	if (!empty($_GET['manufacturer'])) {
		$args['p.manufacturers_id'] = $_GET['manufacturer'];
		$man_select = $_GET['manufacturer'];
	}

	$params = new ezparams($args);

	$result = prepared_query::fetch("SELECT psc.stock_id as ipn, psc.stock_name, p.products_model, c.customers_id, c.customer_price_level_id, cpl.price_level, o.orders_id, DATE(o.date_purchased) as date_purchased, os.orders_status_name, o.customers_name, o.customers_company, op.products_quantity, op.final_price, psc.dealer_price, psc.stock_price, CONCAT(a.admin_firstname, ' ', a.admin_lastname) as admin FROM orders o LEFT JOIN orders_products op ON o.orders_id = op.orders_id LEFT JOIN products p ON p.products_id = op.products_id LEFT JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN customers c ON c.customers_id = o.customers_id LEFT JOIN admin a ON o.orders_sales_rep_id = a.admin_id LEFT JOIN customer_price_levels cpl ON c.customer_price_level_id = cpl.customer_price_level_id WHERE psc.products_stock_control_category_id NOT IN (90) ".(!empty($args)?'AND '.$params->where_cols:'')." ".(!empty($direct_clause)?'AND '.implode(' AND ', $direct_clause):'')." ORDER BY o.orders_id DESC LIMIT 1000", cardinality::SET, $params->query_vals($direct_args));
}

$run_ipn_categories = prepared_query::fetch('SELECT * FROM products_stock_control_categories ORDER BY sort_order', cardinality::SET);
$ipn_cats = '<option value = "">Please Select</option>';
foreach ($run_ipn_categories as $ipn_categories) {
	if ($ipn_categories['name'] != '') {
		if (@$ipn_select == $ipn_categories['categories_id']) $select = 'selected';
		$ipn_cats .= '<option value="'.$ipn_categories['categories_id'].'" '.@$select.'>'.$ipn_categories['name'].'</option>';
		$select = '';
	}
}

$run_manufacturers = prepared_query::fetch('SELECT manufacturers_id, manufacturers_name FROM manufacturers ORDER BY sort_order', cardinality::SET);
$manufacturers = '<option value = "">Please Select</option>';
foreach ($run_manufacturers as $manufacturers_result) {
	if ($manufacturers_result['manufacturers_name'] != '') {
		if (@$man_select == $manufacturers_result['manufacturers_id']) $select = 'selected';
		$manufacturers .= '<option value="'.$manufacturers_result['manufacturers_id'].'" '.$select.'>'.$manufacturers_result['manufacturers_name'].'</option>';
		$select = '';
	}
} ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<script language="javascript" src="includes/menu.js"></script>
	<script language="javascript" src="includes/general.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.tooltip_ipn div').each(function() {
				$(this).qtip({
					content: {
						url: 'advanced_search.php',
						data: { orders_id: $(this).attr('tooltip') },
						method: 'get'
					},
					show: 'mouseover',
					hide: 'mouseout',
					position: {
						corner: {
							tooltip: 'bottomLeft',
							target: 'topRight'
						}
					},
					style: {
						backgroundColor: '#F2F2F2',
						tip: {
							corner: 'bottomLeft'
						},
						border: {
							color: '#000000'
						},
						width: '600px',
						name: 'red'
					}
				});
			});

			$('#ipn').autocomplete({
				minChars: 3,
				source: '/admin/serials_ajax.php?action=ipn_autocomplete'
			}).keypress(function(event) {
				keypressHelper(event, this);
			});

			$('#model').autocomplete({
				minChars: 1,
				source: '/admin/serials_ajax.php?action=model_autocomplete'
			}).keypress(function(event) {
				keypressHelper(event, this);
			});

			$('#customer').autocomplete({
				minChars: 3,
				source: '/admin/serials_ajax.php?action=customer_autocomplete'
			}).keypress(function(event) {
				keypressHelper(event, this);
			});

			$('#company').autocomplete({
				minChars: 3,
				source: '/admin/serials_ajax.php?action=company_autocomplete'
			}).keypress(function(event) {
				keypressHelper(event, this);
			});
			$('#date_from').datepicker({dateFormat: 'yy-mm-dd'});
			$('#date_to').datepicker({dateFormat: 'yy-mm-dd'});
			$('#advanced_form').bind("keypress", function(event) {
				// Prevent form submit on enter key
				if (event.keyCode == 13) {
					return false;
				}
			});
		});

		function keypressHelper(event, object) {
			var action = object.id;
			var value = jQuery(object).val();
			if ((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {
				var info = $('.ui-menu-item');
				jQuery.ajax({
					url: '/admin/serials_ajax.php',
					dataType: 'json',
					data: {
						term: value,
						action: action+'_autocomplete',
						limit: '1',
						get_ipn: 1,
						search_all: 1
					},
					success: function(data) {
						if (data != null) {
							var label = data[0].label;
							if (action == 'model') {
								label = label.split(' ')[0];
							}
							jQuery(object).val(label);
						}
					}
				});
			}
		}
	</script>
	<style>
		img { border-width:0px; }
		span.tooltip_ipn div { float: left; }
	</style>
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
			<td width="100%" valign="top">
				<form id="advanced_form" action="" method="GET">
					<table>
						<tr>
							<td>
								<table>
									<tr>
										<td>IPN:</td>
										<td><input type="text" name="ipn"	id="ipn"	value="<?=@$_GET['ipn']?>"></td>
									</tr>
									<tr>
										<td>Model:</td>
										<td><input type="text" name="model"	id="model"	value="<?=@$_GET['model']?>"></td>
										<?php /*input type="hidden" name="hidden_model" id="hidden_model" value="<?=$_GET['hidden_model']?>" /*/?>
									</tr>
									<tr>
										<td>Customer:</td>
										<td><input type="text" name="customer"	id="customer"	value="<?=@$_GET['customer']?>"></td>
									</tr>
									<tr>
										<td>Company:</td>
										<td><input type="text" name="company"	id="company"	value="<?=@$_GET['company']?>"></td>
									</tr>
									<tr>
										<td>From:</td>
										<td><input type="text" name="date_from"	id="date_from"	value="<?=@$_GET['date_from']?>"></td>
									</tr>
									<tr>
										<td>To:</td>
										<td><input type="text" name="date_to"	id="date_to"	value="<?=@$_GET['date_to']?>"></td>
									</tr>
									<tr>
										<td><input type="submit" value="Search" />
										<td><a href="advanced_search.php">Reset</a></td>
									</tr>
								</table>
							</td>
							<td width="100">&nbsp;</td>
							<td>
								<table>
									<tr>
										<td>
											Customer_id
										</td>
										<td>
											<input name="customer_id" id="customer_id" />
										</td>
									</tr>
									<tr>
										<td>Customer Price Level:</td>
										<td>
											<select name="customer_price_level_id">
												<option value="">Please Select</option>
												<?php $price_levels = prepared_query::fetch('SELECT * FROM customer_price_levels');
												foreach ($price_levels as $price_level) { ?>
												<option value="<?= $price_level['customer_price_level_id']; ?>" <?= @$_GET['customer_price_level_id']==$price_level['customer_price_level_id']?'selected':''; ?>><?= $price_level['price_level']; ?></option>
												<?php } ?>
											</select>
										</td>
									</tr>
									<tr>
										<td>IPN Category:</td>
										<td>
											<select name="ipn_category">
												<?= $ipn_cats; ?>
											</select>
										</td>
									</tr>
									<tr>
										<td>Manufacturer:</td>
										<td>
											<select name="manufacturer">
												<?= $manufacturers; ?>
											</select>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</form>
				<table cellspacing="0" cellpadding="2px" border="0">
					<tr class="dataTableHeadingRow">
						<td class="dataTableHeadingContent" width="100">IPN</td>
						<td class="dataTableHeadingContent" width="100">Model#</td>
						<td class="dataTableHeadingContent" width="75">Order#</td>
						<td class="dataTableHeadingContent" width="100">Order Date</td>
						<td class="dataTableHeadingContent" width="150">Order Status</td>
						<td class="dataTableHeadingContent" width="200">Account Manager</td>
						<td class="dataTableHeadingContent" width="75">Customer Id</td>
						<td class="dataTableHeadingContent" width="200">Company</td>
						<td class="dataTableHeadingContent" width="75">Customer Name</td>
						<td class="dataTableHeadingContent" width="">Customer Price Level</td>
						<td class="dataTableHeadingContent" width="75">Qty</td>
						<td class="dataTableHeadingContent" width="75">Sell Price</td>
						<td class="dataTableHeadingContent" width="75">Stock Price</td>
						<td class="dataTableHeadingContent" width="75">Dealer Price</td>
					</tr>
					<?php if (!empty($result)) {
						$i = 0;
						foreach ($result as $row) {
							$i++;
							if ($i%100 == 0) flush(); ?>
					<tr class="dataTableRow<?= $i%2?'Selected':''; ?>" style="color:rgb(255, 0, 0);">
						<td class="main"><a href="ipn_editor.php?ipnId=<?= $row['stock_name']; ?>"><?= $row['stock_name']; ?></a></td>
						<td class="main"><?= $row['products_model']; ?></td>
						<td class="main">
							<a href="orders_new.php?selected_box=orders&status=2&oID=<?= $row['orders_id']; ?>&action=edit" style="float:left;"><img src="images/icons/preview.gif"></a>
							<span class="tooltip_ipn"><div tooltip="<?=$row['orders_id']?>"><?=$row['orders_id']?></div></span>
						</td>
						<td class="main"><?= $row['date_purchased']; ?></td>
						<td class="main"><?= $row['orders_status_name']; ?></td>
						<td class="main"><?= $row['admin']; ?></td>
						<td class="main"><?= $row['customers_id']; ?></td>
						<td class="main"><?= $row['customers_company']; ?></td>
						<td class="main"><?= $row['customers_name']; ?></td>
						<td class="main"><?= $row['price_level']; ?></td>
						<td class="main"><?= $row['products_quantity']; ?></td>
						<td class="main">$<?php echo number_format($row['final_price'], 2)?></td>
						<td class="main">$<?= $row['stock_price']; ?></td>
						<td class="main">$<?= $row['dealer_price']; ?></td>
					</tr>
						<?php }
					} ?>
				</table>
			</td>
		</tr>
	</table>
	<!-- body_eof //-->
</body>
<html>
