<?php
require('includes/application_top.php');

set_time_limit(90);
ini_set('memory_limit', '1024M');

$problem_list = picture_audit::report_list(0);
$categories = array();
foreach ($problem_list as $ipn) {
	if (!in_array($ipn->category, $categories)) $categories[] = $ipn->category;
}
sort($categories);
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<script language="javascript" src="includes/menu.js"></script>
		<script language="javascript" src="../includes/javascript/prototype.js"></script>
		<style>
			.fc th, .fc td { border-style:solid; border-color:#000; border-width:0px 1px 1px 0px; padding:4px 6px; }
			.fc tr:first-child th, .fc tr:first-child td { border-top-width:1px; }
			.fc th:first-child, .fc td:first-child { border-left-width:1px; }
			.grouper { position:relative; overflow:hidden; margin:10px; border:1px solid #000; }
			.ipnlink { display:block; width:300px; text-align:center; float:left; border-right:1px solid #000; padding:2px 0px; }
			.ipnlink:hover { color:#c00; }
			.ipnlink span { color:#000; }
			#ipn-selector { cursor:pointer; }
			.ipn-selector { display:<?= !empty($_GET['stock_id'])?'none':'block'; ?>; }
			.db_open { display:none; background-color:#fff; border:1px solid #000; padding:5px 7px; position:absolute; }
			table.tablesorter tbody tr.odd.warn td, table.tablesorter tbody tr.even.warn td, .warn th { background-color:#fcc; }
			.ipn-img { text-align:center; }
			.active-product { background-color:#cfc; }
			table.tablesorter tbody td.problem, table.tablesorter tbody tr.odd td.problem { background-color:#fcc; }
			table.tablesorter tbody td.warning, table.tablesorter tbody tr.odd td.warning { background-color:#ffc; }
			.show-db-details { text-align:center; }
		</style>
	</head>
	<body marginstyle="width:0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<!-- header //-->
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
		<script language="javascript" src="includes/general.js"></script>
		<!-- header_eof //-->
		<!-- body //-->
		<table border="0" style="width:100%" cellspacing="2" cellpadding="2">
			<tr>
				<td style="width:<?php echo BOX_WIDTH; ?>" valign="top">
					<table border="0" style="width:<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
						<!-- left_navigation //-->
						<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
						<!-- left_navigation_eof //-->
					</table>
				</td>
				<!-- body_text //-->
				<td style="width:100%" valign="top">
					<select name="category_filter" id="category_filter" size="1">
						<option value="">All Categories</option>
						<?php foreach ($categories as $category) { ?>
						<option value="<?php echo strtolower(preg_replace('/[^a-zA-Z]/', '-', $category)); ?>"><?= $category; ?></option>
						<?php } ?>
					</select><br><br>
					<form action="<?= $_SERVER['PHP_SELF']; ?>" id="srl" method="get">
						<input type="hidden" name="selected_box" value="purchasing">
						<div style="border: 1px solid black; padding: 10px 10px 200px 10px;">
							<?php $batch_size = 100;
							$page = !empty($_GET['page'])?$_GET['page']:1;
							$start = ($page - 1) * $batch_size;
							$end = ($page * $batch_size) - 1;
							$total = count($problem_list);
							$total_pages = ceil($total / $batch_size); ?>
							Page:
							<select id="paging">
								<?php for ($i=1; $i<=$total_pages; $i++) { ?>
								<option value="<?= $i; ?>" <?= $i==$page?'selected':''; ?>><?= $i; ?></option>
								<?php } ?>
							</select>
							<script>
								jQuery('#paging').change(function() {
									window.location = '/admin/image_issues_report.php?page='+jQuery(this).val();
								});
							</script>
							<table cellspacing="0" id="buildertable" cellpadding="0" border="0" class="fc tablesorter">
								<thead>
									<tr>
										<th>#</th>
										<th>IPN</th>
										<th>Category</th>
										<th>Active?</th>
										<th>Naming Scheme</th>
										<th>IPN/Product Consistency</th>
										<th>Slot Gaps</th>
										<th>New Product Img</th>
										<th>Missing File</th>
										<th>Wrong Dimensions</th>
										<!--th>Missing 300 Size</th-->
										<th>Archive Image Issues</th>
										<th>Disabled IPN/Product Consistency</th>
									</tr>
								</thead>
								<tbody>
								<?php if (!empty($problem_list)) {
									foreach ($problem_list as $idx => $ipn) {
										if ($idx < $start) continue;
										if ($idx > $end) break;
										
										$ipn->check_filesystem(); ?>
									<tr class="image-row category-<?php echo strtolower(preg_replace('/[^a-zA-Z]/', '-', $ipn->category)); ?>">
										<th>
											<a href="#" class="db-details" id="db_<?php echo $ipn->stock_id; ?>"><?= $idx+1; ?></a>
											<div class="db_open" id="db_<?php echo $ipn->stock_id; ?>-details">
												<?php $ipn->show_records(); ?>
											</div>
										</th>
										<td><a href="/admin/ipn_editor.php?ipnId=<?php echo $ipn->ipn; ?>" target="_blank"><?php echo $ipn->ipn; ?></a></td>
										<td><?php echo $ipn->category; ?></td>
										<th title="How many active listings does this IPN have?" class="<?php echo $ipn->status_count>0?'active-product':''; ?>"><?php echo $ipn->status_count; ?> of <?php echo $ipn->prod_count; ?> ACTIVE</th>
										<td title="Is this image in the correct folder? Is the image slot identified correctly in the image name? Is the image named for the IPN?" class="ipn-img <?php echo $ipn->problems['naming_ipn']||$ipn->problems['naming_slot']||$ipn->problems['naming_path']?'problem':''; ?>"><?php echo $ipn->problems['naming_ipn']||$ipn->problems['naming_slot']||$ipn->problems['naming_path']?'NAMING':''; ?></td>
										<td title="Does the listing have the same image reference as the IPN? This doesn't cause a problem, but it's an indication something went wrong technically." class="ipn-img <?php echo $ipn->problems['naming_product_consistency']?'problem':''; ?>"><?php echo $ipn->problems['naming_product_consistency']?'PRODUCT NAMING':''; ?></td>
										<td title="Is an image slot skipped?" class="ipn-img <?php echo $ipn->problems['slot_gaps']?'problem':''; ?>"><?php echo $ipn->problems['slot_gaps']?'SLOT GAPS':''; ?></td>
										<td title="Does this IPN use the newproduct.gif image?" class="ipn-img <?php echo $ipn->problems['newproduct']?'problem':''; ?>"><?php echo $ipn->problems['newproduct']?'NEWPRODUCT.GIF':''; ?></td>
										<?php /*if ($ipn->initial_problems['pic_problem'] && $ipn->problems['broken_reference'] === 0) { ? >
										<td colspan="3" class="ipn-img problem">BATCH CHECK SHOWED PROBLEM</td>
										<?php }
										elseif ($ipn->problems['broken_reference'] === 0) { ?>
										<td colspan="3" class="ipn-img warning">NOT CHECKED</td>
										<?php }
										else { */ ?>
										<td title="Does the referenced image exist on our server?" class="ipn-img <?php echo $ipn->problems['broken_reference']?'problem':''; ?>"><?php echo $ipn->problems['broken_reference']?'BROKEN REF':''; ?></td>
										<td title="Are the dimensions correct on all images?" class="ipn-img <?php echo $ipn->problems['wrong_dimensions']?'problem':''; ?>"><?php echo $ipn->problems['wrong_dimensions']?'DIMENSIONS':''; ?></td>
										<?php /*<td class="ipn-img <?php echo $ipn->problems['missing_300_size']?'problem':''; ?>"><?php echo $ipn->problems['missing_300_size']?'MISSING 300 SIZE':''; ? ></td>*/ ?>
										<td title="Does the large/archive image exist?" class="ipn-img <?php echo $ipn->problems['missing_archive']||$ipn->problems['archive_dimensions']?'problem':''; ?>"><?php echo $ipn->problems['missing_archive']?'MISSING ARCHIVE':($ipn->problems['archive_dimensions']?'ARCHIVE DIMENSIONS':''); ?></td>
										<?php /*}*/ ?>
										<td title="Does the listing have the same image reference as the IPN, on inactive products?" class="ipn-img <?php echo $ipn->problems['naming_off_product_consistency']?'warning':''; ?>"><?php echo $ipn->problems['naming_off_product_consistency']?'INACTIVE PRODUCT NAMING':''; ?></td>
									</tr>
									<?php }
								} ?>
								</tbody>
							</table>
							<script>
								jQuery(document).ready(function($) {
									jQuery("#buildertable").tablesorter({
										theme: 'blue',
										//widgets: ['zebra']
									});

									jQuery('#category_filter').change(function() {
										if (jQuery(this).val() == '') jQuery('.image-row').show();
										else {
											jQuery('.image-row').hide();
											jQuery('.category-'+jQuery(this).val()).show();
										}
									});

									jQuery('.pop_open').click(function(e) {
										e.stopPropagation();
									});

									var last_clicked_db;
									jQuery('.db-details').click(function(e) {
										last_clicked_po = '#'+jQuery(this).attr('id')+'-details';

										jQuery(last_clicked_po).show();
										jQuery('body').addClass('viewing-details');

										e.preventDefault();
									});

									jQuery('.viewing-details').live('click', function() {
										jQuery('.db_open.viewing').hide().removeClass('viewing');
										if (last_clicked_po != undefined) {
											jQuery(last_clicked_po).addClass('viewing');
											last_clicked_po = undefined;
										}
										else jQuery('body').removeClass('viewing-details');
									});
								});
							</script>
						</div>
					</form>
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
</html>
