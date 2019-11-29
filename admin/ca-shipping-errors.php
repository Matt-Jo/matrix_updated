<?php
require_once('includes/application_top.php');

$page_handler = 'ck_view_admin_ca_shipping_errors';

if (!empty($page_handler) && class_exists($page_handler)) {
	$view = new $page_handler();
	
	$view->process_response();
	
	if ($view->response_context_is(ck_view::CONTEXT_HTTP)) {
		//----------header-----------------
		$cktpl = new ck_template('includes/templates', ck_template::BACKEND);
		$content_map = new ck_content();
		require('includes/matrix-boilerplate.php');
		$cktpl->open($content_map);
		//-----------end header--------------
	}
	//------------body------------
	$view->respond();
	//------------end body---------
	
	if ($view->response_context_is(ck_view::CONTEXT_HTTP)) {
		//------------footer----------
		$cktpl->close($content_map);
		//-----------end footer-----------
	}
} ?>