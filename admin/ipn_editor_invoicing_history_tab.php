<?php
require_once('ipn_editor_top.php');

if (!empty($_REQUEST['ipn_invoicing_history_start_date'])) $start_date = new DateTime($_REQUEST['ipn_invoicing_history_start_date']);
else {
	$start_date = new DateTime();
	$start_date->sub(new DateInterval('P180D'));
}

if (!empty($_REQUEST['ipn_invoicing_history_end_date'])) $end_date = new DateTime($_REQUEST['ipn_invoicing_history_end_date']);
else $end_date = new DateTime();

//now we will perform the query
$invoices = prepared_query::fetch("SELECT 'invoice' as transaction_type, i.inv_date, MONTHNAME(i.inv_date) as month_text, MONTH(i.inv_date) as `month`, YEAR(i.inv_date) as `year`, SUM(ii.invoice_item_qty) as totalInvoicedUnits, SUM(ii.invoice_item_qty * ii.invoice_item_price) as totalInvoicedCost, COUNT(DISTINCT i.invoice_id) as totalInvoicedTrans FROM acc_invoices i LEFT JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id WHERE ii.ipn_id = :stock_id AND DATE(i.inv_date) >= :start_date AND DATE(i.inv_date) <= :end_date GROUP BY YEAR(i.inv_date), MONTH(i.inv_date) ORDER BY i.inv_date DESC", cardinality::SET, [':stock_id' => $ipn->id(), ':start_date' => $start_date->format('Y-m-d'), ':end_date' => $end_date->format('Y-m-d')]); ?>

<input type="hidden" id="ipn-invoice-stock-id" value="<?= $ipn->id(); ?>">

