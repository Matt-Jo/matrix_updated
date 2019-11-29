<?php
require('includes/application_top.php');

ini_set('memory_limit', '1024M');
set_time_limit(0);


if (isset($_GET['action'])) $action = strtolower(trim($_GET['action']));
else $action = NULL;

$column_prefs = is_file('srl_column_prefs.txt')?@unserialize(file_get_contents('srl_column_prefs.txt')):array();

$stock_id = !empty($_GET['stock_id'])?$_GET['stock_id']:NULL;

$ipns = array();
$forecast = new forecast($stock_id);
if (!empty($action)) {
	switch ($action) {
		case 'save columns':
			if (!isset($_POST['remove'])) {
				uasort($_POST['columns'], 'column_sort');
				$column_prefs[$_SESSION['login_id']] = $_POST['columns'];
			}
			else {
				unset($column_prefs[$_SESSION['login_id']]);
			}
			echo file_put_contents('srl_column_prefs.txt', serialize($column_prefs));
			exit();
			break;
		case 'build report':
		default:
			if (!empty($_GET['debug']) && !empty($_GET['ipns'])) {
				$ipns = $forecast->build_report(TRUE, FALSE, $__FLAG['legacy-method']);
			}
			else {
				$ipns = $forecast->build_report(NULL, FALSE, $__FLAG['legacy-method']);
			}

			// export to excel end
			$fp = @fopen(dirname(__FILE__)."/stock_reorder_report.csv", "w");
			if ($fp) {
				$column_names = array(
					'ipn' => 'IPN',
					'ipn_category' => 'IPN Category',
					'available_quantity' => 'Available Qty',
					'parent_products_qty' => 'Parent Qty',
					'adjusted_available_qty' => 'Adjusted Available Qty',
					'quarantine_available_qty' => 'Quarantine',
					'on_order' => 'On Order',
					'ttl_on_order' => 'Total On Order',
					'display180' => '6mo. Avg.',
					'p3060' => '30-60 Days',
					'to30' => '0-30 Days',
					'display_last_special' => 'Last Special Sold Date',
					'target_min_qty' => 'Target Min',
					'target_qty' => 'Target Qty',
					'target_max_qty' => 'Target Max',
					'target_buy_price' => 'Target Buy Price',
					'export_vendor_name' => 'Pref. Vendor',
					'vendors_pn' => 'Vendor PN',
					'case_qty' => 'Case Qty',
					'lead_time' => 'Lead Time',
					'runrate' => 'Chosen Runrate',
					'reorder_qty' => 'Reorder Qty'
				);
				if (!empty($_GET['save_column_list']) && isset($column_prefs[$_SESSION['login_id']])) $columns = $column_prefs[$_SESSION['login_id']];
				else $columns = $_GET['columns'];
				uasort($columns, 'column_sort');
				$columns_set = TRUE;
				// powers of 2 allow specifying the group options name specifically
				$groups_options = array(array(7 => 'All Available', 3 => 'In Building', 6 => 'Available Shortly', 5 => 'Non-Quarantined'));
				$groups_allowed = array(array('available_quantity' => 1, 'quarantine_available_qty' => 2, 'on_order' => 4));
				$found_columns = array();
				$found_groups = array();
				$idx_factor = 0;
				$max_idx = 0;
				foreach ($columns as $column => $idx) {
					if (!is_numeric($idx)) continue;
					if (isset($found_columns[$idx+$idx_factor])) {
						// we're potentially part of a group
						foreach ($groups_allowed as $group_idx => $group) {
							if (in_array($column, array_keys($group)) && in_array($found_columns[$idx+$idx_factor], array_keys($group))) {
								// this group is allowed
								if (!isset($found_groups[$idx+$idx_factor])) $found_groups[$idx+$idx_factor] = array('group' => $group_idx, 'values' => array($group[$found_columns[$idx+$idx_factor]]));
								$found_groups[$idx+$idx_factor]['values'][] = $group[$column];
								continue 2; // handle the next column
							}
						}
						// we already skipped to the next column if we found a valid group, so we need to handle the problem state
						// adding 1 to the idx_factor puts this column after the column it's conflicting with.
						// since the indexes are ordered properly, we don't have to worry about it conflicting with anything else,
						// and using the idx_factor guarantees that later columns won't conflict either
						$idx_factor++;
					}
					$found_columns[$idx+$idx_factor] = $column;
					$max_idx = $idx+$idx_factor;
				}
				$export = array();
				// build the export from the selected columns in the proper order
				$row = array();
				for ($i=1; $i<=$max_idx; $i++) {
					if (isset($found_columns[$i])) {
						if (isset($found_groups[$i])) {
							$row[$i] = $groups_options[$found_groups[$i]['group']][array_sum($found_groups[$i]['values'])];
						}
						else {
							$row[$i] = $column_names[$found_columns[$i]];
						}
					}
					else {
						$row[$i] = '';
					}
				}
				$export[] = implode(",", $row);
				foreach ($ipns as $ipnidx => $ipn) {
					if (!empty($_GET['ipns'])) {
						$selected_ipns = preg_split('/[\s|;,]/', $_GET['ipns']);
						if (!in_array($ipn['ipn'], $selected_ipns)) {
							unset($ipns[$ipnidx]);
							continue;
						}
					}

					if (!$ipn['display']) continue;
					$row = array();
					for ($i=1; $i<=$max_idx; $i++) {
						if (isset($found_columns[$i])) {
							if (isset($found_groups[$i])) {
								$group = $groups_allowed[$found_groups[$i]['group']];
								$group_data = array();
								foreach ($found_groups[$i]['values'] as $groupval) {
									$group_data[] = $ipn[array_search($groupval, $group)];
								}
								$row[$i] = array_sum($group_data); // we only allow groups on numeric data
							}
							else {
								$row[$i] = $ipn[$found_columns[$i]];
							}
						}
						else {
							$row[$i] = '';
						}
					}
					$export[] = implode(",", $row);
				}
				fwrite($fp, implode("\n", $export));
				fclose($fp);
			}
			break;
	}
}
else {
	$forecast->build_history('vendorsonly', TRUE); // give us all of the vendors, not just the ones with activity within 180 days
	$columns_set = FALSE;
	if (isset($column_prefs[$_SESSION['login_id']])) {
		$columns_set = TRUE;
		$columns = $column_prefs[$_SESSION['login_id']];
	}
}
$lock_columns = FALSE;
if (isset($column_prefs[$_SESSION['login_id']])) {
	$lock_columns = TRUE;
}
$vendorlist = $forecast->vendorlist;
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
		<title><?= TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<script language="javascript" src="includes/menu.js"></script>
		<script language="javascript" src="../includes/javascript/prototype.js"></script>
		<script type="text/javascript">
			function createpo() {
				var p_vendor = '';
				var po_list = [];
				var qty = [];

				jQuery('.add_ipn:checked').each(function(ctr) {
					p_vendor = jQuery('#p_vendor_'+jQuery(this).val()).val();
					po_list.push(jQuery(this).val());
					qty.push(jQuery('#rec_'+jQuery(this).val()).val());
				});
				window.location.href = '/admin/po_editor.php?action=new&method=autofill&p_vendor='+p_vendor+'&po_list='+po_list+'&qty='+qty;
				return false;
			}
		</script>
		<style>
			.report_options { text-align:right; }
			.report_submit { text-align:left; }
			#srl_tbl .dataTableHeadingContent input:not([type=submit]) { width:20px; }
			#srl_tbl th, #srl_tbl td { width:75px; }
			#srl_tbl th.headerHead { width:auto; }
			#srl_tbl th.wide, #srl_tbl td.wide { width:130px; }
			#srl_tbl th.related, #srl_tbl td.related { width:40px; text-align:center; }
			#srl_tbl th.available { background-color:#7cc; }
			#srl_tbl .dataTableRowSelected td.available { background-color:#9ee; }
			#srl_tbl .dataTableRow td.available { background-color:#bee; }
			#srl_tbl th.sold { background-color:#7c7; }
			#srl_tbl .dataTableRowSelected td.sold { background-color:#9e9; }
			#srl_tbl .dataTableRow td.sold { background-color:#beb; }
			#srl_tbl th.target { background-color:#cc7; }
			#srl_tbl .dataTableRowSelected td.target { background-color:#ee9; }
			#srl_tbl .dataTableRow td.target { background-color:#eeb; }
			#srl_tbl .hideRow { display:none; }
			#srl_tbl .empty { color:#f00; }
			#srl_tbl .empty a { color:#f00; }
			#srl_tbl .filtered-off { display:none; }
			#srl_tbl thead.frozen { position:fixed; top:0px; }
			#srl_tbl thead.frozen tr.hider th { border-bottom:3px solid #c00; }
			#srl_tbl input.column-selections[disabled] { background-color:#c9c9c9; color:#000; }
		</style>
	</head>
	<body marginstyle="width:0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<!-- header //-->
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
		<script language="javascript" src="includes/general.js"></script>
		<script type="text/javascript">
			var clicked_button;
			current_livefilter_options = {
				<?php if ($forecast->vendor_id) { echo 'vendor_id: '.$forecast->vendor_id.', default_vendor_id: '.$forecast->vendor_id.','; } ?>
				<?php if ($forecast->show_discontinued) { echo 'discontinued: true, default_discontinued: true,'; } ?>
				<?php if ($forecast->select_selected_vendor) { echo 'select_selected_vendor: true, default_select_selected_vendor: true,'; } ?>
				<?php if ($forecast->show_all_ipns) { echo 'show_all_ipns: true, default_show_all_ipns: true,'; } ?>
			};
			jQuery(document).ready(function($) {
				recolor_srl();

				/* freeze the table header at the top of the page */
				/* this will need to be generalized if we alter the tables on this page so more than one has a thead */
				thead_pos = jQuery('thead').offset().top;
				jQuery(window).scroll(function () {
					if (jQuery('#srl_tbl thead').offset().top - jQuery(window).scrollTop() <= 1) {
						jQuery('#srl_tbl thead').addClass('frozen');
						if (jQuery('#srl_tbl .column-selection').hasClass('hideRow')) {
							jQuery('#srl_tbl thead tr.mainheader').addClass('hider');
						}
						else {
							jQuery('#srl_tbl thead tr.column-selection').addClass('hider');
						}
					}
					if (jQuery(window).scrollTop() <= thead_pos) {
						jQuery('#srl_tbl thead').removeClass('frozen');
						jQuery('#srl_tbl thead tr').removeClass('hider');
					}
					if (jQuery(window).scrollTop() >= thead_pos + jQuery('table#srl_tbl').height()) {
						jQuery('#srl_tbl thead').removeClass('frozen');
						jQuery('#srl_tbl thead tr').removeClass('hider');
					}
				});
				jQuery('#selected_vendor').change(function () {
					if (jQuery(this).val() != '') {
						current_livefilter_options['vendor_id'] = jQuery(this).val();
						// handle vendor selection
						jQuery('.data-row-handle').removeClass('filtered-on').addClass('filtered-off');
						jQuery('.vendor-'+jQuery(this).val()).removeClass('filtered-off').addClass('filtered-on');
						// handle discontinued products
						if (!current_livefilter_options['discontinued']) {
							jQuery('.vendor-'+jQuery(this).val()+'.discontinued').removeClass('filtered-on').addClass('filtered-off');
						}
						// handle if we're auto selecting vendor, and only do it for rows that are showing (not discontinued)
						jQuery('.data-row-handle input[type=checkbox]').removeAttr('checked');
						if (current_livefilter_options['select_selected_vendor']) {
							jQuery('.vendor-'+jQuery(this).val()+'.filtered-on input[type=checkbox]').attr('checked', 'checked');
						}
					}
					else {
						delete current_livefilter_options['vendor_id'];
						// handle vendor selection
						jQuery('.data-row-handle').removeClass('filtered-off').addClass('filtered-on');
						// handle discontinued products
						if (!current_livefilter_options['discontinued']) {
							jQuery('.data-row-handle.discontinued').removeClass('filtered-on').addClass('filtered-off');
						}
						// handle vendor auto select
						jQuery('.data-row-handle input[type=checkbox]').removeAttr('checked');
					}
					jQuery('.data-row-handle.filtered-off input[type=checkbox]').removeAttr('checked');
					if (current_livefilter_options['vendor_id'] == current_livefilter_options['default_vendor_id'] && current_livefilter_options['discontinued'] == current_livefilter_options['default_discontinued']) {
						jQuery('#export_link').text('Export');
					}
					else {
						jQuery('#export_link').text('**Export');
					}
					recolor_srl();
				});
				jQuery('#show_discontinued').click(function () {
					if (jQuery(this).is(':checked')) {
						current_livefilter_options['discontinued'] = true;
						// handle discontinued products for the currently selected vendor, if any
						if (current_livefilter_options['vendor_id']) {
							jQuery('.data-row-handle.vendor-'+current_livefilter_options['vendor_id']+'.discontinued').removeClass('filtered-off').addClass('filtered-on');
							if (current_livefilter_options['select_selected_vendor']) {
								jQuery('.data-row-handle.vendor-'+current_livefilter_options['vendor_id']+'.discontinued input[type=checkbox]').attr('checked', 'checked');
							}
						}
						else {
							jQuery('.data-row-handle.discontinued').removeClass('filtered-off').addClass('filtered-on');
						}
					}
					else {
						delete current_livefilter_options['discontinued'];
						jQuery('.data-row-handle.discontinued').removeClass('filtered-on').addClass('filtered-off');
					}
					jQuery('.data-row-handle.filtered-off input[type=checkbox]').removeAttr('checked');
					if (current_livefilter_options['vendor_id'] == current_livefilter_options['default_vendor_id'] && current_livefilter_options['discontinued'] == current_livefilter_options['default_discontinued']) {
						jQuery('#export_link').text('Export');
					}
					else {
						jQuery('#export_link').text('**Export');
					}
					recolor_srl();
				});
				jQuery('#show_all_ipns').click(function () {
					if (jQuery(this).is(':checked')) {
						current_livefilter_options['show_all_ipns'] = true;
						// handle show_all_ipns products for the currently selected vendor, if any
						if (current_livefilter_options['vendor_id']) {
							jQuery('.data-row-handle.vendor-'+current_livefilter_options['vendor_id']+'.show_all_ipns').removeClass('filtered-off').addClass('filtered-on');
							if (current_livefilter_options['select_selected_vendor']) {
								jQuery('.data-row-handle.vendor-'+current_livefilter_options['vendor_id']+'.show_all_ipns input[type=checkbox]').attr('checked', 'checked');
							}
						}
						else {
							jQuery('.data-row-handle.show_all_ipns').removeClass('filtered-off').addClass('filtered-on');
						}
					}
					else {
						delete current_livefilter_options['show_all_ipns'];
						jQuery('.data-row-handle.show_all_ipns').removeClass('filtered-on').addClass('filtered-off');
					}
					jQuery('.data-row-handle.filtered-off input[type=checkbox]').removeAttr('checked');
					if (current_livefilter_options['vendor_id'] == current_livefilter_options['default_vendor_id'] && current_livefilter_options['show_all_ipns'] == current_livefilter_options['default_show_all_ipns']) {
						jQuery('#export_link').text('Export');
					}
					else {
						jQuery('#export_link').text('**Export');
					}
					recolor_srl();
				});
				jQuery('#select_selected_vendor').click(function () {
					if (jQuery(this).is(':checked')) {
						current_livefilter_options['select_selected_vendor'] = true;
						if (!current_livefilter_options['vendor_id']) return;
						jQuery('.vendor-'+current_livefilter_options['vendor_id']+' input[type=checkbox]').attr('checked', 'checked');
					}
					else {
						delete current_livefilter_options['select_selected_vendor'];
						if (!current_livefilter_options['vendor_id']) return;
						jQuery('.vendor-'+current_livefilter_options['vendor_id']+' input[type=checkbox]').removeAttr('checked');
					}
				});
				jQuery('#rebuild_columns').click(function () {
					if (jQuery(this).is(':checked')) {
						jQuery('.column-selection').removeClass('hideRow');
						if (jQuery('#srl_tbl .mainheader').hasClass('hider')) {
							jQuery('#srl_tbl .mainheader').removeClass('hider');
							jQuery('.column-selection').addClass('hider')
						}
					}
					else {
						jQuery('.column-selection.hidePossible').addClass('hideRow');
						if (jQuery('.column-selection').hasClass('hider')) {
							jQuery('#srl_tbl .mainheader').addClass('hider');
							jQuery('.column-selection').removeClass('hider')
						}
					}
				});
				jQuery('#save_column_list').click(function() {
					if (jQuery(this).is(':checked')) {
						jQuery.post('<?= $_SERVER['PHP_SELF']; ?>?action=save+columns', jQuery('#srl').serialize());
						jQuery('#srl_tbl thead input[type=text]').attr('disabled', 'disabled');
					}
					else {
						jQuery('#srl_tbl thead input[type=text]').removeAttr('disabled');
						jQuery.post('<?= $_SERVER['PHP_SELF']; ?>?action=save+columns', 'remove=1');
					}
				});

				$('.tooltip_qty a').each(function() {
					$(this).qtip({
						content: {
							url: 'quarantine_popup.php',
							data: { ipn: $(this).attr('tooltip') },
							method: 'get'
						},
						show: 'mouseover',
						hide: 'mouseout',
						position: {
							corner: {
								tooltip: 'TopLeft',
								target: 'topRight'
							}
						},
						style: {
							tip: {
								corner: 'TopLeft'
							},
							border: {
								color: '#cb2026'
							},
							name: 'red'
						}
					});
				});
				$('.tooltip_ipn a').each(function() {
					$(this).qtip({
					content: {
							url: 'ipn_popup.php',
							data: { ipn: $(this).attr('tooltip') },
							method: 'get'
						},
						show: 'mouseover',
						hide: 'mouseout',
						position: {
							corner: {
								tooltip: 'TopLeft',
								target: 'topRight'
							}
						},
						style: {
							backgroundColor: '#F2F2F2',
							tip: {
								corner: 'TopLeft'
							},
							border: {
								color: '#000000'
							},
							width: '400px',
							name: 'red'
						}
					});
				});

				function submit_as_form(action, method, data) {
					var $form = jQuery('<form action="'+action+'" method="'+method.toLowerCase()+'" target="_BLANK"></form>');

					for (var key in data) {
						if (data.hasOwnProperty(key)) {
							if (jQuery.isArray(data[key])) {
								for (var i=0; i<data[key].length; i++) {
									$form.append('<input type="hidden" name="'+key+'['+i+']" value="'+data[key][i]+'">');
								}
							}
							else {
								$form.append('<input type="hidden" name="'+key+'" value="'+data[key]+'">');
							}
						}
					}
					jQuery('body').append($form);
					$form.submit();
				}

				jQuery('#createrfq').click(function(e) {
					e.preventDefault();
					var data = { action: 'create-from-srl', stock_id: [], quantity: [] };
					var ctr = 0;
					jQuery('.add_ipn:checked').each(function() {
						data.stock_id[ctr] = jQuery(this).val();
						data.quantity[ctr] = jQuery('#rec_'+jQuery(this).val()).val();
						ctr++;
					});

					submit_as_form('/admin/rfq_detail.php', 'post', data);
					return false;
				});
			});
			function recolor_srl() {
				jQuery('#srl_tbl tbody tr.filtered-on:even').removeClass('dataTableRow').addClass('dataTableRowSelected');
				jQuery('#srl_tbl tbody tr.filtered-on:odd').removeClass('dataTableRowSelected').addClass('dataTableRow');
			}
		</script>
		<!-- header_eof //-->
		<!-- body //-->
		<table border="0" style="width:100%" cellspacing="2" cellpadding="2">
			<tr>
				<td style="width:<?= BOX_WIDTH; ?>" valign="top">
					<table border="0" style="width:<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
						<!-- left_navigation //-->
						<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
						<!-- left_navigation_eof //-->
					</table>
				</td>
				<!-- body_text //-->
				<td style="width:100%" valign="top">
					<form action="<?= $_SERVER['PHP_SELF']; ?>" id="srl" method="get">
						<input type="hidden" name="selected_box" value="purchasing">
						<div style="border: 1px solid black; padding: 10px 10px 200px 10px;">
							<div class="report_options">
								Vendor:
								<select name="vendor_id" id="selected_vendor" class="livefilter">
									<option value=""><!-- no selected vendor --></option>
									<?php foreach ($vendorlist as $vendor_id => $vendor) { ?>
									<option value="<?= $vendor_id; ?>"<?php if ($forecast->vendor_id==$vendor_id) { echo ' selected="selected"'; } ?>><?= $vendor; ?></option>
									<?php } ?>
								</select>
								<input type="checkbox" name="select_selected_vendor" id="select_selected_vendor" class="livefilter"<?php if ($forecast->select_selected_vendor) { echo 'checked'; } ?>><br>
								<input type="checkbox" name="show_discontinued" id="show_discontinued" class="livefilter"<?php if ($forecast->show_discontinued) { echo 'checked'; } ?>> Show Discontinued<br>
								<input type="submit" name="action" value="Build Report"><br>
								<input type="checkbox" name="rebuild_columns" id="rebuild_columns"> Rebuild Columns<br>
								<input type="checkbox" name="show_all_ipns" id="show_all_ipns" class="livefilter"<?php if ($forecast->show_all_ipns) { echo 'checked'; } ?>> Show All IPNs <small>(You must select a vendor & re-build the report)</small><br>
								<input type="checkbox" name="legacy-method" id="legacy-method" <?= $__FLAG['legacy-method']?'checked':''; ?>> Old Style Lead Time (must rebuild)<br>
							</div>
							<div class="report_submit">
								<input type="button" name="action" value="Create PO" onclick="createpo();">
								<input type="button" value="Create RFQ/WTB" id="createrfq">
							</div>
							<table cellspacing="0" cellpadding="2px" border="0" id="srl_tbl">
								<thead>
									<tr class="dataTableHeadingRow topheader">
										<th colspan="5" class="dataTableHeadingContent headerHead"><!-- placeholder --></th>
										<th colspan="6" class="dataTableHeadingContent headerHead available"><small>Available</small></th>
										<!--th colspan="5" class="dataTableHeadingContent headerHead sold"><small>Sales</small></th-->
										<th class="dataTableHeadingContent headerHead sold"></th>
										<th colspan="4" class="dataTableHeadingContent headerHead target"><small>Targets</small></th>
										<th colspan="2" class="dataTableHeadingContent"><!-- placeholder --></th>
										<th class="dataTableHeadingContent"><?php if (!empty($ipns)) { ?><a href="download_csv.php?saveas=stock_reorder_report" id="export_link">Export</a><?php } else { ?><!-- placeholder --><?php } ?></th>
									</tr>
									<tr class="dataTableHeadingRow mainheader">
										<th class="dataTableHeadingContent">
											Select
										</th>
										<th class="dataTableHeadingContent wide">
											IPN
										</th>
										<th class="dataTableHeadingContent wide">
											IPN Category
										</th>
										<th class="dataTableHeadingContent wide">
											Pref. vendor
										</th>
										<th class="dataTableHeadingContent">
											<small>Vendor PN</small>
										</th>
										<th class="dataTableHeadingContent related available">
											Avl.
										</th>
										<th class="dataTableHeadingContent related available">
											Par. Qty
										</th>
										<th class="dataTableHeadingContent related available">
											Adj. Avl.
										</th>
										<th class="dataTableHeadingContent related available">
											Hld.
										</th>
										<th class="dataTableHeadingContent related available">
											Near On Ord.
										</th>
										<th class="dataTableHeadingContent related available">
											Late On Ord.
										</th>
										<!--th class="dataTableHeadingContent related sold">
											6mo Avg.
										</th>
										<th class="dataTableHeadingContent related sold">
											30-60
										</th>
										<th class="dataTableHeadingContent related sold">
											0-30
										</th>
										<th class="dataTableHeadingContent related sold">
											Qs
										</th>
										<th class="dataTableHeadingContent related sold">
											Last Spec.
										</th-->
										<th class="dataTableHeadingContent related sold">
											Trig Days
										</th>
										<th class="dataTableHeadingContent related target">
											Lead Time
										</th>
										<th class="dataTableHeadingContent related target">
											Min Days
										</th>
										<th class="dataTableHeadingContent related target">
											Tgt Days
										</th>
										<th class="dataTableHeadingContent related target">
											Max Days
										</th>
										<!--th class="dataTableHeadingContent related target">
											Buy Price
										</th-->
										<th class="dataTableHeadingContent">
											Chosen Runrate
										</th>
										<th class="dataTableHeadingContent">
											<small>Case Qty</small>
										</th>
										<th class="dataTableHeadingContent">
											Reorder Qty
										</th>
									</tr>
									<?php
									$default_columns = array('ipn' => 1, 'ipn_category' => '1', 'available_quantity' => 2, 'parent_products_qty' => 2, 'adjusted_available_qty' => 2, 'quarantine_available_qty' => 2, 'on_order' => 2, 'ttl_on_order' => 2,'display180' => 3, 'p3060' => 4, 'to30' => 5, 'display_last_special' => '', 'trigger_qty' => '', 'target_min_qty' => 6, 'target_qty' => 7, 'target_max_qty' => 8, 'target_buy_price' => 9, 'export_vendor_name' => 10, 'vendors_pn' => 11, 'case_qty' => 12, 'lead_time' => 13, 'runrate' => 14, 'reorder_qty' => 15);
									if (empty($columns_set)) {
										$columns = $default_columns;
									}
									else {
										foreach ($default_columns as $col => $idx) {
											if (empty($columns[$col])) $columns[$col] = NULL;
										}
									}
									?>
									<tr class="dataTableHeadingRow column-selection <?php if (!empty($action)) { ?>hideRow hidePossible<?php } ?>">
										<th class="dataTableHeadingContent">Export Cols:<br><img src="images/icons/lock2.ico"><!-- This image found at: http://www.freefavicon.com/freefavicons/network/alpha.php?alpha=l --> <input type="checkbox" name="save_column_list" id="save_column_list"<?= $lock_columns?' checked="checked"':''; ?>></th>
										<th class="dataTableHeadingContent wide">
											<input type="text" name="columns[ipn]" class="column-selections" value="<?= $columns['ipn']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent wide">
											<input type="text" name="columns[ipn_category]" class="column-selections" value="<?= $columns['ipn_category']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent wide">
											<input type="text" name="columns[export_vendor_name]" class="column-selections" value="<?= $columns['export_vendor_name']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent">
											<input type="text" name="columns[vendors_pn]" class="column-selections" value="<?= $columns['vendors_pn']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent related available">
											<input type="text" name="columns[available_quantity]" class="column-selections" value="<?= $columns['available_quantity']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent related available">
											<input type="text" name="columns[parent_products_qty]" class="column-selections" value="<?= $columns['parent_products_qty']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent related available">
											<input type="text" name="columns[adjusted_available_qty]" class="column-selections" value="<?= $columns['adjusted_available_qty']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent related available">
											<input type="text" name="columns[quarantine_available_qty]" class="column-selections" value="<?= $columns['quarantine_available_qty']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent related available">
											<input type="text" name="columns[on_order]" class="column-selections" value="<?= $columns['on_order']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent related available">
											<input type="text" name="columns[ttl_on_order]" class="column-selections" value="<?= $columns['ttl_on_order']; ?>"<?= $lock_columns?' disabled="disabled"':'';?>>
										</th>
										<!--th class="dataTableHeadingContent related sold">
											<input type="text" name="columns[display180]" class="column-selections" value="<?= $columns['display180']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent related sold">
											<input type="text" name="columns[p3060]" class="column-selections" value="<?= $columns['p3060']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent related sold">
											<input type="text" name="columns[to30]" class="column-selections" value="<?= $columns['to30']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent related sold">
											<input type="text" name="columns[display_last_special]" class="column-selections" value="<?= $columns['display_last_special']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th-->
										<th class="dataTableHeadingContent related sold">
											<input type="text" name="columns[trigger_qty]" class="column-selections" value="<?= $columns['trigger_qty']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent related target">
											<input type="text" name="columns[lead_time]" class="column-selections" value="<?= $columns['lead_time']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent related target">
											<input type="text" name="columns[target_min_qty]" class="column-selections" value="<?= $columns['target_min_qty']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent related target">
											<input type="text" name="columns[target_qty]" class="column-selections" value="<?= $columns['target_qty']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent related target">
											<input type="text" name="columns[target_max_qty]" class="column-selections" value="<?= $columns['target_max_qty']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<!--th class="dataTableHeadingContent related target">
											<input type="text" name="columns[target_buy_price]" class="column-selections" value="<?= $columns['target_buy_price']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th-->
										<th class="dataTableHeadingContent">
											<input type="text" name="columns[runrate]" class="column-selections" value="<?= $columns['runrate']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent">
											<input type="text" name="columns[case_qty]" class="column-selections" value="<?= $columns['case_qty']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
										<th class="dataTableHeadingContent">
											<input type="text" name="columns[reorder_qty]" class="column-selections" value="<?= $columns['reorder_qty']; ?>"<?= $lock_columns?' disabled="disabled"':''; ?>>
										</th>
									</tr>
								</thead>
								<tbody>
									<?php if (!empty($ipns)) {
										foreach ($ipns as $rowidx => $row) {
											$row_display = 'vendor-'.$row['vendors_id'];
											$row_display .= $row['available_quantity']<=0?' empty':'';
											$row_display .= $row['discontinued']?' discontinued':'';
											$row_display .= $row['show_only_with_all']?' show_all_ipns':'';
											$row_display .= $row['display']?' filtered-on':' filtered-off';
											// go ahead and dereference the stock ID so we can interpolate it directly
											$stock_id = $row['stock_id'];
											$ipn = new ck_ipn2($stock_id);
											?>
									<tr class="data-row-handle <?= $row_display; ?>">
										<td class="main">
											<input type="checkbox" name="add_po[<?= $stock_id; ?>]" value="<?= $stock_id; ?>" <?= $row['checked']?'checked':''; ?> class="add_ipn">
										</td>
										<td class="main wide">
											<span class="tooltip_ipn"><a href="ipn_editor.php?selectedTab=2&ipnId=<?= urlencode($row['ipn']); ?>" tooltip="<?= $stock_id; ?>" target="_blank"><?= $row['ipn']; ?></a></span>
										</td>
										<td class="main wide">
											<span class="tooltip_ipn"><?= $row['ipn_category']; ?></span>
										</td>
										<td class="main wide">
											<?= $row['vendors_company_name']; ?>
											<input type="hidden" id="p_vendor_<?= $stock_id; ?>" value="<?= $row['vendors_id']; ?>">
										</td>
										<td class="main">
											<small><?= $row['vendors_pn']; ?></small>
										</td>
										<td class="main related available">
											<?= $row['available_quantity']; ?>
										</td>
										<td class="main related available">
											<?= $row['parent_products_qty']; ?>
										</td>
										<td class="main related available">
											<?= $row['adjusted_available_qty']; ?>
										</td>
										<td class="main related available">
											<span class="tooltip_qty"><a href="#" tooltip="<?= $stock_id; ?>"><?= $row['quarantine_available_qty']; ?></a></span>
										</td>
										<td class="main related available">
											<span title="Will arrive before <?= $row['lead_time']; ?> Days"><?= $row['on_order']; ?></span>
										</td>
										<td class="main related available">
											<span title="Will arrive after <?= $row['lead_time']; ?> Days"><?= $ipn->get_inventory('on_order') - $row['on_order']; ?></span>
										</td>
										<!--td class="main related sold">
											<?= $row['display180']; ?>
										</td>
										<td class="main related sold">
											<?= $row['p3060']; ?>
										</td>
										<td class="main related sold">
											<?= $row['to30']; ?>
										</td>
										<td class="main related sold">
											<small><?= $row['display_last_special']; ?></small>
										</td-->
										<td class="main related sold">
											<span title="<?= $row['target_min_qty']; ?> Units"><?= ceil($row['lead_factor'] * forecast::min_qty_factor); ?></span>
										</td>
										<td class="main related target">
											<?= $row['lead_time']; ?>
										</td>
										<td class="main related target">
											<span title="<?= ceil($row['min_inventory_level'] * $forecast->daily_qty($row)); ?> Units"><?= $row['min_inventory_level']; ?></span>
										</td>
										<td class="main related target">
											<span title="<?= $row['initial_target_qty']; ?> Units"><?= $row['target_inventory_level']; ?></span>
										</td>
										<td class="main related target">
											<span title="<?= $row['target_max_qty']; ?> Units"><?= $row['max_inventory_level']; ?></span>
										</td>
										<!--td class="main related target">
											<small><small><?= $row['display_target_buy_price']; ?></small></small>
										</td-->
										<td class="main" style="text-align:center;">
											<span title="180:<?= round($row['180_runrate'], 2); ?>; 30-16:<?= round($row['3060_runrate'], 2); ?>; 0-30:<?= round($row['30_runrate'], 2); ?>"><?= round($forecast->daily_qty($row), 2); ?></span>
										</td>
										<td class="main">
											<small><?= !empty((int) $row['case_qty'])?$row['case_qty']:''; ?></small>
										</td>
										<td class="main">
											<input type="text" size="5" id="rec_<?= $stock_id; ?>" value="<?= $row['reorder_qty']; ?>">
										</td>
									</tr>
										<?php }
									}
									else { ?>
									<tr class="dataTableHeadingRow">
										<th colspan="19" class="dataTableHeadingContent">
											<input type="submit" name="action" value="Build Report">
										</th>
									</tr>
									<?php } ?>
								</tbody>
							</table>
							<div class="report_submit">
								<input type="button" name="action" value="Create PO" onclick="createpo();">
							</div>
						</div>
					</form>
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
</html>
