<?php
require('includes/application_top.php');

ini_set('display_errors', 1);
require_once('./includes/engine/tools/imagesizer.class.php');

set_time_limit(600);

$edgecast_account_num = '7FCA';
$edgecast_api_token = 'tok:df4a4a8a-3e20-44da-a412-8ab86341ecaf';

$image_slots = array(
	'a' => array(
		'p' => array(
			'sm' => 'products_image',
			'med' => 'products_image_med',
			'lrg' => 'products_image_lrg'
		),
		'ipn' => array(
			'sm' => 'image',
			'med' => 'image_med',
			'lrg' => 'image_lrg'
		)
	),
	'b' => array(
		'p' => array(
			'sm' => 'products_image_sm_1',
			'lrg' => 'products_image_xl_1'
		),
		'ipn' => array(
			'sm' => 'image_sm_1',
			'lrg' => 'image_xl_1'
		)
	),
	'c' => array(
		'p' => array(
			'sm' => 'products_image_sm_2',
			'lrg' => 'products_image_xl_2'
		),
		'ipn' => array(
			'sm' => 'image_sm_2',
			'lrg' => 'image_xl_2'
		)
	),
	'd' => array(
		'p' => array(
			'sm' => 'products_image_sm_3',
			'lrg' => 'products_image_xl_3'
		),
		'ipn' => array(
			'sm' => 'image_sm_3',
			'lrg' => 'image_xl_3'
		)
	),
	'e' => array(
		'p' => array(
			'sm' => 'products_image_sm_4',
			'lrg' => 'products_image_xl_4'
		),
		'ipn' => array(
			'sm' => 'image_sm_4',
			'lrg' => 'image_xl_4'
		)
	),
	'f' => array(
		'p' => array(
			'sm' => 'products_image_sm_5',
			'lrg' => 'products_image_xl_5'
		),
		'ipn' => array(
			'sm' => 'image_sm_5',
			'lrg' => 'image_xl_5'
		)
	),
	'g' => array(
		'p' => array(
			'sm' => 'products_image_sm_6',
			'lrg' => 'products_image_xl_6'
		),
		'ipn' => array(
			'sm' => 'image_sm_6',
			'lrg' => 'image_xl_6'
		)
	)
);

$response = array('success' => array(), 'error' => array());
$notices = array();

