<?php
require('includes/application_top.php');

$action = !empty($_POST['action'])?$_POST['action']:NULL;

if ($action == 'create') {
	$product = new ck_product_listing($_POST['product_id']);

	$data = [
		'admin_id' => $_SESSION['perms']['admin_id'],
		'products_id' => $product->id(),
		'stock_id' => $product->get_header('stock_id'),
		'image_slot' => $_POST['problem_slots'],
		'reason_id' => $_POST['reason'],
		'status' => $_POST['reason']==1?0:1,
		'notes' => $_POST['notes']
	];

	$content_review = ck_content_review::create($data);

	CK\fn::redirect_and_exit('/admin/product_image_reviewer.php?'.tep_get_all_get_params());

}
elseif ($action == 'bulk_review') {
	$problems_slots = '';
	$product_ids = explode(',', $_POST['bulk_review_ids']);
	foreach ($product_ids as $product_id) {
		$problem_slots = '';
		if ($_POST[$product_id.'_reason'] != 'reviewed - no issue') {
			foreach ($_POST as $name => $value) {
				switch ($name) {
					case ($product_id.'_a'):
						if (trim($problem_slots != '')) $problem_slots .= ',';
						$problem_slots .= 'a';
						break;
					case ($product_id.'_b'):
						if (trim($problem_slots != '')) $problem_slots .= ',';
						$problem_slots .= 'b';
						break;
					case ($product_id.'_c'):
						if (trim($problem_slots != '')) $problem_slots .= ',';
						$problem_slots .= 'c';
						break;
					case ($product_id.'_d'):
						if (trim($problem_slots != '')) $problem_slots .= ',';
						$problem_slots .= 'd';
						break;
					case ($product_id.'_e'):
						if (trim($problem_slots != '')) $problem_slots .= ',';
						$problem_slots .= 'e';
						break;
					case ($product_id.'_f'):
						if (trim($problem_slots != '')) $problem_slots .= ',';
						$problem_slots .= 'f';
						break;
					case ($product_id.'_g'):
						if (trim($problem_slots != '')) $problem_slots .= ',';
						$problem_slots .= 'g';
						break;
				}
			}
		}

		$product = new ck_product_listing($product_id);

		$data = [
			'admin_id' => $_SESSION['perms']['admin_id'],
			'products_id' => $product->id(),
			'stock_id' => $product->get_header('stock_id'),
			'image_slot' => $problem_slots,
			'reason_id' => $_POST[$product_id.'_reason'],
			'status' => $_POST[$product_id.'_reason']==1?0:1,
			'notes' => NULL
		];

		$content_review = ck_content_review::create($data);
	}
	CK\fn::redirect_and_exit('/admin/product_image_reviewer.php?'.tep_get_all_get_params());
}

//filter values
$psc_cats = prepared_query::fetch("select pscc.categories_id as id, concat(pscv.name, ' - ', pscc.name) as name from products_stock_control_categories pscc left join products_stock_control_verticals pscv on (pscc.vertical_id = pscv.id) where 1 order by pscv.name asc, pscc.name asc", cardinality::SET);
$ds_options = array("ALL" => "Show All Products", "0" => "Show Warehouse Products", "1" => "Show Dropship Products");

$cr_reasons = prepared_query::fetch('select crr.id, crr.name from content_review_reasons crr where 1 order by crr.id asc', cardinality::SET);

$days_filter_options = array('30', '60', '90', '180', '365');
$days_filter = 90;
if (!empty($_GET['days_filter'])) {
	$days_filter = $_GET['days_filter'];
}

//filter options
$filter = '';
if (!empty($_GET['pscc'])) {
	$filter .= " and psc.products_stock_control_category_id = '".$_GET['pscc']."' ";
}
if (isset($_GET['ds']) && $_GET['ds'] != "ALL") {
	$filter .= " and psc.drop_ship = '".$_GET['ds']."' ";
}
if (isset($_GET['in_stock'])) {
	$filter .= " and psc.stock_quantity > 0 ";
}
if (isset($_GET['disco'])) {
	$filter .= " and psc.discontinued = 0 ";
}
if (isset($_GET['ipn'])) {
	$filter .= " and psc.stock_name like '%".$_GET['ipn']."%' ";
}

