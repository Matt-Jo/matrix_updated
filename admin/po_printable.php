<?php
require_once('includes/application_top.php');

$poId = !empty($_GET['poId'])?$_GET['poId']:null;

if (empty($poId)) die('Purchase order ID was not set.');

$po = prepared_query::fetch('SELECT po.id, po.purchase_order_number, po.creation_date, po.expected_date, po.notes, po.status, po.confirmation_status, po.confirmation_hash, po.show_vendor_pn, pos.text as status_text, posh.text as shipping_method_text, pot.text as terms_text, v.vendors_id, v.vendors_company_name, a.admin_lastname, a.admin_firstname, a.admin_email_address, v.vendors_email_address, v.vendors_telephone, abv.entry_street_address, abv.entry_suburb, abv.entry_postcode, abv.entry_city, z.zone_code, c.countries_name, ce.entity_name, ce.entity_address, ce.entity_logo_link FROM purchase_orders po LEFT JOIN purchase_order_status pos ON po.status = pos.id LEFT JOIN purchase_order_shipping posh ON po.shipping_method = posh.id LEFT JOIN purchase_order_terms pot ON po.terms = pot.id LEFT JOIN vendors v ON po.vendor = v.vendors_id LEFT JOIN address_book_vendors abv on v.vendors_default_address_id = abv.address_book_id LEFT JOIN countries c ON abv.entry_country_id = c.countries_id LEFT JOIN zones z ON abv.entry_zone_id = z.zone_id LEFT JOIN admin a ON po.administrator_admin_id = a.admin_id LEFT JOIN ck_entities ce ON po.entity_id = ce.entity_id WHERE po.id = :po_id', cardinality::ROW, [':po_id' => $poId]);

$po_results = prepared_query::fetch('SELECT pop.id, pop.quantity, pop.description, pop.cost, psc.stock_id, psc.stock_name, psc.on_order, SUM(porp.quantity_received) AS quantity_received, v2s.vendors_pn FROM purchase_order_products pop LEFT JOIN products_stock_control psc ON pop.ipn_id = psc.stock_id LEFT JOIN purchase_order_received_products porp ON pop.id = porp.purchase_order_product_id LEFT JOIN vendors_to_stock_item v2s ON (v2s.stock_id = psc.stock_id AND v2s.vendors_id = :vendors_id) WHERE pop.purchase_order_id = :po_id GROUP BY pop.id', cardinality::SET, [':vendors_id' => $po['vendors_id'], ':po_id' => $poId]);

$po_products = [];
$ipn_list = [];
$ipn_list[] = '0';

