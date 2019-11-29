<?php require('includes/application_top.php'); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/menu.js"></script>
	<script language="JavaScript1.2">
		function cOn(td) {
			if (document.getElementById||(document.all && !(document.getElementById))) {
				td.style.backgroundColor="#CCCCCC";
			}
		}

		function cOnA(td) {
			if (document.getElementById||(document.all && !(document.getElementById))) {
				td.style.backgroundColor="#CCFFFF";
			}
		}

		function cOut(td) {
			if (document.getElementById||(document.all && !(document.getElementById))) {
				td.style.backgroundColor="DFE4F4";
			}
		}
	</script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php include(DIR_WS_INCLUDES.'header.php'); ?>
	<!-- header_eof //-->

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
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td class="pageHeading"><?php echo "Cross-Sell (X-Sell) Admin"; ?></td>
									<td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<!-- body_text //-->
						<td width="100%" valign="top">
							<!-- Start of cross sale //-->
							<table width="100%" border="0" cellpadding="0" cellspacing="0">
								<tr>
									<td align=center>
										<?php
										// first major piece of the program
										// we have no instructions, so just dump a full list of products and their status for cross selling
										if (empty($add_related_product_ID)) {
											$products = prepared_query::fetch('SELECT p.products_id, pd.products_name from products p LEFT JOIN products_description pd ON p.products_id = pd.products_id ORDER BY pd.products_name', cardinality::SET); ?>
										<table border="0" cellspacing="1" cellpadding="2" bgcolor="#999999">
											<tr class="dataTableHeadingRow">
												<td class="dataTableHeadingContent" nowrap align=center>ID</td>
												<td class="dataTableHeadingContent">Product Name</td>
												<td class="dataTableHeadingContent" nowrap>Cross-Associated Products</td>
												<td class="dataTableHeadingContent" colspan=3 nowrap align=center>Cross Sell Actions</td>
											</tr>
											<?php foreach ($products as $i => $product) {
												/* now we will query the DB for existing related items */
												$cross_products = prepared_query::fetch('SELECT pd.products_name FROM products_xsell px LEFT JOIN products_description pd ON pd.products_id = px.xsell_id WHERE px.products_id = :products_id ORDER BY px.sort_order', cardinality::COLUMN, [':products_id' => $product['products_id']]); ?>
											<tr onMouseOver="cOn(this);" onMouseOut="cOut(this);" bgcolor='#DFE4F4'>
												<td class="dataTableContent" valign="top"> <?= $product['products_id']; ?> </td>
												<td class="dataTableContent" valign="top"> <?= $product['products_name']; ?> </td>
												<td class="dataTableContent">
													<?php if (!empty($cross_products)) { ?>
													<ol>
														<?php foreach ($cross_products as $p) { ?>
														<li><?= $p; ?></li>
														<?php } ?>
													</ol>
													<?php }
													else echo '--'; ?>
												</td>
												<td class="dataTableContent" valign="top"> <a href="/admin/xsell_products.php?add_related_product_ID=<?= $product['products_id']; ?>">Add</a> </td>
												<td class="dataTableContent" valign="top"> <a href="/admin/xsell_products.php?add_related_product_ID=<?= $product['products_id']; ?>">Remove</a> </td>
												<td class="dataTableContent" valign="top" align="center">
													<?php if (!empty($cross_products)) { ?>
													<a href="/admin/xsell_products.php?sort=1&add_related_product_ID=<?= $product['products_id']; ?>">Sort</a>
													<?php }
													else echo '--'; ?>
												</td>
											</tr>
											<?php } ?>
										</table>
										<?php } // the end of -> if (!$add_related_product_ID)

										if ($_POST && !$sort) {
											if ($_POST[run_update]==true) {
												prepared_query::execute("DELETE FROM products_xsell WHERE products_id = :products_id", [':products_id' => $_POST['add_related_product_ID']]);
											}
											if ($_POST[xsell_id]) {
												foreach ($_POST[xsell_id] as $temp) {
													prepared_query::execute("INSERT INTO products_xsell VALUES ('', :products_id, :temp, 1)", [':products_id' => $_POST['add_related_product_ID'], ':temp' => $temp]);
												}
											}
											echo '<a href="/admin/xsell_products.php">Click Here to add a new cross sale</a><br>'."\n";
											if ($_POST[xsell_id]) echo '<a href="/admin/xsell_products.php?sort=1&add_related_product_ID='.$_POST[add_related_product_ID].'">Click here to sort (top to bottom) the added cross sale</a>'."\n";
										}

										if (!empty($add_related_product_ID) && empty($_POST) && empty($sort)) {	?>
										<table border="0" cellpadding="2" cellspacing="1" bgcolor="#999999">
											<form action="/admin/xsell_products.php" method="post">
												<tr class="dataTableHeadingRow">
													<td class="dataTableHeadingContent"> </td>
													<td class="dataTableHeadingContent" nowrap>Item #</td>
													<td class="dataTableHeadingContent">Item Name</td>
													<td class="dataTableHeadingContent">$Price</td>
												</tr>
												<?php $products = prepared_query::fetch('SELECT p.products_id, pd.products_name, p.products_price FROM products p LEFT JOIN products_description pd ON p.products_id = pd.products_id WHERE p.products_id != :products_id ORDER BY pd.products_name', cardinality::SET, [':products_id' => $add_related_product_ID]);

												$cross_products = prepared_query::fetch('SELECT xsell_id FROM products_xsell WHERE products_id = :products_id', cardinality::COLUMN, [':products_id' => $add_related_product_ID]);

												foreach ($products as $i => $product) { ?>
												<tr bgcolor='#DFE4F4'>
													<td class="dataTableContent">
														<?php $run_update = FALSE; // set to false to insert new entry in the DB
														if (!empty($cross_products)) {
															foreach ($cross_products as $p) {
																if ($product['products_id'] === $p) $run_update = TRUE;
															}
														} ?>
														<input <?= $run_update?'checked':''; ?> size="20" size="20" name="xsell_id[]" type="checkbox" value="<?= $product['products_id']; ?>">
													</td>
													<td class="dataTableContent" align=center><?= $product['products_id']; ?></td>
													<td class="dataTableContent"><?= $product['products_name']; ?></td>
													<td class="dataTableContent"><?= CK\text::monetize($product['products_price']); ?></td>
												</tr>
												<?php } ?>
												<tr>
													<td colspan="4">
														<input type="hidden" name="run_update" value="<?= $run_update?'true':'false'; ?>">
														<input type="hidden" name="add_related_product_ID" value="<?= $add_related_product_ID; ?>">
														<input type="submit" name="Submit" value="Submit">
													</td>
												</tr>
											</form>
										</table>
										<?php }

										// sort routines
										if (@$sort==1) {
											//	first lets take care of the DB update.
											$run_once=0;
											if ($_POST) {
												foreach ($_POST as $key_a => $value_a) {
													prepared_query::execute("UPDATE products_xsell SET sort_order = :sort_order WHERE xsell_id= :xsell_id", [':sort_order' => $value_a, ':xsell_id' => $key_a]);
													if ($value_a != 'Update') {
													}
													else {
														if ($run_once==0) {
															echo '<b>The Database was updated <a href="/admin/xsell_products.php">Click here to back to the main page</a></b><br>'."\n";
															$run_once++;
														}
													}
												}// end of foreach.
											} ?>
										<form method="post" action="/admin/xsell_products.php?sort=1&add_related_product_ID=<?= $add_related_product_ID; ?>">
											<table cellpadding="2" cellspacing="1" bgcolor=999999 border="0">
												<tr class="dataTableHeadingRow">
													<td class="dataTableHeadingContent" width="75">Product ID</td>
													<td class="dataTableHeadingContent">Name</td>
													<td class="dataTableHeadingContent" width="150">Price</td>
													<td class="dataTableHeadingContent" width="150">Order (1=Top)</td>
												</tr>
												<?php $cross_products = prepared_query::fetch('SELECT xsell_id, sort_order FROM products_xsell WHERE products_id = :products_id', cardinality::SET, [':products_id' => $add_related_product_ID]);
												$cpcount = count($cross_products);
												foreach ($cross_products as $i => $cp) {
													$product = prepared_query::fetch('SELECT p.products_id, pd.products_namel, a.products_price FROM products p JOIN products_description pd ON p.products_id = pd.products_id WHERE p.products_id = :products_id', cardinality::ROW, [':products_id' => $cp['xsell_id']]); ?>
												<tr class="dataTableContentRow" bgcolor='#DFE4F4'>
													<td class="dataTableContent"><?= $product['products_id']; ?></td>
													<td class="dataTableContent"><?= $product['products_name']; ?></td>
													<td class="dataTableContent"><?= CK\text::monetize($product['products_price']); ?></td>
													<td class="dataTableContent">
														<select name="<?= $product['products_id']; ?>">
															<?php for ($j=1; $j<=$cpcount; $j++) { ?>
															<option value="<?= $j; ?>" <?= $j==$cp['sort_order']?'selected':''; ?>><?= $j; ?></option>
															<?php } ?>
														</select>
													</td>
												</tr>
												<?php } // the end of foreach	?>
												<tr>
													<td colspan="4" bgcolor='#DFE4F4'><input name="runing_update" type="submit" id="runing_update" value="Update"></td>
												</tr>
											</table>
										</form>
										<?php } ?>
									</td>
								</tr>
							</table>
							<!-- End of cross sale //-->
						</td>
						<!-- products_attributes_eof //-->
					</tr>
				</table>
				<!-- body_text_eof //-->
			</td>
		</tr>
	</table>
</body>
</html>
