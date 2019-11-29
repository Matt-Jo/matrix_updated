<?php
require('includes/application_top.php');

if (@$_POST['action'] == 'process') {
	if (!$_FILES['file']['tmp_name']) die('The file could not be uploaded. Please contact development.');

	$import_run = TRUE;

	$file = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['file']['tmp_name']);
	$sheet = $file->getActiveSheet();

	$rows_in_spreadsheet = $sheet->getHighestRow();
	$found_ipns = 0;
	$missing_ipns = 0;
	$existing_specials_updated = 0;
	$new_specials_created = 0;

	$active_criteria = array(
		'QTY' => 1,
		'STOCK' => 2,
		'DATE' => 3,
		1 => 1,
		2 => 2,
		3 => 3
	);

	for ($i=1; $i<=$rows_in_spreadsheet; $i++) {
		$row = array(
			$sheet->getCellByColumnAndRow(1, $i)->getValue(),
			$sheet->getCellByColumnAndRow(2, $i)->getValue(),
			$sheet->getCellByColumnAndRow(3, $i)->getValue(),
			\PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($sheet->getCellByColumnAndRow(4, $i)->getValue()),
			$sheet->getCellByColumnAndRow(5, $i)->getValue()
		);

		if (empty($row[0])) continue;

		if (strtolower($row[0]) == 'ipn') continue; // skip the header row, if any

		$ipn_id = prepared_query::fetch('SELECT psc.stock_id FROM products_stock_control psc WHERE psc.stock_name = ?', cardinality::SINGLE, array($row[0]));

		if (empty($ipn_id)) {
			$missing_ipns++;
			continue;
		}
		else $found_ipns++;

		//0 = IPN
		//1 = qty
		//2 = price
		//3 = end date

		// we only need one, since the model update handles all additional products
		$product = prepared_query::fetch('SELECT products_id, products_model FROM products p WHERE stock_id = ? LIMIT 1', cardinality::ROW, array($ipn_id));

		$new_price = preg_replace('/[^\d.]/', '', $row[2]);
		$new_expires = new DateTime();
		$new_expires->setTimestamp($row[3]);
		$new_expires->add(new DateInterval('PT5H')); // adjust for UTC
		$new_expires->setTime(23, 23, 59);

		$special = [
			'specials_new_products_price' => $new_price,
			'expires_date' => $new_expires->format('Y-m-d H:i:s'),
			'status' => 1,
			'specials_qty' => $row[1],
			'active_criteria' => $active_criteria[trim($row[4])],
		];

		$listing = new ck_product_listing($product['products_id']);
		$listing->set_special($special);
	}
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
		<script language="javascript" src="/includes/javascript/prototype.js"></script>
		<script language="javascript" src="/includes/javascript/scriptaculous/scriptaculous.js"></script>
		<link rel="stylesheet" type="text/css" href="/includes/javascript/scriptaculous/scriptaculous.css">
		<link rel="stylesheet" type="text/css" href="serials/serials.css">
		<script language="javascript" src="serials/serials.js"></script>
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
					<div style="font-family: arial; padding: 25px;">
						<?php if (@$_POST['action'] == 'process') { ?>
						<br/><br/>
						There were [<?= $rows_in_spreadsheet; ?>] rows in the spreadsheet; [<?= $found_ipns; ?>] IPNs found, [<?= $missing_ipns; ?>] rows skipped.<br/>
						There were [<?= $existing_specials_updated; ?>] existing specials updated.<br/>
						There were [<?= $new_specials_created; ?>] new specials created.
						<?php }
						else { ?>
						Use the form below to upload an Excel spreadsheet containing new specials to be inserted into the store.<br/><br/>
						A sample file can be downloaded <a href="specials_import_template.xls">here</a>. The columns from left to right should contain the IPN name, the special quantity field, the special price, and the end date for the special.<br/><br/>

						<form action="specials_import_list.php" method="post" enctype="multipart/form-data">
							<label for="file">Filename:</label> <input type="file" name="file" id="file"><br>
							<input type="hidden" name="action" value="process">
							<input type="submit" name="submit" value="Submit">
						</form>
						<?php } ?>
					</div>
					<!------------------------------------------------------------------- -->
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
<html>
