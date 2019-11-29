<?php
/*
$Id: coupon_admin.php,v 1.2 2004/03/09 17:56:06 ccwjr Exp $

osCommerce, Open Source E-Commerce Solutions
http://www.oscommerce.com
Copyright (c) 2003 osCommerce

Released under the GNU General Public License
*/

require('includes/application_top.php');

$customer_types = array(-1 => 'ALL', 0 => 'Regular Only', 1 => 'Dealer Only');

if (!isset($_GET['action'])) $_GET['action'] = '';

if (!empty($_GET['selected_box'])) {
	$_GET['action']='';
	$_GET['old_action']='';
}

function create_coupon_code($salt="secret", $length=6) {
	$ccid = md5(uniqid("","salt"));
	$ccid .= md5(uniqid("","salt"));
	$ccid .= md5(uniqid("","salt"));
	$ccid .= md5(uniqid("","salt"));
	srand((double)microtime()*1000000); // seed the random number generator
	$random_start = @rand(0, (128-$length));
	$good_result = 0;
	while ($good_result == 0) {
		$id1=substr($ccid, $random_start,$length);
		if (!($code = prepared_query::fetch("select coupon_code from coupons where coupon_code = :code", cardinality::SINGLE, [':code' => $id1]))) $good_result = 1;
	}
	return $id1;
}

switch ($_GET['action']) {
	case 'confirmdelete':
		prepared_query::execute("update coupons set coupon_active = 'N' where coupon_id=:coupon_id", [':coupon_id' => $_GET['coupon_id']]);
		break;
	case 'update':
		// get all _POST and validate
		$_POST['coupon_code'] = trim($_POST['coupon_code']);
		$_POST['coupon_name'][1] = trim($_POST['coupon_name'][1]);
		$_POST['coupon_desc'][1] = trim($_POST['coupon_desc'][1]);
		$_POST['coupon_amount'] = trim($_POST['coupon_amount']);
		$update_errors = 0;
		if (empty($_POST['coupon_name'])) {
			$update_errors = 1;
			$messageStack->add(ERROR_NO_COUPON_NAME, 'error');
		}
		if ((!$_POST['coupon_amount']) && (!$_POST['coupon_free_ship'])) {
			$update_errors = 1;
			$messageStack->add(ERROR_NO_COUPON_AMOUNT, 'error');
		}
		if (empty($_POST['coupon_code'])) {
			$coupon_code = create_coupon_code();
		}
		if ($_POST['coupon_code']) $coupon_code = $_POST['coupon_code'];

		$coupon_id = prepared_query::fetch('SELECT coupon_id FROM coupons WHERE coupon_code = :coupon_code AND coupon_active = :y', cardinality::SINGLE, [':coupon_code' => $coupon_code, ':y' => 'Y']);
		if (!empty($coupon_id) && $_POST['coupon_code'] && $_GET['oldaction'] != 'voucheredit') {
			$update_errors = 1;
			$messageStack->add(ERROR_COUPON_EXISTS, 'error');
		}
		if ($update_errors != 0) {
			$_GET['action'] = 'new';
		} else {
			$_GET['action'] = 'update_preview';
		}
		break;
	case 'update_confirm':
		if (!empty($_POST['back_x']) || !empty($_POST['back_y'])) {
			$_GET['action'] = 'new';
		}
		else {
			$coupon_type = "F";
			if (substr($_POST['coupon_amount'], -1) == '%') $coupon_type='P';
			if ($_POST['coupon_free_ship']) $coupon_type = 'S';

			$sql_data_array = [
				'coupon_code' => $_POST['coupon_code'],
				'coupon_amount' => $_POST['coupon_amount'],
				'coupon_type' => $coupon_type,
				'uses_per_coupon' => $_POST['coupon_uses_coupon'],
				'uses_per_user' => $_POST['coupon_uses_user'],
				'coupon_minimum_order' => $_POST['coupon_min_order'],
				'restrict_to_products' => $_POST['coupon_products'],
				'restrict_to_categories' => $_POST['coupon_categories'],
				'coupon_start_date' => $_POST['coupon_startdate'],
				'coupon_expire_date' => $_POST['coupon_finishdate'],
				'date_created' => prepared_expression::NOW(),
				'date_modified' => prepared_expression::NOW(),
				'customer_type' => $_POST['customer_type']
			];

			if ($_GET['oldaction'] == 'voucheredit') {
				$update = new prepared_fields($sql_data_array, prepared_fields::UPDATE_QUERY);
				$id = new prepared_fields(['coupon_id' => $_GET['coupon_id']]);

				prepared_query::execute('UPDATE coupons SET '.$update->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($update, $id));

				prepared_query::execute('UPDATE coupons_description SET coupon_name = :coupon_name, coupon_description = :coupon_description WHERE coupon_id = :coupon_id AND language_id = 1', [':coupon_name' => $_POST['coupon_name'][1], ':coupon_description' => $_POST['coupon_desc'][1], ':coupon_id' => $_GET['coupon_id']]);
			}
			else {
				$insert = new prepared_fields($sql_data_array, prepared_fields::INSERT_QUERY);
				$coupon_id = prepared_query::insert('INSERT INTO coupons ('.$insert->insert_fields().') VALUES ('.$insert->insert_values().')', $insert->insert_parameters());

				prepared_query::execute('INSERT INTO coupons_description (coupon_name, coupon_description, coupon_id, language_id) VALUES (:coupon_name, :coupon_description, :coupon_id, 1)', [':coupon_name' => $_POST['coupon_name'][1], ':coupon_description' => $_POST['coupon_desc'][1], ':coupon_id' => $coupon_id]);
			}
		}
		break;
}

