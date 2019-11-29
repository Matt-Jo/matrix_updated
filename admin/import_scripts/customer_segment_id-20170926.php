<?php
function upload_data() {
	$output = [];

	$seg = prepared_query::fetch('SELECT * FROM customer_segments');
	$segments = [];
	foreach ($seg as $segment) {
		$segments[$segment['segment_code']] = $segment['customer_segment_id'];
	}

	prepared_query::execute('UPDATE customers SET customer_segment_id = :segment WHERE customer_segment_id IS NULL AND customer_group = :cb AND customer_business_unit_id IN (:cb, 0)', [':segment' => $segments['EU'], ':cb' => 1]);
	prepared_query::execute('UPDATE customers SET customer_segment_id = :segment WHERE customer_segment_id IS NULL AND customer_group = :cb AND customer_business_unit_id IN (:cb, 0)', [':segment' => $segments['RS'], ':cb' => 2]);
	prepared_query::execute('UPDATE customers SET customer_segment_id = :segment WHERE customer_segment_id IS NULL AND customer_group = :cb AND customer_business_unit_id IN (:cb, 0)', [':segment' => $segments['IN'], ':cb' => 3]);
	prepared_query::execute('UPDATE customers SET customer_segment_id = :segment WHERE customer_segment_id IS NULL AND customer_group = :cb AND customer_business_unit_id IN (:cb, 0)', [':segment' => $segments['BD'], ':cb' => 4]);
	prepared_query::execute('UPDATE customers SET customer_segment_id = :segment WHERE customer_segment_id IS NULL AND customer_group = :cb AND customer_business_unit_id IN (:cb, 0)', [':segment' => $segments['MP'], ':cb' => 5]);

	prepared_query::execute('UPDATE customers SET customer_segment_id = :segment WHERE customer_segment_id IS NULL AND customer_business_unit_id = :cb AND customer_group IN (:cb, 0)', [':segment' => $segments['EU'], ':cb' => 1]);
	prepared_query::execute('UPDATE customers SET customer_segment_id = :segment WHERE customer_segment_id IS NULL AND customer_business_unit_id = :cb AND customer_group IN (:cb, 0)', [':segment' => $segments['RS'], ':cb' => 2]);
	prepared_query::execute('UPDATE customers SET customer_segment_id = :segment WHERE customer_segment_id IS NULL AND customer_business_unit_id = :cb AND customer_group IN (:cb, 0)', [':segment' => $segments['IN'], ':cb' => 3]);
	prepared_query::execute('UPDATE customers SET customer_segment_id = :segment WHERE customer_segment_id IS NULL AND customer_business_unit_id = :cb AND customer_group IN (:cb, 0)', [':segment' => $segments['BD'], ':cb' => 4]);
	prepared_query::execute('UPDATE customers SET customer_segment_id = :segment WHERE customer_segment_id IS NULL AND customer_business_unit_id = :cb AND customer_group IN (:cb, 0)', [':segment' => $segments['MP'], ':cb' => 5]);

	prepared_query::execute('UPDATE customers SET customer_segment_id = :segment WHERE customer_segment_id IS NULL AND customers_business_type_id IN (3, 4, 5, 6, 7, 8, 9)', [':segment' => $segments['EU']]);
	prepared_query::execute('UPDATE customers SET customer_segment_id = :segment WHERE customer_segment_id IS NULL AND customers_business_type_id IN (1, 2)', [':segment' => $segments['RS']]);

	prepared_query::execute('UPDATE customers SET customer_segment_id = :segment WHERE customer_segment_id IS NULL AND account_type = 1', [':segment' => $segments['IN']]);

	prepared_query::execute('UPDATE customers c JOIN orders o ON c.customers_id = o.customers_id AND o.channel = :channel SET c.customer_segment_id = :segment WHERE c.customer_segment_id IS NULL', [':channel' => 'amazon', ':segment' => $segments['MP']]);
	prepared_query::execute('UPDATE customers c JOIN orders o ON c.customers_id = o.customers_id AND o.channel = :channel SET c.customer_segment_id = :segment WHERE c.customer_segment_id IS NULL', [':channel' => 'ebay', ':segment' => $segments['MP']]);
	prepared_query::execute('UPDATE customers c JOIN orders o ON c.customers_id = o.customers_id AND o.channel = :channel SET c.customer_segment_id = :segment WHERE c.customer_segment_id IS NULL', [':channel' => 'newegg', ':segment' => $segments['MP']]);
	prepared_query::execute('UPDATE customers c JOIN orders o ON c.customers_id = o.customers_id AND o.channel = :channel SET c.customer_segment_id = :segment WHERE c.customer_segment_id IS NULL', [':channel' => 'newegg_business', ':segment' => $segments['MP']]);
	prepared_query::execute('UPDATE customers c JOIN orders o ON c.customers_id = o.customers_id AND o.channel = :channel SET c.customer_segment_id = :segment WHERE c.customer_segment_id IS NULL', [':channel' => 'walmart', ':segment' => $segments['MP']]);

	return ['output' => $output, 'errors' => []];
}
?>