if (!empty($_REQUEST['images'])) { // we're passed an array of image names, found in the folder
	foreach ($_REQUEST['images'] as $image) {
		preg_match('/^(.+)([a-g])\.(jpg|JPG|png|PNG|gif|GIF)$/', $image, $matches);
		if (empty($matches)) { $response['error'][] = $image; continue; } // we don't recognize the format

		$ipn = $matches[1];
		$slot = $matches[2];
		$ext = strtolower($matches[3]);

		if (!($stock_id = prepared_query::fetch('SELECT stock_id FROM products_stock_control WHERE stock_name LIKE ?', cardinality::SINGLE, array($ipn)))) {
			if (preg_match('/\\$/', $ipn)) {
				$search_ipn = preg_replace('/\\$/', '/', $ipn);
				$stock_id = prepared_query::fetch('SELECT stock_id FROM products_stock_control WHERE stock_name LIKE ?', cardinality::SINGLE, array($search_ipn));
			}
			if (empty($stock_id)) { $response['error'][] = $image; continue; } // we couldn't find the IPN in the database
		}

		// log current images, so we can handle them after we've overwritten the database
		$deprecated_ipn = prepared_query::fetch('SELECT '.implode(', ', array_values($image_slots[$slot]['ipn'])).' FROM products_stock_control_images WHERE stock_id = ?', cardinality::ROW, array($stock_id));
		$deprecated_prods = prepared_query::fetch('SELECT products_id, '.implode(', ', array_values($image_slots[$slot]['p'])).' FROM products WHERE stock_id = ?', cardinality::SET, array($stock_id));

		foreach ($image_slots[$slot]['ipn'] as $size => $field) {
			if (empty($deprecated_ipn[$field])) continue;
			if (preg_match('/newproduct/', $deprecated_ipn[$field])) continue;
			if (preg_match('/deprecated/', $deprecated_ipn[$field])) continue;
			/*if ($size == 'lrg') {
				$fileinfo = pathinfo($deprecated_ipn[$field]);
				if (file_exists(__DIR__.'/images/'.$fileinfo['dirname'].'/'.$fileinfo['filename'].'_300.'.$fileinfo['extension']))
					@rename(__DIR__.'/images/'.$fileinfo['dirname'].'/'.$fileinfo['filename'].'_300.'.$fileinfo['extension'], __DIR__.'/images/deprecated/'.$fileinfo['dirname'].'/'.$fileinfo['filename'].'_300.'.$fileinfo['extension']);
			}*/
			if (file_exists(__DIR__.'/images/'.$deprecated_ipn[$field])) {
				@rename(__DIR__.'/images/'.$deprecated_ipn[$field], __DIR__.'/images/deprecated/'.$deprecated_ipn[$field]);
				prepared_query::execute('UPDATE products_stock_control_images SET '.$field.' = ? WHERE '.$field.' = ?', array('deprecated/'.$deprecated_ipn[$field], $deprecated_ipn[$field]));
			}
		}

		foreach ($image_slots[$slot]['p'] as $size => $field) {
			foreach ($deprecated_prods as $deprecated_prod) {
				if (empty($deprecated_prod[$field])) continue;
				if (preg_match('/newproduct/', $deprecated_prod[$field])) continue;
				if (preg_match('/deprecated/', $deprecated_prod[$field])) continue;
				/*if ($size == 'lrg') {
					$fileinfo = pathinfo($deprecated_prod[$field]);
					if (file_exists(__DIR__.'/images/'.$fileinfo['dirname'].'/'.$fileinfo['filename'].'_300.'.$fileinfo['extension']))
						@rename(__DIR__.'/images/'.$fileinfo['dirname'].'/'.$fileinfo['filename'].'_300.'.$fileinfo['extension'], __DIR__.'/images/deprecated/'.$fileinfo['dirname'].'/'.$fileinfo['filename'].'_300.'.$fileinfo['extension']);
				}*/
				if (file_exists(__DIR__.'/images/'.$deprecated_prod[$field])) {
					@rename(__DIR__.'/images/'.$deprecated_prod[$field], __DIR__.'/images/deprecated/'.$deprecated_prod[$field]); // this should have been handled above
					prepared_query::execute('UPDATE products SET '.$field.' = ? WHERE '.$field.' = ?', array('deprecated/'.$deprecated_prod[$field], $deprecated_prod[$field]));
				}
			}
		}

		//imagesizer::resize(/home/imageuser/staging/'.$image, imagesizer::$map['300'], __DIR__.'/images', 'p/'.$ipn.$slot.'_300.'.$ext, TRUE);
		if ($slot == 'a') {
			imagesizer::resize('/home/imageuser/staging/'.$image, imagesizer::$map['med'], __DIR__.'/images', 'p/'.$ipn.$slot.'_med.'.$ext, TRUE);
			prepared_query::execute('INSERT INTO products_stock_control_images (stock_id, '.$image_slots[$slot]['ipn']['med'].') VALUES (?, ?) ON DUPLICATE KEY UPDATE '.$image_slots[$slot]['ipn']['med'].'=VALUES('.$image_slots[$slot]['ipn']['med'].')', array($stock_id, 'p/'.$ipn.$slot.'_med.'.$ext));
			prepared_query::execute('UPDATE products SET '.$image_slots[$slot]['p']['med'].' = ? WHERE stock_id = ?', array('p/'.$ipn.$slot.'_med.'.$ext, $stock_id));
		}
		imagesizer::resize('/home/imageuser/staging/'.$image, imagesizer::$map['sm'], __DIR__.'/images', 'p/'.$ipn.$slot.'_sm.'.$ext, TRUE);
		prepared_query::execute('INSERT INTO products_stock_control_images (stock_id, '.$image_slots[$slot]['ipn']['sm'].') VALUES (?, ?) ON DUPLICATE KEY UPDATE '.$image_slots[$slot]['ipn']['sm'].'=VALUES('.$image_slots[$slot]['ipn']['sm'].')', array($stock_id, 'p/'.$ipn.$slot.'_sm.'.$ext));
		prepared_query::execute('UPDATE products SET '.$image_slots[$slot]['p']['sm'].' = ? WHERE stock_id = ?', array('p/'.$ipn.$slot.'_sm.'.$ext, $stock_id));

		$dim = imagesizer::dim('/home/imageuser/staging/'.$image);
		if ($dim['width'] > imagesizer::$map['lrg']['width']) imagesizer::resize('/home/imageuser/staging/'.$image, imagesizer::$map['lrg'], __DIR__.'/images', 'p/'.$image, TRUE);
		else @copy('/home/imageuser/staging/'.$image, __DIR__.'/images/p/'.$ipn.$slot.'.'.$ext); // copy large image
		@rename('/home/imageuser/staging/'.$image, __DIR__.'/images/archive/'.$ipn.$slot.'.'.$ext); // move large image to archive
		// in either event, the large image should be named appropriately
		prepared_query::execute('INSERT INTO products_stock_control_images (stock_id, '.$image_slots[$slot]['ipn']['lrg'].') VALUES (?, ?) ON DUPLICATE KEY UPDATE '.$image_slots[$slot]['ipn']['lrg'].'=VALUES('.$image_slots[$slot]['ipn']['lrg'].')', array($stock_id, 'p/'.$image));
		prepared_query::execute('UPDATE products SET '.$image_slots[$slot]['p']['lrg'].' = ? WHERE stock_id = ?', array('p/'.$image, $stock_id));

		$request = new request();
		$data = array('MediaPath' => 'https://media.cablesandkits.com/p/'.$image, 'MediaType' => 8);
		$url = 'https://api.edgecast.com/v2/mcc/customers/'.$edgecast_account_num.'/edge/purge';
		$request->opt(CURLOPT_HTTPHEADER, array('Authorization: '.$edgecast_api_token, 'Accept: Application/JSON', 'Content-Type: Application/JSON'));
		//$request->opt(CURLINFO_HEADER_OUT, TRUE);
		//$request->opt(CURLOPT_VERBOSE, TRUE);
		$purge_response = $request->put($url, json_encode($data));
		// we don't really care, but we might at some point check this response and alert if the purge didn't go through
		// we might also at some point check to make sure that we even need to purge in the first place
		if ($request->status() != 200 || (($purge_response = json_decode($purge_response)) && empty($purge_response->Id))) {
			$notices[] = $image;
		}
		/*$data['MediaPath'] = 'https://media.cablesandkits.com/p/'.$ipn.$slot.'_300.'.$ext;
		$purge_response = $request->put($url, json_encode($data));
		if ($request->status() != 200 || (($purge_response = json_decode($purge_response)) && empty($purge_response->Id))) {
			$notices[] = $ipn.$slot.'_300.'.$ext;
		}*/
		$data['MediaPath'] = 'https://media.cablesandkits.com/p/'.$ipn.$slot.'_med.'.$ext;
		$purge_response = $request->put($url, json_encode($data));
		if ($request->status() != 200 || (($purge_response = json_decode($purge_response)) && empty($purge_response->Id))) {
			$notices[] = $ipn.$slot.'_med.'.$ext;
		}
		$data['MediaPath'] = 'https://media.cablesandkits.com/p/'.$ipn.$slot.'_sm.'.$ext;
		$purge_response = $request->put($url, json_encode($data));
		if ($request->status() != 200 || (($purge_response = json_decode($purge_response)) && empty($purge_response->Id))) {
			$notices[] = $ipn.$slot.'_sm.'.$ext;
		}

		$response['success'][] = $image;
	}
}

if (!empty($notices)) {
    $mailer = service_locator::get_mail_service();
    $mail = $mailer->create_mail()
        ->set_subject('CDN cache purge failed')
        ->set_from('marketing@cablesandkits.com')
        ->add_to('marketing@cablesandkits.com')
        ->set_body(null,"Failed to clear cache for the following images:\n\n".implode("\n", $notices));
    
    try {
        $mailer->send($mail);
    } catch(mail_service_exception $e) {
        // we don't really care if this fails to send, it's just an error that we have no simple (without creating a new error log) place to store.
        // @todo: define specialized log storage for mailer issues
    }
}

echo json_encode($response);