$product_query = "select p.products_id, cr.notice_date, cr.admin_id
		from products p
		left join content_reviews cr on p.products_id = cr.product_id
		left join products_stock_control psc on (p.stock_id = psc.stock_id)
		where p.products_status = 1 and
		(cr.id is null or cr.id = (select max(cr2.id) from content_reviews cr2 where cr2.product_id = p.products_id)) and
		(cr.notice_date is null or cr.notice_date < date_sub(now(), interval $days_filter day)) $filter
		order by p.products_id desc
		limit 0, 10";

$products = prepared_query::fetch($product_query, cardinality::SET);

function insertImageData($product, $slot) {
	$other_product_ids = prepared_query::fetch("select p.products_id from products p where p.stock_id = '". $product->stock_id ."' and p.products_id != '". $product->products_id ."' and p.products_status = '1'", cardinality::SET);

	$other_products = array();
	foreach ($other_product_ids as $unused => $row) {
		$p = new ck_product_listing($row['products_id']);
		$other_products[] = (object) $p->get_image();
	}

	$other_product_problem = false;
	$image = '';
	$image_med = '';
	$image_300 = '';
	$image_sm = '';
	switch ($slot) {
		case 'a':
			$image = $product->products_image_lrg;
			$image_med = $product->products_image_med;
			$image_sm = $product->products_image;
			$image_300 = str_replace(".jpg", "_300.jpg", str_replace(".gif", "_300.gif", $image));
			if (count($other_products) > 0) {
				foreach ($other_products as $unused => $other_product) {
					if ($other_product->products_image_lrg != $image ||
						$other_product->products_image_med != $image_med ||
						$other_product->products_image != $image_sm) {
						$other_product_problem = true;
					}
				}
			}
			break;
		case 'b':
			$image = $product->products_image_xl_1;
			$image_sm = $product->products_image_sm_1;
			$image_300 = str_replace(".jpg", "_300.jpg", str_replace(".gif", "_300.gif", $image));
			if (count($other_products) > 0) {
				foreach ($other_products as $unused => $other_product) {
					if ($other_product->products_image_xl_1 != $image ||
						$other_product->products_image_sm_1 != $image_sm) {
						$other_product_problem = true;
					}
				}
			}
			break;
		case 'c':
			$image = $product->products_image_xl_2;
			$image_sm = $product->products_image_sm_2;
			$image_300 = str_replace(".jpg", "_300.jpg", str_replace(".gif", "_300.gif", $image));
			if (count($other_products) > 0) {
				foreach ($other_products as $unused => $other_product) {
					if ($other_product->products_image_xl_2 != $image ||
						$other_product->products_image_sm_2 != $image_sm) {
						$other_product_problem = true;
					}
				}
			}
			break;
		case 'd':
			$image = $product->products_image_xl_3;
			$image_sm = $product->products_image_sm_3;
			$image_300 = str_replace(".jpg", "_300.jpg", str_replace(".gif", "_300.gif", $image));
			if (count($other_products) > 0) {
				foreach ($other_products as $unused => $other_product) {
					if ($other_product->products_image_xl_3 != $image ||
						$other_product->products_image_sm_3 != $image_sm) {
						$other_product_problem = true;
					}
				}
			}
			break;
		case 'e':
			$image = $product->products_image_xl_4;
			$image_sm = $product->products_image_sm_4;
			$image_300 = str_replace(".jpg", "_300.jpg", str_replace(".gif", "_300.gif", $image));
			if (count($other_products) > 0) {
				foreach ($other_products as $unused => $other_product) {
					if ($other_product->products_image_xl_4 != $image ||
						$other_product->products_image_sm_4 != $image_sm) {
						$other_product_problem = true;
					}
				}
			}
			break;
		case 'f':
			$image = $product->products_image_xl_5;
			$image_sm = $product->products_image_sm_5;
			$image_300 = str_replace(".jpg", "_300.jpg", str_replace(".gif", "_300.gif", $image));
			if (count($other_products) > 0) {
				foreach ($other_products as $unused => $other_product) {
					if ($other_product->products_image_xl_5 != $image ||
						$other_product->products_image_sm_5 != $image_sm) {
						$other_product_problem = true;
					}
				}
			}
			break;
		case 'g':
			$image = $product->products_image_xl_6;
			$image_sm = $product->products_image_sm_6;
			$image_300 = str_replace(".jpg", "_300.jpg", str_replace(".gif", "_300.gif", $image));
			if (count($other_products) > 0) {
				foreach ($other_products as $unused => $other_product) {
					if ($other_product->products_image_xl_6 != $image ||
						$other_product->products_image_sm_6 != $image_sm) {
						$other_product_problem = true;
					}
				}
			}
			break;
	}

	if (trim($image) == '') {
		echo "N/A";
		return;
	}

	$missing_fs_files = array();
	if (service_locator::get_config_service()->is_production() && !file_exists(DIR_FS_CATALOG.DIR_WS_IMAGES.$image)) {
		$missing_fs_files[] = $image;
	}
	if ($slot == 'a' && service_locator::get_config_service()->is_production() && !file_exists(DIR_FS_CATALOG.DIR_WS_IMAGES.$image_med)) {
		$missing_fs_files[] = $image_med;
	}
	if (service_locator::get_config_service()->is_production() && !file_exists(DIR_FS_CATALOG.DIR_WS_IMAGES.$image_sm)) {
		$missing_fs_files[] = $image_sm;
	}
	if (service_locator::get_config_service()->is_production() && !file_exists(DIR_FS_CATALOG.DIR_WS_IMAGES.$image_300)) {
		$missing_fs_files[] = $image_300;
	}
	?><input type="checkbox" id="<?= $product->products_id;?>_<?= $slot; ?>" name="<?= $product->products_id;?>_<?= $slot; ?>" /><a href="javacript: void('0');" onclick="displayImageModal('<?= $image; ?>', '<?= $image_med; ?>', '<?= $image_sm; ?>', '<?= $image_300; ?>', 'Images for <?= $product->products_model;?>, slot <?= $slot; ?>');"><img src="//media.cablesandkits.com/<?= $image_sm; ?>" border="0"/></a>
	<img style="display: none;" src="http://media.cablesandkits.com/<?= $image; ?>"/>
	<?php if ($slot == 'a') { ?><img style="display: none;" src="http://media.cablesandkits.com/<?= $image_med; ?>"/><?php } ?>
	<img style="display: none;" src="http://media.cablesandkits.com/<?= $image_sm; ?>"/>
	<img style="display: none;" src="http://media.cablesandkits.com/<?= $image_300; ?>"/>
	<?php if ($other_product_problem) { ?><br/><b>Other Products for this IPN do not match this image.</b><?php } ?>
	<?php if (count($missing_fs_files) > 0) { ?><br/><strong>Missing from file system:</strong><ul><?php
		foreach ($missing_fs_files as $unsued => $image_path) { ?><li><?= $image_path; ?></li><?php }
	?></ul><?php } ?>
	<?php

}