function tep_draw_date_selector($prefix, $date='') {
	$month_array = array();
	$month_array[1] ='January';
	$month_array[2] ='February';
	$month_array[3] ='March';
	$month_array[4] ='April';
	$month_array[5] ='May';
	$month_array[6] ='June';
	$month_array[7] ='July';
	$month_array[8] ='August';
	$month_array[9] ='September';
	$month_array[10] ='October';
	$month_array[11] ='November';
	$month_array[12] ='December';
	$usedate = getdate($date);
	$day = $usedate['mday'];
	$month = $usedate['mon'];
	$year = $usedate['year'];
	$date_selector = '<select name="'. $prefix .'_day">';
	for ($i=1;$i<32;$i++) {
	$date_selector .= '<option value="'.$i.'"';
	if ($i==$day) $date_selector .= 'selected';
	$date_selector .= '>'.$i.'</option>';
	}
	$date_selector .= '</select>';
	$date_selector .= '<select name="'. $prefix .'_month">';
	for ($i=1;$i<13;$i++) {
	$date_selector .= '<option value="'.$i.'"';
	if ($i==$month) $date_selector .= 'selected';
	$date_selector .= '>'.$month_array[$i].'</option>';
	}
	$date_selector .= '</select>';
	$date_selector .= '<select name="'. $prefix .'_year">';
	$min_year = date('Y');
	$max_year = date('Y');
	$max_year += 3;
	for ($i=$min_year;$i<$max_year;$i++) {
	$date_selector .= '<option value="'.$i.'"';
	if ($i==$year) $date_selector .= 'selected';
	$date_selector .= '>'.$i.'</option>';
	}
	$date_selector .= '</select>';
	return $date_selector;
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<script language="javascript" src="includes/menu.js"></script>
		<script language="javascript" src="includes/general.js"></script>
		<link rel="stylesheet" type="text/css" href="includes/javascript/spiffyCal/spiffyCal_v2_1.css">
		<script language="JavaScript" src="includes/javascript/spiffyCal/spiffyCal_v2_1.js"></script>
		<script language="javascript">
			var dateAvailable = new ctlSpiffyCalendarBox("dateAvailable", "new_product", "products_date_available","btnDate1","<?php echo $pInfo->products_date_available; ?>",scBTNMODE_CUSTOMBLUE);
		</script>
		<script language="JavaScript">
			function init() {
				define('customers_email_address', 'string', 'Customer or Newsletter Group');
			}
		</script>
	</head>
	<body OnLoad="init()" marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
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
				<?php switch ($_GET['action']) {
					case 'voucherreport': ?>
						<td width="100%" valign="top">
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td>
										<table border="0" width="100%" cellspacing="0" cellpadding="0">
											<tr>
												<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
												<td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
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
															<td class="dataTableHeadingContent">Order Placed</td>
															<td class="dataTableHeadingContent" align="center"><?php echo CUSTOMER_NAME; ?></td>
															<td class="dataTableHeadingContent" align="center">Order #</td>
															<td class="dataTableHeadingContent" align="center">Coupon Value</td>
															<td class="dataTableHeadingContent" align="center">Shipping</td>
															<td class="dataTableHeadingContent" align="center">Tax</td>
															<td class="dataTableHeadingContent" align="center">Original Order Total</td>
															<td class="dataTableHeadingContent" align="center">Actual Order Total</td>
															<td class="dataTableHeadingContent" align="center">Cost</td>
															<td class="dataTableHeadingContent" align="center">Margin</td>
															<?php /* MMD - taking out action column - it's useless
															<td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
															*/ ?>
														</tr>
														<?php
														$ccs = prepared_query::fetch("select crt.*, o.orders_status, o.date_purchased, ifnull(abs((select ot1.value from orders_total ot1 where ot1.orders_id = crt.order_id and ot1.class = 'ot_coupon')), 0) as coupon_value, ifnull((select ot2.value from orders_total ot2 where ot2.orders_id = crt.order_id and ot2.class = 'ot_shipping'), 0) as shipping_value, ifnull((select ot3.value from orders_total ot3 where ot3.orders_id = crt.order_id and ot3.class = 'ot_tax'), 0) as tax_value, ifnull((select ot4.value from orders_total ot4 where ot4.orders_id = crt.order_id and ot4.class = 'ot_total'), 0) as total_value, ifnull((select sum(op.products_quantity * op.cost) from orders_products op where op.orders_id = crt.order_id), 0) as total_cost from coupon_redeem_track crt left join orders o on (o.orders_id = crt.order_id) where crt.coupon_id = :coupon_id order by o.date_purchased asc", cardinality::SET, [':coupon_id' => $_GET['coupon_id']]);

														$coupon_total = 0;
														$shipping_total = 0;
														$tax_total = 0;
														$total_charged = 0;
														$total_cost = 0;

														$h_coupon_total = 0;
														$h_shipping_total = 0;
														$h_tax_total = 0;
														$h_total_charged = 0;
														$h_total_cost = 0;

														$rows = 0;

														foreach ($ccs as $cc_list) {
															$rows++;
															if (strlen($rows) < 2) {
																$rows = '0'.$rows;
															}
															if ((empty($_GET['uid']) || (@$_GET['uid'] == $cc_list['unique_id'])) && empty($cInfo)) {
																$cInfo = (object)$cc_list;
															}
															if (!empty($cInfo) && ($cc_list['unique_id'] == $cInfo->unique_id)) {
																echo '<tr class="dataTableRowSelected" onmouseover="this.style.cursor=\'hand\'" onclick="document.location.href=\''.'/admin/coupon_admin.php?'.tep_get_all_get_params(array('coupon_id', 'action', 'uid')).'coupon_id='.$cInfo->coupon_id.'&action=voucherreport&uid='.$cInfo->unique_id.'\'">'."\n";
															} else {
																//echo '<tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\';this.style.cursor=\'hand\'" onmouseout="this.className=\'dataTableRow\'" onclick="document.location.href=\''.'/admin/coupon_admin.php?'. tep_get_all_get_params(array('coupon_id', 'action','uid')).'coupon_id='.$cc_list['coupon_id'].'&action=voucherreport&uid='.$cc_list['unique_id'].'\'">'."\n";
																//MMD - we don't need to be able to select a row - you can't do anything with it
																echo '<tr class="dataTableRow"';
																if ($cc_list['orders_status'] != '3') {
																	//for orders that aren't shipped yet
																	echo ' style="background-color: #ff3333;" ';
																}
																echo '>'."\n";
															}

															$this_order_cost = $cc_list['total_cost'];
															if ($cc_list['orders_status'] == '3') {
																$coupon_total += $cc_list['coupon_value'];
																$shipping_total += $cc_list['shipping_value'];
																$tax_total += $cc_list['tax_value'];
																$total_charged += $cc_list['total_value'];
																$total_cost += $this_order_cost;
															}
															else {
																//lookup average cost for products on order to put a "hypothetical cost" in
																$this_order_cost = prepared_query::fetch("select sum(op.products_quantity * psc.average_cost) as cost from orders_products op left join products p on (op.products_id = p.products_id) left join products_stock_control psc on (p.stock_id = psc.stock_id) where op.orders_id = :orders_id", cardinality::SINGLE, [':orders_id' => $cc_list['order_id']]);
															}

															$h_coupon_total += $cc_list['coupon_value'];
															$h_shipping_total += $cc_list['shipping_value'];
															$h_tax_total += $cc_list['tax_value'];
															$h_total_charged += $cc_list['total_value'];
															$h_total_cost += $this_order_cost;

															$customer = prepared_query::fetch("select customers_firstname, customers_lastname from customers where customers_id = :customers_id", cardinality::ROW, [':customers_id' => $cc_list['customer_id']]); ?>
															<td class="dataTableContent" nowrap><?php echo date('m/d/y g:i A', strtotime($cc_list['date_purchased'])); ?></td>
															<td class="dataTableContent" align="center"><?php echo $customer['customers_firstname'].' '.$customer['customers_lastname']; ?></td>
															<td class="dataTableContent" align="center"><a href="<?php echo '/admin/orders_new.php?oID='.$cc_list['order_id'].'&action=edit';?>"><?= $cc_list['order_id']; ?></a></td>
															<td class="dataTableContent" align="left">$<?php echo number_format($cc_list['coupon_value'], 2); ?></td>
															<td class="dataTableContent" align="left">$<?php echo number_format($cc_list['shipping_value'], 2); ?></td>
															<td class="dataTableContent" align="left">$<?php echo number_format($cc_list['tax_value'], 2); ?></td>
															<td class="dataTableContent" align="left">$<?php echo number_format(($cc_list['total_value'] + $cc_list['coupon_value']), 2); ?></td>
															<td class="dataTableContent" align="left">$<?php echo number_format($cc_list['total_value'], 2); ?></td>
															<td class="dataTableContent" align="left">$<?php echo number_format($this_order_cost, 2); ?></td>
															<td class="dataTableContent" align="left">$<?php echo number_format(($cc_list['total_value'] - $cc_list['shipping_value'] - $cc_list['tax_value'] - $this_order_cost), 2); ?></td>
															<?php /* MMD - taking out action column - it's useless
															<td class="dataTableContent" align="right"><?php if ((is_object($cInfo)) && ($cc_list['unique_id'] == $cInfo->unique_id)) { echo tep_image(DIR_WS_IMAGES.'icon_arrow_right.gif'); } else { echo '<a href="'.'/admin/coupon_admin.php?page='.$_GET['page'].'&coupon_id='.$cc_list['coupon_id'].'">'.tep_image(DIR_WS_IMAGES.'icon_info.gif', IMAGE_ICON_INFO).'</a>'; } ?>&nbsp;</td>
															*/ ?>
														</tr>
														<?php } ?>
														<tr>
															<td class="dataTableContent" align="left" colspan="3"><strong>Totals:</strong></td>
															<td class="dataTableContent" align="left"><strong>$<?php echo number_format($coupon_total, 2);?></strong></td>
															<td class="dataTableContent" align="left"><strong>$<?php echo number_format($shipping_total, 2);?></strong></td>
															<td class="dataTableContent" align="left"><strong>$<?php echo number_format($tax_total, 2);?></strong></td>
															<td class="dataTableContent" align="left"><strong>$<?php echo number_format(($total_charged + $coupon_total), 2);?></strong></td>
															<td class="dataTableContent" align="left"><strong>$<?php echo number_format($total_charged, 2);?></strong></td>
															<td class="dataTableContent" align="left"><strong>$<?php echo number_format($total_cost, 2);?></strong></td>
															<td class="dataTableContent" align="left"><strong>$<?php echo number_format(($total_charged - $shipping_total - $tax_total - $total_cost), 2);?></strong></td>
														</tr>
														<tr>
															<td class="dataTableContent" align="left" colspan="3"><strong>Hypothetical Totals (including unshipped orders):</strong></td>
															<td class="dataTableContent" align="left"><strong>$<?php echo number_format($h_coupon_total, 2);?></strong></td>
															<td class="dataTableContent" align="left"><strong>$<?php echo number_format($h_shipping_total, 2);?></strong></td>
															<td class="dataTableContent" align="left"><strong>$<?php echo number_format($h_tax_total, 2);?></strong></td>
															<td class="dataTableContent" align="left"><strong>$<?php echo number_format(($h_total_charged + $h_coupon_total), 2);?></strong></td>
															<td class="dataTableContent" align="left"><strong>$<?php echo number_format($h_total_charged, 2);?></strong></td>
															<td class="dataTableContent" align="left"><strong>$<?php echo number_format($h_total_cost, 2);?></strong></td>
															<td class="dataTableContent" align="left"><strong>$<?php echo number_format(($h_total_charged - $h_shipping_total - $h_tax_total - $h_total_cost), 2);?></strong></td>
														</tr>
													</table>
												</td>
												<?php
												$heading = array();
												$contents = array();
												$coupon_desc = prepared_query::fetch("select coupon_name from coupons_description where coupon_id = :coupon_id and language_id = 1", cardinality::SINGLE, [':coupon_id' => $_GET['coupon_id']]);

												$heading[] = array('text' => '<b>['.$_GET['coupon_id'].']'.COUPON_NAME.' '.$coupon_desc.'</b>');
												$contents[] = array('text' => '<b>'.TEXT_REDEMPTIONS.'</b>');
												$contents[] = array('text' => TEXT_REDEMPTIONS_TOTAL.'='.count($ccs));
												$contents[] = array('text' => ''); ?>
												<td width="25%" valign="top">
													<?php $box = new box;
													echo $box->infoBox($heading, $contents); ?>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
						<?php break;
					case 'update_preview': ?>
						<td width="100%" valign="top">
							<table border="0" width="100%" cellspacing="0" cellpadding="2">
								<tr>
									<td>
										<table border="0" width="100%" cellspacing="0" cellpadding="0">
											<tr>
												<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
												<td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td>
										<?php echo tep_draw_form('coupon', 'coupon_admin.php', 'action=update_confirm&oldaction='.$_GET['oldaction'].'&coupon_id='.$_GET['coupon_id']); ?>
										<table border="0" width="100%" cellspacing="0" cellpadding="6">
											<tr>
												<td align="left"><?php echo COUPON_NAME; ?></td>
												<td align="left"><?php echo $_POST['coupon_name'][1]; ?></td>
											</tr>
											<tr>
												<td align="left"><?php echo COUPON_DESC; ?></td>
												<td align="left"><?php echo $_POST['coupon_desc'][1]; ?></td>
											</tr>
											<tr>
												<td align="left"><?php echo COUPON_AMOUNT; ?></td>
												<td align="left"><?= $_POST['coupon_amount']; ?></td>
											</tr>
											<tr>
												<td align="left"><?php echo COUPON_MIN_ORDER; ?></td>
												<td align="left"><?= $_POST['coupon_min_order']; ?></td>
											</tr>

											<tr>
												<td align="left"><?php echo COUPON_FREE_SHIP; ?></td>
												<?php if (!empty($_POST['coupon_free_ship'])) { ?>
												<td align="left"><?php echo TEXT_FREE_SHIPPING; ?></td>
												<?php } else { ?>
												<td align="left"><?php echo TEXT_NO_FREE_SHIPPING; ?></td>
												<?php } ?>
											</tr>
											<tr>
												<td align="left"><?php echo COUPON_CODE; ?></td>
												<?php if (!empty($_POST['coupon_code'])) {
													$c_code = $_POST['coupon_code'];
												}
												else {
													$c_code = $coupon_code;
												} ?>
												<td align="left"><?= $coupon_code; ?></td>
											</tr>
											<tr>
												<td align="left"><?php echo COUPON_USES_COUPON; ?></td>
												<td align="left"><?= $_POST['coupon_uses_coupon']; ?></td>
											</tr>
											<tr>
												<td align="left"><?php echo COUPON_USES_USER; ?></td>
												<td align="left"><?= $_POST['coupon_uses_user']; ?></td>
											</tr>
											<tr>
												<td align="left"><?php echo COUPON_PRODUCTS; ?></td>
												<td align="left"><?= $_POST['coupon_products']; ?></td>
											</tr>
											<tr>
												<td align="left"><?php echo COUPON_CATEGORIES; ?></td>
												<td align="left"><?= $_POST['coupon_categories']; ?></td>
											</tr>
											<tr>
												<td align="left"><?php echo COUPON_STARTDATE; ?></td>
												<?php $start_date = date('m/d/Y', mktime(0, 0, 0, $_POST['coupon_startdate_month'],$_POST['coupon_startdate_day'] ,$_POST['coupon_startdate_year'] )); ?>
												<td align="left"><?= $start_date; ?></td>
											</tr>
											<tr>
												<td align="left"><?php echo COUPON_FINISHDATE; ?></td>
												<?php $finish_date = date('m/d/Y', mktime(0, 0, 0, $_POST['coupon_finishdate_month'],$_POST['coupon_finishdate_day'] ,$_POST['coupon_finishdate_year'] ));
												echo date('Y-m-d', mktime(0, 0, 0, $_POST['coupon_startdate_month'],$_POST['coupon_startdate_day'] ,$_POST['coupon_startdate_year'] )); ?>
												<td align="left"><?= $finish_date; ?></td>
											</tr>
											<tr>
												<td align="left">Customer Type</td>
												<td align="left"><?php echo $customer_types[$_POST['customer_type']]; ?></td>
											</tr>
											<?php
											echo tep_draw_hidden_field('coupon_name[1]', $_POST['coupon_name'][1]);
											echo tep_draw_hidden_field('coupon_desc[1]', $_POST['coupon_desc'][1]);
											echo tep_draw_hidden_field('coupon_amount', $_POST['coupon_amount']);
											echo tep_draw_hidden_field('coupon_min_order', $_POST['coupon_min_order']);
											echo tep_draw_hidden_field('coupon_free_ship', @$_POST['coupon_free_ship']);
											echo tep_draw_hidden_field('coupon_code', $c_code);
											echo tep_draw_hidden_field('coupon_uses_coupon', $_POST['coupon_uses_coupon']);
											echo tep_draw_hidden_field('coupon_uses_user', $_POST['coupon_uses_user']);
											echo tep_draw_hidden_field('coupon_products', $_POST['coupon_products']);
											echo tep_draw_hidden_field('coupon_categories', $_POST['coupon_categories']);
											echo tep_draw_hidden_field('coupon_startdate', date('Y-m-d', mktime(0, 0, 0, $_POST['coupon_startdate_month'],$_POST['coupon_startdate_day'] ,$_POST['coupon_startdate_year'] )));
											echo tep_draw_hidden_field('coupon_finishdate', date('Y-m-d', mktime(0, 0, 0, $_POST['coupon_finishdate_month'],$_POST['coupon_finishdate_day'] ,$_POST['coupon_finishdate_year'] )));
											echo tep_draw_hidden_field('customer_type', $_POST['customer_type']); ?>
											<tr>
												<td align="left"><?php echo tep_image_submit('button_confirm.gif','confirm'); ?></td>
												<td align="left"><?php echo tep_image_submit('button_back.gif','back', 'name=back'); ?></td>
											</tr>
										</table>
										</form>
									</td>
								</tr>
							</table>
						</td>
						<?php break;
					case 'voucheredit':
						$coupon = prepared_query::fetch("select coupon_name,coupon_description from coupons_description where coupon_id = :coupon_id and language_id = 1", cardinality::ROW, [':coupon_id' => $_GET['coupon_id']]);
						$coupon_name[1] = $coupon['coupon_name'];
						$coupon_desc[1] = $coupon['coupon_description'];

						$coupon = prepared_query::fetch("select coupon_code, coupon_amount, coupon_type, coupon_minimum_order, coupon_start_date, coupon_expire_date, uses_per_coupon, uses_per_user, restrict_to_products, restrict_to_categories, customer_type from coupons where coupon_id = :coupon_id", cardinality::ROW, [':coupon_id' => $_GET['coupon_id']]);
						$coupon_amount = $coupon['coupon_amount'];
						if ($coupon['coupon_type']=='P') {
							$coupon_amount .= '%';
						}
						if ($coupon['coupon_type']=='S') {
							$coupon_free_ship .= true;
						}
						$coupon_min_order = $coupon['coupon_minimum_order'];
						$coupon_code = $coupon['coupon_code'];
						$coupon_uses_coupon = $coupon['uses_per_coupon'];
						$coupon_uses_user = $coupon['uses_per_user'];
						$coupon_products = $coupon['restrict_to_products'];
						$coupon_categories = $coupon['restrict_to_categories'];
						$customer_type = $coupon['customer_type'];
					case 'new':
						// set some defaults
						if (empty($coupon_uses_user)) $coupon_uses_user = 1; ?>
						<td width="100%" valign="top">
							<table border="0" width="100%" cellspacing="0" cellpadding="2">
								<tr>
									<td>
										<table border="0" width="100%" cellspacing="0" cellpadding="0">
											<tr>
												<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
												<td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td>
										<?php echo tep_draw_form('coupon', 'coupon_admin.php', 'action=update&oldaction='.$_GET['action'].'&coupon_id='.(@$_GET['coupon_id'])); ?>
										<table border="0" width="100%" cellspacing="0" cellpadding="6">
											<tr>
												<td align="left" class="main"><?= COUPON_NAME; ?></td>
												<td align="left"><?php echo tep_draw_input_field('coupon_name[1]', @$coupon_name[1]).'&nbsp;'.tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English'); ?></td>
												<td align="left" class="main" width="40%"><?= COUPON_NAME_HELP; ?></td>
											</tr>
											<tr>
												<td align="left" valign="top" class="main"><?= COUPON_DESC; ?></td>
												<td align="left" valign="top"><?php echo tep_draw_textarea_field('coupon_desc[1]','physical','24','3', @$coupon_desc[1]).'&nbsp;'.tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English'); ?></td>
												<td align="left" valign="top" class="main"><?= COUPON_DESC_HELP; ?></td>
											</tr>
											<tr>
												<td align="left" class="main"><?php echo COUPON_AMOUNT; ?></td>
												<td align="left"><?php echo tep_draw_input_field('coupon_amount', @$coupon_amount); ?></td>
												<td align="left" class="main"><?php echo COUPON_AMOUNT_HELP; ?></td>
											</tr>
											<tr>
												<td align="left" class="main"><?php echo COUPON_MIN_ORDER; ?></td>
												<td align="left"><?php echo tep_draw_input_field('coupon_min_order', @$coupon_min_order); ?></td>
												<td align="left" class="main"><?php echo COUPON_MIN_ORDER_HELP; ?></td>
											</tr>
											<tr>
												<td align="left" class="main"><?php echo COUPON_FREE_SHIP; ?></td>
												<td align="left"><?php echo tep_draw_checkbox_field('coupon_free_ship', @$coupon_free_ship); ?></td>
												<td align="left" class="main"><?php echo COUPON_FREE_SHIP_HELP; ?></td>
											</tr>
											<tr>
												<td align="left" class="main"><?php echo COUPON_CODE; ?></td>
												<td align="left"><?php echo tep_draw_input_field('coupon_code', @$coupon_code); ?></td>
												<td align="left" class="main"><?php echo COUPON_CODE_HELP; ?></td>
											</tr>
											<tr>
												<td align="left" class="main"><?php echo COUPON_USES_COUPON; ?></td>
												<td align="left"><?php echo tep_draw_input_field('coupon_uses_coupon', @$coupon_uses_coupon); ?></td>
												<td align="left" class="main"><?php echo COUPON_USES_COUPON_HELP; ?></td>
											</tr>
											<tr>
												<td align="left" class="main"><?php echo COUPON_USES_USER; ?></td>
												<td align="left"><?php echo tep_draw_input_field('coupon_uses_user', @$coupon_uses_user); ?></td>
												<td align="left" class="main"><?php echo COUPON_USES_USER_HELP; ?></td>
											</tr>
											<tr>
												<td align="left" class="main"><?php echo COUPON_PRODUCTS; ?></td>
												<td align="left"><?php echo tep_draw_input_field('coupon_products', @$coupon_products); ?> <A HREF="validproducts.php" TARGET="_blank" ONCLICK="window.open('validproducts.php', 'Valid_Products', 'scrollbars=yes,resizable=yes,menubar=yes,width=600,height=600'); return false">View</A></td>
												<td align="left" class="main"><?php echo COUPON_PRODUCTS_HELP; ?></td>
											</tr>
											<tr>
												<td align="left" class="main"><?php echo COUPON_CATEGORIES; ?></td>
												<td align="left"><?php echo tep_draw_input_field('coupon_categories', @$coupon_categories); ?> <A HREF="validcategories.php" TARGET="_blank" ONCLICK="window.open('validcategories.php', 'Valid_Categories', 'scrollbars=yes,resizable=yes,menubar=yes,width=600,height=600'); return false">View</A></td>
												<td align="left" class="main"><?php echo COUPON_CATEGORIES_HELP; ?></td>
											</tr>
											<tr>
												<?php if (empty($_POST['coupon_startdate'])) {
													$coupon_startdate = explode('-', date('Y-m-d'));
												}
												else {
													$coupon_startdate = explode("-", $_POST['coupon_startdate']);
												}
												if (empty($_POST['coupon_finishdate'])) {
													$coupon_finishdate = explode('-', date('Y-m-d'));
													$coupon_finishdate[0] = $coupon_finishdate[0] + 1;
												} else {
													$coupon_finishdate = explode("-", $_POST['coupon_finishdate']);
												} ?>
												<td align="left" class="main"><?php echo COUPON_STARTDATE; ?></td>
												<td align="left"><?php echo tep_draw_date_selector('coupon_startdate', mktime(0,0,0, $coupon_startdate[1], $coupon_startdate[2], $coupon_startdate[0])); ?></td>
												<td align="left" class="main"><?php echo COUPON_STARTDATE_HELP; ?></td>
											</tr>
											<tr>
												<td align="left" class="main"><?php echo COUPON_FINISHDATE; ?></td>
												<td align="left"><?php echo tep_draw_date_selector('coupon_finishdate', mktime(0,0,0, $coupon_finishdate[1], $coupon_finishdate[2], $coupon_finishdate[0])); ?></td>
												<td align="left" class="main"><?php echo COUPON_FINISHDATE_HELP; ?></td>
											</tr>
											<tr>
												<td align="left" class="main">Customer Type</td>
												<td align="left">
													<select name="customer_type" size="1">
														<option value="-1" <?php echo !isset($customer_type)||$customer_type==-1?'selected':NULL; ?>>ALL</option>
														<option value="0" <?php echo isset($customer_type)&&$customer_type==0?'selected':NULL; ?>>Regular Only</option>
														<option value="1" <?php echo isset($customer_type)&&$customer_type==1?'selected':NULL; ?>>Dealer Only</option>
													</select>
												</td>
												<td align="left" class="main">The types of customers allowed to use this coupon</td>
											</tr>
											<tr>
												<td align="left"><?php echo tep_image_submit('button_preview.gif',@COUPON_BUTTON_PREVIEW); ?></td>
												<td align="left"><?php echo '&nbsp;&nbsp;<a href="'.'/admin/coupon_admin.php'; ?>"><?php echo tep_image_button('button_cancel.gif', IMAGE_CANCEL); ?></a></td>
											</tr>
										</table>
										</form>
									</td>
								</tr>
							</table>
						</td>
						<?php break;
					default: ?>
						<td width="100%" valign="top">
							<table border="0" width="100%" cellspacing="0" cellpadding="2">
								<tr>
									<td width="100%">
										<table border="0" width="100%" cellspacing="0" cellpadding="0">
											<tr>
												<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
												<td class="main">
													<?php echo tep_draw_form('status', 'coupon_admin.php', '', 'get'); ?>
													<?php $status_array[] = array('id' => 'Y', 'text' => TEXT_COUPON_ACTIVE);
													$status_array[] = array('id' => 'N', 'text' => TEXT_COUPON_INACTIVE);
													$status_array[] = array('id' => '*', 'text' => TEXT_COUPON_ALL);

													if (!empty($_GET['status'])) {
														$status = $_GET['status'];
													} else {
														$status = 'Y';
													}
													echo HEADING_TITLE_STATUS.' '.tep_draw_pull_down_menu('status', $status_array, $status, 'onChange="this.form.submit();"'); ?>
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
															<td class="dataTableHeadingContent"><?php echo COUPON_NAME; ?></td>
															<td class="dataTableHeadingContent" align="center"><?php echo COUPON_AMOUNT; ?></td>
															<td class="dataTableHeadingContent" align="center"><?php echo COUPON_CODE; ?></td>
															<td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
														</tr>
														<?php if (!empty($_GET['page']) && $_GET['page'] > 1) $rows = $_GET['page'] * 20 - 20;
														else $rows = 0;
														if ($status != '*') {
															if ($status == 'Y') {
																$ccs = prepared_query::fetch("select coupon_id, coupon_code, coupon_amount, coupon_type, coupon_active, coupon_start_date,coupon_expire_date,uses_per_user,uses_per_coupon,restrict_to_products, restrict_to_categories, date_created,date_modified, customer_type from coupons where coupon_active='Y' and coupon_type != 'G' and coupon_expire_date >= NOW()", cardinality::SET);
															}
															else {
																$ccs = prepared_query::fetch("select coupon_id, coupon_code, coupon_amount, coupon_type, coupon_active, coupon_start_date,coupon_expire_date,uses_per_user,uses_per_coupon,restrict_to_products, restrict_to_categories, date_created,date_modified, customer_type from coupons WHERE coupon_type != 'G' and coupon_expire_date < NOW()", cardinality::SET);
															}
														}
														else {
															$ccs = prepared_query::fetch("select coupon_id, coupon_code, coupon_amount, coupon_type, coupon_active, coupon_start_date,coupon_expire_date,uses_per_user,uses_per_coupon,restrict_to_products, restrict_to_categories, date_created,date_modified, customer_type from coupons where coupon_type != 'G'", cardinality::SET);
														}

														foreach ($ccs as $cc_list) {
															$rows++;
															if (strlen($rows) < 2) {
																$rows = '0'.$rows;
															}
															if ((empty($_GET['coupon_id']) || $_GET['coupon_id'] == $cc_list['coupon_id']) && empty($cInfo)) {
																$cInfo = (object)$cc_list;
															}
															if (!empty($cInfo) && ($cc_list['coupon_id'] == $cInfo->coupon_id)) {
																echo '<tr class="dataTableRowSelected" onmouseover="this.style.cursor=\'hand\'" onclick="document.location.href=\''.'/admin/coupon_admin.php?'.tep_get_all_get_params(array('coupon_id', 'action')).'coupon_id='.$cInfo->coupon_id.'&action=edit'.'\'">'."\n";
															}
															else {
																echo '<tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\';this.style.cursor=\'hand\'" onmouseout="this.className=\'dataTableRow\'" onclick="document.location.href=\''.'/admin/coupon_admin.php?'. tep_get_all_get_params(array('coupon_id', 'action')).'coupon_id='.$cc_list['coupon_id'].'\'">'."\n";
															}
															$coupon_desc = prepared_query::fetch("select coupon_name from coupons_description where coupon_id = :coupon_id and language_id = 1", cardinality::SINGLE, [':coupon_id' => $cc_list['coupon_id']]); ?>
															<td class="dataTableContent"><?= $coupon_desc; ?></td>
															<td class="dataTableContent" align="center">
																<?php if ($cc_list['coupon_type'] == 'P') {
																	echo $cc_list['coupon_amount'].'%';
																}
																elseif ($cc_list['coupon_type'] == 'S') {
																	echo TEXT_FREE_SHIPPING;
																}
																else {
																	echo CK\text::monetize($cc_list['coupon_amount']);
																} ?>
																&nbsp;
															</td>
															<td class="dataTableContent" align="center"><?= $cc_list['coupon_code']; ?></td>
															<td class="dataTableContent" align="right"><?php if ( !empty($cInfo) && ($cc_list['coupon_id'] == $cInfo->coupon_id) ) { echo tep_image(DIR_WS_IMAGES.'icon_arrow_right.gif'); } else { echo '<a href="'.'/admin/coupon_admin.php?page='.@$_GET['page'].'&coupon_id='.$cc_list['coupon_id'].'">'.tep_image(DIR_WS_IMAGES.'icon_info.gif', IMAGE_ICON_INFO).'</a>'; } ?>&nbsp;</td>
														</tr>
														<?php }

														if (empty($cInfo)) $cInfo = (object) ['coupon_id' => NULL, 'coupon_code' => NULL, 'coupon_amount' => NULL, 'coupon_type' => NULL, 'coupon_active' => NULL, 'coupon_start_date' => NULL, 'coupon_expire_date' => NULL, 'uses_per_user' => NULL, 'uses_per_coupon' => NULL, 'restrict_to_products' => NULL, 'restrict_to_categories' => NULL, 'date_created' => NULL, 'date_modified' => NULL, 'customer_type' => NULL]; ?>
														<tr>
															<td colspan="5">
																<table border="0" width="100%" cellspacing="0" cellpadding="2">
																	<tr>
																		<td class="smallText"></td>
																		<td align="right" class="smallText"></td>
																	</tr>
																	<tr>
																		<td align="right" colspan="2" class="smallText"><?php echo '<a href="'.'/admin/coupon_admin.php?page='.@$_GET['page'].'&coupon_id='.$cInfo->coupon_id.'&action=new'.'">'.tep_image_button('button_insert.gif', IMAGE_INSERT).'</a>'; ?></td>
																	</tr>
																</table>
															</td>
														</tr>
													</table>
												</td>
												<?php
												$heading = array();
												$contents = array();

												switch ($_GET['action']) {
													case 'release':
														break;
													case 'voucherreport':
														$heading[] = array('text' => '<b>'.TEXT_HEADING_COUPON_REPORT.'</b>');
														$contents[] = array('text' => TEXT_NEW_INTRO);
														break;
													case 'new':
														$heading[] = array('text' => '<b>'.TEXT_HEADING_NEW_COUPON.'</b>');
														$contents[] = array('text' => TEXT_NEW_INTRO);
														$contents[] = array('text' => '<br>'.COUPON_NAME.'<br>'.tep_draw_input_field('name'));
														$contents[] = array('text' => '<br>'.COUPON_AMOUNT.'<br>'.tep_draw_input_field('voucher_amount'));
														$contents[] = array('text' => '<br>'.COUPON_CODE.'<br>'.tep_draw_input_field('voucher_code'));
														$contents[] = array('text' => '<br>'.COUPON_USES_COUPON.'<br>'.tep_draw_input_field('voucher_number_of'));
														break;
													default:
														$heading[] = array('text'=>'['.$cInfo->coupon_id.'] '.$cInfo->coupon_code);
														$active = $cInfo->coupon_active;
														$amount = $cInfo->coupon_amount;
														if ($cInfo->coupon_type == 'P') {
															$amount .= '%';
														}
														else {
															$amount = CK\text::monetize($amount);
														}
														if ($_GET['action'] == 'voucherdelete') {
															$contents[] = array('text'=> TEXT_CONFIRM_DELETE.'</br></br><a href="'.'/admin/coupon_admin.php?action=confirmdelete&coupon_id='.$_GET['coupon_id'].'">'.tep_image_button('button_confirm.gif','Confirm Delete Voucher').'</a><a href="'.'/admin/coupon_admin.php?coupon_id='.$cInfo->coupon_id.'">'.tep_image_button('button_cancel.gif','Cancel').'</a>');
														}
														else {
															$ccs = prepared_query::fetch("select crt.*, o.orders_status, o.date_purchased,
															ifnull(abs((select ot1.value from orders_total ot1 where ot1.orders_id = crt.order_id and ot1.class = 'ot_coupon')), 0) as coupon_value,
															ifnull((select ot2.value from orders_total ot2 where ot2.orders_id = crt.order_id and ot2.class = 'ot_shipping'), 0) as shipping_value,
															ifnull((select ot3.value from orders_total ot3 where ot3.orders_id = crt.order_id and ot3.class = 'ot_tax'), 0) as tax_value,
															ifnull((select ot4.value from orders_total ot4 where ot4.orders_id = crt.order_id and ot4.class = 'ot_total'), 0) as total_value,
															ifnull((select sum(op.products_quantity * op.cost) from orders_products op where op.orders_id = crt.order_id), 0) as total_cost
															from coupon_redeem_track crt left join orders o on (o.orders_id = crt.order_id) where crt.coupon_id = :coupon_id order by o.date_purchased asc", cardinality::SET, [':coupon_id' => @$_GET['coupon_id']]);

															$coupon_total = 0;
															$shipping_total = 0;
															$tax_total = 0;
															$total_charged = 0;
															$total_cost = 0;

															$h_coupon_total = 0;
															$h_shipping_total = 0;
															$h_tax_total = 0;
															$h_total_charged = 0;
															$h_total_cost = 0;

															foreach ($ccs as $cc_list) {
																$rows++;
																if (strlen($rows) < 2) {
																	$rows = '0'.$rows;
																}
																if ((empty($_GET['uid']) || (@$_GET['uid'] == $cc_list['unique_id'])) && empty($cInfo)) {
																	$cInfo = (object)$cc_list;
																}
																$this_order_cost = $cc_list['total_cost'];
																if ($cc_list['orders_status'] == '3') {
																	$coupon_total += $cc_list['coupon_value'];
																	$shipping_total += $cc_list['shipping_value'];
																	$tax_total += $cc_list['tax_value'];
																	$total_charged += $cc_list['total_value'];
																	$total_cost += $this_order_cost;
																}
															}
															$prod_details = 'NONE';
															if ($cInfo->restrict_to_products) {
																$prod_details = '<A HREF="listproducts.php?coupon_id='.$cInfo->coupon_id.'" TARGET="_blank" ONCLICK="window.open(\'listproducts.php?coupon_id='.$cInfo->coupon_id.'\', \'Valid_Categories\', \'scrollbars=yes,resizable=yes,menubar=yes,width=600,height=600\'); return false">View</A>';
															}
															$cat_details = 'NONE';
															if ($cInfo->restrict_to_categories) {
																$cat_details = '<A HREF="listcategories.php?coupon_id='.$cInfo->coupon_id.'" TARGET="_blank" ONCLICK="window.open(\'listcategories.php?coupon_id='.$cInfo->coupon_id.'\', \'Valid_Categories\', \'scrollbars=yes,resizable=yes,menubar=yes,width=600,height=600\'); return false">View</A>';
															}
															$coupon_name = prepared_query::fetch("select coupon_name from coupons_description where coupon_id = :coupon_id and language_id = 1", cardinality::SINGLE, [':coupon_id' => $cInfo->coupon_id]);

															$start_date = new DateTime($cInfo->coupon_start_date);
															$expire_date = new DateTime($cInfo->coupon_expire_date);
															$date_created = new DateTime($cInfo->date_created);
															$date_modified = new DateTime($cInfo->date_modified);

															$text = '';
															$text .= COUPON_NAME.'&nbsp;::&nbsp; '.$coupon_name.'<br>';
															$text .= COUPON_AMOUNT.'&nbsp;::&nbsp; '.$amount.'<br>';
															$text .= COUPON_STARTDATE.'&nbsp;::&nbsp; '.$start_date->format('m/d/Y').'<br>';
															$text .= COUPON_FINISHDATE.'&nbsp;::&nbsp; '.$expire_date->format('m/d/Y').'<br>';
															$text .= COUPON_USES_COUPON.'&nbsp;::&nbsp; '.$cInfo->uses_per_coupon.'<br>';
															$text .= COUPON_USES_USER.'&nbsp;::&nbsp; '.$cInfo->uses_per_user.'<br>';
															$text .= COUPON_PRODUCTS.'&nbsp;::&nbsp; '.$prod_details.'<br>';
															$text .= COUPON_CATEGORIES.'&nbsp;::&nbsp; '.$cat_details.'<br>';
															$text .= DATE_CREATED.'&nbsp;::&nbsp; '.$date_created->format('m/d/Y').'<br>';
															$text .= DATE_MODIFIED.'&nbsp;::&nbsp; '.$date_modified->format('m/d/Y').'<br>';
															$text .= 'Number Of Redemptions&nbsp;::&nbsp; '.count($ccs).'<br>';
															$text .= 'Original Order Total&nbsp;::&nbsp; $'.number_format(($total_charged + $coupon_total), 2).'<br>';
															$text .= 'Coupon Value&nbsp;::&nbsp; $'.number_format($coupon_total, 2).'<br>';
															$text .= 'Actual Order Total&nbsp;::&nbsp; $'.number_format($total_charged, 2).'<br>';
															$text .= 'Margin&nbsp;::&nbsp; $'.number_format(($total_charged - $shipping_total - $tax_total - $total_cost), 2).'<br>';
															$text .= 'Active?&nbsp;::&nbsp; '.$active.'<br>';
															$text .= 'Customer Type&nbsp;::&nbsp;'.@$customer_types[$cInfo->customer_type].'<br><br><br>';
															$text .= '<center><a href="'.'/admin/coupon_admin.php?action=voucheredit&coupon_id='.$cInfo->coupon_id.'">'.tep_image_button('button_edit.gif','Edit Voucher').'</a>';
															$text .= '<a href="'.'/admin/coupon_admin.php?action=voucherdelete&coupon_id='.$cInfo->coupon_id.'">'.tep_image_button('button_delete.gif','Delete Voucher').'</a>';
															$text .= '<a href="'.'/admin/coupon_admin.php?action=voucherreport&coupon_id='.$cInfo->coupon_id.'">'.tep_image_button('button_report.gif','Voucher Report').'</a></center>';

															$contents[] = ['text' => $text];
														}
														break;
												} ?>
												<td width="25%" valign="top">
													<?php $box = new box;
													echo $box->infoBox($heading, $contents); ?>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
				<?php } ?>
			</tr>
		</table>
	</body>
</html>
