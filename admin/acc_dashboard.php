<?php
require('includes/application_top.php');
require_once('includes/modules/accounting_notes.php');

if (!empty($_REQUEST['search_term'])) {
	$results = ['results' => []];
	$customers = ck_customer2::legacy_search_customers_past_due($_REQUEST['search_term']);
	foreach ($customers as $customer) {
		$c = [];
		$c['result_id'] = $customer['customers_id'];
		$c['field_value'] = $customer['customers_id'];
		$c['result_label'] = $customer['name'];
		$results['results'][] = $c;
	}
	echo json_encode($results);
	exit();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<link rel="stylesheet" type="text/css" href="acc_dashboard.css">
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('a.payment_expand').live('click', function(e) {
				var id = $(this).attr('id');
				$('#payment_' + id).toggle();
				e.preventDefault();
			});

			$('a.order_expand').live('click', function(e) {
				var id = $(this).attr('id');
				if ($('#orders_' + id).css('display') == 'none') {
					$('#orders_' + id).load('acc_dashboard_content.php', 'content=customer_orders&customer_id=' + id + '&sort=order');
				}
				$('#orders_' + id).toggle();
				e.preventDefault();
			});
		});

		function dashboard_content(content, customer_id, sort) {
			if (sort==undefined) {
				sort='name';
			}

			jQuery('#dashboard-content').load('acc_dashboard_content.php', 'content=' + content + '&customer_id=' + customer_id + '&sort=' + sort, function () {
				jQuery('#dashboard-header ul li').removeClass('selected');
				jQuery('#' + content).addClass('selected');
			});
		}

		function get_customer_accounting(customer_id, sort, dir) {
			new Ajax.Updater('orders_'+customer_id,'acc_dashboard_content.php', {
				method: 'get',
				parameters: {content: 'customer_orders',customer_id: customer_id, sort: sort},
				onComplete: function(request) {
					Effect.BlindDown('orders_'+customer_id, { duration: 0.3 });
					$('span_'+customer_id).update('<a href="javascript: void(0)">-</a>');
				}
			});
		}
	</script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onload="dashboard_content('receivables', '0', 'name');">
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
			<td width="100%" valign="top">
				<!------------------------------------------------------------------- -->
				<div style="float:left; margin-left: 20px;"><h2>Accounting Dashboard</h2></div>
				<div style="width:1110px; border:1px solid #333333; margin:20px; clear:both;">
					<div class="acc_title">Summary</div>
					<div class="acc_content_box">
						<div style="float:left; width:180px; height:40px;"><strong><u>Total outstanding</u></strong><br><?= CK\text::monetize(ck_invoice::get_total_ar_outstanding()); ?></div>
						<div style="float:left; height:40px;">
							<strong>Search:</strong>
							<input type="text" id="company_search" name="company_search">
							<input type="button" value="clear" onclick="$('name_search').clear();">
							<script src="//cdnjs.cloudflare.com/ajax/libs/mustache.js/2.1.3/mustache.min.js"></script>
							<script src="/images/static/js/ck-styleset.js"></script>
							<script src="/images/static/js/ck-autocomplete.js?v=3"></script>
							<script>
								var customer_ac = new ck.autocomplete('company_search', '/admin/acc_dashboard.php', {
									autocomplete_action: 'company_search',
									autocomplete_field_name: 'search_term',
									select_result: function(data) {
										location.href = '/admin/acc_customer_invoices.php?content=history&customer_id='+data.result_id;
									},
									force_results_return: true,
								});
							</script>

						</div>
						<div style="clear:left;"></div>
					</div>
				</div>
				<div id="dashboard-header">
					<ul>
						<li id="receivables" class="selected"><a href="javascript: void(0);" onclick="dashboard_content('receivables', '0');">Receivables</a></li>
						<li id="unposted"><a href="javascript: void(0);" onclick="dashboard_content('unposted', '0');">Unposted Payments</a></li>
					</ul>
				</div>

				<div style="width:1110px; border:1px solid #333333; margin-left:20px;float:left" id="dashboard-content"></div>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<!-- body_eof //-->
</body>
<html>
