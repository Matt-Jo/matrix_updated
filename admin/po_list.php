<?php
require('includes/application_top.php');

$GLOBALS['use_jquery_1.8.3'] = true;

if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'customer_search':
			$vendors = prepared_query::fetch('SELECT vendors_company_name, vendors_id FROM vendors WHERE vendors_company_name LIKE ? GROUP BY vendors_id ORDER BY vendors_company_name', cardinality::SET, $_REQUEST['search_string'].'%');
			echo '<ul>';
			foreach ($vendors as $vendor) {
				echo '<li id="'.$vendor['vendors_id'].'">'.$vendor['vendors_company_name'].'</li>';
			}
			echo '</ul>';
			die();
			break;
		case 'tracking_search':
			$trackings = prepared_query::fetch('SELECT DISTINCT pot.tracking_number FROM purchase_order_tracking pot WHERE pot.tracking_number LIKE ?', cardinality::SET, str_replace(' ', '', $_REQUEST['search_string']).'%');
			echo '<ul>';
			foreach ($trackings as $tracking) {
				echo '<li id="'.$tracking['tracking_number'].'">'.$tracking['tracking_number'].'</li>';
			}
			echo '</ul>';
			die();
			break;
		default:
			break;
	}

}
$make_active = $append_params = '';
if (!empty($_GET['po_search'])) {
	$make_active = 'class="ui-tabs-selected"';
	$make_active_link = '<a href="po_list_content.php?po_search='.$_GET['po_search'].'">All PO\'s</a>';
}
elseif (!empty($_GET['vendor_search'])) {
	$make_active = 'class="ui-tabs-selected"';
	$make_active_link = '<a href="po_list_content.php?action=tabs-5&vendor_search='.$_GET['vendor_search'].'">All PO\'s</a>';
	$append_params = "&vendor_search=".$_GET['vendor_search'];
}
elseif ($__FLAG['only_open_pos']) {
	$make_active = 'class="ui-tabs-selected"';
	$make_active_link = '<a href="po_list_content.php?action=tabs-5&only_open_pos=1">All POs</a>';
	$append_params = '&only_open_pos=1';
}
elseif (!empty($_REQUEST['my_pos'])) {
	$make_active = 'class="ui-tabs-selected"';
	$make_active_link = '<a href="po_list_content.php?action=tabs-5&my_pos=true">All PO\'s</a>';
	$append_params = "&my_pos=true";
}
elseif (!empty($_GET['tracking_search'])) {
	$make_active = 'class="ui-tabs-selected"';
	$make_active_link = '<a href="po_list_content.php?action=tabs-5&tracking_search='.$_GET['tracking_search'].'">All PO\'s</a>';
	$append_params = "&tracking_search=".$_GET['tracking_search'];
}
else {
	$make_active_link = '<a href="po_list_content.php?action=tabs-5">All PO\'s</a>';
} ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<style>
		.main {font-size:10px;}
	</style>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<script type="text/javascript" language="javascript" src="/admin/includes/javascript/DataTables-1.10.5/media/js/jquery.dataTables.js"></script>
	<link rel="stylesheet" type="text/css" href="/admin/includes/javascript/DataTables-1.10.5/media/css/jquery.dataTables.css"/>
	<script language="javascript" src="includes/menu.js"></script>
	<!-- header_eof //-->
	<!-- body //-->
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?php echo BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top" class="main">
				<!-- ----------------------------------------------------------------- -->
				<script type="text/javascript">
					jQuery(document).ready(function($) {
						$("#please_wait").bind("ajaxSend", function() {
							$(this).show();
						}).bind("ajaxComplete", function() {
							$(this).hide();
						});

						$("#tabs").tabs({
							select: function(event, ui) {
								clear_ipn_search();
								return true;
							}
						});
						$("#po_search").keypress(function(e) {
							if (e.which == 13) {
								$("#po_search_button").trigger('click');
							}
						});

						$("#ipn_search").keypress(function(e) {
							if (e.which == 13) {
								$("#ipn_search_button").trigger('click');
							}
						});

						$("#po_search_button").click(function(ev) {
							//MMD - change this to refresh page so the search gets store in the URL
							//per Chris's request on 11/22/11
							window.location = 'po_list.php?po_search=' + $("#po_search").val();
							/*var page = '1';
							var tabs = 'tabs-5';
							var table_id = $("#tabs .ui-state-active").attr("table_id");
							var po_num = $("#po_search").val();
							$.ajax({
								type: "GET",
								url: "po_list_content.php",
								data: "po_search="+po_num,
								success: function(html) {
									$("#"+table_id).replaceWith(html);
								}
							});*/
						});
						$("#ipn_search_button").click(function(ev) {
							var page = '1';
							var tabs = $("#tabs .ui-state-active").attr("id");
							var table_id = $("#tabs .ui-state-active").attr("table_id");
							var ipn_num = $("#ipn_search").val();
							$.ajax({
								type: "GET",
								url: "po_list_content.php",
								data: "ipn_search="+ipn_num+"&action="+tabs,
								success: function(html) {
									$("#"+table_id).replaceWith(html);
								}
							});
						});

						$("#show_all_button").click(function(ev) {
							var page = '1';
							var tabs = $("#tabs .ui-state-active").attr("id");
							var table_id = $("#tabs .ui-state-active").attr("table_id");
							window.location.href="po_list.php?tabs="+tabs;
						});
						$("#my_pos").click(function(ev) {
							if ($("#my_pos").attr('checked')) {
							var page = '1';
							var tabs = $("#tabs .ui-state-active").attr("id");
							var table_id = $("#tabs .ui-state-active").attr("table_id");
							window.location = 'po_list.php?my_pos=true';
							} else {
							window.location.href="po_list.php?tabs="+tabs;
							}
						});
						$('#only_open_pos').click(function(e) {
							var page = '1';
							var tabs = $('#tabs .ui-state-active').attr('id');
							var table_id = $('#tabs .ui-state-active').attr('table_id');

							if ($(this).is(':checked')) {
								window.location.href = '/admin/po_list.php?tabs='+tabs+'&only_open_pos=1';
							}
							else {
								window.location.href = '/admin/po_list.php?tabs='+tabs;
							}
						});
					});

					function clear_ipn_search() {
						jQuery("#ipn_search").val('');
					}

					function show_sessions(po_id) {
						if ($('tr_pors_'+po_id) != undefined ) {
							$('tr_pors_'+po_id).toggle();
						}
						else {
							new Ajax.Updater('tr_'+po_id, '/admin/po_list_content.php',
								{
									parameters: {action: 'get_pors', po_id: po_id},
									insertion: 'after',
									onComplete: function(s) {
										var bg_color = $('tr_'+po_id).getStyle('background-color');
										$('tr_pors_'+po_id).setStyle({backgroundColor: bg_color});
									}
								});
						}
					}
				</script>
				<div style="width:90%; border:0px solid #333333; margin:5px; height:800px; padding:10px;">
					<div class="pageHeading" style="padding-bottom:20px;">PO List</div>
					<div>
						<table cellspacing="5px" cellpadding="5px" border="0">
							<tr>
								<td class="main" colspan="6">
									<input type="button" onclick="window.location='po_editor.php?action=new';" value="New Purchase Order"/>
								</td>
							</tr>
							<tr>
								<td class="main">Search by PO #:</td>
								<td class="main">
									<input type="text" id="po_search" name="po_search"/>
									<input type="button" id="po_search_button" value="Go"/>
									<script type="text/javascript">
										document.getElementById('po_search').focus();
									</script>
								</td>
								<td class="main">Search by IPN:</td>
								<td class="main">
									<input type="text" id="ipn_search" name="ipn_search"/>
									<input type="button" id="ipn_search_button" value="Go"/>
								</td>
								<td class="main">Search by Vendor:</td>
								<td class="main">
									<input id="customer_autocomplete" type="text" size="40"/>
									<div id="customer_choices" class="autocomplete" style="border: 1px solid rgb(0, 0, 0); display: none; background-color: rgb(255, 255, 255); z-index: 100;"></div>
									<script type="text/javascript">
										new Ajax.Autocompleter('customer_autocomplete', "customer_choices", "po_list.php", {
											method: 'get',
											minChars: 3,
											paramName: 'search_string',
											parameters: 'action=customer_search',
											afterUpdateElement: function(input, li) {
												var i = 0;
												var $obj = null;
												while (true) {
													i++;
													var $objNew = jQuery('#tabs-'+i);
													if ($objNew.get(0) == null) {
														// ran out of elems
														break;
													}
													else {
														$obj = $objNew;
													}
													console.log($obj, 'tabs-'+i);

													if ($obj.hasClass('ui-tabs-selected')) {
														// found it
														break;
													}
												}
												var tabData;
												if ($obj != null) {
													tabData = $obj.find('a').attr('href');
												}
												else {
													tabData = "";
												}
												window.location='po_list.php?vendor_search='+li.id+tabData;
											}
										});
									</script>
								</td>
								<td>
									<input type="button" id="show_all_button" value="Show All"/>
								</td>
							</tr>
							<tr>
								<td colspan="2">Show Only Open POs <input type="checkbox" name="only_open_pos" id="only_open_pos" <?= $__FLAG['only_open_pos']?'checked':''; ?>></td>
								<td class="main"> Show My POs</td>
								<td class="main"><input type="checkbox" name="my_pos" id="my_pos" <?php if (!empty($_REQUEST['my_pos']) && $_REQUEST['my_pos'] == 'true') echo 'checked="yes"'; ?> /></td>
								<td class="main">Search by Tracking:</td>
								<td class="main">
									<input id="tracking_autocomplete" type="text" size="40"/>
									<div id="tracking_choices" class="autocomplete" style="border: 1px solid rgb(0, 0, 0); display: none; background-color: rgb(255, 255, 255); z-index: 100;"></div>
									<script type="text/javascript">
										new Ajax.Autocompleter('tracking_autocomplete', 'tracking_choices', 'po_list.php', {
											method: 'get',
											minChars: 2,
											paramName: 'search_string',
											parameters: 'action=tracking_search',
											afterUpdateElement: function(input, li) {
												var i = 0;
												var obj = null;
												while (true) {
													i++;
													var objNew = document.getElementById('tabs-'+i);

													// ran out of elems
													if (objNew == null) break;
													else obj = objNew;

													var myClass = obj.getAttribute('class').split('ui-tabs-selected')[1];

													// found it
													if (myClass != null) break;
												}
												var tabData;
												if (obj != null) tabData = obj.childNodes[1].getAttribute('href');
												else tabData = "";
												window.location='po_list.php?tracking_search='+li.id+tabData;
											}
										});
									</script>
								</td>
							</tr>
						</table>
					</div>
					<div id="please_wait" class="please_wait">
						<div class="please_wait_inner"><img src="images/ajax-loader.gif"></div>
					</div>
					<div id="tabs" style="clear: both; width: 1200px;">
						<ul>
							<li id="tabs-0" table_id="my_pos_tab" <?= !empty($_GET['tabs'])&&$_GET['tabs']=='tabs-0'?'class="ui-tabs-selected"':''; ?>><a href="po_list_content.php?action=tabs-5&my_pos=true<?= $append_params; ?>">My POs</a></li>
							<li id="tabs-1" table_id="open_po" <?= !empty($_GET['tabs'])&&$_GET['tabs']=='tabs-1'?'class="ui-tabs-selected"':''; ?>><a href="po_list_content.php?action=tabs-1<?= $append_params; ?>">All Open PO's</a></li>
							<li id="tabs-11" table_id="follow_up" <?= !empty($_GET['tabs'])&&$_GET['tabs']=='tabs-11'?'class="ui-tabs-selected"':''; ?>><a href="po_list_content.php?action=tabs-11<?= $append_params; ?>">Follow Up Req’d</a></li>
							<li id="tabs-2" table_id="open_po_past_due" <?= !empty($_GET['tabs'])&&$_GET['tabs']=='tabs-2'?'class="ui-tabs-selected"':''; ?>><a href="po_list_content.php?action=tabs-2<?= $append_params; ?>">All Open PO's Past Due</a></li>
							<li id="tabs-3" table_id="part_rec_po" <?= !empty($_GET['tabs'])&&$_GET['tabs']=='tabs-3'?'class="ui-tabs-selected"':''; ?>><a href="po_list_content.php?action=tabs-3<?= $append_params; ?>">Partially Rec'd PO's</a></li>
							<li id="tabs-4" table_id="pend_conf_po" <?= !empty($_GET['tabs'])&&$_GET['tabs']=='tabs-4'?'class="ui-tabs-selected"':''; ?>><a href="po_list_content.php?action=tabs-4<?= $append_params; ?>">PO's Pending Confirmation</a></li>
							<li id="tabs-7" table_id="urgent_po" <?= !empty($_GET['tabs'])&&$_GET['tabs']=='tabs-7'?'class="ui-tabs-selected"':''; ?>><a href="po_list_content.php?action=tabs-7<?= $append_params; ?>">Urgent PO's</a></li>
							<li id="tabs-5" table_id="all_po" <?= !empty($_GET['tabs'])&&$_GET['tabs']=='tabs-5'?'class="ui-tabs-selected"':''; ?> <?= $make_active; ?>><?= $make_active_link; ?></li>
							<li id="tabs-8" table_id="prepaid_po" <?= !empty($_GET['tabs'])&&$_GET['tabs']=='tabs-8'?'class="ui-tabs-selected"':''; ?>><a href="po_list_content.php?action=tabs-8<?= $append_params; ?>">Prepaid PO's</a></li>
							<li id="tabs-10" table_id="for_review" style="float: right;" <?= !empty($_GET['tabs'])&&$_GET['tabs']=='tabs-10'?'class="ui-tabs-selected"':''; ?>><a style="background-color: #ff9999;" href="po_list_content.php?action=tabs-10<?= $append_params; ?>">For Review</a></li>
							<li id="tabs-9" table_id="open_sessions" style="float: right;" <?= !empty($_GET['tabs'])&&$_GET['tabs']=='tabs-9'?'class="ui-tabs-selected"':''; ?>><a style="background-color: #ff9999;" href="po_list_content.php?action=tabs-9<?= $append_params; ?>">Open Sessions</a></li>
						</ul>
						<!--
						<div id="tabs-1"></div>
						<div id="tabs-2"></div>
						<div id="tabs-3"></div>
						<div id="tabs-4"></div>
						<div id="tabs-5"></div>
						-->
					</div>
				</div>
				<!-- ----------------------------------------------------------------- -->
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<!-- body_eof //-->
</body>
</html>
