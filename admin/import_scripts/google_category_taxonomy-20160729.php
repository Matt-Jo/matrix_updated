<?php
function upload_data($data) {
	foreach ($data as $idx => $item) {
		if ($idx === 0) continue; // skip the header row

		$import = [
			':google_category_id' => $item[0],
			':category_1' => $item[1],
			':category_2' => $item[2],
			':category_3' => $item[3],
			':category_4' => $item[4],
			':category_5' => $item[5],
			':category_6' => $item[6],
			':category_7' => $item[7]
		];

		prepared_query::execute('INSERT INTO google_categories (google_category_id, category_1, category_2, category_3, category_4, category_5, category_6, category_7) VALUES (:google_category_id, :category_1, :category_2, :category_3, :category_4, :category_5, :category_6, :category_7)', $import);
	}

	return array('output' => ['Upload Complete'], 'errors' => []);
}
?>