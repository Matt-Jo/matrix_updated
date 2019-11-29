<?php
require_once('ipn_editor_top.php');

if (isset($_REQUEST['ipn_history_start_date'])) $start_date = new DateTime($_REQUEST['ipn_history_start_date']);
else {
	$start_date = new DateTime();
	$start_date->sub(new DateInterval('P180D'));
}

if (isset($_REQUEST['ipn_history_end_date'])) $end_date = new DateTime($_REQUEST['ipn_history_end_date']);
else $end_date = new DateTime();

$transactions = prepared_query::fetch("SELECT o.date_purchased, MONTHNAME(o.date_purchased) AS month_text, MONTH(o.date_purchased) AS `month`, YEAR(o.date_purchased) as `year`, SUM(op.products_quantity) AS totalSalesUnits, SUM(op.products_quantity * op.final_price) AS totalSalesValue, COUNT(DISTINCT op.orders_id) AS totalSalesTrans, SUM(aii_data.totalInvTrans) AS totalInvTrans, SUM(aii_data.totalInvValue) AS totalInvValue, SUM(aii_data.totalInvUnits) AS totalInvUnits, SUM(aii_data.totalInvRevenue) AS totalInvRevenue FROM orders_products op JOIN orders o ON op.orders_id = o.orders_id JOIN products p ON op.products_id = p.products_id LEFT JOIN (SELECT aii.orders_product_id, SUM(aii.invoice_item_qty) AS totalInvUnits, SUM((aii.invoice_item_qty * ABS(aii.invoice_item_price)) - aii.orders_product_cost_total) AS totalInvValue, SUM((aii.invoice_item_qty * ABS(aii.invoice_item_price))) AS totalInvRevenue, COUNT(DISTINCT aii.invoice_id) AS totalInvTrans FROM acc_invoice_items aii JOIN acc_invoices ai ON aii.invoice_id = ai.invoice_id LEFT JOIN orders o ON ai.inv_order_id = o.orders_id WHERE aii.ipn_id = :stock_id AND DATE(o.date_purchased) >= :start_date AND DATE(o.date_purchased) <= :end_date GROUP BY aii.orders_product_id) aii_data ON op.orders_products_id = aii_data.orders_product_id WHERE p.stock_id = :stock_id AND o.orders_status IN (1, 2, 3, 5, 7, 8, 10, 11, 12) AND DATE(o.date_purchased) >= :start_date AND DATE(o.date_purchased) <= :end_date GROUP BY MONTH(o.date_purchased), YEAR(o.date_purchased)", cardinality::SET, [':stock_id' => $ipn->id(), ':start_date' => $start_date->format('Y-m-d'), ':end_date' => $end_date->format('Y-m-d')]);

$history = [];
foreach ($transactions as $transaction) {
	$rawdate = new DateTime($transaction['date_purchased']);
	$key = $rawdate->format('Ym'); //$transaction['year'].$transaction['month'];
	if (empty($history[$key])) $history[$key] = $transaction;
		$history[$key]['totalSalesUnits'] = $transaction['totalSalesUnits'];
		$history[$key]['totalSalesValue'] = $transaction['totalSalesValue'];
		$history[$key]['totalSalesTrans'] = $transaction['totalSalesTrans'];
		$history[$key]['salesUnitAvg'] = !empty($transaction['totalSalesUnits'])?$transaction['totalSalesValue']/$transaction['totalSalesUnits']:0;
		$history[$key]['totalInvRevenue'] = $transaction['totalInvRevenue'];
		$history[$key]['totalInvUnits'] = $transaction['totalInvUnits'];
		$history[$key]['totalInvValue'] = $transaction['totalInvValue'];
		$history[$key]['totalInvTrans'] = $transaction['totalInvTrans'];
}

function sort_dates($a, $b) {
	return $a>$b?-1:($a<$b?1:0);
}

