<?php
function upload_data($data) {
	foreach ($data as $idx => $item) {
		if ($idx === 0) continue; // skip the header row

		$import = [
			':categories_id' => $item[0],
			':google_category_id' => $item[1]
		];

		prepared_query::execute('UPDATE categories SET google_category_id = :google_category_id WHERE categories_id = :categories_id', $import);
	}

	return ['output' => ['Upload Complete'], 'errors' => []];
}
?>