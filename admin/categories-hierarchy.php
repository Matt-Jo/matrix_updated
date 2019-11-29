<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
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
			<td width="<?php echo BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top">
				<table border="0" width="100%" cellspacing="0" cellpadding="2">
					<tr>
						<td>
							<a href="/admin/category-redirects">View All Category Redirects</a>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td class="pageHeading">Categories / Products</td>
									<td class="pageHeading" align="right"></td>
									<td align="right" class="smallText">
										IPN Search: <input type="text" id="ipn_search" name="ipn_search">
										<form name="search" action="/admin/categories.php" method="get" style="display:block;margin:0px;padding:0px;">
											Search <input type="text" name="search">
										</form>
										<form name="goto" action="/admin/categories.php" method="get" style="display:block;margin:0px;padding:0px;">
											Go To: <?= tep_draw_pull_down_menu('cPath', tep_get_category_tree(), $current_category_id, 'onChange="this.form.submit();"'); ?>
										</form>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td valign="top">
										<table border="0" width="100%" cellspacing="0" cellpadding="2">
											<tr class="dataTableHeadingRow">
												<td class="dataTableHeadingContent">Categories / Products</td>
												<td class="dataTableHeadingContent">IPN</td>
												<td class="dataTableHeadingContent">Part Number</td>
												<td class="dataTableHeadingContent" align="center">Status</td>
												<td class="dataTableHeadingContent" align="center">Sort Order</td>
												<td class="dataTableHeadingContent" align="right">Action&nbsp;</td>
											</tr>
											<?php if (isset($_GET['search'])) {
												$categories_raw = prepared_query::fetch('SELECT c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified FROM categories c JOIN categories_description cd ON c.categories_id = cd.categories_id AND cd.language_id = :language_id WHERE cd.categories_name LIKE :categories_name ORDER BY c.sort_order, cd.categories_name', cardinality::SET, [':language_id' => $_SESSION['languages_id'], ':categories_name' => '%'.$_GET['search'].'%']);
											}
											else {
												$categories_raw = prepared_query::fetch('SELECT c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified FROM categories c JOIN categories_description cd ON c.categories_id = cd.categories_id AND cd.language_id = :language_id WHERE c.parent_id = :parent_categories_id ORDER BY c.sort_order, cd.categories_name', cardinality::SET, [':language_id' => $_SESSION['languages_id'], ':parent_categories_id' => $current_category_id]);
											}

											$rows = 0;
											$categories_count = 0;
											foreach ($categories_raw as $categories_count => $categories) {
												$rows++;

												// Get parent_id for subcategories if search
												if (isset($_GET['search'])) $cPath = $categories['parent_id'];

												if ((!isset($_GET['categories_id']) && !isset($_GET['pID']) || (isset($_GET['categories_id']) && ($_GET['categories_id'] == $categories['categories_id']))) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
													$category_childs = ['childs_count' => tep_childs_in_category_count($categories['categories_id'])];
													$category_products = ['products_count' => tep_products_in_category_count($categories['categories_id'])];

													$cInfo_array = array_merge($categories, $category_childs, $category_products);
													$cInfo = (object)$cInfo_array;
												}

												$ck_category = new ck_listing_category($categories['categories_id']);

												$selected = FALSE;
												if (isset($cInfo) && is_object($cInfo) && ($categories['categories_id'] == @$cInfo->categories_id)) $selected = TRUE; ?>
											<tr <?php echo $selected?'id="defaultSelected"':''; ?> class="dataTableRow<?php echo $selected?'Selected':''; ?>" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href='/admin/categories.php?cPath=<?= $selected?$ck_category->get_cpath():$cPath.'&categories_id='.$categories['categories_id']; ?>'">
												<td class="dataTableContent"><a href="/admin/categories.php?cPath=<?= $ck_category->get_cpath(); ?>"><img src="/admin/images/icons/folder.gif" alt="Folder"></a>&nbsp;<strong><?= $categories['categories_name']; ?></strong></td>
												<td class="dataTableContent" align="center" colspan="3">&nbsp;</td>
												<td class="dataTableContent" align="center"><?= $categories['sort_order']; ?></td>
												<td class="dataTableContent" align="right">
													<?php if (isset($cInfo) && is_object($cInfo) && ($categories['categories_id'] == @$cInfo->categories_id) ) { ?>
													<img src="/admin/images/icon_arrow_right.gif">
													<?php }
													else { ?>
													<a href="/admin/categories.php?cPath=<?= $cPath; ?>&categories_id=<?= $categories['categories_id']; ?>"><img src="/admin/images/icon_info.gif"></a>
													<?php } ?>
													&nbsp;
												</td>
											</tr>
											<?php }
											
											if (isset($_GET['search'])) {
												$products_set = prepared_query::fetch("select p.products_id, p.products_model, psc.stock_name, p.allow_mult_opts, pd.products_name, pd.products_ebay_name, p.products_quantity, p.stock_id, p.products_image, p.products_price, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_model, p2c.categories_id from products p, products_description pd, products_to_categories p2c, products_stock_control psc where p.products_id = pd.products_id and pd.language_id = :language_id and p.products_id = p2c.products_id and p.stock_id = psc.stock_id and pd.products_name like :search order by pd.products_name", cardinality::SET, [':language_id' => $_SESSION['languages_id'], ':search' => '%'.$search.'%']);
											}
											else {
												$products_set = prepared_query::fetch("select p.products_id, p.products_model, psc.stock_name,  p.allow_mult_opts, pd.products_name, pd.products_ebay_name, p.products_quantity, p.stock_id, p.products_image, p.products_price, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_model from products p, products_description pd, products_to_categories p2c, products_stock_control psc where p.products_id = pd.products_id and pd.language_id = :language_id and p.products_id = p2c.products_id and p.stock_id = psc.stock_id and p2c.categories_id = :categories_id order by pd.products_name", cardinality::SET, [':language_id' => $_SESSION['languages_id'], ':categories_id' => $current_category_id]);
											}

											$idx = 0;

											//Sri get prodict number ans ipn here....
											foreach ($products_set as $idx => $products) {
												$rows++;

												// Get categories_id for product if search
												if (isset($_GET['search'])) $cPath = $products['categories_id'];

												if ((!isset($_GET['pID']) && !isset($_GET['categories_id']) || (isset($_GET['pID']) && ($_GET['pID'] == $products['products_id']))) && !isset($pInfo) && !isset($cInfo) && substr($action, 0, 3) != 'new') {
													// find out the rating average from customer reviews
													$reviews = prepared_query::fetch('select (avg(reviews_rating) / 5 * 100) as average_rating from reviews where products_id = ?', cardinality::ROW, array($products['products_id']));
													$pInfo_array = array_merge($products, $reviews);
													$pInfo = (object)$pInfo_array;
												}

												if (isset($pInfo) && is_object($pInfo) && ($products['products_id'] == @$pInfo->products_id)) { ?>

											<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href='<?php echo '/admin/categories.php?cPath='.$cPath.'&pID='.$products['products_id'].'&action=new_product_preview&read=only'; ?>'">
												<?php }
												else { ?>
											<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href='<?php echo '/admin/categories.php?cPath='.$cPath.'&pID='.$products['products_id']; ?>'">
												<?php } ?>
	
												<td class="dataTableContent"><a href="<?php echo '/admin/categories.php?cPath='.$cPath.'&pID='.$products['products_id'].'&action=new_product_preview&read=only'; ?>"><?php echo tep_image(DIR_WS_ICONS.'preview.gif', ICON_PREVIEW); ?></a>&nbsp;<?= $products['products_name']. ''; ?></td>
												
												
												<td class="dataTableContent" align="left">
													<?php echo $products['stock_name']; ?>
												</td>
	
												
												<td class="dataTableContent" align="left">
													<?php echo $products['products_model']; ?>
												</td>
												
												<td class="dataTableContent" align="center">
													<?php if ($products['products_status'] == '1') {
														echo tep_image(DIR_WS_IMAGES.'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10).'&nbsp;&nbsp;<a href="'.'/admin/categories.php?action=setflag&flag=0&pID='.$products['products_id'].'&cPath='.$cPath.'">'.tep_image(DIR_WS_IMAGES.'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10).'</a>';
													}
													else {
														echo '<a href="'.'/admin/categories.php?action=setflag&flag=1&pID='.$products['products_id'].'&cPath='.$cPath.'">'.tep_image(DIR_WS_IMAGES.'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10).'</a>&nbsp;&nbsp;'.tep_image(DIR_WS_IMAGES.'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
													} ?>
												</td>
												<td class="dataTableContent" align="center"></td>
												<td class="dataTableContent" align="right">
													<?php if (isset($pInfo) && is_object($pInfo) && ($products['products_id'] == @$pInfo->products_id)) {
														echo tep_image(DIR_WS_IMAGES.'icon_arrow_right.gif', '');
													}
													else {
														echo '<a href="'.'/admin/categories.php?cPath='.$cPath.'&pID='.$products['products_id'].'">'.tep_image(DIR_WS_IMAGES.'icon_info.gif', IMAGE_ICON_INFO).'</a>';
													} ?>&nbsp;
												</td>
											</tr>
											<?php }
						
											$cPath_back = '';
											if (sizeof($cPath_array) > 0) {
												for ($i=0, $n=sizeof($cPath_array)-1; $i<$n; $i++) {
													if (empty($cPath_back)) {
														$cPath_back .= $cPath_array[$i];
													}
													else {
														$cPath_back .= '_'.$cPath_array[$i];
													}
												}
											}

											$cPath_back = tep_not_null($cPath_back)?'cPath='.$cPath_back.'&':''; ?>
											<tr>
												<td colspan="6">
													<table border="0" width="100%" cellspacing="0" cellpadding="2">
														<tr>
															<td class="smallText"><?php echo TEXT_CATEGORIES.'&nbsp;'.$categories_count.'<br>'.TEXT_PRODUCTS.'&nbsp;'.$idx; ?></td>
															<td align="right" class="smallText">
																<?php if (sizeof($cPath_array) > 0) { ?>
																<a href="/admin/categories.php?<?= $cPath_back; ?>categories_id=<?= $current_category_id; ?>"><?= tep_image_button('button_back.gif', IMAGE_BACK); ?></a>
																<?php }

																if (!isset($_GET['search'])) { ?>
																<a href="/admin/categories.php?cPath=<?= $cPath; ?>&action=new_category"><?= tep_image_button('button_new_category.gif', IMAGE_NEW_CATEGORY); ?></a>
																<a href="/admin/categories.php?cPath=<?= $cPath; ?>&action=new_product"><?= tep_image_button('button_new_product.gif', IMAGE_NEW_PRODUCT); ?></a>
																<?php } ?>
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
									</td>
									<?php
									$heading = [];
									$contents = [];

									switch ($action) {
										case 'new_category':
											$heading[] = array('text' => '<b>'.TEXT_INFO_HEADING_NEW_CATEGORY.'</b>');

											$contents = array('form' => tep_draw_form('newcategory', FILENAME_CATEGORIES, 'action=insert_category&cPath='.$cPath, 'post', 'enctype="multipart/form-data"'));
											$contents[] = array('text' => TEXT_NEW_CATEGORY_INTRO);

											$category_inputs_string = '';
											$category_inputs_string .= '<br>'.tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English').'&nbsp;'.tep_draw_input_field('categories_name[1]');
											$contents[] = array('text' => '<br>'.TEXT_CATEGORIES_NAME.$category_inputs_string);
											$contents[] = array('text' => '<br>'.TEXT_CATEGORIES_IMAGE.'<br>'.tep_draw_file_field('categories_image'));
											$contents[] = array('text' => '<br>'.TEXT_SORT_ORDER.'<br>'.tep_draw_input_field('sort_order', '', 'size="2"'));
											$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_save.gif', IMAGE_SAVE).' <a href="'.'/admin/categories.php?cPath='.$cPath.'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');
											break;

										case 'edit_category':
											$heading[] = array('text' => '<b>'.TEXT_INFO_HEADING_EDIT_CATEGORY.'</b>');

											$contents = array('form' => tep_draw_form('categories', FILENAME_CATEGORIES, 'action=update_category&cPath='.$cPath, 'post', 'enctype="multipart/form-data"').tep_draw_hidden_field('categories_id', @$cInfo->categories_id));
											$contents[] = array('text' => TEXT_EDIT_INTRO);

											$category_inputs_string = '';
											$cd = prepared_query::fetch('SELECT * FROM categories_description WHERE categories_id = :categories_id AND language_id = 1', cardinality::ROW, [':categories_id' => @$cInfo->categories_id]);

											$category_inputs_string .= '<br>'.tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English').'&nbsp;'.tep_draw_input_field('categories_name[1]', $cd['categories_name']);

											$contents[] = array('text' => '<br>'.TEXT_EDIT_CATEGORIES_NAME.$category_inputs_string);
											$contents[] = array('text' => '<br>'.tep_image(DIR_WS_CATALOG_IMAGES.@$cInfo->categories_image, @$cInfo->categories_name).'<br>'.DIR_WS_CATALOG_IMAGES.'<br><b>'.@$cInfo->categories_image.'</b>');
											$contents[] = array('text' => '<br>'.TEXT_EDIT_CATEGORIES_IMAGE.'<br>'.tep_draw_file_field('categories_image'));
											$contents[] = array('text' => '<br>'.TEXT_EDIT_SORT_ORDER.'<br>'.tep_draw_input_field('sort_order', @$cInfo->sort_order, 'size="2"'));
											$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_save.gif', IMAGE_SAVE).' <a href="'.'/admin/categories.php?cPath='.$cPath.'&categories_id='.@$cInfo->categories_id.'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');
											break;

										case 'delete_category':
											$heading[] = array('text' => '<b>'.TEXT_INFO_HEADING_DELETE_CATEGORY.'</b>');

											$contents = array('form' => tep_draw_form('categories', FILENAME_CATEGORIES, 'action=delete_category_confirm&cPath='.$cPath).tep_draw_hidden_field('categories_id', @$cInfo->categories_id));
											$contents[] = array('text' => TEXT_DELETE_CATEGORY_INTRO);
											$contents[] = array('text' => '<br><b>'.@$cInfo->categories_name.'</b>');
											if (@$cInfo->childs_count > 0) $contents[] = array('text' => '<br>'.sprintf(TEXT_DELETE_WARNING_CHILDS, @$cInfo->childs_count));
											if (@$cInfo->products_count > 0) $contents[] = array('text' => '<br>'.sprintf(TEXT_DELETE_WARNING_PRODUCTS, @$cInfo->products_count));
											$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_delete.gif', IMAGE_DELETE).' <a href="'.'/admin/categories.php?cPath='.$cPath.'&categories_id='.@$cInfo->categories_id.'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');
											break;

										case 'move_category':
											$heading[] = array('text' => '<b>'.TEXT_INFO_HEADING_MOVE_CATEGORY.'</b>');

											$contents = array('form' => tep_draw_form('categories', FILENAME_CATEGORIES, 'action=move_category_confirm&cPath='.$cPath).tep_draw_hidden_field('categories_id', @$cInfo->categories_id));
											$contents[] = array('text' => sprintf(TEXT_MOVE_CATEGORIES_INTRO, @$cInfo->categories_name));
											$contents[] = array('text' => '<br>'.sprintf(TEXT_MOVE, @$cInfo->categories_name).'<br>'.tep_draw_pull_down_menu('move_to_category_id', tep_get_category_tree(), $current_category_id));
											$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_move.gif', IMAGE_MOVE).' <a href="'.'/admin/categories.php?cPath='.$cPath.'&categories_id='.@$cInfo->categories_id.'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');
											break;

										case 'delete_product':
											$heading[] = array('text' => '<b>'.TEXT_INFO_HEADING_DELETE_PRODUCT.'</b>');

											$contents = array('form' => tep_draw_form('products', FILENAME_CATEGORIES, 'action=delete_product_confirm&cPath='.$cPath).tep_draw_hidden_field('products_id', @$pInfo->products_id));
											$contents[] = array('text' => TEXT_DELETE_PRODUCT_INTRO);
											$contents[] = array('text' => '<br><b>'.@$pInfo->products_name.'</b>');

											$product_categories_string = '';
											$product_categories = tep_generate_category_path(@$pInfo->products_id, 'product');
											for ($i = 0, $n = sizeof($product_categories); $i < $n; $i++) {
												$category_path = '';
												for ($j = 0, $k = sizeof($product_categories[$i]); $j < $k; $j++) {
													$category_path .= $product_categories[$i][$j]['text'].'&nbsp;&gt;&nbsp;';
												}
												$category_path = substr($category_path, 0, -16);
												$product_categories_string .= tep_draw_checkbox_field('product_categories[]', $product_categories[$i][sizeof($product_categories[$i])-1]['id'], true).'&nbsp;'.$category_path.'<br>';
											}
											$product_categories_string = substr($product_categories_string, 0, -4);

											$contents[] = array('text' => '<br>'.$product_categories_string);

											$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_delete.gif', IMAGE_DELETE).' <a href="'.'/admin/categories.php?cPath='.$cPath.'&pID='.@$pInfo->products_id.'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');
											break;

										case 'move_product':
											$heading[] = array('text' => '<b>'.TEXT_INFO_HEADING_MOVE_PRODUCT.'</b>');

											$contents = array('form' => tep_draw_form('products', FILENAME_CATEGORIES, 'action=move_product_confirm&cPath='.$cPath).tep_draw_hidden_field('products_id', @$pInfo->products_id));
											$contents[] = array('text' => sprintf(TEXT_MOVE_PRODUCTS_INTRO, @$pInfo->products_name));
											$contents[] = array('text' => '<br>'.TEXT_INFO_CURRENT_CATEGORIES.'<br><b>'.tep_output_generated_category_path(@$pInfo->products_id, 'product').'</b>');
											$contents[] = array('text' => '<br>'.sprintf(TEXT_MOVE, @$pInfo->products_name).'<br>'.tep_draw_pull_down_menu('move_to_category_id', tep_get_category_tree(), $current_category_id));

											$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_move.gif', IMAGE_MOVE).' <a href="'.'/admin/categories.php?cPath='.$cPath.'&pID='.@$pInfo->products_id.'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');
											break;

										case 'copy_to':
											$heading[] = array('text' => '<b>'.TEXT_INFO_HEADING_COPY_TO.'</b>');

											$contents = array('form' => tep_draw_form('copy_to', FILENAME_CATEGORIES, 'action=copy_to_confirm&cPath='.$cPath).tep_draw_hidden_field('products_id', @$pInfo->products_id));
											$contents[] = array('text' => TEXT_INFO_COPY_TO_INTRO);
											$contents[] = array('text' => '<br>'.TEXT_INFO_CURRENT_CATEGORIES.'<br><b>'.tep_output_generated_category_path(@$pInfo->products_id, 'product').'</b>');
											$contents[] = array('text' => '<br>'.TEXT_CATEGORIES.'<br>'.tep_draw_pull_down_menu('categories_id', tep_get_category_tree(), $current_category_id));
											$contents[] = array('text' => '<br>'.TEXT_HOW_TO_COPY.'<br>'.tep_draw_radio_field('copy_as', 'link').' '.TEXT_COPY_AS_LINK.'<br>'.tep_draw_radio_field('copy_as', 'duplicate', true).' '.TEXT_COPY_AS_DUPLICATE);
											$contents[] = array('text' => '<br><input type="checkbox" name="copy_options">&nbsp;Copy Product Options and Data?');
											// BOF: WebMakers.com Added: Attributes Copy
											$contents[] = array('text' => '<br>'.tep_image(DIR_WS_IMAGES.'pixel_black.gif','','100%','3'));
											$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_copy.gif', IMAGE_COPY).' <a href="'.'/admin/categories.php?cPath='.$cPath.'&pID='.@$pInfo->products_id.'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');
											break;

										default:
											if ($rows > 0) {
												if (isset($cInfo) && is_object($cInfo)) { // category info box contents
													$heading[] = array('text' => '<b>'.@$cInfo->categories_name.'</b>');

													$date_added = new DateTime($cInfo->date_added);

													$contents[] = array('align' => 'center', 'text' => '<a href="'.'/admin/categories.php?cPath='.$cPath.'&categories_id='.@$cInfo->categories_id.'&action=edit_category'.'">'.tep_image_button('button_edit.gif', IMAGE_EDIT).'</a> <a href="'.'/admin/categories.php?cPath='.$cPath.'&categories_id='.@$cInfo->categories_id.'&action=delete_category'.'">'.tep_image_button('button_delete.gif', IMAGE_DELETE).'</a> <a href="'.'/admin/categories.php?cPath='.$cPath.'&categories_id='.@$cInfo->categories_id.'&action=move_category'.'">'.tep_image_button('button_move.gif', IMAGE_MOVE).'</a>');
													$contents[] = array('text' => '<br>'.TEXT_DATE_ADDED.' '.$date_added->format('m/d/Y'));

													$last_modified = new DateTime($cInfo->last_modified);

													if (tep_not_null(@$cInfo->last_modified)) $contents[] = array('text' => TEXT_LAST_MODIFIED.' '.$last_modified->format('m/d/Y'));

													$contents[] = array('text' => '<br>'.tep_info_image(@$cInfo->categories_image, @$cInfo->categories_name, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT).'<br>'.@$cInfo->categories_image);
													$contents[] = array('text' => '<br>'.TEXT_SUBCATEGORIES.' '.@$cInfo->childs_count.'<br>'.TEXT_PRODUCTS.' '.@$cInfo->products_count);
												}
												elseif (isset($pInfo) && is_object($pInfo)) { // product info box contents
													$ckp = new ck_product_listing(@$pInfo->products_id);
													$heading[] = array('text' => '<b>'.$ckp->get_header('products_name').'</b>');

													$date_added = new DateTime($pInfo->products_date_added);

													$contents[] = array('align' => 'center', 'text' => '<a href="'.'/admin/categories.php?cPath='.$cPath.'&pID='.@$pInfo->products_id.'&action=new_product'.'">'.tep_image_button('button_edit.gif', IMAGE_EDIT).'</a> <a href="'.'/admin/categories.php?cPath='.$cPath.'&pID='.@$pInfo->products_id.'&action=delete_product'.'">'.tep_image_button('button_delete.gif', IMAGE_DELETE).'</a> <a href="'.'/admin/categories.php?cPath='.$cPath.'&pID='.@$pInfo->products_id.'&action=move_product'.'">'.tep_image_button('button_move.gif', IMAGE_MOVE).'</a> <a href="'.'/admin/categories.php?cPath='.$cPath.'&pID='.@$pInfo->products_id.'&action=copy_to'.'">'.tep_image_button('button_copy_to.gif', IMAGE_COPY_TO).'</a>');
													$contents[] = array('text' => '<br>'.TEXT_DATE_ADDED.' '.$date_added->format('m/d/Y'));

													$last_modified = new DateTime($pInfo->products_last_modified);
													$date_available = new DateTime($pInfo->products_date_available);

													if (tep_not_null(@$pInfo->products_last_modified)) $contents[] = array('text' => TEXT_LAST_MODIFIED.' '.$last_modified->format('m/d/Y'));
													if (date('Y-m-d') < @$pInfo->products_date_available) $contents[] = array('text' => TEXT_DATE_AVAILABLE.' '.$date_available->format('m/d/Y'));

													$contents[] = array('text' => '<br>'.tep_info_image(@$pInfo->products_image, @$pInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT).'<br>'.@$pInfo->products_image);

													if ((int)@$pInfo->stock_id > 1) {
														$ipn = new ck_ipn2($pInfo->stock_id);
														$contents[] = ['text' => '<br>'.TEXT_PRODUCTS_PRICE_INFO.' '.CK\text::monetize(@$pInfo->products_price)];
														$contents[] = ['text' => '<br>Qty On Hand: '.$ipn->get_inventory('on_hand').'<br>Qty Allocated: '.$ipn->get_inventory('local_allocated').'<br>Qty Available: '.$ipn->get_inventory('available')];
													}
													else {
														$contents[] = array('text' => '<br>'.TEXT_PRODUCTS_PRICE_INFO.' '.CK\text::monetize(@$pInfo->products_price).'<br>'.TEXT_PRODUCTS_QUANTITY_INFO.' '.@$pInfo->products_quantity);
													}
													$contents[] = array('text' => '<br>'.TEXT_PRODUCTS_AVERAGE_RATING.' '.number_format(@$pInfo->average_rating, 2).'%');
													$contents[] = array('text' => '<br>'.tep_image(DIR_WS_IMAGES.'pixel_black.gif','','100%','3'));
												}
											}
											else { // create category/product info
												$heading[] = array('text' => '<b>'.EMPTY_CATEGORY.'</b>');

												$contents[] = array('text' => TEXT_NO_CHILD_CATEGORIES_OR_PRODUCTS);
											}
											break;
									}
 
									if ((tep_not_null($heading)) && (tep_not_null($contents))) {
										echo '<td width="25%" valign="top">'."\n";
										
										$box = new box;
										echo $box->infoBox($heading, $contents);

										echo '</td>'."\n";
									} ?>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<!-- body_eof //-->
</body>
</html>