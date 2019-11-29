<?php
require('includes/application_top.php');

// large, but not crazy large
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

$import_run = FALSE;
$output = [];
$errors = [];

$upload_status_map = [
	UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
	UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
	UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
	UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
	UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
	UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
	UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
];

function import_csv($file, $delim) {
	$data = [];

	while (($row = fgetcsv($file, 0, $delim)) !== FALSE) {
		$data[] = $row;
	}

	return $data;
}

function import_xlsx($file) {
	$data = [];

	$sheet = $file->getActiveSheet();
	$columns_in_spreadsheet = $sheet->getHighestColumn();
	$rows_in_spreadsheet = $sheet->getHighestRow();

	for ($i=1; $i<=$rows_in_spreadsheet; $i++) {
		$data[] = $sheet->rangeToArray('A'.$i.':'.$columns_in_spreadsheet.$i)[0];
	}

	return $data;
}

if ($ck_keys->import_generic == 1 && !empty($_POST['run_import']) && (!empty($_FILES['upload_file']) || $_POST['filetype'] == 'none')) {
	$import_run = TRUE;
	if ($_POST['filetype'] == 'none') {
		$import_script = pathinfo($_SESSION['generic_import_script']);
		require_once('import_scripts/'.$import_script['filename'].'.php');

		$result = upload_data();

		$output = $result['output'];
		$errors = array_merge($errors, $result['errors']);
	}
	elseif ($_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) {
		$errors[] = 'There was a problem with receiving that uploaded file: '.$upload_status_map[$_FILES['upload_file']['error']];
	}
	else {
		$file_details = pathinfo($_FILES['upload_file']['name']);
		if ($file_details['extension'] == 'csv') {
			if (($file = fopen($_FILES['upload_file']['tmp_name'], 'r')) === FALSE) {
				$errors[] = 'There was a problem opening the uploaded file for reading';
			}
			else {
				if ($_POST['filetype'] == 'csv') $data = import_csv($file, ',');
				elseif ($_POST['filetype'] == 'tab') $data = import_csv($file, "\t");
			}
		}
		/*elseif ($file_details['extension'] == 'xls') {
		}*/
		elseif ($file_details['extension'] == 'xlsx') {
			try {
				$file = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['upload_file']['tmp_name']);
				try {
					$data = import_xlsx($file);
				}
				catch (Exception $e) {
					$errors[] = 'There was a problem parsing the uploaded data from the spreadsheet: '.$e->getMessage();
				}
			}
			catch (Exception $e) {
				$errors[] = 'There was a problem opening the uploaded file for reading: '.$e->getMessage();
			}
		}

		if (!empty($data)) {
			$import_script = pathinfo($_SESSION['generic_import_script']);
			require_once('import_scripts/'.$import_script['filename'].'.php');

			$result = upload_data($data);

			$output = $result['output'];
			$errors = array_merge($errors, $result['errors']);
		}
	}

	unset($_SESSION['generic_import_script']);
	$ck_keys->import_generic = 0;
} ?>
<!doctype html>
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
				<?php if (!empty($import_run)) {
					echo implode('<br>', $output).'<br>';
					if (!empty($errors)) {
						echo '<br>ERRORS:<br>';
						echo implode('<br>', $errors).'<br>';
					}
				}

				if (!empty($_GET['turn_off_import'])) {
					unset($_SESSION['generic_import_script']);
					$ck_keys->import_generic = 0;
				}
				
				if ($ck_keys->import_generic != 1) { ?>
				NO IMPORT READY<br>
					<?php if (!empty($_GET['turn_on_import'])) {
						$_SESSION['generic_import_script'] = $_GET['turn_on_import']; ?>
				IMPORT ENABLED: <?= $_SESSION['generic_import_script']; ?><br>
				<a href="/admin/import_generic.php">RELOAD</a><br>
						<?php $ck_keys->import_generic = 1;
					} ?>
				<form action="/admin/import_generic.php" method="get">
					Enable Import Script: /admin/import_scripts/
					<select name="turn_on_import">
						<?php $files = scandir(__DIR__.'/import_scripts/');
						usort($files, function($a, $b) {
							preg_match('/-([0-9]+).php/', $a, $amatches);
							preg_match('/-([0-9]+).php/', $b, $bmatches);

							if (@$amatches[1] > @$bmatches[1]) return -1;
							elseif (@$amatches[1] < @$bmatches[1]) return 1;
							else return 0;
						});
						foreach ($files as $file) {
							if (in_array($file, ['.', '..'])) continue; ?>
						<option value="<?= $file; ?>"><?= $file; ?></option>
						<?php } ?>
					</select>
					<input type="submit" value="Enable">
				</form>
					
					<?php exit();
				} ?>
				<form enctype="multipart/form-data" action="/admin/import_generic.php" method="post">
					<input type="hidden" name="run_import" value="1">
					<strong><?= @$_SESSION['generic_import_script']; ?></strong><br>
					<strong>Upload</strong>
					<select name="filetype" size="1" title="We'll try to figure it out based on the extension, but this helps us out">
						<option value="none">None</option>
						<option value="csv">CSV - comma</option>
						<option value="tab">CSV - tab</option>
						<option value="xl">Excel</option>
					</select>
					File:
					<input type="file" name="upload_file">
					<input type="submit" value="Upload">
				</form>
			</td>
		</tr>
	</table>
</body>
</html>
