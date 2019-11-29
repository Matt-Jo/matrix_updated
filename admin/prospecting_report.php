<?php
ini_set('memory_limit', '256M');
require('includes/application_top.php');

function format_phone_number($dirty_number) {
	$parts = array('country' => NULL, 'area' => NULL, 'pre' => NULL, 'line' => NULL, 'ext' => NULL);
	$workspace = preg_replace('/[^\d+]/', '', $dirty_number);
	if (empty($workspace) || $workspace[0] == '+') return $dirty_number; // there's nothing else we can productively do with this, without making this way more complicated than I'm aiming for

	if ($workspace[0] == '1') {
		$parts['country'] = 1;
		$workspace = substr($workspace, 1);
	}

	$parts['area'] = substr($workspace, 0, 3);
	$parts['pre'] = substr($workspace, 3, 3);
	$parts['line'] = substr($workspace, 6, 4);
	$parts['ext'] = substr($workspace, 10);

	$clean_number = (!empty($parts['country'])?'+'.$parts['country'].' ':'').'('.$parts['area'].') '.$parts['pre'].'-'.$parts['line'].(!empty($parts['ext'])?' x'.$parts['ext']:'');

	return $clean_number;
}

$sugar = mysqli_init();
@$sugar->real_connect(SUGAR_DB, SUGAR_DB_USERNAME, SUGAR_DB_PASSWORD, SUGAR_DATABASE);
$sugar_good = TRUE;
if ($sugar->connect_error) {
	error_log('Error: '.$sugar->connect_error);
	$sugar_good = FALSE;
}
elseif ($last_sugar_contact_result = $sugar->query('SELECT customer_id_c as customers_id, dolc_c as last_contact_date FROM accounts_cstm WHERE TO_DAYS(dolc_c) >= TO_DAYS(NOW()) - 31')) {
	while ($last_contact = $last_sugar_contact_result->fetch_assoc()) {
		prepared_query::execute('UPDATE customers SET last_contact_date = ? WHERE customers_id = ? AND (last_contact_date IS NULL OR last_contact_date < ?)', array($last_contact['last_contact_date'], $last_contact['customers_id'], $last_contact['last_contact_date']));
	}
}

