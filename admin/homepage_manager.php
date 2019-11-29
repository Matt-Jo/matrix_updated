<?php
require('includes/application_top.php');

$action = isset($_REQUEST['action'])?$_REQUEST['action']:NULL;

if (!empty($action)) {
	ck_homepage::process_action($action);
}

$homepage = new ck_homepage();
?>
<!doctype html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.38.0/codemirror.css">
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
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
					<style>
						#homepage-manager {}
						.homepage-element { border-collapse:collapse; font-size:.9em; }
						.homepage-element input[type=text] { width:200px; }
						.homepage-element th, .homepage-element td { border:1px solid #000; padding:6px 10px; }
						.homepage-element thead th { border-bottom-width:2px; }
						.homepage-element th small { font-size:.7em; }
						.homepage-element td.inner-block { padding:0px 0px 0px 0px; }
						.img-health { padding:14px 10px; }
						.img-health.found { background-color:#cfc; border:2px solid #cfc; }
						.img-health.missing { background-color:#fcc; border:2px solid #fcc; }

						.inactive td { background-color:#ccc; }
						.inactive .img-health.found, .inactive .img-health.missing { background-color:#ccc; }

						.homepage-element tfoot td { text-align:right; border-width:1px 0px 0px 0px; }

						.autocomplete-lookup { display:none; background-color:#fff; border:1px solid #5cc; position:absolute; /*max-width:1000px;*/ max-height:700px; overflow-y:scroll; }
						.autocomplete-lookup .entry { margin:0px; padding:4px 6px; font-size:.5vw; white-space:nowrap; border-bottom:1px solid #999; display:block; }
						.autocomplete-lookup .entry:hover { border-radius:3px; border-bottom-color:transparent; background:linear-gradient(#6ff, #7cf); }

						.homepage-element input.short-numeric-input { width:25px; }

						.showcase { width:100%; }
						.showcase td, .showcase th { white-space:nowrap; }

						.html-input-header { width:85%; }
						#manage-showcase-section { margin:20px; }
						.showcase-html-td { width:80%; }
						.showcase-html { width:99%; height:150px; overflow:auto; }
					</style>
					<form style="display:block;" action="/admin/homepage_manager.php" method="post">
						<input type="hidden" name="action" value="manage_elements">
						<input type="hidden" name="element-type" value="rotator">
						<h3>Rotator</h3>
						<table class="homepage-element rotator" cellpadding="0" cellspacing="0" border="0">
							<thead>
								<tr>
									<th colspan="2">Image Reference / Health</th>
									<th>Image Alt Text</th>
									<th>Link Target</th>
									<th>Sort Order</th>
									<th>Active</th>
									<th>Remove</th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<td colspan="7"><input type="submit" value="Submit"></td>
								</tr>
							</tfoot>
							<tbody>
								<?php $rotator = $homepage->get_rotator();
								foreach ($rotator as $element) { ?>
								<tr class="<?= empty($element['active'])?'inactive':''; ?>">
									<td colspan="2" class="inner-block">
										<div class="img-health <?= (!ck_homepage::is_image_fully_qualified($element)&&is_file(__DIR__.'/../images/static'.$element['img_src']))||(ck_homepage::is_image_fully_qualified($element)&&CK\fn::remote_img_exists($element['absolute_img_ref']))?'found':'missing'; ?>">
											<input type="hidden" name="elements[<?= $element['site_homepage_id']; ?>][element]" value="rotator">
											<?= !ck_homepage::is_image_fully_qualified($element)?ck_homepage::$static_url:''; ?>
											<input type="text" name="elements[<?= $element['site_homepage_id']; ?>][img_src]" value="<?= $element['img_src']; ?>"><br>
											<a href="<?= $element['absolute_img_ref']; ?>" target="_blank">SEE IT &#8599;</a><!--br>
											<input type="file" name="elements[<?= $element['site_homepage_id']; ?>][file]"> Upload New File -->
										</div>
									</td>
									<td>
										<input type="text" name="elements[<?= $element['site_homepage_id']; ?>][alt_text]" value="<?= $element['alt_text']; ?>">
									</td>
									<td>
										Target Type:
										<select id="link-target-<?= $element['site_homepage_id']; ?>" name="elements[<?= $element['site_homepage_id']; ?>][link_target_type]">
											<option value="none" <?= $element['link_target_type']=='none'?'selected':''; ?>>NONE</option>
											<option value="category_id" <?= $element['link_target_type']=='category_id'?'selected':''; ?>>Category Page</option>
											<option value="direct_link" <?= $element['link_target_type']=='direct_link'?'selected':''; ?>>Direct Link</option>
										</select>
										<br>

										<div>
											<?php $category = $element['link_target_type']=='category_id'&&!empty($element['link_target'])?new ck_listing_category($element['link_target']):NULL; ?>
											<input id="link-target-<?= $element['site_homepage_id']; ?>-autocomplete-field" class="autocomplete-field" data-row-id="<?= $element['site_homepage_id']; ?>" type="text" name="elements[<?= $element['site_homepage_id']; ?>][link_target_full]" value="<?= !empty($category)?$category->get_header('categories_name'):$element['link_target']; ?>" autocomplete="off">

											<div class="autocomplete-lookup" id="link-target-<?= $element['site_homepage_id']; ?>-autocomplete-lookup"></div>

											<input type="hidden" name="elements[<?= $element['site_homepage_id']; ?>][link_target]" value="<?= $element['link_target']; ?>" id="link-target-<?= $element['site_homepage_id']; ?>-autocomplete-value">
										</div>
										<span id="category_url-<?= $element['site_homepage_id']; ?>"><?= !empty($category)?$category->get_url():''; ?></span>
									</td>
									<td>
										<input type="text" name="elements[<?= $element['site_homepage_id']; ?>][sort_order]" value="<?= $element['sort_order']; ?>" class="short-numeric-input">
									</td>
									<td>
										<input type="checkbox" name="elements[<?= $element['site_homepage_id']; ?>][active]" <?= !empty($element['active'])?'checked':''; ?>>
									</td>
									<td>
										<input type="checkbox" name="elements[<?= $element['site_homepage_id']; ?>][archived]">
									</td>
								</tr>
								<?php } ?>
								<tr>
									<th>New</th>
									<td>
										<input type="hidden" name="elements[newr][element]" value="rotator">
										<input type="text" name="elements[newr][img_src]" value=""><br>
										<!--br>
										<input type="file" name="elements[newr][file]"> Upload New File -->
									</td>
									<td>
										<input type="text" name="elements[newr][alt_text]" value="">
									</td>
									<td>
										Target Type:
										<select id="link-target-newr" name="elements[newr][link_target_type]">
											<option value="none">NONE</option>
											<option value="category_id">Category Page</option>
											<option value="direct_link">Direct Link</option>
										</select>
										<br>

										<div>
											<input id="link-target-newr-autocomplete-field" class="autocomplete-field" data-row-id="newr" type="text" name="elements[newr][link_target_full]" value="" autocomplete="off">

											<div class="autocomplete-lookup" id="link-target-newr-autocomplete-lookup"></div>

											<input type="hidden" name="elements[newr][link_target]" value="" id="link-target-newr-autocomplete-value">

											<span id="category_url-newr"></span>
										</div>
									</td>
									<td>
										<input type="text" name="elements[newr][sort_order]" value="" class="short-numeric-input">
									</td>
									<td colspan="2"></td>
								</tr>
							</tbody>
						</table>
					</form>
					<form style="display:block" action="/admin/homepage_manager.php" method="post">
						<input type="hidden" name="action" value="manage_elements">
						<input type="hidden" name="element-type" value="kickers">
						<h3>Kickers</h3>
						<table class="homepage-element rotator" cellpadding="0" cellspacing="0" border="0">
							<thead>
								<tr>
									<th colspan="2">Image Reference / Health</th>
									<th>Image Alt Text</th>
									<th>Link Target</th>
									<th>Sort Order</th>
									<th>Deactivate</th>
									<th>Remove</th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<td colspan="7"><input type="submit" value="Submit"></td>
								</tr>
							</tfoot>
							<tbody>
								<?php $kickers = $homepage->get_kickers();
								foreach ($kickers as $element) { ?>
								<tr class="<?= empty($element['active'])?'inactive':''; ?>">
									<td colspan="2" class="inner-block">
										<div class="img-health <?= (!ck_homepage::is_image_fully_qualified($element)&&is_file(__DIR__.'/../images/static'.$element['img_src']))||(ck_homepage::is_image_fully_qualified($element)&&CK\fn::remote_img_exists($element['absolute_img_ref']))?'found':'missing'; ?>">
											<input type="hidden" name="elements[<?= $element['site_homepage_id']; ?>][element]" value="kickers">
											<?= !ck_homepage::is_image_fully_qualified($element)?ck_homepage::$static_url:''; ?>
											<input type="text" name="elements[<?= $element['site_homepage_id']; ?>][img_src]" value="<?= $element['img_src']; ?>"><br>
											<a href="<?= $element['absolute_img_ref']; ?>" target="_blank">SEE IT &#8599;</a><!--br>
											<input type="file" name="elements[<?= $element['site_homepage_id']; ?>][file]"> Upload New File -->
										</div>
									</td>
									<td>
										<input type="text" name="elements[<?= $element['site_homepage_id']; ?>][alt_text]" value="<?= $element['alt_text']; ?>">
									</td>
									<td>
										Target Type:
										<select id="link-target-<?= $element['site_homepage_id']; ?>" name="elements[<?= $element['site_homepage_id']; ?>][link_target_type]">
											<option value="none" <?= $element['link_target_type']=='none'?'selected':''; ?>>NONE</option>
											<option value="category_id" <?= $element['link_target_type']=='category_id'?'selected':''; ?>>Category Page</option>
											<option value="direct_link" <?= $element['link_target_type']=='direct_link'?'selected':''; ?>>Direct Link</option>
										</select>
										<br>

										<div>
											<?php $category = $element['link_target_type']=='category_id'?new ck_listing_category($element['link_target']):NULL; ?>
											<input id="link-target-<?= $element['site_homepage_id']; ?>-autocomplete-field" class="autocomplete-field" data-row-id="<?= $element['site_homepage_id']; ?>" type="text" name="elements[<?= $element['site_homepage_id']; ?>][link_target_full]" value="<?= !empty($category)?$category->get_header('categories_name'):$element['link_target']; ?>" autocomplete="off">

											<div class="autocomplete-lookup" id="link-target-<?= $element['site_homepage_id']; ?>-autocomplete-lookup"></div>

											<input type="hidden" name="elements[<?= $element['site_homepage_id']; ?>][link_target]" value="<?= $element['link_target']; ?>" id="link-target-<?= $element['site_homepage_id']; ?>-autocomplete-value">
										</div>
										<span id="category_url-<?= $element['site_homepage_id']; ?>"><?= !empty($category)?$category->get_url():''; ?></span>
									</td>
									<td>
										<input type="text" name="elements[<?= $element['site_homepage_id']; ?>][sort_order]" value="<?= $element['sort_order']; ?>" class="short-numeric-input">
									</td>
									<td>
										<input type="checkbox" name="elements[<?= $element['site_homepage_id']; ?>][active]" <?= !empty($element['active'])?'checked':''; ?>>
									</td>
									<td>
										<input type="checkbox" name="elements[<?= $element['site_homepage_id']; ?>][archived]">
									</td>
								</tr>
								<?php } ?>
								<tr>
									<th>New</th>
									<td>
										<input type="hidden" name="elements[newk][element]" value="kickers">
										<input type="text" name="elements[newk][img_src]" value=""><br>
										<!--br>
										<input type="file" name="elements[newk][file]"> Upload New File -->
									</td>
									<td>
										<input type="text" name="elements[newk][alt_text]" value="">
									</td>
									<td>
										Target Type:
										<select id="link-target-newk" name="elements[newk][link_target_type]">
											<option value="none">NONE</option>
											<option value="category_id">Category Page</option>
											<option value="direct_link">Direct Link</option>
										</select>
										<br>

										<div>
											<input id="link-target-newk-autocomplete-field" class="autocomplete-field" data-row-id="newk" type="text" name="elements[newk][link_target_full]" value="" autocomplete="off">

											<div class="autocomplete-lookup" id="link-target-newk-autocomplete-lookup"></div>

											<input type="hidden" name="elements[newk][link_target]" value="" id="link-target-newk-autocomplete-value">

											<span id="category_url-newk"></span>
										</div>
									</td>
									<td>
										<input type="text" name="elements[newk][sort_order]" value="" class="short-numeric-input">
									</td>
									<td colspan="2"></td>
								</tr>
							</tbody>
						</table>
					</form>
					<div id="manage-showcase-section">
						<form action="/admin/homepage_manager.php" method="post">
							<input type="hidden" name="action" value="manage_elements">
							<input type="hidden" name="element-type" value="showcases">
							<h3>Showcases</h3>
							<table class="homepage-element showcase">
								<thead>
									<tr>
										<th>Title</th>
										<th id="html-input-header">HTML Input</th>
										<th>Product IDs</th>
										<th>Active</th>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<td colspan="7"><button type="submit">Submit</button></td>
									</tr>
								</tfoot>
								<tbody>
								<?php if ($homepage->has_showcases()) {
									foreach ($homepage->get_showcases() as $showcase) { ?>
									<tr>
										<td>
											<input type="hidden" name="elements[<?= $showcase['site_homepage_id']; ?>][element]" value="showcases">
											<input type="text" class="showcase-title" name="elements[<?= $showcase['site_homepage_id']; ?>][title]" value="<?= $showcase['title']; ?>">
										</td>
										<td class="showcase-html-td">
											<textarea class="showcase-html code-editor" name="elements[<?= $showcase['site_homepage_id']; ?>][html]"><?= $showcase['html']; ?></textarea>
										</td>
										<td>
											<input type="text" class="product-ids-for-showcase" name="elements[<?= $showcase['site_homepage_id']; ?>][product_ids]" value="<?= $showcase['product_ids']; ?>">
										</td>
										<td>
											<input type="checkbox" class="showcase-status" name="elements[<?= $showcase['site_homepage_id']; ?>][active]" <?=$showcase['active']==1?'checked':'';?>>
										</td>
									</tr>
								<?php }
								} ?>
									<tr>
										<th colspan="4">New Showcase</th>
									</tr>
									<tr>
										<td>
											<input type="hidden" name="elements[news][element]" value="showcases">
											<input type="text" class="showcase-title" name="elements[news][title]">
										</td>
										<td class="showcase-html-td">
											<textarea id="new-showcase-textarea" class="showcase-html code-editor" name="elements[news][html]"></textarea>
										</td>
										<td>
											<input type="text" class="product-ids-for-showcase" name="elements[news][product_ids]">
										</td>
										<td></td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<script>
						jQuery('.autocomplete-field').keyup(function() {
							var row_id = jQuery(this).attr('data-row-id');
							var ac_id = 'link-target-'+row_id; // main autocomplete ID
							if (jQuery('#'+ac_id).val() != 'category_id') {
								jQuery('#'+ac_id+'-autocomplete-value').val(jQuery(this).val());
								return;
							}
							if (jQuery(this).val().length < 3) return;							

							jQuery.ajax({
								url: '/admin/homepage_manager.php',
								type: 'get',
								dataType: 'json',
								data: {
									ajax: 1,
									action: 'category_lookup',
									field: jQuery(this).val()
								},
								success: function(data) {
									var $container = jQuery('#'+ac_id+'-autocomplete-lookup').html('');
									for (var i=0; i<data.rows.length; i++) {
										var $row = jQuery('<a href="#" class="entry" data-entry-value="'+data.rows[i].value+'" data-entry-result="'+data.rows[i].result+'" data-entry-url="'+data.rows[i].url+'">'+data.rows[i].label+'</a>');
										$row.click(select_autocomplete_entry(row_id));
										$container.append($row);
									}
									$container.show();
								}
							});
						});
						jQuery('body').click(function() {
							jQuery('.autocomplete-lookup').hide().html('');
						});
						function select_autocomplete_entry(row_id) {
							var ac_id = 'link-target-'+row_id; // main autocomplete ID
							return function(e) {
								e.preventDefault();
								jQuery('#'+ac_id+'-autocomplete-field').val(jQuery(this).attr('data-entry-result'));
								jQuery('#'+ac_id+'-autocomplete-value').val(jQuery(this).attr('data-entry-value'));
								jQuery('#category_url-'+row_id).html(jQuery(this).attr('data-entry-url'));
							}
						}
					</script>
				</td>
			</tr>
		</table>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.38.0/codemirror.js"></script>
		<script>
			var code_editors = document.getElementsByClassName('code-editor');
			for(var i = 0; i < code_editors.length; i++) {
				CodeMirror.fromTextArea(code_editors[i], {
					tabSize: 4,
					indenetWithTabs: true,
					lineNumbers: true,
					lineWrapping: true
				});
			}
		</script>
	</body>
</html>
