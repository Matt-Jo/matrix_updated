<?php
require_once('ipn_editor_top.php');

$rfqs = prepared_query::fetch('SELECT cr.rfq_id, crrp.rfq_response_product_id, crr.created_date, crr.customers_id, crr.customers_extra_login_id, crrp.quantity, crrp.notes, crrp.price, c.conditions_name FROM ck_rfqs cr LEFT JOIN ck_rfq_response_products crrp ON cr.rfq_id = crrp.rfq_id LEFT JOIN ck_rfq_responses crr ON crrp.rfq_response_id = crr.rfq_response_id LEFT JOIN conditions c ON crrp.conditions_id = c.conditions_id WHERE crrp.stock_id = :stock_id ORDER BY crr.created_date DESC', cardinality::SET, [':stock_id' => $ipn->id()]);

$conditions = prepared_query::fetch('SELECT conditions_id, conditions_name FROM conditions', cardinality::SET);

$date_limit = new DateTime();
$date_limit->setTime(0, 0, 0);
$date_limit->sub(new DateInterval('P30D'));

$quote_avg = [];
$used_for_avg = [];
$ipns = [];

if (empty($_SESSION['perms']['rfq_signature'])) {
	$signature = "Thanks!\n\n".$_SESSION['perms']['admin_firstname'].' '.$_SESSION['perms']['admin_lastname']."\nCablesAndKits.com\nd) [DIRECT LINE] | t) [TOLL FREE] | f) [FAX LINE]\nIM: ".strtoupper($_SESSION['perms']['admin_firstname'])."atCK";
}
else {
	$signature = $_SESSION['perms']['rfq_signature'];
}

if (empty($_SESSION['perms']['rfq_greeting'])) {
	$greeting = "Hi There!\n\nPlease provide pricing and availability for the following.";
}
else {
	$greeting = $_SESSION['perms']['rfq_greeting'];
}

foreach ($rfqs as &$rfq) {
	$rfq['created_date'] = new DateTime($rfq['created_date']);

	if ($rfq['created_date'] >= $date_limit) {
		$quote_avg[] = $rfq['price'];
		$used_for_avg[] = $rfq['rfq_id'];
	}
}

if (count($quote_avg) >= 4) $avg_quoted_cost = array_sum($quote_avg) / count($quote_avg);
else $used_for_avg = []; ?>

