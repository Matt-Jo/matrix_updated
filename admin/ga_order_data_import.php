<?php
require('includes/application_top.php');
require_once('includes/ExcelClass/reader.php');

function trim_quotes($s) {
	$s=trim($s);
	return trim($s, "\"");
}

set_time_limit(0);

$import_run = FALSE;
$output = array();
$errors = array();

if (!empty($_FILES)) {
	if (!empty($_FILES['ga_import'])) {
		$import_run = TRUE;

		if (!empty($_FILES['ga_import']['error'])) {
			//print "Sorry, there was a problem with that uploaded file.<br/>";
			//die();
			$errors[] = "Sorry, there was a problem with that uploaded file.<br/>";
		}
		else {
			$data = new Spreadsheet_Excel_Reader();
			$data->setOutputEncoding('CP1251');

			$number_added = $number_failed = 0;

			$data->read($_FILES['ga_import']['tmp_name']);

			for ($i=2; $i<=$data->sheets[0]['numRows']; $i++) {
				@list(
					$tmp,
					$order_id,
					$channel,
					$source,
					$medium,
					$campaign,
					$content
				) = $data->sheets[0]['cells'][$i];

				updateOrderData($order_id, $channel, $source, $medium, $campaign, $content);

				$number_added++;
			}

			$output[] = $number_added." orders have been updated.";
		}
	}
}

function updateOrderData($order_id, $channel, $source, $medium, $campaign, $content) {
	prepared_query::execute('UPDATE orders SET channel = :channel, source = :source, medium = :medium, campaign = :campaign, content = :content WHERE orders_id = :orders_id', [':channel' => $channel, ':source' => $source, ':medium' => $medium, ':campaign' => $campaign, ':content' => $content, ':orders_id' => $order_id]);

	$split_orders = prepared_query::fetch('SELECT o.orders_id FROM orders o WHERE o.parent_orders_id = :order_id', cardinality::COLUMN, [':order_id' => $order_id]);

	foreach ($split_orders as $split_order) {
		updateOrderData($split_order, $channel, $source, $medium, $campaign, $content);
	}
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
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
					<table border="0" width="800" cellspacing="0" cellpadding="2">
						<tr>
							<td>
								<?php if (!empty($import_run)) {
									echo implode("\n", $output);
									if (!empty($errors)) {
										echo "<br/>ERRORS:<br/>";
										echo implode("<br/>", $errors);
									}
								} ?>
								<?php if (isset($_GET['action']) && $_GET['action'] == 'export') { ?>
								<a href="/admin/attribute_update_rpt.txt">EXPORT</a> (right click, save as)<br/>
								<?php } ?>
								<script src="https://use.edgefonts.net/piedra:n4:all.js"></script>
								<style>
									.scorched { text-transform:uppercase; font-weight:600; font-family: piedra; font-size:18px; color:#f62; background: -webkit-linear-gradient(#fa4, #f32); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
									.utabs { margin:0px; padding:0px; }
									.utabs li { float:left; padding:4px 8px; list-style-type:none; margin-left:8px; border-width:1px 1px 0px 1px; border-style:solid; border-color:#000; background-color:#cb2026; color:#fff; font-weight:bold; cursor:pointer; }
									.utabs li.selected { cursor:default; background-color:#fff; position:relative; top:1px; z-index:5; color:#000; }
									.tabbox { padding:10px; border:1px solid #000; float:left; clear:both; display:none; }
									.tabbox.selected { display:block; }
								</style>
								<ul class="utabs">
									<li class="selected" data-tabref="ga_import">GA Order Data Import</li>
								</ul>
								<div id="ga_import" class="tabbox selected">
									<p><b>Upload GA Order Data Excel File</b></p>
					<p>Please make sure the file you upload is a .xls (not .xlsx) file. Also, the order of the columns should be as follows:</p>
					<table border="1"><tr>
						<td>Order ID</td>
						<td>Channel</td>
						<td>Source</td>
						<td>Medium</td>
						<td>Campaign</td>
						<td>Content</td>
					</tr></table>
					<p>NOTE: The first line of the Excel file will be ignored so you may leave column labels in the first row.</p>
									<form enctype="multipart/form-data" action="/admin/ga_order_data_import.php" method="POST">
										File: <input name="ga_import" type="file" />
										<input type="submit" value="Run Import" />
									</form>
								</div>
								<script>
									jQuery('.utabs li').click(function() {
										if (jQuery(this).hasClass('selected')) return;
										jQuery('.utabs li.selected').removeClass('selected');
										jQuery(this).addClass('selected');
										jQuery('.tabbox.selected').removeClass('selected');
										jQuery('#'+jQuery(this).attr('data-tabref')).addClass('selected');
									});
								</script>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</body>
</html>
