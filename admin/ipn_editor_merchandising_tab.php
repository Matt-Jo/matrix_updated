<?php
require_once('ipn_editor_top.php');

// until we actually refactor product listings around merchandising containers, we'll just piecemeal this together
$products = $ipn->get_listings();
$family_units = $ipn->get_family_units();
$primary_container = $ipn->get_primary_container(); ?>
<table cellspacing="5px" cellpadding="5px" border="0">
	<thead>
		<tr>
			<td class="main"><strong>Links</strong></td>
			<td class="main"><strong>Model</strong></td>
			<td class="main"><strong>Name</strong></td>
			<td class="main"><strong>Status</strong></td>
			<td class="main"><strong>Broker Status</strong></td>
			<td class="main"><strong>Primary</strong></td>
			<?php // top admin, purchasing mgr, marketing
			if (in_array($_SESSION['perms']['admin_groups_id'], [1, 20, 27])) { ?>
			<td class="main"><strong>Level 1 Product</strong></td>
			<?php } ?>
			<td class="main"><strong>Special?</strong></td>
			<td class="main"><a href="dow_schedule.php"><strong>DOW</strong></a></td>
		</tr>
	</thead>

	<tbody>
		<?php foreach ($products as $idx => $product) { ?>
		<tr style="<?= $idx%2==0?'background-color:#dcdcdc;':''; ?>" id="<?= $product->id(); ?>">
			<td class="main">
				<a href="<?= $product->get_url(); ?>" target="_blank">View Page</a>
				&nbsp;&nbsp;
				<a href="/admin/categories.php?action=new_product&pID=<?= $product->id(); ?>" target="_blank">Edit</a>
			</td>
			<td class="main"><?= $product->get_header('products_model'); ?></td>
			<td class="main"><?= $product->get_header('products_name'); ?></td>
			<td class="main">
				<?php if ($product->is_active(ck_product_listing::ACTIVE_CONTEXT_STANDARD)) { ?>
				<img src="/admin/images/icon_status_green.gif" title="Active" alt="Active">
				<a href="/admin/ipn_editor.php?action=setflag&sub-action=set_status&flag=0&products_id=<?= $product->id(); ?>&stock_id=<?= $ipn->id(); ?>"><img src="/admin/images/icon_status_red_light.gif" title="Set Inactive" alt="Set Inactive"></a>
				<?php }
				else { ?>
				<a href="/admin/ipn_editor.php?action=setflag&sub-action=set_status&flag=1&products_id=<?= $product->id(); ?>&stock_id=<?= $ipn->id(); ?>"><img src="/admin/images/icon_status_green_light.gif" title="Set Active" alt="Set Active"></a>
				<img src="/admin/images/icon_status_red.gif" title="Inactive" alt="Inactive">
				<button type="button" class="archive-product" data-products-id="<?= $product->id(); ?>">Archive</button>
				<?php } ?>
			</td>
			<td class="main">
				<?php if ($product->is_active(ck_product_listing::ACTIVE_CONTEXT_BROKER)) { ?>
				<img src="/admin/images/icon_status_green.gif" title="Active" alt="Active">
				<a href="/admin/ipn_editor.php?action=setflag&sub-action=set_broker_status&flag=0&products_id=<?= $product->id(); ?>&stock_id=<?= $ipn->id(); ?>"><img src="/admin/images/icon_status_red_light.gif" title="Set Inactive" alt="Set Inactive"></a>
				<?php }
				else { ?>
				<a href="/admin/ipn_editor.php?action=setflag&sub-action=set_broker_status&flag=1&products_id=<?= $product->id(); ?>&stock_id=<?= $ipn->id(); ?>"><img src="/admin/images/icon_status_green_light.gif" title="Set Active" alt="Set Active"></a>
				<img src="/admin/images/icon_status_red.gif" title="Inactive" alt="Inactive">
				<?php } ?>
			</td>
			<td class="main">
				<form method="post" action="/admin/ipn_editor.php">
					<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
					<input type="hidden" name="action" value="set-primary-container">
					<input type="hidden" name="container_type_id" value="2">
					<input type="hidden" name="container_id" value="<?= $product->id(); ?>">
					<select id="primary_container" name="primary_container">
						<?php if (empty($primary_container)) $selected = '';
						elseif ($primary_container['container_type_id'] != 2) $selected = '';
						elseif ($primary_container['container_id'] != $product->id()) $selected = '';
						elseif ($primary_container['redirect']) $selected = 'redirect';
						elseif ($primary_container['canonical']) $selected = 'canonical';
						else $selected = 'primary'; ?>
						<option value="" <?= $selected==''?'selected':''; ?>>Not Primary</option>
						<option value="primary" <?= $selected=='primary'?'selected':''; ?>>Primary</option>
						<option value="canonical" <?= $selected=='canonical'?'selected':''; ?>>Primary &amp; Canonical for Siblings</option>
						<option value="redirect" <?= $selected=='redirect'?'selected':''; ?>>Primary &amp; Redirect all Siblings</option>
					</select>
					<input type="submit" value="Set">
				</form>
			</td>
			<?php // top admin, purchasing mgr, marketing
			if (in_array($_SESSION['perms']['admin_groups_id'], [1, 20, 27])) { ?>
			<td class="main">
				<?php if ($product->is('level_1_product')) { ?>
				<img src="/admin/images/icon_status_green.gif" title="Active" alt="Active">
				<a href="/admin/ipn_editor.php?action=setflag&sub-action=set_level1product&flag=0&products_id=<?= $product->id(); ?>&stock_id=<?= $ipn->id(); ?>"><img src="/admin/images/icon_status_red_light.gif" title="Set Inactive" alt="Set Inactive"></a>
				<?php }
				else { ?>
				<a href="/admin/ipn_editor.php?action=setflag&sub-action=set_level1product&flag=1&products_id=<?= $product->id(); ?>&stock_id=<?= $ipn->id(); ?>"><img src="/admin/images/icon_status_green_light.gif" title="Set Active" alt="Set Active"></a>
				<img src="/admin/images/icon_status_red.gif" title="Inactive" alt="Inactive">
				<?php } ?>
			</td>
			<?php } ?>
			<td class="main">
				<?php if ($product->has_any_specials()) {
					$special = $product->get_all_specials()[0];
					if ($special['status'] == 1) { ?>
				<img src="/admin/images/icon_status_green.gif" title="Active" alt="Active">
				<a href="/admin/ipn_editor.php?action=setflag&sub-action=set_special_status&flag=0&specials_id=<?= $special['specials_id']; ?>&stock_id=<?= $ipn->id(); ?>"><img src="/admin/images/icon_status_red_light.gif" title="Set Inactive" alt="Set Inactive"></a>
				[<?= CK\text::monetize($special['specials_new_products_price']); ?>]
				[QTY: <?= $special['specials_qty']; ?>]
				[<?= !empty($special['expires_date'])?'Exp. '.$special['expires_date']->format('m/d/Y'):'No Expiration'; ?>]
					<?php }
					else { ?>
				<a href="/admin/ipn_editor.php?action=setflag&sub-action=set_special_status&flag=1&specials_id=<?= $special['specials_id']; ?>&stock_id=<?= $ipn->id(); ?>"><img src="/admin/images/icon_status_green_light.gif" title="Set Active" alt="Set Active"></a>
				<img src="/admin/images/icon_status_red.gif" title="Inactive" alt="Inactive">
					<?php } ?>
				<a href="/admin/specials.php?page=1&sID=<?= $special['specials_id']; ?>&action=edit">[EDIT]</a>
				<?php }
				else { ?>
				<a href="/admin/specials.php?page=1&action=new&selected_products_id=<?= $product->id(); ?>">[ADD]</a>
				<?php } ?>
			</td>
			<td class="main" id="dow_schedule">
				<?php $dow = prepared_query::fetch('SELECT * FROM ck_dow_schedule WHERE products_id = :products_id AND (start_date >= DATE(NOW()) OR active = true) ORDER BY start_date ASC LIMIT 1', cardinality::ROW, [':products_id' => $product->id()]); ?>
				<form style="display:inline;" id="dow_control" action="/admin/dow_schedule.php" method="post">
					<input type="hidden" name="action" value="GO">
					<input type="hidden" name="source" value="<?= $_SERVER['PHP_SELF']; ?>">
					<?php if (!empty($dow)) { ?>
					<input type="hidden" name="dow_schedule_id" value="<?= $dow['dow_schedule_id']; ?>">
					<?php } ?>
					<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
					<input type="hidden" name="products_id" value="<?= $product->id(); ?>">
					<input type="date" name="start_date" value="<?= @$dow['start_date']; ?>">
					<?php if (!empty($dow) && $dow['active']) { ?>
					ACTIVE
					<?php }
					elseif (!empty($dow)) { ?>
					<select size="1" name="active">
						<?php if (empty($dow)) { ?><option value="">Status</option><?php } ?>
						<option value="0" <?= !empty($dow)&&empty($dow['active'])?'selected':''; ?>>inactive</option>
						<option value="1" <?= !empty($dow['active'])?'selected':''; ?>>ACTIVE</option>
					</select>
					<?php } ?>
					<input type="submit" value="GO">
				</form>
				<script>
					jQuery('#dow_control').submit(function(e) {
						e.preventDefault();
						jQuery('#dow_schedule').css('background-color', '#ffc;');
						var qstring = jQuery(this).serialize();
						var send = {
							url: jQuery(this).attr('action'),
							type: jQuery(this).attr('method'),
							dataType: 'json',
							data: qstring,
							success: function(data, textStatus, jqXHR) {
								if (data == null) return;

								if (data.status == 1) jQuery('#dow_schedule').css('background-color', '#cfc;'); // success
								else if (data.status == 2) jQuery('#dow_schedule').css('background-color', '#ccf;'); // conflict
								else jQuery('#dow_schedule').css('background-color', '#fcc;'); // error

								if (data.message) alert(data.message);
							},
							error: function(jqXHR, textStatus, errorThrown) {
								jQuery('#dow_schedule').css('background-color', '#fcc;'); // error
							}
						};
						if (jQuery(this).find('input[name=dow_schedule_id]').length) {
							// we're updating a currently existing dow entry
							if (jQuery(this).find('input[name=start_date]').val() == '') {
								if (confirm('Are you sure you want to remove the DOW for this product?')) {
									jQuery.ajax(send);
								}
								// otherwise, don't do anything
							}
							else {
								// we've got a date
								jQuery.ajax(send);
							}
						}
						else {
							// this is a new entry
							jQuery.ajax(send);
						}
						return false;
					});
				</script>
			</td>
		</tr>
		<?php } ?>

		<?php foreach ($family_units as $family_unit) {
			$containers = $family_unit->get_containers();
			foreach ($containers as $container) {
				$idx++; ?>
		<tr style="<?= $idx%2==0?'background-color:#dcdcdc;':''; ?>">
			<td class="main">
				<a href="<?= $container->get_url(); ?>" target="_blank">View Page</a>
				&nbsp;&nbsp;
				<a href="/admin/merchandising-family-container-detail.php?context=edit&family_container_id=<?= $container->id(); ?>" target="_blank">Edit</a>
			</td>
			<td class="main"><a href="/admin/merchandising-unit-family-detail.php?context=edit&family_unit_id=<?= $family_unit->id(); ?>" target="_blank">[Family]</a></td>
			<td class="main"><?= $container->get_header('name'); ?></td>
			<td class="main">
				<?php if ($container->is_active()) { ?>
				<img src="/admin/images/icon_status_green.gif" title="Active" alt="Active">
				<a href="/admin/ipn_editor.php?action=setflag&sub-action=set_container_status&flag=0&container_type_id=1&container_id=<?= $container->id(); ?>&stock_id=<?= $ipn->id(); ?>"><img src="/admin/images/icon_status_red_light.gif" title="Set Inactive" alt="Set Inactive"></a>
				<?php }
				else { ?>
				<a href="/admin/ipn_editor.php?action=setflag&sub-action=set_container_status&flag=1&container_type_id=1&container_id=<?= $container->id(); ?>&stock_id=<?= $ipn->id(); ?>"><img src="/admin/images/icon_status_green_light.gif" title="Set Active" alt="Set Active"></a>
				<img src="/admin/images/icon_status_red.gif" title="Inactive" alt="Inactive">
				<?php } ?>
			</td>
			<td></td>
			<td class="main">
				<form method="post" action="/admin/ipn_editor.php">
					<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
					<input type="hidden" name="action" value="set-primary-container">
					<input type="hidden" name="container_type_id" value="1">
					<input type="hidden" name="container_id" value="<?= $container->id(); ?>">
					<select id="primary_container" name="primary_container">
						<?php if (empty($primary_container)) $selected = '';
						elseif ($primary_container['container_type_id'] != 1) $selected = '';
						elseif ($primary_container['container_id'] != $container->id()) $selected = '';
						elseif ($primary_container['redirect']) $selected = 'redirect';
						elseif ($primary_container['canonical']) $selected = 'canonical';
						else $selected = 'primary'; ?>
						<option value="" <?= $selected==''?'selected':''; ?>>Not Primary</option>
						<option value="primary" <?= $selected=='primary'?'selected':''; ?>>Primary</option>
						<option value="canonical" <?= $selected=='canonical'?'selected':''; ?>>Primary &amp; Canonical for Siblings</option>
						<option value="redirect" <?= $selected=='redirect'?'selected':''; ?>>Primary &amp; Redirect all Siblings</option>
					</select>
					<input type="submit" value="Set">
				</form>
			</td>
			<?php // top admin, purchasing mgr, marketing
			if (in_array($_SESSION['perms']['admin_groups_id'], [1, 20, 27])) { ?>
			<td class="main"></td>
			<?php } ?>
			<td class="main"></td>
			<td class="main" id="dow_schedule"></td>
		</tr>
			<?php }
		} ?>
	</tbody>
