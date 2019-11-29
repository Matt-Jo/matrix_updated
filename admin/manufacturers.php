<?php
 require('includes/application_top.php');

 $action = (isset($_GET['action']) ? $_GET['action'] : '');

 if (tep_not_null($action)) {
	switch ($action) {
	case 'insert':
	case 'save':
		if (isset($_GET['mID'])) $manufacturers_id = $_GET['mID'];
		$manufacturers_name = $_POST['manufacturers_name'];

		$sql_data_array = array('manufacturers_name' => $manufacturers_name);

		if ($action == 'insert') {
			$insert_sql_data = array('date_added' => prepared_expression::NOW());

			$sql_data_array = array_merge($sql_data_array, $insert_sql_data);

			$insert = new prepared_fields($sql_data_array, prepared_fields::INSERT_QUERY);
			$manufacturers_id = prepared_query::insert('INSERT INTO manufacturers ('.$insert->insert_fields().') VALUES ('.$insert->insert_values().')', $insert->insert_parameters());
		}
		elseif ($action == 'save') {
			$update_sql_data = array('last_modified' => prepared_expression::NOW());

			$sql_data_array = array_merge($sql_data_array, $update_sql_data);

			$update = new prepared_fields($sql_data_array, prepared_fields::UPDATE_QUERY);
			$id = new prepared_fields(['manufacturers_id' => $manufacturers_id]);

			prepared_query::execute('UPDATE manufacturers SET '.$update->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($update, $id));
		}

        $manufacturers_image = new upload('manufacturers_image', DIR_FS_CATALOG_IMAGES);
		prepared_query::execute("update manufacturers set manufacturers_image = :image where manufacturers_id = :manufacturers_id", [':image' => $manufacturers_image->filename, ':manufacturers_id' => $manufacturers_id]);

		$manufacturers_url_array = $_POST['manufacturers_url'];

		$sql_data_array = array('manufacturers_url' => $manufacturers_url_array[1]);

		if ($action == 'insert') {
			$insert_sql_data = array('manufacturers_id' => $manufacturers_id,
									'languages_id' => 1);

			$sql_data_array = array_merge($sql_data_array, $insert_sql_data);

			$insert = new prepared_fields($sql_data_array, prepared_fields::INSERT_QUERY);
			prepared_query::execute('INSERT INTO manufacturers_info ('.$insert->insert_fields().') VALUES ('.$insert->insert_values().')', $insert->insert_parameters());
		}
		elseif ($action == 'save') {
			$update = new prepared_fields($sql_data_array, preprared_fields::UPDATE_QUERY);
			$id = new prepared_fields(['manufacturers_id' => $manufacturers_id, 'languages_id' => 1]);
			prepared_query::execute('UPDATE manufacturers_info SET '.$update->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($update, $id));
		}

		CK\fn::redirect_and_exit('/admin/manufacturers.php?'.(isset($_GET['page']) ? 'page='.$_GET['page'].'&' : '').'mID='.$manufacturers_id);

		break;
	case 'deleteconfirm':
		$manufacturers_id = $_GET['mID'];

		if (isset($_POST['delete_image']) && ($_POST['delete_image'] == 'on')) {
			$manufacturer = prepared_query::fetch("select manufacturers_image from manufacturers where manufacturers_id = :manufacturers_id", cardinality::ROW, [':manufacturers_id' => $manufacturers_id]);

			$image_location = DIR_FS_DOCUMENT_ROOT.DIR_WS_CATALOG_IMAGES.$manufacturer['manufacturers_image'];

			if (file_exists($image_location)) @unlink($image_location);
		}

		prepared_query::execute("delete from manufacturers where manufacturers_id = :manufacturers_id", [':manufacturers_id' => $manufacturers_id]);
		prepared_query::execute("delete from manufacturers_info where manufacturers_id = :manufacturers_id", [':manufacturers_id' => $manufacturers_id]);

		if (isset($_POST['delete_products']) && ($_POST['delete_products'] == 'on')) {
			$products_ids = prepared_query::fetch("select products_id from products where manufacturers_id = :manufacturers_id", cardinality::COLUMN, [':manufacturers_id' => $manufacturers_id]);
			foreach ($products_ids as $products_id) {
				tep_remove_product($products_id);
			}
		}
		else {
			prepared_query::execute("update products set manufacturers_id = '' where manufacturers_id = :manufacturers_id", [':manufacturers_id' => $manufacturers_id]);
		}

		CK\fn::redirect_and_exit('/admin/manufacturers.php?page='.$_GET['page']);

		break;
	}
 }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onload="SetFocus();">
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
		<td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
		<tr>
			<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
			<td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
		</tr>
		</table></td>
	</tr>
	<tr>
		<td><table border="0" width="100%" cellspacing="0" cellpadding="0">
		<tr>
			<td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
			<tr class="dataTableHeadingRow">
				<td class="dataTableHeadingContent">Manufacturers</td>
				<td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
			</tr>
