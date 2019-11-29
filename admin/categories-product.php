<?php $mult_opts_arr = [
	['id' => '0', 'text' => 'No'],
	['id' => '1', 'text' => 'Yes']
]; ?>
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
					<?php if ($action == 'new_product') {
						$parameters = array(
							'products_ebay_name' => '',
							'products_name' => '',
							'products_google_name' => '',
							'products_description' => '',
							'products_url' => '',
							'products_id' => '',
							'products_quantity' => '',
							'stock_id' => '',
							'stock_name' => '',
							'products_model' => '',
							'products_image' => '',
							'products_image_med' => '',
							'products_image_lrg' => '',
							'products_image_sm_1' => '',
							'products_image_xl_1' => '',
							'products_image_sm_2' => '',
							'products_image_xl_2' => '',
							'products_image_sm_3' => '',
							'products_image_xl_3' => '',
							'products_image_sm_4' => '',
							'products_image_xl_4' => '',
							'products_image_sm_5' => '',
							'products_image_xl_5' => '',
							'products_image_sm_6' => '',
							'products_image_xl_6' => '',
							'products_price' => '',
							'products_dealer_price' => '',
							'products_weight' => '',
							'products_date_added' => '',
							'products_last_modified' => '',
							'products_date_available' => '',
							'products_status' => '',
							'products_tax_class_id' => '',
							'manufacturers_id' => '',
							'allow_mult_opts' => '',
							'use_seo_urls' => '',
							'seo_url_text' => ''
						);

						$pInfo = (object)$parameters;

						if (isset($_GET['pID']) && empty($_POST)) {
							$pInfo = (object) prepared_query::fetch("SELECT pd.products_name, pd.products_ebay_name, pd.products_description, pd.products_head_title_tag, pd.products_head_desc_tag, pd.products_url, pd.products_google_name, p.products_id, p.products_quantity, p.stock_id, psc.stock_name, p.products_model, p.products_image, p.products_image_med, p.products_image_lrg, p.products_image_sm_1, p.products_image_xl_1, p.products_image_sm_2, p.products_image_xl_2, p.products_image_sm_3, p.products_image_xl_3, p.products_image_sm_4, p.products_image_xl_4, p.products_image_sm_5, p.products_image_xl_5, p.products_image_sm_6, p.products_image_xl_6, p.products_price, p.products_weight, p.products_date_added, p.products_last_modified, date_format(p.products_date_available, '%Y-%m-%d') as products_date_available, p.products_status, p.products_tax_class_id, p.manufacturers_id, p.products_dealer_price, p.allow_mult_opts, p.use_seo_urls, p.seo_url_text FROM products p JOIN products_description pd ON p.products_id = pd.products_id and pd.language_id = :language_id LEFT JOIN products_stock_control psc ON p.stock_id = psc.stock_id where p.products_id = :products_id", cardinality::ROW, [':language_id' => $_SESSION['languages_id'], ':products_id' => $_GET['pID']]);
						}
						elseif (tep_not_null($_POST)) {
							$pInfo = (object)$_POST;
							$products_ebay_name = $_POST['products_ebay_name'];
							$products_name = $_POST['products_name'];
							$products_description = $_POST['products_description'];
							$products_url = $_POST['products_url'];
						}

						$manufacturers_array = [['id' => '', 'text' => TEXT_NONE]];
						$manufacturers_array = array_merge($manufacturers_array, prepared_query::fetch("select manufacturers_id as id, manufacturers_name as text from manufacturers order by manufacturers_name", cardinality::SET));

						if (!isset($pInfo->products_status)) $pInfo->products_status = '1';
						switch (@$pInfo->products_status) {
							case '0': $in_status = false; $out_status = true; break;
							case '1':
							default: $in_status = true; $out_status = false;
						} ?>
					<tr>
						<td>
							<link rel="stylesheet" type="text/css" href="includes/javascript/spiffyCal/spiffyCal_v2_1.css">
							<script src="includes/javascript/spiffyCal/spiffyCal_v2_1.js"></script>
							<link rel="stylesheet" type="text/css" href="/yui/build/tabview/assets/tabview.css">
							<link rel="stylesheet" type="text/css" href="/yui/build/tabview/assets/border_tabs.css">
							<script src="/yui/build/yahoo/yahoo.js"></script>
							<script src="/yui/build/dom/dom-min.js"></script>
							<script src="/yui/build/event/event-min.js"></script>
							<script src="/yui/build/connection/connection-min.js"></script>
							<script src="/yui/build/dom/dom.js"></script>
							<script src="/yui/build/element/element-beta.js"></script>
							<script src="/yui/build/autocomplete/autocomplete.js"></script>
							<script src="/yui/build/tabview/tabview.js"></script>
							<?php if (!empty($_GET['pretty_description'])) { ?>
							<script src="/admin/includes/javascript/tiny_mce/tiny_mce.js"></script>
							<script>
								tinyMCE.init({
									mode : "exact",
									theme : "advanced",
									plugins : "spellchecker",
									theme_advanced_buttons3_add : "spellchecker",
									elements: "products_description[1]"
								});
							</script>
							<?php } ?>
							<script>
								tabConInit = function() {
									var tabView = new YAHOO.widget.TabView('tabCon');
								};
								tabConInit();
							</script>
							<form name="new_product" action="/admin/categories.php?cPath=<?= $cPath; ?><?= isset($_GET['pID'])?'&pID='.$_GET['pID']:''; ?>&action=new_product_preview" method="post" enctype="multipart/form-data" id="new_product_submit_form">
								<input type="hidden" name="products_tax_class_id" value="1">
								<div id="tabCon" class="yui-navset">
									<ul class="yui-nav">
										<li class="selected"><a href="#general"><em>General</em></a></li>
										<!--<li><a href="#meta"><em>Meta information</em></a></li>-->
										<li><a href="#pictures"><em>Pictures</em></a></li>
										<!--<li><a href="#ebay"><em>Ebay information</em></a></li>-->
										<li><a href="#options"><em>Product options</em></a></li>
									</ul>
									<div class="yui-content" style="padding: 10px;">
										<div id="general">
											<?php $ckp = new ck_product_listing((int)$_GET['pID']); ?>
											<table border="0" cellspacing="0" cellpadding="2">
												<tr>
													<td class="main"></td>
													<td class="main"><span style="font-size: 10px;">ON OFF</span></td>
												</tr>
												<tr>
													<td class="main">Products Status:</td>
													<td class="main">
														<?php if (!empty($pInfo->products_id)) {
															if (@$pInfo->products_status == 1) { ?>
														<img src="images/icon_status_green.gif" border="0" alt="Active" title=" Active " width="10" height="10">
														<a href="/admin/categories.php?action=setflag&flag=0&pID=<?= @$pInfo->products_id; ?>&cPath=<?= $cPath; ?>&editing=1"><img src="images/icon_status_red_light.gif" border="0" alt="Set Inactive" title=" Set Inactive " width="10" height="10"></a>
														<input type="hidden" name="products_status" value="1">
															<?php }
															else { ?>
														<a href="/admin/categories.php?action=setflag&flag=1&pID=<?= @$pInfo->products_id; ?>&cPath=<?= $cPath; ?>&editing=1"><img src="images/icon_status_green_light.gif" border="0" alt="Set Active" title=" Set Active " width="10" height="10"></a>
														<img src="images/icon_status_red.gif" border="0" alt="Inactive" title=" Inactive " width="10" height="10">
														<input type="hidden" name="products_status" value="0">
															<?php }
														}
														else {
															echo tep_draw_radio_field('products_status', '1', $in_status).'&nbsp;'.TEXT_PRODUCT_AVAILABLE.'&nbsp;'.tep_draw_radio_field('products_status', '0', $out_status).'&nbsp;'.TEXT_PRODUCT_NOT_AVAILABLE;
														} ?>
													</td>
												</tr>
												<tr>
													<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
												</tr>
												<tr>
													<td class="main">Products Manufacturer:</td>
													<td class="main"><?= tep_draw_pull_down_menu('manufacturers_id', $manufacturers_array, @$pInfo->manufacturers_id); ?></td>
												</tr>
												<tr>
													<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
												</tr>
												<tr>
													<td class="main">Parent Stock Item:</td>
													<td class="main">
														<input type="text" name="stock_name" id="autocomplete_search_box" value="<?= @$pInfo->stock_name; ?>">
														<input type="hidden" name="stock_id" id="auto_search_stock_id" value="<?= @$pInfo->stock_id; ?>">
														<script>
															jQuery('#autocomplete_search_box').autocomplete({
																minChars: 3,
																source: function(request, response) {
																	jQuery.ajax({
																		minLength: 2,
																		url: '/admin/serials_ajax.php?action=ipn_autocomplete',
																		dataType: 'json',
																		data: {
																			term: request.term,
																			search_type: 'ipn',
																			ipn_only: 1
																		},
																		success: function(data) {
																			response(jQuery.map(data, function(item) {
																				if (item.value == null) item.value = item.label;
																				if (item.data_display == null) item.data_display = item.label;
																				return {
																					misc: item.value,
																					label: item.label,
																					value: item.label,
																					id: item.stock_id
																				}
																			}))
																		}
																	});
																},
																select: function(event, ui) {
																	jQuery('#auto_search_stock_id').val(ui.item.id);
																}
															});
														</script>
														<?php /*echo tep_draw_pull_down_menu('stock_id', $stocks_array, @$pInfo->stock_id);*/ ?>
													</td>
												</tr>
												<tr>
													<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
												</tr>
												<tr>
													<td class="main">UPC:</td>
													<td class="main">
														<?php if (!$ckp->has_upcs()) echo '<strong style="color:#c00;">NO UPC ASSIGNED!</strong>';
														else echo $ckp->get_upc_number(); ?>
													</td>
												</tr>
												<tr>
													<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
												</tr>
												<tr>
													<td class="main">Products Model:</td>
													<td class="main"><input type="text" name="products_model" value="<?= @$pInfo->products_model; ?>" maxlength="32"></td>
												</tr>
												<tr>
													<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
												</tr>
												<?php if ($ckp->has_categories()) {
													foreach ($ckp->get_categories() as $category) { ?>
												<tr>
													<td class="main">Assigned Category:</td>
													<td>
														<select class="add-category" name="add-category[]" data-cpath="<?= implode('-', array_reverse($category->get_id_path())); ?>">
															<option value="">NONE</option>
														</select>
													</td>
												</tr>
													<?php }
												} ?>
												<tr>
													<td class="main">Add Category:</td>
													<td class="main">
														<select class="add-category" name="add-category[]">
															<option value="">NONE</option>
														</select>
													</td>
												</tr>
												<tr>
													<td colspan="2">
														<img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10">
														<?php $cats = ck_listing_category::get_select_navigator_category_list();
														$encoded_top_level = json_encode($cats['top_level']);
														$encoded_selections = json_encode($cats['selections']); ?>
														<script src="/images/static/js/ck-styleset.max.js"></script>
														<script src="/images/static/js/ck-category-navigator.max.js?v=0.03"></script>
														<script>
															var cn = new ck.category_navigator(jQuery('.add-category'));
															cn.load_top_level(<?= $encoded_top_level; ?>);
															cn.load_selections(<?= $encoded_selections; ?>);

															jQuery('.add-category').each(function() {
																let cpath = jQuery(this).attr('data-cpath');
																if (cpath != undefined && cpath.length > 0) {
																	carr = cpath.split('-');
																	cn.select_path(jQuery(this), carr);
																}
															});
														</script>
													</td>
												</tr>
												<tr>
													<td class="main">New SEO Naming:</td>
													<td class="main">
														Use New SEO Naming: <input type="checkbox" name="use_seo_urls" <?= @$pInfo->use_seo_urls?'checked':''; ?>><br>
														New SEO URL Text: <input type="text" name="seo_url_text" value="<?= @$pInfo->seo_url_text; ?>">
													</td>
												</tr>
												<tr>
													<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
												</tr>
												<tr>
													<td class="main">URL:</td>
													<td class="main"><a href="<?= $ckp->get_url(); ?>" target="_blank"><?= $ckp->get_url(); ?></a></td>
												</tr>
												<tr>
													<td class="main">Canonical URL:</td>
													<td class="main"><a href="<?= $ckp->get_canonical_url(); ?>" target="_blank"><?= $ckp->get_canonical_url(); ?></a></td>
												</tr>
												<tr>
													<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
												</tr>
												<tr>
													<td class="main">Product Name:</td>
													<td class="main">
														<input type="text" name="products_name[1]" value="<?= isset($products_name[1])?htmlspecialchars($products_name[1]):htmlspecialchars($ckp->get_header('products_name')); ?>" size="64" maxlength="128"><br>
														<small>This is the main name used on the product page, as well as the field used for old style SEO friendly URL</small>
													</td>
												</tr>
												<tr>
													<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
												</tr>
												<tr>
													<td class="main" valign="top">Product Head Title Tag:</td>
													<td class="main">
														<input type="text" name="products_head_title_tag[1]" value="<?= isset($products_head_title_tag[1])?$products_head_title_tag[1]:$ckp->get_header('products_head_title_tag'); ?>" size="80" maxlength="80"><br>
														<small>The product page tab title - if this is empty, it uses product name instead</small>
													</td>
												</tr>
												<tr>
													<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
												</tr>
												<tr>
													<td class="main" valign="top">Short Description:</td>
													<td class="main">
														<textarea id="products_head_desc_tag[1]" name="products_head_desc_tag[1]" wrap="soft" cols="70" rows="3"><?= isset($products_head_desc_tag[1])?$products_head_desc_tag[1]:$ckp->get_header('products_head_desc_tag'); ?></textarea><br>
														<small>Description shown on the category listing results and on the product page below the title. <strong>Limit to 3 lines max</strong></small>
													</td>
												</tr>
												<tr>
													<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
												</tr>
												<tr>
													<td class="main">Ebay Name:</td>
													<td class="main">
														<input type="text" name="products_ebay_name[1]" value="<?= isset($products_ebay_name[1])?$products_ebay_name[1]:$ckp->get_header('products_ebay_name'); ?>" maxlength="80" size="80"><br>
														<small>Not currently in use</small>
													</td>
												</tr>
												<tr>
													<td class="main">Google Name:</td>
													<td class="main">
														<input type="text" name="products_google_name[1]" value="<?= isset($products_google_name[1])?$products_google_name[1]:@$pInfo->products_google_name; ?>">
													</td>
												</tr>
												<tr>
													<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
												</tr>
												<tr bgcolor="#ebebff">
													<td class="main">Regular Price:</td>
													<td class="main"><?= CK\text::monetize(@$pInfo->products_price); ?></td>
												</tr>
												<tr bgcolor="#ebebff">
													<td class="main">Dealer Price:</td>
													<td class="main"><?= CK\text::monetize(@$pInfo->products_dealer_price); ?></td>
												</tr>
												<tr>
													<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
												</tr>
												<tr>
													<td class="main" valign="top">Product Description</td>
													<td class="main">
														<?php if (empty($_GET['pretty_description'])) { ?>
														<a href="/admin/categories.php?cPath=<?= $cPath; ?><?= isset($_GET['pID'])?'&pID='.$_GET['pID']:''; ?>&action=new_product&pretty_description=1">Edit HTML in WYSIWYG</a> <small>(You will lose any unsaved work)</small><br>
														<?php }
														else { ?>
														<a href="/admin/categories.php?cPath=<?= $cPath; ?><?= isset($_GET['pID'])?'&pID='.$_GET['pID']:''; ?>&action=new_product">Edit Raw HTML</a> <small>(You will lose any unsaved work)</small><br>
														<?php } ?>
														<textarea cols="140" rows="60" title="test" name="products_description[1]" wrap="soft"><?= isset($products_description[1])?$products_description[1]:$ckp->get_header('products_description'); ?></textarea><br>
														<small>This is the main description on the product info page</small>
													</td>
												</tr>
												<tr>
													<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
												</tr>
												<?php
												//MMD - if the product has an IPN, display stock data
												if ((int)@$pInfo->stock_id > 1) {
													$ipn = new ck_ipn2($pInfo->stock_id); ?>
												<tr>
													<td class="main">Quantity On Hand:</td>
													<td class="main"><?= $ipn->get_inventory('on_hand'); ?></td>
												</tr>
												<tr>
													<td class="main">Quantity Allocated:</td>
													<td class="main"><?= $ipn->get_inventory('local_allocated'); ?></td>
												</tr>
												<tr>
													<td class="main">Quantity Available:</td>
													<td class="main"><?= $ipn->get_inventory('available'); ?></td>
												</tr>
												<tr>
													<td class="main">Products Weight:</td>
													<td class="main"><?= $ipn->get_header('stock_weight'); ?></td>
												</tr>
												<?php }
												else { ?>
												<tr>
													<td class="main" colspan="2">This item does not have an attached IPN to pull Qty or Weight info from</td>
												</tr>
												<?php } ?>
												<tr>
													<td colspan="2"><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
												</tr>
											</table>
										</div>
										<div id="pictures">
											<?php if (!empty($_GET['pID'])) { ?>
											<style>
												#pic_list { font-size:8pt; }
												#pic_list th, #pic_list td { padding:3px 6px; border-style:solid; border-width:1px 0px 0px 1px; }
												#pic_list tr:last-child th, #pic_list tr:last-child td, #pic_list tr.demark td, #pic_list td.imgsize { border-bottom-width:1px; }
												#pic_list th:last-child, #pic_list td:last-child { border-right-width:1px; }
												#pic_list th { background-color:#e6eeee; border-color:#fff; }
												#pic_list td { background-color:#fff; }
												#pic_list td.found { background-color:#cfc; }
												#pic_list td.missing { background-color:#fcc; }
												#pic_list .show-actual-size { display:block; height:120px; width:180px; }
												#pic_list .show-actual-size img { height:120px; width:180px; position:absolute; border:1px solid #00f; }
												#pic_list .show-actual-size img:hover { height:auto; width:auto; z-index:100; border-color:#fff; }
											</style>
											<table border="0" cellspacing="0" cellpadding="0" id="pic_list">
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
														<td rowspan="3" class="imgsize">Full<br>(<?= imagesizer::$map['lrg']['width'].'x'.imagesizer::$map['lrg']['height']; ?>)</td>
														<td>CDN</td>
														<td><?php if (!empty($pInfo->products_image_lrg)) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= @$pInfo->products_image_lrg; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= @$pInfo->products_image_lrg; ?>"></a><?php } else { echo 'NONE'; } ?></td>
														<td><?php if (!empty($pInfo->products_image_xl_1)) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= @$pInfo->products_image_xl_1; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= @$pInfo->products_image_xl_1; ?>"></a><?php } else { echo 'NONE'; } ?></td>
														<td><?php if (!empty($pInfo->products_image_xl_2)) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= @$pInfo->products_image_xl_2; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= @$pInfo->products_image_xl_2; ?>"></a><?php } else { echo 'NONE'; } ?></td>
														<td><?php if (!empty($pInfo->products_image_xl_3)) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= @$pInfo->products_image_xl_3; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= @$pInfo->products_image_xl_3; ?>"></a><?php } else { echo 'NONE'; } ?></td>
														<td><?php if (!empty($pInfo->products_image_xl_4)) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= @$pInfo->products_image_xl_4; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= @$pInfo->products_image_xl_4; ?>"></a><?php } else { echo 'NONE'; } ?></td>
														<td><?php if (!empty($pInfo->products_image_xl_5)) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= @$pInfo->products_image_xl_5; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= @$pInfo->products_image_xl_5; ?>"></a><?php } else { echo 'NONE'; } ?></td>
														<td><?php if (!empty($pInfo->products_image_xl_6)) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= @$pInfo->products_image_xl_6; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= @$pInfo->products_image_xl_6; ?>"></a><?php } else { echo 'NONE'; } ?></td>
													</tr>
													<tr>
														<td>LOCAL</td>
														<td class="<?= file_exists(picture_audit::$imgfolder.'/'.@$pInfo->products_image_lrg)?'found':'missing'; ?>"><?php if (!empty($pInfo->products_image_lrg)) { ?><a href="/images/<?= @$pInfo->products_image_lrg; ?>" target="_blank">SEE</a><?php } ?></td>
														<td class="<?= file_exists(picture_audit::$imgfolder.'/'.@$pInfo->products_image_xl_1)?'found':'missing'; ?>"><?php if (!empty($pInfo->products_image_xl_1)) { ?><a href="/images/<?= @$pInfo->products_image_xl_1; ?>" target="_blank">SEE</a><?php } ?></td>
														<td class="<?= file_exists(picture_audit::$imgfolder.'/'.@$pInfo->products_image_xl_2)?'found':'missing'; ?>"><?php if (!empty($pInfo->products_image_xl_2)) { ?><a href="/images/<?= @$pInfo->products_image_xl_2; ?>" target="_blank">SEE</a><?php } ?></td>
														<td class="<?= file_exists(picture_audit::$imgfolder.'/'.@$pInfo->products_image_xl_3)?'found':'missing'; ?>"><?php if (!empty($pInfo->products_image_xl_3)) { ?><a href="/images/<?= @$pInfo->products_image_xl_3; ?>" target="_blank">SEE</a><?php } ?></td>
														<td class="<?= file_exists(picture_audit::$imgfolder.'/'.@$pInfo->products_image_xl_4)?'found':'missing'; ?>"><?php if (!empty($pInfo->products_image_xl_4)) { ?><a href="/images/<?= @$pInfo->products_image_xl_4; ?>" target="_blank">SEE</a><?php } ?></td>
														<td class="<?= file_exists(picture_audit::$imgfolder.'/'.@$pInfo->products_image_xl_5)?'found':'missing'; ?>"><?php if (!empty($pInfo->products_image_xl_5)) { ?><a href="/images/<?= @$pInfo->products_image_xl_5; ?>" target="_blank">SEE</a><?php } ?></td>
														<td class="<?= file_exists(picture_audit::$imgfolder.'/'.@$pInfo->products_image_xl_6)?'found':'missing'; ?>"><?php if (!empty($pInfo->products_image_xl_6)) { ?><a href="/images/<?= @$pInfo->products_image_xl_6; ?>" target="_blank">SEE</a><?php } ?></td>
													</tr>
												</tbody>
											</table>
											<?php } ?>

											<p>
												<strong>Please Note:</strong> Images are no longer managed on this screen.<br>
												<?php if (!empty($_GET['pID'])) { ?>
												The images shown are what you will currently see on the product page. They are managed from the parent IPN by the marketing dept.<br>
												If you have changed the parent IPN for this listing, you'll see the new images on the preview page.
												<?php }
												else { ?>
												On the preview page you'll see the images that will automatically be copied from the parent IPN to populate this item.
												<?php } ?>
											</p>
										</div>
										<div id="options">
											<?php if (isset($_GET['pID'])) {
												//MMD - we only allow product options for an existing product
												//we need this to render the product selection tree
												require(DIR_WS_CLASSES.'productCheckTreeView.php'); ?>
											<script src="includes/javascript/productAddons.js"></script>
											<table border="0" cellspacing="0" cellpadding="2">
												<tr>
													<td class="main">
														<div id="pa_options_children_hidden" style="display: none">
															<a href="javascript: void(0);" onClick="document.getElementById('pa_options_children_hidden').style.display='none'; document.getElementById('pa_options_children').style.display='';"> + </a>
															&nbsp;Options for this product
														</div>
														<div id="pa_options_children">
															<a href="javascript: void(0);" onClick="document.getElementById('pa_options_children').style.display='none'; document.getElementById('pa_options_children_hidden').style.display='';"> - </a>
															&nbsp;Options for this product:
															<br><br>
															<table id="pa_children_table" cellspacing="5px" cellpadding="5px" style="border:1px solid black;">
																<tr>
																	<th style="font-size: 10px;">Product</th>
																	<th style="font-size: 10px;">Included</th>
																	<th style="font-size: 10px;">Bundle Qty</th>
																	<th style="font-size: 10px;">Recommended</th>
																	<th style="font-size: 10px;">Allowed Qty</th>
																	<th style="font-size: 10px;">Use custom price</th>
																	<th style="font-size: 10px;">Price</th>
																	<th style="font-size: 10px;">Use custom title</th>
																	<th style="font-size: 10px;">Title</th>
																	<th style="font-size: 10px;">Use custom description</th>
																	<th style="font-size: 10px;">Description</th>
																	<th style="font-size: 10px;">[X]</th>
																</tr>
																<?php
																$pa_child_addons_query = prepared_query::fetch("SELECT pa.product_id, pa.product_addon_id, pa.recommended, pa.bundle_quantity, pa.included, pa.allow_mult_opts, pa.use_custom_price, pa.custom_price, pa.use_custom_name, pa.custom_name, pa.use_custom_desc, pa.custom_desc, pd.products_name, p.products_price, pad.default_price, pad.default_desc from product_addons pa inner join products_description pd on pd.products_id = pa.product_addon_id and pa.product_id = :products_id inner join products p on p.products_id = pa.product_addon_id and pa.product_id = :products_id left join product_addon_data pad on pa.product_addon_id = pad.product_id and pa.product_id = :products_id", cardinality::SET, [':products_id' => $_GET['pID']]);
																$child_checked_list = [];

																foreach ($pa_child_addons_query as $pa_child_addon_values) {
																	$parentId = $pa_child_addon_values['product_id'];
																	$childId = $pa_child_addon_values['product_addon_id'];
																	$childName = $pa_child_addon_values['products_name'];
																	$recommended = $pa_child_addon_values['recommended'];
																	$included = $pa_child_addon_values['included'];
																	$bundle_quantity = $pa_child_addon_values['bundle_quantity'];
																	$allow_mult_opts = $pa_child_addon_values['allow_mult_opts'];
																	$use_custom_price = $pa_child_addon_values['use_custom_price'];
																	$default_price = $pa_child_addon_values['default_price']!=null?$pa_child_addon_values['default_price']:$pa_child_addon_values['products_price'];
																	$custom_price = $pa_child_addon_values['custom_price']!=null&&$pa_child_addon_values['custom_price']!=''?$pa_child_addon_values['custom_price']:$default_price;
																	$default_name = $pa_child_addon_values['products_name'];
																	$use_custom_name = $pa_child_addon_values['use_custom_name'];
																	$custom_name = $pa_child_addon_values['custom_name']!=null&&$pa_child_addon_values['custom_name']!=''?$pa_child_addon_values['custom_name']:$default_name;
																	$use_custom_desc = $pa_child_addon_values['use_custom_desc'];
																	$default_desc = $pa_child_addon_values['default_desc']!=null?$pa_child_addon_values['default_desc']:'';
																	$custom_desc = $pa_child_addon_values['custom_desc']!=null&&$pa_child_addon_values['custom_desc']!=''?$pa_child_addon_values['custom_desc']:$default_desc;
																	$addon_uid = 'pa_'.$pa_child_addon_values['product_id'].'_'.$pa_child_addon_values['product_addon_id'];
																	$parent_cat_id = prepared_query::fetch('select categories_id from products_to_categories where products_id = :products_id', cardinality::SINGLE, [':products_id' => $childId]);
																	get_parent_cats($parent_cat_id, true);
																	$child_checked_list = array_merge($child_checked_list, $parent_cat_array); 
																	?>
																<tr id="<?= $addon_uid; ?>">
																	<td style="font-size: 10px;">
																		<a href="categories.php?action=new_product&pID=<?= $childId; ?>"><?= $childName; ?></a>
																	</td>
																	<td style="font-size: 10px;" align="center">
																		<input type="checkbox" onChange="toggleIncluded(this.checked, '<?= $addon_uid; ?>');" <?php if (!empty($included)) { echo 'checked'; } ?> name="<?= $addon_uid; ?>_included">
																	</td>
																	<td style="font-size: 10px;" align="center">
																		<input type="text" size="4" name="<?= $addon_uid; ?>_bundle_quantity" id="<?= $addon_uid; ?>_bundle_quantity"<?php if (!$included) {?>style="display:none;"<?php } ?> value="<?= $bundle_quantity; ?>">
																	</td>
																	<td style="font-size: 10px;" align="center">
																		<input type="checkbox" <?php if ($recommended) { ?>checked<?php } ?> name="<?= $addon_uid; ?>_recommended" id="<?= $addon_uid; ?>_recommended"<?php if ($included) {?>style="display:none;"<?php } ?>>
																	</td>
																	<td style="font-size: 10px;" align="center">
																		<input type="text" value="<?= $allow_mult_opts; ?>" name="<?= $addon_uid; ?>_allow_mult_opts" id="<?= $addon_uid; ?>_allow_mult_opts"<?php if ($included) {?>style="display:none;"<?php } ?> size="3" maxlength="4">
																	</td>
																	<td style="font-size: 10px;" align="center">
																		<input type="checkbox" onChange="toggleCustomPrice(this.checked, '<?= $addon_uid?>');" <?php if ($use_custom_price) { ?>checked<?php } ?> name="<?= $addon_uid; ?>_use_custom_price" id="<?= $addon_uid; ?>_use_custom_price" <?php if ($included) {?>style="display:none;"<?php } ?>>
																	</td>
																	<td style="font-size: 10px;">
																		<input type="text" value="<?= number_format($custom_price, 2, '.', ',')?>" size="10" name="<?= $addon_uid; ?>_custom_price" id="<?= $addon_uid; ?>_custom_price" <?php if (!$use_custom_price || $included) {?> style="display:none"<?php } ?>>
																		<span id="<?= $addon_uid; ?>_default_price" <?php if ($use_custom_price || $included) {?> style="display:none"<?php } ?>><?= number_format($default_price, 2, '.', ',');?></span>
																	</td>
																	<td style="font-size: 10px;" align="center">
																		<input type="checkbox" onChange="toggleCustomName(this.checked, '<?= $addon_uid?>');" <?php if ($use_custom_name) { ?>checked<?php } ?> name="<?= $addon_uid; ?>_use_custom_name">
																	</td>
																	<td style="font-size: 10px;">
																		<textarea row="3" cols="40" name="<?= $addon_uid; ?>_custom_name" id="<?= $addon_uid; ?>_custom_name" <?php if (!$use_custom_name) {?> style="display:none"<?php } ?>><?= $custom_name; ?></textarea>
																		<span id="<?= $addon_uid; ?>_default_name" <?php if ($use_custom_name) {?> style="display:none"<?php } ?>><?= $default_name; ?></span>
																	</td>
																	<td style="font-size: 10px;" align="center">
																		<input type="checkbox" onChange="toggleCustomDesc(this.checked, '<?= $addon_uid?>');" <?php if ($use_custom_desc) { ?>checked<?php } ?> name="<?= $addon_uid; ?>_use_custom_desc">
																	</td>
																	<td style="font-size: 10px;">
																		<textarea row="3" cols="40" name="<?= $addon_uid; ?>_custom_desc" id="<?= $addon_uid; ?>_custom_desc" <?php if (!$use_custom_desc) {?> style="display:none"<?php } ?>><?= $custom_desc; ?></textarea>
																		<span id="<?= $addon_uid; ?>_default_desc" <?php if ($use_custom_desc) {?> style="display:none"<?php } ?>><?= $default_desc; ?></span>
																	</td>
																	<td style="font-size: 10px;">
																		<input type="checkbox" onClick="this.checked?jQuery('tr#<?= $addon_uid; ?> td, tr#<?= $addon_uid; ?> a, tr#<?= $addon_uid; ?> textarea, tr#<?= $addon_uid; ?> input:not([id=delete_<?= $parentId; ?>_<?= $childId; ?>])').css('background-color', '#fcc').css('color', '#999').attr('disabled', 'disabled'):jQuery('tr#<?= $addon_uid; ?> td, tr#<?= $addon_uid; ?> a, tr#<?= $addon_uid; ?> textarea, tr#<?= $addon_uid; ?> input:not([id=delete_<?= $parentId; ?>_<?= $childId; ?>])').css('background-color', '').css('color', '').removeAttr('disabled');" name="delete_product_option[<?= $parentId; ?>][<?= $childId; ?>]" id="delete_<?= $parentId; ?>_<?= $childId; ?>">
																	</td>
																</tr>
																<?php } ?>
															</table>
															<br>
															<div id="pa_options_children_add_remove_hidden" style="font-size: 10px">
																<a href="javascript: void(0);" onClick="document.getElementById('pa_options_children_add_remove_hidden').style.display='none'; document.getElementById('pa_options_children_add_remove').style.display='';"> + </a>
																&nbsp;Add/remove options
															</div>
															<div id="pa_options_children_add_remove" style="display: none; font-size: 10px;">
																<a href="javascript: void(0);" onClick="document.getElementById('pa_options_children_add_remove').style.display='none'; document.getElementById('pa_options_children_add_remove_hidden').style.display='';"> - </a>
																&nbsp;Add/remove options
																<div style="padding: 10px;">
																	<div id="childTabCon" class="yui-navset">
																		<ul class="yui-nav">
																			<li class="selected"><a href="#child_cat_tree"><em>Category tree view</em></a></li>
																			<li><a href="#child_ipn_search"><em>IPN search</em></a></li>
																		</ul>
																		<div class="yui-content" style="padding: 15px;">
																			<div id="child_cat_tree">
																				<?php
																				$childProductTree = new productCheckTreeView("childTree");
																				$childProductTree->setProductCheckedList($child_checked_list);
																				$childProductTree->setOnProductCheck("createProductAddon(".$_GET['pID'].", %product_id%, 'pa_children_table', %product_id%);addIdToChildrenCheckedList('%product_id%');");
																				$childProductTree->setOnProductUncheck("removeProductAddon(".$_GET['pID'].", %product_id%, 'pa_children_table', 'pa_".$_GET['pID']."_%product_id%');removeIdFromChildrenCheckedList('%product_id%')");
																				$childProductTree->setTreeType('child');
																				$childProductTree->renderAjaxTree();
																				?>
																			</div>
																			<div id="child_ipn_search">
																				<style type="text/css">
																					#child_ipn_search_input {width:15em;height:1.4em;}
																					#child_ipn_search_container {position:absolute;z-index:9050;}
																					#child_ipn_search_container .yui-ac-content {position:absolute;left:0;top:0;width:15em;border:1px solid #404040;background:#fff;overflow:hidden;text-align:left;z-index:9050;}
																					#child_ipn_search_container .yui-ac-shadow {position:absolute;left:0;top:0;margin:.3em;background:#a0a0a0;z-index:9049;}
																					#child_ipn_search_container ul {padding:5px 0;width:100%;}
																					#child_ipn_search_container li {padding:0 5px;cursor:default;white-space:nowrap;}
																					#child_ipn_search_container li.yui-ac-highlight {background:#ff0;}
																				</style>
																				<label>IPN Lookup:</label>
																				<input id="child_ipn_search_input">
																				<div id="child_ipn_search_container"></div>
																				<div id="child_ipn_product_container"></div>
																			</div>
																		</div>
																	</div>
																</div>
																<script>
																	childTabConInit = function() {
																		var tabView = new YAHOO.widget.TabView('childTabCon');
																	};

																	childTabConInit();

																	var childrenCheckedList = [];
																	<?php
																	for ($i = 0; $i < count($child_checked_list); $i++) {
																	?>
																	childrenCheckedList[childrenCheckedList.length] = '<?= $child_checked_list[$i];?>';
																	<?php
																	}
																	?>

																	function addIdToChildrenCheckedList(id) {
																		childrenCheckedList[childrenCheckedList.length] = id;
																	}

																	function removeIdFromChildrenCheckedList(id) {
																		var newArray = [];
																		for (var j = 0; j < childrenCheckedList.length; j++) {
																			if (childrenCheckedList[j] != id) {
																				newArray[newArray.length] = childrenCheckedList[j];
																			}
																		}
																		childrenCheckedList = newArray;
																	}

																	childIpnSearchInit = function() {
																		var cist_xhr_ds = new YAHOO.widget.DS_XHR("ipn_autoCompleteQuery.php", ["results","name"]);
																		cist_xhr_ds.queryMatchContains = true;

																		// Instantiate AutoComplete
																		var cist_auto_complete = new YAHOO.widget.AutoComplete("child_ipn_search_input","child_ipn_search_container", cist_xhr_ds, {"forceSelection" : true});
																		cist_auto_complete.useShadow = true;
																		cist_auto_complete.typeAhead = true;
																		cist_auto_complete.forceSelection = true;
																		cist_auto_complete.forceSelection = true;
																		cist_auto_complete.formatResult = function(oResultItem, sQuery) {
																			return oResultItem[1].name;
																		};
																		cist_auto_complete.doBeforeExpandContainer = function(oTextbox, oContainer, sQuery, aResults) {
																			var pos = YAHOO.util.Dom.getXY(oTextbox);
																			pos[1] += YAHOO.util.Dom.get(oTextbox).offsetHeight;
																			YAHOO.util.Dom.setXY(oContainer,pos);
																			return true;
																		};
																		cist_auto_complete.itemSelectEvent.subscribe(function(type, args) {

																			var callback = {
																				success: function(o) {
																					var result = eval(o.responseText);

																					//get the HTML element we will update
																					var divElem = document.getElementById("child_ipn_product_container");
																					divElem.innerHTML = "";

																					//create the ul we will use
																					var ulElem = document.createElement('ul');
																					ulElem.style.listStyleType = 'none';
																					for (var i = 0; i < result.results.length; i++) {

																						//first we look if this item should be checked
																						var isChecked = false;
																						for (var j = 0; j < childrenCheckedList.length; j++) {
																							if (childrenCheckedList[j] == result.results[i].id) {
																								isChecked = true;
																							}
																						}

																						var liElem = document.createElement('li');
																						liElem.style.padding = '5px';

																						var checkElem = document.createElement('input');
																						checkElem.type='checkbox';
																						if (isChecked) {
																							checkElem.checked = true;
																						}
																						checkElem.id = result.results[i].id;
																						checkElem.onclick = function() {
																							if (this.checked) {
																								addIdToChildrenCheckedList(this.id);
																								tree_childTree.getNodeByProperty('product_id', this.id).check();
																								createProductAddon("<?= $_GET['pID']; ?>", this.id, 'pa_children_table', this.id);
																							}
																							else {
																								removeIdFromChildrenCheckedList(this.id);
																								tree_childTree.getNodeByProperty('product_id', this.id).uncheck();
																								removeProductAddon("<?= $_GET['pID']; ?>", this.id, 'pa_children_table', 'pa_<?= $_GET['pID']; ?>_' + this.id);
																							}
																						}
																						liElem.appendChild(checkElem);

																						var spanElem = document.createElement('span');
																						spanElem.product_id = result.results[i].id;
																						spanElem.innerHTML = '&nbsp;&nbsp;&nbsp;' + result.results[i].name;
																						spanElem.onmouseover = function(e) {
																							ppd_show(this.product_id, e);
																						}
																						spanElem.onmouseout = function(e) {
																							ppd_hide();
																						}
																						liElem.appendChild(spanElem);

																						ulElem.appendChild(liElem);
																					}

																					divElem.appendChild(ulElem);
																				},
																				failure: function(o) {
																					if (o.responseText !== undefined) {
																						alert("Get products for IPN failed: " + o.responseText);
																					}
																					else {
																						alert("Get products for IPN failed: no error message available");
																					}
																				},
																				argument: []
																			};

																			var url = "ipn_getProductByIPN.php?stock_name=" + args[2][0];

																			YAHOO.util.Connect.asyncRequest('GET', url, callback);
																		});

																	}
																	childIpnSearchInit();
																</script>
																<br><br>
															</div>
														</div>
														<br>
													</td>
												</tr>
												<tr>
													<td class="main"><hr></td>
												</tr>
												<tr>
													<td class="main">
														<div id="pa_options_parents_hidden">
															<a href="javascript: void(0);" onClick="document.getElementById('pa_options_parents_hidden').style.display='none'; document.getElementById('pa_options_parents').style.display='';"> + </a>
															&nbsp;This product is an option for:
														</div>
														<div id="pa_options_parents" style="display: none;">
															<a href="javascript: void(0);" onClick="document.getElementById('pa_options_parents').style.display='none'; document.getElementById('pa_options_parents_hidden').style.display='';"> - </a>
															&nbsp;This product is an option for:
															<br><br>
															<?php $pad = prepared_query::fetch("select pad.default_price, pad.default_desc from product_addon_data pad where pad.product_id = :products_id", cardinality::ROW, [':products_id' => $_GET['pID']]); ?>
															<span class="main">Default price:</span>&nbsp;&nbsp;&nbsp;
															<?= tep_draw_input_field('pad_default_price', (isset($pad['default_price'])?$pad['default_price']:''), 'disabled' ); ?>
															<br>
															<span class="main">Default description:</span> &nbsp;&nbsp;&nbsp;
															<textarea rows='3' cols='40' name='pad_default_desc'><?= $pad['default_desc']; ?></textarea>
															<br><br>
															<table id="pa_parents_table" cellspacing="5px" cellpadding="5px" style="border:1px solid black;">
																<tr>
																	<th style="font-size: 10px;">Product</th>
																	<th style="font-size: 10px;">Included</th>
																	<th style="font-size: 10px;">Bundle Qty</th>
																	<th style="font-size: 10px;">Recommended</th>
																	<th style="font-size: 10px;">Allowed Qty</th>
																	<th style="font-size: 10px;">Use custom price</th>
																	<th style="font-size: 10px;">Price</th>
																	<th style="font-size: 10px;">Use custom title</th>
																	<th style="font-size: 10px;">Title</th>
																	<th style="font-size: 10px;">Use custom description</th>
																	<th style="font-size: 10px;">Description</th>
																</tr>
																<?php
																$pa_parent_addons_query = prepared_query::fetch("SELECT pa.product_id, pa.product_addon_id, pa.recommended, pa.included, pa.bundle_quantity, pa.allow_mult_opts, pa.use_custom_price, pa.custom_price, pa.use_custom_name, pa.custom_name, pa.use_custom_desc, pa.custom_desc, pd.products_name, p.products_price, pad.default_price, pad.default_desc from product_addons pa inner join products_description pd on pd.products_id = pa.product_id and pa.product_addon_id = :products_id inner join products p on p.products_id = pa.product_addon_id and pa.product_addon_id = :products_id left join product_addon_data pad on pa.product_addon_id = pad.product_id and pa.product_addon_id = :products_id", cardinality::SET, [':products_id' => $_GET['pID']]);
																$parent_checked_list = [];
																foreach ($pa_parent_addons_query as $pa_parent_addon_values) {
																	$parentId = $pa_parent_addon_values['product_id'];
																	$childId = $pa_parent_addon_values['product_addon_id'];
																	$parentName = $pa_parent_addon_values['products_name'];
																	$recommended = $pa_parent_addon_values['recommended'];
																	$included = $pa_parent_addon_values['included'];
																	$bundle_quantity = $pa_parent_addon_values['bundle_quantity'];
																	$allow_mult_opts = $pa_parent_addon_values['allow_mult_opts'];
																	$use_custom_price = $pa_parent_addon_values['use_custom_price'];
																	$default_price = $pa_parent_addon_values['default_price']!=null?$pa_parent_addon_values['default_price']:@$pInfo->products_price;
																	$custom_price = $pa_parent_addon_values['custom_price']!=null&&$pa_parent_addon_values['custom_price']!=''?$pa_parent_addon_values['custom_price']:$default_price;
																	$default_name = @$pInfo->products_name;
																	$use_custom_name = $pa_parent_addon_values['use_custom_name'];
																	$custom_name = $pa_parent_addon_values['custom_name']!=null&& $pa_parent_addon_values['custom_name']!=''?$pa_parent_addon_values['custom_name']:$default_name;
																	$use_custom_desc = $pa_parent_addon_values['use_custom_desc'];
																	$default_desc = $pa_parent_addon_values['default_desc']!=null?$pa_parent_addon_values['default_desc']:'';
																	$custom_desc = $pa_parent_addon_values['custom_desc']!=null&&$pa_parent_addon_values['custom_desc']!=''?$pa_parent_addon_values['custom_desc']:$default_desc;
																	$addon_uid = 'pa_'.$pa_parent_addon_values['product_id'].'_'.$pa_parent_addon_values['product_addon_id'];
																	$parent_cat_id = prepared_query::fetch('select categories_id from products_to_categories where products_id = :products_id', cardinality::SINGLE, [':products_id' => $parentId]);
																	get_parent_cats($parent_cat_id, true);
																	$parent_checked_list = array_merge($parent_checked_list, $parent_cat_array); ?>
																<tr id="<?= $addon_uid; ?>">
																	<td style="font-size: 10px;">
																		<a href="categories.php?action=new_product&pID=<?= $parentId; ?>"><?= $parentName; ?></a>
																	</td>
																	<td style="font-size: 10px;" align="center">
																		<input type="checkbox" onChange="toggleIncluded(this.checked, '<?= $addon_uid; ?>');" <?php if ($included) { ?>checked<?php } ?> name="<?= $addon_uid; ?>_included">
																	</td>
																	<td style="font-size: 10px;" align="center">
																		<input type="text" size="4" name="<?= $addon_uid; ?>_bundle_quantity" id="<?= $addon_uid; ?>_bundle_quantity" style="<?= !$included?'display:none;':''; ?>" value="<?= $bundle_quantity; ?>">
																	</td>
																	<td style="font-size: 10px;" align="center">
																		<input type="checkbox" <?php if ($recommended) { ?>checked<?php } ?> name="<?= $addon_uid; ?>_recommended" id="<?= $addon_uid; ?>_recommended" <?php if ($included) {?>style="display:none;"<?php } ?>>
																	</td>
																	<td style="font-size: 10px;" align="center">
																		<input type="text" value="<?= $allow_mult_opts; ?>" name="<?= $addon_uid; ?>_allow_mult_opts" id="<?= $addon_uid; ?>_allow_mult_opts" <?php if ($included) {?>style="display:none;"<?php } ?>>
																	</td>
																	<td style="font-size: 10px;" align="center">
																		<input type="checkbox" onChange="toggleCustomPrice(this.checked, '<?= $addon_uid; ?>');" <?php if ($use_custom_price) { ?>checked<?php } ?> name="<?= $addon_uid; ?>_use_custom_price" id="<?= $addon_uid; ?>_use_custom_price" <?php if ($included) {?>style="display:none;"<?php } ?>>
																	</td>
																	<td style="font-size: 10px;">
																		<input type="text" value="<?= number_format($custom_price, 2, '.', ',')?>" size="10" name="<?= $addon_uid; ?>_custom_price" id="<?= $addon_uid; ?>_custom_price" <?php if (!$use_custom_price || $included) {?> style="display:none"<?php } ?>>
																		<span id="<?= $addon_uid; ?>_default_price" <?php if ($use_custom_price || $included) {?> style="display:none"<?php } ?>><?= number_format($default_price, 2, '.', ',');?></span>
																	</td>
																	<td style="font-size: 10px;" align="center">
																		<input type="checkbox" onChange="toggleCustomName(this.checked, '<?= $addon_uid?>');" <?php if ($use_custom_name) { ?>checked<?php } ?> name="<?= $addon_uid; ?>_use_custom_name">
																	</td>
																	<td style="font-size: 10px;">
																		<textarea row="3" cols="40" name="<?= $addon_uid; ?>_custom_name" id="<?= $addon_uid; ?>_custom_name" <?php if (!$use_custom_name) {?> style="display:none"<?php } ?>><?= $custom_name; ?></textarea>
																		<span id="<?= $addon_uid; ?>_default_name" <?php if ($use_custom_name) {?> style="display:none"<?php } ?>><?= $default_name; ?></span>
																	</td>
																	<td style="font-size: 10px;" align="center">
																		<input type="checkbox" onChange="toggleCustomDesc(this.checked, '<?= $addon_uid?>');" <?php if ($use_custom_desc) { ?>checked<?php } ?> name="<?= $addon_uid; ?>_use_custom_desc">
																	</td>
																	<td style="font-size: 10px;">
																		<textarea row="3" cols="40" name="<?= $addon_uid; ?>_custom_desc" id="<?= $addon_uid; ?>_custom_desc" <?php if (!$use_custom_desc) {?> style="display:none"<?php } ?>><?= $custom_desc; ?></textarea>
																		<span id="<?= $addon_uid; ?>_default_desc" <?php if ($use_custom_desc) {?> style="display:none"<?php } ?>><?= $default_desc; ?></span>
																	</td>
																</tr>
																<?php } ?>
															</table>
															<br>
															<div id="pa_options_parents_add_remove_hidden" style="font-size: 10px;">
																<a href="javascript: void(0);" onClick="document.getElementById('pa_options_parents_add_remove_hidden').style.display='none'; document.getElementById('pa_options_parents_add_remove').style.display='';"> + </a>
																&nbsp;Add/remove options
															</div>
															<div id="pa_options_parents_add_remove" style="display: none; font-size: 10px;">
																<a href="javascript: void(0);" onClick="document.getElementById('pa_options_parents_add_remove').style.display='none'; document.getElementById('pa_options_parents_add_remove_hidden').style.display='';"> - </a>
																&nbsp;Add/remove options
																<div style="padding: 10px;">
																	<div id="parentTabCon" class="yui-navset">
																		<ul class="yui-nav">
																			<li class="selected"><a href="#parent_cat_tree"><em>Category tree view</em></a></li>
																			<li><a href="#parent_ipn_search"><em>IPN search</em></a></li>
																		</ul>
																		<div class="yui-content" style="padding: 15px;">
																			<div id="parent_cat_tree">
																				<?php
																				$parentProductTree = new productCheckTreeView("parentTree");
																				$parentProductTree->setProductCheckedList($parent_checked_list);
																				$parentProductTree->setOnProductCheck("createProductAddon(%product_id%, ".$_GET['pID'].", 'pa_parents_table', %product_id%);addIdToParentsCheckedList('%product_id%');");
																				$parentProductTree->setOnProductUncheck("removeProductAddon(%product_id%, ".$_GET['pID'].", 'pa_parents_table', 'pa_%product_id%_".$_GET['pID']."');removeIdFromParentsCheckedList('%product_id%');");
																				$parentProductTree->setTreeType('parent');
																				$parentProductTree->renderAjaxTree();
																				?>
																			</div>
																			<div id="parent_ipn_search">
																				<style type="text/css">
																					#parent_ipn_search_input {width:15em;height:1.4em;}
																					#parent_ipn_search_container {position:absolute;z-index:9050;}
																					#parent_ipn_search_container .yui-ac-content {position:absolute;left:0;top:0;width:15em;border:1px solid #404040;background:#fff;overflow:hidden;text-align:left;z-index:9050;}
																					#parent_ipn_search_container .yui-ac-shadow {position:absolute;left:0;top:0;margin:.3em;background:#a0a0a0;z-index:9049;}
																					#parent_ipn_search_container ul {padding:5px 0;width:100%;}
																					#parent_ipn_search_container li {padding:0 5px;cursor:default;white-space:nowrap;}
																					#parent_ipn_search_container li.yui-ac-highlight {background:#ff0;}
																				</style>
																				<label>IPN Lookup:</label>
																				<input id="parent_ipn_search_input">
																				<div id="parent_ipn_search_container"></div>
																				<div id="parent_ipn_product_container"></div>
																			</div>
																		</div>
																	</div>
																</div>
																<script>
																	parentTabConInit = function() {
																		var tabView = new YAHOO.widget.TabView('parentTabCon');
																	};

																	parentTabConInit();

																	var parentsCheckedList = [];
																	<?php
																	for ($i = 0; $i < count($parent_checked_list); $i++) {
																	?>
																								parentsCheckedList[parentsCheckedList.length] = '<?= $parent_checked_list[$i];?>';
																	<?php
																	}
																	?>

																	function addIdToParentsCheckedList(id) {
																		parentsCheckedList[parentsCheckedList.length] = id;
																	}

																	function removeIdFromParentsCheckedList(id) {
																		var newArray = [];
																		for (var j = 0; j < parentsCheckedList.length; j++) {
																			if (parentsCheckedList[j] != id) {
																				newArray[newArray.length] = parentsCheckedList[j];
																			}
																		}
																		parentsCheckedList = newArray;
																	}


																	parentIpnSearchInit = function() {
																		var pist_xhr_ds = new YAHOO.widget.DS_XHR("ipn_autoCompleteQuery.php", ["results","name"]);
																		pist_xhr_ds.queryMatchContains = true;

																		// Instantiate AutoComplete
																		var pist_auto_complete = new YAHOO.widget.AutoComplete("parent_ipn_search_input","parent_ipn_search_container", pist_xhr_ds, {"forceSelection":true});
																		pist_auto_complete.useShadow = true;
																		pist_auto_complete.typeAhead = true;
																		pist_auto_complete.forceSelection = true;
																		pist_auto_complete.forceSelection = true;
																		pist_auto_complete.formatResult = function(oResultItem, sQuery) {
																			return oResultItem[1].name;
																		};
																		pist_auto_complete.doBeforeExpandContainer = function(oTextbox, oContainer, sQuery, aResults) {
																			var pos = YAHOO.util.Dom.getXY(oTextbox);
																			pos[1] += YAHOO.util.Dom.get(oTextbox).offsetHeight;
																			YAHOO.util.Dom.setXY(oContainer,pos);
																			return true;
																		};
																		pist_auto_complete.itemSelectEvent.subscribe(function(type, args) {

																			var callback = {
																				success: function(o) {
																					var result = eval(o.responseText);

																					//get the HTML element we will update
																					var divElem = document.getElementById("parent_ipn_product_container");
																					divElem.innerHTML = "";

																					//create the ul we will use
																					var ulElem = document.createElement('ul');
																					ulElem.style.listStyleType = 'none';
																					for (var i = 0; i < result.results.length; i++) {

																						//first we look if this item should be checked
																						var isChecked = false;
																						for (var j = 0; j < parentsCheckedList.length; j++) {
																							if (parentsCheckedList[j] == result.results[i].id) {
																								isChecked = true;
																							}
																						}

																						var liElem = document.createElement('li');
																						liElem.style.padding = '5px';

																						var checkElem = document.createElement('input');
																						checkElem.type='checkbox';
																						if (isChecked) {
																							checkElem.checked = true;
																						}
																						checkElem.id = result.results[i].id;
																						checkElem.onclick = function() {
																							if (this.checked) {
																								addIdToParentsCheckedList(this.id);
																								tree_parentTree.getNodeByProperty('product_id', this.id).check();
																								createProductAddon(this.id, "<?= $_GET['pID']; ?>", 'pa_parents_table', this.id);
																							}
																							else {
																								removeIdFromParentsCheckedList(this.id);
																								tree_parentTree.getNodeByProperty('product_id', this.id).uncheck();
																								removeProductAddon(this.id, "<?= $_GET['pID']; ?>", 'pa_parents_table', 'pa_' + this.id + '_<?= $_GET['pID']; ?>');
																							}
																						}
																						liElem.appendChild(checkElem);

																						var spanElem = document.createElement('span');
																						spanElem.product_id = result.results[i].id;
																						spanElem.innerHTML = '&nbsp;&nbsp;&nbsp;' + result.results[i].name;
																						spanElem.onmouseover = function(e) {
																							ppd_show(this.product_id, e);
																						}
																						spanElem.onmouseout = function(e) {
																							ppd_hide();
																						}
																						liElem.appendChild(spanElem);

																						ulElem.appendChild(liElem);
																					}

																					divElem.appendChild(ulElem);
																				},
																				failure: function(o) {
																					if (o.responseText !== undefined) {
																						alert("Get products for IPN failed: " + o.responseText);
																					}
																					else {
																						alert("Get products for IPN failed: no error message available");
																					}
																				},
																				argument: []
																			};

																			var url = "ipn_getProductByIPN.php?stock_name=" + args[2][0];

																			YAHOO.util.Connect.asyncRequest('GET', url, callback);
																		});

																	}
																	parentIpnSearchInit();
																</script>
																<br><br>
															</div>
														</div>
													</td>
												</tr>
											</table>
											<script>
												function toggleIncluded(checked, addon_uid) {
													if (checked) {
														document.getElementById(addon_uid + "_bundle_quantity").style.display="";
														document.getElementById(addon_uid + "_recommended").style.display="none";
														document.getElementById(addon_uid + "_allow_mult_opts").style.display="none";
														document.getElementById(addon_uid + "_use_custom_price").style.display="none";
														document.getElementById(addon_uid + "_default_price").style.display="none";
														document.getElementById(addon_uid + "_custom_price").style.display="none";
													}
													else {
														document.getElementById(addon_uid + "_bundle_quantity").style.display="none";
														document.getElementById(addon_uid + "_recommended").style.display="";
														document.getElementById(addon_uid + "_allow_mult_opts").style.display="";
														document.getElementById(addon_uid + "_use_custom_price").style.display="";
														if (document.getElementById(addon_uid + "_use_custom_price").checked) {
															document.getElementById(addon_uid + "_custom_price").style.display=""
														}
														else {
															document.getElementById(addon_uid + "_default_price").style.display="";
														}
													}
												}
												function toggleCustomPrice(checked, addon_uid) {
													if (checked) {
														document.getElementById(addon_uid + "_default_price").style.display="none";
														document.getElementById(addon_uid + "_custom_price").style.display="";
													}
													else {
														document.getElementById(addon_uid + "_default_price").style.display="";
														document.getElementById(addon_uid + "_custom_price").style.display="none";
													}
												}
												function toggleCustomName(checked, addon_uid) {
													if (checked) {
														document.getElementById(addon_uid + "_default_name").style.display="none";
														document.getElementById(addon_uid + "_custom_name").style.display="";
													}
													else {
														document.getElementById(addon_uid + "_default_name").style.display="";
														document.getElementById(addon_uid + "_custom_name").style.display="none";
													}
												}
												function toggleCustomDesc(checked, addon_uid) {
													if (checked) {
														document.getElementById(addon_uid + "_default_desc").style.display="none";
														document.getElementById(addon_uid + "_custom_desc").style.display="";
													}
													else {
														document.getElementById(addon_uid + "_default_desc").style.display="";
														document.getElementById(addon_uid + "_custom_desc").style.display="none";
													}
												}
											</script>
											<?php }
											else {
												//MMD - otherwise display a message instructing user to create the product first
												?>
											<span class="main">You may only set product options for a product that has already been created. Please finish creating this product then return to this screen in order to set product options.</span>
											<?php } ?>
										</div>
									</div>
								</div>

								<table border="0" width="100%" cellspacing="0" cellpadding="2">
									<tr>
										<td><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
									</tr>
									<tr>
										<td class="main" align="right">
											<input type="hidden" name="products_date_added" value="<?= tep_not_null(@$pInfo->products_date_added)?@$pInfo->products_date_added:date('Y-m-d'); ?>">
											<input type="image" src="includes/languages/english/images/buttons/button_preview.gif" border="0" alt="Preview" title=" Preview ">
											<a href="/admin/categories.php?cPath=<?= $cPath.(isset($_GET['pID'])?'&pID='.$_GET['pID']:''); ?>"><img src="includes/languages/english/images/buttons/button_cancel.gif" border="0" alt="Cancel" title=" Cancel "></a>
										</td>
									</tr>
								</table>
							</form>
							<script>
								jQuery('#new_product_submit_form').submit(function(e) {
									if (jQuery('#manufacturers_id').val() == '') {
										alert('You must select a manufacturer to create a product.');
										e.preventDefault();
									}
								});
							</script>
						</td>
					</tr>
					<?php }
					elseif ($action == 'new_product_preview') {
						if (tep_not_null($_POST)) {
							$pInfo = (object)$_POST;
							$products_ebay_name = @$_POST['products_ebay_name'];
							$products_google_name = @$_POST['productes_google_name'];
							$products_name = @$_POST['products_name'];
							$products_description = @$_POST['products_description'];
							$products_head_title_tag = @$_POST['products_head_title_tag'];
							$products_head_desc_tag = @$_POST['products_head_desc_tag'];
							$products_url = !empty($_POST['products_url'])?$_POST['products_url']:NULL;
						}
						else {
							$product = prepared_query::fetch('SELECT p.products_id, p.allow_mult_opts, pd.language_id, pd.products_name, pd.products_ebay_name, pd.products_description, pd.products_head_title_tag, pd.products_head_desc_tag, pd.products_url, p.products_quantity, p.stock_id, p.products_model, p.products_price, p.products_weight, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.manufacturers_id, p.use_seo_urls, p.seo_url_text FROM products p JOIN products_description pd ON p.products_id = pd.products_id WHERE p.products_id = :products_id', cardinality::ROW, [':products_id' => $_GET['pID']]);

							$pInfo = (object)$product;
							$allow_mult_opts = @$pInfo->allow_mult_opts;
						}

						$form_action = (isset($_GET['pID']))?'update_product':'insert_product'; ?>
					<tr>
						<td>
							<form name="<?= $form_action; ?>" action="/admin/categories.php?cPath=<?= $cPath; ?><?= (isset($_GET['pID'])?'&pID='.$_GET['pID']:''); ?>&action=<?= $form_action; ?>" method="post" enctype="multipart/form-data">
								<?php if (isset($_GET['read']) && ($_GET['read'] == 'only')) {
									$ckp = new ck_product_listing(@$pInfo->products_id);
									@$pInfo->products_ebay_name = $ckp->get_header('products_ebay_name');
									@$pInfo->products_name = $ckp->get_header('products_name');
									@$pInfo->products_description = $ckp->get_header('products_description');
									@$pInfo->products_head_title_tag = $products_head_title_tag[1];
									@$pInfo->products_head_desc_tag = $products_head_desc_tag[1];
									@$pInfo->products_url = $ckp->get_header('products_url');
								}
								else {
									@$pInfo->products_ebay_name = $products_ebay_name[1];
									@$pInfo->products_name = $products_name[1];
									@$pInfo->products_google_name = $products_google_name[1];
									@$pInfo->products_description = $products_description[1];
									@$pInfo->products_head_title_tag = $products_head_title_tag[1];
									@$pInfo->products_head_desc_tag = $products_head_desc_tag[1];
									@$pInfo->products_url = $products_url[1];
								} ?>
								<table border="0" width="100%" cellspacing="0" cellpadding="2">
									<tr>
										<td>
											<table border="0" width="100%" cellspacing="0" cellpadding="0">
												<tr>
													<td class="pageHeading"><?= @$pInfo->products_name; ?></td>
													<td class="pageHeading" align="right"><?= CK\text::monetize(@$pInfo->products_price); ?></td>
												</tr>
											</table>
										</td>
									</tr>
									<tr>
										<td><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
									</tr>
									<tr>
										<td class="main">
											<?= @$pInfo->products_description; ?>
										</td>
									</tr>
									<?php if (@$pInfo->products_url) { ?>
									<tr>
										<td><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
									</tr>
									<tr>
										<td class="main"><?= sprintf(TEXT_PRODUCT_MORE_INFORMATION, @$pInfo->products_url); ?></td>
									</tr>
									<?php } ?>
									<tr>
										<td><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
									</tr>
									<tr>
										<td>
											<?php if (empty($_GET['pID'])) { ?>
											<strong>These images will be copied from the parent IPN</strong>
											<?php }

											$images = prepared_query::fetch('SELECT psc.stock_id, psci.image as products_image, psci.image_med as products_image_med, psci.image_lrg as products_image_lrg, psci.image_sm_1 as products_image_sm_1, psci.image_xl_1 as products_image_xl_1, psci.image_sm_2 as products_image_sm_2, psci.image_xl_2 as products_image_xl_2, psci.image_sm_3 as products_image_sm_3, psci.image_xl_3 as products_image_xl_3, psci.image_sm_4 as products_image_sm_4, psci.image_xl_4 as products_image_xl_4, psci.image_sm_5 as products_image_sm_5, psci.image_xl_5 as products_image_xl_5, psci.image_sm_6 as products_image_sm_6, psci.image_xl_6 as products_image_xl_6 FROM products_stock_control psc LEFT JOIN products_stock_control_images psci ON psc.stock_id = psci.stock_id WHERE psc.stock_id = ?', cardinality::ROW, array($_POST['stock_id']));
											?>
											<style>
												#pic_list { font-size:8pt; }
												#pic_list th, #pic_list td { padding:3px 6px; border-style:solid; border-width:1px 0px 0px 1px; }
												#pic_list tr:last-child th, #pic_list tr:last-child td, #pic_list tr.demark td, #pic_list td.imgsize { border-bottom-width:1px; }
												#pic_list th:last-child, #pic_list td:last-child { border-right-width:1px; }
												#pic_list th { background-color:#e6eeee; border-color:#fff; }
												#pic_list td { background-color:#fff; }
												#pic_list td.found { background-color:#cfc; }
												#pic_list td.missing { background-color:#fcc; }
												#pic_list .show-actual-size { display:block; height:120px; width:180px; }
												#pic_list .show-actual-size img { height:120px; width:180px; position:absolute; border:1px solid #00f; }
												#pic_list .show-actual-size img:hover { height:auto; width:auto; z-index:100; border-color:#fff; }
											</style>
											<table border="0" cellspacing="0" cellpadding="0" id="pic_list">
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
														<td rowspan="3" class="imgsize">Full<br>(<?= imagesizer::$map['lrg']['width'].'x'.imagesizer::$map['lrg']['height']; ?>)</td>
														<td>CDN</td>
														<td><?php if (!empty($images['products_image_lrg'])) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= $images['products_image_lrg']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['products_image_lrg']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
														<td><?php if (!empty($images['products_image_xl_1'])) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= $images['products_image_xl_1']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['products_image_xl_1']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
														<td><?php if (!empty($images['products_image_xl_2'])) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= $images['products_image_xl_2']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['products_image_xl_2']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
														<td><?php if (!empty($images['products_image_xl_3'])) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= $images['products_image_xl_3']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['products_image_xl_3']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
														<td><?php if (!empty($images['products_image_xl_4'])) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= $images['products_image_xl_4']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['products_image_xl_4']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
														<td><?php if (!empty($images['products_image_xl_5'])) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= $images['products_image_xl_5']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['products_image_xl_5']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
														<td><?php if (!empty($images['products_image_xl_6'])) { ?><a class="show-actual-size" href="https://media.cablesandkits.com/<?= $images['products_image_xl_6']; ?>" target="_blank"><img src="https://media.cablesandkits.com/<?= $images['products_image_xl_6']; ?>"></a><?php } else { echo 'NONE'; } ?></td>
													</tr>
													<tr>
														<td>LOCAL</td>
														<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['products_image_lrg'])?'found':'missing'; ?>"><?php if (!empty($images['products_image_lrg'])) { ?><a href="/images/<?= $images['products_image_lrg']; ?>" target="_blank">SEE</a><?php } ?></td>
														<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['products_image_xl_1'])?'found':'missing'; ?>"><?php if (!empty($images['products_image_xl_1'])) { ?><a href="/images/<?= $images['products_image_xl_1']; ?>" target="_blank">SEE</a><?php } ?></td>
														<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['products_image_xl_2'])?'found':'missing'; ?>"><?php if (!empty($images['products_image_xl_2'])) { ?><a href="/images/<?= $images['products_image_xl_2']; ?>" target="_blank">SEE</a><?php } ?></td>
														<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['products_image_xl_3'])?'found':'missing'; ?>"><?php if (!empty($images['products_image_xl_3'])) { ?><a href="/images/<?= $images['products_image_xl_3']; ?>" target="_blank">SEE</a><?php } ?></td>
														<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['products_image_xl_4'])?'found':'missing'; ?>"><?php if (!empty($images['products_image_xl_4'])) { ?><a href="/images/<?= $images['products_image_xl_4']; ?>" target="_blank">SEE</a><?php } ?></td>
														<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['products_image_xl_5'])?'found':'missing'; ?>"><?php if (!empty($images['products_image_xl_5'])) { ?><a href="/images/<?= $images['products_image_xl_5']; ?>" target="_blank">SEE</a><?php } ?></td>
														<td class="<?= file_exists(picture_audit::$imgfolder.'/'.$images['products_image_xl_6'])?'found':'missing'; ?>"><?php if (!empty($images['products_image_xl_6'])) { ?><a href="/images/<?= $images['products_image_xl_6']; ?>" target="_blank">SEE</a><?php } ?></td>
													</tr>
												</tbody>
											</table>
										</td>
									</tr>
									<tr>
										<td><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
									</tr>
									<tr>
										<td align="center" class="smallText">
											<?php if (!empty($pInfo->products_date_added)) {
												$date_added = new DateTime($pInfo->products_date_added);
												$date_added = $date_added->format('l d F, Y');
											}
											else $date_added = '';

											echo sprintf(TEXT_PRODUCT_DATE_ADDED, $date_added); ?>
										</td>
									</tr>
									<tr>
										<td><img src="/admin/images/pixel_trans.gif" border="0" alt="" width="1" height="10"></td>
									</tr>
								</table>
								<?php if (isset($_GET['read']) && ($_GET['read'] == 'only')) {
									if (isset($_GET['origin'])) {
										$pos_params = strpos($_GET['origin'], '?', 0);
										if ($pos_params != false) {
											$back_url = substr($_GET['origin'], 0, $pos_params);
											$back_url_params = substr($_GET['origin'], $pos_params + 1);
										}
										else {
											$back_url = $_GET['origin'];
											$back_url_params = '';
										}
									}
									else {
										$back_url = FILENAME_CATEGORIES;
										$back_url_params = 'cPath='.$cPath.'&pID='.(@$pInfo->products_id);
									} ?>
								<table border="0" width="100%" cellspacing="0" cellpadding="2">
									<tr>
										<td align="right"><a href="<?= '/admin/categories.php?'.$back_url.$back_url_params; ?>"><?= tep_image_button('button_back.gif', IMAGE_BACK); ?></a></td>
									</tr>
								</table>
								<?php }
								else { ?>
								<table border="0" width="100%" cellspacing="0" cellpadding="2">
									<tr>
										<td align="right" class="smallText">
											<?php
											/////////////////////////////////////////////////////////////////////
											// BOF: WebMakers.com Added: Preview Back
											/* Re-Post all POST'ed variables */
											foreach ($_POST as $key => $value) {
												if ($key == 'delete_product_option') {
													foreach ($value as $parentID => $child) {
														foreach ($child as $childID => $on) {
															echo tep_draw_hidden_field($key.'['.$parentID.']['.$childID.']', 'on');
														}
													}
												}
												else {
													if (!is_array($_POST[$key])) {
														echo tep_draw_hidden_field($key, htmlspecialchars(stripslashes($value)));
													}
													else {
														foreach ($value as $k => $v) {
															echo tep_draw_hidden_field($key.'['.$k.']', htmlspecialchars(stripslashes($v)));
														}
													}
												}
											}
											echo tep_draw_hidden_field('products_ebay_name[1]', htmlspecialchars(stripslashes($products_ebay_name[1])));
											echo tep_draw_hidden_field('products_name[1]', htmlspecialchars(stripslashes($products_name[1])));
											echo tep_draw_hidden_field('products_description[1]', htmlspecialchars(stripslashes($products_description[1])));
											echo tep_draw_hidden_field('products_head_title_tag[1]', htmlspecialchars(stripslashes($products_head_title_tag[1])));
											echo tep_draw_hidden_field('products_head_desc_tag[1]', htmlspecialchars(stripslashes($products_head_desc_tag[1])));
											echo tep_draw_hidden_field('products_url[1]', htmlspecialchars(stripslashes($products_url[1])));

											echo tep_draw_hidden_field('allow_mult_opts', stripslashes(@$allow_mult_opts));
											// EOF MaxiDVD: Added For Ultimate Images Pack!
											echo tep_image_submit('button_back.gif', IMAGE_BACK, 'name="edit"').'&nbsp;&nbsp;';

											if (isset($_GET['pID'])) echo tep_image_submit('button_update.gif', IMAGE_UPDATE);
											else echo tep_image_submit('button_insert.gif', IMAGE_INSERT);

											echo '&nbsp;&nbsp;<a href="'.'/admin/categories.php?cPath='.$cPath.(isset($_GET['pID'])?'&pID='.$_GET['pID']:'').'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>'; ?>
										</td>
									</tr>
								</table>
								<?php } ?>
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
</body>
</html>