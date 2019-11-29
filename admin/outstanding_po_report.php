<?php
 require('includes/application_top.php');


 function get_direction($column) {
		if (!empty($_GET['sort']) && $_GET['sort']==$column) {
				if (!empty($_GET['direction']) && $_GET['direction']=='ASC') {
						return 'DESC';
				}
				else {
						return 'ASC';
				}
		}
		else {
				return 'ASC';
		}
 }
 $sort=isset($_GET['sort']) ? $_GET['sort'] : 'purchase_order_number';
 $direction=isset($_GET['direction']) ? $_GET['direction'] : 'ASC';


 $po_list = prepared_query::fetch('select po.purchase_order_number, po.id as po_id, pos.text as status, date(po.expected_date) as expected_date, vendors.vendors_company_name as vendor, pot.text as terms,  case when avg(porp.paid) is null then "unpaid" when avg(porp.paid) = 0 then "unpaid" when avg(porp.paid) < 1 then "partially paid" else "paid" end as paid   from purchase_orders po left join vendors on po.vendor=vendors.vendors_id left join purchase_order_terms pot on po.terms=pot.id left join purchase_order_products pop on pop.purchase_order_id=po.id left join purchase_order_received_products porp on porp.purchase_order_product_id=pop.id left join purchase_order_status pos on po.status=pos.id where po.status in (1,2) group by po.id order by '.$sort.' '.$direction, cardinality::SET); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>
<script language="javascript" src="/includes/javascript/prototype.js"></script>
<script language="javascript" src="/includes/javascript/scriptaculous/scriptaculous.js"></script>
<script language="javascript">
</script>
<link rel="stylesheet" type="text/css" href="/includes/javascript/scriptaculous/scriptaculous.css">
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
<!-- header //-->
<?php require(DIR_WS_INCLUDES.'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
 <tr>
	<td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
<!-- left_navigation_eof //-->
		</table></td>

<!-- body_text //-->
	<td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
	<tr>
		<td><table border="0" width="100%" cellspacing="0" cellpadding="0">
		<tr>
			<td class="dataTableContent">Outstanding Po's</td>
			<td class="dataTableContent" align="right">
			</td>
		</tr>
		</table></td>
	</tr>
	<tr>
		<td>
		<table border="0" width="100%" cellspacing="0" cellpadding="2">
		<tr>
			<td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
			<tr class="dataTableHeadingRow">
		<td class="dataTableHeadingContent">
				<a href="/admin/outstanding_po_report.php?sort=purchase_order_number&direction=<?php echo get_direction('purchase_order_number')?>">Po Number</a>
				</td>
				<td class="dataTableHeadingContent">
						<a href="/admin/outstanding_po_report.php?sort=status&direction=<?php echo get_direction('status')?>">Status</a>
				</td>
				<td class="dataTableHeadingContent">
						<a href="/admin/outstanding_po_report.php?sort=expected_date&direction=<?php echo get_direction('expected_date')?>">Expected Date</a>
				</td>
				<td class="dataTableHeadingContent">
						<a href="/admin/outstanding_po_report.php?sort=vendor&direction=<?php echo get_direction('vendor')?>">Vendor</a>
				</td>
				<td class="dataTableHeadingContent">
						<a href="/admin/outstanding_po_report.php?sort=terms&direction=<?php echo get_direction('terms')?>">Payment Type</a>
				</td>
				<td class="dataTableHeadingContent">
						<a href="/admin/outstanding_po_report.php?sort=paid&direction=<?php echo get_direction('paid')?>">Paid</a>
				</td>


			</tr>
			<?php foreach ($po_list as $row) { ?>
			<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
				<td class="dataTableContent"><a href="po_viewer.php?poId=<?= $row['po_id']; ?>"><?= $row['purchase_order_number']; ?></a></td>
				<td class="dataTableContent"><?= $row['status']; ?></td>
				<td class="dataTableContent"><?= $row['expected_date']; ?></td>
				<td class="dataTableContent"><?= $row['vendor']; ?></td>
				<td class="dataTableContent"><?= $row['terms']; ?></td>
				<td class="dataTableContent"><?= $row['paid']; ?></td>
			</tr>
			<?php } ?>
		</table>
		</td>
		</tr>
		<tr>
		<td colspan="3"><table border="0" width="100%" cellspacing="0" cellpadding="2">
		</table></td>
		</tr>
		</table></td>
	</tr>
	</table></td>
<!-- body_text_eof //-->



 </tr>
</table>
<!-- body_eof //-->
</body>
</html>
