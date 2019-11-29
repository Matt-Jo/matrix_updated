<?php ini_set('max_execution_time', 1200); ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script src="includes/menu.js"></script>
	<script src="includes/general.js"></script>

	<script src="../includes/javascript/jquery-1.4.2.min.js"></script>
	<script src="../includes/javascript/jquery.autocomplete.min.js"></script>

	<style type="text/css" media="all">
		@import url('includes/stylesheet.css');
	</style>
	<script>
		function urlencode(str) {
			str = escape(str);
			str = str.replace('+', '%2B');
			str = str.replace('%20', '+');
			str = str.replace('*', '%2A');
			str = str.replace('/', '%2F');
			str = str.replace('@', '%40');
			return str;
		}
		jQuery(document).ready(function($) {
			$('#ipn_search').autocomplete("ipn_autoCompleteQuery2.php", {
				'extraParams': {'results': 'name'},
				'matchContains': true,
				//'mustMatch': true,
				'cacheLength': 100,
				'scrollHeight': 500,
				'max': 25
			}).result(function(event, data, formatted) {
				location.href = "categories.php?ipn=" + urlencode(data);
			});
		});
	</script>

	<script>
		function popupWindow(url) {
			window.open(url,'popupWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,copyhistory=no,width=100,height=100,screenX=150,screenY=150,top=150,left=150')
		}

	</script>

	<?php
	// WebMakers.com Added: Java Scripts - popup window
	include(DIR_WS_INCLUDES.'javascript/'.'webmakers_added_js.php')
	?>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onLoad="SetFocus();">
	<div id="spiffycalendar" class="text"></div>
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
				<table border="0" width="100%" cellspacing="0" cellpadding="2">
					<?php if ($_GET['action'] == 'new_category' || $_GET['action'] == 'edit_category') {
						if (!empty($_GET['categories_id']) && empty($_POST)) {
							$category = new ck_listing_category($_GET['categories_id']);
							$cInfo = (object)$category->get_header();
						}
						elseif (!empty($_POST)) {
							$cInfo = (object)$_POST;
							$categories_name = $_POST['categories_name'];
							$categories_heading_title = $_POST['categories_heading_title'];
							$categories_description = $_POST['categories_description'];
							$categories_head_title_tag = $_POST['categories_head_title_tag'];
							$categories_head_desc_tag = $_POST['categories_head_desc_tag'];
							$categories_url = $_POST['categories_url'];

							$google_category_id = $_POST['google_category_id'];

							$topnav_redirect = $_POST['topnav_redirect'];

							$use_seo_urls = $__FLAG['use_seo_urls'];
							$seo_url_text = $_POST['seo_url_text'];
							$seo_url_parent_text = $_POST['seo_url_parent_text'];

							$product_finder_description = $_POST['product_finder_description'];
							$product_finder_image = $_POST['product_finder_image'];
							$product_finder_hide = $_POST['product_finder_hide'];
						}
						else {
							$cInfo = new stdClass;
						}

						$text_new_or_edit = $_GET['action']=='new_category'?'New Category':'Edit Category'; ?>
					<tr>
						<td class="pageHeading"><?= $text_new_or_edit; ?></td>
					</tr>
					<tr>
						<td>
							<form name="new_category" action="/admin/categories.php?cPath=<?= $cPath; ?>&categories_id=<?= @$_GET['categories_id']; ?>&action=new_category_preview" method="post" enctype="multipart/form-data">
								<table border="0" cellspacing="0" cellpadding="2">
									<?php if (!empty($category)) { ?>
									<tr>
										<td class="main">URL</td>
										<td class="main"><?= $category->get_url(); ?> <a href="https://<?= FQDN.$category->get_url(); ?>" target="_blank">[View]</a></td>
									</tr>
										<?php if ($category->has_primary_container()) { ?>
									<tr>
										<td class="main">Original URL</td>
										<td class="main"><?= $category->get_original_url(); ?></td>
									</tr>
									<?php }
									//MMD - D-44 - check for active products in this category or it's subcategories that are not in another active category.
									// If we find one of these products and this category is not inactive, disable this checkbox and leave a message
									$disable_inactive_checkbox = false;
									if (empty($cInfo->inactive) && $_REQUEST['action'] != 'new_category') {
										$thisCategory = new ck_listing_category(@$cInfo->categories_id);

										if (!$thisCategory->is_redundant_direct()) $disable_inactive_checkbox = TRUE;
									} ?>
									<?php if ($disable_inactive_checkbox) { ?>
									<tr>
										<td class="main" colspan="2">
											<span style="color:#777777; font-size:10px;">This category cannot be made inactive or redirected because it or one of its subcategories contain an active product that is not in another active category.</span>
										</td>
									</tr>
										<?php }
									} ?>
									<tr>
										<td class="main">Redirect To:</td>
										<td class="main">
											<select name="redirect_container_id" <?= !empty($disable_inactive_checkbox)?'disabled':''; ?>>
												<option value="">No Redirect</option>
												<?php foreach(ck_listing_category::get_all() as $category_option) {
													$selected = FALSE;
													if (!empty($category) && $category->has_primary_container() && $category_option->id() == $category->get_primary_container()['container_id']) $selected = TRUE; ?>
													<option value="<?= $category_option->id(); ?>" <?= $selected?'selected':''; ?>>
														<?= $category_option->get_header('categories_name'); ?> (<?= $category_option->get_url(); ?>)
													</option>
												<?php } ?>
											</select>
										</td>
									</tr>
									<tr>
										<td class="main">Category Name</td>
										<td class="main">
											<input type="text" name="categories_name[1]" value="<?= !empty($categories_name[1])?stripslashes($categories_name[1]):@$cInfo->categories_name; ?>">
											<!--Make Inactive:
											<input type="checkbox" name="inactive" <?= !empty($cInfo->inactive)&&@$cInfo->inactive=='1'?'checked="checked"':''; ?> <?= !empty($disable_inactive_checkbox)?'disabled':''; ?>-->
										</td>
									</tr>

									<tr>
										<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
									</tr>
									<tr>
										<td class="main">Category Heading Title</td>
										<td class="main">
											<input type="text" name="categories_heading_title[1]" value="<?= !empty($categories_heading_title[1])?stripslashes($categories_heading_title[1]):@$cInfo->categories_heading_title; ?>">
										</td>
									</tr>
									<tr>
										<td colspan="2"><hr></td>
									</tr>
									<tr>
										<td class="main" valign="top">Google Category</td>
										<td class="main" valign="top">
											<style>
												#google_category_lookup { display:none; background-color:#fff; border:1px solid #5cc; position:absolute; /*max-width:1000px;*/ max-height:700px; overflow-y:scroll; }
												#google_category_lookup .entry { margin:0px; padding:4px 6px; font-size:.5vw; white-space:nowrap; border-bottom:1px solid #999; display:block; }
												#google_category_lookup .entry:hover { border-radius:3px; border-bottom-color:transparent; background:linear-gradient(#6ff, #7cf); }
											</style>
											<?php $last_category = NULL;
											$category_list = [];
											if ($category = prepared_query::fetch('SELECT * FROM google_categories WHERE google_category_id = :google_category_id', cardinality::ROW, [':google_category_id' => @$cInfo->google_category_id])) {
												for ($i=0; $i<8; $i++) {
													if (empty($category['category_'.$i])) continue;
													$category_list[] = $last_category = $category['category_'.$i];
													if ($i > 0) $last_category = '> '.$last_category;
												}
											} ?>
											<input type="text" name="google_category" autocomplete="off" value="<?= $last_category; ?>" id="google_category">
											<div id="google_category_lookup"></div>
											<input type="hidden" name="google_category_id" value="<?= @$cInfo->google_category_id; ?>" id="google_category_id">
											<script>
												jQuery('#google_category').keyup(function() {
													if (jQuery(this).val().length < 3) return;
													jQuery.ajax({
														url: '/admin/categories.php',
														type: 'get',
														dataType: 'json',
														data: {
															ajax: 1,
															action: 'google_category_lookup',
															field: jQuery(this).val()
														},
														success: function(data) {
															var $container = jQuery('#google_category_lookup').html('');
															for (var i=0; i<data.rows.length; i++) {
																var $row = jQuery('<a href="#" class="entry" data-entry-value="'+data.rows[i].value+'" data-entry-result="'+data.rows[i].result+'">'+data.rows[i].label+'</a>');
																$row.click(select_google_category);
																$container.append($row);
															}
															$container.show();
														}
													});
												});
												jQuery('body').click(function() {
													jQuery('#google_category_lookup').hide().html('');
												});
												function select_google_category(e) {
													e.preventDefault();
													jQuery('#google_category').val(jQuery(this).attr('data-entry-result'));
													jQuery('#google_category_id').val(jQuery(this).attr('data-entry-value'));
												}
											</script>
											<a href="https://support.google.com/merchants/answer/1705911?hl=en" target="_blank" style="color:0c0;">See All Options &#8599;</a>
											<?php if (!empty($category_list)) { ?>
											<br><?= implode(' > ', $category_list); ?>
											<?php } ?>
										</td>
									</tr>
									<tr>
										<td colspan="2" class="main"><hr></td>
									</tr>
									<tr>
										<td class="main" valign="top">Use New SEO Naming</td>
										<td class="main" valign="top"><input type="checkbox" name="use_seo_urls" <?= @$cInfo->use_seo_urls?'checked':''; ?>></td>
									</tr>
									<tr>
										<td class="main" valign="top">New SEO URL Text</td>
										<td class="main" valign="top"><input type="text" name="seo_url_text" value="<?= @$cInfo->seo_url_text; ?>"></td>
									</tr>
									<tr>
										<td class="main" valign="top">New SEO URL Parent Text</td>
										<td class="main" valign="top"><input type="text" name="seo_url_parent_text" value="<?= @$cInfo->seo_url_parent_text; ?>"></td>
									</tr>
									<tr>
										<td colspan="2" class="main"><hr></td>
									</tr>
									<tr>
										<td class="main" valign="top">Title Tag (Tab Title)</td>
										<td class="main" valign="top">
											<textarea name="categories_head_title_tag[1]" wrap="soft" style="width:auto;" cols="40" rows="2"><?= !empty($categories_head_title_tag[1])?stripslashes($categories_head_title_tag[1]):@$cInfo->categories_head_title_tag; ?></textarea>
											<br>
											<small>Please Note - This field is used to build the old style SEO friendly URL</small>
										</td>
									</tr>
									<tr>
										<td colspan="2" class="main"><hr></td>
									</tr>
									<tr>
										<td class="main" valign="top">Custom URL Redirect</td>
										<td class="main" valign="top">
											<input type="text" name="topnav_redirect" value="<?= @$cInfo->topnav_redirect; ?>" style="background-color:#ffc;"><br>
											<small>Warning - do not use without consulting Dev</small>
										</td>
									</tr>
									<tr>
										<td colspan="2" class="main"><hr></td>
									</tr>
									<tr>
										<td class="main" valign="top">Product Finder Description:</td>
										<td class="main" valign="top">
											<textarea name="product_finder_desc" wrap="soft" style="width:auto;" cols="20" rows="4"><?= @$cInfo->product_finder_description; ?></textarea>
										</td>
									</tr>
									<tr>
										<td class="main" valign="top">Product Finder Image:</td>
										<td class="main" valign="top">
											<input type="file" name="product_finder_image_new"><br>
											Current: [<?= @$cInfo->product_finder_image; ?>]<input type="hidden" name="product_finder_image" value="<?= @$cInfo->product_finder_image; ?>">
										</td>
									</tr>
									<tr>
										<td class="main" valign="top">Product Finder Hide:</td>
										<td class="main" valign="top">
											<input type="checkbox" name="product_finder_hide" <?= @$cInfo->product_finder_hide=='on'||@$cInfo->product_finder_hide==1?'checked':''; ?>>
										</td>
									</tr>
									<tr>
										<td colspan="2" class="main"><hr></td>
									</tr>
									<tr>
										<td class="main" valign="top">eBay Category Mapping:</td>
										<td class="main" valign="top">
											<input type="text" name="ebay_category1_id" value="<?= @$cInfo->ebay_category1_id; ?>">
										</td>
									</tr>
									<tr>
										<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
									</tr>
									<tr>
										<td class="main" valign="top">eBay Shop Category Mapping</td>
										<td class="main" valign="top">
											<?php if (is_array($ebayShopCatArr)) { ?>
											<select name="ebay_shop_category1_id">
												<?php $selectxt = "";
												foreach ($ebayShopCatArr as $catid => $catname) {
													$selectxt = (@$cInfo->ebay_shop_category1_id == $catid)?'selected':''; ?>
												<option value="<?= $catid; ?>" <?= $selectxt; ?>> <?= $catname; ?> </option>
												<?php } ?>
											</select>
											<?php } ?>
										</td>
									</tr>
									<tr>
										<td colspan="2" class="main"><hr></td>
									</tr>
									<tr>
										<td class="main" valign="top">Category Description Meta Tag</td>
										<td class="main" valign="top">
											<textarea name="categories_head_desc_tag[1]" wrap="soft" style="width:auto;" cols="50" rows="10"><?= !empty($categories_head_desc_tag[1])?stripslashes($categories_head_desc_tag[1]):@$cInfo->categories_head_desc_tag; ?></textarea>
										</td>
									<tr>
									<tr>
										<td colspan="2" class="main"><hr></td>
									</tr>
									<tr>
										<td class="main">Canonical URL Category:</td>
										<td class="main">
											<script>
												jQuery(document).ready(function() {
													jQuery('#category_selector').change(function() {
														var category_id = jQuery(this).val();
														jQuery(this).find('option').each(function() {
															if (jQuery(this).attr('value') == category_id) return;
															jQuery(this).remove();
														});
														if (category_id == -1) {
															// we're backing up
															category_list.selected_list.pop();
															if (category_list.selected_list.length) {
																// there's a previously selected category to back up to
																category_id = category_list.selected_list[category_list.selected_list.length - 1];
																if (category_list.selected_list.length > 1) {
																	previous_category_id = category_list.selected_list[category_list.selected_list.length - 2];
																	for (var j=0; j<category_list.selections[previous_category_id].length; j++) {
																		if (category_list.selections[previous_category_id][j]['id'] == category_id) {
																			jQuery(this).prepend('<option value="'+category_id+'">'+category_list.selections[previous_category_id][j]['name']+' ['+(category_list.selected_list.length-1)+']</option>');
																		}
																	}
																}
																else {
																	for (var j=0; j<category_list.top_level.length; j++) {
																		if (category_list.top_level[j]['id'] == category_id) {
																			jQuery(this).prepend('<option value="'+category_id+'">'+category_list.top_level[j]['name']+'</option>');
																		}
																	}
																}
																jQuery(this).val(category_id);
																for (var i=0; i<category_list.selections[category_id].length; i++) {
																	jQuery(this).append('<option value="'+category_list.selections[category_id][i]['id']+'">'+category_list.selections[category_id][i]['name']+' ['+category_list.selected_list.length+']</option>');
																}
															}
															else {
																// we're back at the top level
																jQuery(this).find('option').remove();
																jQuery(this).append('<option value="0">None</option>');
																jQuery(this).val('0');
																for (var i=0; i<category_list.top_level.length; i++) {
																	jQuery(this).append('<option value="'+category_list.top_level[i]['id']+'">'+category_list.top_level[i]['name']+'</option>');
																}
															}
														}
														else {
															// we selected a category
															category_list.selected_list.push(category_id);
															jQuery(this).append('<option value="-1">Back One Level</option>');
															for (var i=0; i<category_list.selections[category_id].length; i++) {
																jQuery(this).append('<option value="'+category_list.selections[category_id][i]['id']+'">'+category_list.selections[category_id][i]['name']+' ['+category_list.selected_list.length+']</option>');
															}
														}
													});
												});

												var category_list = { selected_list: [], selections: {}, top_level: [] };
											</script>
											<?php
											$top_level = [];
											$selections = [];
											$selected_path = [];
											?>
											<select id="category_selector" name="canonical_category_id" size="1">
											<?php $categories = prepared_query::fetch('SELECT c.categories_id, cd.categories_name, c.parent_id, COUNT(ptc.products_id) as pcount FROM categories_description cd JOIN categories c ON cd.categories_id = c.categories_id LEFT JOIN products_to_categories ptc ON c.categories_id = ptc.categories_id WHERE c.categories_id IN (SELECT DISTINCT parent_id as categories_id FROM categories UNION SELECT DISTINCT categories_id FROM products_to_categories) GROUP BY c.categories_id, cd.categories_name, c.parent_id ORDER BY cd.categories_name, c.categories_id', cardinality::SET);
											foreach ($categories as $category) {
												$cname = $category['categories_name'];
												if (empty($category['parent_id'])) {
													$top_level[] = array('id' => $category['categories_id'], 'name' => $cname);
												}
												else {
													if (!isset($selections[$category['parent_id']])) $selections[$category['parent_id']] = [];
													$selections[$category['parent_id']][] = array('id' => $category['categories_id'], 'name' => $cname);
												}
											}
											if (empty($cInfo->canonical_id) || @$cInfo->canonical_category_id == '0') { ?>
												<option value="0">None</option>
												<?php foreach ($top_level as $unused => $top_cat) { ?>
												<option value="<?= $top_cat['id']; ?>"><?= $top_cat['name']; ?></option>
												<?php }
											}
											else { //canonical_id is set
												//calculated the selected path
												$sel_cat = new ck_listing_category($cInfo->canonical_category_id);
												$selected_path[] = @$cInfo->canonical_category_id;
												$path_cat = $sel_cat;
												while ($path_cat->get_header('parent_id') != 0) {
													$selected_path = array_merge(array($path_cat->get_header('parent_id')), $selected_path);
													$path_cat = new ck_listing_category($path_cat->get_header('parent_id'));
												} ?>
												<option value="<?= $sel_cat->id(); ?>" selected><?= $sel_cat->get_header('categories_name').' ['.(count($selected_path) - 1).']';?></option>
												<option value="-1">Back One Level</option>
												<?php
												foreach ($selections[$sel_cat->id()] as $cat_data) { ?>
												<option value="<?= $cat_data['id']; ?>"><?= $cat_data['name'].' ['.count($selected_path).']';?></option>
												<?php }
											} ?>
											</select>
											<?php if (!empty($top_level)) { ?>
											<script>
												category_list.top_level = <?= json_encode($top_level); ?>;
												category_list.selections = <?= json_encode($selections); ?>;
												category_list.selected_list = <?= json_encode($selected_path);?>;
											</script>
											<?php } ?>
											<span style="font-size: 10px;"><br>You can get to sub-categories by going back to the dropdown once you've made a selection. A subcategory's depth is displayed inside []. Please do not set this field unless you are certain it needs to be set.</span>
										</td>
									</tr>
									<tr>
										<td colspan="2" class="main"><hr></td>
									</tr>
									<tr>
										<td class="main" valign="top">Use Category Description</td>
										<td class="main" valign="top"><input type="checkbox" name="use_categories_description" <?= @$cInfo->use_categories_description?'checked':''; ?>></td>
									</tr>
									<tr>
										<td class="main" valign="top">Category Heading Description</td>
										<td class="main" valign="top">
											<textarea id="categories_description[1]" name="categories_description[1]" wrap="soft" cols="70" rows="15"><?= !empty($categories_description[1])?stripslashes($categories_description[1]):@$cInfo->categories_description; ?></textarea>
										</td>
									</tr>
									<tr>
										<td class="main" valign="top">Product IDs for Category Description</td>
										<td class="main" valign="top"><input type="text" name="categories_description_product_ids" value="<?= @$cInfo->categories_description_product_ids; ?>"></td>
									</tr>
									<tr>
										<td class="main">Category Image:</td>
										<td class="main">
											<?php if (!empty($cInfo->categories_image)) { ?>
											<img src="/images/<?= $cInfo->categories_image; ?>"><br>
											[<input type="checkbox" name="remove_category_image"> Remove Category Image]<br>
											<?php }
											else { ?>
											No Category Image Set<br>
											<?php } ?>
											<input type="file" name="categories_image">
											<input type="hidden" name="categories_previous_image" value="<?= @$cInfo->categories_image; ?>">
										</td>
									</tr>
									<tr>
										<td colspan="2" class="main"><hr></td>
									</tr>
									<tr>
										<td class="main" valign="top">Use Categories Bottom Text</td>
										<td class="main" valign="top"><input type="checkbox" name="use_categories_bottom_text" <?= CK\fn::check_flag(@$cInfo->use_categories_bottom_text)?'checked':''; ?>></td>
									</tr>
									<tr>
										<td class="main" valign="top">Categories Bottom Text</td>
										<td class="main" valign="top"><textarea id="categories_bottom_text" name="categories_bottom_text" wrap="soft" cols="70" rows="15"><?= @$cInfo->categories_bottom_text; ?></textarea>
									</tr>
									<tr>
										<td class="main" valign="top">Product IDs for Bottom Text</td>
										<td class="main" valign="top"><input type="text" name="categories_bottom_text_product_ids" value="<?= @$cInfo->categories_bottom_text_product_ids; ?>"></td>
									</tr>
									<tr>
										<td colspan="2" class="main"><hr></td>
									</tr>
									<tr>
										<td class="main">Category Promo Image:</td>
										<td class="main">
											<?php if (!empty($cInfo->promo_image)) { ?>
											<img src="/images/<?= $cInfo->promo_image; ?>"><br>
											[<input type="checkbox" name="remove_promo_image"> Remove Promo Image]<br>
											<?php }
											else { ?>
											No Promo Image Set<br>
											<?php } ?>
											<input type="file" name="promo_image">
											<input type="hidden" name="previous_promo_image" value="<?= @$cInfo->promo_image; ?>">
										</td>
									</tr>
									<tr>
										<td class="main">Category Promo Link:</td>
										<td class="main">
											<?= !empty($cInfo->promo_link)?'<a href="'.$cInfo->promo_link.'" '.(@$cInfo->promo_offsite?'target="_blank"':'').'>'.$cInfo->promo_link.'</a>':'No Promo Link Set'; ?><br>
											<input type="text" name="promo_link" value="<?= @$cInfo->promo_link; ?>" style="width:300px;">
											[<input type="checkbox" name="promo_offsite" <?= @$cInfo->promo_offsite?'checked':''; ?>> force open in new tab]
										</td>
									</tr>
									<tr>
										<td colspan="2" class="main"><hr></td>
									</tr>
									<tr>
										<td class="main">Sort Order:</td>
										<td class="main"><input type="text" name="sort_order" value="<?= @$cInfo->sort_order; ?>" size="2"></td>
									</tr>
									<tr>
										<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
									</tr>
									<tr>
										<td colspan="2" class="main" align="right">
											<input type="hidden" name="categories_date_added" value="<?= @$cInfo->date_added?@$cInfo->date_added->format('Y-m-d'):date('Y-m-d'); ?>">
											<input type="hidden" name="parent_id" value="<?= @$cInfo->parent_id; ?>">
											<input type="image" src="includes/languages/english/images/buttons/button_preview.gif" alt="Preview" title="Preview">&nbsp;&nbsp;<a href="/admin/categories.php?cPath=<?= $cPath; ?>&categories_id=<?= @$_GET['categories_id']; ?>"><img src="includes/languages/english/images/buttons/button_cancel.gif" alt="Cancel" title="Cancel"></a>
										</td>
									</tr>
								</table>
							</form>
						</td>
					</tr>
					<?php }
					elseif ($_GET['action'] == 'new_category_preview') {
						if (!empty($_POST)) {
							$cInfo = (object)$_POST;
							$categories_name = $_POST['categories_name'];
							$categories_heading_title = $_POST['categories_heading_title'];
							$use_categories_description = $__FLAG['use_categories_description'];
							$categories_description = $_POST['categories_description'];
							$categories_description_product_ids = $_POST['categories_description_product_ids'];
							$use_categories_bottom_text = $__FLAG['use_categories_bottom_text'];
							$categories_bottom_text = $_POST['categories_bottom_text'];
							$categories_bottom_text_product_ids = $_POST['categories_bottom_text_product_ids'];
							$categories_template_id = !empty($_POST['categories_template_id'])?$_POST['categories_template_id']:NULL;
							$categories_head_title_tag = $_POST['categories_head_title_tag'];
							$categories_head_desc_tag = $_POST['categories_head_desc_tag'];
							$canonical_category_id = $_POST['canonical_category_id'];

							$google_category_id = $_POST['google_category_id'];

							$topnav_redirect = $_POST['topnav_redirect'];

							$use_seo_urls = $__FLAG['use_seo_urls'];
							$seo_url_text = $_POST['seo_url_text'];
							$seo_url_parent_text = $_POST['seo_url_parent_text'];

							$product_finder_desc = $_POST['product_finder_desc'];
							$product_finder_hide = !empty($_POST['product_finder_hide'])?$_POST['product_finder_hide']:NULL;
							$product_finder_image_new = new upload('product_finder_image_new');
							$product_finder_image_new->set_destination(DIR_FS_CATALOG_IMAGES.'/');
							if ($product_finder_image_new->parse() && $product_finder_image_new->save()) {
								$product_finder_image = $product_finder_image_new->filename;
							}
							else {
								$product_finder_image = $_POST['product_finder_image'];
							}

							$ebay_category1_id = $_POST['ebay_category1_id'];
							$ebay_shop_category1_id = $_POST['ebay_shop_category1_id'];

							// copy image only if modified
							$categories_image = new upload('categories_image');
							$categories_image->set_destination(DIR_FS_CATALOG_IMAGES);
							if ($categories_image->parse() && $categories_image->save()) {
								$categories_image_name = $categories_image->filename;
							}
							elseif (!empty($_POST['remove_category_image'])) {
								$categories_image_name = NULL;
							}
							else {
								$categories_image_name = $_POST['categories_previous_image'];
							}

							$promo_image = new upload('promo_image');
							$promo_image->set_destination(DIR_FS_CATALOG.'images/static/img/');
							if ($promo_image->parse() && $promo_image->save()) {
								$promo_image_name = 'static/img/'.$promo_image->filename;
							}
							elseif (!empty($_POST['remove_promo_image'])) {
								$promo_image_name = NULL;
							}
							else {
								$promo_image_name = $_POST['previous_promo_image'];
							}
							$promo_link = $_POST['promo_link'];
							$parts = parse_url($promo_link);
							if ($parts && !empty($parts['host']) && $parts['host'] == 'www.cablesandkits.com') {
								$promo_link = !empty($parts['path'])?$parts['path']:'/';
								if (!empty($parts['query'])) $promo_link .= '?'.$parts['query'];
								if (!empty($parts['fragment'])) $promo_link .= '#'.$parts['fragment'];
							}
						}
						else {
							$category = new ck_listing_category($_GET['categories_id']);
							//$category = prepared_query::fetch('SELECT c.categories_id, cd.language_id, cd.categories_name, cd.categories_heading_title, cd.categories_description, cd.categories_head_title_tag, cd.categories_head_desc_tag, c.categories_image, c.sort_order, c.date_added, c.last_modified, cd.product_finder_desc, cd.product_finder_image, cd.product_finder_hide, c.google_category_id, c.topnav_redirect, c.promo_image, c.promo_link, c.promo_offsite, c.use_seo_urls, c.seo_url_text, c.seo_url_parent_text FROM categories c JOIN categories_description cd ON c.categories_id = cd.categories_id WHERE c.categories_id = ', cardinality::ROW, array($_GET['categories_id']));

							$cInfo = (object)$category->get_header();
							$categories_image_name = @$cInfo->categories_image;
							$product_finder_image = @$cInfo->product_finder_image;
						}

						$form_action = ($_GET['categories_id'])?'update_category':'insert_category';

						@$cInfo->categories_name = $categories_name[1];
						@$cInfo->categories_heading_title = $categories_heading_title[1];
						@$cInfo->categories_description = $categories_description[1];
						@$cInfo->category_template_id = @$category_template_id[1];
						@$cInfo->categories_head_title_tag = $categories_head_title_tag[1];
						@$cInfo->categories_head_desc_tag = $categories_head_desc_tag[1]; ?>
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td class="pageHeading"><?= @$cInfo->categories_heading_title; ?></td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
					</tr>
					<tr>
						<td class="main"><?= tep_image(DIR_WS_CATALOG_IMAGES.$categories_image_name, @$cInfo->categories_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'align="right" hspace="5" vspace="5"').@$cInfo->categories_description; ?></td>
					</tr>
					<tr>
						<td align="right" class="smallText">
							<form name="<?= $form_action; ?>" action="/admin/categories.php?cPath=<?= $cPath; ?>&categories_id=<?= $_GET['categories_id']; ?>&action=<?= $form_action; ?>" method="post" enctype="multipart/form-data">
								<?php /* Re-Post all POST'ed variables */
								foreach ($_POST as $key => $value) {
									if (!is_array($_POST[$key])) {
										echo tep_draw_hidden_field($key, htmlspecialchars(stripslashes($value)));
									}
								}

								echo tep_draw_hidden_field('categories_name[1]', htmlspecialchars(stripslashes($categories_name[1])));
								echo tep_draw_hidden_field('categories_heading_title[1]', htmlspecialchars(stripslashes($categories_heading_title[1])));
								echo tep_draw_hidden_field('categories_description[1]', htmlspecialchars(stripslashes($categories_description[1])));
								echo tep_draw_hidden_field('categories_head_title_tag[1]', htmlspecialchars(stripslashes($categories_head_title_tag[1])));
								echo tep_draw_hidden_field('categories_head_desc_tag[1]', htmlspecialchars(stripslashes($categories_head_desc_tag[1])));
								echo tep_draw_hidden_field('X_categories_image', stripslashes($categories_image_name));
								echo tep_draw_hidden_field('categories_image', stripslashes($categories_image_name));

								if ($use_seo_urls) echo tep_draw_hidden_field('use_seo_urls', 1);
								echo tep_draw_hidden_field('seo_url_text', stripslashes($seo_url_text));
								echo tep_draw_hidden_field('seo_url_parent_text', stripslashes($seo_url_parent_text));

								echo tep_draw_hidden_field('X_product_finder_image', stripslashes($product_finder_image));
								echo tep_draw_hidden_field('product_finder_image', stripslashes($product_finder_image));

								echo tep_draw_hidden_field('ebay_category1_id', stripslashes($ebay_category1_id));
								echo tep_draw_hidden_field('ebay_shop_category1_id', stripslashes($ebay_shop_category1_id));

								echo tep_image_submit('button_back.gif', IMAGE_BACK, 'name="edit"').'&nbsp;&nbsp;';

								if (!empty($_GET['categories_id'])) {
									echo tep_image_submit('button_update.gif', IMAGE_UPDATE);
								}
								else {
									echo tep_image_submit('button_insert.gif', IMAGE_INSERT);
								}
								echo '&nbsp;&nbsp;<a href="'.'/admin/categories.php?cPath='.$cPath.'&categories_id='.$_GET['categories_id'].'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>'; ?>
								<input type="hidden" name="X_promo_image" value="<?= $promo_image_name; ?>">
								<input type="hidden" name="promo_image" value="<?= $promo_image_name; ?>">
								<input type="hidden" name="X_promo_link" value="<?= $promo_link; ?>">
								<input type="hidden" name="promo_link" value="<?= $promo_link; ?>">
							</form>
						</td>
					</tr>
					<?php } ?>
				</table>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<!-- body_eof //-->
	<br>
</body>
</html>