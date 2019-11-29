<?php
require('includes/application_top.php');

ini_set('memory_limit', '256M');

if ($__FLAG['ajax']) {
	$action = !empty($_POST['action'])?$_POST['action']:NULL;

	if (!empty($action)) {
		$success = TRUE;

		if (empty($_POST['content_review_id'])) $success = FALSE;
		$content_review = new ck_content_review($_POST['content_review_id']);
		if (!$content_review->found()) $success = FALSE;

		if ($success) {
			switch ($action) {
				case 'update_review':
					if (empty($_POST['status'])) $success = FALSE;
					else {
						try {
							$content_review->update_status($_POST['status']);
						}
						catch (Exception $e) {
							throw $e;
							$success = FALSE;
						}
					}
					break;
				case 'update_reason':
					if (empty($_POST['reason_id'])) $success = FALSE;
					else {
						try {
							$content_review->update_reason($_POST['reason_id']);
						}
						catch (Exception $e) {
							throw $e;
							$success = FALSE;
						}
					}
					break;
				default:
					break;
			}
		}
		echo $success?'SUCCESS':'ERROR';
		die();
	}
}

$action = !empty($_GET['action'])?$_GET['action']:NULL;

$page_limit = 100;
$page = !empty($_GET['page'])?$_GET['page']:1;

// zero based indices
$start_idx = ($page - 1) * $page_limit;
$end_idx = ($page * $page_limit) - 1;

$total_results = 0;
$total_pages = 0;

$lookup_status = NULL;
if (!isset($_GET['status'])) $lookup_status = 1;
elseif ($_GET['status'] != 'ALL') $lookup_status = (int) $_GET['status'];

$lookup_dropship = NULL;
if (!isset($_GET['drop_ship'])) $lookup_dropship = 0;
elseif ($_GET['drop_ship'] != 'ALL') $lookup_dropship = (int) $_GET['drop_ship'];

$lookup_instock = NULL;
if (empty($action) || $__FLAG['in_stock']) $lookup_instock = 1;

$lookup_discontinued = NULL;
if (empty($action) || $__FLAG['discontinued']) $lookup_discontinued = 1;

switch ($action) {
	case 'lookup':
		$lookup = [];
		if (!is_null($lookup_status)) $lookup[':status'] = $lookup_status;
		if (!is_null($lookup_dropship)) $lookup[':drop_ship'] = $lookup_dropship;
		if (!is_null($lookup_instock)) $lookup[':in_stock'] = $lookup_instock;
		if (!is_null($lookup_discontinued)) $lookup[':discontinued'] = $lookup_discontinued;

		if (!empty($_GET['reason_id']) && $_GET['reason_id'] != 'ALL') $lookup[':reason_id'] = $_GET['reason_id'];
		if (!empty($_GET['categories_id']) && $_GET['categories_id'] != 'ALL') $lookup[':categories_id'] = $_GET['categories_id'];

		if (!empty($_GET['ipn'])) $lookup[':ipn'] = '%'.$_GET['ipn'].'%';

		$content_reviews = ck_content_review::get_content_reviews_by_field_lookup($lookup);

		break;
	default:
		$lookup = [];
		if (!is_null($lookup_status)) $lookup[':status'] = $lookup_status;
		if (!is_null($lookup_dropship)) $lookup[':drop_ship'] = $lookup_dropship;
		if (!is_null($lookup_instock)) $lookup[':in_stock'] = $lookup_instock;
		if (!is_null($lookup_discontinued)) $lookup[':discontinued'] = $lookup_discontinued;

		$content_reviews = ck_content_review::get_content_reviews_by_field_lookup($lookup);
		break;
}

if (!empty($_GET['submit-action']) && $_GET['submit-action'] == 'Export') {
	$workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	$worksheet = $workbook->getSheet(0);
	$worksheet->setTitle('Content Reviews');

	$worksheet->getCell('A1')->setValue('IPN');
	$worksheet->getCell('B1')->setValue('Bin(s)');
	$worksheet->getCell('C1')->setValue('Reason');

	foreach ($content_reviews as $idx => $content_review) {
		$ipn = $content_review->get_ipn();

		$bins = [];
		if (!empty($ipn)) {
			if ($ipn->is('serialized')) {
				if ($ipn->has_serials()) {
					$serials = array_merge($ipn->get_serials(2), $ipn->get_serials(3), $ipn->get_serials(6));
					foreach ($serials as $serial) {
						if (!empty($serial->get_current_history()['bin_location'])) $bins[] = $serial->get_current_history()['bin_location'];
					}
				}
			}
			else {
				if (!empty($ipn->get_header('bin1'))) $bins[] = $ipn->get_header('bin1');
				if (!empty($ipn->get_header('bin2'))) $bins[] = $ipn->get_header('bin2');
			}
			$bins = array_unique($bins);
		}
		$bins = implode(', ', $bins);

		$worksheet->getCell('A'.($idx+2))->setValue($ipn->get_header('ipn'));
		$worksheet->getCell('B'.($idx+2))->setValue($bins);
		$worksheet->getCell('C'.($idx+2))->setValue($content_review->get_header('reason'));
	}

	$wb_file = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($workbook);
	$wb_file->save(__DIR__.'/data_management/content-reviews.xlsx');

	header('Content-disposition: attachment; filename=content-reviews.xlsx');
	header('Content-Type: application/vnd.ms-excel');

	echo file_get_contents(__DIR__.'/data_management/content-reviews.xlsx');
	exit();
}

