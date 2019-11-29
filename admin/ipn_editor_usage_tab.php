<?php
require_once('ipn_editor_top.php');

$forecasting_metadata = $ipn->get_forecasting_metadata();
?>
<style>
</style>
<div id="ipn-changes">
	<style>
		.ttl { text-align:right; font-weight:bold; }
		.zero-to-thirty { background-color:#cfc; }
		.thirty-to-sixty { background-color:#ccf; }
		.zero-to-oneeighty { background-color:#fcf; }
	</style>
	<table cellpadding="0" cellspacing="0" border="0" class="ipn-usage ck-table-manager">
		<thead>
			<tr>
				<th>Date</th>
				<th>Action</th>
				<th>Qty</th>
			</tr>
		</thead>
		<tbody>
		<?php $total = $thirty_to_sixty = $zero_to_thirty = 0;
		foreach ($forecasting_metadata['usage_transactions'] as $transaction) {
			$total += $transaction['qty'];
			$class = 'zero-to-oneeighty';
			if ($transaction['date'] >= $forecasting_metadata['u30_date']) {
				$zero_to_thirty += $transaction['qty'];
				$class = 'zero-to-thirty';
			}
			elseif ($transaction['date'] >= $forecasting_metadata['u60_date']) {
				$thirty_to_sixty += $transaction['qty'];
				$class = 'thirty-to-sixty';
			} ?>
			<tr class="<?= $class; ?>">
				<td><?= $transaction['date']->format('m/d/Y'); ?></td>
				<td><?= $transaction['type']; ?></td>
				<td><?= $transaction['qty']; ?></td>
			</tr>
		<?php } ?>
		</tbody>
		<tfoot>
			<!--tr>
				<td colspan="2" class="ttl">0-30 Total:</td>
				<td><?= $zero_to_thirty; ?></td>
			</tr>
			<tr>
				<td colspan="2" class="ttl">30-60 Total:</td>
				<td><?= $thirty_to_sixty; ?></td>
			</tr-->
			<tr>
				<td colspan="2" class="ttl"><!--0-180 -->Total:</td>
				<td><?= $total; ?></td>
			</tr>
		</tfoot>
	</table>
</div>
<script>
</script>
