<?php
// eventually, we'll kill application top and move all of those functions into the front controller or this file
require('includes/application_top.php');

$front_controller = new ck_front_controller(ck_front_controller::CONTEXT_ADMIN);

$view = $front_controller->run();

if (!empty($view) && $view->is_permitted()) {
	$view->process_response();

	if ($view->response_context_is(ck_view::CONTEXT_HTTP)) {
		// we need to move this into the view - but since we're still doing some necessary work in the boilerplate file, we'll do it this way for the moment
		//---------header-----------------
		$cktpl = new ck_template('includes/templates', ck_template::BACKEND);
		$content_map = new ck_content();
		$meta_title = $view->get_meta_title();
		require('includes/matrix-boilerplate.php');
		$cktpl->open($content_map);
		ck_bug_reporter::render();
		//---------end header-------------
	}

	//---------body-------------------
	$view->respond();
	//---------end body---------------

	if ($view->response_context_is(ck_view::CONTEXT_HTTP)) {
		//---------footer-----------------
		$cktpl->close($content_map);
		//---------end footer-------------
	}
}
elseif(!empty($view) && !$view->is_permitted()) {
	http_response_code(403);
	// we need to move this into the view - but since we're still doing some necessary work in the boilerplate file, we'll do it this way for the moment
	//---------header-----------------
	$cktpl = new ck_template('includes/templates', ck_template::BACKEND);
	$content_map = new ck_content();
	require('includes/matrix-boilerplate.php');
	$cktpl->open($content_map);
	ck_bug_reporter::render();
	//---------end header-------------

	$cktpl->content('includes/templates/page-forbidden.mustache.html', []);

	//---------footer-----------------
	$cktpl->close($content_map);
	//---------end footer-------------
}
else {
	http_response_code(404);
	if ($__FLAG['ajax']) echo json_encode(['success' => 0, 'error' => 'Unknown request']);
	else {
		// we need to move this into the view - but since we're still doing some necessary work in the boilerplate file, we'll do it this way for the moment
		//---------header-----------------
		$cktpl = new ck_template('includes/templates', ck_template::BACKEND);
		$content_map = new ck_content();
		require('includes/matrix-boilerplate.php');
		$cktpl->open($content_map);
		ck_bug_reporter::render();
		//---------end header-------------

		$cktpl->content('includes/templates/page-404.mustache.html', []);

		//---------footer-----------------
		$cktpl->close($content_map);
		//---------end footer-------------
	}
}
?>