?><!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<style type="text/css">
		.dataTableContent { max-width: 250px; font-family: Arial, sans-serif; font-size: 10px; }
	</style>
	<script type="text/javascript">
	</script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" style="background-color:#FFFFFF;">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<script language="javascript" src="includes/general.js"></script>
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
		<h3>Image Reviews</h3>
		<div style="font-size: 12px;">
		<form method="GET" action="product_image_reviewer.php">
			Last reviewed:
			<select name="days_filter"><?php
			foreach ($days_filter_options as $unused => $option) {
				?><option value="<?= $option; ?>" <?php if ($days_filter == $option) { ?> selected <?php } ?>><?= $option; ?> days ago</option><?php
			}
			?></select><br/>
			Vertical/Category:
			<select name="pscc">
				<option value="0">All</option><?php
				foreach ($psc_cats as $unused => $row) {
				?><option value="<?= $row['id']; ?>" <?php if (@$_GET['pscc'] == $row['id']) { ?> selected <?php } ?>><?= $row['name']; ?></option><?php
				} ?>
			</select><br/>
			Dropship Flag:
			<select name="ds">
				<?php foreach ($ds_options as $id => $name) {
				?><option value="<?= $id; ?>" <?php if (isset($_GET['ds']) && $_GET['ds'] == $id) { ?> selected <?php } ?>><?= $name; ?></option><?php
				} ?>
			</select><br/>
			In stock only:
			<input type="checkbox" <?php if (isset($_GET['in_stock'])) { ?>checked<?php } ?> name="in_stock"/><br/>
			Exclude inactive/discontinued:
			<input type="checkbox" <?php if (isset($_GET['disco'])) { ?>checked<?php } ?> name="disco"/><br/>
			IPN Filter:
					<input type="text" name="ipn" id="ipn_search" value="<?php if (!empty($_GET['ipn'])) { echo $_GET['ipn'];} ?>"><br/>
			<input type="submit" value="Update"/>
		</form>
				<script language="javascript">
		jQuery(document).ready(function($) {
			$('#ipn_search').autocomplete({
				minChars: 3,
				source: function(request, response) {
					$.ajax({
						minLength: 2,
						url:	'/admin/serials_ajax.php?'	+
							'action=ipn_autocomplete',
						dataType: 'json',
						data: {
							term: request.term,
							search_type: 'ipn'
						},
						success: function(data) {
							response($.map(data, function(item) {
								if (item.value == null) {
									item.value = item.label;
								}
												if (item.data_display == null) {
													item.data_display = item.label;
												}
								return {
									misc: item.value,
									label: item.data_display,
									value: item.label
								}
							}))
						}
					});
				}
			});
		});
				</script>
		</div>

		<form method="POST" action="/admin/product_image_reviewer.php?<?= tep_get_all_get_params(); ?>">

			<input type="hidden" name="action" value="bulk_review"/>
		<table border="0" cellpadding="5px" cellspacing="0" style="font-size: 12px;">
			<tr>
				<th>IPN</th>
				<th>Product</th>
				<th>Image A</th>
				<th>Image B</th>
				<th>Image C</th>
				<th>Image D</th>
				<th>Image E</th>
				<th>Image F</th>
				<th>Image G</th>
				<th>Last Review Date</th>
				<th>Last Reviewed By</th>
				<th>Review Status</th>
				<th>Action</th>
			</tr>
		<?php
		$bulk_review_ids = array();
		foreach ($products as $unused => $product_data) {
			$reviewer = null;
			if ($product_data['admin_id'] != null) {
				$reviewer = new ck_admin($product_data['admin_id']);
			}
			$product = new ck_product_listing($product_data['products_id']);
			$images = (object) $product->get_image();
			$images->products_id = $product->id();
			$images->stock_id = $product->get_header('stock_id');
			$images->products_model = $product->get_header('products_model');
			$bulk_review_ids[] = $product->id(); ?>
			<tr style="border-bottom: 1px solid black;">
				<td>
					<?php try {
						echo (new item_popup($product->get_ipn()->get_header('ipn'), service_locator::get_db_service(), array('products_id' => $product->id())));
					}
					catch (Exception $e) {
						// do nothing
					} ?></td>
				<td id="product_model_<?= $product->id(); ?>"><?= $product->get_header('products_model'); ?></td>
				<td><?php insertImageData($images, 'a');?></td>
				<td><?php insertImageData($images, 'b');?></td>
				<td><?php insertImageData($images, 'c');?></td>
				<td><?php insertImageData($images, 'd');?></td>
				<td><?php insertImageData($images, 'e');?></td>
				<td><?php insertImageData($images, 'f');?></td>
				<td><?php insertImageData($images, 'g');?></td>
				<td><?= ($product_data['notice_date'] == null ? 'Never' : date("m/d/y g:ia", strtotime($product_data['notice_date'])));?></td>
				<td><?= ($product_data['admin_id'] == null ? 'N/A' : $reviewer->get_name());?></td>
				<td>
					<select name="<?= $product->id(); ?>_reason"><?php
						foreach ($cr_reasons as $unused => $reason) {
							?><option value="<?= $reason['id']; ?>"><?= $reason['name']; ?></option><?php
					} ?></select>
				</td>
				<td><input type="button" value="Create Review" onclick="displayCRForm('<?= $product->id(); ?>');"/></td>
			</tr>
		<?php } ?>
		</table>
			<input type="hidden" name="bulk_review_ids" value="<?= implode(',', $bulk_review_ids);?>"/>
			<input type="submit" value="Submit Bulk Review"/>
		</form>
		<div id="imageDialog" style="display: none;">
			<table border="0px" cellpadding="5px"><tr>
				<th>Large Image</th>
				<th>300 Image</th>
				<th id="modal_med_header">Medium Image</th>
				<th>Small Image</th>
			</tr><tr>
				<td><img src="" border="0" id="modal_lg_image"/></td>
				<td><img src="" border="0" id="modal_300_image"/></td>
				<td id="modal_med_cell"><img src="" border="0" id="modal_med_image"/></td>
				<td><img src="" border="0" id="modal_sm_image"/></td>
			</tr></table>
		</div>
		<script type="text/javascript">
			function displayImageModal(lg_image, med_image, sm_image, image_300, dialogTitle) {
				jQuery('#modal_lg_image').attr('src', 'http://media.cablesandkits.com/' + lg_image);
				if (jQuery.trim(med_image) == "") {
					jQuery('#modal_med_cell').hide();
					jQuery('#modal_med_header').hide();
				} else {
					jQuery('#modal_med_cell').show();
					jQuery('#modal_med_header').show();
					jQuery('#modal_med_image').attr('src', 'http://media.cablesandkits.com/' + med_image);
				}
				jQuery('#modal_sm_image').attr('src', 'http://media.cablesandkits.com/' + sm_image);
				jQuery('#modal_300_image').attr('src', 'http://media.cablesandkits.com/' + image_300);
				jQuery('#imageDialog').attr('title', dialogTitle);
				jQuery('#imageDialog').dialog({height: 600, width: 900, modal: true});
			}
		</script>
		<div id="contentReviewDialog" style="display: none;" title="Create Content Review Record">

			<form method="POST" action="/admin/product_image_reviewer.php?<?= tep_get_all_get_params(); ?>">

			<h4>Product Model</h4>
			<span id="product_model_display"></span><input type="hidden" name="product_id" id="product_id"/><input type="hidden" name="action" value="create"/>
			<h4>Problem Slots</h4>
			<span id="problem_slots_display"></span><input type="hidden" id="problem_slots" name="problem_slots" value=""/>
			<h4>Review Reason</h4>
			<select name="reason" id="cr_reason"><?php
				foreach ($cr_reasons as $unused => $reason) {
					?><option value="<?= $reason['id']; ?>"><?= $reason['name']; ?></option><?php
				} ?></select>
			<h4>Notes (50 char max)</h4>
			<textarea name="notes" rows='2' cols='25'></textarea>
			<input type="submit" value="Submit"/>
			</form>
		</div>
		<script type="text/javascript">
			function displayCRForm(product_id) {
				//first we set product data
				jQuery('#product_model_display').html(jQuery('#product_model_' + product_id).html());
				jQuery('#product_id').val(product_id);

				//next we get all the checked boxes for the row in an array
				var problems = Array();
				if (jQuery('#' + product_id + "_a").attr('checked')) {
					problems[problems.length] = "a";
				}
				if (jQuery('#' + product_id + "_b").attr('checked')) {
					problems[problems.length] = "b";
				}
				if (jQuery('#' + product_id + "_c").attr('checked')) {
					problems[problems.length] = "c";
				}
				if (jQuery('#' + product_id + "_d").attr('checked')) {
					problems[problems.length] = "d";
				}
				if (jQuery('#' + product_id + "_e").attr('checked')) {
					problems[problems.length] = "e";
				}
				if (jQuery('#' + product_id + "_f").attr('checked')) {
					problems[problems.length] = "f";
				}
				if (jQuery('#' + product_id + "_g").attr('checked')) {
					problems[problems.length] = "g";
				}
				if (problems.length == 0) {
					jQuery('#problem_slots_display').html('None');
					jQuery('#problem_slots').val('');
					jQuery('#cr_reason').val('1');
				} else {
					var probs = "";
					for (var i = 0; i < problems.length; i++) {
						if (i >= 1) {
							probs += ',';
						}
						probs += problems[i];
					}
					jQuery('#problem_slots_display').html(probs);
					jQuery('#problem_slots').val(probs);
					jQuery('#cr_reason').val('2');
				}

				jQuery('#contentReviewDialog').dialog({height: 600, width: 600, modal: true});
			}
		</script>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
<!-- body_eof //-->
</body>
</html>
