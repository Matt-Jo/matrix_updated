<?php
// we're repurposing this file - to start with, it's only going to be a controller for ajax calls,
// then we'll add in regular calls that get redirected at the end, and finally it'll become a full front controller

$ajax = empty($_REQUEST['ajax'])||$_REQUEST['ajax']!=1?FALSE:TRUE;
$model = !empty($_REQUEST['model'])?$_REQUEST['model']:NULL;
$action = !empty($_REQUEST['action'])?$_REQUEST['action']:NULL;

// for now, ajax only
if (!$ajax || !$model || !$action) {
	header('Location: orders_new.php?status=2&selected_box=orders');
	exit();
}

echo $action; exit;

$response = $model::process_request($action, $_REQUEST, $ajax);

echo json_encode($response);

exit();
?>
