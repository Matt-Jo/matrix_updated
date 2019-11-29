<?php
require('includes/application_top.php');
require('includes/functions/faqdesk_general.php');

$action = !empty($_GET['action'])?$_GET['action']:NULL;

if (!empty($action)) {
	switch ($action) {
		// -------------------------------------------------------------------------------------------------------------------------------------------------------------
		// status call area ... you know the green/red lights
		// -------------------------------------------------------------------------------------------------------------------------------------------------------------
		case 'setflag':
			// -------------------------------------------------------------------------------------------------------------------------------------------------------------
			if ( ($_GET['flag'] == '0') || ($_GET['flag'] == '1') ) {
				if (!empty($_GET['pID'])) {
					faqdesk_set_product_status($_GET['pID'], $_GET['flag']);
				}
				if (!empty($_GET['categories_id'])) {
					faqdesk_set_categories_status($_GET['categories_id'], $_GET['flag']);
				}
			}

		// -----------------------------------------------------------------------
		// sticky call area ... you know the green/red lights
		// -----------------------------------------------------------------------
		case 'setflag_sticky':
			// -----------------------------------------------------------------------
			if ( ($_GET['flag_sticky'] == '0') || ($_GET['flag_sticky'] == '1') ) {
				if (!empty($_GET['pID'])) {
					faqdesk_set_product_sticky($_GET['pID'], $_GET['flag_sticky']);
				}
			}

			CK\fn::redirect_and_exit(tep_href_link(FILENAME_FAQDESK, 'cPath='.$_GET['cPath']));
			break;

		// -------------------------------------------------------------------------------------------------------------------------------------------------------------
		case 'insert_category':
		// -------------------------------------------------------------------------------------------------------------------------------------------------------------
		case 'update_category':
			// -------------------------------------------------------------------------------------------------------------------------------------------------------------
			// double call codes ... all in one mentality ???
			// -------------------------------------------------------------------------------------------------------------------------------------------------------------
			$categories_id = $_POST['categories_id'];
			$sort_order = $_POST['sort_order'];

			//$sql_data_array = array('sort_order' => $sort_order);
			$catagory_status = $_POST['catagory_status'];
			$sql_data_array = array('sort_order' => $sort_order, 'catagory_status' => $catagory_status);

			if ($action == 'insert_category') {
				$insert_sql_data = array(
					'parent_id' => $current_category_id,
					'date_added' => prepared_expression::NOW()
				);
				$sql_data_array = array_merge($sql_data_array, $insert_sql_data);

				$insert = new prepared_fields($sql_data_array, prepared_fields::INSERT_QUERY);
				$categories_id = prepared_query::insert('INSERT INTO faqdesk_categories ('.$insert->insert_fields().') VALUES ('.$insert->insert_values().')', $insert->insert_parameters());
			}
			elseif ($action == 'update_category') {
				$update_sql_data = array('last_modified' => prepared_expression::NOW());
				$sql_data_array = array_merge($sql_data_array, $update_sql_data);

				$update = new prepared_fields($sql_data_array, prepared_fields::UPDATE_QUERY);
				$id = new prepared_fields(['categories_id' => $categories_id]);

				prepared_query::execute('UPDATE faqdesk_categories SET '.$update->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($update, $id));
			} // top if closing bracket

			$categories_name_array = $_POST['categories_name'];
			$categories_description_array = $_POST['categories_description'];
			$sql_data_array = array('categories_name' => $categories_name_array[1],
					'categories_description' => $categories_description_array[1]
				);
			if ($action == 'insert_category') {
				$insert_sql_data = array(
					'categories_id' => $categories_id,
					'language_id' => 1
				);
				$sql_data_array = array_merge($sql_data_array, $insert_sql_data);

				$insert = new prepared_fields($sql_data_array, prepared_fields::INSERT_QUERY);
				prepared_query::execute('INSERT INTO faqdesk_categories_description ('.$insert->insert_fields().') VALUES ('.$insert->insert_values().')', $insert->insert_parameters());
			}
			elseif ($action == 'update_category') {
				$update = new prepared_fields($sql_data_array, prepared_fields::UPDATE_QUERY);
				$id = new prepared_fields(['categories_id' => $categories_id, 'language_id' => 1]);
				prepared_query::execute('UPDATE faqdesk_categories_description SET '.$update->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($update, $id));
			}

			$categories_image = tep_get_uploaded_file('categories_image');
			$image_directory = tep_get_local_path(DIR_FS_CATALOG_IMAGES);

			if (is_uploaded_file($categories_image['tmp_name'])) {
				prepared_query::execute("update faqdesk_categories set categories_image = :image where categories_id = :categories_id", [':image' => $categories_image['name'], ':categories_id' => $categories_id]);
				tep_copy_uploaded_file($categories_image, $image_directory);
			}

			CK\fn::redirect_and_exit(tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&categories_id='.$categories_id));
			break;

		// -------------------------------------------------------------------------------------------------------------------------------------------------------------
		case 'delete_category_confirm':
			// -------------------------------------------------------------------------------------------------------------------------------------------------------------
			if (!empty($_POST['categories_id'])) {
				$categories_id = $_POST['categories_id'];

				$categories = faqdesk_get_category_tree($categories_id, '', '0', '', true);
				$products = array();
				$products_delete = array();

				for ($i = 0, $n = sizeof($categories); $i < $n; $i++) {
					$faqdesk_ids = prepared_query::fetch("select faqdesk_id from faqdesk_to_categories where categories_id = :categories_id", cardinality::COLUMN, [':categories_id' => $categories[$i]['id']]);
					foreach ($faqdesk_ids as $faqdesk_id) {
						$products[$faqdesk_id]['categories'][] = $categories[$i]['id'];
					}
				}

				foreach ($products as $key => $value) {
					$category_ids = '';
					for ($i = 0, $n = sizeof($value['categories']); $i < $n; $i++) {
						$category_ids .= '\''.$value['categories'][$i].'\', ';
					}
					$category_ids = substr($category_ids, 0, -2);

					$check = prepared_query::fetch("select count(*) as total from faqdesk_to_categories where faqdesk_id = :faqdesk_id and categories_id not in (".$category_ids.")", cardinality::SINGLE, [':faqdesk_id' => $key]);
					if ($check < '1') {
						$products_delete[$key] = $key;
					}
				}

				// Removing categories can be a lengthy process
				tep_set_time_limit(0);
				for ($i = 0, $n = sizeof($categories); $i < $n; $i++) {
					faqdesk_remove_category($categories[$i]['id']);
				}

				foreach ($products_delete as $key => $val) {
					faqdesk_remove_product($key);
				}
			}
			// main if closing bracket

			CK\fn::redirect_and_exit(tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath));
			break;

		// -------------------------------------------------------------------------------------------------------------------------------------------------------------
		case 'delete_product_confirm':
			// -------------------------------------------------------------------------------------------------------------------------------------------------------------
			if ( ($_POST['faqdesk_id']) && (is_array($_POST['product_categories'])) ) {
				$product_id = $_POST['faqdesk_id'];
				$product_categories = $_POST['product_categories'];

				for ($i = 0, $n = sizeof($product_categories); $i < $n; $i++) {
					prepared_query::execute("delete from faqdesk_to_categories where faqdesk_id = :faqdesk_id and categories_id = :categories_id", [':faqdesk_id' => $product_id, ':categories_id' => $product_categories[$i]]);
				}

				$product_categories = prepared_query::fetch("select count(*) as total from faqdesk_to_categories where faqdesk_id = :faqdesk_id", cardinality::SINGLE, [':faqdesk_id' => $product_id]);

				if ($product_categories == '0') {
					faqdesk_remove_product($product_id);
				}

				if ($_POST['delete_image'] == 'yes') {
					unlink(DIR_FS_CATALOG_IMAGES.$_POST['products_previous_image']);
				}
			} // top if closing bracket

			CK\fn::redirect_and_exit(tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath));
			break;

		// -------------------------------------------------------------------------------------------------------------------------------------------------------------
		case 'move_category_confirm':
			// -------------------------------------------------------------------------------------------------------------------------------------------------------------
			if ( ($_POST['categories_id']) && ($_POST['categories_id'] != $_POST['move_to_category_id']) ) {
				$categories_id = $_POST['categories_id'];
				$new_parent_id = $_POST['move_to_category_id'];
				prepared_query::execute("update faqdesk_categories set parent_id = :parent_id, last_modified = now() where categories_id = :categories_id", [':parent_id' => $new_parent_id, ':categories_id' => $categories_id]);
			} // top if closing bracket

			CK\fn::redirect_and_exit(tep_href_link(FILENAME_FAQDESK, 'cPath='.$new_parent_id.'&categories_id='.$categories_id));
			break;

		// -------------------------------------------------------------------------------------------------------------------------------------------------------------
		case 'move_product_confirm':
			// -------------------------------------------------------------------------------------------------------------------------------------------------------------
			$faqdesk_id = $_POST['faqdesk_id'];
			$new_parent_id = $_POST['move_to_category_id'];

			$duplicate_check = prepared_query::fetch("select count(*) as total from faqdesk_to_categories where faqdesk_id = :faqdesk_id and categories_id = :categories_id", cardinality::SINGLE, [':faqdesk_id' => $faqdesk_id, ':categories_id' => $new_parent_id]);
			if ($duplicate_check < 1) prepared_query:execute("update faqdesk_to_categories set categories_id = :new_categories_id where faqdesk_id = :faqdesk_id and categories_id = :categories_id", [':new_categories_id' => $new_parent_id, ':faqdesk_id' => $faqdesk_id, ':categories_id' => $current_category_id]);

			CK\fn::redirect_and_exit(tep_href_link(FILENAME_FAQDESK, 'cPath='.$new_parent_id.'&pID='.$faqdesk_id));
			break;

		// -------------------------------------------------------------------------------------------------------------------------------------------------------------
		case 'insert_product':
		// -------------------------------------------------------------------------------------------------------------------------------------------------------------
		case 'update_product':
			// -------------------------------------------------------------------------------------------------------------------------------------------------------------
			// Another double case situation -- must be an all in one mentality!
			// -------------------------------------------------------------------------------------------------------------------------------------------------------------
			if ( ($_POST['edit_x']) || ($_POST['edit_y']) ) {
				$action = 'new_product';
			}
			else {
				$faqdesk_id = $_GET['pID'];
				$faqdesk_date_available = $_POST['faqdesk_date_available'];

				$faqdesk_date_available = (date('Y-m-d') < $faqdesk_date_available) ? $faqdesk_date_available : 'null';

				$sql_data_array = array(
					'faqdesk_image' => (($_POST['faqdesk_image'] == 'none') ? '' : $_POST['faqdesk_image']),
					'faqdesk_image_two' => (($_POST['faqdesk_image_two'] == 'none') ? '' : $_POST['faqdesk_image_two']),
					'faqdesk_image_three' => (($_POST['faqdesk_image_three'] == 'none') ? '' : $_POST['faqdesk_image_three']),
					'faqdesk_date_available' => $faqdesk_date_available,
					'faqdesk_status' => $_POST['faqdesk_status'],
					'faqdesk_sticky' => $_POST['faqdesk_sticky'],
				);

				if ($action == 'insert_product') {
					$insert_sql_data = array('faqdesk_date_added' => prepared_expression::NOW());
					$sql_data_array = array_merge($sql_data_array, $insert_sql_data);

					$insert = new prepared_fields($sql_data_array, prepared_fields::INSERT_QUERY);
					$faqdesk_id = prepared_query::insert('INSERT INTO faqdesk ('.$insert->insert_fields().') VALUES ('.$insert->insert_values().')', $insert->insert_parameters());
					prepared_query::execute("insert into faqdesk_to_categories (faqdesk_id, categories_id) values (:faqdesk_id, :categories_id)", [':faqdesk_id' => $faqdesk_id, ':categories_id' => $current_category_id]);
				}
				elseif ($action == 'update_product') {
					$update_sql_data = array('faqdesk_last_modified' => prepared_expression::NOW());
					$sql_data_array = array_merge($sql_data_array, $update_sql_data);

					$update = new prepared_fields($sql_data_array, prepared_fields::UPDATE_QUERY);
					$id = new prepared_fields(['faqdesk_id' => $faqdesk_id]);
					prepared_query::execute('UPDATE faqdesk SET '.$update->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($update, $id));
				}

				$sql_data_array = array(
					'faqdesk_question' => $_POST['faqdesk_question'][1],
					'faqdesk_answer_long' => $_POST['faqdesk_answer_long'][1],
					'faqdesk_answer_short' => $_POST['faqdesk_answer_short'][1],
					'faqdesk_extra_url' => $_POST['faqdesk_extra_url'][1],
					'faqdesk_image_text' => $_POST['faqdesk_image_text'][1],
					'faqdesk_image_text_two' => $_POST['faqdesk_image_text_two'][1],
					'faqdesk_image_text_three' => $_POST['faqdesk_image_text_three'][1],
				);

				if ($action == 'insert_product') {
					$insert_sql_data = array(
						'faqdesk_id' => $faqdesk_id,
						'language_id' => 1
					);
					$sql_data_array = array_merge($sql_data_array, $insert_sql_data);

					$insert = new prepared_fields($sql_data_array, prepared_fields::INSERT_QUERY);
					prepared_query::execute('INSERT INTO faqdesk_description ('.$insert->insert_fields().') VALUES ('.$insert->insert_values().')', $insert->insert_parameters());
				}
				elseif ($action == 'update_product') {
					$update = new prepared_fields($sql_data_array, prepared_fields::UPDATE_QUERY);
					$id = new prepared_fields(['faqdesk_id' => $faqdesk_id, 'language_id' => 1]);
					prepared_query::execute('UPDATE faqdesk_description SET '.$update->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($update, $id));
				}

				CK\fn::redirect_and_exit(tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&pID='.$faqdesk_id));
			} // midway closing if bracket
			break;

		// -------------------------------------------------------------------------------------------------------------------------------------------------------------
		case 'copy_to_confirm':
			// -------------------------------------------------------------------------------------------------------------------------------------------------------------
			if ( (tep_not_null($_POST['faqdesk_id'])) && (tep_not_null($_POST['categories_id'])) ) {
				$faqdesk_id = $_POST['faqdesk_id'];
				$categories_id = $_POST['categories_id'];

				if ($_POST['copy_as'] == 'link') {
					if ($_POST['categories_id'] != $current_category_id) {
						$check = prepared_query::fetch("select count(*) as total from faqdesk_to_categories where faqdesk_id = :faqdesk_id and categories_id = :categories_id", cardinality::SINGLE, [':faqdesk_id' => $faqdesk_id, ':categories_id' => $categories_id]);
						if ($check < '1') {
							prepared_query::execute("insert into faqdesk_to_categories (faqdesk_id, categories_id) values (:faqdesk_id, :categories_id)", [':faqdesk_id' => $faqdesk_id, ':categories_id' => $categories_id]);
						}
					}
					else {
						$messageStack->add_session(ERROR_CANNOT_LINK_TO_SAME_CATEGORY, 'error');
					}
				}
				elseif ($_POST['copy_as'] == 'duplicate') {
					$product = prepared_query::fetch("select faqdesk_image, faqdesk_image_two, faqdesk_image_three, faqdesk_date_added, faqdesk_date_available, faqdesk_status, faqdesk_sticky from faqdesk where faqdesk_id = :faqdesk_id", cardinality::ROW, [':faqdesk_id' => $faqdesk_id]);

					$dup_faqdesk_id = prepared_query::insert("insert into faqdesk (faqdesk_image, faqdesk_image_two, faqdesk_image_three, faqdesk_date_added, faqdesk_date_available, faqdesk_status, faqdesk_sticky) values (:faqdesk_image, :faqdesk_image_two, :faqdesk_image_three, :faqdesk_date_added, :faqdesk_date_available, :faqdesk_status, :faqdesk_sticky)", [':faqdesk_image' => $product['faqdesk_image'], ':faqdesk_image_two' => $product['faqdesk_image_two'], ':faqdesk_image_three' => $product['faqdesk_image_three'], ':faqdesk_date_added' => $product['faqdesk_date_added'], ':faqdesk_date_available' => $product['faqdesk_date_available'], ':faqdesk_status' => $product['faqdesk_status'], ':faqdesk_sticky' => $product['faqdesk_sticky']]);

					$descriptions = prepared_query::fetch("select language_id, faqdesk_question, faqdesk_answer_long, faqdesk_extra_url, faqdesk_image_text, faqdesk_image_text_two, faqdesk_image_text_three, faqdesk_extra_viewed, faqdesk_answer_short from faqdesk_description where faqdesk_id = :faqdesk_id", cardinality::SET, [':faqdesk_id' => $faqdesk_id]);

					foreach ($descriptions as $description) {
						prepared_query::execute("insert into faqdesk_description (faqdesk_id, language_id, faqdesk_question, faqdesk_answer_long, faqdesk_extra_url, faqdesk_image_text, faqdesk_image_text_two, faqdesk_image_text_three, faqdesk_extra_viewed, faqdesk_answer_short) values (:faqdesk_id, :language_id, :faqdesk_question, :faqdesk_answer_long, :faqdesk_extra_url, :faqdesk_image_text, :faqdesk_image_text_two, :faqdesk_image_text_three, :faqdesk_extra_viewed, :faqdesk_answer_short)", [':faqdesk_id' => $dup_faqdesk_id, ':language_id' => $description['language_id'], ':faqdesk_question' => $description['faqdesk_question'], ':faqdesk_answer_long' => $description['faqdesk_answer_long'], ':faqdesk_extra_url' => $description['faqdesk_extra_url'], ':faqdesk_image_text' => $description['faqdesk_image_text'], ':faqdesk_image_text_two' => $description['faqdesk_image_text_two'], ':faqdesk_image_text_three' => $description['faqdesk_image_text_three'], ':faqdesk_extra_viewed' => $description['faqdesk_extra_viewed'], ':faqdesk_answer_short' => $description['faqdesk_answer_short']]);
					}

					prepared_query::execute("insert into faqdesk_to_categories (faqdesk_id, categories_id) values (:faqdesk_id, :categories_id)", [':faqdesk_id' => $dup_faqdesk_id, ':categories_id' => $categories_id]);
					$faqdesk_id = $dup_faqdesk_id;
				}
			} // top closing if bracket

			CK\fn::redirect_and_exit(tep_href_link(FILENAME_FAQDESK, 'cPath='.$categories_id.'&pID='.$faqdesk_id));
			break;

		// -------------------------------------------------------------------------------------------------------------------------------------------------------------
	} // very top switch closing bracket
} // very top if closing bracket