uksort($history, 'sort_dates'); ?>
<div class="main" style="width: 90%; border: 1px solid black; padding: 8px;">
	<span style="font-size: 14px; font-weight: bold;">Monthly Sales</span><br><br>
	<form name="update_history" action="/admin/ipn_editor.php" method="get">
		<input type="hidden" name="ipnId" value="<?= $ipn->get_header('ipn'); ?>">
		<input type="hidden" name="action" value="update_history">
		<input type="hidden" name="selectedTab" value="<?= $_GET['selectedTab']; ?>">
		<input type="hidden" name="selectedSubTab" value="ipn-history">
		Start Date: <input type="date" name="ipn_history_start_date" value="<?= $start_date->format('Y-m-d'); ?>" id="ipn_history_start_date">
		End Date: <input type="date" name="ipn_history_end_date" value="<?= $end_date->format('Y-m-d'); ?>" id="ipn_history_end_date">
		<input type="submit" value="Submit">
	</form>

	<?php if (empty($history)) echo 'There is no sales data for this IPN.';
	else { ?>
	<style>
		.history-totals th, .history-totals td { padding:4px 10px 4px 10px; text-align:center; }
		.history-totals th { border-bottom:1px solid #999; }
		.history-totals .accounting { text-align:right; width:100px; }
		#grand-totals-history-table-row td { font-weight: bold; border-top:1px solid #000; }
	</style>
	<table cellpadding="0" cellspacing="0" border="0" class="history-totals">
		<thead>
			<tr>
				<th class="main">Year</th>
				<th class="main">Month</th>
				<th class="main">Total Orders</th>
				<th class="main">Units Sold</th>
				<th class="main accounting">Avg. Price/Unit</th>
				<th class="main accounting">Total Price</th>
				<th class="main">Units Invoiced</th>
				<th class="main">Invoiced Revenue</th>
				<th class="main accounting">Total Margin</th>
				<th class="main">Margin Percentage</th>
			</tr>
		</thead>
		<tbody>
			<?php $count = 0;
			$total_sales = 0;
			$units_sold = 0;
			$total_avg_price = 0;
			$total_spent = 0;
			$total_units_invoiced = 0;
			$total_margin = 0;
			$total_inv_revenue = 0;
			foreach ($history as $yrmt => $transaction) { ?>
			<tr style="cursor:pointer;<?= $count%2==1?'background-color:#ccc;':''; ?>" onclick="updateSalesTable('<?= $transaction['month']; ?>', '<?= $transaction['year']; ?>');">
				<td class="main"><?= $transaction['year']; ?></td>
				<td class="main"><?= $transaction['month_text']; ?></td>
				<td class="main"><?= empty($transaction['totalSalesTrans'])?0:$transaction['totalSalesTrans']; ?></td>
				<td class="main"><?= empty($transaction['totalSalesUnits'])?0:$transaction['totalSalesUnits']; ?></td>
				<td class="main accounting">$<?= number_format($transaction['salesUnitAvg'], 2); ?></td>
				<td class="main accounting">$<?= number_format($transaction['totalSalesValue'], 2); ?></td>
				<td class="main"><?= $transaction['totalInvUnits']; ?></td>
				<td class="main"><?= CK\text::monetize($transaction['totalInvRevenue']); ?></td>
				<td class="main accounting">$<?= number_format($transaction['totalInvValue'], 2);?></td>
				<td class="main"><?= $transaction['totalSalesValue']==0?'<i>Incalculable</i>':number_format(($transaction['totalInvValue']/$transaction['totalSalesValue'])*100, 2).'%'; ?></td>
			</tr>
			<tr id="sales_row_<?= $transaction['month']; ?>_<?= $transaction['year']; ?>" style="display: none;">
				<td>&nbsp;</td>
				<td colspan="6">
					<table id="sales_table_<?= $transaction['month']; ?>_<?= $transaction['year']; ?>" cellspacing="0" cellpadding="3px" style="border: 1px solid #999;">
						<tr><td>&nbsp;</td></tr>
					</table>
				</td>
			</tr>
			<?php
				$count++;
				//collecting totals/averages and storing them in variables to display in the bottom row of the table
				$total_sales += $transaction['totalSalesTrans'];
				$units_sold += $transaction['totalSalesUnits'];
				$total_spent += $transaction['totalSalesValue'];
				$total_units_invoiced += $transaction['totalInvUnits'];
				$total_margin += $transaction['totalInvValue'];
				$total_inv_revenue += $transaction['totalInvRevenue'];
			}
			$total_avg_price = empty($units_sold)?$total_avg_price='<i>Incalculable</i>':$total_spent/$units_sold;
			$total_margin_percent = empty($total_spent)?'<i>Incalculable</i>':number_format(($total_margin/$total_spent) * 100, 2).'%'; ?>
		</tbody>
		<tfoot>
			<tr id="grand-totals-history-table-row">
				<td class="main" colspan="2">Totals:</td>
				<td class="main"><?= $total_sales; ?></td>
				<td class="main"><?= $units_sold; ?></td>
				<td class="main accounting">$<?= number_format($total_avg_price, 2); ?></td>
				<td class="main accounting">$<?= number_format($total_spent, 2); ?></td>
				<td class="main"><?= $total_units_invoiced; ?></td>
				<td class="main"><?= CK\text::monetize($total_inv_revenue); ?></td>
				<td class="main accounting">$<?= number_format($total_margin, 2); ?></td>
				<td class="main"><?= $total_margin_percent; ?></td>
			</tr>
		</tfoot>
	</table>
	<?php } ?>
</div>
<script type="text/javascript">
	function updateSalesTable(aMonth, aYear) {
		var rowId = 'sales_row_' + aMonth + '_' + aYear;
		var tableId = 'sales_table_' + aMonth + '_' + aYear;

		if (document.getElementById(rowId).style.display != 'none') {
			document.getElementById(rowId).style.display = 'none';
			return;
		}

		var stockId = '<?= $ipn->get_header('stock_id'); ?>';

		var callback = {
			success: function(o) {
				//var result = eval(o.responseText);
				var result = o;

				var tableHeaders = new Array('Order', 'Customer', 'Date Purchased', 'Product', 'Quantity', 'Price', 'Cost', 'Total Margin', 'Margin Percentage', 'Status', 'Exclude');
				var tableElem = document.getElementById('sales_table_' + result.month + '_' + result.year);

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
					tdElem.innerHTML = '<a href="/admin/customers_detail.php?customers_id=' + result.sales[i].customers_id + '">' + result.sales[i]['customers_name'] + '</a>';

					tdElem = rowElem.insertCell(2);
					tdElem.className = 'main';
					tdElem.innerHTML = result.sales[i]['date_purchased'];

					tdElem = rowElem.insertCell(3);
					tdElem.className = 'main';
					tdElem.innerHTML = result.sales[i]['products_name'];

					tdElem = rowElem.insertCell(4);
					tdElem.className = 'main';
					tdElem.innerHTML = result.sales[i]['products_quantity'];

					tdElem = rowElem.insertCell(5);
					tdElem.className = 'main';
					tdElem.innerHTML = '$' + result.sales[i]['final_price'];

					tdElem = rowElem.insertCell(6);
					tdElem.className = 'main';
					tdElem.innerHTML = '$' + result.sales[i]['products_cost'];

					tdElem = rowElem.insertCell(7);
					tdElem.className = 'main';
					tdElem.innerHTML = '$' + result.sales[i]['products_margin'];

					tdElem = rowElem.insertCell(8);
					tdElem.className = 'main';
					tdElem.innerHTML = result.sales[i]['products_margin_percentage'];

					tdElem = rowElem.insertCell(9);
					tdElem.className = 'main';
					tdElem.innerHTML = result.sales[i]['orders_status'];

					tdElem = rowElem.insertCell(10);
					tdElem.className = 'main';
					tdElem.innerHTML = '<input type="checkbox" class="exclude_forecast" name="exclude_op['+result.sales[i]['orders_products_id']+']"'+(result.sales[i]['exclude_forecast']==1?' checked':'')+'>';
				}

				document.getElementById('sales_row_' + result.month + '_' + result.year).style.display = '';
				jQuery('.exclude_forecast').click(function() {
					console.log('what what');
					var $check = jQuery(this);
					jQuery.ajax({
						url: '/admin/ipn_editor.php?ajax=1&action=exclude_forecast',
						type: 'GET',
						dataType: 'json',
						data: encodeURI($check.attr('name'))+'='+($check.is(':checked')?1:0),
						success: function(data) {
							if (data.status == 1) $check.css('background-color', '#cfc');
							else {
								$check.css('background-color', '#fcc');
								alert(data.message);
							}
						},
						error: function() {
							alert('Your exclude action failed to save appropriately.');
						}
					});
				});
			},

			failure: function(o) {
				if (o.responseText !== undefined) {
					alert("Get Sales info for IPN failed: " + o.responseText);
				}
				else {
					alert("Get Sales info for IPN failed: no error message available");
				}
			},

			argument: {}
		};

		var url = "ipn_getSalesInfoForIPN.php?stock_id=" + stockId + "&month=" + aMonth + "&year=" + aYear;

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
