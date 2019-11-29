<?php
require('includes/application_top.php');

$action = !empty($_GET['action'])?$_GET['action']:NULL;

if ($action == 'get_customers') {
	$product_id = $_GET['product_id'];

	$customers = prepared_query::fetch('select * from stock_notification sn left join customers c on c.customers_email_address=sn.email where product_id=:products_id', cardinality::SET, [':products_id' => $_GET['product_id']]); ?>
	<table border="0" width="100%" cellspacing="2" cellpadding="2" style="border: 1px solid #000;">
		<tr class="dataTableHeadingRow">
			<td class="dataTableHeadingContent">Email</td>
			<td class="dataTableHeadingContent">Customer Info</td>
			<td class="dataTableHeadingContent">QTY Desired</td>
			<td class="dataTableHeadingContent">Last Notification</td>
			<td class="dataTableHeadingContent">Phone Number</td>
			<td class="dataTableHeadingContent">Called</td>
			<td class="dataTableHeadingContent">Date Added</td>
			<td class="dataTableHeadingContent">End Date</td>
			<td class="dataTableHeadingContent">Delete</td>
		</tr>
		<?php foreach ($customers as $customer) { ?>
		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
			<td class="dataTableContent"><?= $customer['email']; ?></td>
			<td class="dataTableContent">
				<?php if ($customer['customers_id']) { ?>
				<a href='/admin/customers_detail.php?selected_box=customers&customers_id=<?= $customer['customers_id']; ?>'>Details</a> <a href='/admin/orders_new.php?customers_id=<?= $customer['customers_id']; ?>'>Orders</a>
				<?php } ?>
			</td>
			<td class="dataTableContent"><?= $customer['qty_desired']; ?></td>
			<td class="dataTableContent"><?= $customer['last_notified']; ?></td>
			<td class="dataTableContent"><?php if ($customer['phone'] > 1) {echo $customer['phone'];} elseif ($customer['customers_telephone'] > 1) { echo $customer['customers_telephone'];} ?></td>
			<td class="dataTableContent"><input type="checkbox" id="checkbox_<?php echo $customer['notification_id'].'_'.$customer['product_id']?>" <?php if ($customer['called']==1) echo 'checked'?> onClick="update_called(<?= $customer['notification_id']; ?>, <?= $customer['product_id']; ?> );"/></td>
			<td class="dataTableContent"><?= $customer['date_added']; ?></td>
			<td class="dataTableContent"><?= $customer['end_date']; ?></td>
			<td class="dataTableContent"><a href="javascript: void(0);" onclick="delete_customer(<?= $customer['notification_id']; ?>, <?= $customer['product_id']; ?>);"><img src="images/delete.png" border="0"></a></td>
		</tr>
		<?php } ?>
	</table>
	<?php
	exit();
 }
 elseif ($action == 'update_called') {
	$notification_id = $_GET['notification_id'];
	$checked = $_GET['checked'];
	prepared_query::execute('update stock_notification set called=:called where notification_id=:notification_id', [':called' => $checked, ':notification_id' => $notification_id]);
	exit();
 }
 elseif ($action == 'delete_customer') {
	$notification_id = $_GET['notification_id'];
	prepared_query::execute('delete from stock_notification where notification_id=:notification_id', [':notification_id' => $notification_id]);
	exit();
} ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/menu.js"></script>
	<script language="javascript" src="includes/general.js"></script>
	<script language="javascript" src="/includes/javascript/prototype.js"></script>
	<script language="javascript" src="/includes/javascript/scriptaculous/scriptaculous.js"></script>
	<script language="javascript">
		function get_customer(product_id) {
			if ($('product_'+product_id+'_row').visible()) $('product_'+product_id+'_row').hide();
			else {
				new Ajax.Updater('product_'+product_id+'_cell', 'product_notifications.php', {
					method:'get',
					parameters : {
						action : 'get_customers',
						product_id: product_id
					},
					onComplete: function(transport) {
						$('product_'+product_id+'_row').show();
					}
				});
			}
		}

		function update_called(notification_id, product_id) {
			if ($('checkbox_'+notification_id+'_'+product_id).checked) checked=1;
			else checked=0;

			new Ajax.Request('product_notifications.php', {
				method:'get',
				parameters : {
					action : 'update_called',
					notification_id: notification_id,
					checked: checked
				},
				onComplete: function(transport) {
					$('product_'+product_id+'_row').hide();
					get_customer(product_id);
				}
			});
		}

		function delete_customer(notification_id, product_id) {
			new Ajax.Request('product_notifications.php', {
				method:'get',
				parameters : {
					action : 'delete_customer',
					notification_id: notification_id,
				},
				onComplete: function(transport) {
					$('product_'+product_id+'_row').hide();
					get_customer(product_id);
				}
			});
		}
	</script>
	<link rel="stylesheet" type="text/css" href="/includes/javascript/scriptaculous/scriptaculous.css">
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
				<table border="0" width="100%" cellspacing="0" cellpadding="2">
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td class="dataTableContent">Products with Notifications</td>
									<td class="dataTableContent" align="right"></td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="2">
								<tr>
									<td valign="top">
										<table border="0" width="100%" cellspacing="0" cellpadding="2">
											<tr class="dataTableHeadingRow">
												<td class="dataTableHeadingContent">Waiting</td>
												<td class="dataTableHeadingContent">IPN</td>
												<td class="dataTableHeadingContent">Product</td>
												<td class="dataTableHeadingContent">In Stock</td>
											</tr>
											<?php
											$notifications_set = prepared_query::fetch("select product_id, COUNT(notification_id) as `count` from stock_notification group by product_id", cardinality::SET);
											foreach ($notifications_set as $notifications) {
												$prod = new ck_product_listing($notifications['product_id']);
												$qty = $prod->get_ipn()->get_inventory('salable'); ?>
											<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onClick="get_customer(<?= $notifications['product_id']; ?>);">
												<td class="dataTableContent"><?= $notifications['count']; ?></td>
												<td class="dataTableContent"><?= $prod->get_ipn()->get_header('ipn'); ?></td>
												<td class="dataTableContent"><?= $prod->get_header('products_name'); ?></td>
												<td class="dataTableContent"><?php echo $qty>0 ? tep_image('/admin/images/icons/tick.gif') : ''; ?></td>
											</tr>
											<tr class="dataTableRow" id="product_<?= $notifications['product_id']; ?>_row" style="display:none">
												<td id="product_<?= $notifications['product_id']; ?>_cell" colspan="3"></td>
											</tr>
											<?php } ?>
										</table>
									</td>
								</tr>
								<tr>
									<td colspan="3">
										<table border="0" width="100%" cellspacing="0" cellpadding="2"></table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
			<!-- body_text_eof //-->
		 </tr>
	</table>
	<!-- body_eof //-->
</body>
</html>
