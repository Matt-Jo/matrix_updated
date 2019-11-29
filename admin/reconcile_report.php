<?php
require_once('includes/application_top.php');

$type = 'refunds';
if (!empty($_GET['type']) && $_GET['type'] == 'payments') $type = 'payments';

if ($__FLAG['export']) {
	header('Content-disposition: attachment; filename='.$type.'-reconcile.xlsx');
	header('Content-Type: application/vnd.ms-excel');

	echo file_get_contents(__DIR__.'/data_management/'.$type.'-reconcile.xlsx');

	exit();
}

if (isset($_GET['start_date'])) $start_date = new DateTime($_GET['start_date']);
else $start_date = new DateTime(date('Y-m-01'));

if (isset($_GET['end_date'])) $end_date = new DateTime($_GET['end_date']);
else $end_date = new DateTime(date('Y-m-d'));

// handle SQL between for same date properly
$start_date->setTime(0, 0, 0);
$end_date->setTime(23, 59, 59);

if (!empty($_GET['settlement_adjust'])) {
	if ($_GET['settlement_adjust'] < 0) {
		$start_date->sub(new DateInterval('PT'.abs($_GET['settlement_adjust']).'H'));
		$end_date->sub(new DateInterval('PT'.abs($_GET['settlement_adjust']).'H'));
	}
	else {
		$start_date->add(new DateInterval('PT'.$_GET['settlement_adjust'].'H'));
		$end_date->add(new DateInterval('PT'.$_GET['settlement_adjust'].'H'));
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<style type="text/css">
		.dataTableHeadingContent { width: 100px; }
	</style>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<!-- header_eof //-->
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$("#tabs").tabs();

			//$('#start_date').datepicker({ dateFormat: 'yy-mm-dd' });
			//$('#end_date').datepicker({ dateFormat: 'yy-mm-dd' });
			$('#results').tablesorter();

			$('a#print').click(function() {
				window.print();
			});
		});
	</script>
	<!-- body //-->
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?= BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top">
				<style>
					#reconcile-form { display:block; clear:both; margin:10px 0px 0px 0px; }
					#export-link { clear:both; margin:5px 10px 0px 10px; }
					.print-box { font-size:8px; float:right; margin-right:20px; }
					.type-selector { font-size:12px; float:left; margin:10px 0px 10px 20px; font-family:arial; }
					.report-totals { font-family:arial; font-size:10px }
					.report-totals th, .report-totals td { padding:2px 6px 2px 3px; }
				</style>
				<div class="pageHeading">Reconcile Payments Report</div>

				<form id="reconcile-form" method="get" action="/admin/reconcile_report.php">
					<input type="hidden" name="type" value="<?= $type; ?>">
					Start Date <input type="date" id="start_date" name="start_date" value="<?= $start_date->format('Y-m-d'); ?>">
					End Date <input type="date" id="end_date" name="end_date" value="<?= $end_date->format('Y-m-d'); ?>">
					<?php if ($type == 'payments') { ?>
					[Adjust Time for Settlement
					<select name="settlement_adjust" size="1">
						<option value="0">+0 Hours</option>
						<option value="-6" <?= @$_GET['settlement_adjust']==-6?'selected':''; ?>>-6 Hours</option>
					</select>]
					<?php } ?>
					<input type="submit" value="Submit">
				</form>

				<div style="display:none;" id="export-link">
					<a href="/admin/reconcile_report.php?export=1&type=<?= $type; ?>">Export</a>
				</div>

				<div class="print-box"><a href="#" id="print">print</a></div>

				<div class="type-selector">
					<?php if ($type == 'refunds') { ?>
					Type: <strong>Refunds</strong>
					<a href="/admin/reconcile_report.php?type=payments&start_date=<?= $start_date->format('Y-m-d'); ?>&end_date=<?= $end_date->format('Y-m-d'); ?>">Payments</a>
					<?php }
					else { ?>
					Type: <a href="/admin/reconcile_report.php?type=refunds&start_date=<?= $start_date->format('Y-m-d'); ?>&end_date=<?= $end_date->format('Y-m-d'); ?>">Refunds</a>
					<strong>Payments</strong>
					<?php } ?>
				</div>

				<?php /* MMD - two possible different views to display - toggle based on 'type' param */
				if ($type == 'refunds') { ?>
				<div style="clear:both;">
					<table id="refund_totals" class="report-totals" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<th>Refund Total</th>
						</tr>
					</table>

					<table id="results" class="tablesorter">
						<thead>
							<tr>
								<th>Payment Id</th>
								<th>Customer Id</th>
								<th>Customer Name</th>
								<th>Payment Amount</th>
								<th>Payment Method</th>
								<th>Payment Ref</th>
								<th>Payment Date</th>
								<th>RMA Inv Id</th>
								<th>RMA Id</th>
								<th>Refund Inv ID</th>
								<th>Refund Total</th>
								<th>Refund Date</th>
							</tr>
						</thead>
						<tbody>
							<?php $totals = [];
							$refunds = prepared_query::fetch("SELECT ap.payment_id, ap.customer_id, CONCAT(c.customers_lastname, ', ', c.customers_firstname) as customer_name, ap.payment_amount, pm.label, ap.payment_ref, ap.payment_date, air.invoice_id as rma_invoice_id, air.rma_id as rma_id, ai.invoice_id as refund_invoice_id, ait.invoice_total_price as refund_total, ap2i.credit_date as refund_date FROM acc_payments ap LEFT JOIN customers c ON c.customers_id = ap.customer_id LEFT JOIN payment_method pm ON pm.id = ap.payment_method_id LEFT JOIN acc_invoices air ON air.credit_payment_id = ap.payment_id LEFT JOIN acc_payments_to_invoices ap2i ON ap.payment_id = ap2i.payment_id LEFT JOIN acc_invoices ai ON ai.invoice_id = ap2i.invoice_id LEFT JOIN acc_invoice_totals ait ON ai.invoice_id = ait.invoice_id and ait.invoice_total_line_type = 'ot_total' WHERE ap2i.credit_date > :start_date AND ap2i.credit_date < :end_date AND ai.inv_order_id IS NULL ORDER BY ap2i.credit_date asc", cardinality::SET, [':start_date' => $start_date->format('Y-m-d H:i:s'), ':end_date' => $end_date->format('Y-m-d H:i:s')]); //where ap.payment_method_id in (8, 9, 12)
							if (!empty($refunds)) {
								$workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
								$worksheet = $workbook->getSheet(0);
								$worksheet->setTitle('Reconcile Refunds');

								$worksheet->getCell('A1')->setValue('Payment Id');
								$worksheet->getCell('B1')->setValue('Customer Id');
								$worksheet->getCell('C1')->setValue('Customer Name');
								$worksheet->getCell('D1')->setValue('Payment Amount');
								$worksheet->getCell('E1')->setValue('Payment Method');
								$worksheet->getCell('F1')->setValue('Payment Ref');
								$worksheet->getCell('G1')->setValue('Payment Date');
								$worksheet->getCell('H1')->setValue('RMA Inv Id');
								$worksheet->getCell('I1')->setValue('RMA Id');
								$worksheet->getCell('J1')->setValue('Refund Inv ID');
								$worksheet->getCell('K1')->setValue('Refund Total');
								$worksheet->getCell('L1')->setValue('Refund Date');

								foreach ($refunds as $idx => $refund) {
									if (empty($totals['Refunds'])) $totals['Refunds'] = 0;
									$totals['Refunds'] += $refund['refund_total'];
									$refund['payment_date'] = new DateTime($refund['payment_date']);
									$refund['refund_date'] = new DateTime($refund['refund_date']);
									
									$worksheet->getCell('A'.($idx+2))->setValue($refund['payment_id']);
									$worksheet->getCell('B'.($idx+2))->setValue($refund['customer_id']);
									$worksheet->getCell('C'.($idx+2))->setValue($refund['customer_name']);
									$worksheet->getCell('D'.($idx+2))->setValue(CK\text::monetize($refund['payment_amount']));
									$worksheet->getCell('E'.($idx+2))->setValue($refund['label']);
									$worksheet->getCell('F'.($idx+2))->setValue($refund['payment_ref']);
									$worksheet->getCell('G'.($idx+2))->setValue($refund['payment_date']->format('M-d-Y'));
									$worksheet->getCell('H'.($idx+2))->setValue($refund['rma_invoice_id']);
									$worksheet->getCell('I'.($idx+2))->setValue($refund['rma_id']);
									$worksheet->getCell('J'.($idx+2))->setValue($refund['refund_invoice_id']);
									$worksheet->getCell('K'.($idx+2))->setValue(CK\text::monetize($refund['refund_total']));
									$worksheet->getCell('L'.($idx+2))->setValue($refund['refund_date']->format('M-d-Y')); ?>
							<tr>
								<td class="dataTableContent"><?= $refund['payment_id']; ?></td>
								<td class="dataTableContent"><?= $refund['customer_id']; ?></td>
								<td class="dataTableContent"><?= $refund['customer_name']; ?></td>
								<td class="dataTableContent"><?= CK\text::monetize($refund['payment_amount']); ?></td>
								<td class="dataTableContent"><?= $refund['label']; ?></td>
								<td class="dataTableContent"><?= $refund['payment_ref']; ?></td>
								<td class="dataTableContent"><?= $refund['payment_date']->format('M-d-Y'); ?></td>
								<td class="dataTableContent"><?= $refund['rma_invoice_id']; ?></td>
								<td class="dataTableContent"><?= $refund['rma_id']; ?></td>
								<td class="dataTableContent"><?= $refund['refund_invoice_id']; ?></td>
								<td class="dataTableContent"><?= CK\text::monetize($refund['refund_total']); ?></td>
								<td class="dataTableContent"><?= $refund['refund_date']->format('M-d-Y'); ?></td>
							</tr>
								<?php }

								$wb_file = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($workbook);
								$wb_file->save(__DIR__.'/data_management/refunds-reconcile.xlsx');
							} ?>
						</tbody>
					</table>
					<?php if (!empty($totals)) { ?>
					<script>
						jQuery('#export-link').show();
					</script>
						<?php foreach ($totals as $label => $amount) { ?>
					<input type="hidden" class="report-total-passer" name="<?= $label; ?>" value="<?= CK\text::monetize($amount); ?>">
						<?php }
					} ?>
					<script type="text/javascript">
						jQuery('.report-total-passer').each(function() {
							jQuery('#refund_totals').append('<tr><td>'+jQuery(this).val()+'</td></tr>');
						});
					</script>
				</div>
				<?php }
				elseif ($type == 'payments') { ?>
				<div style="clear:both;">
					<table id="payment_totals" class="report-totals" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<th>Payment Method</th>
							<th>Total</th>
						</tr>
					</table>

					<table id="results" class="tablesorter">
						<thead>
							<tr>
								<th>Payment Id</th>
								<th>Customer Id</th>
								<th>Customer Name</th>
								<th>Payment Amount</th>
								<th>Payment Method</th>
								<th>Payment Ref</th>
								<th>Payment Date</th>
							</tr>
						</thead>
						<tbody>
							<?php $totals = array();
							$payments = prepared_query::fetch("SELECT ap.payment_id, ap.customer_id, CONCAT(c.customers_lastname, ', ', c.customers_firstname) as customer_name, ap.payment_amount, pm.label, ap.payment_ref, ap.payment_date FROM acc_payments ap LEFT JOIN customers c ON c.customers_id = ap.customer_id LEFT JOIN payment_method pm ON pm.id = ap.payment_method_id WHERE ap.payment_date >= :start_date AND ap.payment_date < :end_date ORDER BY ap.payment_date ASC", cardinality::SET, [':start_date' => $start_date->format('Y-m-d H:i:s'), ':end_date' => $end_date->format('Y-m-d H:i:s')]); //where ap.payment_method_id not in (8, 9, 12) MMD - took this out from above query - 122812
							if (!empty($payments)) {
								$workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
								$worksheet = $workbook->getSheet(0);
								$worksheet->setTitle('Reconcile Payments');

								$worksheet->getCell('A1')->setValue('Payment Id');
								$worksheet->getCell('B1')->setValue('Customer Id');
								$worksheet->getCell('C1')->setValue('Customer Name');
								$worksheet->getCell('D1')->setValue('Payment Amount');
								$worksheet->getCell('E1')->setValue('Payment Method');
								$worksheet->getCell('F1')->setValue('Payment Ref');
								$worksheet->getCell('G1')->setValue('Payment Date');

								foreach ($payments as $idx => $payment) {
									$label = $payment['label'];
									if ($label == 'Credit Card') {
										$order = prepared_query::fetch('SELECT o.orders_id, o.legacy_order FROM orders o JOIN acc_invoices i ON o.orders_id = i.inv_order_id JOIN acc_payments_to_invoices pti ON i.invoice_id = pti.invoice_id WHERE pti.payment_id = :payment_id', cardinality::ROW, [':payment_id' => $payment['payment_id']]);
										if (empty($order)) $order = prepared_query::fetch('SELECT o.orders_id, o.legacy_order FROM orders o JOIN acc_payments_to_orders pto ON o.orders_id = pto.order_id WHERE pto.payment_id = :payment_id', cardinality::ROW, [':payment_id' => $payment['payment_id']]);
										
										$label .= !empty($order['legacy_order'])?' [PP]':' [BT]';
									}
									if (empty($totals[$label])) $totals[$label] = 0;
									$totals[$label] += $payment['payment_amount'];
									$payment['payment_date'] = new DateTime($payment['payment_date']);
									if (!empty($_GET['settlement_adjust'])) {
										if ($_GET['settlement_adjust'] < 0) $payment['payment_date']->add(new DateInterval('PT'.abs($_GET['settlement_adjust']).'H'));
										else $payment['payment_date']->sub(new DateInterval('PT'.$_GET['settlement_adjust'].'H'));
									}
									
									$worksheet->getCell('A'.($idx+2))->setValue($payment['payment_id']);
									$worksheet->getCell('B'.($idx+2))->setValue($payment['customer_id']);
									$worksheet->getCell('C'.($idx+2))->setValue($payment['customer_name']);
									$worksheet->getCell('D'.($idx+2))->setValue(CK\text::monetize($payment['payment_amount']));
									$worksheet->getCell('E'.($idx+2))->setValue($label);
									$worksheet->getCell('F'.($idx+2))->setValue($payment['payment_ref']);
									$worksheet->getCell('G'.($idx+2))->setValue($payment['payment_date']->format('M-d-Y')); ?>
							<tr class="pmttype-<?= preg_replace('/[^a-zA-Z0-9_-]/', '-', $label); ?>">
								<td class="dataTableContent"><?= $payment['payment_id']; ?></td>
								<td class="dataTableContent"><?= $payment['customer_id']; ?></td>
								<td class="dataTableContent"><?= $payment['customer_name']; ?></td>
								<td class="dataTableContent"><?= CK\text::monetize($payment['payment_amount']); ?></td>
								<td class="dataTableContent"><?= $label; ?></td>
								<td class="dataTableContent"><?= $payment['payment_ref']; ?></td>
								<td class="dataTableContent"><?= $payment['payment_date']->format('M-d-Y'); ?></td>
							</tr>
								<?php }

								$wb_file = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($workbook);
								$wb_file->save(__DIR__.'/data_management/payments-reconcile.xlsx');
							} ?>
						</tbody>
					</table>
					<?php if (!empty($totals)) { ?>
					<script>
						jQuery('#export-link').show();
					</script>
						<?php foreach ($totals as $label => $amount) { ?>
					<input type="hidden" class="report-total-passer" name="<?= $label; ?>" value="<?= CK\text::monetize($amount); ?>" data-selector-label="<?= preg_replace('/[^a-zA-Z0-9_-]/', '-', $label); ?>">
						<?php }
					} ?>
					<script type="text/javascript">
						jQuery('.report-total-passer').each(function() {
							jQuery('#payment_totals').append('<tr><td><input type="checkbox" class="pmttype-selector" value="'+jQuery(this).attr('data-selector-label')+'" checked> '+jQuery(this).attr('name')+'</td><td>'+jQuery(this).val()+'</td></tr>');
						});
						jQuery('.pmttype-selector').live('click', function() {
							jQuery('.pmttype-selector').each(function() {
								if (jQuery(this).is(':checked')) jQuery('.pmttype-'+jQuery(this).val()).show();
								else jQuery('.pmttype-'+jQuery(this).val()).hide();
							});
						});
					</script>
				</div>
				<?php } ?>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<!-- body_eof //-->
</body>
</html>