if (!empty($_POST['action'])) {
	$result = array('status' => 0);
	switch ($_POST['action']) {
		case 'claim':
			$customers_id = $_POST['claim_id'];

			$result['customers_id'] = $customers_id;

			if (!($customer = prepared_query::fetch('SELECT customers_id FROM customers WHERE customers_id = ? AND TO_DAYS(last_contact_date) >= TO_DAYS(NOW()) - 30', cardinality::SINGLE, array($customers_id)))) {
				prepared_query::execute('UPDATE customers SET last_contact_date = NOW() WHERE customers_id = ?', array($customers_id));
				if (!empty($sugar_good)) {
					$stmt = $sugar->prepare('UPDATE accounts_cstm SET dolc_c = UTC_TIMESTAMP() WHERE customer_id_c = ?');
					$stmt->bind_param('s', $customers_id);
					$stmt->execute();
				}

				$result['status'] = 1;
			}
			else {
				$result['status'] = 2;
			}
			break;
		default;
	}

	echo json_encode($result);
	exit();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
		<title><?= TITLE; ?></title>
		<script type="text/javascript" src="serials/serials.js"></script>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<link rel="stylesheet" type="text/css" href="serials/serials.css">
		<link rel="stylesheet" type="text/css" href="/includes/javascript/scriptaculous/scriptaculous.css">
		<link rel="stylesheet" type="text/css" href="css/shipaddrtrack.css">
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<!-- header //-->
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
		<!-- header_eof //-->
		<!-- body //-->
		<table border="0" width="100%" cellspacing="2" cellpadding="2">
			<tr>
				<td width="<?= BOX_WIDTH; ?>" valign="top">
					<table border="0" width="<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
						<!-- left_navigation //-->
						<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
						<!-- left_navigation_eof //-->
					</table>
				</td>
				<!-- body_text //-->
				<td width="100%" valign="top">
					<style>
						.row-0 td, .row-1 td { padding:3px; }
						.row-1 { background-color:#eee; }
						.irrelevant-lv, .irrelevant-ct { display:none; }
						.unclaimed:hover { background-color:#ffc; }
						.claiming { background-color:#fcc; }
						.claimed td { background-color:#cfc; padding:7px 3px; }
						.unavailable td { background-color:#555; color:#fff; padding:7px 3px; }
						a.sort-field { display:block; width:70px; }
						.sort-field.none:hover::after { content: " \21e9"; font-size:1.4em; }
						.sort-field.asc::after { content: " \21e7"; font-size:1.4em; }
						.sort-field.asc:hover::after { content: " \21e9"; font-size:1.4em; }
						.sort-field.desc::after { content: " \21e9"; font-size:1.4em; }
						.sort-field.desc:hover::after { content: " \21e7"; font-size:1.4em; }
					</style>
					Filters:<br>
					Lifetime Order &gt; <input type="text" id="lifetime_gt"> |
					Customer Type(s)
					<select id="customer_types" size="1">
						<option value="111">ALL</option>
						<option value="010">Business</option>
						<option value="100">Personal</option>
						<option value="001">Unknown</option>
						<option value="011">Business/Unknown</option>
						<option value="101">Personal/Unknown</option>
					</select>
					<br><br>
					<table border="0" width="100%" cellspacing="0" cellpadding="0" style="border-left: 1px solid black; border-bottom: 1px solid black; border-right: 1px solid black;">
						<thead>
							<tr class="dataTableHeadingRow">
								<td class="dataTableHeadingContent">Order No.</td>
								<td class="dataTableHeadingContent"><a href="#" class="sort-field none" data-sort="ov">Order $</a></td>
								<td class="dataTableHeadingContent">Customer</td>
								<td class="dataTableHeadingContent">Company</td>
								<td class="dataTableHeadingContent">Business/<br>Personal</td>
								<td class="dataTableHeadingContent"><a href="#" class="sort-field none" data-sort="lv">Lifetime $</a></td>
								<td class="dataTableHeadingContent">Orders</td>
								<td class="dataTableHeadingContent">Products</td>
								<td class="dataTableHeadingContent">State</td>
								<td class="dataTableHeadingContent">Country</td>
								<td class="dataTableHeadingContent">Email</td>
								<td class="dataTableHeadingContent">Phone</td>
								<td class="dataTableHeadingContent">Most Recent Invoice</td>
								<td class="dataTableHeadingContent">Last Contact</td>
								<td class="dataTableHeadingContent">Claim</td>
							</tr>
						</thead>
						<tbody id="prospect-list">
							<?php // STRAIGHT_JOIN forces the MySQL query planner to join the tables in the order in which they are in the query, which in this case is much faster than the alternative
							$customers = prepared_query::fetch('SELECT STRAIGHT_JOIN o.orders_id, ot.value as order_total, o.customers_id, c.customer_type, o.customers_name, o.customers_company, o.customers_state, o.customers_country, o.customers_email_address, o.customers_telephone, ai.inv_date, c.last_contact_date FROM acc_invoices ai JOIN orders o ON ai.inv_order_id = o.orders_id JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = \'ot_total\' JOIN customers c ON o.customers_id = c.customers_id AND c.account_manager_id = 0 LEFT JOIN acc_invoices ai0 ON ai.inv_order_id = ai0.inv_order_id AND ai0.invoice_id < ai.invoice_id LEFT JOIN acc_invoices ai1 ON ai.customer_id = ai1.customer_id AND ai.inv_order_id != ai1.inv_order_id AND ai1.rma_id IS NULL AND ai1.invoice_id > ai.invoice_id LEFT JOIN orders o1 ON ai.customer_id = o1.customers_id AND ai.inv_order_id < o1.orders_id AND o1.orders_status IN (2, 5, 7, 8, 10, 11) WHERE TO_DAYS(ai.inv_date) <= TO_DAYS(NOW()) - 7 AND TO_DAYS(ai.inv_date) >= TO_DAYS(NOW()) - 14 AND (c.last_contact_date IS NULL OR TO_DAYS(c.last_contact_date) < TO_DAYS(NOW()) - 30) AND c.customer_type = 0 AND ai0.invoice_id IS NULL AND ai1.invoice_id IS NULL AND o1.orders_id IS NULL ORDER BY DATE(ai.inv_date) DESC', cardinality::SET);

							if (!empty($customers)) {
								$ctotals = prepared_query::fetch("SELECT o.customers_id, COUNT(o.orders_id) as lifetime_orders, SUM(ot.value) as lifetime_total FROM orders o JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_total' WHERE o.orders_status IN (2, 3, 5, 7, 8, 10, 11) GROUP BY o.customers_id", cardinality::SET);
								$customer_lifetime = array();
								foreach ($ctotals as $total) {
									$customer_lifetime[$total['customers_id']] = $total;
								}
								unset($ctotals);

								foreach ($customers as $idx => $customer) { ?>
							<tr class="row-<?= $idx%2; ?> prospect unclaimed" id="customer-<?= $customer['customers_id']; ?>-row" data-ov="<?= $customer['order_total']; ?>" data-lv="<?= $customer_lifetime[$customer['customers_id']]['lifetime_total']; ?>" data-ct="">
								<td class="dataTableContent"><a href="/admin/orders_new.php?selected_box=orders&oID=<?= $customer['orders_id']; ?>&action=edit" target="_blank"><?= $customer['orders_id']; ?></a></td>
								<td class="dataTableContent">$<?= number_format($customer['order_total'], 2); ?></td>
								<td class="dataTableContent"><a href="/admin/customers_detail.php?selected_box=customers&customers_id=<?= $customer['customers_id']; ?>" target="_blank"><?= $customer['customers_name']; ?></a></td>
								<td class="dataTableContent"><?= $customer['customers_company']; ?></td>
								<td class="dataTableContent"></td>
								<td class="dataTableContent">$<?= number_format($customer_lifetime[$customer['customers_id']]['lifetime_total'], 2); ?></td>
								<td class="dataTableContent"><a href="/admin/orders_new.php?customers_id=<?= $customer['customers_id']; ?>" target="_blank"><?= $customer_lifetime[$customer['customers_id']]['lifetime_orders']; ?></a></td>
								<td class="dataTableContent"><a href="/admin/advanced_search.php?customer_id=<?= $customer['customers_id']; ?>" target="_blank">products</a></td>
								<td class="dataTableContent"><?= $customer['customers_state']; ?></td>
								<td class="dataTableContent"><?= $customer['customers_country']; ?></td>
								<td class="dataTableContent"><a href="mailto:<?= $customer['customers_email_address']; ?>"><?= $customer['customers_email_address']; ?></a></td>
								<td class="dataTableContent"><?= format_phone_number($customer['customers_telephone']); ?></td>
								<td class="dataTableContent"><?php $invdate = new DateTime($customer['inv_date']); echo $invdate->format('m/d/Y'); ?></td>
								<td class="dataTableContent"><?php if (!empty($customer['last_contact_date'])) { $contdate = new DateTime($customer['last_contact_date']); echo $contdate->format('m/d/Y'); } ?></td>
								<td class="dataTableContent" id="customer-<?= $customer['customers_id']; ?>-cell"><input class="claim-customer" id="customer-<?= $customer['customers_id']; ?>" type="button" name="claim[<?= $customer['customers_id']; ?>]" value="CLAIM"></td>
							</tr>
								<?php }
							} ?>
						</tbody>
					</table>
					<script>
						function recolor_rows() {
							var ctr = 0;
							jQuery('.prospect').each(function() {
								if (jQuery(this).hasClass('irrelevant-lv') || jQuery(this).hasClass('irrelevant-ct')) return;
								jQuery(this).addClass('row-'+(ctr%2)).removeClass('row-'+((++ctr)%2));
							});
						}

						jQuery('.sort-field').click(function(e) {
							e.preventDefault();

							var sort_field = jQuery(this).attr('data-sort');

							var $prospects = jQuery('.prospect').get();

							if (jQuery(this).hasClass('none') || jQuery(this).hasClass('asc')) {
								jQuery(this).removeClass('none').removeClass('asc').addClass('desc');

								$prospects.sort(function(a, b) {
									var alv = parseFloat(jQuery(a).attr('data-'+sort_field));
									var blv = parseFloat(jQuery(b).attr('data-'+sort_field));
									return alv>blv?-1:(alv<blv?1:0);
								});
							}
							else if (jQuery(this).hasClass('desc')) {
								jQuery(this).removeClass('desc').addClass('asc');

								$prospects.sort(function(a, b) {
									var alv = parseFloat(jQuery(a).attr('data-'+sort_field));
									var blv = parseFloat(jQuery(b).attr('data-'+sort_field));
									return alv>blv?1:(alv<blv?-1:0);
								});
							}

							jQuery.each($prospects, function(idx, itm) { jQuery('#prospect-list').append(itm); });

							recolor_rows();

							return false;
						});

						jQuery('#lifetime_gt').keyup(function() {
							var limit = parseFloat(jQuery(this).val());
							if (isNaN(limit)) limit = 0;

							jQuery('.prospect').each(function() {
								var lv = parseFloat(jQuery(this).attr('data-lv'));
								if (lv >= limit) jQuery(this).removeClass('irrelevant-lv');
								else jQuery(this).addClass('irrelevant-lv');
							});

							recolor_rows();
						});
						jQuery('#customer_types').change(function() {
							var limit = parseInt(jQuery(this).val());

							var opts = { Personal: 100, Business: 010, Unknown: 001 };

							jQuery('.prospect').each(function() {
								if (limit & opts[jQuery(this).attr('data-ct')]) jQuery(this).removeClass('irrelevant-ct');
								else jQuery(this).addClass('irrelevant-ct');
							});

							recolor_rows();
						});

						jQuery('.claim-customer').click(function() {
							var id = jQuery(this).attr('id').split('-')[1];

							jQuery('#customer-'+id+'-row').removeClass('unclaimed').addClass('claiming');

							jQuery.ajax({
								url: '/admin/prospecting_report.php',
								type: 'POST',
								dataType: 'json',
								data: { action: 'claim', claim_id: id },
								timeout: 10000,
								success: function(data, textStatus, jqXHR) {
									if (data == null) return;

									if (data.status == 1) {
										jQuery('#customer-'+data.customers_id+'-row').removeClass('claiming').addClass('claimed');
										jQuery('#customer-'+data.customers_id+'-cell').html('[CLAIMED]');
									}
									else if (data.status == 2) {
										jQuery('#customer-'+data.customers_id+'-row').removeClass('claiming').addClass('unavailable');
										jQuery('#customer-'+data.customers_id+'-cell').html('[UNAVAILABLE]');
										alert('This customer has just been claimed by somebody else.');
									}
									else {
										jQuery('#customer-'+data.customers_id+'-row').removeClass('claiming').addClass('unclaimed');
										alert('There was some unknown error claiming this customer, please try again.');
									}
								},
								error: function(jqXHR, textStatus, errorThrown) {
									jQuery('#customer-'+id+'-row').removeClass('claiming').addClass('unclaimed');
									alert('There was some unknown error claiming this customer, please try again.');
								}
							});
						});
					</script>
				</td>
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
</html>
