<?php
require_once(__DIR__.'/../includes/application_top.php');

$cli = PHP_SAPI==='cli'?TRUE:FALSE;
$path = dirname(__FILE__);

$cli_flag = [];
if ($cli && !empty($argv[1])) {
	for ($i=1; $i<count($argv); $i++) {
		$flag = explode('=', $argv[$i], 2);
		$cli_flag[$flag[0]] = !empty($flag[1])?$flag[1]:TRUE;
	}
}

if ($cli && !empty($cli_flag['--verbose'])) $verbose = TRUE;
elseif (!$cli && $__FLAG['verbose']) $verbose = TRUE;
else $verbose = FALSE;

// http://stackoverflow.com/questions/11267086/php-unlink-all-files-within-a-directory-and-then-deleting-that-directory
function recursiveRemoveDirectory($directory, $rmdir=FALSE, $rmsubdirs=TRUE) {
	foreach(glob("{$directory}/*") as $file) {
		if (is_dir($file)) recursiveRemoveDirectory($file, $rmsubdirs);
		else unlink($file);
	}
	if ($rmdir) rmdir($directory);
}

recursiveRemoveDirectory(__DIR__.'/../includes/templates/cache');

exit();
?>