<?php
 $manufacturers_list = prepared_query::fetch('SELECT m.manufacturers_id, m.manufacturers_name, m.manufacturers_image, m.date_added, m.last_modified, mi.manufacturers_url FROM manufacturers m LEFT JOIN manufacturers_info mi ON m.manufacturers_id = mi.manufacturers_id AND mi.languages_id = 1 ORDER BY m.manufacturers_name', cardinality::SET);

 foreach ($manufacturers_list as $manufacturers) {
	if ((!isset($_GET['mID']) || (isset($_GET['mID']) && ($_GET['mID'] == $manufacturers['manufacturers_id']))) && !isset($mInfo) && (substr($action, 0, 3) != 'new')) {
		$manufacturer_products = prepared_query::fetch("select count(*) as products_count from products where manufacturers_id = :manufacturers_id", cardinality::ROW, [':manufacturers_id' => $manufacturers['manufacturers_id']]);

		$mInfo_array = array_merge($manufacturers, $manufacturer_products);
		$mInfo = (object)$mInfo_array;
	}

	if (isset($mInfo) && is_object($mInfo) && ($manufacturers['manufacturers_id'] == $mInfo->manufacturers_id)) {
	echo '			<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\''.'/admin/manufacturers.php?page='.@$_GET['page'].'&mID='.$manufacturers['manufacturers_id'].'&action=edit'.'\'">'."\n";
	} else {
	echo '			<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\''.'/admin/manufacturers.php?page='.@$_GET['page'].'&mID='.$manufacturers['manufacturers_id'].'\'">'."\n";
	}
?>
				<td class="dataTableContent"><?= $manufacturers['manufacturers_name']; ?></td>
				<td class="dataTableContent" align="right"><?php if (isset($mInfo) && is_object($mInfo) && ($manufacturers['manufacturers_id'] == $mInfo->manufacturers_id)) { echo tep_image(DIR_WS_IMAGES.'icon_arrow_right.gif'); } else { echo '<a href="'.'/admin/manufacturers.php?page='.@$_GET['page'].'&mID='.$manufacturers['manufacturers_id'].'">'.tep_image(DIR_WS_IMAGES.'icon_info.gif', IMAGE_ICON_INFO).'</a>'; } ?>&nbsp;</td>

			</tr>
<?php
 }
?>
			<tr>
				<td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
				<tr>
					<td class="smallText" valign="top"></td>
					<td class="smallText" align="right"></td>
				</tr>
				</table></td>
			</tr>
<?php
 if (empty($action)) {
?>
			<tr>

				<td align="right" colspan="2" class="smallText"><?php echo '<a href="/admin/manufacturers.php?page='.@$_GET['page'].'&mID='.$mInfo->manufacturers_id.'&action=new'.'">'.tep_image_button('button_insert.gif', IMAGE_INSERT).'</a>'; ?></td>

			</tr>
<?php
 }
?>
			</table></td>
