<?php
require('includes/application_top.php');

ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 600);

$export_run = FALSE;
$output = [];
$errors = [];

if ($ck_keys->export_generic == 1 && !empty($_GET['run_export'])) {
	$export_run = TRUE;

	$export_script = pathinfo($_SESSION['generic_export_script']);
	require_once('export_scripts/'.$export_script['filename'].'.php');

	header('Content-disposition: attachment; filename='.$export_script['filename'].'-export.csv');
	header('Content-Type: text/csv');

	$result = export_data();

	$output = $result['output'];
	$errors = array_merge($errors, $result['errors']);

	unset($_SESSION['generic_export_script']);
	$ck_keys->export_generic = 0;
} ?>
<!doctype html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?= BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top">
				<?php if (!empty($export_run)) {
					echo implode('<br>', $output).'<br>';
					if (!empty($errors)) {
						echo '<br>ERRORS:<br>';
						echo implode('<br>', $errors).'<br>';
					}
				}

				if (!empty($_GET['turn_off_export'])) {
					unset($_SESSION['generic_export_script']);
					$ck_keys->export_generic = 0;
				}
				
				if ($ck_keys->export_generic != 1) { ?>
				NO EXPORT READY<br>
					<?php if (!empty($_GET['turn_on_export'])) {
						$_SESSION['generic_export_script'] = $_GET['turn_on_export']; ?>
				EXPORT ENABLED: <?= $_SESSION['generic_export_script']; ?><br>
				<a href="/admin/export_generic.php">RELOAD</a><br>
						<?php $ck_keys->export_generic = 1;
					} ?>
				<form action="/admin/export_generic.php" method="get">
					Enable Export Script: /admin/export_scripts/
					<select name="turn_on_export">
						<?php $files = scandir(__DIR__.'/export_scripts/');
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
				<form action="/admin/export_generic.php" method="get">
					<input type="hidden" name="run_export" value="1">
					<strong><?= @$_SESSION['generic_export_script']; ?></strong><br>
					<input type="submit" value="Download">
				</form>
			</td>
		</tr>
	</table>
</body>
</html>
