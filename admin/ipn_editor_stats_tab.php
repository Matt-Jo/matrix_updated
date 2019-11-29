<?php
require_once('ipn_editor_top.php');

$start_date = isset($_REQUEST['startvalue'])&&trim($_REQUEST['startvalue'])?new DateTime(trim($_REQUEST['startvalue'])):NULL;
$end_date = isset($_REQUEST['endvalue'])&&trim($_REQUEST['endvalue'])?new DateTime(trim($_REQUEST['endvalue'])):NULL;

if (empty($start_date)) {
	//filter by start date
	$start_date = new DateTime;
	$start_date->sub(new DateInterval('P1M'));
}

if (empty($end_date)) {
	//filter by start date
	$end_date = new DateTime;
	$end_date->add(new DateInterval('P1D'));
}

$prods = [];
if (!empty($_GET['action']) && $_GET['action'] == 'update_stats') {
	$gapi = new gapi;
	$products = $ipn->get_listings();

	foreach ($products as $product) {
		$traffic = $gapi->product_traffic($product->id(), $start_date->format('Y-m-d'), $end_date->format('Y-m-d'));
		$traffic->product_id = $product->id();
		$traffic->model_number = $product->get_header('products_model');
		$prods[] = $traffic;
	}
}
?>
<div class="main">
	<form name="update_stats" action="/admin/ipn_editor.php" method="get">
		<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
		<input type="hidden" name="action" value="update_stats">
		<input type="hidden" name="selectedTab" value="<?= $_GET['selectedTab']; ?>">
		<input type="hidden" name="selectedSubTab" value="ipn-stats">
		<?php /*foreach ($_GET as $key => $val) {
			if (in_array($key, ['action', 'stock_id', 'selectedTab'])) continue; ?>
		<input type="hidden" name="<?= $key; ?>" value="<?= $val; ?>">
		<?php }*/ ?>

		<div id="datefields">
			<h5>Statistics date range</h5>
			Start Date: <input type="date" name="startvalue" value="<?= $start_date->format('Y-m-d'); ?>">
			End Date: <input type="date" name="endvalue" value="<?= $end_date->format('Y-m-d'); ?>">
			<input type="submit" value="Submit">
		</div>
	</form>
	<span style="font-size:14px; font-weight:bold;">Traffic</span><br><br>
	<?php if (empty($_GET['action'])) { ?>
	Please run the report.
	<?php }
	elseif (empty($prods)) { ?>
	There is no traffic data for this IPN during the selected dates.
	<?php }
	else { ?>
	<style>
		#traffic th, #traffic td { border-color:#000; border-style:solid; border-width:0px 1px 1px 0px; padding:4px 8px; }
		#traffic thead tr:first-child th { border-top-width:1px; }
		#traffic th:first-child, #traffic td:first-child { border-left-width:1px; }
		#traffic thead th, #traffic tfoot th, #traffic tfoot td { background-color:#fff; }
		#traffic tbody:nth-child(odd) tr:nth-child(odd) th, #traffic tbody:nth-child(odd) tr:nth-child(odd) td { background-color:#cfc; }
		#traffic tbody:nth-child(odd) tr:nth-child(even) th, #traffic tbody:nth-child(odd) tr:nth-child(even) td { background-color:#ded; }
		#traffic tbody:nth-child(even) tr:nth-child(odd) th, #traffic tbody:nth-child(even) tr:nth-child(odd) td { background-color:#ccf; }
		#traffic tbody:nth-child(even) tr:nth-child(even) th, #traffic tbody:nth-child(even) tr:nth-child(even) td { background-color:#dde; }
		#traffic tbody tr:last-child td:first-child { background-color:transparent; border-left-width:0px; }
	</style>
	<table cellpadding="0" cellspacing="0" border="0" id="traffic">
		<thead>
			<tr>
				<th class="main">Model #</th>
				<th class="main">Product ID</th>
				<th class="main">Week Starting</th>
				<th class="main">Unique Visitors</th>
				<th class="main">Entrances</th>
				<th class="main">Page Views</th>
			</tr>
		</thead>
		<?php
		$totals = ['visitors' => 0, 'entrances' => 0, 'pageviews' => 0];
		foreach ($prods as $prod) {
			$totals['visitors'] += $prod->visitors;
			$totals['entrances'] += $prod->entrances;
			$totals['pageviews'] += $prod->pageviews; ?>
		<tbody>
			<?php foreach ($prod->traffic_details as $week) { ?>
			<tr>
				<td class="main"><?= $prod->model_number; ?></td>
				<td class="main"><?= $prod->product_id; ?></td>
				<td class="main"><?= $week->date->format('m/d/Y'); ?></td>
				<td class="main"><?= $week->visitors; ?></td>
				<td class="main"><?= $week->entrances; ?></td>
				<td class="main"><?= $week->pageviews; ?></td>
			</tr>
			<?php } ?>
			<tr>
				<td colspan="3"></td>
				<th class="main"><?= $prod->visitors; ?></th>
				<th class="main"><?= $prod->entrances; ?></th>
				<th class="main"><?= $prod->pageviews; ?></th>
			</tr>
		</tbody>
		<?php } ?>
		<tfoot>
			<tr>
				<td colspan="3" class="main" style="text-align:right;">TOTALS:</td>
				<th class="main"><?= $totals['visitors']; ?></th>
				<th class="main"><?= $totals['entrances']; ?></th>
				<th class="main"><?= $totals['pageviews']; ?></th>
			</tr>
		</tfoot>
	</table>
	<?php } ?>
</div>
