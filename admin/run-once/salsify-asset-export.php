<?php
require_once(__DIR__.'/../../includes/application_top.php');

$cli = PHP_SAPI==='cli'?TRUE:FALSE;

@ini_set("memory_limit","2048M");
set_time_limit(0);

debug_tools::show_all();

debug_tools::mark('START');

$context = $_GET['context']??'row';

try {
	$path = [
		'feeds' => realpath(__DIR__.'/../../feeds'),
		'archive' => realpath(__DIR__.'/../../images/archive')
	];

	$products = prepared_query::fetch('SELECT p.salsify_id, NULLIF(psci.image_lrg, :npg) as image_lrg, psci.image_xl_1, psci.image_xl_2, psci.image_xl_3, psci.image_xl_4, psci.image_xl_5, psci.image_xl_6 FROM products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN products_stock_control_images psci ON IFNULL(psc.image_reference, psc.stock_id) = psci.stock_id WHERE p.salsify_id IS NOT NULL', cardinality::SET, [':npg' => 'newproduct.gif']);

	$exp = fopen($path['feeds'].'/salsify-assets-'.$context.'.txt', 'w');

	if ($context == 'row') fwrite($exp, implode("\t", ['salsify_unique_id', 'image_slot_a', 'image_slot_b', 'image_slot_c', 'image_slot_d', 'image_slot_e', 'image_slot_f', 'image_slot_g'])."\n");
	elseif ($context == 'column') fwrite($exp, implode("\t", ['salsify_unique_id', 'image', 'image_slot'])."\n");

	$total_products = count($products);

	$skipped = [];

	foreach ($products as $idx => $product) {
		if ($idx < 500 && $idx % 100 == 0 || $idx % 500 == 0) {
			debug_tools::mark('Running # '.($idx + 1).' of '.$total_products.' products.');
		}

		$image = [
			'a' => $product['image_lrg'],
			'b' => $product['image_xl_1'],
			'c' => $product['image_xl_2'],
			'd' => $product['image_xl_3'],
			'e' => $product['image_xl_4'],
			'f' => $product['image_xl_5'],
			'g' => $product['image_xl_6'],
		];

		$found_one = FALSE;

		foreach ($image as $slot => $ref) {
			if (empty($ref)) continue;

			$file = pathinfo($ref);

			if (file_exists($path['archive'].'/'.$file['basename'])) $ref = 'archive/'.$file['basename'];

			$image[$slot] = 'https://media.cablesandkits.com/'.$ref;

			$found_one = TRUE;

			if ($context == 'column') fwrite($exp, implode("\t", [$product['salsify_id'], $image[$slot], $slot])."\n");
		}

		if (!$found_one) {
			$skipped[] = $product['salsify_id'];
			continue;
		}

		if ($context == 'row') fwrite($exp, implode("\t", [$product['salsify_id'], $image['a'], $image['b'], $image['c'], $image['d'], $image['e'], $image['f'], $image['g']])."\n");
	}

	fclose($exp);
}
catch (Exception $e) {
	echo $e->getMessage();
}

debug_tools::clear_sub_timer_context();

debug_tools::mark('Done');
?>