<?php
 $heading = array();
 $contents = array();

 switch ($action) {
	case 'new':
	$heading[] = array('text' => '<b>'.TEXT_HEADING_NEW_MANUFACTURER.'</b>');

	$contents = array('form' => tep_draw_form('manufacturers', FILENAME_MANUFACTURERS, 'action=insert', 'post', 'enctype="multipart/form-data"'));
	$contents[] = array('text' => TEXT_NEW_INTRO);
	$contents[] = array('text' => '<br>'.TEXT_MANUFACTURERS_NAME.'<br>'.tep_draw_input_field('manufacturers_name'));
	$contents[] = array('text' => '<br>'.TEXT_MANUFACTURERS_IMAGE.'<br>'.tep_draw_file_field('manufacturers_image'));

	$manufacturer_inputs_string = '';
	$manufacturer_inputs_string .= '<br>'.tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English').'&nbsp;'.tep_draw_input_field('manufacturers_url[1]');

	$contents[] = array('text' => '<br>'.TEXT_MANUFACTURERS_URL.$manufacturer_inputs_string);

	$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_save.gif', IMAGE_SAVE).' <a href="/admin/manufacturers.php?page='.$_GET['page'].'&mID='.$_GET['mID'].'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');

	break;
	case 'edit':
	$heading[] = array('text' => '<b>'.TEXT_HEADING_EDIT_MANUFACTURER.'</b>');

	$contents = array('form' => tep_draw_form('manufacturers', FILENAME_MANUFACTURERS, 'page='.$_GET['page'].'&mID='.$mInfo->manufacturers_id.'&action=save', 'post', 'enctype="multipart/form-data"'));
	$contents[] = array('text' => TEXT_EDIT_INTRO);
	$contents[] = array('text' => '<br>'.TEXT_MANUFACTURERS_NAME.'<br>'.tep_draw_input_field('manufacturers_name', $mInfo->manufacturers_name));
	$contents[] = array('text' => '<br>'.TEXT_MANUFACTURERS_IMAGE.'<br>'.tep_draw_file_field('manufacturers_image').'<br>'.$mInfo->manufacturers_image);

	$manufacturer_inputs_string = '';
	$manufacturer_inputs_string .= '<br>'.tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English').'&nbsp;'.tep_draw_input_field('manufacturers_url[1]', $mInfo->manufacturers_url);

	$contents[] = array('text' => '<br>'.TEXT_MANUFACTURERS_URL.$manufacturer_inputs_string);

	$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_save.gif', IMAGE_SAVE).' <a href="/admin/manufacturers.php?page='.$_GET['page'].'&mID='.$mInfo->manufacturers_id.'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');

	break;
	case 'delete':
	$heading[] = array('text' => '<b>'.TEXT_HEADING_DELETE_MANUFACTURER.'</b>');

	$contents = array('form' => tep_draw_form('manufacturers', FILENAME_MANUFACTURERS, 'page='.$_GET['page'].'&mID='.$mInfo->manufacturers_id.'&action=deleteconfirm'));
	$contents[] = array('text' => TEXT_DELETE_INTRO);
	$contents[] = array('text' => '<br><b>'.$mInfo->manufacturers_name.'</b>');
	$contents[] = array('text' => '<br>'.tep_draw_checkbox_field('delete_image', '', true).' '.TEXT_DELETE_IMAGE);

	if ($mInfo->products_count > 0) {
		$contents[] = array('text' => '<br>'.tep_draw_checkbox_field('delete_products').' '.TEXT_DELETE_PRODUCTS);
		$contents[] = array('text' => '<br>'.sprintf(TEXT_DELETE_WARNING_PRODUCTS, $mInfo->products_count));
	}


	$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_delete.gif', IMAGE_DELETE).' <a href="/admin/manufacturers.php?page='.$_GET['page'].'&mID='.$mInfo->manufacturers_id.'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');

	break;
	default:
	if (isset($mInfo) && is_object($mInfo)) {
		$heading[] = array('text' => '<b>'.$mInfo->manufacturers_name.'</b>');


		$contents[] = array('align' => 'center', 'text' => '<a href="/admin/manufacturers.php?page='.@$_GET['page'].'&mID='.$mInfo->manufacturers_id.'&action=edit'.'">'.tep_image_button('button_edit.gif', IMAGE_EDIT).'</a> <a href="/admin/manufacturers.php?page='.@$_GET['page'].'&mID='.$mInfo->manufacturers_id.'&action=delete'.'">'.tep_image_button('button_delete.gif', IMAGE_DELETE).'</a>');

		$date_added = new ck_datetime($mInfo->date_added);
		$last_modified = new ck_datetime($mInfo->last_modified);

		$contents[] = array('text' => '<br>'.TEXT_DATE_ADDED.' '.$date_added->format('m/d/Y'));
		if (tep_not_null($mInfo->last_modified)) $contents[] = array('text' => TEXT_LAST_MODIFIED.' '.$last_modified->format('m/d/Y'));
		$contents[] = array('text' => '<br>'.tep_info_image($mInfo->manufacturers_image, $mInfo->manufacturers_name));
		$contents[] = array('text' => '<br>'.TEXT_PRODUCTS.' '.$mInfo->products_count);
	}
	break;
 }

 if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
	echo '			<td width="25%" valign="top">'."\n";

	$box = new box;
	echo $box->infoBox($heading, $contents);

	echo '			</td>'."\n";
 }
?>
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