$total_results = count($content_reviews);
$total_pages = ceil($total_results / $page_limit);

$content_reviews = array_slice($content_reviews, $start_idx, $page_limit);

$reasons = prepared_query::fetch('SELECT * FROM content_review_reasons ORDER BY name ASC', cardinality::SET); ?>
<!doctype html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
		<title><?= TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<style type="text/css">
			.dataTableContent { max-width: 250px; font-family: Arial, sans-serif; font-size: 10px; }
		</style>
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" style="background-color:#FFFFFF;">
		<!-- header //-->
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
		<script src="includes/general.js"></script>
		<!-- header_eof //-->
		<!-- body //-->
		<table border="0" width="100%" cellspacing="2" cellpadding="2">
			<tr>
				<td class="noPrint" width="<?= BOX_WIDTH; ?>" valign="top">
					<table border="0" width="<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
						<!-- left_navigation //-->
						<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
						<!-- left_navigation_eof //-->
					</table>
				</td>
				<!-- body_text //-->
				<td width="100%" valign="top" style="font-family: arial; padding: 15px;">
					<h3>Content Review Tickets</h3>
					<div style="font-size: 12px;">
						<form action="content_reviews.php" method="get">
							<input type="hidden" name="action" value="lookup">
							<input type="hidden" name="page" value="1">
							Status:
							<select name="status" id="status-lookup">
								<option value="ALL">All</option>
								<?php foreach (ck_content_review::$status as $id => $status) { ?>
								<option value="<?= $id; ?>" <?= $lookup_status==$id?'selected':''; ?>><?= $status; ?></option>
								<?php } ?>
							</select><br>
							Reason:
							<select name="reason_id">
								<option value="ALL">All</option>
								<?php foreach ($reasons as $reason) { ?>
								<option value="<?= $reason['id']; ?>" <?= @$_GET['reason_id']==$reason['id']?'selected':''; ?>><?= $reason['name']; ?></option>
								<?php } ?>
							</select><br>
							Vertical/Category:
							<select name="categories_id">
								<option value="ALL">All</option>
								<?php $categories = prepared_query::fetch('SELECT pscc.categories_id, pscv.name as vertical, pscc.name as category FROM products_stock_control_categories pscc LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id ORDER BY pscv.name ASC, pscc.name ASC', cardinality::SET);
								foreach ($categories as $category) { ?>
								<option value="<?= $category['categories_id']; ?>" <?= @$_GET['categories_id']==$category['categories_id']?'selected':''; ?>><?= $category['vertical'].' - '.$category['category']; ?></option>
								<?php } ?>
							</select><br>
							Dropship Flag:
							<select name="drop_ship">
								<option value="ALL">Show All Products</option>
								<option value="0" <?= $lookup_dropship===0?'selected':''; ?>>Show Warehouse Products</option>
								<option value="1" <?= $lookup_dropship===1?'selected':''; ?>>Show Dropship Products</option>
							</select><br>
							In stock only: <input type="checkbox" name="in_stock" <?= $lookup_instock?'checked':''; ?>><br>
							Exclude inactive/discontinued: <input type="checkbox" name="discontinued" <?= $lookup_discontinued?'checked':''; ?>><br>
							IPN Filter: <input type="text" name="ipn" id="ipn_search" value="<?= !empty($_GET['ipn'])?$_GET['ipn']:''; ?>"><br>
							<input type="submit" name="submit-action" value="Update">
							<input type="submit" name="submit-action" value="Export">
							<script>
								jQuery('#ipn_search').autocomplete({
									minChars: 3,
									source: function(request, response) {
										jQuery.ajax({
											minLength: 2,
											url: '/admin/serials_ajax.php?action=ipn_autocomplete',
											dataType: 'json',
											data: {
												term: request.term,
												search_type: 'ipn'
											},
											success: function(data) {
												response(jQuery.map(data, function(item) {
													if (item.value == null) item.value = item.label;
													if (item.data_display == null) item.data_display = item.label;
													return {
														misc: item.value,
														label: item.data_display,
														value: item.label
													}
												}));
											}
										});
									}
								});
							</script>
						</form>
					</div>
					<table id="review_table" class="tablesorter" border="0" cellpadding="5px" cellspacing="0" style="font-size:12px;">
						<thead>
							<tr>
								<th class="header">ID</th>
								<th class="header">IPN</th>
								<th class="header">Product Model</th>
								<th class="header">Bin(s)</th>
								<th class="header" data-sorter="shortDate" data-date-format="mmddyyyy">Review Date</th>
								<th class="header">Reviewer</th>
								<th class="header">Type</th>
								<th class="header">Slots</th>
								<th class="header">Reason</th>
								<th class="header">Status</th>
								<th class="header">Notes</th>
							</tr>
						</thead>
						<tfoot>
							<td colspan="12">
								<form action="/admin/content_reviews.php" method="get" id="cr-pagination">
									<input type="hidden" name="action" value="lookup">
									<input type="hidden" name="status" value="<?= !empty($_GET['status'])?$_GET['status']:1; ?>">
									<input type="hidden" name="reason_id" value="<?= @$_GET['reason_id']; ?>">
									<input type="hidden" name="categories_id" value="<?= @$_GET['categories_id']; ?>">
									<?php if (isset($_GET['drop_ship'])) { ?>
									<input type="hidden" name="drop_ship" value="<?= $_GET['drop_ship']; ?>">
									<?php }
									if ($__FLAG['in_stock']) { ?>
									<input type="hidden" name="in_stock" value="1">
									<?php }
									if ($__FLAG['discontinued']) { ?>
									<input type="hidden" name="discontinued" value="1">
									<?php } ?>
									<input type="hidden" name="ipn" value="<?= @$_GET['ipn']; ?>">
									<?php if ($page > 1) { ?>
									<a href="#" id="cr-pagination-previous" data-page="<?= $page-1; ?>">&lt; PREV</a>
									<?php } ?>
									[Page:
									<select name="page" id="content-review-page">
										<?php for ($i=1; $i<=$total_pages; $i++) { ?>
										<option value="<?= $i; ?>" <?= $page==$i?'selected':''; ?>><?= $i; ?></option>
										<?php } ?>
									</select>
									of <?= $total_pages; ?>]
									<?php if ($page < $total_pages) { ?>
									<a href="#" id="cr-pagination-next" data-page="<?= $page+1; ?>">NEXT &gt;</a>
									<?php } ?>
								</form>
								<script>
									jQuery('#content-review-page').change(function(e) {
										jQuery('#cr-pagination').submit();
									});
									jQuery('#cr-pagination-previous, #cr-pagination-next').click(function(e) {
										e.preventDefault();
										var page = jQuery(this).attr('data-page');
										jQuery('#content-review-page').val(page);
										jQuery('#cr-pagination').submit();
									});
								</script>
							</td>
						</tfoot>
						<tbody>
							<?php if ($content_reviews) {
								foreach ($content_reviews as $content_review) {
									$ipn = $content_review->get_ipn();
									$product = $content_review->get_listing(); ?>
							<tr id="row_<?= $content_review->id(); ?>">
								<td><?= $content_review->id(); ?></td>
								<td>
									<?php if (!empty($ipn)) { ?>
									<a href="/admin/ipn_editor.php?ipnId=<?= $ipn->get_header('ipn'); ?>" target="_blank"><?= $ipn->get_header('ipn'); ?></a>
									<?php }
									else echo 'N/A'; ?>
									<br>
									<?php if (!empty($product)) { ?>
									<a href="/product_info.php?products_id=<?= $product->id(); ?>&action=buy_now" target="_BLANK">Add to cart</a>
									<?php }
									else { ?>
									No product listing
									<?php } ?>
								</td>
								<td>
									<?php if (!empty($ipn) && !empty($ipn->get_image('image'))) { ?>
									<img src="https://media.cablesandkits.com/<?= $ipn->get_image('image'); ?>">
									<?php }
									else echo 'NONE'; ?>
								</td>
								<td>
									<?php $bins = [];
									if (!empty($ipn)) {
										if ($ipn->is('serialized')) {
											if ($ipn->has_serials()) {
												$serials = array_merge($ipn->get_serials(2), $ipn->get_serials(3), $ipn->get_serials(6));
												foreach ($serials as $serial) {
													if (!empty($serial->get_current_history()['bin_location'])) $bins[] = $serial->get_current_history()['bin_location'];
												}
											}
										}
										else {
											if (!empty($ipn->get_header('bin1'))) $bins[] = $ipn->get_header('bin1');
											if (!empty($ipn->get_header('bin2'))) $bins[] = $ipn->get_header('bin2');
										}
										$bins = array_unique($bins);
										
										echo implode(', ', $bins);
									} ?>
								</td>
								<td><?= $content_review->get_header('notice_date')->format('m/d/y g:ia'); ?></td>
								<td><?= $content_review->get_header('requester_firstname').' '.$content_review->get_header('requester_lastname'); ?></td>
								<td><?= $content_review->get_header('element'); ?></td>
								<td><?= $content_review->get_header('image_slot'); ?></td>
								<td id="reason_<?= $content_review->id(); ?>"><?= $content_review->get_header('reason'); ?></td>
								<td id="status_<?= $content_review->id(); ?>"><?= ck_content_review::$status[$content_review->get_header('status')]; ?></td>
								<td><?= $content_review->get_header('notes'); ?></td>
								<td id="actions_<?= $content_review->id(); ?>">
									<?php if ($content_review->get_header('status') == 1) { ?>
									<input type="button" class="mark-fixed" data-content-review-id="<?= $content_review->id(); ?>" value="Mark fixed">
									<input type="button" class="will-not-fix" data-content-review-id="<?= $content_review->id(); ?>" value="Will not fix">
									<input type="button" class="update-reason" data-content-review-id="<?= $content_review->id(); ?>" data-reason-id="<?= $content_review->get_header('reason_id'); ?>" value="Update Reason">
									<?php } ?>
								</td>
							</tr>
								<?php }
							} ?>
						</tbody>
					</table>
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<style>
			#update-reason { display:none; }
		</style>
		<div id="update-reason">
			<form action="/admin/content_reviews.php" method="post" id="update-reason-form">
				<input type="hidden" name="action" value="update_reason">
				<input type="hidden" name="ajax" value="1">
				<input type="hidden" name="content_review_id" id="reason-content_review_id" value="">
				<select name="reason_id" id="reason-reason_id">
					<?php foreach ($reasons as $reason) { ?>
					<option value="<?= $reason['id']; ?>"><?= $reason['name']; ?></option>
					<?php } ?>
				</select>
				<input type="submit" value="Save">
			</form>
		</div>
		<script>
			jQuery('.mark-fixed').click(function(e) {
				var content_review_id = jQuery(this).attr('data-content-review-id');
				jQuery.ajax({
					url: '/admin/content_reviews.php',
					type: 'post',
					dataType: 'text',
					data: { ajax: 1, action: 'update_review', content_review_id: content_review_id, status: 2 },
					success: function(data) {
						if (data == 'ERROR') {
							alert('There was a problem marking this issue fixed');
						}
						else {
							if (jQuery('status-lookup').val() == 'ALL') {
								jQuery('#row_'+content_review_id).hide();
							}
							else {
								jQuery('#status_'+content_review_id).html('Fixed');
								jQuery('#actions_'+content_review_id).html('');
							}
						}
					}
				});
			});
			jQuery('.will-not-fix').click(function(e) {
				var content_review_id = jQuery(this).attr('data-content-review-id');
				jQuery.ajax({
					url: '/admin/content_reviews.php',
					type: 'post',
					dataType: 'text',
					data: { ajax: 1, action: 'update_review', content_review_id: content_review_id, status: 3 },
					success: function(data) {
						if (data == 'ERROR') {
							alert('There was a problem marking this issue will not fix');
						}
						else {
							if (jQuery('status-lookup').val() == 'ALL') {
								jQuery('#row_'+content_review_id).hide();
							}
							else {
								jQuery('#status_'+content_review_id).html('Will Not Fix');
								jQuery('#actions_'+content_review_id).html('');
							}
						}
					}
				});
			});
			jQuery('.update-reason').click(function(e) {
				jQuery('#reason-content_review_id').val(jQuery(this).attr('data-content-review-id'));
				jQuery('#reason-reason_id').val(jQuery(this).attr('data-reason-id'));
				jQuery('#update-reason').dialog({ modal: true });
			});
			jQuery('#update-reason-form').submit(function(e) {
				e.preventDefault();

				var content_review_id = jQuery('#reason-content_review_id').val();
				var params = jQuery(this).serialize();

				jQuery.ajax({
					url: '/admin/content_reviews.php',
					type: 'post',
					dataType: 'text',
					data: params,
					success: function(data) {
						if (data == 'ERROR') {
							alert('There was a problem changing the reason on this problem');
						}
						else {
							jQuery('#reason_'+content_review_id).html(jQuery('#reason-reason_id option:selected').text());
							jQuery('#update-reason').dialog('close');
						}
					}
				});
			});
			jQuery('#review_table').tablesorter({
				widgets: ['zebra']
				/*headers: {
					6: {
						sorter: false
					}
				}*/
			});
		</script>
	</body>
</html>
