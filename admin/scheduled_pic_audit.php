<?php
require_once(__DIR__.'/../includes/application_top.php');

$cli = false;

if (isset($argv) && count($argv) > 0) {
	$cli = true;
}

$path = dirname(__FILE__);

@ini_set("memory_limit","768M");
set_time_limit(0);

try {
	// this has to be run first so we don't re-catch new errors
	picture_audit::audit_follow_up();

	while (picture_audit::checkpoint_audit()) {
		$piclist = picture_audit::audit_list();

		foreach ($piclist as $picture) {
			if (picture_audit::check_audit_loop($picture['stock_id'])) break 2;

			$audit = new picture_audit($picture['stock_id']);
			if (!$audit->check_filesystem()) { // we only check filesystem, even though naming checks should be trivial since we don't want to flag naming checks for batch checking
				picture_audit::record_audit_result($picture['stock_id'], 1);
			}
			else {
				picture_audit::record_audit_result($picture['stock_id']);
			}
		}
	}
}
catch (Exception $e) {
	echo $e->getMessage();
	// we should make some sort of notification to someone who cares here
}
?>
