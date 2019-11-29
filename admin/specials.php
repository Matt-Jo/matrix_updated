<?php
/*
	$Id: specials.php,v 1.1.1.1 2004/03/04 23:38:58 ccwjr Exp $

	osCommerce, Open Source E-Commerce Solutions
	http://www.oscommerce.com

	Copyright (c) 2003 osCommerce

	Released under the GNU General Public License
*/

require('includes/application_top.php');

$action = isset($_GET['action'])?$_GET['action']:'';
if (!empty($action)) {
	switch ($action) {
		case 'setflag':
			tep_set_specials_status($_GET['id'], $_GET['flag']);

			CK\fn::redirect_and_exit('/admin/specials.php?'.(isset($_GET['page']) ? 'page='.$_GET['page'].'&' : '').'sID='.$_GET['id']);

			break;

		case 'insert':
			$stock_id = $_POST['stock_id'];
			if ($stock_id != -1) {
				$specials_price = preg_replace('/[^\d%.]/', '', $_POST['specials_price']);
				$sell_to_qty = is_numeric($_POST['specials_qty'])?(int) $_POST['specials_qty']:NULL;
				$status = (int) $_POST['specials_status'];
				$expiration_date = !empty($_POST['expiration_date'])?new DateTime($_POST['expiration_date']):NULL;

				$stock_price = prepared_query::fetch('SELECT stock_price FROM products_stock_control WHERE stock_id = ?', cardinality::SINGLE, array($stock_id));
				if (preg_match('/%/', $specials_price)) $specials_price = $stock_price - ((preg_replace('/%/', '', $specials_price) / 100) * $stock_price);

				$products = prepared_query::fetch('SELECT products_id, products_model FROM products p WHERE stock_id = ? AND archived = 0', cardinality::SET, array($stock_id));

				$new = 'Status '.($status?'On':'Off').' | Price: '.$specials_price.' | Qty: '.$sell_to_qty.' | Expires: '.(!empty($expiration_date)?$expiration_date->format('m/d/Y 23:59:59'):'NONE');

				foreach ($products as $product) {
					prepared_query::execute('INSERT INTO specials (products_id, specials_new_products_price, specials_date_added, specials_last_modified, expires_date, status, specials_qty, active_criteria) VALUES (:products_id, :specials_new_products_price, NOW(), NOW(), :expires_date, :status, :specials_qty, :active_criteria)', [':products_id' => $product['products_id'], ':specials_new_products_price' => $specials_price, ':expires_date' => !empty($expiration_date)?$expiration_date->format('Y-m-d 23:59:59'):'', ':status' => !empty($status)?1:0, ':specials_qty' => $sell_to_qty, ':active_criteria' => $status]);

					$old = '';
					insert_psc_change_history($stock_id, 'Special Update', $old, $new);
				}
			}

			CK\fn::redirect_and_exit('/admin/specials.php?page='.$_GET['page']);

			break;

		case 'update':
			$specials_id = $_POST['specials_id'];
			$specials_price = preg_replace('/[^\d%.]/', '', $_POST['specials_price']);
			$sell_to_qty = is_numeric($_POST['specials_qty'])?(int) $_POST['specials_qty']:NULL;
			$status = $_POST['specials_status'];
			$expiration_date = !empty($_POST['expiration_date'])?new DateTime($_POST['expiration_date']):NULL;

			$details = prepared_query::fetch('SELECT p.stock_id, p.products_model, p.products_price, s.status, s.specials_new_products_price, s.specials_qty, s.expires_date FROM products p JOIN specials s ON p.products_id = s.products_id WHERE s.specials_id = :specials_id AND p.archived = 0', cardinality::ROW, [':specials_id' => $specials_id]);

			if (preg_match('/%/', $specials_price)) $specials_price = $details['products_price'] - ((preg_replace('/%/', '', $specials_price) / 100) * $details['products_price']);

			$old = $new = array();
			if ($status != $details['status']) {
				$old[] = $details['status']?'Status On':'Status Off';
				$new[] = $status?'Status On':'Status Off';
			}
			if ($specials_price != $details['specials_new_products_price']) {
				$old[] = 'Price: '.$details['specials_new_products_price'];
				$new[] = 'Price: '.$specials_price;
			}
			if ($sell_to_qty != $details['specials_qty']) {
				$old[] = 'Qty: '.$details['specials_qty'];
				$new[] = 'Qty: '.$sell_to_qty;
			}
			$old_expiration_date = ck_datetime::datify($details['expires_date']);
			if ($expiration_date != $old_expiration_date) {
				$old[] = 'Expires: '.(!empty($old_expiration_date)?$old_expiration_date->format('m/d/Y H:i:s'):'');
				$new[] = 'Expires: '.(!empty($expiration_date)?$expiration_date->format('m/d/Y 23:59:59'):'');
			}
			insert_psc_change_history($details['stock_id'], 'Special Update ['.$details['products_model'].']', implode(' | ', $old), implode(' | ', $new));

			prepared_query::execute('UPDATE specials SET specials_new_products_price = ?, specials_qty = ?, specials_last_modified = NOW(), expires_date = ?, status = ?, active_criteria = ? WHERE specials_id = ?', array($specials_price, $sell_to_qty, (!empty($expiration_date)?$expiration_date->format('Y-m-d 23:59:59'):NULL), !empty($status)?1:0, $status, $specials_id));

			CK\fn::redirect_and_exit('/admin/specials.php?page='.$_GET['page'].'&sID='.$specials_id);

			break;

		case 'deleteconfirm':
			$specials_id = $_GET['sID'];

			$details = prepared_query::fetch('SELECT p.stock_id, p.products_model, s.status FROM products p JOIN specials s ON p.products_id = s.products_id WHERE s.specials_id = ?', cardinality::ROW, array($specials_id));
			$old = $details['status']?'Status On':'Status Off';
			insert_psc_change_history($details['stock_id'], 'Special Delete ['.$details['products_model'].']', $old, '');

			prepared_query::execute('DELETE FROM specials WHERE specials_id = ?', array($specials_id));

			CK\fn::redirect_and_exit('/admin/specials.php?page='.$_GET['page']);

			break;

		case 'remove_disabled':
			$admin = prepared_query::fetch('SELECT admin_email_address FROM admin WHERE admin_id = ?', cardinality::SINGLE, array($_SESSION['login_id']));
			$specs = prepared_query::fetch('SELECT p.stock_id, p.products_model FROM products p JOIN specials s ON p.products_id = s.products_id WHERE s.status = 0', cardinality::SET);
			foreach ($specs as $spec) {
				insert_psc_change_history($spec['stock_id'], 'Special Delete ['.$spec['products_model'].']', 'Status Off', '');
			}
			prepared_query::execute('DELETE FROM specials WHERE status = 0');

			CK\fn::redirect_and_exit('/admin/specials.php');

			break;
	}
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<?php if ($action == 'new' || $action == 'edit') { ?>
	<link rel="stylesheet" type="text/css" href="includes/javascript/calendar.css">
	<script language="JavaScript" src="includes/javascript/calendarcode.js"></script>
	<?php } ?>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onload="SetFocus();">
	<div id="popupcalendar" class="text"></div>
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<!-- header_eof //-->
	<script language="javascript" src="includes/menu.js"></script>
	<script language="javascript" src="includes/general.js"></script>

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
						<td width="100%">
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
									<td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
								</tr>
							</table>
						</td>
					</tr>
					<?php if ($action == 'new' || $action == 'edit') {
						$form_action = 'insert';
						if ($action == 'edit' && isset($_GET['sID']) ) {
							$form_action = 'update';

							$product = prepared_query::fetch('SELECT p.products_id, pd.products_name, p.products_price, s.specials_new_products_price, s.expires_date, s.specials_qty, s.status, s.active_criteria FROM products p JOIN products_description pd ON p.products_id = pd.products_id AND pd.language_id = ? JOIN specials s ON p.products_id = s.products_id WHERE s.specials_id = ?', cardinality::ROW, array($_SESSION['languages_id'], $_GET['sID']));
							$sInfo = (object) $product;
							$sInfo->expires_date = ck_datetime::datify($sInfo->expires_date);
						}
						else {
							$sInfo = (object) array();
						} ?>
					<tr>

						<form name="new_special" action="<?= '/admin/specials.php?'.tep_get_all_get_params(array('action', 'info', 'sID')).'action='.$form_action; ?>" method="post">

							<?php if ($form_action == 'update') echo tep_draw_hidden_field('specials_id', $_GET['sID']); ?>
						<td>
							<table border="0" cellspacing="0" cellpadding="2">
								<tr>
									<td class="main">Product:</td>
									<td class="main">
										<?php if (!empty($sInfo->products_name)) {
											echo $sInfo->products_name.' <small>('.CK\text::monetize($sInfo->products_price).')</small>';
										}
										else { ?>
										<input type="text" name="stock_name" id="autocomplete_search_box" value="">
										<input type="hidden" name="stock_id" id="auto_search_stock_id" value="">
										<script language="javascript">
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
															special: 1
														},
														success: function(data) {
															response(jQuery.map(data, function(item) {
																if (item.value == null) item.value = item.label;
																if (item.data_display == null) item.data_display = item.label;
																return {
																	misc: item.value,
																	label: item.data_display,
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
										<?php } ?>
									</td>
									<td><?php if ($action == 'new') { ?>(items with a black background already have a special, they will not show up unless you enter them explicitly)<?php } ?></td>
								</tr>
								<tr>
									<td class="main">Special Price:</td>
									<td class="main"><input type="text" name="specials_price" value="<?php echo isset($sInfo->specials_new_products_price)?$sInfo->specials_new_products_price:''; ?>"></td>
									<td></td>
								</tr>
								<tr>
									<td class="main">Reserve Qty:</td>
									<td class="main"><input type="text" name="specials_qty" value="<?php echo isset($sInfo->specials_qty)?$sInfo->specials_qty:''; ?>"></td>
									<td></td>
								</tr>
								<tr>
									<td class="main">Last Active Date:</td>
									<td class="main"><input type="date" name="expiration_date" value="<?php echo !empty($sInfo->expires_date)?$sInfo->expires_date->format('Y-m-d'):''; ?>"></td>
									<td></td>
								</tr>
								<tr>
									<td class="main">Status:</td>
									<td class="main">
										<select name="specials_status" size="1">
											<option value="1" <?php echo !empty($sInfo->status)&&$sInfo->status==1&&isset($sInfo->active_criteria)&&$sInfo->active_criteria==1?'selected':''; ?>>ACTIVE thru SET QTY</option>
											<option value="2" <?php echo !empty($sInfo->status)&&$sInfo->status==1&&isset($sInfo->active_criteria)&&$sInfo->active_criteria==2?'selected':''; ?>>ACTIVE thru STOCK QTY</option>
											<option value="3" <?php echo !empty($sInfo->status)&&$sInfo->status==1&&isset($sInfo->active_criteria)&&$sInfo->active_criteria==3?'selected':''; ?>>ACTIVE thru EXP. DATE</option>
											<option value="0" <?php echo (!isset($sInfo->status)||$sInfo->status==0)||(isset($sInfo->active_criteria)&&$sInfo->active_criteria==0)?'selected':''; ?>>INACTIVE</option>
										</select>
									</td>
									<td></td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="2">
								<tr>
									<td class="main"><br><?php echo TEXT_SPECIALS_PRICE_TIP; ?></td>
									<td class="main" align="right" valign="top">
										<br>
										<?php echo ($form_action=='insert')?tep_image_submit('button_insert.gif', IMAGE_INSERT):tep_image_submit('button_update.gif', IMAGE_UPDATE); ?>
										&nbsp;&nbsp;&nbsp;

										<a href="<?= '/admin/specials.php?page='.$_GET['page'].(isset($_GET['sID'])?'&sID='.$_GET['sID']:''); ?>"><?php echo tep_image_button('button_cancel.gif', IMAGE_CANCEL); ?></a>

									</td>
								</tr>
							</table>
						</td>
						</form>
					</tr>
					<?php }
					else { ?>
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td valign="top">
										<table border="0" width="100%" cellspacing="0" cellpadding="2">
											<?php /* BEGIN SEARCH BOX */ ?>
											<tr>
												<td colspan="6" style="font-family: arial;">
													<b>IMPORTANT NOTE: Setting the special quantity to 999999 will cause the catalog to ignore the special quantity and leave it on regardless of the stock quantity.</b>
												</td>
											</tr>
											<tr>
												<td colspan="4" style="font-family: arial;">
													IPN Search:
													<input type="text" id="ipn_search" name="ipn_search" value="<?= @$_GET['ipnId']; ?>">
													<script language="javascript">
														<?php /* MMD - taken from IPN Editor and modified */ ?>
														jQuery(document).ready(function($) {
															$('#ipn_search').autocomplete({
																minChars: 3,
																source: function(request, response) {
																	$.ajax({
																		minLength: 2,
																		url: '/admin/serials_ajax.php?' +
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
																				return {
																					misc: item.value,
																					label: item.label,
																					value: item.label
																				}
																			}))
																		}
																	});
																},
																select: function(event, ui) {
																	var newLoc;
																	newLoc = "specials.php?ipnId=" + urlencode(ui.item.value);
																	window.location = newLoc;
																}
															});
														});
													</script>
												</td>
												<td colspan="2" style="font-family:arial;">
													<a id="remove_disabled" href="<?= $_SERVER['PHP_SELF']; ?>?action=remove_disabled" style="display:block;text-align:center;width:150px;padding:6px 0px;background-color:#ccc;border:1px solid #036; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px;">[REM DISABLED]</a>
													<script>
														jQuery('#remove_disabled').click(function(e) {
															if (!confirm('Are you sure you want to remove all disabled specials?')) {
																e.preventDefault();
																return false;
															}
														});
													</script>
												</td>
											</tr>
											<?php /* END SEARCH BOX */ ?>
											<tr class="dataTableHeadingRow">
												<td class="dataTableHeadingContent">Products</td>
												<td class="dataTableHeadingContent">Product Model</td>
												<td class="dataTableHeadingContent">IPN</td>
												<td class="dataTableHeadingContent" align="right">Products Price</td>
												<td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_STATUS; ?></td>
												<td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
											</tr>
											<?php
											$criteria = [':ipn' => !empty($_GET['ipnId'])?$_GET['ipnId']:NULL];
											$specials_count = prepared_query::fetch('SELECT COUNT(p.products_id) FROM products p JOIN specials s ON p.products_id = s.products_id JOIN products_description pd ON p.products_id = pd.products_id AND pd.language_id = 1 JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE :ipn IS NULL OR psc.stock_name LIKE :ipn', cardinality::SINGLE, $criteria);
											$specials_list = prepared_query::page_fetch('SELECT p.products_id, p.products_image, pd.products_name, p.products_model, psc.stock_name, p.products_price, s.specials_id, s.specials_new_products_price, s.specials_qty, s.specials_date_added, s.specials_last_modified, s.expires_date, s.date_status_change, s.status FROM products p JOIN specials s ON p.products_id = s.products_id JOIN products_description pd ON p.products_id = pd.products_id AND pd.language_id = 1 JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE :ipn IS NULL OR psc.stock_name LIKE :ipn ORDER BY pd.products_name', @$_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $criteria);

											foreach ($specials_list as $specials) {
												if (empty($sInfo) && (empty($_GET['sID']) || $_GET['sID'] == $specials['specials_id'])) {
													$sInfo = (object) $specials;
													if (!empty($_GET['ipnId'])) { ?>
											<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href='/admin/specials.php?ipnId=<?= @$_GET['ipnId']; ?>&sID=<?= $sInfo->specials_id; ?>&action=edit';">
													<?php }
													else { ?>
											<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href='/admin/specials.php?page=<?= @$_GET['page']; ?>&sID=<?= $sInfo->specials_id; ?>&action=edit';">
													<?php }
												}
												else {
													if (!empty($_GET['ipnId'])) { ?>
											<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href='<?= '/admin/specials.php?ipnId='.@$_GET['ipnId'].'&sID='.$specials['specials_id']; ?>'">
													<?php }
													else { ?>
											<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href='<?= '/admin/specials.php?page='.@$_GET['page'].'&sID='.$specials['specials_id']; ?>'">
													<?php }
												} ?>
												<td class="dataTableContent"><?= $specials['products_name']; ?></td>
												<td class="dataTableContent"><?= $specials['products_model']; ?></td>
												<td class="dataTableContent"><?= $specials['stock_name']; ?></td>
												<td class="dataTableContent" align="right"><span class="oldPrice"><?php echo CK\text::monetize($specials['products_price']); ?></span> <span class="specialPrice"><?php echo CK\text::monetize($specials['specials_new_products_price']); ?></span></td>
												<td class="dataTableContent" align="right">
													<?php if ($specials['status'] == '1') {

														echo tep_image(DIR_WS_IMAGES.'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10).'&nbsp;&nbsp;<a href="/admin/specials.php?action=setflag&flag=0&id='.$specials['specials_id'].'">'.tep_image(DIR_WS_IMAGES.'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10).'</a>';
													}
													else {
														echo '<a href="/admin/specials.php?action=setflag&flag=1&id='.$specials['specials_id'].'">'.tep_image(DIR_WS_IMAGES.'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10).'</a>&nbsp;&nbsp;'.tep_image(DIR_WS_IMAGES.'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);

													} ?>
												</td>
												<td class="dataTableContent" align="right">
													<?php if (isset($sInfo) && is_object($sInfo) && ($specials['specials_id'] == $sInfo->specials_id)) {
														echo tep_image(DIR_WS_IMAGES.'icon_arrow_right.gif', '');
													}
													else {
														if (!empty($_GET['ipnId'])) {
															echo '<a href="/admin/specials.php?ipnId='.@$_GET['ipnId'].'&sID='.$specials['specials_id'].'">'.tep_image(DIR_WS_IMAGES.'icon_info.gif', IMAGE_ICON_INFO).'</a>';
														}
														else {
															echo '<a href="/admin/specials.php?page='.@$_GET['page'].'&sID='.$specials['specials_id'].'">'.tep_image(DIR_WS_IMAGES.'icon_info.gif', IMAGE_ICON_INFO).'</a>';

														}
													} ?>
												</td>
											</tr>
											<?php } ?>
											<tr>
												<td colspan="4">
													<table border="0" width="100%" cellpadding="0"cellspacing="2">
														<tr>
															<td class="smallText" valign="top">
																<?php $page = !empty($_GET['page'])?$_GET['page']:1;
																$last_page = ceil($specials_count / MAX_DISPLAY_SEARCH_RESULTS); ?>
																Displaying <strong><?= (($page-1)*MAX_DISPLAY_SEARCH_RESULTS)+1; ?></strong> to <strong><?= min($page*MAX_DISPLAY_SEARCH_RESULTS, $specials_count); ?></strong> (of <?= $specials_count; ?> products on special)
															</td>
															<td class="smallText" align="right">
																<form name="pages" action="/admin/specials.php" method="get">
																	<?php if ($page == 1) { ?>
																	&lt;&lt;
																	<?php }
																	else { ?>
																	<a href="/admin/specials.php?page=<?= $page-1; ?>" class="splitPageLink">&lt;&lt;</a>
																	<?php } ?>
																	Page
																	<select id="page" name="page" onchange="this.form.submit();">
																		<?php for ($i=1; $i<= $last_page; $i++) { ?>
																		<option value="<?= $i; ?>" id="<?= $i; ?>" <?= $i==$page?'selected':''; ?>><?= $i; ?></option>
																		<?php } ?>
																	</select> of <?= $last_page; ?>
																	<?php if ($page == $last_page) { ?>
																	&gt;&gt;
																	<?php }
																	else { ?>
																	<a href="/admin/specials.php?page=<?= $page+1; ?>" class="splitPageLink">&gt;&gt;</a>
																	<?php } ?>
																</form>
															</td>
														</tr>
														<?php if (empty($action)) { ?>
														<tr>
															<td colspan="2" align="right"><?php echo '<a href="/admin/specials.php?page='.(@$_GET['page']).'&action=new'.'">'.tep_image_button('button_new_product.gif', IMAGE_NEW_PRODUCT).'</a>'; ?></td>

														</tr>
														<?php } ?>
													</table>
												</td>
											</tr>
										</table>
									</td>
									<?php
									$heading = array();
									$contents = array();

									switch ($action) {
										case 'delete':
											$heading[] = array('text' => '<b>'.TEXT_INFO_HEADING_DELETE_SPECIALS.'</b>');

											$contents = array('form' => tep_draw_form('specials', FILENAME_SPECIALS, 'page='.$_GET['page'].'&sID='.$sInfo->specials_id.'&action=deleteconfirm'));
											$contents[] = array('text' => TEXT_INFO_DELETE_INTRO);
											$contents[] = array('text' => '<br><b>'.$sInfo->products_name.'</b>');

											$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_delete.gif', IMAGE_DELETE).'&nbsp;<a href="/admin/specials.php?page='.$_GET['page'].'&sID='.$sInfo->specials_id.'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');

											break;

										default:
											if (!empty($sInfo)) {
												$heading[] = array('text' => '<b>'.$sInfo->products_name.'</b>');
												$contents[] = array('align' => 'center', 'text' => '<a href="/admin/specials.php?page='.(@$_GET['page']).'&sID='.$sInfo->specials_id.'&action=edit'.'">'.tep_image_button('button_edit.gif', IMAGE_EDIT).'</a> <a href="/admin/specials.php?page='.(@$_GET['page']).'&sID='.$sInfo->specials_id.'&action=delete'.'">'.tep_image_button('button_delete.gif', IMAGE_DELETE).'</a>');

												$date_added = new ck_datetime($sInfo->specials_date_added);
												$last_modified = new ck_datetime($sInfo->specials_last_modified);

												$contents[] = array('text' => '<br>'.TEXT_INFO_DATE_ADDED.' '.$date_added->short_date());
												$contents[] = array('text' => ''.TEXT_INFO_LAST_MODIFIED.' '.$last_modified->short_date());
												$contents[] = array('align' => 'center', 'text' => '<br>'.tep_info_image($sInfo->products_image, $sInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT));
												$contents[] = array('text' => '<br>'.TEXT_INFO_ORIGINAL_PRICE.' '.CK\text::monetize($sInfo->products_price));
												$contents[] = array('text' => ''.TEXT_INFO_NEW_PRICE.' '.CK\text::monetize($sInfo->specials_new_products_price));
												$contents[] = array('text' => ''.TEXT_INFO_PERCENTAGE.' '.number_format(100 - (($sInfo->specials_new_products_price / $sInfo->products_price) * 100)).'%');
												$contents[] = array('text' => ''.'Max Qty:'.' '.$sInfo->specials_qty);

												$expires_date = new ck_datetime($sInfo->expires_date);
												$status_change = new ck_datetime($sInfo->date_status_change);

												$contents[] = array('text' => '<br>'.TEXT_INFO_EXPIRES_DATE.' <b>'.$expires_date->short_date().'</b>');
												$contents[] = array('text' => ''.TEXT_INFO_STATUS_CHANGE.' '.$status_change->short_date());
											}
											break;
									}
									if (tep_not_null($heading) && tep_not_null($contents)) {
										echo ' <td width="25%" valign="top">'."\n";

										$box = new box;
										echo $box->infoBox($heading, $contents);

										echo ' </td>'."\n";
									} ?>
								</tr>
							</table>
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