</table>
<a href="/admin/dow_schedule.php" target="_blank">Manage all DOWS</a> | <a href="#" id="dow-control">DOW control colors:</a><br>
<div id="dow-control-info" style="display:none;">
	<span style="background-color:#cfc;">Green</span>: Success<br>
	<span style="background-color:#ffc;">Yellow</span>: Loading<br>
	<span style="background-color:#fcc;">Red</span>: Error - go to the dow management page to try again<br>
	<span style="background-color:#ccf;">Blue</span>: Conflict - go to the dow management page to resolve
</div>
<script>
	jQuery('#dow-control').click(function(e) {
		e.preventDefault();
		jQuery('#dow-control-info').toggle();
		return false;
	});
</script>

<?php if (!in_array($_SESSION['perms']['admin_groups_id'],[1, 27]) && $_SESSION['perms']['upload_images'] != 1) return; ?>
<hr>

Pictures:<br>

<?php $images = prepared_query::fetch('SELECT image, image_med, image_lrg, image_sm_1, image_xl_1, image_sm_2, image_xl_2, image_sm_3, image_xl_3, image_sm_4, image_xl_4, image_sm_5, image_xl_5, image_sm_6, image_xl_6 FROM products_stock_control_images WHERE stock_id = :stock_id', cardinality::ROW, [':stock_id' => $ipn->id()]);

