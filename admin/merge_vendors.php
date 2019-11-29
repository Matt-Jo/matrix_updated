<?php require('includes/application_top.php'); ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/menu.js"></script>
	<script language="javascript" src="includes/general.js"></script>
	<script language="JavaScript" type="text/javascript">
	function confirm_merge() {
		var answer = confirm("Are you sure you want to merge the two Vendors?")
			if (answer) {
				return true;
			} else {
				return false;
			}
		}
	</script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
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
				<!------------------------------------------------------------------- -->
				<div style="width:800px; border:1px solid #333333; margin:20px; height:800px; padding:10px;">
					<?php if (!empty($_POST['old_vendor']) && !empty($_POST['new_vendor']) && !empty($_POST['complete'])) {
						$old_vendor = $_POST['old_vendor'];
						$new_vendor = $_POST['new_vendor'];
						$stock_duplicates = unserialize(urldecode($_POST['stock_duplicates']));
						if ($old_vendor != $new_vendor) {
							foreach ($stock_duplicates as $duplicate) {
								prepared_query::execute("DELETE FROM vendors_to_stock_item WHERE vendors_id = :vendors_id AND stock_id = :stock_id", [':vendors_id' => $old_vendor, ':stock_id' => $duplicate]);
							}
							prepared_query::execute("UPDATE vendors_to_stock_item SET vendors_id = :new_vendor WHERE vendors_id = :old_vendor", [':new_vendor' => $new_vendor, ':old_vendor' => $old_vendor]);
							prepared_query::execute("UPDATE purchase_orders SET vendor = :new_vendor WHERE vendor = :old_vendor", [':new_vendor' => $new_vendor, ':old_vendor' => $old_vendor]);
							prepared_query::execute("UPDATE address_book_vendors SET vendors_id = :new_vendor WHERE vendors_id = :old_vendor", [':new_vendor' => $new_vendor, ':old_vendor' => $old_vendor]);
							prepared_query::execute("UPDATE products_stock_control_extra SET preferred_vendor_id = :new_vendor WHERE preferred_vendor_id = :old_vendor", [':new_vendor' => $new_vendor, ':old_vendor' => $old_vendor]);

							prepared_query::execute("DELETE FROM vendors WHERE vendors_id = :old_vendor", [':old_vendor' => $old_vendor]);
							prepared_query::execute("DELETE FROM vendors_info WHERE vendors_info_id = :old_vendor", [':old_vendor' => $old_vendor]);
							prepared_query::execute("DELETE FROM vendors_contact WHERE vendors_id = :old_vendor", [':old_vendor' => $old_vendor]);
							echo 'Vendor Merge Success.<br/><br/><a href="merge_vendors.php">Continue</a>';
						}
						else {
							echo 'You can not merge a vendor onto itself.<br/><br/><a href="merge_vendors.php">Continue</a>';
						}
					}
					elseif (!empty($_POST['old_vendor']) && !empty($_POST['new_vendor']) && empty($_POST['complete'])) {
						$old_vendor = $_POST['old_vendor'];
						$new_vendor = $_POST['new_vendor'];
						if ($old_vendor != $new_vendor) {
							if ($chk_dups_query = prepared_query::fetch("SELECT v2s.*, psc.stock_name, v.vendors_company_name FROM vendors_to_stock_item v2s LEFT JOIN products_stock_control psc ON v2s.stock_id = psc.stock_id LEFT JOIN vendors v on v.vendors_id = v2s.vendors_id WHERE v2s.vendors_id = :old_vendor AND v2s.stock_id IN(SELECT v2s2.stock_id from vendors_to_stock_item v2s2 where v2s2.vendors_id=:new_vendor)", cardinality::SET, [':old_vendor' => $old_vendor, ':new_vendor' => $new_vendor])) { ?>
					<form name="vendor_merge" action="merge_vendors.php" method="POST" onSubmit="return confirm_merge();">
						There are a few conflicts.<br/>
						Please make sure the vendor to IPN relationships are manually reviewed for these IPNs.<br/><br/>
						<?php $stock_duplicates = array();
						$i = 0;
						foreach ($chk_dups_query as $result) {
							echo $result['vendors_company_name'].' will be deleted from '.$result['stock_name'].'<br>';
							$stock_duplicates[$i] = $result['stock_id'];
							$i++;
						}
						$stock_duplicates = urlencode(serialize($stock_duplicates)); ?>
						<input type="hidden" name="stock_duplicates" value="<?= $stock_duplicates; ?>">
						<input type="hidden" name="complete" value="true">
						<input type="hidden" name="old_vendor" value="<?= $old_vendor; ?>">
						<input type="hidden" name="new_vendor" value="<?= $new_vendor; ?>">
						<input type="submit" name="Continue" value="Continue"> &nbsp;&nbsp;<input type="button" value="Cancel" name="Cancel" onclick="history.go(-1)">
					</form>
							<?php }
							else {
								prepared_query::execute("UPDATE vendors_to_stock_item SET vendors_id = :new_vendor WHERE vendors_id = :old_vendor", [':new_vendor' => $new_vendor, ':old_vendor' => $old_vendor]);
								prepared_query::execute("UPDATE purchase_orders SET vendor = :new_vendor WHERE vendor = :old_vendor", [':new_vendor' => $new_vendor, ':old_vendor' => $old_vendor]);
								prepared_query::execute("UPDATE address_book_vendors SET vendors_id = :new_vendor WHERE vendors_id = :old_vendor", [':new_vendor' => $new_vendor, ':old_vendor' => $old_vendor]);
								prepared_query::execute("UPDATE products_stock_control_extra SET preferred_vendor_id = :new_vendor WHERE preferred_vendor_id = :old_vendor", [':new_vendor' => $new_vendor, ':old_vendor' => $old_vendor]);

								prepared_query::execute("DELETE FROM vendors WHERE vendors_id = :old_vendor", [':old_vendor' => $old_vendor]);
								prepared_query::execute("DELETE FROM vendors_info WHERE vendors_info_id = :old_vendor", [':old_vendor' => $old_vendor]);
								prepared_query::execute("DELETE FROM vendors_contact WHERE vendors_id = :old_vendor", [':old_vendor' => $old_vendor]);
								echo 'Vendor Merge Success.<br/><br/><a href="merge_vendors.php">Continue</a>';
							}
						}
						else {
							echo 'You can not merge a vendor onto itself.<br/><br/><a href="merge_vendors.php">Continue</a>';
						}
					}
					else {
						$vendors = prepared_query::fetch('SELECT v.vendors_id, v.vendors_company_name, v.vendors_email_address, COUNT(po.id) as po_count FROM vendors v LEFT JOIN purchase_orders po ON po.vendor = v.vendors_id GROUP BY v.vendors_id ORDER BY v.vendors_company_name, v.vendors_id', cardinality::SET); ?>
					<form name="vendor_merge" action="merge_vendors.php" method="POST" onSubmit="return confirm_merge();">
						Vendor to be removed:<br/>
						<select name="old_vendor">
							<?php foreach ($vendors as $old_vendors) { ?>
							<option value="<?= $old_vendors['vendors_id']; ?>"><?= $old_vendors['vendors_company_name']; ?> (id: <?= $old_vendors['vendors_id']; ?>) - <?= $old_vendors['po_count']; ?> POs - <?= $old_vendors['vendors_email_address']; ?></option>
							<?php } ?>
						</select>
						<br/><br/>
						New Vendor:<br/>
						<select name="new_vendor">
							<?php foreach ($vendors as $new_vendors) { ?>
							<option value="<?= $new_vendors['vendors_id']; ?>"><?= $new_vendors['vendors_company_name']; ?> (id: <?= $new_vendors['vendors_id']; ?>) - <?= $new_vendors['po_count']; ?> POs - <?= $new_vendors['vendors_email_address']; ?></option>
							<?php } ?>
						</select>
						<br/><br/>
						<input type="submit" name="convert" value="convert"/>
					</form>
					<?php } ?>
				</div>
				<!-- body_eof //-->
			</td>
		</tr>
	</table>
</body>
<html>
