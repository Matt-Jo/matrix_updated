<?php
require_once("includes/application_top.php");

// this is a stub because we've not yet moved to a real front controller
if (!empty($page_handler) && class_exists($page_handler)) {
	$view = new $page_handler();
	$view->process_response();
}

require_once(__DIR__.'/templates/Pixame_v1/main_page.tpl.php');
?>
