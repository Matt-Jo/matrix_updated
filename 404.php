<?php
require('includes/application_top.php');

$request = parse_url($_SERVER['REQUEST_URI']);

if (($target_url = prepared_query::fetch('SELECT target_url FROM ck_pretty_url WHERE pretty_url = ? AND active = 1', cardinality::SINGLE, array(ltrim($request['path'], '/'))))) {
	header('Location: /'.$target_url);
	exit();
}

if (preg_match('#/products_new.php#', $request['path'])) {
	CK\fn::redirect_and_exit('/');
}

$content = '404';

require('templates/Pixame_v1/main_page.tpl.php');
