<?php
require('includes/application_top.php');

require_once('includes/modules/accounting_notes.php');

if (!isset($_GET['sort'])) {$_GET['sort'] == 'name';}

if ($_GET['content'] == 'receivables') {
	$companies = ck_customer2::get_customers_with_outstanding_invoices();
	switch ($_GET['sort']) {
		default:
		case 'name':
			usort($companies, function($a, $b) {
				return strnatcasecmp($a->get_prop('display_label'), $b->get_prop('display_label'));
			});
			break;
		case 'r_name':
			usort($companies, function($a, $b) {
				return -1*strnatcasecmp($a->get_prop('display_label'), $b->get_prop('display_label'));
			});
			break;
		case 'age':
			usort($companies, function($a, $b) {
				$aage = $a->get_prop('customer_age');
				$bage = $b->get_prop('customer_age');
				if ($aage == $bage) return 0;
				else return $aage<$bage?-1:1;
			});
			break;
		case 'r_age':
			usort($outstanding_invoices, function($a, $b) {
				$aage = $a->get_prop('customer_age');
				$bage = $b->get_prop('customer_age');
				if ($aage == $bage) return 0;
				else return $aage<$bage?1:-1;
			});
			break;
		case 'terms':
			usort($companies, function($a, $b) {
				$aterms = $a->get_prop('terms');
				$bterms = $b->get_prop('terms');
				if ($aterms == $bterms) return 0;
				else return $aterms<$bterms?-1:1;
			});
			break;
		case 'r_terms':
			usort($companies, function($a, $b) {
				$aterms = $a->get_prop('terms');
				$bterms = $b->get_prop('terms');
				if ($aterms == $bterms) return 0;
				else return $aterms<$bterms?1:-1;
			});
			break;
	}
	?>
	<div class="acc_title" style="float:left; width:1100px;">Receivables</div>
	<div class="acc_content_box" style="float:left;">
		<div class="left_350"><strong><a href="javascript: void(0);" onclick="dashboard_content('receivables',0,'<?= ($_GET['sort']=='r_name')? 'name' : 'r_name'?>');" style="font-weight:bold; color:#15489E; font-size: 12px">Company</a></strong></div>
		<div class="left_160"><strong>Outstanding Invoices</strong></div>
		<div class="left_140"><strong>Account Credits</strong></div>
		<div class="left_140"><strong>Account Balance</strong></div>
		<div class="left_100"><strong><a href="javascript: void(0);" onclick="dashboard_content('receivables',0,'<?= ($_GET['sort']=='r_terms')? 'terms' : 'r_terms'?>');" style="font-weight:bold; color:#15489E; font-size: 12px">Terms<a/></strong></div>
		<div class="left_80"><strong><a href="javascript: void(0);" onclick="dashboard_content('receivables',0,'<?= ($_GET['sort']=='r_age')? 'age' : 'r_age'?>');" style="font-weight:bold; color:#15489E; font-size: 12px">Age</a></strong></div>
		<div class="left_80"><strong>Actions</strong></div>
		<?php foreach ($companies as $idx => $customer) {
			$nominal_balance = $customer->get_prop('customer_balance');
			$unapplied_credit_total = $customer->get_prop('unapplied_total'); ?>
		<div style="clear:left; float:left; width:1100px; height:30px;padding-top:5px; background-color:<?= ($idx%2)? '#EFEFEF': '#FFF'?>" class="customer_container" id="customer_<?= $customer->id(); ?>">
			<div class="left_350">
				<span style="width: 20px;" class="acc_expand"><a class="order_expand" id="<?= $customer->id(); ?>" href="#">+</a></span>
				<a href="customer_account_history.php?customer_id=<?= $customer->id(); ?>"><?= $customer->get_prop('display_label'); ?></a>
				<?php insert_accounting_notes_manager($customer->id(), null, 'notes'); ?>
			</div>
			<div class="left_140 monetary" style="vertical-align:text-bottom;"><?= CK\text::monetize($nominal_balance); ?></div>
			<div class="left_120 monetary"><?= CK\text::monetize($unapplied_credit_total); ?></div>
			<div class="left_140 monetary"><?= CK\text::monetize($nominal_balance - $unapplied_credit_total); ?></div>
			<div class="left_100" style="padding-left: 40px;"><?= $customer->get_prop('terms_label'); ?></div>
			<div class="left_80"><?= $customer->get_prop('customer_age'); ?></div>
			<div class="left_80">
				<a href="acc_enter_payment.php?customer_id=<?= $customer->id(); ?>"><img src="images/dollarbill.png" title="Enter Payment/Credit"/></a>
				<a href="acc_issue_refund.php?customer_id=<?= $customer->id(); ?>" style="margin-left: 10px;"><img src="images/user_go.png" title="Issue Refund"/></a>
			</div>
		</div>
		<div class="customer_orders" id="orders_<?= $customer->id(); ?>" style="display:none;"></div>
		<?php } ?>
	</div>
<?php }
elseif ($_GET['content'] == 'unposted') {
	$results = prepared_query::fetch('SELECT p.customer_id, SUM(p.payment_amount - (SELECT IF(SUM(p2i.credit_amount) IS NULL, 0, SUM(p2i.credit_amount)) FROM acc_payments_to_invoices p2i WHERE p2i.payment_id = p.payment_id)) AS amount FROM acc_payments p GROUP BY p.customer_id HAVING amount > 0', cardinality::SET); ?>
	<div class="acc_title">Unposted Payments</div>
	<div class="acc_content_box" style="float:left">
		<div class="left_350"><strong><a href="javascript: void(0);" onclick="dashboard_content('unposted',0,'<?= ($_GET['sort']=='r_name')? 'name' : 'r_name'?>');" style="font-weight:bold; color:#000; font-size: 12px">Company</a></strong></div>
		<div class="left_160"><strong>Unapplied Payments</strong></div>
		<?php $unposted_total = 0;
		$row_col = 0;
		foreach ($results as $result) {
			$row_col++;
			$unposted_total += $result['amount'];
			$customer = new ck_customer2($result['customer_id']); ?>
		<div style="clear:left; float:left; width:1100px; height:30px;padding-top:5px; background-color:<?= ($row_col%2)? '#EFEFEF': '#FFF'?>" class="customer_container" id="customer_<?= $customer->id(); ?>">
			<div class="left_350">
				<span style="width: 20px;" class="acc_expand"><a class="payment_expand" id="<?= $customer->id(); ?>" href="#">+</a></span>
				<a href="/admin/outstanding_invoices_by_customer.php?customers_id=<?= $customer->id(); ?>"><?= $customer->get_highest_name(); ?></a>
			</div>
			<div class="left_140" style="vertical-align:text-bottom;"><?= money_format('%n', $result['amount'])?></div>
		</div>

		<div style="clear:left;float:left;display:none; width:900px; margin-top:3px;" id="payment_<?= $customer->id(); ?>">
			<div style="clear:left; float:left;padding-left:50px; float:left; width:125px; height:30px;"><strong>Payment Type</strong></div>
			<div class="left_150"><strong>Ref</strong></div>
			<div class="left_100"><strong>Date</strong></div>
			<div class="left_100"><strong>Amount</strong></div>
			<div class="left_100"><strong>Available</strong></div>
			<div style="float:left; width:150px; height:30px;"><strong>Actions</strong></div>
			<?php $credits = $customer->get_unapplied_payments();
			foreach ($credits as $credit) { ?>
			<div style="clear:left; float:left; width: 900px;">
				<div style="float:left; padding-left:50px; width:125px; height:30px;"><?= $credit['payment_method_label']; ?></div>
				<div class="left_150"><?= $credit['payment_ref']; ?></div>
				<div class="left_100"><?= $credit['payment_date']->format('Y-m-d'); ?></div>
				<div class="left_100"><?= CK\text::monetize($credit['payment_amount'])?></div>
				<div class="left_100"><?= CK\text::monetize($credit['unapplied_amount'])?></div>
				<div style="float:left; width:150px; height:30px;"><a href="acc_apply_credit2.php?customer_id=<?= $customer->id(); ?>&payment_id=<?= $credit['payment_id']; ?>"><img src="images/arrow.png"/></a></div>
			</div>
			<?php } ?>
		</div>
		<?php } ?>
		<div>Total Unposted Payments: <b><?= money_format('%n', $unposted_total);?></b></div>
	</div>
<?php }
elseif ($_GET['content'] == 'history') {
	$company = new ck_customer2($_GET['customer_id']); ?>
	<div class="acc_title"><?= $company->get_highest_name(); ?></div>
	<div class="acc_content_box" style="float:left">
		<div class="left_250"><strong>Customer</strong></div>
		<div class="left_80"><strong>Invoice</strong></div>
		<div class="left_100"><strong>Order Id</strong></div>
		<div class="left_100"><strong>Terms</strong></div>
		<div class="left_80"><strong>Date</strong></div>
		<div class="left_80"><strong>Age</strong></div>
		<div class="left_100"><strong>Total</strong></div>
		<div class="left_80"><strong>Payments</strong></div>
		<div class="left_80"><strong>Balance</strong></div>
		<div class="left_80"><strong>Actions</strong></div>
		<?php foreach ($company->get_outstanding_invoices_direct() as $invoice) {
			$row_col++; ?>
		<div style="clear:left; float:left; width:1100px; height:30px;padding-top:5px; background-color:<?= ($row_col%2)? '#EFEFEF': '#FFF'?>" class="customer_container">
			<div style="float:left; width:250px; height:30px;">
				<?= $invoice->get_name(); ?>
			</div>
			<div class="left_80" style="vertical-align:text-bottom;"><?= $invoice->id(); ?></div>
			<div class="left_100"><?= $invoice->get_header('orders_id'); ?></div>
			<div class="left_100"><?= $company->has_terms()?$company->get_terms('label'):'None'; ?></div>
			<div class="left_80"><?= $invoice->get_header('invoice_date')->format('Y-m-d'); ?></div>
			<div class="left_80"><?= $invoice->get_age(); ?></div>
			<div class="left_100"><?= CK\text::monetize($invoice->get_simple_totals('total')); ?></div>
			<div class="left_80"><?= CK\text::monetize($invoice->get_paid()); ?></div>
			<div class="left_80"><?= CK\text::monetize($invoice->get_balance()); ?></div>
			<div class="left_80">
				<a href="acc_apply_credit.php?customer_id=<?= $company->id(); ?>&order_id=<?= $invoice->get_header('orders_id'); ?>"><img src="images/arrow.png"/></a>
			</div>
		</div>
		<?php } ?>
	</div>
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
	<div style="clear: both;"></div>
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
