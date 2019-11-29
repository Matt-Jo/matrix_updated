<?php require_once('includes/application_top.php');
if (empty($_GET['start_date'])) {
	$d = strtotime('now - 2 month');
	$_GET['start_date'] = date('Y-m-d', $d);
}

if (empty($_GET['end_date'])) {
	$d = strtotime('now');
	$_GET['end_date'] = date('Y-m-d', $d);
}


function tab($num, $title) {
	$append_vendor = '&vendor_search='.@$_GET['vendor_search'].'&start_date='.@$_GET['start_date'].'&end_date='.@$_GET['end_date'];
	?>
	<li id="tabs-<?=$num?>" table_id="open_po" <?php if (@$_GET['tabs'] == "tabs-{$num}") { echo 'class="ui-tabs-selected"';} ?>>
		<a href="payables_report_content.php?action=tabs-<?=$num?><?=$append_vendor?>"><?=$title?></a>
	</li>
<?php }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
		<script language="javascript" src="includes/menu.js"></script>
		<script language="javascript" src="includes/general.js"></script>
		<style>
			.main {font-size:10px;}
		</style>
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<!-- header //-->
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
jQuery(document).ready(function() {
	jQuery('#date_search_end').datepicker({dateFormat: 'yy-mm-dd'});
	jQuery('#date_search_start').datepicker({dateFormat: 'yy-mm-dd'});
	/* Date Search */
	jQuery("#date_search_start").keypress(function(e) {
		if (e.which == 13) {
			jQuery("#date_search_button").trigger('click');
		}
	});

	jQuery("#date_search_end").keypress(function(e) {
		if (e.which == 13) {
			jQuery("#date_search_button").trigger('click');
		}
	});

	jQuery("#date_search_button").click(function(ev) {
		var tabs = jQuery("#tabs .ui-state-active").attr("id");
		var table_id = jQuery("#tabs .ui-state-active").attr("table_id");
		var date_start = jQuery('#date_search_start').val();
		var date_end = jQuery('#date_search_end').val();
		window.location='payables_report.php?start_date='+date_start+'&end_date='+date_end;

		/*
		jQuery.ajax({
			type: "GET",
			url: "payables_report_content.php",
			data:{
				action: tabs,
				start_date: date_start,
				end_date: date_end
			},
			success: function(html) {
				jQuery("#"+table_id+'_'+tabs).replaceWith(html);
			}
		});
		*/
	});

	/* PO Search */
	jQuery("#po_search").keypress(function(e) {
		if (e.which == 13) {
			jQuery("#po_search_button").trigger('click');
		}
	});

	jQuery("#po_search_button").click(function(ev) {
		var tabs = jQuery("#tabs .ui-state-active").attr("id");
		var table_id = jQuery("#tabs .ui-state-active").attr("table_id");
		var po_num = jQuery("#po_search").val();
		jQuery.ajax({
			type: "GET",
			url: "payables_report_content.php",
			data:{
				po_search: po_num,
				action: tabs
			},
			success: function(html) {
				jQuery("#"+table_id+'_'+tabs).replaceWith(html);
			}
		});
	});

	/* IPN Search */
	jQuery("#ipn_search").keypress(function(e) {
		if (e.which == 13) {
			jQuery("#ipn_search_button").trigger('click');
		}
	});

	jQuery("#ipn_search_button").click(function(ev) {
		var tabs = jQuery("#tabs .ui-state-active").attr("id");
		var table_id = jQuery("#tabs .ui-state-active").attr("table_id");
		var ipn_num = jQuery("#ipn_search").val();
		jQuery.ajax({
			type: "GET",
			url: "payables_report_content.php",
			data:{
				ipn_search: ipn_num,
				action: tabs
			},
			success: function(html) {
				jQuery("#"+table_id+'_'+tabs).replaceWith(html);
			}
		});
	});

	/* Reset */
	jQuery("#show_all_button").click(function(ev) {
		var tabs = jQuery("#tabs .ui-state-active").attr("id");
		var table_id = jQuery("#tabs .ui-state-active").attr("table_id");
		window.location.href="payables_report.php?tabs="+tabs;
	});

	jQuery("#please_wait").bind("ajaxSend", function() {
		jQuery(this).show();
	}).bind("ajaxComplete", function() {
		jQuery(this).hide();
	});

	jQuery("#tabs").tabs();
	jQuery("#open_po").tablesorter().tablesorterPager({container: jQuery("#pager-tabs-1")});

});
</script>
<div style="width:90%; border:0px solid #333333; margin:5px; height:800px; padding:10px;">
	<div class="pageHeading" style="padding-bottom:20px;">
		PO List
	</div>
	<div>
		<table cellspacing="5px" cellpadding="5px" border="0">
			<tr>
				<td class="main" colspan="2">
					<input type="button" onclick="window.location='po_editor.php?action=new';" value="New Purchase Order"/>
				</td>
				<td class="main">Search by Date</td>
				<td class="main" colspan="3">
			<input type="text" id="date_search_start" name="date_search_start" value="<?=$_GET['start_date']?>" />
			<input type="text" id="date_search_end" name="date_search_end" value="<?=$_GET['end_date']?>" />
						<input type="button" id="date_search_button" value="Go"/>
				</td>
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
						<div id="customer_choices" class="autocomplete" style="border: 1px solid rgb(0, 0, 0); display: none; background-color: rgb(255, 255, 255); z-index: 100;"> </div>
							<script type="text/javascript">
								new Ajax.Autocompleter('customer_autocomplete', "customer_choices", "po_list.php", {
									method: 'get',
									minChars: 3,
									paramName: 'search_string',
									parameters: 'action=customer_search',
									afterUpdateElement: function(input, li) {
										var i = 0;
										var obj = null;
										while (true) {
											i++;
											var objNew = document.getElementById('tabs-'+i);
											if (objNew == null) {
												// ran out of elems
												break;
											}
											else {
												obj = objNew;
											}
											var myClass = obj.getAttribute('class').split('ui-tabs-selected')[1]
											if (myClass != null) {
												// found it
												break;
											}
										}
										var tabData;
										if (obj != null) {
											tabData = obj.childNodes[1].getAttribute('href');
										}
										else {
											tabData = "";
										}
										window.location='payables_report.php?vendor_search='+li.id+tabData;
									}
								});
							</script>
				</td>
				<td>
					<input type="button" id="show_all_button" value="Show All"/>
			</tr>
		</table>
	</div>
	<div id="please_wait" class="please_wait">
		<div class="please_wait_inner">
		<img src="images/ajax-loader.gif"></div>
	</div>
	<div id="tabs" style="clear: both;">
		<ul>
		<?php tab(3, "Received/Unpaid POs"); ?>
		<?php tab(2, "Received/Paid POs"); ?>
		<?php tab(1, "All Received POs"); ?>
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
