<?php 
require_once(__DIR__.'/../includes/application_top.php');

$backorder = NULL;
$warehouse = NULL;
$sourcing = NULL;
$workorder = NULL;
$painthold = NULL;
$sourcingexpress = NULL;
$accounting = NULL;
$dropship = NULL;
$uncat = NULL;

$orders_status = ['uncat' => 1, 'sourcing' => 2, 'backorder' => 5, 'warehouse' => 7, 'workorder' => 11, 'painthold' => 15, 'sourcingexpress' => 17, 'accounting' => 20, 'dropship' => 21];

try {

	$total_orders = prepared_query::fetch('SELECT o.orders_id, o.orders_sub_status AS orders_sub_status_id, o.orders_status AS orders_status_id, o.promised_ship_date, SUM(op.final_price * op.products_quantity) AS order_revenue FROM orders o LEFT JOIN orders_sub_status oss ON oss.orders_sub_status_id = o.orders_sub_status LEFT JOIN orders_status os ON os.orders_status_id = o.orders_status LEFT JOIN orders_products op ON op.orders_id = o.orders_id LEFT JOIN products_stock_control psc ON psc.stock_id = op.products_id WHERE IFNULL(oss.orders_sub_status_id, -1) IN (1, 2, 15, 17, 20, 21, 22, -1) AND os.orders_status_id IN (5, 7, 11) GROUP BY o.orders_id', cardinality::SET);

	foreach ($total_orders as $to) {
		$orderObj = new ck_sales_order($to['orders_id']);
		$emd = $orderObj->get_estimated_margin_dollars('total');
		if (!is_numeric($emd)) $emd = 0;

		//check for backorder orders
		if ($to['orders_sub_status_id'] == $orders_status['backorder'] || $to['orders_status_id'] == $orders_status['backorder']) {
			if (empty($table_records['backorder'])) $table_records['backorder'] = ['num_orders' => 0, 'rev' => 0, 'margin' => 0];
			$workorder_orders = $table_records['backorder']['num_orders']++;
			$table_records['backorder']['display'] = 'Backorder';
			$table_records['backorder']['rev'] += $to['order_revenue'];
			
			$table_records['backorder']['margin'] += $emd;

			$backorder .= '<a href="'.FQDN.'/admin/orders_new.php?status=2&oID='.$to['orders_id'].'&action=edit">'.$to['orders_id'].'</a>, ';
		}

		//check for warehouse orders
		if ($to['orders_sub_status_id'] == $orders_status['warehouse'] || $to['orders_status_id'] == $orders_status['warehouse']) {
			if (empty($table_records['warehouse'])) $table_records['warehouse'] = ['num_orders' => 0, 'rev' => 0, 'margin' => 0];
			$table_records['warehouse']['display'] = 'Warehouse';
			$table_records['warehouse']['num_orders'] ++;
			$table_records['warehouse']['rev'] += $to['order_revenue'];

			$table_records['warehouse']['margin'] += $emd;

			$warehouse .= '<a href="'.FQDN.'/admin/orders_new.php?status=2&oID='.$to['orders_id'].'&action=edit">'.$to['orders_id'].'</a>, ';
		}

		//check for sourcing orders
		if ($to['orders_sub_status_id'] == $orders_status['sourcing'] || $to['orders_status_id'] == $orders_status['sourcing']) {
			if (empty($table_records['sourcing'])) $table_records['sourcing'] = ['num_orders' => 0, 'rev' => 0, 'margin' => 0];
			$table_records['sourcing']['display'] = 'Sourcing';
			$table_records['sourcing']['num_orders'] ++;
			$table_records['sourcing']['rev'] += $to['order_revenue'];
		
			$table_records['sourcing']['margin'] += $emd;

			$sourcing .= '<a href="'.FQDN.'/admin/orders_new.php?status=2&oID='.$to['orders_id'].'&action=edit">'.$to['orders_id'].'</a>, ';
		}

		//check for workorder orders
		if ($to['orders_sub_status_id'] == $orders_status['workorder'] || $to['orders_status_id'] == $orders_status['workorder']) {
			if (empty($table_records['workorder'])) $table_records['workorder'] = ['num_orders' => 0, 'rev' => 0, 'margin' => 0];
			$table_records['workorder']['display'] = 'Workorder';
			$table_records['workorder']['num_orders'] ++;
			$table_records['workorder']['rev'] += $to['order_revenue'];
		
			$table_records['workorder']['margin'] += $emd;

			$workorder .= '<a href="'.FQDN.'/admin/orders_new.php?status=2&oID='.$to['orders_id'].'&action=edit">'.$to['orders_id'].'</a>, ';
		}

		//check for painthold orders
		if ($to['orders_sub_status_id'] == $orders_status['painthold'] || $to['orders_status_id'] == $orders_status['painthold']) {
			if (empty($table_records['painthold'])) $table_records['painthold'] = ['num_orders' => 0, 'rev' => 0, 'margin' => 0];
			$table_records['painthold']['display'] = 'Paint Hold';
			$table_records['painthold']['num_orders'] ++;
			$table_records['painthold']['rev'] += $to['order_revenue'];
		
			$table_records['painthold']['margin'] += $emd;

			$painthold .= '<a href="'.FQDN.'/admin/orders_new.php?status=2&oID='.$to['orders_id'].'&action=edit">'.$to['orders_id'].'</a>, ';
		}

		//check for sourcingexpress orders
		if ($to['orders_sub_status_id'] == $orders_status['sourcingexpress'] || $to['orders_status_id'] == $orders_status['sourcingexpress']) {
			if (empty($table_records['sourcingexpress'])) $table_records['sourcingexpress'] = ['num_orders' => 0, 'rev' => 0, 'margin' => 0];
			$table_records['sourcingexpress']['display'] = 'Sourcing Express';
			$table_records['sourcingexpress']['num_orders'] ++;
			$table_records['sourcingexpress']['rev'] += $to['order_revenue'];
		
			$table_records['sourcingexpress']['margin'] += $emd;

			$sourcingexpress .= '<a href="'.FQDN.'/admin/orders_new.php?status=2&oID='.$to['orders_id'].'&action=edit" target="_blank">'.$to['orders_id'].'</a>, ';
		}

		//check for accounting orders
		if ($to['orders_sub_status_id'] == $orders_status['accounting'] || $to['orders_status_id'] == $orders_status['accounting']) {
			if (empty($table_records['accounting'])) $table_records['accounting'] = ['num_orders' => 0, 'rev' => 0, 'margin' => 0];
			$table_records['accounting']['display'] = 'Accounting';
			$table_records['accounting']['num_orders'] ++;
			$table_records['accounting']['rev'] += $to['order_revenue'];
		
			$table_records['accounting']['margin'] += $emd;

			$accounting .= '<a href="'.FQDN.'/admin/orders_new.php?status=2&oID='.$to['orders_id'].'&action=edit" target="_blank">'.$to['orders_id'].'</a>, ';
		}

		//check for dropship orders
		if ($to['orders_sub_status_id'] == $orders_status['dropship'] || $to['orders_status_id'] == $orders_status['dropship']) {
			if (empty($table_records['dropship'])) $table_records['dropship'] = ['num_orders' => 0, 'rev' => 0, 'margin' => 0];
			$table_records['dropship']['display'] = 'Dropship';
			$table_records['dropship']['num_orders'] ++;
			$table_records['dropship']['rev'] += $to['order_revenue'];
		
			$table_records['dropship']['margin'] += $emd;

			$dropship .= '<a href="'.FQDN.'/admin/orders_new.php?status=2&oID='.$to['orders_id'].'&action=edit" target="_blank">'.$to['orders_id'].'</a>, ';
		}

		//check for uncat orders
		if ($to['orders_sub_status_id'] == $orders_status['uncat'] || $to['orders_status_id'] == $orders_status['uncat']) {
			if (empty($table_records['uncat'])) $table_records['uncat'] = ['num_orders' => 0, 'rev' => 0, 'margin' => 0];
			$table_records['uncat']['display'] = 'Uncat';
			$table_records['uncat']['num_orders'] ++;
			$table_records['uncat']['rev'] += $to['order_revenue'];
		
			$table_records['uncat']['margin'] += $emd;

			$uncat .= '<a href="'.FQDN.'/admin/orders_new.php?status=2&oID='.$to['orders_id'].'&action=edit" target="_blank">'.$to['orders_id'].'</a>, ';
		}
	}
	//initial body build for email
	$body = '<table style="border-collapse:collapse; width:800px;" align="center"><thead><tr><th colspan="4" style="border:1px solid #000; text-align:center;">Order Summary ('.date('l\, F jS Y').')</th></tr><tr><th style="border:1px solid #000; text-align:center;">Status</th><th style="border:1px solid #000; text-align:center;">Total Orders</th><th style="border:1px solid #000; text-align:center;">Total Rev</th><th style="border:1px solid #000; text-align:center;">Total Margin</th></tr></thead><tbody>';
	//loop through array that we just built
	foreach ($table_records as $status => $tr) {
		//build table for email update
		$body .= '<tr><td style="border:1px solid #000; text-align:center;">'.$tr['display'].'</td><td style="border:1px solid #000; text-align:center;">'.$tr['num_orders'].'</td><td style="border:1px solid #000; text-align:center;"> $ '.number_format($tr['rev'], 2).'</td><td style="border:1px solid #000; text-align:center;">'.(!empty($tr['margin'])?'$ '.number_format($tr['margin'], 2):'<i>Incalculable</i>').'</td></tr>';
		//insert status data into table
		prepared_query::execute('INSERT INTO ck_daily_order_snapshot (orders_status, total_revenue, total_margin, total_orders, date) VALUES (:orders_status, :total_revenue, :projected_total_margin, :total_orders, NOW())', [':orders_status' => $tr['display'], ':total_revenue' => $tr['rev'], ':projected_total_margin' => (!empty($tr['margin'])?'$ '.number_format($tr['margin'], 2):'<i>Incalculable</i>'), ':total_orders' => $tr['num_orders']]);
	}
		
	$body .= '</tbody></table>';
	$body .= '<br><br><br><br>';
	//building a list of each status with all the orders that were left in that status
	$body .= '<b>Warehouse:</b> '.(empty($warehouse)?'None':trim($warehouse, ', ')).'<br><br><hr><br><br>';
	$body .= '<b>Sourcing:</b> '.(empty($sourcing)?'None':trim($sourcing, ', ')).'<br><br><hr><br><br>';
	$body .= '<b>Sourcing Express:</b> '.(empty($sourcingexpress)?'None':trim($sourcingexpress, ', ')).'<br><br><hr><br><br>';
	$body .= '<b>Workorder:</b> '.(empty($workorder)?'None':trim($workorder, ', ')).'<br><br><hr><br><br>';
	$body .= '<b>Paint Hold:</b> '.(empty($painthold)?'None':trim($painthold, ', ')).'<br><br><hr><br><br>';
	$body .= '<b>Drop Ship:</b> '.(empty($dropship)?'None':trim($dropship, ', ')).'<br><br><hr><br><br>';
	$body .= '<b>Backorder:</b> '.(empty($backorder)?'None':trim($backorder, ', ')).'<br><br><hr><br><br>';
	$body .= '<b>Uncat:</b> '.(empty($uncat)?'None':trim($uncat, ', '));
	
	//send email
	$mailer = service_locator::get_mail_service();
	$mail = $mailer->create_mail();
	$mail->set_body(null,$body);
	$mail->set_body($body);
	$mail->set_from('webmaster@cablesandkits.com', 'CK Webmaster');
	$mail->add_to('gary.epp@cablesandkits.com', 'Gary Epp');
	$mail->set_subject('Order Status - Snapshot');
	$mailer->send($mail);
}
catch (Exception $e) {
	echo $e->getMessage();
} ?>