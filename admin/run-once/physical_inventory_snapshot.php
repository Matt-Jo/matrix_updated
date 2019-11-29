<?php
require_once(__DIR__.'/../../includes/application_top.php');

exit(); // this has been added into the inventory_ledger run once file

ini_set('memory_limit', '4096M');
ini_set('max_execution_time', 10000);

try {
	prepared_query::execute('TRUNCATE ck_daily_physical_inventory_snapshot');

	$batch_size = 500;
	$batch_idx = 0;

	$start = time();
	$counter = 0;

	while ($inventory_ledger_info = prepared_query::fetch('SELECT stock_id, qty_change, ending_qty, avg_cost_on_date, action_date FROM ck_inventory_ledgers WHERE stock_id >= :batch_start AND stock_id < :batch_end ORDER BY action_date ASC', cardinality::SET, [':batch_start' => $batch_idx*$batch_size, ':batch_end' => (++$batch_idx)*$batch_size])) {
		$ipns_on_date = [];

		$ledger_date = new DateTime($inventory_ledger_info[0]['action_date']);
		$ledger_date->setTime(0, 0, 0);
		foreach ($inventory_ledger_info as $idx => $transaction) {
			$ledger_date0 = new DateTime($transaction['action_date']);
			$ledger_date0->setTime(0, 0, 0);
			while ($ledger_date < $ledger_date0) {
				//var_dump([$idx, $ipns_on_date, $ledger_date, $ledger_date0]);
				$ledger_date->add(new DateInterval('P1D'));
				foreach ($ipns_on_date as $stock_id => $data) {
					if ($stock_id > 0) {
						prepared_query::execute('INSERT INTO ck_daily_physical_inventory_snapshot (stock_id, usage_qty, increase_qty, day_end_qty, day_end_unit_cost, record_date) VALUES (:stock_id, :usage_qty, :increase_qty, :day_end_qty, :day_end_unit_cost, :record_date)', [':stock_id' => $stock_id, ':usage_qty' => $data['usage_qty'], ':increase_qty' => $data['increase_qty'], ':day_end_qty' => $data['day_end_qty'], ':day_end_unit_cost' => $data['day_end_unit_cost'], ':record_date' => $data['record_date']]);

						$ipns_on_date[$stock_id]['record_date'] = $ledger_date->format('Y-m-d');
						$ipns_on_date[$stock_id]['usage_qty'] = 0;
						$ipns_on_date[$stock_id]['increase_qty'] = 0;
					}
				}
			}

			if (empty($ipns_on_date[$transaction['stock_id']])) {
				$ipns_on_date[$transaction['stock_id']] = [
					'record_date' => $ledger_date->format('Y-m-d'),
					'usage_qty' => 0,
					'increase_qty' => 0,
					'day_end_qty' => 0,
					'day_end_unit_cost' => 0
				];
			}
			if ($transaction['qty_change'] < 0) $ipns_on_date[$transaction['stock_id']]['usage_qty'] += abs($transaction['qty_change']);
			if ($transaction['qty_change'] > 0) $ipns_on_date[$transaction['stock_id']]['increase_qty'] += $transaction['qty_change'];
			$ipns_on_date[$transaction['stock_id']]['day_end_qty'] = $transaction['ending_qty'];
			$ipns_on_date[$transaction['stock_id']]['day_end_unit_cost'] = $transaction['avg_cost_on_date'];
			//process pulse to help identify how far along and to ensure actual progress
			if ($counter % 10000 == 0) {
				echo "checkpoint (".$counter.")\n Time elapsed: ".(time() - $start)." seconds\n";
				flush();
			}
			$counter++;
		}

		$today = new DateTime();
		$today->setTime(0, 0, 0);
		while ($ledger_date < $today) {
			$ledger_date->add(new DateInterval('P1D'));
			foreach ($ipns_on_date as $stock_id => $data) {
				if ($stock_id > 0) {
					prepared_query::execute('INSERT INTO ck_daily_physical_inventory_snapshot (stock_id, usage_qty, increase_qty, day_end_qty, day_end_unit_cost, record_date) VALUES (:stock_id, :usage_qty, :increase_qty, :day_end_qty, :day_end_unit_cost, :record_date)', [':stock_id' => $stock_id, ':usage_qty' => $data['usage_qty'], ':increase_qty' => $data['increase_qty'], ':day_end_qty' => $data['day_end_qty'], ':day_end_unit_cost' => $data['day_end_unit_cost'], ':record_date' => $data['record_date']]);

					$ipns_on_date[$stock_id]['record_date'] = $ledger_date->format('Y-m-d');
					$ipns_on_date[$stock_id]['usage_qty'] = 0;
					$ipns_on_date[$stock_id]['increase_qty'] = 0;
				}
			}
		}
	}

	//query all data from the inventory ledger table -- we are doing this right before we need to use it so not to use up all of our memory

	//Once complete output total time elapsed
	echo "\n<span style='color:red'>Completed in: ".(time() - $start)." seconds</span>\n";
}
catch (Exception $e) {
	echo $e->getMessage();
} ?>