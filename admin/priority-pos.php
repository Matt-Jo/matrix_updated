<?php
require('includes/application_top.php');
$action = !empty($_REQUEST['action'])?$_REQUEST['action']:NULL;

$statuses_raw = prepared_query::fetch('SELECT * FROM purchase_order_status ORDER BY id ASC', cardinality::SET);
$statuses = [];
foreach ($statuses_raw as $status) {
	$statuses[$status['id']] = $status['text'];
}

switch ($action) {
	case 'tracking-lookup':
		$results = ['results' => []];
		$tracking = trim($_GET['term']);
		if (empty($tracking)) exit();
		if ($tracking_numbers = prepared_query::fetch('SELECT po.id as purchase_order_id, pot.id as tracking_id, pot.tracking_number, pot.STATUS as arrived, pot.bin_number, pot.arrival_time, pot.shipping_cost, po.purchase_order_number, po.status, v.vendors_company_name as vendor, po.shipping_method as shipping_method_id, SUM(potoa.quantity) as allocated_qty FROM purchase_order_tracking pot JOIN purchase_orders po ON pot.po_id = po.id JOIN purchase_order_products pop ON po.id = pop.purchase_order_id LEFT JOIN purchase_order_to_order_allocations potoa ON pop.id = potoa.purchase_order_product_id LEFT JOIN vendors v ON po.vendor = v.vendors_id WHERE pot.tracking_number LIKE :tracking_number GROUP BY po.id, pot.id, pot.tracking_number, pot.STATUS, po.purchase_order_number, po.status, v.vendors_company_name, po.shipping_method ORDER BY pot.tracking_number ASC', cardinality::SET, [':tracking_number' => $tracking.'%'])) {
			foreach ($tracking_numbers as $tracking_number) {
				$tracking_number['result_id'] = $tracking_number['tracking_id'];
				$tracking_number['field_value'] = $tracking_number['tracking_number'];
				$tracking_number['result_label'] = $tracking_number['tracking_number'].' ['.$tracking_number['purchase_order_number'].' - '.($tracking_number['arrived']==1?'ARRIVED':'AWAITING').']';
				$results['results'][] = $tracking_number;
			}
		}

		echo json_encode($results);
		exit();
		break;
	case 'po-lookup':
		$results = ['results' => []];
		$ponum = trim($_GET['term']);
		if (empty($ponum)) exit();
		if ($po_numbers = prepared_query::fetch('SELECT po.id as purchase_order_id, NULL as tracking_id, NULL as tracking_number, NULL as arrived, NULL as bin_number, NULL as arrival_time, po.purchase_order_number, po.status, v.vendors_company_name as vendor, po.shipping_method as shipping_method_id, SUM(potoa.quantity) as allocated_qty FROM purchase_orders po JOIN purchase_order_products pop ON po.id = pop.purchase_order_id LEFT JOIN purchase_order_to_order_allocations potoa ON pop.id = potoa.purchase_order_product_id LEFT JOIN vendors v ON po.vendor = v.vendors_id WHERE po.purchase_order_number LIKE :po_number GROUP BY po.id, po.purchase_order_number, po.status, v.vendors_company_name, po.shipping_method ORDER BY po.purchase_order_number ASC', cardinality::SET, [':po_number' => $ponum.'%'])) {
			foreach ($po_numbers as $po_number) {
				$po_number['result_id'] = $po_number['purchase_order_id'];
				$po_number['field_value'] = $po_number['purchase_order_number'];
				$po_number['result_label'] = $po_number['purchase_order_number'].' ['.$po_number['vendor'].'] ['.$statuses[$po_number['status']].']';
				$results['results'][] = $po_number;
			}
		}

		echo json_encode($results);
		exit();
		break;
	case 'vendor-lookup':
		$results = ['results' => []];
		$vendor = trim($_GET['term']);
		$part_number = !empty(trim($_GET['part_number']))?trim($_GET['part_number']).'%':NULL;
		if (empty($vendor)) exit();
		if ($po_numbers = prepared_query::fetch('SELECT po.id as purchase_order_id, NULL as tracking_id, NULL as tracking_number, NULL as arrived, NULL as bin_number, NULL as arrival_time, po.purchase_order_number, po.status, v.vendors_company_name as vendor, po.shipping_method as shipping_method_id, SUM(potoa.quantity) as allocated_qty, psc.stock_name as ipn, pop.quantity as qty FROM purchase_orders po JOIN purchase_order_products pop ON po.id = pop.purchase_order_id LEFT JOIN purchase_order_to_order_allocations potoa ON pop.id = potoa.purchase_order_product_id LEFT JOIN vendors v ON po.vendor = v.vendors_id JOIN products_stock_control psc ON pop.ipn_id = psc.stock_id WHERE po.status IN (1, 2) AND (v.vendors_company_name LIKE :vendor OR v.vendors_email_address LIKE :vendor) AND (:stock_name IS NULL OR psc.stock_name LIKE :stock_name) GROUP BY po.id, po.purchase_order_number, po.status, v.vendors_company_name, po.shipping_method, psc.stock_name, pop.quantity ORDER BY po.purchase_order_number ASC', cardinality::SET, [':vendor' => $vendor.'%', ':stock_name' => $part_number])) {
			foreach ($po_numbers as $po_number) {
				$po_number['result_id'] = $po_number['purchase_order_id'].'-'.$po_number['ipn'];
				$po_number['field_value'] = $po_number['vendor'];
				$po_number['result_label'] = $po_number['purchase_order_number'].' ['.$po_number['vendor'].'] ['.$statuses[$po_number['status']].']';
				$results['results'][] = $po_number;
			}
		}

		echo json_encode($results);
		exit();
		break;
	case 'ipn-lookup':
		$results = ['results' => []];
		$part_number = trim($_GET['term']);
		$vendor = !empty(trim($_GET['vendor']))?trim($_GET['vendor']).'%':NULL;
		if (empty($part_number)) exit();
		if ($po_numbers = prepared_query::fetch('SELECT po.id as purchase_order_id, NULL as tracking_id, NULL as tracking_number, NULL as arrived, NULL as bin_number, NULL as arrival_time, po.purchase_order_number, po.status, v.vendors_company_name as vendor, po.shipping_method as shipping_method_id, SUM(potoa.quantity) as allocated_qty, psc.stock_name as ipn, pop.quantity as qty FROM purchase_orders po JOIN purchase_order_products pop ON po.id = pop.purchase_order_id LEFT JOIN purchase_order_to_order_allocations potoa ON pop.id = potoa.purchase_order_product_id LEFT JOIN vendors v ON po.vendor = v.vendors_id JOIN products_stock_control psc ON pop.ipn_id = psc.stock_id WHERE po.status IN (1, 2) AND psc.stock_name LIKE :stock_name AND (:vendor IS NULL OR v.vendors_company_name LIKE :vendor OR v.vendors_email_address LIKE :vendor) GROUP BY po.id, po.purchase_order_number, po.status, v.vendors_company_name, po.shipping_method, psc.stock_name, pop.quantity ORDER BY po.purchase_order_number ASC', cardinality::SET, [':stock_name' => $part_number.'%', ':vendor' => $vendor])) {
			foreach ($po_numbers as $po_number) {
				$po_number['result_id'] = $po_number['purchase_order_id'].'-'.$po_number['ipn'];
				$po_number['field_value'] = $po_number['ipn'];
				$po_number['result_label'] = $po_number['purchase_order_number'].' ['.$po_number['vendor'].'] ['.$statuses[$po_number['status']].']';
				$results['results'][] = $po_number;
			}
		}

		echo json_encode($results);
		exit();
		break;
	case 'associate-tracking':
		$bin_number = !empty($_REQUEST['bin_number'])?trim($_REQUEST['bin_number']):NULL;
		$bin_message = !empty($bin_number)?' - Set Bin # to '.$bin_number:'';
		$tracking_id = prepared_query::insert('INSERT INTO purchase_order_tracking (po_id, tracking_number, tracking_method, status, bin_number, arrival_time) SELECT id, :tracking_number, shipping_method, 1, :bin_number, NOW() FROM purchase_orders WHERE id = :purchase_order_id ON DUPLICATE KEY UPDATE bin_number=:bin_number', [':tracking_number' => trim($_REQUEST['tracking_number']), ':purchase_order_id' => $_REQUEST['purchase_order_id'], ':bin_number' => $bin_number]);
		echo json_encode(['message' => '<br>Successfully Added Tracking # To PO And Marked Arrived'.$bin_message, 'tracking_id' => $tracking_id]);
		exit();
		break;
	case 'mark-arrived':
		$bin_number = !empty($_REQUEST['bin_number'])?trim($_REQUEST['bin_number']):NULL;
		$bin_message = !empty($bin_number)?' - Set Bin # to '.$bin_number:'';
		prepared_query::execute('UPDATE purchase_order_tracking SET status = 1, bin_number = :bin_number, arrival_time = NOW() WHERE id = :tracking_id AND po_id = :purchase_order_id', [':tracking_id' => $_REQUEST['tracking_id'], ':purchase_order_id' => $_REQUEST['purchase_order_id'], ':bin_number' => $bin_number]);
		echo json_encode(['message' => '<br>Successfully Marked Arrived'.$bin_message]);
		exit();
		break;
	case 'just-update':
		$bin_number = !empty($_REQUEST['bin_number'])?trim($_REQUEST['bin_number']):NULL;
		$bin_message = !empty($bin_number)?' - Set Bin # to '.$bin_number:'';
		prepared_query::execute('UPDATE purchase_order_tracking SET bin_number = :bin_number WHERE id = :tracking_id', [':tracking_id' => $_REQUEST['tracking_id'], ':bin_number' => $bin_number]);
		echo json_encode(['message' => '<br>Successfully Updated Tracking #'.$bin_message]);
		exit();
		break;
	case 'add-package-info':
		$package_detail[] = [
			'weight' => $_REQUEST['package_weight'],
			'dim' => [
				'length' => $_REQUEST['package_length'],
				'width' => $_REQUEST['package_width'],
				'height' => $_REQUEST['package_height']
			]
		];
		$shipment_detail = [
			'shipping_method' => $_REQUEST['shipping_method'],
			'origin_zip_code' => $_REQUEST['origin_zip_code'],
			'origin_state' => $_REQUEST['origin_state'],
			'tracking_id' => $_REQUEST['tracking_id']
		];

		if (ck_purchase_order::update_tracking_number_cost($package_detail, $shipment_detail)) echo json_encode(['message' => 'Success', 'success' => true]);
		else echo json_encode(['message' => 'Failed to update tracking number cost', 'success' => false]);
		exit();
		break;
	default:
		break;
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
		<script src="//cdnjs.cloudflare.com/ajax/libs/mustache.js/2.1.3/mustache.min.js"></script>
		<script src="/images/static/js/ck-styleset.js"></script>
		<script src="/images/static/js/ck-autocomplete.js"></script>
		<table border="0" width="100%" cellspacing="2" cellpadding="2">
			<tr>
				<td width="<?php echo BOX_WIDTH; ?>" valign="top">
					<table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
						<!-- left_navigation //-->
						<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
						<!-- left_navigation_eof //-->
					</table>
				</td>
				<!-- body_text //-->
				<td width="100%" valign="top">
					<table border="0" width="800" cellspacing="0" cellpadding="2">
						<tr>
							<td>
								<?php if (!empty($errors)) {
									if ($errors) {
										echo "<br>ERRORS:<br>";
										echo implode("<br>", $errors);
									}
								} ?>
								<style>
									.po-fields { width:270px; margin-bottom:30px; }
									.po-priority { background-color:#f00; color:#fff; font-weight:bold; font-size:1.1em; }
									.po-normal { background-color:#cfc; }
									.po-priority a { color:#fff; font-weight:bold; font-size:1.1em; }
									.modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4); }
									.modal-content { background-color: #fefefe; margin: 15% auto; padding:5px; border: 1px solid #888; width: 30%; }
									.close { color: #aaa; float: right; font-size: 28px; font-weight: bold; display:block; }
									.close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
									.modal-content header { width:100%; display:block; overflow:none; height:35px; }
									.modal-content main { margin:20px; }
									.package-info { width:100%; margin:5px 0; height:30px;  }
									.missing-data { background-color:red; }
								</style>
								<div class="po-fields">
									<strong>Tracking #:</strong> <input type="text" id="tracking_number" name="tracking_number">
									<br>
									<br>
									<strong>PO #:</strong> <input type="text" id="po_number" name="po_number">
									<br>
									<br>
									<strong>Vendor:</strong> <input type="text" id="vendor" name="vendor">
									<br>
									<br>
									<strong>Part #:</strong> <input type="text" id="part_number" name="part_number">
								</div>
								<div id="po_result">
								</div>
								<div id="dim-weight-modal" class="modal">
									<div class="modal-content">
										<header>
											<span class="close">&times;</span>
										</header>
										<main>
											<input type="text" id="package-weight" class="package-info" name="package_weight" value="" placeholder="Package Weight">
											<input type="text" id="package-height" class="package-info" name="package_height" value="" placeholder="Package Height">
											<input type="text" id="package-length" class="package-info" name="package_length" value="" placeholder="Package Length">
											<input type="text" id="package-width" class="package-info" name="package_width" value="" placeholder="Package Width">
											<input type="text" id="origin-zip-code" class="package-info" name="origin_zip_code" value="" placeholder="Vendor Zip Code">
											<?php $states = prepared_query::fetch('SELECT zone_code, zone_name FROM zones', cardinality::SET); ?>
											<select id="origin-state" class="package-info" name="origin_state">
												<option></option>
												<?php foreach($states as $state) { ?>
												<option value="<?= $state['zone_code']; ?>"><?= $state['zone_name']; ?></option>
												<?php } ?>
											</select>
											<select id="shipping-method" class="package-info" name="shipping_method">
												<option></option>
												<option value="03">UPS Ground</option>
												<option value="12">UPS Three-Day Select</option>
												<option value="59">UPS Second Day Air AM</option>
												<option value="02">UPS Second Day Air Saver</option>
												<option value="13">UPS Next Day Air Saver</option>
												<option value="14">UPS Next Day Air Early AM</option>
												<option value="01">UPS Next Day Air</option>
											</select>
											<input type="button" id="submit-details" class="package-info" value="Enter Details">
										</main>
									</div>
								</div>
								<script>
									var tracking_number_ac = new ck.autocomplete('tracking_number', '/admin/priority-pos.php', {
										preprocess: function() {
											jQuery('#po_result').html('');
											jQuery('#po_number').val('');
											jQuery('#vendor').val('');
										},
										autocomplete_action: 'tracking-lookup',
										autocomplete_field_name: 'term',
										select_result: po_result
									});

									var po_number_ac = new ck.autocomplete('po_number', '/admin/priority-pos.php', {
										preprocess: function() {
											jQuery('#po_result').html('');
										},
										autocomplete_action: 'po-lookup',
										autocomplete_field_name: 'term',
										select_result: po_result
									});

									var vendor_ac = new ck.autocomplete('vendor', '/admin/priority-pos.php', {
										preprocess:function() {
											jQuery('#po_result').html('');
										},
										autocomplete_results_class: 'table-results',
										autocomplete_action: 'vendor-lookup',
										autocomplete_field_name: 'term',
										process_additional_fields: function(data) {
											data.part_number = jQuery('#part_number').val();
											return data;
										},
										select_result: po_result,
										results_template: '<table cellpadding="0" cellspacing="0" border="0" class="autocomplete-results-table"><thead><tr><th>Vendor</th><th>PO #</th><th>Status</th><th>IPN</th><th>Qty</th></tr></thead><tbody>{{#results}}<tr class="table-entry" id="{{result_id}}"><td>{{vendor}}</td><td>{{purchase_order_number}}</td><td>{{status}}</td><td>{{ipn}}</td><td>{{qty}}</td></tr>{{/results}}</tbody></table>',
										auto_select_single: false
									});

									var partnum_ac = new ck.autocomplete('part_number', '/admin/priority-pos.php', {
										preprocess:function() {
											jQuery('#po_result').html('');
										},
										autocomplete_results_class: 'table-results',
										autocomplete_action: 'ipn-lookup',
										autocomplete_field_name: 'term',
										process_additional_fields: function(data) {
											data.vendor = jQuery('#vendor').val();
											return data;
										},
										select_result: po_result,
										results_template: '<table cellpadding="0" cellspacing="0" border="0" class="autocomplete-results-table"><thead><tr><th>IPN</th><th>Qty</th><th>PO #</th><th>Status</th><th>Vendor</th></tr></thead><tbody>{{#results}}<tr class="table-entry" id="{{result_id}}"><td>{{ipn}}</td><td>{{qty}}</td><td>{{purchase_order_number}}</td><td>{{status}}</td><td>{{vendor}}</td></tr>{{/results}}</tbody></table>',
										auto_select_single: false
									});

									ck.autocomplete.styles({ '.autocomplete-group': 'float:right;' });
									ck.autocomplete.styles({
										'.autocomplete-results.table-results': 'border:0px;',
										'.autocomplete-results-table': 'border-collapse:collapse; margin-right:20px;',
										'.autocomplete-results-table th': 'font-size:1.1vw; white-space:nowrap; border:1px solid #999; background-color:#9cf;',
										'.autocomplete-results-table .table-entry td': 'margin:0px; padding:4px 3px; font-size:1vw; white-space:nowrap; border:1px solid #999; cursor:pointer;',
										'.autocomplete-results-table .table-entry:hover td': 'background:linear-gradient(#6ff, #7cf);'
									});

									function po_result(data) {
										var po = '<a href="/admin/po_viewer.php?poId='+data.purchase_order_id+'" target="_blank">'+data.purchase_order_number+'</a>';
										var priority = !isNaN(data.allocated_qty)&&data.allocated_qty>0?'priority':'normal';
										var receive = '<input type="button" onclick="window.open(\'/admin/po_receiver.php?poId='+data.purchase_order_id+'\')" name="receive" value="Receive">';
										var vendor = data.vendor;
										var display_priority = !isNaN(data.allocated_qty)&&data.allocated_qty>0?'PRIORITY':'NORMAL';

										var action = '', set_bin = '';

										if (data.arrived == 1) {
											var tracking_known = 3;
											action += ' <input type="button" id="just_update" data-tracking="'+data.tracking_id+'" value="Update">';
											set_bin = '[Set Bin: <input type="text" id="associate-bin">]';
										}
										else {
											if (data.tracking_id == null && jQuery('#tracking_number').val() != '') {
												action += ' <input type="button" id="associate_tracking" data-tracking="'+jQuery('#tracking_number').val()+'" data-poid="'+data.purchase_order_id+'" value="Associate Tracking # And Mark Arrived">';
												set_bin = '[Set Bin: <input type="text" id="associate-bin">]';
												var tracking_known = 2;
											}
											else if (data.tracking_id == null) {
												var tracking_known = 0;
											}
											else {
												action += ' <input type="button" id="mark_arrived" data-tracking="'+data.tracking_id+'" data-poid="'+data.purchase_order_id+'" value="Mark Arrived">';
												set_bin = '[Set Bin: <input type="text" id="associate-bin">]';
												var tracking_known = 1;
											}
										}

										if (data.shipping_cost == null) action += '<input type="button" id="package-details-modal" value="Add Package Details">';

										jQuery('#po_result').html('<div class="po-'+priority+'">[PO#: '+po+'] [Vendor: '+vendor+'] ['+display_priority+'] ['+receive+'] '+set_bin+' '+action+'</div>');

										jQuery('#associate_tracking').click(associate_tracking);
										jQuery('#mark_arrived').click(mark_arrived);
										jQuery('#just_update').click(just_update);
										if (tracking_known == 1) jQuery('#mark_arrived').click();

										jQuery('#associate-bin').val(data.bin_number).select().keyup(function(e) {
											if (e.which == 13) {
												jQuery('#mark_arrived').click();
												jQuery('#associate_tracking').click();
												jQuery('#just_update').click();
											}
										});
									}

									function associate_tracking(e) {
										e.preventDefault();

										jQuery.ajax({
											url: '/admin/priority-pos.php?action=associate-tracking',
											dataType: 'json',
											data: {
												purchase_order_id: jQuery(this).attr('data-poid'),
												tracking_number: jQuery(this).attr('data-tracking'),
												bin_number: jQuery('#associate-bin').val()
											},
											success: function(data) {
												jQuery('#po_result').append(data.message);
												jQuery('#associate_tracking').after(' <input type="button" id="just_update" data-tracking="'+data.tracking_id+'" value="Update">');
												jQuery('#just_update').click(just_update);
												jQuery('#associate_tracking').remove();
											}
										});

										jQuery('#tracking_number').select();
									}

									function mark_arrived(e) {
										e.preventDefault();

										var tracking_id = jQuery(this).attr('data-tracking');

										jQuery.ajax({
											url: '/admin/priority-pos.php?action=mark-arrived',
											dataType: 'json',
											data: {
												purchase_order_id: jQuery(this).attr('data-poid'),
												tracking_id: tracking_id,
												bin_number: jQuery('#associate-bin').val()
											},
											success: function(data) {
												jQuery('#po_result').append(data.message);
												jQuery('#mark_arrived').after(' <input type="button" id="just_update" data-tracking="'+tracking_id+'" value="Update">');
												jQuery('#just_update').click(just_update);
												jQuery('#mark_arrived').remove();
											}
										});

										jQuery('#tracking_number').select();
									}

									function just_update(e) {
										e.preventDefault();

										jQuery.ajax({
											url: '/admin/priority-pos.php?action=just-update',
											dataType: 'json',
											data: {
												tracking_id: jQuery(this).attr('data-tracking'),
												bin_number: jQuery('#associate-bin').val()
											},
											success: function(data) {
												jQuery('#po_result').append(data.message);
											}
										});

										jQuery('#tracking_number').select();
									}

									function add_package_info() {
										jQuery.ajax({
											url: '/admin/priority-pos.php?action=add-package-info',
											dataType: 'json',
											data: {
												tracking_id: jQuery('#just_update').attr('data-tracking'),
												origin_state: jQuery('#origin-state').val(),
												origin_zip_code: jQuery('#origin-zip-code').val(),
												package_weight: jQuery('#package-weight').val(),
												package_height: jQuery('#package-height').val(),
												package_length: jQuery('#package-length').val(),
												package_width: jQuery('#package-width').val(),
												shipping_method: jQuery('#shipping-method').val()
											},
											success: function(results) {
												console.log(results);
												jQuery('#dim-weight-modal').hide();
											},
											fail: function(jqXHR, textStatus) {
												console.log(jqXHR, textStatus);
											}
										});
									}

									function open_package_detail_modal() {
										jQuery('#dim-weight-modal').show();
										jQuery('#package-weight').focus();

										function verify_input_and_move_to_the_next($this) {
											if (!$this.val()) {
												$this.addClass('missing-data');
												return;
											}
											if ($this.hasClass('missing-data')) $this.removeClass('missing-data');
										}

										function verify_inputs_and_submit() {
											missing_data = false;
											jQuery('.package-info').each(function () {
												if (!jQuery(this).val()) {
													jQuery(this).addClass('missing-data');
													missing_data = true;
												}
											});
											if (missing_data) alert('Required data is missing');
											else add_package_info();
										}

										jQuery('.package-info').bind('blur', function (e) {
											verify_input_and_move_to_the_next(jQuery(this));
										});

										jQuery('.package-info').keypress(function (e) {
											if (e.which == 13) {
												verify_input_and_move_to_the_next(jQuery(this));
												jQuery(this).next('input').focus();
											}
										});

										jQuery('#submit-details').click(function () {
											verify_inputs_and_submit();
										});

										jQuery('.close').click(function () {
											jQuery('#dim-weight-modal').hide();
										});
									}

									jQuery('#package-details-modal').live('click', function () {
										open_package_detail_modal();
									});

									/*jQuery('#tracking_number').autocomplete({
										minChars: 3,
										source: function(request, response) {
											jQuery('#po_result').html('');
											jQuery('#po_number').val('');
											jQuery('#vendor').val('');
											jQuery.ajax({
												minLength: 2,
												url: '/admin/priority-pos.php?action=tracking-lookup',
												dataType: 'json',
												data: {
													term: request.term
												},
												success: function(data) {
													if (data.length == 1) {
														po_result(data[0]);
														response([]);
													}
													else {
														response(data);
													}
												}
											});
										},
										select: function(event, ui) {
											po_result(ui.item);
										}
									});

									jQuery('#po_number').autocomplete({
										minChars: 3,
										source: function(request, response) {
											jQuery('#po_result').html('');
											jQuery.ajax({
												minLength: 2,
												url: '/admin/priority-pos.php?action=po-lookup',
												dataType: 'json',
												data: {
													term: request.term
												},
												success: function(data) {
													if (data.length == 1) {
														po_result(data[0]);
														response([]);
													}
													else {
														response(data);
													}
												}
											});
										},
										select: function(event, ui) {
											po_result(ui.item);
										}
									});

									jQuery('#vendor').autocomplete({
										minChars: 3,
										source: function(request, response) {
											jQuery('#po_result').html('');
											jQuery.ajax({
												minLength: 2,
												url: '/admin/priority-pos.php?action=vendor-lookup',
												dataType: 'json',
												data: {
													term: request.term
												},
												success: function(data) {
													if (data.length == 1) {
														po_result(data[0]);
														response([]);
													}
													else {
														response(data);
													}
												}
											});
										},
										select: function(event, ui) {
											po_result(ui.item);
										}
									});*/
								</script>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</body>
</html>
