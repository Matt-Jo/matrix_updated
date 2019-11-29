<?php
require('includes/application_top.php');

$page_handler = 'ck_view_admin_shipping_estimator';
// this is a stub because we've not yet moved to a real front controller
if (!empty($page_handler) && class_exists($page_handler)) {
	$view = new $page_handler();

	$view->process_response();

	/*if ($view->response_context_is(ck_view::CONTEXT_HTTP)) {
		// we need to move this into the view - but since we're still doing some necessary work in the boilerplate file, we'll do it this way for the moment
		//---------header-----------------
		$cktpl = new ck_template('includes/templates', ck_template::SLIM);
		$content_map = new ck_content();
		require('includes/matrix-boilerplate.php');
		$cktpl->open($content_map);
		ck_bug_reporter::render();
		//---------end header-------------
	}*/

	//---------body-------------------
	$view->respond();
	//---------end body---------------

	/*if ($view->response_context_is(ck_view::CONTEXT_HTTP)) {
		//---------footer-----------------
		$cktpl->close($content_map);
		//---------end footer-------------
	}*/
} ?>
