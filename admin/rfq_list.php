<?php
require('includes/application_top.php');

// this page shouldn't see an inordinate amount of traffic, we'll just do a check for expired RFQs right here at the top of every load
prepared_query::execute('UPDATE ck_rfqs SET active = 0 WHERE expiration_date IS NOT NULL AND DATE(NOW()) > expiration_date');

$status = !empty($_GET['status'])?$_GET['status']:'OPEN';

if (!empty($_REQUEST['action'])) {
	if ($_REQUEST['action'] == 'delete_rfq') {
		$rfq_id = $_GET['rfq_id'];
		prepared_query::execute('delete from ck_rfq_response_products where rfq_id = :rfq_id', [':rfq_id' => $rfq_id]);
		prepared_query::execute('delete from ck_rfq_responses where rfq_id = :rfq_id', [':rfq_id' => $rfq_id]);
		prepared_query::execute('delete from ck_rfq_products where rfq_id = :rfq_id', [':rfq_id' => $rfq_id]);
		prepared_query::execute('delete from ck_rfqs where rfq_id = :rfq_id', [':rfq_id' => $rfq_id]);
		header('Location: /admin/rfq_list.php');
	}
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
						.row-0.expired { background-color:#fee; }
						.row-1.expired { background-color:#edd; }
						a.sort-field { display:block; width:70px; }
						.sort-field.none:hover::after { content: " \21e9"; font-size:1.4em; }
						.sort-field.asc::after { content: " \21e7"; font-size:1.4em; }
						.sort-field.asc:hover::after { content: " \21e9"; font-size:1.4em; }
						.sort-field.desc::after { content: " \21e9"; font-size:1.4em; }
						.sort-field.desc:hover::after { content: " \21e7"; font-size:1.4em; }
					</style>
					<a href="/admin/rfq_detail.php?action=blank">Create New RFQ</a>
					<form method="get" action="/admin/rfq_list.php">
						<select name="status">
							<option value="ALL" <?php if ($status == 'ALL') { ?>selected<?php } ?>>All</option>
							<option value="OPEN" <?php if ($status == 'OPEN') { ?>selected<?php } ?>>Open</option>
							<option value="EXPIRED" <?php if ($status == 'EXPIRED') { ?>selected<?php } ?>>Expired</option>
						</select>
						<input type="submit" value="Filter">
					</form>
					<table border="0" width="100%" cellspacing="0" cellpadding="0" style="border-left: 1px solid black; border-bottom: 1px solid black; border-right: 1px solid black;" class="tablesorter" id="rfqs">
						<thead>
							<tr class="dataTableHeadingRow">
								<th class="dataTableHeadingContent">Date Published</th>
								<th class="dataTableHeadingContent">Nickname</th>
								<th class="dataTableHeadingContent">Subject</th>
								<th class="dataTableHeadingContent">User</th>
								<th class="dataTableHeadingContent">Expiration Date</th>
								<th class="dataTableHeadingContent">Actions</th>
								<th class="dataTableHeadingContent">Responses</th>
								<th class="dataTableHeadingContent">Coverage</th>
								<th class="dataTableHeadingContent">View Responses</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$filter = '';
							if ($status == 'OPEN') {
								$filter = ' and r.active = 1 ';
							}
							else if ($status == 'EXPIRED') {
								$filter = ' and r.active = 0 ';
							}

							$rfqs = prepared_query::fetch('SELECT r.rfq_id, r.nickname, r.subject_line, r.admin_id, a.admin_email_address, a.admin_firstname, a.admin_lastname, r.published_date, r.expiration_date, r.active, r.created_date, IFNULL(rr.reps, 0) as responses FROM ck_rfqs r JOIN admin a ON r.admin_id = a.admin_id LEFT JOIN (SELECT rfq_id, COUNT(DISTINCT rfq_response_id) as reps FROM ck_rfq_response_products GROUP BY rfq_id) rr ON r.rfq_id = rr.rfq_id where 1 '.$filter.' ORDER BY r.active DESC, r.created_date DESC', cardinality::SET);

							if (!empty($rfqs)) {
								foreach ($rfqs as $idx => $rfq) { ?>
							<tr class="row-<?= $idx%2; ?> <?= !$rfq['active']?'expired':''; ?>">
								<td class="dataTableContent"><?php if (!empty($rfq['published_date'])) { $pub = new DateTime($rfq['published_date']); echo $pub->format('m/d/Y'); } ?></td>
								<td class="dataTableContent"><?= $rfq['nickname']; ?></td>
								<td class="dataTableContent"><?= $rfq['subject_line']; ?></td>
								<td class="dataTableContent"><?= $rfq['admin_firstname'].' '.$rfq['admin_lastname']; ?></td>
								<td class="dataTableContent"><?php if (!empty($rfq['expiration_date'])) { $exp = new DateTime($rfq['expiration_date']); echo $exp->format('m/d/Y'); } ?></td>
								<td class="dataTableContent">
									<a href="/admin/rfq_detail.php?rfq_id=<?= $rfq['rfq_id']; ?>&action=edit">[EDIT]</a>
									<?php if (!empty($rfq['active'])) { ?>
									<a href="/admin/rfq_detail.php?rfq_id=<?= $rfq['rfq_id']; ?>&action=update&submit=Force+Expire&skip_process=1" data-action="expire" data-rfqid="<?= $rfq['rfq_id']; ?>" class="confirm">[EXPIRE]</a>
									<?php } ?>
									<a href="/admin/rfq_detail.php?rfq_id=<?= $rfq['rfq_id']; ?>&action=template" data-action="create a new request from template" data-rfqid="<?= $rfq['rfq_id']; ?>" class="confirm">[TEMPLATE]</a>
									<a href="javascript:void(0);" onclick="deleteRFQ(<?= $rfq['rfq_id']; ?>)">[DELETE]</a>
								</td>
								<td class="dataTableContent"><?= $rfq['responses']; ?></td>
								<td class="dataTableContent">
									<?php $coverage = prepared_query::fetch('SELECT rp.stock_id, rp.quantity, IFNULL(SUM(rrp.quantity), 0) as total_coverage FROM ck_rfq_products rp LEFT JOIN ck_rfq_response_products rrp ON rp.rfq_id = rrp.rfq_id AND rp.stock_id = rrp.stock_id WHERE rp.rfq_id = ? GROUP BY rp.stock_id, rp.quantity', cardinality::SET, array($rfq['rfq_id']));
									$items = 0;
									$covered_items = 0;
									foreach ($coverage as $ipn) {
										$items++;
										if ($ipn['total_coverage'] > 0 && $ipn['quantity'] <= $ipn['total_coverage']) $covered_items++;
									}
									echo $covered_items.'/'.$items; ?>
								</td>
								<td class="dataTableContent"><a href="/admin/rfq_detail.php?rfq_id=<?= $rfq['rfq_id']; ?>&action=results" target="_blank">[VIEW]</a></td>
							</tr>
								<?php }
							} ?>
						</tbody>
					</table>
				</td>
			</tr>
		</table>
		<script>
			function deleteRFQ(rfqId) {
				var confirmMsg = "Are you sure you want to delete this RFQ and any responses it may have?";
				if (confirm(confirmMsg)) {
					location.href="/admin/rfq_list.php?action=delete_rfq&rfq_id=" + rfqId;
				}
			}
			jQuery('#rfqs').tablesorter({
					theme: 'blue',
					widgets: ['zebra']
			});
			jQuery('.confirm').click(function(e) {
				if (!confirm('Are you sure you want to '+jQuery(this).attr('data-action')+' RFQ ID: '+jQuery(this).attr('data-rfqid')+'?')) {
					e.preventDefault();
				}
			});
		</script>
		<!-- body_eof //-->
	</body>
</html>