<style>
	#open-rfq-modal-button { color:green; font-size:12px; }
	.modal { display:none; position:fixed; z-index:1; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgb(0,0,0); background-color:rgba(0,0,0,0.4); }
	.modal-content {  background-color:#fefefe; margin:3% auto 15% auto; padding:20px; border:1px solid #888; width:50%; }
	.modal-header { width:100%; height:auto; display:block; clear:both; overflow:auto; border-bottom:1px solid #5f9ea0; margin-bottom: 10px; }
	.close { color:#aaa; float:right; font-size:28px; font-weight:bold; cursor:pointer; }
	.modal-title { float:left; }
	.close:hover, .close:focus { color:#000; text-decoration:none; cursor:pointer; }
	.modal-content form, .modal-content input, .modal-content input[type="submit"], .modal-content button, .modal-content form textarea, .modal-content select, #submit-unpublished-post-selection { width:100%; padding:0; }
	.modal-content input, .modal-content textarea, .modal-content input[type="submit"], .modal-content button, .modal-content select, #submit-unpublished-post-selection { height:25px; margin-bottom:10px; }
	.modal-content input[type="submit"], .modal-content button, #submit-unpublished-post-selection { border:none; height:45px; }
	.modal-content input[type="submit"]:hover, .modal-content button:hover, #submit-unpublished-post-selection:hover { cursor:pointer; font-weight:bold; border:1px solid black; background-color:lightgrey; }
	.modal-content textarea { margin-top:5px; height:100px; }
	/*#signature { height:50px; }*/
	.radio-container { display:block; width:100%; }
	.checkbox-container { display:inline-block; width:15%; }
	.checkbox-container input { display:inline-block; height:auto; width:auto; }
	.radio-container input, .radio-container label, .checkbox-container input, .checkbox-container label { cursor:pointer; }
	.modal-content input[type="radio"] { display:inline; width:auto; height:auto; }
	.table-container { border-width:1px 0 1px 0; margin:10px 0; padding:10px 0; }
	.table-container table input, .table-container table select, .table-container table textarea { width:auto; height:auto; }
	.table-container table { margin:auto; }
	.input-container { display:block; width:100%; height:auto; }
	.input-container input { width:85%; display:inline-block; }
	.input-container label { width:10%; display:inline-block; }
	.manage_ipns { margin-bottom:20px; width:100%; }
	.manage_ipns th, .manage_ipns td { padding:3px 7px; border-color:#000; border-style:solid; border-width:1px 0px 0px 1px; font-size:.9em; }
	.manage_ipns th:last-child, .manage_ipns td:last-child { border-right-width:1px; }
	.manage_ipns tbody:last-child td { border-bottom-width:1px; }
	.button-group input[type="submit"], .button-group button { width:32.2%; margin:.5%; background-color:darkgrey; }
	.button-group { width:100%; display:block; overflow:hidden; }
	#save_and_publish_rfq_button, #save_to_open_rfq_button, #save_rfq_button { float:left; }

	#select-open-rfq-modal-content { width:35%; }

	#unpublished-posts-table { margin-bottom:20px; box-sizing:border-box; }
	#unpublished-posts-table tr { border-collapse:collapse; cursor:pointer; }
	#unpublished-posts-table tr td { padding:10px; }
	/*class set for highlight on table rows */
	.selected-rfq-table-row { background-color:#ffd700; }
	.highlight-rfq-table-row td { border-bottom:1px solid #000; border-top:1px solid #000; }
	.highlight-rfq-table-row td:first-child { border-left: 1px solid #000; }
	.highlight-rfq-table-row td:last-child { border-right: 1px solid #000; }

	#rfq-add-message { color:#ff0000; text-align:center; font-size:20px; }

	@media only screen and (max-width: 1770px) {
		.table-container { overflow-x:auto; }
	}
	@media only screen and (max-width: 1300px) {
		.input-container label { display:block; width:100%; }
		.input-container input { display:block; width: 100%; }
	}

	table.tablesorter tbody tr.creating td { background-color:#ffc; }
</style>
<div class="modal" id="rfq-modal">
	<div class="modal-content" id="rfq-modal-content">
		<div class="modal-header">
			<h4 id="rfq-modal-title" class="modal-title">Create RFQ for <span style="color:red;"><?= $ipn->get_header('ipn') ?></span></h4>
			<span id="close-rfq-modal" class="close">&times;</span>
		</div>
		<form method="POST" action="/admin/rfq_detail.php?action=update&source=ipn_editor&ipn=<?= $ipn->get_header('ipn'); ?>" id="rfq-form" data-ipn="<?= $ipn->get_header('ipn'); ?>">
			<div class="radio-container">
				<label for="rfq_selection">[RFQ:<input type="radio" name="request_type" class="reqtype" id="rfq_selection" value="RFQ">]</label>
				<label for="wtb_selection">[WTB:<input type="radio" name="request_type" class="reqtype" id="wtb_selection" value="WTB" checked>]</label>
			</div>
			<div class="input-container">
				<label for="nickname">Nickname</label>
				<input type="text" name="nickname" id="nickname" value="" maxlength="18">
			</div>
			<div class="input-container">
				<label for="expiration_date">Expiration</label>
				<input type="date" name="expiration_date" id="expiration_date">
			</div>
			<div class="input-container">
				<label for="send_to">Send To</label>
				<input type="text" name="send_to" id="send-to_field" value="" placeholder="[DEFAULT UNEDA]">
			</div>
			<div class="input-container">
				<label for="subject_line">Subject</label>
				<input type="text" name="subject_line" id="subject_line" maxlength="50">
			</div>
			<label for="greeting">Greeting</label>
			<textarea name="greeting" id="greeting" rows="4" cols="60"><?= $greeting; ?></textarea>
			<label for="request_details">Request Details <small style="font-size:12px;">(these instructions will be displayed on the email and on the response page for the vendors)</small></label>
			<textarea name="request_details" rows="4" cols="60"></textarea>
			<input type="hidden" id="ipn_count" value="<?= count($ipns); ?>">
				<div class="table-container">
					<table border="0" cellspacing="0" cellpadding="0" class="manage_ipns">
						<thead>
							<tr>
								<th>[X]</th>
								<th>IPN</th>
								<th>Model/Alias</th>
								<th>Condition</th>
								<th>Quantity</th>
								<th>[+]</th>
								<th>Comment</th>
							</tr>
						</thead>
						<tbody id="ipn_list">
						<?php
						if (!empty($ipns)) {
							foreach ($ipns as $idx => $ipn) { ?>
								<tr>
									<td>
										<input type="hidden" name="canonical_id[<?= $idx; ?>]" value="<?= $ipn['rfq_product_id']; ?>">
										<input type="checkbox" name="remove[<?= $idx; ?>]">
									</td>
									<td><?= $ipn['stock_name']; ?></td>
									<td><input type="text" name="alias[<?= $idx; ?>]" value="<?= $ipn['model_alias']; ?>" maxlength="25"></td>
									<td>
										<select size="1" name="condition[<?= $idx; ?>]">
											<option value="0" <?php echo $ipn['conditions_id']==0?'selected':NULL; ?>>ANY</option>
											<?php foreach ($conditions as $condition) { ?>
												<option value="<?= $condition['conditions_id']; ?>" <?= $ipn['conditions_id']==$condition['conditions_id']?'selected':NULL; ?>> <?= $condition['conditions_name']; ?>
												</option>
											<?php } ?>
										</select>
									</td>
									<td><input type="text" name="quantity[<?= $idx; ?>]" value="<?= $ipn['quantity']; ?>" style="width:45px;"></td>
									<td><input type="checkbox" name="qtyplus[<?= $idx; ?>]" <?php echo $ipn['qtyplus']==1?'checked':''; ?>></td>
									<td>
										<textarea name="comment[<?= $idx; ?>]" rows="2" cols="30" maxlength="100"><?= $ipn['comment']; ?></textarea>
									</td>
								</tr>
							<?php }
							} ?>
						</tbody>
						<tbody>
						<tr>
							<td></td>
							<td><input type="text" name="ipn_lookup" id="ipn_lookup" value=""></td>
							<td><input type="text" name="alias_lookup" value=""></td>
							<td>
								<select size="1" name="condition_lookup" id="condition_lookup">
									<option value="0">ANY</option>
									<?php foreach ($conditions as $condition) { ?>
										<option value="<?= $condition['conditions_id']; ?>"><?= $condition['conditions_name']; ?></option>
									<?php } ?>
								</select>
							</td>
							<td><input type="text" name="quantity_lookup" value="" style="width:45px;"></td>
							<td><input type="checkbox" name="qtyplus_lookup"></td>
							<td>
								<textarea name="comment_lookup" rows="2" cols="30" maxlength="100"></textarea>
							</td>
						</tr>
						</tbody>
					</table>
				</div>
			<label for="signature">Signature</label>
			<textarea name="signature" id="signature" cols="60" rows="6"><?= $signature; ?></textarea><br><br>
			<input type="checkbox" name="alert_creator" style="width:initial; margin-bottom:0px; height:initial;"> Alert me of new responses<br><br>


			<div class="button-group">
				<input type="submit" id="save_and_publish_rfq_button" name="submit" value="Save & Publish">
				<input type="submit" id="save_rfq_button" name="submit" value="Save">
				<button type="button" id="save_to_open_rfq_button">Save To Open RFQ</button>
			</div>
		</form>
	</div>
</div>
<div class="modal" id="select-open-rfq-modal">
	<div class="modal-content" id="select-open-rfq-modal-content">
		<div class="modal-header">
			<h4 class="modal-title">Choose Unpublished Post</h4>
			<span id="close-select-open-rfq-modal" class="close">&times;</span>
		</div>
		<div class="table-container">
			<table id="unpublished-posts-table" class="unpublished-posts">
				<thead>
					<tr>
						<th>RFQ ID</th>
						<th>Nickname</th>
						<th>Request Type</th>
						<th>Subject Line</th>
						<th>Created Date</th>
					</tr>
				</thead>
				<tbody>
				<?php
				$open_rfq = prepared_query::fetch('SELECT rfq_id, nickname, request_type, subject_line, created_date FROM ck_rfqs WHERE active = 1 AND published_date IS NULL AND admin_id = :admin_id', cardinality::SET, [':admin_id' => $_SESSION['perms']['admin_id']]);
				foreach ($open_rfq as $rfq) { ?>
					<tr id="rfq-<?= $rfq['rfq_id']; ?>" data-rfq-id="<?= $rfq['rfq_id']; ?>" class="choose-rfq-row">
						<td><?= $rfq['rfq_id']; ?></td>
						<td><?= $rfq['nickname']; ?></td>
						<td><?= $rfq['request_type']; ?></td>
						<td><?= $rfq['subject_line']; ?></td>
						<td><?= $rfq['created_date']; ?></td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		</div>
		<button type="submit" id="submit-unpublished-post-selection">Add To Open Post</button>
	</div>
</div>
<div>
	<a href="#" id="open-rfq-modal-button" class="button-link">Create RFQ</a>
</div>
<strong>Average Quoted Cost:</strong> <?= !empty($avg_quoted_cost)?'$'.number_format($avg_quoted_cost, 2):'N/A'; ?>
<table cellspacing="0" id="rfqtable" cellpadding="3px" border="0" class="fc tablesorter">
	<thead>
		<tr>
			<th>RFQ</th>
			<th>Response Date</th>
			<th>Price</th>
			<th>Qty</th>
			<th>Condition</th>
			<th>Customer</th>
			<th>Notes</th>
			<th>Create PO</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($rfqs as $rfq) {
			$customer = new ck_customer2($rfq['customers_id']); ?>
		<tr class="rfq-line-<?= $rfq['rfq_response_product_id']; ?>">
			<td><?= $rfq['rfq_id']; ?></td>
			<td><?= $rfq['created_date']->format('m/d/y'); ?></td>
			<td><?= CK\text::monetize($rfq['price']); ?> <?= in_array($rfq['rfq_id'], $used_for_avg)?'<strong style="color:#c00;">*AQC</strong>':''; ?></td>
			<td><?= $rfq['quantity']; ?></td>
			<td><?= $rfq['conditions_name']; ?></td>
			<td><?= $customer->get_display_label($rfq['customers_extra_login_id']); ?></td>
			<td><?= $rfq['notes']; ?></td>
			<td>
				<form action="/admin/po_editor.php" method="get" class="create-po" data-rfq-line-id="<?= $rfq['rfq_response_product_id']; ?>">
					<input type="hidden" name="action" value="new">
					<input type="hidden" name="method" value="autofill">

					<input type="hidden" name="p_email" value="<?= $customer->get_email_address($rfq['customers_extra_login_id']); ?>">
					<input type="hidden" name="p_vendor" value="<?= $customer->get_header('vendor_id'); ?>">
					<input type="hidden" name="po_list" value="<?= $ipn->id(); ?>">
					<input type="hidden" name="prices" value="<?= $rfq['price']; ?>">

					<input type="text" name="qty" value="<?= $rfq['quantity']; ?>" style="width:35px;">
					<input type="submit" value="Create PO" class="create-po-button" data-rfq-line-id="<?= $rfq['rfq_response_product_id']; ?>">
				</form>
			</td>
		</tr>
		<?php } ?>
	</tbody>
</table>

<script type="text/javascript">
	/*jQuery('.create-po-button').click(function() {
		var rfq_id = jQuery(this).data('rfq-line-id');
		jQuery('tr.rfq-line-'+rfq_id).addClass('creating');
	});

	jQuery('.create-po').submit(function(e) {
		var rfq_id = jQuery(this).data('rfq-line-id');
		if (!confirm('Confirm you want to create this PO')) {
			jQuery('tr.rfq-line-'+rfq_id).removeClass('creating');
			e.preventDefault();
			return false;
		}
	});*/

	jQuery('.choose-rfq-row').mouseenter(function (){
		jQuery(this).addClass('highlight-rfq-table-row');
	});

	jQuery('.choose-rfq-row').mouseleave(function (){
		jQuery(this).removeClass('highlight-rfq-table-row');
	});

	jQuery('.choose-rfq-row').click(function (){
		if (jQuery(this).hasClass('selected-rfq-table-row')) {
			jQuery(this).removeClass('selected-rfq-table-row');
			jQuery('#submit-unpublished-post-selection').html('Exit');
		}
		else {
			jQuery('.selected-rfq-table-row').removeClass('selected-rfq-table-row');
			jQuery(this).addClass('selected-rfq-table-row');
			jQuery('#submit-unpublished-post-selection').html('Add To Open Post');
		}
	});


    jQuery("#rfqtable").tablesorter({
		theme: 'blue',
		widgets: ['zebra']
	});

    jQuery('.reqtype').click(function() {
        var subj = jQuery('#subject_line').val();

        if (subj == '') subj = jQuery(this).val()+': ';
        else subj = subj.replace(/^(RFQ|WTB):/, jQuery(this).val()+':');

        jQuery('#subject_line').val(subj);
    });

    jQuery('#ipn_lookup').autocomplete({
        minChars: 3,
        source: function(request, response) {
            console.log(request);
            jQuery.ajax({
                minLength: 2,
                url: '/admin/serials_ajax.php?action=ipn_autocomplete',
                dataType: 'json',
                data: {
                    term: request.term,
                    search_type: 'ipn',
                    result_type: 'rfq'
                },
                success: response
            });
        },
        select: function(event, ui) {
            console.log(ui);
            add_ipn(ui.item);
        }
    });

    jQuery("#open-rfq-modal-button").click(function () {
        jQuery.ajax({
            minLength: 2,
            url: '/admin/serials_ajax.php?action=ipn_autocomplete',
            dataType: 'json',
            data: {
                term: jQuery('#rfq-form').data('ipn'),
                search_type: 'ipn',
                result_type: 'rfq'
            },
            success: function (response) {
                add_ipn(response[0]);
                if (jQuery('#subject_line').val() == '') jQuery('#subject_line').val('WTB: '+response[0].model_number);
            }
        });
	});

    function add_ipn(data) {
        var ipn_count = parseInt(jQuery('#ipn_count').val());
        var $select = jQuery('#condition_lookup').clone().removeAttr('id').attr('name', 'condition['+ipn_count+']').val(data.condition_id);
        jQuery('#ipn_list').append('<tr><td><input type="hidden" name="canonical_id['+ipn_count+']" value=""><input type="checkbox" name="remove['+ipn_count+']"></td><td>'+data.stock_name+'<input type="hidden" name="stock_id['+ipn_count+']" value="'+data.stock_id+'"></td><td><input type="text" name="alias['+ipn_count+']" value="'+data.model_number+'"></td><td id="condition_'+ipn_count+'"></td><td><input type="text" name="quantity['+ipn_count+']" id="qty_'+ipn_count+'" value="1" style="width:45px;"></td><td><input type="checkbox" name="qtyplus['+ipn_count+']"></td><td><textarea name="comment['+ipn_count+']" rows="2" cols="30" maxlength="100"></textarea></td></tr>');

        jQuery('#condition_'+ipn_count).append($select);
        jQuery('#ipn_count').val(ipn_count+1);

        setTimeout(function () { jQuery('#qty_'+ipn_count).select(); }, 0);
    }

    jQuery('#save_to_open_rfq_button').click(function() {
		jQuery('#select-open-rfq-modal').show();
		jQuery('#select-open-rfq-modal').css('z-index', '1000');
		jQuery('#rfq-modal').css('z-index', '999');
	});

    jQuery('#close-select-open-rfq-modal').click(function () {
		jQuery('#select-open-rfq-modal').hide();
	});

    jQuery('#submit-unpublished-post-selection').click(function () {
    	rfq_id = jQuery('.selected-rfq-table-row').attr('data-rfq-id');
    	if (rfq_id) {
			jQuery('<input>').attr({
				type: 'hidden',
				id: 'save_to_rfq_id',
				name: 'rfq_id',
				value: rfq_id
			}).prependTo('#rfq-form');
			jQuery('#rfq-modal-content').append('<p id="rfq-add-message">!! You are adding your item(s) to RFQ ' + rfq_id + ' !!</p>');
		}
		else {
    		jQuery('#rfq-add-message').remove();
    		jQuery('#save_to_rfq_id').remove();
		}
		jQuery('#select-open-rfq-modal').hide();
	});

    jQuery('#open-rfq-modal-button').click(function () {
    	jQuery('#rfq-modal').show();
	});

    jQuery('#close-rfq-modal').click(function () {
    	jQuery('#rfq-modal').hide();
		var ipn_count = parseInt(jQuery('#ipn_count').val());
		jQuery("#ipn_count").val(ipn_count-1);
		jQuery('#ipn_list').html('');
	});
</script>