<?php
function upload_data($data) {
	foreach ($data as $idx => $item) {
		if ($idx === 0) continue; // skip the header row

		$import = [':upc' => $item[1], ':products_model' => $item[0]];

		prepared_query::execute('UPDATE products SET amazon_upc = :upc WHERE products_model = :products_model', $import);
	}

	return array('output' => ['Upload Complete'], 'errors' => []);
}
?>