// -------------------------------------------------------------------------------------------------------------------------------------------------------------
// check if the catalog image directory exists
// -------------------------------------------------------------------------------------------------------------------------------------------------------------
if (is_dir(DIR_FS_CATALOG_IMAGES)) {
	if (!is_writeable(DIR_FS_CATALOG_IMAGES)) $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_NOT_WRITEABLE, 'error');
}
else {
	$messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_DOES_NOT_EXIST, 'error');
}

// -------------------------------------------------------------------------------------------------------------------------------------------------------------
// end of the case scenrio code
// -------------------------------------------------------------------------------------------------------------------------------------------------------------
//
// html head / body / left column code area
//
// -------------------------------------------------------------------------------------------------------------------------------------------------------------
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/menu.js"></script>
	<script language="javascript" src="includes/general.js"></script>
	<script language="JavaScript" src="includes/modules/faqdesk/html_editor/jsfunc.js" type="text/javascript"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onload="SetFocus();">
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
			<td width="100%" valign="top">
				<table border="0" width="100%" cellspacing="0" cellpadding="2">
					<?php
					// -------------------------------------------------------------------------------------------------------------------------------------------------------------
					// start of main body-text table
					//
					// Also in here you'll find the new_product wood work
					// -------------------------------------------------------------------------------------------------------------------------------------------------------------
					if ($action == 'new_product') {
						if ( ($_GET['pID']) && (!$_POST) ) {
							$product = prepared_query::fetch("select pd.faqdesk_question, pd.faqdesk_answer_long, pd.faqdesk_answer_short, pd.faqdesk_extra_url, pd.faqdesk_image_text, pd.faqdesk_image_text_two, pd.faqdesk_image_text_three, p.faqdesk_id, p.faqdesk_image, p.faqdesk_image_two, p.faqdesk_image_three, p.faqdesk_date_added, p.faqdesk_last_modified, date_format(p.faqdesk_date_available, '%Y-%m-%d') as faqdesk_date_available, p.faqdesk_status, p.faqdesk_sticky from faqdesk p, faqdesk_description pd where p.faqdesk_id = :faqdesk_id and p.faqdesk_id = pd.faqdesk_id and pd.language_id = 1", cardinality::ROW, [':faqdesk_id' => $_GET['pID']]);

							$pInfo = (object)$product;
						}
						elseif (!empty($_POST)) {
							$pInfo = (object)$_POST;
							$faqdesk_question = $_POST['faqdesk_question'];
							$faqdesk_answer_long = $_POST['faqdesk_answer_long'];
							$faqdesk_answer_short = $_POST['faqdesk_answer_short'];
							$faqdesk_extra_url = $_POST['faqdesk_extra_url'];
							$faqdesk_image_text = $_POST['faqdesk_image_text'];
							$faqdesk_image_text_two = $_POST['faqdesk_image_text_two'];
							$faqdesk_image_text_three = $_POST['faqdesk_image_text_three'];
							$faqdesk_image = $_POST['faqdesk_image'];
							$faqdesk_image_two = $_POST['faqdesk_image_two'];
							$faqdesk_image_three = $_POST['faqdesk_image_three'];
						}
						else {
							$pInfo = new stdClass;
						}

						switch ($pInfo->faqdesk_status) {
							case '0': $in_status = false; $out_status = true; break;
							case '1':
							default: $in_status = true; $out_status = false;
						}

						switch ($pInfo->faqdesk_sticky) {
							case '0': $sticky_on = false; $sticky_off = true; break;
							case '1': $sticky_on = true; $sticky_off = false; break;
							default: $sticky_on = false; $sticky_off = true;
						}

						// -------------------------------------------------------------------------------------------------------------------------------------------------------------
						?>
					<link rel="stylesheet" type="text/css" href="includes/javascript/spiffyCal/spiffyCal_v2_1.css">
					<script language="JavaScript" src="includes/javascript/spiffyCal/spiffyCal_v2_1.js"></script>
					<script language="javascript">
						var dateAvailable = new ctlSpiffyCalendarBox("dateAvailable", "new_product", "faqdesk_date_available","btnDate1","<?php echo $pInfo->faqdesk_date_available; ?>",scBTNMODE_CUSTOMBLUE);
					</script>
					<tr>
						<td>
							<?php echo tep_draw_form('new_product', FILENAME_FAQDESK, 'cPath='.$cPath.'&pID='.$_GET['pID'].'&action=new_product_preview', 'post', 'enctype="multipart/form-data"'); ?>
								<table border="0" width="100%" cellspacing="3" cellpadding="3">
									<tr>
										<td class="pageHeading"><?php echo sprintf(TEXT_NEW_FAQDESK, faqdesk_output_generated_category_path($current_category_id)); ?></td>
										<td class="pageHeading" align="right"> <?php echo tep_draw_hidden_field('faqdesk_date_added', (($pInfo->faqdesk_date_added) ? $pInfo->faqdesk_date_added : date('Y-m-d'))).tep_draw_hidden_field('faqdesk_date_available', (($pInfo->faqdesk_date_available) ? $pInfo->faqdesk_date_available : date('Y-m-d'))).tep_image_submit('button_preview.gif', IMAGE_PREVIEW).'&nbsp;&nbsp;<a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&pID='.$_GET['pID']).'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>'; ?> </td>
									</tr>
								</table>
								<table border="0" width="100%" cellspacing="0" cellpadding="0">
									<tr>
										<td class="main" width="50%" valign="top">
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent"><?php echo TEXT_FAQDESK_QUESTION; ?></td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr>
													<td class="main"> <?php echo tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English'); ?> </td>
													<td class="main"> <?php echo tep_draw_input_field('faqdesk_question[1]', (!empty($faqdesk_question[1]) ? stripslashes($faqdesk_question[1]) : faqdesk_get_faqdesk_question($pInfo->faqdesk_id, 1)), 'size="50"'); ?> </td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent"><?php echo TEXT_FAQDESK_ANSWER_SHORT; ?></td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr>
													<td class="main" valign="top"> <?php echo tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English'); ?> </td>
													<td class="main"> <?php require(DIR_WS_INCLUDES.'modules/faqdesk/html_editor/summary_bb.php'); ?> </td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent"><?php echo TEXT_FAQDESK_ANSWER_LONG; ?></td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr>
													<td class="main" valign="top"> <?php echo tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English'); ?> </td>
													<td class="main"> <?php require(DIR_WS_INCLUDES.'modules/faqdesk/html_editor/content_bb.php'); ?> </td>
												</tr>
											</table>
										</td>
										<td class="main" width="50%" valign="top">
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent"><?php echo TEXT_FAQDESK_STATUS; ?></td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr>
													<td class="main"> <?php echo tep_draw_radio_field('faqdesk_status', '1', $in_status).'&nbsp;'.TEXT_FAQDESK_AVAILABLE; ?> <?php echo tep_draw_radio_field('faqdesk_status', '0', $out_status).'&nbsp;'.TEXT_FAQDESK_NOT_AVAILABLE; ?> </td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent"><?php echo TEXT_FAQDESK_STICKY; ?></td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr>
													<td class="main"> <?php echo tep_draw_radio_field('faqdesk_sticky', '1', $sticky_on).'&nbsp;'.TEXT_FAQDESK_STICKY_ON; ?> <?php echo tep_draw_radio_field('faqdesk_sticky', '0', $sticky_off).'&nbsp;'.TEXT_FAQDESK_STICKY_OFF; ?> </td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent">Date formatted as:&nbsp;&nbsp;<small>(YYYY-MM-DD)</small></td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr>
													<td class="main" width="25%"><?php echo TEXT_FAQDESK_START_DATE; ?></td>
													<td class="main"> <script language="javascript">dateAvailable.writeControl(); dateAvailable.dateFormat="yyyy-MM-dd";</script> </td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent"><?php echo TEXT_FAQDESK_URL.'&nbsp;&nbsp;<small>'.TEXT_FAQDESK_URL_WITHOUT_HTTP.'</small>'; ?></td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr>
													<td class="main"> <?php echo tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English'); ?> </td>
													<td class="main"> <?php echo tep_draw_input_field('faqdesk_extra_url[1]', (!empty($faqdesk_extra_url[1]) ? stripslashes($faqdesk_extra_url[1]) : faqdesk_get_faqdesk_extra_url($pInfo->faqdesk_id, 1)), 'size="45"'); ?> </td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent"><?php echo TEXT_FAQDESK_IMAGE; ?></td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr>
													<td> <?php echo tep_draw_separator('pixel_trans.gif', '100%', '5'); ?> </td>
												</tr>
												<tr class="main">
													<td class="" colspan="2"><?php echo TEXT_FAQDESK_IMAGE_ONE; ?></td>
												</tr>
												<tr>
													<td> <?php echo tep_draw_separator('pixel_trans.gif', '100%', '5'); ?> </td>
												</tr>
												<tr>
													<td class="main"> <?php echo faqdesk_draw_file_field('faqdesk_image', 'size="40"'); ?> </td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="main">
													<td class="main" colspan="2"><?php echo TEXT_FAQDESK_IMAGE_SUBTITLE; ?></td>
												</tr>
												<tr>
													<td class="main"> <?php echo tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English'); ?> </td>
													<td class="main"> <?php echo tep_draw_input_field('faqdesk_image_text[1]', (!empty($faqdesk_image_text[1]) ?stripslashes($faqdesk_image_text[1]) : faqdesk_get_faqdesk_image_text($pInfo->faqdesk_id, 1)), 'size="50"'); ?> </td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="0" cellpadding="0">
												<tr>
													<td class="main">
														<?php echo tep_draw_hidden_field('products_previous_image', $pInfo->faqdesk_image);
														echo tep_image(DIR_WS_CATALOG_IMAGES.$pInfo->faqdesk_image); ?>
													</td>
												</tr>
												<tr>
													<td class="main"> <?php echo $pInfo->faqdesk_image; ?> </td>
												</tr>
												<tr>
													<td> <?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?> </td>
												</tr>
												<tr>
													<td class="headerBar"> <?php echo tep_draw_separator('pixel_trans.gif', '100%', '1'); ?> </td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr>
													<td> <?php echo tep_draw_separator('pixel_trans.gif', '100%', '5'); ?> </td>
												</tr>
												<tr class="main">
													<td class="main" colspan="2"><?php echo TEXT_FAQDESK_IMAGE_TWO; ?></td>
												</tr>
												<tr>
													<td> <?php echo tep_draw_separator('pixel_trans.gif', '100%', '5'); ?> </td>
												</tr>
												<tr>
													<td class="main"> <?php echo faqdesk_draw_file_field('faqdesk_image_two', 'size="40"'); ?> </td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="main">
													<td class="main" colspan="2"><?php echo TEXT_FAQDESK_IMAGE_SUBTITLE_TWO; ?></td>
												</tr>
												<tr>
													<td class="main"> <?php echo tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English'); ?> </td>
													<td class="main"> <?php echo tep_draw_input_field('faqdesk_image_text_two[1]', (!empty($faqdesk_image_text_two[1]) ? stripslashes($faqdesk_image_text_two[1]) : faqdesk_get_faqdesk_image_text_two($pInfo->faqdesk_id, 1)), 'size="50"'); ?> </td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="0" cellpadding="0">
												<tr>
													<td class="main"> <?php echo tep_draw_hidden_field('products_previous_image_two', $pInfo->faqdesk_image_two); echo tep_image(DIR_WS_CATALOG_IMAGES.$pInfo->faqdesk_image_two); ?> </td>
												</tr>
												<tr>
													<td class="main"> <?php echo $pInfo->faqdesk_image_two; ?> </td>
												</tr>
												<tr>
													<td> <?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?> </td>
												</tr>
												<tr>
													<td class="headerBar"> <?php echo tep_draw_separator('pixel_trans.gif', '100%', '1'); ?> </td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr>
													<td> <?php echo tep_draw_separator('pixel_trans.gif', '100%', '5'); ?> </td>
												</tr>
												<tr class="main">
													<td class="main" colspan="2"><?php echo TEXT_FAQDESK_IMAGE_THREE; ?></td>
												</tr>
												<tr>
													<td> <?php echo tep_draw_separator('pixel_trans.gif', '100%', '5'); ?> </td>
												</tr>
												<tr>
													<td class="main"> <?php echo faqdesk_draw_file_field('faqdesk_image_three', 'size="40"'); ?> </td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="main">
													<td class="main" colspan="2"><?php echo TEXT_FAQDESK_IMAGE_SUBTITLE_THREE; ?></td>
												</tr>
												<tr>
													<td class="main"> <?php echo tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English'); ?> </td>
													<td class="main"> <?php echo tep_draw_input_field('faqdesk_image_text_three[1]', (!empty($faqdesk_image_text_three[1]) ? stripslashes($faqdesk_image_text_three[1]) : faqdesk_get_faqdesk_image_text_three($pInfo->faqdesk_id, 1)), 'size="50"'); ?> </td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="0" cellpadding="0">
												<tr>
													<td class="main"> <?php echo tep_draw_hidden_field('products_previous_image_three', $pInfo->faqdesk_image_three); echo tep_image(DIR_WS_CATALOG_IMAGES.$pInfo->faqdesk_image_three); ?> </td>
												</tr>
												<tr>
													<td class="main"> <?php echo $pInfo->faqdesk_image_three; ?> </td>
												</tr>
												<tr>
													<td> <?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?> </td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
								<br>
								<table border="0" width="100%" cellspacing="3" cellpadding="3">
									<tr>
										<td class="main" align="right"> <?php echo tep_draw_hidden_field('faqdesk_date_added', (($pInfo->faqdesk_date_added) ? $pInfo->faqdesk_date_added : date('Y-m-d'))).tep_image_submit('button_preview.gif', IMAGE_PREVIEW).'&nbsp;&nbsp;<a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&pID='.$_GET['pID']).'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>'; ?> </td>
									</tr>
								</table>
							</form>
						</td>
					</tr>
					<?php }
					elseif ($action == 'new_product_preview') {
						if (!empty($_POST)) {
							$pInfo = (object)$_POST;
							$faqdesk_question = !empty($_POST['faqdesk_question'])?$_POST['faqdesk_question']:NULL;
							$faqdesk_answer_long = !empty($_POST['faqdesk_answer_long'])?$_POST['faqdesk_answer_long']:NULL;
							$faqdesk_answer_short = !empty($_POST['faqdesk_answer_short'])?$_POST['faqdesk_answer_short']:NULL;

							$faqdesk_answer_short[1] = !empty($_POST['faqdesk_answer_short_1'])?nl2br($_POST['faqdesk_answer_short_1']):NULL;
							$faqdesk_answer_short[2] = !empty($_POST['faqdesk_answer_short_2'])?nl2br($_POST['faqdesk_answer_short_2']):NULL;
							$faqdesk_answer_short[3] = !empty($_POST['faqdesk_answer_short_3'])?nl2br($_POST['faqdesk_answer_short_3']):NULL;
							$faqdesk_answer_short[4] = !empty($_POST['faqdesk_answer_short_4'])?nl2br($_POST['faqdesk_answer_short_4']):NULL;

							$faqdesk_answer_long[1] = !empty($_POST['faqdesk_answer_long_1'])?nl2br($_POST['faqdesk_answer_long_1']):NULL;
							$faqdesk_answer_long[2] = !empty($_POST['faqdesk_answer_long_2'])?nl2br($_POST['faqdesk_answer_long_2']):NULL;
							$faqdesk_answer_long[3] = !empty($_POST['faqdesk_answer_long_3'])?nl2br($_POST['faqdesk_answer_long_3']):NULL;
							$faqdesk_answer_long[4] = !empty($_POST['faqdesk_answer_long_4'])?nl2br($_POST['faqdesk_answer_long_4']):NULL;

							$faqdesk_extra_url = !empty($_POST['faqdesk_extra_url'])?$_POST['faqdesk_extra_url']:NULL;
							$faqdesk_image_text = !empty($_POST['faqdesk_image_text'])?$_POST['faqdesk_image_text']:NULL;
							$faqdesk_image_text_two = !empty($_POST['faqdesk_image_text_two'])?$_POST['faqdesk_image_text_two']:NULL;
							$faqdesk_image_text_three = !empty($_POST['faqdesk_image_text_three'])?$_POST['faqdesk_image_text_three']:NULL;

							// copy image only if modified
							$faqdesk_image = tep_get_uploaded_file('faqdesk_image');
							$faqdesk_image_two = tep_get_uploaded_file('faqdesk_image_two');
							$faqdesk_image_three = tep_get_uploaded_file('faqdesk_image_three');
							$image_directory = tep_get_local_path(DIR_FS_CATALOG_IMAGES);

							// BEGIN code by Peter
							if ( ($faqdesk_image != 'none') && ($faqdesk_image != '') ) {
								$faqdesk_image = tep_get_uploaded_file('faqdesk_image');
								$image_directory = tep_get_local_path(DIR_FS_CATALOG_IMAGES);
							}
							if ( ($faqdesk_image_two != 'none') && ($faqdesk_image_two != '') ) {
								$faqdesk_image_two = tep_get_uploaded_file('faqdesk_image_two');
								$image_directory = tep_get_local_path(DIR_FS_CATALOG_IMAGES);
							}
							if ( ($faqdesk_image_three != 'none') && ($faqdesk_image_three != '') ) {
								$faqdesk_image_three = tep_get_uploaded_file('faqdesk_image_three');
								$image_directory = tep_get_local_path(DIR_FS_CATALOG_IMAGES);
							}

							if (is_uploaded_file($faqdesk_image['tmp_name'])) {
								tep_copy_uploaded_file($faqdesk_image, $image_directory);
								$faqdesk_image_name = $faqdesk_image['name'];
							}
							else {
								$faqdesk_image_name = $_POST['products_previous_image'];
							}

							if (is_uploaded_file($faqdesk_image_two['tmp_name'])) {
								tep_copy_uploaded_file($faqdesk_image_two, $image_directory);
								$faqdesk_image_name_two = $faqdesk_image_two['name'];
							}
							else {
								$faqdesk_image_name_two = $_POST['products_previous_image_two'];
							}

							if (is_uploaded_file($faqdesk_image_three['tmp_name'])) {
								tep_copy_uploaded_file($faqdesk_image_three, $image_directory);
								$faqdesk_image_name_three = $faqdesk_image_three['name'];
							}
							else {
								$faqdesk_image_name_three = $_POST['products_previous_image_three'];
							}
							// END of Peter's changes
						}
						else {
							$product = prepared_query::fetch("select p.faqdesk_id, pd.language_id, pd.faqdesk_question, pd.faqdesk_answer_long, pd.faqdesk_answer_short, pd.faqdesk_extra_url, pd.faqdesk_image_text, pd.faqdesk_image_text_two, pd.faqdesk_image_text_three, p.faqdesk_image, p.faqdesk_image_two, p.faqdesk_image_three, p.faqdesk_date_added, p.faqdesk_last_modified, p.faqdesk_date_available, p.faqdesk_status, p.faqdesk_sticky from faqdesk p, faqdesk_description pd where p.faqdesk_id = pd.faqdesk_id and p.faqdesk_id = :faqdesk_id", cardinality::ROW, [':faqdesk_id' => $_GET['pID']]);

							$pInfo = (object)$product;
							$faqdesk_image_name = $pInfo->faqdesk_image;
							$faqdesk_image_name_two = $pInfo->faqdesk_image_two;
							$faqdesk_image_name_three = $pInfo->faqdesk_image_three;
						}

						$form_action = ($_GET['pID']) ? 'update_product' : 'insert_product';

						if (!empty($_GET['read']) && $_GET['read'] == 'only') {
							$pInfo->faqdesk_question = faqdesk_get_faqdesk_question($pInfo->faqdesk_id, 1);
							$pInfo->faqdesk_answer_long = faqdesk_get_faqdesk_answer_long($pInfo->faqdesk_id, 1);
							$pInfo->faqdesk_answer_short = faqdesk_get_faqdesk_answer_short($pInfo->faqdesk_id, 1);
							$pInfo->faqdesk_extra_url = faqdesk_get_faqdesk_extra_url($pInfo->faqdesk_id, 1);
							$pInfo->faqdesk_image_text = faqdesk_get_faqdesk_image_text($pInfo->faqdesk_id, 1);
							$pInfo->faqdesk_image_text_two = faqdesk_get_faqdesk_image_text_two($pInfo->faqdesk_id, 1);
							$pInfo->faqdesk_image_text_three = faqdesk_get_faqdesk_image_text_three($pInfo->faqdesk_id, 1);
						}
						else {
							$pInfo->faqdesk_question = $faqdesk_question[1];
							$pInfo->faqdesk_answer_long = $faqdesk_answer_long[1];
							$pInfo->faqdesk_answer_short = $faqdesk_answer_short[1];
							$pInfo->faqdesk_extra_url = $faqdesk_extra_url[1];
							$pInfo->faqdesk_image_text = $faqdesk_image_text[1];
							$pInfo->faqdesk_image_text_two = $faqdesk_image_text_two[1];
							$pInfo->faqdesk_image_text_three = $faqdesk_image_text_three[1];
						} ?>
					<?= tep_draw_form($form_action, FILENAME_FAQDESK, 'cPath='.$cPath.'&pID='.$_GET['pID'].'&action='.$form_action, 'post', 'enctype="multipart/form-data"'); ?>
						<tr>
							<td>
								<table border="0" width="100%" cellspacing="3" cellpadding="3">
									<tr>
										<td colspan="2">
											<table border="0" width="100%" cellspacing="0" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent" width="5%"> <?php echo tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English'); ?> </td>
													<td class="headerBarContent"><?php echo $pInfo->faqdesk_question; ?></td>
												</tr>
											</table>
										</td>
									</tr>
									<tr>
										<td width="50%" valign="top">
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent"><?php echo TEXT_FAQDESK_ANSWER_SHORT; ?></td>
												</tr>
												<tr>
													<td class="main"> <?php echo $pInfo->faqdesk_answer_short; ?> </td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent"><?php echo TEXT_FAQDESK_ANSWER_LONG; ?></td>
												</tr>
												<tr>
													<td class="main"> <?php echo $pInfo->faqdesk_answer_long; ?> </td>
												</tr>
											</table>
										</td>
										<td width="50%" valign="top">
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent"><?php echo TEXT_FAQDESK_IMAGE_PREVIEW_ONE; ?></td>
												</tr>
												<tr>
													<td class="main"> <?php echo (($faqdesk_image_name) ? tep_image(DIR_WS_CATALOG_IMAGES.$faqdesk_image_name, $pInfo->faqdesk_question, '', '', 'align="right" hspace="5" vspace="5"') : '') .''; ?> </td>
												</tr>
												<tr>
													<td><?php echo $pInfo->faqdesk_image_text; ?></td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent"><?php echo TEXT_FAQDESK_IMAGE_PREVIEW_TWO; ?></td>
												</tr>
												<tr>
													<td class="main"> <?php echo (($faqdesk_image_name_two) ? tep_image(DIR_WS_CATALOG_IMAGES.$faqdesk_image_name_two, $pInfo->faqdesk_question, '', '', 'align="right" hspace="5" vspace="5"') : '') .''; ?> </td>
												</tr>
												<tr>
													<td><?php echo $pInfo->faqdesk_image_text_two; ?></td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent"><?php echo TEXT_FAQDESK_IMAGE_PREVIEW_THREE; ?></td>
												</tr>
												<tr>
													<td class="main"> <?php echo (($faqdesk_image_name_three) ? tep_image(DIR_WS_CATALOG_IMAGES.$faqdesk_image_name_three, $pInfo->faqdesk_question, '', '', 'align="right" hspace="5" vspace="5"') : '') .''; ?> </td>
												</tr>
												<tr>
													<td><?php echo $pInfo->faqdesk_image_text_three; ?></td>
												</tr>
											</table>
											<?php if ($pInfo->faqdesk_extra_url) { ?>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent"><?php echo TEXT_FAQDESK_ADDED_LINK_HEADER; ?></td>
												</tr>
												<tr>
													<td class="main"> <?php echo sprintf(TEXT_FAQDESK_ADDED_LINK, $pInfo->faqdesk_extra_url); ?> </td>
												</tr>
											</table>
											<?php } ?>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent"><?php echo TEXT_FAQDESK_DATE_ADDED; ?></td>
												</tr>
												<tr>
													<td class="main">
														<?php $date_added = new DateTime($pInfo->faqdesk_date_added);
														echo $date_added->format('l d F, Y'); ?>
													</td>
												</tr>
											</table>
											<table border="0" width="100%" cellspacing="3" cellpadding="3">
												<tr class="headerBar">
													<td class="headerBarContent"><?php echo TEXT_FAQDESK_DATE_AVAILABLE; ?></td>
												</tr>
												<tr>
													<td class="main">
														<?php $date_available = new DateTime($pInfo->faqdesk_date_available);
														echo $date_available->format('l d F, Y'); ?>
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<?php if (!empty($_GET['read']) && $_GET['read'] == 'only') {
							if (!empty($_GET['origin'])) {
								$pos_params = strpos($_GET['origin'], '?', 0);
								if ($pos_params != false) {
									$back_url = substr($_GET['origin'], 0, $pos_params);
									$back_url_params = substr($_GET['origin'], $pos_params + 1);
								}
								else {
									$back_url = $_GET['origin'];
									$back_url_params = '';
								}
							}
							else {
								$back_url = FILENAME_FAQDESK;
								$back_url_params = 'cPath='.$cPath.'&pID='.$pInfo->faqdesk_id;
							} ?>
						<tr>
							<td align="right"> <?php echo '<a href="'.tep_href_link($back_url, $back_url_params, 'NONSSL').'">'.tep_image_button('button_back.gif', IMAGE_BACK).'</a>'; ?> </td>
						</tr>
						<?php }
						else { ?>
						<tr>
							<td align="right" class="smallText">
								<?php
								// -------------------------------------------------------------------------------------------------------------------------------------------------------------
								// Re-Post all POST'ed variables
								// main table area that shows the catagories, the left box, and the counts at the bottom of the catagory area
								// -------------------------------------------------------------------------------------------------------------------------------------------------------------
								foreach ($_POST as $key => $value) {
									if (!is_array($_POST[$key])) {
										echo tep_draw_hidden_field($key, htmlspecialchars(stripslashes($value)));
									}
								}

								echo tep_draw_hidden_field('faqdesk_question[1]', htmlspecialchars($faqdesk_question[1]));
								echo tep_draw_hidden_field('faqdesk_answer_long[1]', htmlspecialchars($faqdesk_answer_long[1]));
								echo tep_draw_hidden_field('faqdesk_answer_short[1]', htmlspecialchars($faqdesk_answer_short[1]));
								echo tep_draw_hidden_field('faqdesk_extra_url[1]', htmlspecialchars($faqdesk_extra_url[1]));
								echo tep_draw_hidden_field('faqdesk_image_text[1]', htmlspecialchars($faqdesk_image_text[1]));
								echo tep_draw_hidden_field('faqdesk_image_text_two[1]', htmlspecialchars($faqdesk_image_text_two[1]));
								echo tep_draw_hidden_field('faqdesk_image_text_three[1]', htmlspecialchars($faqdesk_image_text_three[1]));

								echo tep_draw_hidden_field('faqdesk_image', $faqdesk_image_name);
								echo tep_draw_hidden_field('faqdesk_image_two', $faqdesk_image_name_two);
								echo tep_draw_hidden_field('faqdesk_image_three', $faqdesk_image_name_three);

								echo tep_image_submit('button_back.gif', IMAGE_BACK, 'name="edit"').'&nbsp;&nbsp;';

								if (!empty($_GET['pID'])) {
									echo tep_image_submit('button_update.gif', IMAGE_UPDATE);
								}
								else {
									echo tep_image_submit('button_insert.gif', IMAGE_INSERT);
								}

								echo '&nbsp;&nbsp;<a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&pID='.$_GET['pID']).'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>'; ?>
							</td>
						</tr>
						<?php } ?>
					</form>
					<?php }
					else { ?>
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
									<td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
									<td align="right">
										<table border="0" width="100%" cellspacing="0" cellpadding="0">
											<tr>
												<?php echo tep_draw_form('search', FILENAME_FAQDESK, '', 'get'); ?>
													<td class="smallText" align="right"><?php echo HEADING_TITLE_SEARCH.' '.tep_draw_input_field('search', @$_GET['search']); ?></td>
												</form>
											</tr>
											<tr>
												<?php echo tep_draw_form('goto', FILENAME_FAQDESK, '', 'get'); ?>
													<td class="smallText" align="right"> <?php echo HEADING_TITLE_GOTO.' '.tep_draw_pull_down_menu('cPath', faqdesk_get_category_tree(), $current_category_id, 'onChange="this.form.submit();"'); ?> </td>
												</form>
											</tr>
										</table>
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
												<td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CATEGORIES_FAQDESK; ?></td>
												<td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_STATUS; ?></td>
												<td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_STICKY; ?></td>
												<td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
											</tr>
											<?php
											$categories_count = 0;
											$rows = 0;

											if (!empty($_GET['search'])) {
												$categories_list = prepared_query::fetch("select c.categories_id, cd.categories_name, cd.categories_description, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, c.catagory_status from faqdesk_categories c, faqdesk_categories_description cd where c.categories_id = cd.categories_id and cd.language_id = 1 and cd.categories_name like :category_name order by c.sort_order, cd.categories_name", cardinality::SET, [':category_name' => '%'.$_GET['search'].'%']);
											}
											else {
												$categories_list = prepared_query::fetch("select c.categories_id, cd.categories_name, cd.categories_description, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, c.catagory_status from faqdesk_categories c, faqdesk_categories_description cd where c.parent_id = :parent_id and c.categories_id = cd.categories_id and cd.language_id = 1 order by c.sort_order, cd.categories_name", cardinality::SET, [':parent_id' => $current_category_id]);
											}

											foreach ($categories_list as $categories) {
												$categories_count++;
												$rows++;
												if (!empty($_GET['search'])) $cPath= $categories['parent_id'];

												if ( (empty($_GET['categories_id']) && empty($_GET['pID']) || (@$_GET['categories_id'] == $categories['categories_id'])) && empty($cInfo) && (substr($action, 0, 4) != 'new_') ) {
													$category_childs = array('childs_count' => faqdesk_childs_in_category_count($categories['categories_id']));
													$category_products = array('products_count' => faqdesk_products_in_category_count($categories['categories_id']));

													$cInfo_array = array_merge($categories, $category_childs, $category_products);
													$cInfo = (object)$cInfo_array;
												}

												if ( (!empty($cInfo)) && ($categories['categories_id'] == $cInfo->categories_id) ) {
													echo '<tr class="dataTableRowSelected" onmouseover="this.style.cursor=\'hand\'" onclick="document.location.href=\''.tep_href_link(FILENAME_FAQDESK, faqdesk_get_path($categories['categories_id'])).'\'">'."\n";
												}
												else {
													echo '<tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\';this.style.cursor=\'hand\'" onmouseout="this.className=\'dataTableRow\'" onclick="document.location.href=\''.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&categories_id='.$categories['categories_id']).'\'">'."\n";
												} ?>
												<td class="dataTableContent">
													<?php echo '<a href="'.tep_href_link(FILENAME_FAQDESK, faqdesk_get_path($categories['categories_id'])).'">'. tep_image(DIR_WS_ICONS.'folder.gif', ICON_FOLDER).'</a>&nbsp;<b>'.$categories['categories_name'].'</b>';?>
												</td>
												<td class="dataTableContent" align="center">
													<?php if ($categories['catagory_status'] == '1') {
														echo tep_image(DIR_WS_IMAGES.'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10).'&nbsp;&nbsp;<a href="'. tep_href_link(FILENAME_FAQDESK, 'action=setflag&flag=0&categories_id='.$categories['categories_id'].'&cPath='.$cPath).'">'. tep_image(DIR_WS_IMAGES.'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10).'</a>';
													}
													else {
														echo '<a href="'.tep_href_link(FILENAME_FAQDESK, 'action=setflag&flag=1&categories_id='.$categories['categories_id']. '&cPath='.$cPath).'">'.tep_image(DIR_WS_IMAGES.'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10). '</a>&nbsp;&nbsp;'.tep_image(DIR_WS_IMAGES.'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
													} ?>
												</td>
												<td class="dataTableContent" align="right">&nbsp;</td>
												<td class="dataTableContent" align="right">
													<?php if ( (!empty($cInfo)) && ($categories['categories_id'] == $cInfo->categories_id) ) {
														echo tep_image(DIR_WS_IMAGES.'icon_arrow_right.gif', '');
													}
													else {
														echo '<a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&categories_id='.$categories['categories_id']).'">'.tep_image(DIR_WS_IMAGES.'icon_info.gif', IMAGE_ICON_INFO).'</a>';
													} ?>&nbsp;
												</td>
											</tr>
											<?php }

											$products_count = 0;
											if (!empty($_GET['search'])) {
												$products_list = prepared_query::fetch("select p.faqdesk_id, pd.faqdesk_question, p.faqdesk_image, p.faqdesk_image_two, p.faqdesk_image_three, p.faqdesk_date_added, p.faqdesk_last_modified, p.faqdesk_date_available, p.faqdesk_status, p.faqdesk_sticky, p2c.categories_id from faqdesk p, faqdesk_description pd, faqdesk_to_categories p2c where p.faqdesk_id = pd.faqdesk_id and pd.language_id = 1 and p.faqdesk_id = p2c.faqdesk_id and pd.faqdesk_question like :question order by pd.faqdesk_question", cardinality::SET, [':question' => '%'.$_GET['search'].'%']);
											}
											else {
												$products_list = prepared_query::fetch("select p.faqdesk_id, pd.faqdesk_question, p.faqdesk_image, p.faqdesk_image_two, p.faqdesk_image_three, p.faqdesk_date_added, p.faqdesk_last_modified, p.faqdesk_date_available, p.faqdesk_status, p.faqdesk_sticky from faqdesk p, faqdesk_description pd, faqdesk_to_categories p2c where p.faqdesk_id = pd.faqdesk_id and pd.language_id = 1 and p.faqdesk_id = p2c.faqdesk_id and p2c.categories_id = :categories_id order by pd.faqdesk_question", cardinality::SET, [':categories_id' => $current_category_id]);
											}

											foreach ($products_list as $products) {
												$products_count++;
												$rows++;
												if (!empty($_GET['search'])) $cPath=$products['categories_id'];
												if ( (empty($_GET['pID']) && empty($_GET['categories_id']) || (@$_GET['pID'] == $products['faqdesk_id'])) && empty($pInfo) && empty($cInfo) && (substr($action, 0, 4) != 'new_') ) {
													$reviews = prepared_query::fetch("select (avg(reviews_rating) / 5 * 100) as average_rating from reviews where products_id = :products_id", cardinality::ROW, [':products_id' => $products['faqdesk_id']]);
													$pInfo_array = array_merge($products, $reviews);
													$pInfo = (object)$pInfo_array;
												}

												if ( !empty($pInfo) && ($products['faqdesk_id'] == $pInfo->faqdesk_id) ) {
													echo '<tr class="dataTableRowSelected" onmouseover="this.style.cursor=\'hand\'" onclick="document.location.href=\''.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&pID='.$products['faqdesk_id'].'&action=new_product_preview&read=only').'\'">'."\n";
												}
												else {
													echo '<tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\';this.style.cursor=\'hand\'" onmouseout="this.className=\'dataTableRow\'" onclick="document.location.href=\''.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&pID='.$products['faqdesk_id']).'\'">'."\n";
												} ?>
												<td class="dataTableContent">
													<?php echo '<a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&pID='.$products['faqdesk_id'].'&action=new_product_preview&read=only').'">'.tep_image(DIR_WS_ICONS.'preview.gif', ICON_PREVIEW).'</a>&nbsp;'.$products['faqdesk_question']; ?>
												</td>
												<td class="dataTableContent" align="center">
													<?php if ($products['faqdesk_status'] == '1') {
														echo tep_image(DIR_WS_IMAGES.'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10).'&nbsp;&nbsp;<a href="'.tep_href_link(FILENAME_FAQDESK, 'action=setflag&flag=0&pID='.$products['faqdesk_id'].'&cPath='.$cPath).'">'.tep_image(DIR_WS_IMAGES.'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10).'</a>';
													}
													else {
														echo '<a href="'.tep_href_link(FILENAME_FAQDESK, 'action=setflag&flag=1&pID='.$products['faqdesk_id'].'&cPath='.$cPath).'">'.tep_image(DIR_WS_IMAGES.'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10).'</a>&nbsp;&nbsp;'.tep_image(DIR_WS_IMAGES.'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
													} ?>
												</td>
												<td class="dataTableContent" align="center">
													<?php if ($products['faqdesk_sticky'] == '1') {
														echo tep_image(DIR_WS_IMAGES.'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10).'&nbsp;&nbsp;<a href="'.tep_href_link(FILENAME_FAQDESK, 'action=setflag_sticky&flag_sticky=0&pID='.$products['faqdesk_id'].'&cPath='.$cPath).'">'.tep_image(DIR_WS_IMAGES.'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10).'</a>';
													}
													else {
														echo '<a href="'.tep_href_link(FILENAME_FAQDESK, 'action=setflag_sticky&flag_sticky=1&pID='.$products['faqdesk_id'].'&cPath='.$cPath).'">'.tep_image(DIR_WS_IMAGES.'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10).'</a>&nbsp;&nbsp;'.tep_image(DIR_WS_IMAGES.'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
													} ?>
												</td>
												<td class="dataTableContent" align="right">
													<?php if ( !empty($pInfo) && ($products['faqdesk_id'] == $pInfo->faqdesk_id) ) {
														echo tep_image(DIR_WS_IMAGES.'icon_arrow_right.gif', '');
													}
													else {
														echo '<a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&pID='.$products['faqdesk_id']).'">'.tep_image(DIR_WS_IMAGES.'icon_info.gif', IMAGE_ICON_INFO).'</a>';
													} ?>&nbsp;
												</td>
											</tr>
											<?php }

											if (!empty($cPath_array)) {
												$cPath_back = '';
												for ($i = 0, $n = sizeof($cPath_array) - 1; $i < $n; $i++) {
													if ($cPath_back == '') {
														$cPath_back .= $cPath_array[$i];
													}
													else {
														$cPath_back .= '_'.$cPath_array[$i];
													}
												}
											}

											$cPath_back = !empty($cPath_back) ? 'cPath='.$cPath_back : ''; ?>
											<tr>
												<td colspan="3">
													<table border="0" width="100%" cellspacing="0" cellpadding="2">
														<tr>
															<td class="smallText"><?php echo TEXT_CATEGORIES.'&nbsp;'.$categories_count.'<br>'.TEXT_FAQDESK.'&nbsp;'.$products_count; ?></td>
															<td align="right" class="smallText">
																<?php if ($cPath) echo '<a href="'.tep_href_link(FILENAME_FAQDESK, $cPath_back.'&categories_id='.$current_category_id).'">' . tep_image_button('button_back.gif', IMAGE_BACK).'</a>&nbsp;'; if (empty($_GET['search'])) echo '<a href="' . tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&action=new_category').'">'.tep_image_button('button_new_category.gif', IMAGE_NEW_CATEGORY).'</a>'; ?>

																<?php if ((isset($cPath)) && ($cPath != '')) { ?>
																<?php echo '&nbsp;<a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&action=new_product').'">'.tep_image_button('button_new_faq.gif', IMAGE_NEW_STORY).'</a>'; ?>
																<?php } ?>
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
									</td>
									<?php
									// -------------------------------------------------------------------------------------------------------------------------------------------------------------
									// types of actions and the text based informatioin declaration area
									// -------------------------------------------------------------------------------------------------------------------------------------------------------------
									$heading = array();
									$contents = array();
									switch ($action) {
										// -------------------------------------------------------------------------------------------------------------------------------------------------------------
										case 'new_category':
											// -------------------------------------------------------------------------------------------------------------------------------------------------------------
											$heading[] = array('text' => '<b>'.TEXT_INFO_HEADING_NEW_CATEGORY.'</b>');

											$contents = array('form' => tep_draw_form('newcategory', FILENAME_FAQDESK, 'action=insert_category&cPath='.$cPath, 'post', 'enctype="multipart/form-data"'));
											$contents[] = array('text' => TEXT_NEW_CATEGORY_INTRO);

											$category_inputs_string = '';
											$category_inputs_string .= '<br>'.tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English').'&nbsp;'.tep_draw_input_field('categories_name[1]');

											$categories_description_inputs_string = '';
											$categories_description_inputs_string .= '<br>'.tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English').'&nbsp;'.tep_draw_input_field('categories_description[1]');

											$contents[] = array('text' => '<br>'.TEXT_CATEGORIES_NAME.$category_inputs_string);
											$contents[] = array('text' => '<br>'.TEXT_CATEGORIES_DESCRIPTION_NAME.$categories_description_inputs_string);
											$contents[] = array('text' => '<br>'.TEXT_CATEGORIES_IMAGE.'<br>'.tep_draw_file_field('categories_image'));
											$contents[] = array('text' => '<br>'.TEXT_SORT_ORDER.'<br>'.tep_draw_input_field('sort_order', '', 'size="2"'));
											//$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_save.gif', IMAGE_SAVE).' <a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath).'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');
											$contents[] = array('text' => '<br>'.TEXT_SHOW_STATUS.'<br>'.tep_draw_input_field('catagory_status', $cInfo->catagory_status, 'size="2"').'1=Enabled 0=Disabled');
											$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_save.gif', IMAGE_SAVE).' <a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&categories_id='.$cInfo->categories_id).'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');
											break;

										// -------------------------------------------------------------------------------------------------------------------------------------------------------------
										case 'edit_category':
											// -------------------------------------------------------------------------------------------------------------------------------------------------------------
											$heading[] = array('text' => '<b>'.TEXT_INFO_HEADING_EDIT_CATEGORY.'</b>');
											$contents = array(
												'form' => tep_draw_form('categories', FILENAME_FAQDESK, 'action=update_category&cPath='.$cPath, 'post', 'enctype="multipart/form-data"').tep_draw_hidden_field('categories_id', $cInfo->categories_id)
											);
											$contents[] = array('text' => TEXT_EDIT_INTRO);

											$category_inputs_string = '';
											$category_inputs_string .= '<br>'.tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English').'&nbsp;'.tep_draw_input_field('categories_name[1]', faqdesk_get_category_name($cInfo->categories_id, 1));

											$categories_description_inputs_string = '';
											$categories_description_inputs_string .= '<br>'.tep_image(DIR_WS_CATALOG_LANGUAGES.'/english/images/icon.gif', 'English').'&nbsp;'.tep_draw_input_field('categories_description[1]', faqdesk_get_category_description($cInfo->categories_id, 1));

											$contents[] = array('text' => '<br>'.TEXT_EDIT_CATEGORIES_NAME.$category_inputs_string);
											$contents[] = array('text' => '<br>'.TEXT_EDIT_CATEGORIES_DESCRIPTION.$categories_description_inputs_string);
											$contents[] = array(
												'text' => '<br>'.tep_image(DIR_WS_CATALOG_IMAGES.$cInfo->categories_image, $cInfo->categories_name).'<br>'.DIR_WS_CATALOG_IMAGES.'<br><b>'.$cInfo->categories_image.'</b>'
											);
											$contents[] = array('text' => '<br>'.TEXT_EDIT_CATEGORIES_IMAGE.'<br>'.tep_draw_file_field('categories_image'));
											$contents[] = array('text' => '<br>'.TEXT_EDIT_SORT_ORDER.'<br>'.tep_draw_input_field('sort_order', $cInfo->sort_order, 'size="2"'));
											/*
											$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_save.gif', IMAGE_SAVE).' <a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&categories_id='.$cInfo->categories_id).'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');
											*/
											$contents[] = array('text' => '<br>'.TEXT_SHOW_STATUS.'<br>'.tep_draw_input_field('catagory_status', $cInfo->catagory_status, 'size="2"').'1=Enabled 0=Disabled');
											$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_save.gif', IMAGE_SAVE).' <a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&categories_id='.$cInfo->categories_id).'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');

											break;

										// -------------------------------------------------------------------------------------------------------------------------------------------------------------
										case 'delete_category':
											// -------------------------------------------------------------------------------------------------------------------------------------------------------------
											$heading[] = array('text' => '<b>'.TEXT_INFO_HEADING_DELETE_CATEGORY.'</b>');

											$contents = array('form' => tep_draw_form('categories', FILENAME_FAQDESK, 'action=delete_category_confirm&cPath='.$cPath).tep_draw_hidden_field('categories_id', $cInfo->categories_id));
											$contents[] = array('text' => TEXT_DELETE_CATEGORY_INTRO);
											$contents[] = array('text' => '<br><b>'.$cInfo->categories_name.'</b>');
											if ($cInfo->childs_count > 0) $contents[] = array('text' => '<br>'.sprintf(TEXT_DELETE_WARNING_CHILDS, $cInfo->childs_count));
											if ($cInfo->products_count > 0) $contents[] = array('text' => '<br>'.sprintf(TEXT_DELETE_WARNING_FAQDESK, $cInfo->products_count));
											$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_delete.gif', IMAGE_DELETE).' <a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&categories_id='.$cInfo->categories_id).'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');
											break;

										// -------------------------------------------------------------------------------------------------------------------------------------------------------------
										case 'move_category':
											// -------------------------------------------------------------------------------------------------------------------------------------------------------------
											$heading[] = array('text' => '<b>'.TEXT_INFO_HEADING_MOVE_CATEGORY.'</b>');

											$contents = array('form' => tep_draw_form('categories', FILENAME_FAQDESK, 'action=move_category_confirm').tep_draw_hidden_field('categories_id', $cInfo->categories_id));
											$contents[] = array('text' => sprintf(TEXT_MOVE_CATEGORIES_INTRO, $cInfo->categories_name));
											$contents[] = array('text' => '<br>'.sprintf(TEXT_MOVE, $cInfo->categories_name).'<br>'.tep_draw_pull_down_menu('move_to_category_id', faqdesk_get_category_tree('0', '', $cInfo->categories_id), $current_category_id));
											$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_move.gif', IMAGE_MOVE).' <a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&categories_id='.$cInfo->categories_id).'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');
											break;

										// -------------------------------------------------------------------------------------------------------------------------------------------------------------
										case 'delete_product':
											// -------------------------------------------------------------------------------------------------------------------------------------------------------------
											$heading[] = array('text' => '<b>'.TEXT_INFO_HEADING_DELETE_NEWS.'</b>');

											$contents = array('form' => tep_draw_form('products', FILENAME_FAQDESK, 'action=delete_product_confirm&cPath='.$cPath).tep_draw_hidden_field('faqdesk_id', $pInfo->faqdesk_id));
											$contents[] = array('text' => TEXT_DELETE_PRODUCT_INTRO);
											$contents[] = array('text' => '<br><b>'.$pInfo->faqdesk_question.'</b>');

											$product_categories_string = '';
											$product_categories = faqdesk_generate_category_path($pInfo->faqdesk_id, 'product');
											for ($i = 0, $n = sizeof($product_categories); $i < $n; $i++) {
												$category_path = '';
												for ($j = 0, $k = sizeof($product_categories[$i]); $j < $k; $j++) {
													$category_path .= $product_categories[$i][$j]['text'].'&nbsp;&gt;&nbsp;';
												}
												$category_path = substr($category_path, 0, -16);
												$product_categories_string .= tep_draw_checkbox_field('product_categories[]', $product_categories[$i][sizeof($product_categories[$i])-1]['id'], true).'&nbsp;'.$category_path.'<br>';
											}

											$product_categories_string = substr($product_categories_string, 0, -4);

											$contents[] = array('text' => '<br>'.$product_categories_string);

											$contents[] = array('text' => '<br>'.TEXT_DELETE_IMAGE_INTRO);

											$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_delete.gif', IMAGE_DELETE).' <a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&pID='.$pInfo->faqdesk_id).'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');
											break;

										// -------------------------------------------------------------------------------------------------------------------------------------------------------------
										case 'move_product':
											// -------------------------------------------------------------------------------------------------------------------------------------------------------------
											$heading[] = array('text' => '<b>'.TEXT_INFO_HEADING_MOVE_PRODUCT.'</b>');

											$contents = array('form' => tep_draw_form('products', FILENAME_FAQDESK, 'action=move_product_confirm&cPath='.$cPath).tep_draw_hidden_field('faqdesk_id', $pInfo->faqdesk_id));
											$contents[] = array('text' => sprintf(TEXT_MOVE_FAQDESK_INTRO, $pInfo->faqdesk_question));
											$contents[] = array('text' => '<br>'.TEXT_INFO_CURRENT_CATEGORIES.'<br><b>'.faqdesk_output_generated_category_path($pInfo->faqdesk_id, 'product').'</b>');
											$contents[] = array('text' => '<br>'.sprintf(TEXT_MOVE, $pInfo->faqdesk_question).'<br>'.tep_draw_pull_down_menu('move_to_category_id', faqdesk_get_category_tree(), $current_category_id));
											$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_move.gif', IMAGE_MOVE).' <a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&pID='.$pInfo->faqdesk_id).'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');
											break;

										// -------------------------------------------------------------------------------------------------------------------------------------------------------------
										case 'copy_to':
											// -------------------------------------------------------------------------------------------------------------------------------------------------------------
											$heading[] = array('text' => '<b>'.TEXT_INFO_HEADING_COPY_TO.'</b>');

											$contents = array('form' => tep_draw_form('copy_to', FILENAME_FAQDESK, 'action=copy_to_confirm&cPath='.$cPath) .tep_draw_hidden_field('faqdesk_id', $pInfo->faqdesk_id));
											$contents[] = array('text' => TEXT_INFO_COPY_TO_INTRO);
											$contents[] = array('text' => '<br>'.TEXT_INFO_CURRENT_CATEGORIES.'<br><b>'.faqdesk_output_generated_category_path($pInfo->faqdesk_id, 'product').'</b>');
											$contents[] = array('text' => '<br>'.TEXT_CATEGORIES.'<br>'.tep_draw_pull_down_menu('categories_id', faqdesk_get_category_tree(), $current_category_id));
											$contents[] = array('text' => '<br>'.TEXT_HOW_TO_COPY.'<br>'.tep_draw_radio_field('copy_as', 'link', true).' '.TEXT_COPY_AS_LINK.'<br>'.tep_draw_radio_field('copy_as', 'duplicate').' '.TEXT_COPY_AS_DUPLICATE);
											$contents[] = array('align' => 'center', 'text' => '<br>'.tep_image_submit('button_copy.gif', IMAGE_COPY).' <a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&pID='.$pInfo->faqdesk_id).'">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>');
											break;

										// -------------------------------------------------------------------------------------------------------------------------------------------------------------
										default:
											// -------------------------------------------------------------------------------------------------------------------------------------------------------------
											// right box that runs the buttons and what not
											// -------------------------------------------------------------------------------------------------------------------------------------------------------------
											if ($rows > 0) {
												if (!empty($cInfo)) { // category info box contents
													$heading[] = array('text' => '<b>'.$cInfo->categories_name.'</b>');

													$contents[] = array(
														'align' => 'center',
														'text' => '<a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&categories_id='.$cInfo->categories_id.'&action=edit_category').'">' . tep_image_button('button_edit.gif', IMAGE_EDIT).'</a> <a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&categories_id=' . $cInfo->categories_id.'&action=delete_category').'">'.tep_image_button('button_delete.gif', IMAGE_DELETE).'</a> <a href="' . tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&categories_id='.$cInfo->categories_id.'&action=move_category').'">' . tep_image_button('button_move.gif', IMAGE_MOVE).'</a>'
													);

													$date_added = new DateTime($cInfo->date_added);
													$contents[] = array('text' => '<br>'.TEXT_DATE_ADDED.' '.$date_added->format('m/d/Y'));

													$last_modified = new DateTime($cInfo->last_modified);

													if (tep_not_null($cInfo->last_modified)) $contents[] = array('text' => TEXT_LAST_MODIFIED.' '.$last_modified->format('m/d/Y'));
													$contents[] = array('text' => '<br>'.tep_info_image($cInfo->categories_image, $cInfo->categories_name, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT).'<br>'.$cInfo->categories_image);
													$contents[] = array('text' => '<br>'.TEXT_SUBCATEGORIES.' '.$cInfo->childs_count.'<br>'.TEXT_FAQDESK.' '.$cInfo->products_count);
												}
												elseif (is_object($pInfo)) { // news info box contents
													$heading[] = array('text' => '<b>'.faqdesk_get_faqdesk_question($pInfo->faqdesk_id, $_SESSION['languages_id']).'</b>');
													$contents[] = array(
														'align' => 'center',
														'text' => '<a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&pID='.$pInfo->faqdesk_id.'&action=new_product') .'">'.tep_image_button('button_edit.gif', IMAGE_EDIT).'</a> <a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&pID=' .$pInfo->faqdesk_id.'&action=delete_product').'">'.tep_image_button('button_delete.gif', IMAGE_DELETE).'</a> <a href="' .tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&pID='.$pInfo->faqdesk_id.'&action=move_product').'">' .tep_image_button('button_move.gif', IMAGE_MOVE).'</a> <a href="'.tep_href_link(FILENAME_FAQDESK, 'cPath='.$cPath.'&pID=' .$pInfo->faqdesk_id.'&action=copy_to').'">'.tep_image_button('button_copy_to.gif', IMAGE_COPY_TO).'</a>'
													);
													$date_added = new DateTime($pInfo->faqdesk_date_added);
													$last_modified = new DateTime($pInfo->faqdesk_last_modified);
													$date_available = new DateTime($pInfo->faqdesk_date_available);

													$contents[] = array('text' => '<br>'.TEXT_DATE_ADDED.' '.$date_added->format('m/d/Y'));
													if (tep_not_null($pInfo->faqdesk_last_modified)) $contents[] = array('text' => TEXT_LAST_MODIFIED.' '.$last_modified->format('m/d/Y'));
													if (date('Y-m-d') < $pInfo->faqdesk_date_available) $contents[] = array('text' => TEXT_DATE_AVAILABLE.' '.$date_available->format('m/d/Y'));
													$contents[] = array(
														'text' => '<br>'.tep_info_image($pInfo->faqdesk_image, $pInfo->faqdesk_question, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT).'<br>'.$pInfo->faqdesk_image .'<br>'.tep_info_image($pInfo->faqdesk_image_two, $pInfo->faqdesk_question, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT).'<br>'.$pInfo->faqdesk_image_two .'<br>'.tep_info_image($pInfo->faqdesk_image_three, $pInfo->faqdesk_question, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT).'<br>'.$pInfo->faqdesk_image_three
													);
													$contents[] = array('text' => '<br>'.TEXT_FAQDESK_AVERAGE_RATING.' '.number_format($pInfo->average_rating, 2).'%');
												}
											}
											else { // create category/news info
												$heading[] = array('text' => '<b>'.EMPTY_CATEGORY.'</b>');
												$contents[] = array('text' => sprintf(TEXT_NO_CHILD_CATEGORIES_OR_story, $parent_categories_name));
											}

											break;
									}

									if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
										echo '<td width="25%" valign="top">'."\n";
										$box = new box;
										echo $box->infoBox($heading, $contents);
										echo '</td>'."\n";
									} ?>
								</tr>
							</table>
						</td>
					</tr>
					<?php } ?>
				</table>
			</td>
		 </tr>
	</table>
</body>
</html>