<div class="main" style="width: 90%; border: 1px solid black; padding: 8px;">
	<span style="font-size: 14px; font-weight: bold;">Monthly Sales</span><br><br>

	<form name="update_history" action="/admin/ipn_editor.php" method="get">
		<input type="hidden" name="ipnId" value="<?= $ipn->get_header('ipn'); ?>">
		<input type="hidden" name="action" value="update_history">
		<input type="hidden" name="selectedTab" value="<?= $_GET['selectedTab']; ?>">
		<input type="hidden" name="selectedSubTab" value="ipn-invoicing_history">

		Start Date: <input type="date" name="ipn_invoicing_history_start_date" value="<?= $start_date->format('Y-m-d'); ?>" id="ipn_invoicing_history_start_date">
		End Date: <input type="date" name="ipn_invoicing_history_end_date" value="<?= $end_date->format('Y-m-d'); ?>" id="ipn_invoicing_history_end_date">

		<input type="submit">
	</form>

	<?php if (empty($invoices)) echo 'There is no sales data for this IPN.';
	else { ?>
	<table cellpadding="4px" cellspacing="0" border="0" width="100%">
		<thead>
			<tr>
				<td class="main"><b>Year</b></td>
				<td class="main"><b>Month</b></td>
				<td class="main"><b>Total Orders</b></td>
				<td class="main"><b>Units Sold</b></td>
				<td class="main"><b>Average Price Per Unit</b></td>
				<td class="main"><b>Total Price</b></td>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($invoices as $idx => $invoice) { ?>
			<tr class="invoice_month" data-invoice-month="<?= $invoice['month']; ?>" data-invoice-year="<?= $invoice['year']; ?>" style="cursor:pointer;<?= $idx%2==1?'background-color:#ccc;':''; ?>">
				<td class="main"><?= $invoice['year']; ?></td>
				<td class="main"><?= $invoice['month_text']; ?></td>
				<td class="main"><?= $invoice['totalInvoicedTrans']; ?></td>
				<td class="main"><?= $invoice['totalInvoicedUnits']; ?></td>
				<td class="main"><?= $invoice['totalInvoicedUnits']>0?CK\text::monetize($invoice['totalInvoicedCost']/$invoice['totalInvoicedUnits']):'N/A'; ?></td>
				<td class="main"><?= CK\text::monetize($invoice['totalInvoicedCost']); ?></td>
			</tr>
			<tr id="invoices_row_<?= $invoice['month']; ?>_<?= $invoice['year']; ?>" style="display:none;">
				<td>&nbsp;</td>
				<td colspan='6'>
					<table id="invoices_table_<?= $invoice['month']; ?>_<?= $invoice['year']; ?>" cellspacing="0" cellpadding="3px" style="border: 1px solid #999999;">
						<tr><td>&nbsp;</td></tr>
					</table>
				</td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php } ?>
</div>
<script>
	jQuery('.invoice_month').click(function(e) {
		updateInvoicesTable(jQuery(this).attr('data-invoice-month'), jQuery(this).attr('data-invoice-year'));
	});

	function updateInvoicesTable(aMonth, aYear) {

		var rowId = 'invoices_row_' + aMonth + '_' + aYear;
		var tableId = 'invoices_table_' + aMonth + '_' + aYear;

		if (document.getElementById(rowId).style.display != 'none') {
			document.getElementById(rowId).style.display = 'none';
			return;
		}

		var stockId = jQuery('#ipn-invoice-stock-id').val();

		var callback = {

			success: function(o) {
				//var result = eval(o.responseText);
				var result = o;

				var tableHeaders = new Array('Order', 'Invoice ID', 'Customer', 'Invoice Date', 'Quantity', 'Price');
				var tableElem = document.getElementById('invoices_table_' + result.month + '_' + result.year);

				var tableSize = tableElem.rows.length;
				for (var i = tableSize - 1; i >= 0; i--) {
					tableElem.deleteRow(i);
				}

				var headerRowElem = tableElem.insertRow(tableElem.rows.length);

				for (var i = 0; i < tableHeaders.length; i++) {
					var headerTdElem = headerRowElem.insertCell(i);
					headerTdElem.className = 'main';
					headerTdElem.innerHTML='<b>' + tableHeaders[i] + '</b>';
				}

				for (var i = 0; i < result.sales.length; i++) {

					var rowElem = tableElem.insertRow(tableElem.rows.length);
					if (i % 2 == 1) {
						rowElem.style.backgroundColor = "#cccccc";
					}

					var tdElem = rowElem.insertCell(0);
					tdElem.className = 'main';
					tdElem.innerHTML = '<a href="orders_new.php?oID=' + result.sales[i].orders_id + '&action=edit">' + result.sales[i]['orders_id'] + '</a>';

					tdElem = rowElem.insertCell(1);
					tdElem.className = 'main';
					tdElem.innerHTML = result.sales[i]['date_purchased'];

					tdElem = rowElem.insertCell(2);
					tdElem.className = 'main';
					tdElem.innerHTML = '<a href="/admin/customers_detail.php?customers_id=' + result.sales[i].customers_id + '">' + result.sales[i]['customers_name'] + '</a>';

					tdElem = rowElem.insertCell(3);
					tdElem.className = 'main';
					tdElem.innerHTML = result.sales[i]['inv_date'];

					tdElem = rowElem.insertCell(4);
					tdElem.className = 'main';
					tdElem.innerHTML = result.sales[i]['products_quantity'];

					tdElem = rowElem.insertCell(5);
					tdElem.className = 'main';
					tdElem.innerHTML = '$' + result.sales[i]['final_price'];


				}

				document.getElementById('invoices_row_' + result.month + '_' + result.year).style.display = '';
			},

			failure: function(o) {
				if (o.responseText !== undefined) {
					alert("Get Invoice info for IPN failed: " + o.responseText);
				}
				else {
					alert("Get Invoice info for IPN failed: no error message available");
				}
			},

			argument: {}
		};

		var url = "ipn_getInvoicesInfoForIPN.php?stock_id=" + stockId + "&month=" + aMonth + "&year=" + aYear;

		jQuery.ajax({
			url: url,
			type: 'get',
			dataType: 'json',
			success: function(data) {
				callback.success(data);
			},
			error: function(a, b, c) {
				console.log(a, b, c);
			}
		});
	}

</script>
