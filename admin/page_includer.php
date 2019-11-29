<?php
require('includes/application_top.php');

$action = !empty($_REQUEST['action'])?$_REQUEST['action']:NULL;
switch ($action) {
	case 'manage_pages':
		$data = !empty($_POST['data'])?$_POST['data']:[];
		$delete = !empty($_POST['delete'])?$_POST['delete']:[];

		foreach ($data as $page_includer_id => $fields) {
			if ($page_includer_id == 'new') {
				if (empty($fields['label']) || empty($fields['target']) || empty($fields['page_height'])) continue;
				$create = [
					'header' => [
						'label' => $fields['label'],
						'target' => $fields['target'],
						'page_height' => (int)$fields['page_height'],
					],
					'requests' => []
				];

				foreach ($fields['request']['new'] as $request) {
					$create['requests'][] = $request;
				}

				$page_includer = ck_page_includer::create($create);
			}
			else {
				$page_includer = new ck_page_includer($page_includer_id);
				$update = [];

				if ($page_includer->get_header('label') != $fields['label']) $update['label'] = $fields['label'];
				if ($page_includer->get_header('target') != $fields['target']) $update['target'] = $fields['target'];
				if ($page_includer->get_header('page_height') != (int)$fields['page_height']) $update['page_height'] = (int)$fields['page_height'];

				$page_includer->update($update);

				foreach ($fields['request'] as $page_includer_request_map_id => $request) {
					if ($page_includer_request_map_id == 'new') {
						foreach ($request as $req) {
							$page_includer->add_map($req);
						}
					}
					else {
						if ($page_includer->get_request_maps($page_includer_request_map_id) != $request) {
							$page_includer->remove_map($page_includer_request_map_id, NULL);
							$page_includer->add_map($request);
						}
					}
				}
			}
		}

		foreach ($delete as $page_includer_id => $requests) {
			$page_includer = new ck_page_includer($page_includer_id);
			foreach ($requests['request'] as $page_includer_request_map_id => $nothing) {
				$page_includer->remove_map($page_includer_request_map_id, NULL);
			}
		}

		CK\fn::redirect_and_exit('/admin/page_includer.php');
		break;
	default:
		break;
}

//---------header-----------------
$cktpl = new ck_template('includes/templates', ck_template::BACKEND);
$content_map = new ck_content();
require('includes/matrix-boilerplate.php');
$cktpl->open($content_map);
ck_bug_reporter::render();
//---------end header-------------

//---------body-------------------
$content_map = new ck_content();

if (!empty($errors)) {
	$content_map->{'has_errors?'} = 1;
	$content_map->errors = $errors;
}

$page_includers = ck_page_includer::get_all();
$content_map->pages = [];
foreach ($page_includers as $page_includer) {
	$pi = $page_includer->get_header();
	$pi['requests'] = [];
	foreach ($page_includer->get_request_maps() as $request) {
		$pi['requests'][] = [
			'page_includer_request_map_id' => $request['page_includer_request_map_id'],
			'request' => $request['request']
		];
	}
	$content_map->pages[] = $pi;
}

$cktpl->content('includes/templates/page-page_includer.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>