$audit = new picture_audit($ipn->id());
$nm = $audit->check_naming();
$fs = $audit->check_filesystem();

if (!$nm || !$fs) { ?>
<strong>FOUND ISSUES:</strong>
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
		<tr class="image-row category-<?= strtolower(preg_replace('/[^a-zA-Z]/', '-', $audit->category)); ?>">
			<th>
				<a href="#" class="db-details" id="db_<?= $audit->stock_id; ?>">1</a>
				<div class="db_open" id="db_<?= $audit->stock_id; ?>-details">
					<?php $audit->show_records(); ?>
				</div>
			</th>
			<td><a href="/admin/ipn_editor.php?ipnId=<?= $audit->ipn; ?>" target="_blank"><?= $audit->ipn; ?></a></td>
			<td><?= $audit->category; ?></td>
			<th class="<?= $audit->status_count>0?'active-product':''; ?>"><?= $audit->status_count; ?> of <?= $audit->prod_count; ?> ACTIVE</th>
			<td class="ipn-img <?= $audit->problems['naming_ipn']||$audit->problems['naming_slot']||$audit->problems['naming_path']?'problem':''; ?>"><?= $audit->problems['naming_ipn']||$audit->problems['naming_slot']||$audit->problems['naming_path']?'NAMING':''; ?></td>
			<td class="ipn-img <?= $audit->problems['naming_product_consistency']?'problem':''; ?>"><?= $audit->problems['naming_product_consistency']?'PRODUCT NAMING':''; ?></td>
			<td class="ipn-img <?= $audit->problems['slot_gaps']?'problem':''; ?>"><?= $audit->problems['slot_gaps']?'SLOT GAPS':''; ?></td>
			<td class="ipn-img <?= $audit->problems['newproduct']?'problem':''; ?>"><?= $audit->problems['newproduct']?'NEWPRODUCT.GIF':''; ?></td>
			<?php if ($audit->initial_problems['pic_problem'] && $audit->problems['broken_reference'] === 0) { ?>
			<td colspan="4" class="ipn-img problem">BATCH CHECK SHOWED PROBLEM</td>
			<?php }
			elseif ($audit->problems['broken_reference'] === 0) { ?>
			<td colspan="4" class="ipn-img warning">NOT CHECKED</td>
			<?php }
			else { ?>
			<td class="ipn-img <?= $audit->problems['broken_reference']?'problem':''; ?>"><?= $audit->problems['broken_reference']?'BROKEN REF':''; ?></td>
			<td class="ipn-img <?= $audit->problems['wrong_dimensions']?'problem':''; ?>"><?= $audit->problems['wrong_dimensions']?'DIMENSIONS':''; ?></td>
			<?php /*<td class="ipn-img <?= $audit->problems['missing_300_size']?'problem':''; ?>"><?= $audit->problems['missing_300_size']?'MISSING 300 SIZE':''; ? ></td>*/ ?>
			<td class="ipn-img <?= $audit->problems['missing_archive']||$audit->problems['archive_dimensions']?'problem':''; ?>"><?= $audit->problems['missing_archive']?'MISSING ARCHIVE':($audit->problems['archive_dimensions']?'ARCHIVE DIMENSIONS':''); ?></td>
			<?php } ?>
			<td class="ipn-img <?= $audit->problems['naming_off_product_consistency']?'warning':''; ?>"><?= $audit->problems['naming_off_product_consistency']?'INACTIVE PRODUCT NAMING':''; ?></td>
		</tr>
	</tbody>
</table>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		jQuery("#buildertable").tablesorter({
			theme: 'blue',
			//widgets: ['zebra']
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
<?php }

global $image_errors;
if (!empty($image_errors)) { ?>
<strong>IMAGE UPLOAD ISSUES:</strong>
<style>
	.image_errs { background-color:#fcc; }
</style>
<ul class="image_errs">
	<?php foreach ($image_errors as $err) { ?>
	<li><?= $err; ?></li>
	<?php } ?>
</ul>
<?php } ?>

<style>
	#pic_list { font-size:8pt; }
	#pic_list th, #pic_list td { padding:3px 6px; border-style:solid; border-width:1px 0px 0px 1px; }
	#pic_list tr:last-child th, #pic_list tr:last-child td, #pic_list tr.demark td, #pic_list td.imgsize, #pic_list td.big_na { border-bottom-width:1px; }
	#pic_list th:last-child, #pic_list td:last-child { border-right-width:1px; }
	#pic_list th { background-color:#e6eeee; border-color:#fff; }
	#pic_list td { background-color:#fff; }
	#pic_list td.found { background-color:#cfc; }
	#pic_list td.missing { background-color:#fcc; }
	#pic_list .show-actual-size { display:block; height:120px; width:180px; }
	#pic_list .show-actual-size img { height:120px; width:180px; position:absolute; border:1px solid #00f; }
	#pic_list .show-actual-size img:hover { height:auto; width:auto; z-index:100; border-color:#fff; }
</style>

<form action="/admin/ipn_editor.php?selectedTab=1" method="post" enctype="multipart/form-data" id="manage_images">
	<input type="hidden" name="action" value="upload_images">
	<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
	<table cellspacing="0" id="pic_list" cellpadding="0" border="0" class="">
		<thead>
			<tr>
				<th>Size</th>
				<th>src</th>
				<th>MAIN / Slot A</th>
				<th>Slot B</th>
				<th>Slot C</th>
				<th>Slot D</th>
				<th>Slot E</th>
				<th>Slot F</th>
				<th>Slot G</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="imgsize" rowspan="2">Full<br>(<?= imagesizer::$map['lrg']['width'].'x'.imagesizer::$map['lrg']['height']; ?>)</td>
				<td>CDN</td>
				<td><?php if (!empty($images['image_lrg'])) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= $images['image_lrg']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['image_lrg']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
				<td><?php if (!empty($images['image_xl_1'])) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= $images['image_xl_1']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['image_xl_1']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
				<td><?php if (!empty($images['image_xl_2'])) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= $images['image_xl_2']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['image_xl_2']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
				<td><?php if (!empty($images['image_xl_3'])) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= $images['image_xl_3']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['image_xl_3']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
				<td><?php if (!empty($images['image_xl_4'])) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= $images['image_xl_4']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['image_xl_4']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
				<td><?php if (!empty($images['image_xl_5'])) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= $images['image_xl_5']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['image_xl_5']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
				<td><?php if (!empty($images['image_xl_6'])) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= $images['image_xl_6']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['image_xl_6']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
			</tr>
			<tr class="demark">
				<td>LOCAL</td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['image_lrg'])?'found':'missing'; ?>"><?php if (!empty($images['image_lrg'])) { ?><a href="/images/<?= $images['image_lrg']; ?>" target="_blank">SEE</a><?php } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['image_xl_1'])?'found':'missing'; ?>"><?php if (!empty($images['image_xl_1'])) { ?><a href="/images/<?= $images['image_xl_1']; ?>" target="_blank">SEE</a><?php } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['image_xl_2'])?'found':'missing'; ?>"><?php if (!empty($images['image_xl_2'])) { ?><a href="/images/<?= $images['image_xl_2']; ?>" target="_blank">SEE</a><?php } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['image_xl_3'])?'found':'missing'; ?>"><?php if (!empty($images['image_xl_3'])) { ?><a href="/images/<?= $images['image_xl_3']; ?>" target="_blank">SEE</a><?php } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['image_xl_4'])?'found':'missing'; ?>"><?php if (!empty($images['image_xl_4'])) { ?><a href="/images/<?= $images['image_xl_4']; ?>" target="_blank">SEE</a><?php } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['image_xl_5'])?'found':'missing'; ?>"><?php if (!empty($images['image_xl_5'])) { ?><a href="/images/<?= $images['image_xl_5']; ?>" target="_blank">SEE</a><?php } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['image_xl_6'])?'found':'missing'; ?>"><?php if (!empty($images['image_xl_6'])) { ?><a href="/images/<?= $images['image_xl_6']; ?>" target="_blank">SEE</a><?php } ?></td>
			</tr>
			<tr>
				<td class="imgsize" rowspan="2">Med<br>(<?= imagesizer::$map['med']['width'].'x'.imagesizer::$map['med']['height']; ?>)</td>
				<td>CDN</td>
				<td><?php if (!empty($images['image_med'])) { ?><a href="https://media.cablesandkits.com/<?= $images['image_med']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['image_med']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
				<td colspan="6" rowspan="2" class="big_na" style="text-align:center; vertical-align:middle;">N/A</td>
			</tr>
			<tr class="demark">
				<td>LOCAL</td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['image_med'])?'found':'missing'; ?>"><?php if (!empty($images['image_med'])) { ?><a href="/images/<?= $images['image_med']; ?>" target="_blank">SEE</a><?php } ?></td>
			</tr>
			<tr>
				<td class="imgsize" rowspan="2">Thumb<br>(<?= imagesizer::$map['sm']['width'].'x'.imagesizer::$map['sm']['height']; ?>)</td>
				<td>CDN</td>
				<td><?php if (!empty($images['image'])) { ?><a href="https://media.cablesandkits.com/<?= $images['image']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['image']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
				<td><?php if (!empty($images['image_sm_1'])) { ?><a href="https://media.cablesandkits.com/<?= $images['image_sm_1']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['image_sm_1']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
				<td><?php if (!empty($images['image_sm_2'])) { ?><a href="https://media.cablesandkits.com/<?= $images['image_sm_2']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['image_sm_2']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
				<td><?php if (!empty($images['image_sm_3'])) { ?><a href="https://media.cablesandkits.com/<?= $images['image_sm_3']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['image_sm_3']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
				<td><?php if (!empty($images['image_sm_4'])) { ?><a href="https://media.cablesandkits.com/<?= $images['image_sm_4']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['image_sm_4']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
				<td><?php if (!empty($images['image_sm_5'])) { ?><a href="https://media.cablesandkits.com/<?= $images['image_sm_5']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['image_sm_5']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
				<td><?php if (!empty($images['image_sm_6'])) { ?><a href="https://media.cablesandkits.com/<?= $images['image_sm_6']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['image_sm_6']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
			</tr>
			<tr class="demark">
				<td>LOCAL</td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['image'])?'found':'missing'; ?>"><?php if (!empty($images['image'])) { ?><a href="/images/<?= $images['image']; ?>" target="_blank">SEE</a><?php } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['image_sm_1'])?'found':'missing'; ?>"><?php if (!empty($images['image_sm_1'])) { ?><a href="/images/<?= $images['image_sm_1']; ?>" target="_blank">SEE</a><?php } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['image_sm_2'])?'found':'missing'; ?>"><?php if (!empty($images['image_sm_2'])) { ?><a href="/images/<?= $images['image_sm_2']; ?>" target="_blank">SEE</a><?php } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['image_sm_3'])?'found':'missing'; ?>"><?php if (!empty($images['image_sm_3'])) { ?><a href="/images/<?= $images['image_sm_3']; ?>" target="_blank">SEE</a><?php } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['image_sm_4'])?'found':'missing'; ?>"><?php if (!empty($images['image_sm_4'])) { ?><a href="/images/<?= $images['image_sm_4']; ?>" target="_blank">SEE</a><?php } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['image_sm_5'])?'found':'missing'; ?>"><?php if (!empty($images['image_sm_5'])) { ?><a href="/images/<?= $images['image_sm_5']; ?>" target="_blank">SEE</a><?php } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['image_sm_6'])?'found':'missing'; ?>"><?php if (!empty($images['image_sm_6'])) { ?><a href="/images/<?= $images['image_sm_6']; ?>" target="_blank">SEE</a><?php } ?></td>
			</tr>
			<tr>
				<td class="imgsize" rowspan="3">Archive<br>(<?= imagesizer::$map['archive']['width'].'x'.imagesizer::$map['archive']['height']; ?>)<br><input type="submit" value="Submit"></td>
				<td>Upload</td>
				<td><input type="file" name="slot_a" data-slot="a" style="width:180px;"><input type="hidden" id="slot_a_ok" value="1"></td>
				<td><input type="file" name="slot_b" data-slot="b" style="width:180px;"><input type="hidden" id="slot_b_ok" value="1"></td>
				<td><input type="file" name="slot_c" data-slot="c" style="width:180px;"><input type="hidden" id="slot_c_ok" value="1"></td>
				<td><input type="file" name="slot_d" data-slot="d" style="width:180px;"><input type="hidden" id="slot_d_ok" value="1"></td>
				<td><input type="file" name="slot_e" data-slot="e" style="width:180px;"><input type="hidden" id="slot_e_ok" value="1"></td>
				<td><input type="file" name="slot_f" data-slot="f" style="width:180px;"><input type="hidden" id="slot_f_ok" value="1"></td>
				<td><input type="file" name="slot_g" data-slot="g" style="width:180px;"><input type="hidden" id="slot_g_ok" value="1"></td>
			</tr>
			<tr>
				<td>LOCAL</td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/archive/'.basename($images['image_lrg']))?'found':'missing'; ?>"><?php if (!empty($images['image_lrg'])) { ?><a href="/images/archive/<?= basename($images['image_lrg']); ?>" target="_blank">SEE</a><?php } else { echo 'NONE'; } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/archive/'.basename($images['image_xl_1']))?'found':'missing'; ?>"><?php if (!empty($images['image_xl_1'])) { ?><a href="/images/archive/<?= basename($images['image_xl_1']); ?>" target="_blank">SEE</a><?php } else { echo 'NONE'; } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/archive/'.basename($images['image_xl_2']))?'found':'missing'; ?>"><?php if (!empty($images['image_xl_2'])) { ?><a href="/images/archive/<?= basename($images['image_xl_2']); ?>" target="_blank">SEE</a><?php } else { echo 'NONE'; } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/archive/'.basename($images['image_xl_3']))?'found':'missing'; ?>"><?php if (!empty($images['image_xl_3'])) { ?><a href="/images/archive/<?= basename($images['image_xl_3']); ?>" target="_blank">SEE</a><?php } else { echo 'NONE'; } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/archive/'.basename($images['image_xl_4']))?'found':'missing'; ?>"><?php if (!empty($images['image_xl_4'])) { ?><a href="/images/archive/<?= basename($images['image_xl_4']); ?>" target="_blank">SEE</a><?php } else { echo 'NONE'; } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/archive/'.basename($images['image_xl_5']))?'found':'missing'; ?>"><?php if (!empty($images['image_xl_5'])) { ?><a href="/images/archive/<?= basename($images['image_xl_5']); ?>" target="_blank">SEE</a><?php } else { echo 'NONE'; } ?></td>
				<td class="<?= file_exists(picture_audit::$imgfolder.'/archive/'.basename($images['image_xl_6']))?'found':'missing'; ?>"><?php if (!empty($images['image_xl_6'])) { ?><a href="/images/archive/<?= basename($images['image_xl_6']); ?>" target="_blank">SEE</a><?php } else { echo 'NONE'; } ?></td>
			</tr>
			<tr>
				<td>Remove</td>
				<td><input type="checkbox" name="remove[a]"></td>
				<td><input type="checkbox" name="remove[b]"></td>
				<td><input type="checkbox" name="remove[c]"></td>
				<td><input type="checkbox" name="remove[d]"></td>
				<td><input type="checkbox" name="remove[e]"></td>
				<td><input type="checkbox" name="remove[f]"></td>
				<td><input type="checkbox" name="remove[g]"></td>
			</tr>
		</tbody>
	</table>
</form>

<input type="hidden" id="products_tab_ipn" value="<?= preg_replace('#/#', '$', $ipn->get_header('ipn')); ?>">
<script>
	var ipn = jQuery('#products_tab_ipn').val();
	jQuery(':file').change(function() {
		if (this.files[0].name != ipn+jQuery(this).attr('data-slot')+'.jpg') {
			jQuery('#'+jQuery(this).attr('name')+'_ok').val(0);
			jQuery(this).parent('td').removeClass('found').addClass('missing');
			var errmsg = 'There is a problem with the naming convention used in this file: '+this.files[0].name;
			if (/\//.test(this.files[0].name)) errmsg = 'Please note that the "/" character is not allowed, if needed by the IPN replace with "$": '+this.files[0].name;
			alert(errmsg);
		}
		else {
			jQuery('#'+jQuery(this).attr('name')+'_ok').val(1);
			jQuery(this).parent('td').removeClass('missing').addClass('found');
		}
	});

	jQuery('.archive-product').on('click', function () {
		var products_id = jQuery(this).attr('data-products-id');
		jQuery.ajax({
			method: 'GET',
			dataType: 'json',
			url: '/admin/ipn_editor.php',
			data: { ajax:1, action: 'archive-product', products_id: products_id },
			success: function(data) {
				jQuery('#'+products_id).remove();
			}
		});
	});
</script>
