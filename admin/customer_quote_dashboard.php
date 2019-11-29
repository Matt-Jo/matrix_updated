<?php
require('includes/application_top.php');

$customer_quote_id = !empty($_REQUEST['customer_quote_id'])?$_REQUEST['customer_quote_id']:NULL;
$admin_action = !empty($_REQUEST['admin_action'])?strtolower($_REQUEST['admin_action']):NULL;

$customers_id = !empty($_REQUEST['customers_id'])?$_REQUEST['customers_id']:NULL;

switch ($admin_action) {
	case 'delete':
		if (isset($customer_quote_id) && $customer_quote_id != 0) {
			prepared_query::execute('DELETE FROM customer_quote_products WHERE customer_quote_id = ?', [$customer_quote_id]);
			prepared_query::execute('DELETE FROM customer_quote WHERE customer_quote_id = ?', [$customer_quote_id]);
			CK\fn::redirect_and_exit('/admin/customer_quote_dashboard.php');
		}
		break;
	case 'create':
		$quote = ck_quote::create_quote();

		$user = ck_admin::login_instance(ck_admin::CONTEXT_ADMIN);
		if ($user->has_sales_team()) $quote->change_sales_team($user->get_sales_team()['team']->id());

		CK\fn::redirect_and_exit('/admin/customer-quote.php?customer_quote_id='.$quote->id());
		break;
	case 'copy':
		$quote = ck_quote::create_quote_copy($customer_quote_id);

		CK\fn::redirect_and_exit('/admin/customer-quote.php?customer_quote_id='.$quote->id());
		break;
	case 'run report':
	default:
		$status = [0 => 'unsent', 1 => 'sent', 2 => 'viewed', 3 => 'expired', 4 => 'ordered'];

		// grab, sanitize, and default options
		$options = !empty($_REQUEST['options'])?$_REQUEST['options']:[];
		empty($options['results_per_page'])?$options['results_per_page']=50:NULL;
		empty($options['page'])?$options['page']=1:NULL;
		empty($options['admin_id'])?$options['admin_id']=NULL:NULL;
		empty($options['customers_email'])?$options['customers_email']=NULL:NULL;

		$where = [];
		$criteria = [];

		// specifically for the salesperson query, because we might not want to always limit it in the same way
		$sp_where = [];
		$sp_criteria = [];

		if (empty($options['show_expired'])) {
			$where[] = 'cq.expiration_date > date(now())';
			$sp_where[] = 'cq.expiration_date > date(now())';
		}

		if (!empty($options['admin_id'])) {
			$where[] = 'cq.admin_id = ?';
			$criteria[] = $options['admin_id'];
		}

		if (!empty($options['customers_email'])) {
			$where[] = 'cq.customer_email LIKE ?';
			$criteria[] = $options['customers_email'].'%';

			$sp_where[] = 'cq.customer_email LIKE ?';
			$sp_criteria[] = $options['customers_email'].'%';
			$customers_id = NULL;
		}
		elseif (!empty($customers_id)) {
			$where[] = 'cq.customers_id = ?';
			$criteria[] = $customers_id;

			$sp_where[] = 'cq.customers_id = ?';
			$sp_criteria[] = $customers_id;
		}

		$where = !empty($where)?'AND '.implode(' AND ', $where):'';
		$sp_where = !empty($sp_where)?'AND '.implode(' AND ', $sp_where):'';

		$total_quotes = prepared_query::fetch("SELECT COUNT(customer_quote_id) FROM customer_quote cq WHERE true $where", cardinality::SINGLE, $criteria);
		$start_limit = ($options['page']-1) * $options['results_per_page'];
		if ($start_limit > $total_quotes) {
			$start_limit = 0;
			$options['page'] = 1;
		}
		$limit = $start_limit.','.$options['results_per_page'];
		$total_pages = ceil($total_quotes / $options['results_per_page']);

		$sales_persons = prepared_query::fetch("SELECT DISTINCT concat_ws(' ', a.admin_firstname, a.admin_lastname) as admin_name, a.admin_email_address, a.admin_id FROM customer_quote cq JOIN admin a ON cq.admin_id = a.admin_id WHERE true $sp_where ORDER BY a.admin_firstname, a.admin_lastname", cardinality::SET, $sp_criteria);

		// assume a column name with only letters, numbers and underscores, I know we can accept identifiers quoted by `, but in practice we won't need to worry about it
		$sort_by = preg_replace('/[^\w.]/', '_', @$_REQUEST['sort_by']);
		if (empty($sort_by)) $sort_by = 'created';
		$direction = !empty($_REQUEST['direction'][$sort_by])&&$_REQUEST['direction'][$sort_by]=='ASC'?'ASC':'DESC';

		$query = "select cq.*, concat_ws(' ', a.admin_firstname, a.admin_lastname) as admin_name, a.admin_email_address, date(cq.created) as created, SUM(cqp.price*cqp.quantity) as total, (cq.expiration_date < date(now())) as expired FROM customer_quote cq LEFT JOIN admin a ON cq.admin_id = a.admin_id LEFT JOIN customer_quote_products cqp ON cq.customer_quote_id = cqp.customer_quote_id WHERE true $where GROUP BY cq.customer_quote_id ORDER BY $sort_by $direction LIMIT $limit";

		$quotes = prepared_query::fetch($query, cardinality::SET, $criteria);

		break;
}

