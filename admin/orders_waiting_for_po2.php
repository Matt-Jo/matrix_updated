<?php
 require('includes/application_top.php');

if (!empty($_REQUEST['ipn'])) {
	$ipn = $_REQUEST['ipn'];
	$run_query = prepared_query::fetch("SELECT tracking_number, tracking_method, eta FROM purchase_order_tracking WHERE po_id = :po_id", cardinality::SET, [':po_id' => $ipn]); ?>
<table width="400">
	<tr>
		<td class="main">Tracking Number</td>
		<td class="main">Tracking Method</td>
		<td class="main">ETA</td>
	</tr>
	<?php foreach ($run_query as $result) { ?>
	<tr>
		<td class='main'><?= $result['tracking_number']; ?></td>
		<td class='main'><?= $result['tracking_method']; ?></td>
		<td class='main'><?= $result['eta']; ?></td>
	</tr>
	<?php } ?>
</table>
	<?php die;
}

function hotpo_display_row($disp_product) { ?>
	<tr>
		<td style="font-family: verdana; font-size: 11px;"><?= $disp_product['vendors_company_name']; ?></td>
		<td style="font-family: verdana; font-size: 11px;"><a href="po_viewer.php?poId=<?= $disp_product['po_id']; ?>"><?= $disp_product['po_number']; ?></a></td>
		<td style="font-family: verdana; font-size: 11px;"><a href="ipn_editor.php?ipnId=<?= $disp_product['stock_name']; ?>" ><?= $disp_product['stock_name']; ?></a></td>
		<td style="font-family: verdana; font-size: 11px;"><?= $disp_product['on_order']; ?></td>
		<td style="font-family: verdana; font-size: 11px;"><?= $disp_product['total_alloced']; ?></td>
		<td style="font-family: verdana; font-size: 11px;"><?= $disp_product['orders']; ?></td>
		<td style="font-family: verdana; font-size: 11px;"><?= $disp_product['shipping']; ?></td>
		<td style="font-family: verdana; font-size: 11px;">
			<?php if ($disp_product['package_count'] > 1) { ?>
			<span class="tooltip" style="color:red;"><a tooltip="<?= $disp_product['po_id']; ?>">+</a></span>&nbsp;&nbsp;
			<?php }
			echo $disp_product['expected_date']; ?>
		</td>
		<td style="font-family: verdana; font-size: 11px;"><input type="button" onclick="window.open('po_receiver.php?poId=<?= $disp_product['po_id']; ?>')" name="receive" value="receive"></td>
	</tr>
<?php }

function hotpo_display_header() { ?>
	<tr>
		<td style="font-family: verdana; font-size: 11px; width:150px;">Vendor</td>
		<td style="font-family: verdana; font-size: 11px;">PO Number</td>
		<td style="font-family: verdana; font-size: 11px;">IPN</td>
		<td style="font-family: verdana; font-size: 11px;">PO Qty</td>
		<td style="font-family: verdana; font-size: 11px;">Backorder Qty</td>
		<td style="font-family: verdana; font-size: 11px;">Order Number</td>
		<td style="font-family: verdana; font-size: 11px;">Shipping Method</td>
		<td style="font-family: verdana; font-size: 11px;">Expected Date</td>
		<td style="font-family: verdana; font-size: 11px;">Receive</td>
	</tr>
<?php } ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
<title><?= TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript" src="../includes/javascript/prototype.js"></script>
<script type="text/javascript" src="../includes/javascript/scriptaculous/scriptaculous.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
<!-- header //-->
<?php require(DIR_WS_INCLUDES.'header.php'); ?>
<!-- header_eof //-->
<script type="text/javascript">
jQuery(document).ready(function($) {
	$('.tooltip a').each(function() {
		$(this).qtip({
			content: {
				url: 'orders_waiting_for_po2.php',
				data: { ipn: $(this).attr('tooltip') },
				method: 'get'
			},
			show: 'click',
			hide: 'click',
			position: {
				corner: {
					tooltip: 'TopLeft',
					target: 'topRight'
				}
			},
			style: {
				tip: {
					corner: 'TopLeft'
				},
				border: {
					color: '#cb2026'
				},
				width: '400px'

			}
		});
	});
});
</script>

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
 <tr>
	<td width="<?= BOX_WIDTH; ?>" valign="top"><table border="0" width="<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