foreach ($po_results as $result) {
	$po_products[] = $result;
	$ipn_list[] = $result['stock_id'];
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
		<title><?= $po['entity_name']; ?> PO Number: <?= $po['purchase_order_number']; ?></title>
		<style type="text/css">
			.table1 { border:3px solid #ababab; font-family:arial; font-size:11px; }
			.table1 td, .table1 th { padding-left:30px; padding-right:30px; padding-top:2px; padding-bottom:2px; }
			.table1 th { color:white; text-align:center; font-weight:bold; background-color:#ababab; white-space:nowrap; }
		</style>
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0">
		<table border="0" width="800px" cellspacing="2" cellpadding="2" style="margin-bottom: 15px;">
			<tr>
				<td width="100%" valign="top" align="center">
					<?php if (!empty($_GET['confirmation']) && $_GET['confirmation'] == 'yes' && $po['confirmation_status'] == '1' && trim($po['confirmation_hash']) != '') { ?>
						<span style="font-family: verdana; font-size: 12px; font-weight: bold;">
							Please confirm receipt of this PO - <a href="www.cablesandkits.com/po_confirmation.php?key=<?= $po['confirmation_hash']; ?>">CLICK HERE</a>
						</span>
					<?php } ?>
				</td>
			</tr>
		</table>
		<!-- body //-->
		<table border="0" width="800px" height="1000px" cellspacing="2" cellpadding="2" style="border: 1px solid black; margin: 3px;">
			<tr>
				<!-- body_text //-->
				<td width="100%" valign="top">
					<!------------------------------------------------------------------- -->
					<table border="0" width="100%" cellspacing="5" cellpadding="5">
						<tr>
							<td colspan="2" align="center">
								<h2 style="font-family: arial; margin-bottom: 0px;">Purchase Order</h2>
							</td>
						</tr>
						<tr>
							<td>
								<img src="<?= $po['entity_logo_link']; ?>" border="0" alt="<?= $po['entity_name']; ?>">
							</td>
							<td align="right">
								<table class="table1" cellspacing="2px" cellpadding="5px">
									<tr>
										<th>PO Number</th>
										<th>Date</th>
									</tr>
									<tr>
										<td align="center"><?= $po['purchase_order_number']; ?></td>
										<td align="center"><?= date('m/d/Y', strtotime($po['creation_date']));?></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td colspan="2" align="left">
								<table class="table1" width="100%" cellspacing="2px" cellpadding="5px">
									<tr>
										<th>Vendor</th>
										<th>Ship To</th>
										<th>Bill To</th>
									</tr>
									<tr>
										<td align="left" nowrap>
											<?= $po['vendors_company_name']; ?><br/>
											<?= $po['entry_street_address']; ?><br/>
											<?php if (trim($po['entry_suburb'] != '')) { ?>
												<?= $po['entry_suburb']; ?><br/>
											<?php } ?>
											<?= $po['entry_city'].', '.$po['zone_code'].' '.$po['entry_postcode']; ?><br/>
											<?= $po['countries_name']; ?><br/>
											<?= $po['vendors_telephone']; ?><br/><br/>
											<?= $po['vendors_email_address']; ?>
										</td>
										<td align="left">
											<?= $po['entity_name']; ?><br>
											<?= $po['entity_address']; ?>
											<br><br>
											<?= $po['admin_email_address']; ?>
										</td>
										<td align="left">
											<?= $po['entity_name']; ?><br>
											<?= $po['entity_address']; ?>
											<br><br>
											<?= $po['admin_email_address']; ?>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td colspan="2" align="left">
								<table class="table1" width="100%" cellspacing="2px" cellpadding="5px">
									<tr>
										<th>Administered By</th>
										<th>Date Expected</th>
										<th>Shipping Method</th>
										<th>Terms</th>
									</tr>
									<tr>
										<td align="center">
											<?= $po['admin_lastname'].', '.$po['admin_firstname'];?>
										</td>
										<td align="center">
											<?= date('m/d/Y', strtotime($po['expected_date']));?>
										</td>
										<td align="center">
											<?= $po['shipping_method_text']; ?>
										</td>
										<td align="center">
											<?= $po['terms_text']; ?>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td colspan="2" align="left">
								<table class="table1" width="100%" cellspacing="2px" cellpadding="5px">
									<tr>
										<th>Item Number</th>
										<?php if (!empty($po['show_vendor_pn'])) { ?>
										<th>Vendor P/N</th>
										<?php } ?>
										<th>Description</th>
										<th>Quantity</th>
										<th>Unit Price</th>
										<th>Amount</th>
									</tr>
									<?php $total = '0';
									$total_pieces = '0';
									foreach ($po_products as $unused => $product) {
										$amount = $product['quantity'] * $product['cost'];
										$total += $amount;
										$total_pieces += $product['quantity']; ?>
									<tr>
										<td align="left"><?= $product['stock_name']; ?></td>
										<?php if (!empty($po['show_vendor_pn'])) { ?>
										<td align="left" style="background-color: #cacaca;"><?= $product['vendors_pn']; ?></th>
										<?php } ?>
										<td align="left"><?= $product['description']; ?></td>
										<td align="center"><?= $product['quantity']; ?></td>
										<td align="center">$<?= number_format($product['cost'], 2);?></td>
										<td align="center">$<?= number_format($amount, 2);?></td>
									</tr>
									<?php } ?>
								</table>
							</td>
						</tr>
						<tr>
							<td align="left" width="50%" valign="top">
								<table class="table1" cellspacing="2px" cellpadding="5px" width="100%">
									<tr>
										<th>Notes</th>
									</tr>
									<tr>
										<td><?= nl2br($po['notes']);?></td>
									</tr>
								</table>
							</td>
							<td align="right" width="50%" valign="top">
								<table class="table1" cellspacing="2px" cellpadding="5px">
									<tr>
										<th>Total Pieces</th>
										<th>Purchase Order Total</th>
									</tr>
									<tr>
										<td align="center"><?= $total_pieces; ?></td>
										<td align="center">$<?= number_format($total, 2); ?></td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<!------------------------------------------------------------------- -->
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
	</body>
</html>
