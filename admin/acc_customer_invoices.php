<?php
require('includes/application_top.php');

set_time_limit(0);
@ini_set("memory_limit","512M");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
		<title><?= TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css" />
		<link rel="stylesheet" type="text/css" href="acc_dashboard.css" />
		<script type="text/javascript" src="includes/menu.js"></script>
		<script type="text/javascript" src="includes/general.js"></script>
</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php
		require(DIR_WS_INCLUDES.'header.php');
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$("#myTable").tablesorter({
				headers: {
					9: {
						sorter: false
					}
				}
			});
		});
	</script>
	<!-- header_eof //-->
	<!-- body //-->
		<table border="0" width="100%" cellspacing="2" cellpadding="2">
			<tr>
				<td class="noPrint" width="<?= BOX_WIDTH; ?>" valign="top">
					<table border="0" width="<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
						<!-- left_navigation //-->
						<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
						<!-- left_navigation_eof //-->
					</table>
				</td>
				<!-- body_text //-->
				<td width="100%" valign="top">
					<?php if (empty($_GET['sort'])) $_GET['sort'] = 'name';
					if ($_GET['content'] == 'history') {
						$company = new ck_customer2($_GET['customer_id']); ?>
					<div style="width: 1100px; padding-right:20px; padding-top:3px;" class="noPrint">
						<a href="acc_dashboard.php?selected_box=outstanding_invoices">&laquo; Dashboard</a>
						<div style="float:right;">
							<a href="acc_enter_payment.php?customer_id=<?= $company->id(); ?>"><img style="border: none;" src="images/dollarbill.png" title="Enter Payment/Credit"/></a>
							<a href="acc_issue_refund.php?customer_id=<?= $company->id(); ?>" style="margin-left: 10px;"><img style="border: none;" src="images/user_go.png" title="Issue Refund"/></a>
						</div>
						<div style="clear: both;"></div>
					</div>
					<div class="acc_title" style="clear:left; width:1100px; border-top:1px solid #666;"><?= $company->get_highest_name(); ?></div>
					<table id="myTable" class="acc_content_box tablesorter" style="float:left; width: 1100px;">
						<thead>
							<tr>
								<th>Customer</th>
								<th class="numeric">Invoice</th>
								<th class="numeric">Order</th>
								<th>Terms</th>
								<th>Date</th>
								<th class="numeric">Age</th>
								<th class="numeric">Total</th>
								<th class="numeric">Payments</th>
								<th class="numeric">Balance</th>
								<th class="numeric noPrint">Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($company->get_outstanding_invoices_direct() as $invoice) { ?>
							<tr>
								<td class="noPrint"><?= $invoice->get_name(); ?></td>
								<td class="numeric" style="padding-right: 10px;"><?= $invoice->id(); ?></td>
								<td class="numeric" style="padding-right: 5px;"><?= $invoice->get_header('orders_id'); ?></td>
								<td style="padding-left: 5px;"><?= $company->has_terms()?$company->get_terms('label'):'None'; ?></td>
								<td><?= $invoice->get_header('invoice_date')->format('Y-m-d'); ?></td>
								<td class="numeric" style="padding-right: 5px;"><?= $invoice->get_age(); ?></td>
								<td class="numeric"><?= CK\text::monetize($invoice->get_simple_totals('total')); ?></td>
								<td class="numeric"><?= CK\text::monetize($invoice->get_paid()); ?></td>
								<td class="numeric"><?= CK\text::monetize($invoice->get_balance()); ?></td>
								<td class="numeric noPrint"><a href="/admin/acc_apply_credit.php?customer_id=<?= $company->id(); ?>&invoice_id=<?= $invoice->id(); ?>"><img src="images/arrow.png"/></a></td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
					<?php }
					elseif ($_GET['content'] == 'customer_orders') {
						$customer = new ck_customer2($_GET['customer_id']);
						$outstanding_invoices = $customer->get_outstanding_invoices_direct();
						switch ($_GET['sort']) {
							default:
							case 'order':
								usort($outstanding_invoices, function($a, $b) {
									$aoid = $a->get_header('orders_id');
									$boid = $b->get_header('orders_id');
									if ($aoid == $boid) return 0;
									else return $aoid<$boid?-1:1;
								});
								break;
							case 'r_order':
								usort($outstanding_invoices, function($a, $b) {
									$aoid = $a->get_header('orders_id');
									$boid = $b->get_header('orders_id');
									if ($aoid == $boid) return 0;
									else return $aoid<$boid?1:-1;
								});
								break;
							case 'age':
								usort($outstanding_invoices, function($a, $b) {
									$aage = $a->get_age();
									$bage = $b->get_age();
									if ($aage == $bage) return 0;
									else return $aage<$bage?-1:1;
								});
								break;
							case 'r_age':
								usort($outstanding_invoices, function($a, $b) {
									$aage = $a->get_age();
									$bage = $b->get_age();
									if ($aage == $bage) return 0;
									else return $aage<$bage?1:-1;
								});
								break;
						}
						?>
					<div class="left_250" style="clear:left;padding-left:50px;"><strong>Customer</strong></div>
					<div class="left_80"><strong><a href="javascript:get_customer_accounting('<?= $customer->id(); ?>', '<?= $_GET['sort']=='order'?'r_order':'order'; ?>')"><b>Order</b></a></strong></div>
					<div class="left_80"><strong><a href="javascript:get_customer_accounting('<?= $customer->id(); ?>', '<?= $_GET['sort']=='age'?'r_age':'age'; ?>')"><b>Age</b></a></strong></div>
					<div class="left_160"><strong>Amount / Remaining</strong></div>
					<div style="float:left; width:10px; height:30px; border-left: 1px solid #f2f2f2;">&nbsp;</div>
					<div class="left_150"><strong>Actions</strong></div>
						<?php if (!empty($outstanding_invoices)) {
							foreach ($outstanding_invoices as $invoice) { ?>
					<div style="clear:left; float:left; width: 900px;">
						<div style="float:left; padding-left:50px; width:250px; height:30px;">
							<a href="/admin/orders_new.php?customers_id=<?= $customer->id(); ?>"><?= $invoice->get_name(); ?></a>
						</div>
						<div class="left_80">
							<a href="orders_new.php?selected_box=orders&oID=<?= $invoice->get_header('orders_id'); ?>&action=edit"><?= $invoice->get_header('orders_id'); ?></a>
						</div>
						<div class="left_80"><?= $invoice->get_age(); ?></div>
						<div class="left_160"><?= CK\text::monetize($invoice->get_simple_totals('total')); ?> / <?= CK\text::monetize($invoice->get_balance()); ?></div>
						<div style="float:left; width:10px; height:30px; border-left: 1px solid #f2f2f2;">&nbsp;</div>
						<div class="left_150">
							<a href="acc_apply_credit.php?customer_id=<?= $customer->id(); ?>&order_id=<?= $invoice->get_header('orders_id'); ?>"><img src="images/arrow.png"/></a>
						</div>
					</div>
							<?php }
						}
						else { ?>
					<div style="clear:left; float:left; width: 900px;">No orders found</div>
						<?php }
					} ?>
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
<html>