<!-- left_navigation_eof //-->
	</table></td>
<!-- body_text //-->
	<td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
	<tr>
		<td><table border="0" width="100%" cellspacing="0" cellpadding="0">
		<tr>
			<td class="pageHeading">Inventory Management</td>
		</tr>
		</table></td>
	</tr>
	<tr>
		<td>
			<br>
			<h4 style="font-family: verdana;">Waiting For Arrival:</h4>

			<?php
				$allocated_array = ck_ipn2::get_legacy_allocated_ipns();
				$vendor_id = ' ';

				$disp_product = null;
				$results = array();
				if ($products = prepared_query::fetch("select psc.stock_name, psc.stock_id, o.orders_id, op.po_waiting, op.products_quantity as backorder_qty, p.products_quantity, pop.quantity as on_order, pop.id as pop_id, psc.stock_id, po.id as po_id, po.purchase_order_number as po_number, po.shipping_method, po2oa.quantity as alloc_qty, IFNULL(pot.eta,date(po.expected_date)) as expected_date, (SELECT count(*) from purchase_order_tracking WHERE po_id = po.id) as package_count, pos.text as shipping, v.vendors_company_name, v.vendors_id, sum(porvp.qty_received) from purchase_order_to_order_allocations po2oa left join purchase_order_products pop on po2oa.purchase_order_product_id = pop.id left join purchase_orders po on pop.purchase_order_id = po.id left join purchase_order_received_products porp on porp.purchase_order_product_id = pop.ipn_id left join purchase_order_review por on (por.po_number = po.purchase_order_number and por.status in (0,1)) left join purchase_order_review_product porvp on (porvp.po_review_id = por.id and porvp.pop_id = pop.id) inner join products_stock_control psc on psc.stock_id = pop.ipn_id left join products p on p.stock_id = psc.stock_id inner join orders_products op on (p.products_id=op.products_id and op.orders_products_id = po2oa.order_product_id) inner join orders o on op.orders_id=o.orders_id left join purchase_order_shipping pos on po.shipping_method = pos.id left join products_stock_control_extra psce on psc.stock_id = psce.stock_id left join vendors v on po.vendor = v.vendors_id left join purchase_order_tracking pot on pot.po_id = po.id where psc.on_order > '0' and (porp.quantity_remaining >= 0 or porp.quantity_remaining is null) and o.orders_status in (1,2,5,7,8,10,11,12) AND po.status IN (1,2) GROUP BY po_id, po_number, psc.stock_name, o.orders_id ORDER BY expected_date, v.vendors_company_name, pop.id, psc.stock_id", cardinality::SET)) { ?>
					<div id="out_of_stock"><table cellpadding="5px" cellspacing="0" border="0">
						<?php hotpo_display_header(); ?>
						<?php foreach ($products as $product) {

								//one off situation for first item in query response
								if (!$disp_product) {
									$disp_product = $product;
									$disp_product['total_alloced'] = 0;
								}

								if ($disp_product['pop_id'] != $product['pop_id']) {
									hotpo_display_row($disp_product);
									$disp_product = $product;
									$disp_product['total_alloced'] = 0;
								}

					@$disp_product['orders'] .= "<a href = 'orders_new.php?selected_box=orders&oID=" .
										$product['orders_id']."&action=edit'>".$product['orders_id'] .
										" (".$product['alloc_qty']. ")</a><br/>";
								$disp_product['total_alloced'] += $product['alloc_qty'];
							}
				hotpo_display_row($disp_product);


					?>
					</table></div>
					<?php
				}
			?>
		</td>
	</tr>
<!-- EO adding code for items needed for orders -->
	</table></td>
<!-- body_text_eof //-->
 </tr>
</table>
<!-- body_eof //-->
</body>
</html>