$showdir = ['customer_quote_id' => 'ASC', 'customer_email' => 'ASC', 'admin_name' => 'ASC', 'status' => 'ASC', 'created' => 'ASC', 'expiration_date' => 'ASC', 'total' => 'ASC', 'order_id' => 'ASC'];
@($_REQUEST['direction'][$sort_by]=='ASC')?$showdir[$sort_by]='DESC':NULL;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
		<title><?= TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
		<table border="0" width="100%" cellspacing="2" cellpadding="2">
			<tr>
				<td width="<?= BOX_WIDTH; ?>" valign="top">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</td>
				<td width="100%" valign="top">
					<style>
						#quote-report { font-family:Verdana, Arial, sans-serif; font-size:10px; }
						#quote-report th, #quote-report td { margin:0px; padding:4px 7px; }
						.report-title { font-size:18px; color:#727272; text-align:left; }
						.report-control {}
						/* specify the id of the containing table to make it more specific than the rule above */
						#quote-report .report-header th { background-color:#aaa; color:#fff; padding:0px; }
						.report-header th a { color:#fff; font-weight:bold; display:block; text-align:center; padding:4px 7px; }
						.report-header th a:link { color:#fff; font-weight:bold; text-decoration:none; }
						.report-header th a:hover { text-decoration:underline; background-color:#aa4; }
						.report-header th a:visited { color:#fff; font-weight:bold; text-decoration:none; }
						.report-header th.sorted { border:1px solid #000; }
						.report-header th.sorted.ASC { background: -webkit-linear-gradient(#33f, #aaf); }
						.report-header th.sorted.DESC { background: -webkit-linear-gradient(#aaf, #33f); }
						.report-row.alt0 td { background-color:#fff; }
						.report-row.alt1 td { background-color:#ddd; }
						.report-row td { max-width:400px; min-width:70px; }
						.report-row td.expired { background-color:#fcc; }
						.report-row:hover td { background-color:#ffc; }
					</style>
					<table border="0" cellspacing="0" cellpadding="0" id="quote-report">
						<thead>
							<tr>
								<th colspan="9" class="report-title">Customer Quote Dashboard</th>
							</tr>
							<tr>
								<td colspan="9" class="report-control">
									<form action="<?= $_SERVER['PHP_SELF']; ?>" method="get" id="run-report">
										Options:
										Page # <input type="text" style="width:35px;" name="options[page]" value="<?= $options['page']; ?>"> of <?= $total_pages; ?> |
										# Per Page <input type="text" style="width:35px;" name="options[results_per_page]" value="<?= $options['results_per_page']; ?>"> |
										Show Expired <input type="checkbox" name="options[show_expired]" <?= empty($options['show_expired'])?'':'checked'; ?>> |
										Sales Person:
										<select name="options[admin_id]" size="1">
											<option value=""><!-- nuthin' --></option>
											<?php foreach ($sales_persons as $sales_person) { ?>
											<option value="<?= $sales_person['admin_id']; ?>" <?= $sales_person['admin_id']==$options['admin_id']?'selected':''; ?>><?= $sales_person['admin_name']; ?></option>
											<?php } ?>
										</select> |
										Customer Email:
										<input type="text" name="options[customers_email]" value="<?= $options['customers_email']; ?>"> |
										<input type="submit" name="admin_action" value="Run Report">
										<input type="hidden" id="sort_by" name="sort_by" value="<?= $sort_by; ?>">
										<input type="hidden" id="sort_dir" name="direction[<?= $sort_by; ?>]" value="<?= $direction; ?>">
										<input type="hidden" name="customers_id" value="<?= $customers_id; ?>">
									</form>
									<a href="/admin/customer_quote_dashboard.php?admin_action=create" style="margin-left:30px;">Create New Quote</a>
								</td>
							</tr>
							<tr class="report-header">
								<th class="<?= $sort_by=='customer_quote_id'?'sorted '.$showdir[$sort_by]:''; ?>"><a href="/admin/customer_quote_dashboard.php?sort_by=customer_quote_id&direction[customer_quote_id]=<?= $showdir['customer_quote_id']; ?>" class="colsort" data-sb="customer_quote_id" data-sd="<?= $showdir['customer_quote_id']; ?>">Quote ID <?= $showdir['customer_quote_id']=='ASC'?'&uarr;':'&darr;'; ?></a></th>
								<th class="<?= $sort_by=='customer_email'?'sorted '.$showdir[$sort_by]:''; ?>"><a href="/admin/customer_quote_dashboard.php?sort_by=customer_email&direction[customer_email]=<?= $showdir['customer_email']; ?>" class="colsort" data-sb="customer_email" data-sd="<?= $showdir['customer_email']; ?>">Sent to Email <?= $showdir['customer_email']=='ASC'?'&uarr;':'&darr;'; ?></a></th>
								<th class="<?= $sort_by=='admin_name'?'sorted '.$showdir[$sort_by]:''; ?>"><a href="/admin/customer_quote_dashboard.php?sort_by=admin_name&direction[admin_name]=<?= $showdir['admin_name']; ?>" class="colsort" data-sb="admin_name" data-sd="<?= $showdir['admin_name']; ?>">Admin <?= $showdir['admin_name']=='ASC'?'&uarr;':'&darr;'; ?></a></th>
								<th class="<?= $sort_by=='status'?'sorted '.$showdir[$sort_by]:''; ?>"><a href="/admin/customer_quote_dashboard.php?sort_by=status&direction[status]=<?= $showdir['status']; ?>" class="colsort" data-sb="status" data-sd="<?= $showdir['status']; ?>">Status <?= $showdir['status']=='ASC'?'&uarr;':'&darr;'; ?></a></th>
								<th class="<?= $sort_by=='created'?'sorted '.$showdir[$sort_by]:''; ?>"><a href="/admin/customer_quote_dashboard.php?sort_by=created&direction[created]=<?= $showdir['created']; ?>" class="colsort" data-sb="created" data-sd="<?= $showdir['created']; ?>">Created <?= $showdir['created']=='ASC'?'&uarr;':'&darr;'; ?></a></th>
								<th class="<?= $sort_by=='expiration_date'?'sorted '.$showdir[$sort_by]:''; ?>"><a href="/admin/customer_quote_dashboard.php?sort_by=expiration_date&direction[expiration_date]=<?= $showdir['expiration_date']; ?>" class="colsort" data-sb="expiration_date" data-sd="<?= $showdir['expiration_date']; ?>">Expiration <?= $showdir['expiration_date']=='ASC'?'&uarr;':'&darr;'; ?></a></th>
								<th class="<?= $sort_by=='total'?'sorted '.$showdir[$sort_by]:''; ?>"><a href="/admin/customer_quote_dashboard.php?sort_by=total&direction[total]=<?= $showdir['total']; ?>" class="colsort" data-sb="total" data-sd="<?= $showdir['total']; ?>">Total <?= $showdir['total']=='ASC'?'&uarr;':'&darr;'; ?></a></th>
								<th class="<?= $sort_by=='order_id'?'sorted '.$showdir[$sort_by]:''; ?>"><a href="/admin/customer_quote_dashboard.php?sort_by=order_id&direction[order_id]=<?= $showdir['order_id']; ?>" class="colsort" data-sb="order_id" data-sd="<?= $showdir['order_id']; ?>">Order Id <?= $showdir['order_id']=='ASC'?'&uarr;':'&darr;'; ?></a></th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($quotes as $idx => $quote) { ?>
							<tr class="report-row alt<?= $idx%2; ?>">
								<td><?= $quote['customer_quote_id']; ?></td>
								<td><?= $quote['customer_email']; ?></td>
								<td><a href="mailto:<?= $quote['admin_email_address']; ?>"><?= $quote['admin_name']; ?></a></td>
								<td><?= $status[$quote['status']]; ?></td>
								<td><?= $quote['created']; ?></td>
								<td class="<?= $quote['expired']?'expired':''; ?>"><?= $quote['expiration_date']; ?></td>
								<td>$<?= number_format($quote['total'], 2); ?></td>
								<td><?php if (!empty($quote['order_id'])) { ?><a href="/admin/orders_new.php?selected_box=orders&page=1&oID=<?= $quote['order_id']; ?>&action=edit"><?= $quote['order_id']; ?></a><?php } ?></td>
								<td class="admin-actions">
									<?php if ($quote['status'] != 4) { ?>
									<a href="/admin/customer-quote.php?customer_quote_id=<?= $quote['customer_quote_id']; ?>">Edit</a> |
									<a href="/admin/customer_quote_dashboard.php?admin_action=delete&customer_quote_id=<?= $quote['customer_quote_id']; ?>">Delete</a> |
									<?php } ?>
									<a href="/admin/customer_quote_dashboard.php?admin_action=copy&customer_quote_id=<?= $quote['customer_quote_id']; ?>">Copy</a> |
									<a href="/custom_quote.php?key=<?= $quote['url_hash']; ?>" target="_blank">View Cart</a>
									<br>
									<a href="/quote.php?customer_quote_id=<?= $quote['customer_quote_id']; ?>" target="_blank">Show Products / Resend Email</a>
								</td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
					<script>
						jQuery('.colsort').click(function(e) {
							e.preventDefault();

							jQuery('#sort_by').val(jQuery(this).attr('data-sb'));
							jQuery('#sort_dir').val(jQuery(this).attr('data-sd'));

							jQuery('#run-report').submit();
						});
					</script>
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
</html>
