<?php
$boxitems = [
	'id' => 'accounting',
	'title' => 'Accounting',
	'sort' => [0 => 'individual', 'Reports' => 'groups'],
	'individual' => [
		['file' => 'customer_account_history.php', 'text' => 'Account History'],
		['file' => 'acc_dashboard.php', 'text' => 'Dashboard'],
	],
	'groups' => [
		'Reports' => [
			['file' => 'cogs_report.php', 'text' => 'COGS Report'],
			['file' => 'inventory_report.php', 'text' => 'Inventory Report'],
			['file' => 'stats_invoices.php', 'text' => 'Invoice Stats'],
			['file' => 'paypal_accounting_compare.php', 'text' => 'PayPal Accounting Compare'],
			['file' => 'outstanding_invoices_report.php', 'text' => 'AR Report'],
			['file' => 'reconcile_report.php', 'text' => 'Reconcile Payments & Refunds'],
			['file' => 'outstanding_po_report.php', 'text' => 'Outstanding POs'],
			['file' => 'sales_tax_report.php', 'text' => 'Sales Tax Report'],
			['file' => 'payables_report.php', 'text' => 'Payables Report'],
			['file' => 'credit_card_pos_report.php', 'text' => 'CC POs Report'],
			['file' => 'sales-commission-accounting.php', 'text' => 'Sales Commission Accounting'],
			['file' => 'manage-tax-liabilities', 'text' => 'Manage Tax Liabilities'],
		],
	]
]; ?>
<h3 id="<?= $boxitems['id']; ?>"><a href="#"><?= $boxitems['title']; ?></a></h3>
<div>
	<?php foreach ($boxitems['sort'] as $boxgroup => $boxtype) {
		if ($boxtype == 'groups') { ?>
	<span class="subsection"><?= $boxgroup; ?></span>
		<?php } ?>
	<ul>
		<?php $boxset = $boxtype=='individual'?$boxitems['individual']:$boxitems['groups'][$boxgroup];
		foreach ($boxset as $boxlink) {
			if (tep_admin_check_boxes($boxlink['file'])) { ?>
		<li><a href="/admin/<?= $boxlink['file']; ?>?<?= @$boxlink['parameters']; ?>selected_box=<?= $boxitems['id']; ?>"><?= $boxlink['text']; ?></a></li>
			<?php }
		} ?>
	</ul>
	<?php } ?>
</div>
