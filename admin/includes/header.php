<script type="text/javascript" src="/includes/javascript/prototype.js"></script>
<script type="text/javascript" src="/includes/javascript/scriptaculous/scriptaculous.js?load=effects,controls"></script>
<!-- This must go after all other JS frameworks for compatibility -->
<?php if (!empty($GLOBALS['use_jquery_1.8.3'])) { ?>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<?php } else { ?>
<script type="text/javascript" src="/includes/javascript/jquery-1.4.2.min.js"></script>
<?php } ?>
<script type="text/javascript" src="/includes/javascript/jquery-ui-1.8.custom.min.js"></script>
<script type="text/javascript" src="/includes/javascript/jquery.tablesorter.min.js"></script>
<?php /* MMD - removing this as part of D-155 - generating a 404 <script type="text/javascript" src="/includes/javascript/jquery.tablesorter.widgets.js"></script> */ ?>
<script type="text/javascript" src="/includes/javascript/jquery.tablesorter.pager.js"></script>
<script type="text/javascript" src="/admin/includes/javascript/jqModal.js"></script>
<script type="text/javascript" src="/includes/javascript/jquery.qtip-1.0.min.js"></script>
<script type="text/javascript" src="/includes/javascript/chosen/chosen.jquery.min.js"></script>
<script type="text/javascript" src="/admin/includes/general.js?v=5"></script>
<script src="/images/static/js/ck-j-greedy-search.max.js?v=2"></script>
<link rel="stylesheet" type="text/css" href="/admin/css/smoothness/jquery-ui-1.8.custom.css" />
<link type="text/css" href="/admin/css/ck-jquery-ui-changes.css" rel="stylesheet" />
<link type="text/css" href="/admin/css/jquery.autocomplete.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="/admin/includes/stylesheet.css" />
<link rel="stylesheet" type="text/css" href="/admin/css/jqModal.css" />
<link rel="stylesheet" type="text/css" href="/admin/css/tablesorter-blue.css" />
<link rel="stylesheet" type="text/css" href="/includes/javascript/chosen/chosen.min.css" />
<link rel="stylesheet" type="text/css" href="/admin/css/printable.css" media="print" />

<script type="text/javascript">
	jQuery.noConflict();
	function urlencode(str) {
		str = escape(str);
		str = str.replace('+', '%2B');
		str = str.replace('%20', '+');
		str = str.replace('*', '%2A');
		str = str.replace('/', '%2F');
		str = str.replace('@', '%40');
		return str;
	}
</script>

<style>
	.ajax_response_data { margin:0px; }
	.ajax_response_data td { margin:0px; padding:0px 5px; border-width:0px 0px 1px 1px; border-style:solid; border-color:#000; -moz-border-radius:3px;-webkit-border-radius:3px;-khtml-border-radius:3px;border-radius:3px; }
	.ajax_response_data td:first-child { border-left-width:0px; }
</style>

<table border="0" width="100%" height="82" cellspacing="0" cellpadding="0" id="header" style="border-bottom: 2px solid black;">
	<tr>
		<td><img src="//media.cablesandkits.com/pop-the-top4.png" width="250" height="75" alt="CablesAndKits.com - The Network Accessory Superstore" style="margin-left: 20px;" /></td>
		<td width="400">
			<?php if (tep_admin_check_boxes('stats_invoices.php')) { ?>
			<div style="float: right; font-family: arial; font-size: 12px;" id="header_margin_box"></div>
			<script type="text/javascript">
				jQuery.ajax({
					url: "/admin/header_margin.php",
					type: "GET",
					dataType: "html",
					success: function (data) {
						jQuery('#header_margin_box').html(data);
					},
					error: function (xhr, status) {
						//alert("Sorry, there was a problem with the header margin data!");
					},
				});

				jQuery(document).ready(function($) {
					$('body').greedy_search({
						$search_field: $('#header_search_box'),
						$greedy_flag: $('#greedy-search')
					});

					$('#greedy-search').click(function() {
						$.ajax({
							data: { ajax: 1, action: 'set-greedy-search', 'greedy-search': $(this).is(':checked')?1:0 }
						});
						$(this).blur();
					});
				});
			</script>
			<?php } ?>
		</td>
		<td align="right" style="padding-top: 10px;">
			<table border="0" width="500" cellspacing="0" cellpadding="0" align="center">
				<tr>
					<td>
						<table border="0" width="100%" cellspacing="0" cellpadding="0">
							<tr>
								<td valign="bottom" class="main"> <b>Links:</b>&nbsp; <a href="/" class="headerLink" title="Catalog">Catalog</a>&nbsp; |&nbsp; <a href="http://www.ebay.com" class="headerLink" title="Ebay">Ebay</a>&nbsp; |&nbsp; <a href="http://www.paypal.com" class="headerLink" title="Paypal">Paypal</a>&nbsp; |&nbsp; <a href="http://www.ups.com" class="headerLink" title="UPS">UPS</a>&nbsp; |&nbsp; <a href="http://www.fedex.com" class="headerLink" title="Fedex">Fedex</a>&nbsp; |&nbsp; <a href="http://forum" class="headerLink" title="Forum">Forum</a></td>
							</tr>
							<tr>
								<td>&nbsp;</td>
							</tr>
							<tr>
								<td>
									<table>
										<tr>
											<td class="main">
												<b>Search:</b>
												<?php $search_option = '';
												if (in_array($_SESSION['perms']['admin_groups_id'], [7, 29, 18, 20])) $search_option = "SELECTED"; ?>
												<select id="header_search_type">
													<option value="order">Order ID</option>
													<option value="ipn">IPN</option>
													<?php if (tep_admin_check_boxes('po_list.php')) { ?>
													<option value="po_number" <?= $search_option; ?>>PO Number</option>
													<?php } ?>
													<option value="serial">Serial</option>
													<option value="track_number">Track Number</option>
													<option value="invoice">Invoice Number</option>
													<option value="customer_email">Customer Email</option>
												</select>
											</td>
											<td>
												<input id="header_search_box">
												<input type="checkbox" id="greedy-search" <?= !empty($_SESSION['greedy-search'])?'checked':''; ?> title="check this box to capture all keystrokes and put them in the search box">
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
					<td valign="top">
						<a href="<?= FILENAME_LOGOFF ?>" title="Log Off">Log Off</a> |
						<a href="/admin/admin-account-details" style="margin-right: 15px;">Settings</a>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php if ($messageStack->size > 0) {
	echo $messageStack->output();
}

ck_bug_reporter::render(); ?>
