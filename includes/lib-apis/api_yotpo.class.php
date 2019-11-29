<?php
class api_yotpo extends ck_master_api {
	private static $auth = [
		'client_id' => 'RS5sKge5HAa1Oe1gbgC5XkKMu2IATDFERi5Cypav',
		'client_secret' => 'xX2ewJFrICBhvAfnnhEdzYyJXrNHbrmkDcc70wMf',
		'grant_type' => 'client_credentials',
	];

	private static $uri = 'https://api.yotpo.com';

	private static $endpoints = [
		'auth' => [
			'service' => 'oauth',
			'endpoint' => 'token',
		],
		'orders' => [
			'service' => 'apps',
			'insert' => 'client_id',
			'endpoint' => 'purchases/mass_create',
		],
	];

	private $rh; // request handle
	private $utoken;

	public function __construct() {
		$this->rh = new request();

		$this->fetch_utoken();
	}

	public function request($uri, $req) {
		$this->rh->opt(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		return $this->rh->post($uri, $req);
	}

	private static function uri($service) {
		$uri = self::$uri;
		if (!empty(self::$endpoints[$service])) {
			if (!empty(self::$endpoints[$service]['service'])) $uri .= '/'.self::$endpoints[$service]['service'];
			if (!empty(self::$endpoints[$service]['insert'])) $uri .= '/'.self::$auth[self::$endpoints[$service]['insert']];
			if (!empty(self::$endpoints[$service]['endpoint'])) $uri .= '/'.self::$endpoints[$service]['endpoint'];
		}
		return $uri;
	}

	private function fetch_utoken() {
		$req = self::$auth;

		$res = $this->request(self::uri('auth'), $req);

		$response = json_decode($res);
		return $this->utoken = $response->access_token;
	}

	public function send_order_feed(DateTime $start_date, DateTime $end_date=NULL) {
		$req = [
			'validate_data' => TRUE,
			'platform' => 'general',
			'utoken' => $this->utoken,
			'orders' => [],
		];

		if (empty($end_date)) $end_date = $start_date; // it's OK that it's copy by ref

		if ($orders_ids = prepared_query::fetch('SELECT DISTINCT i.inv_order_id FROM acc_invoices i LEFT JOIN acc_invoices i0 ON i.inv_order_id = i0.inv_order_id AND i.invoice_id > i0.invoice_id WHERE i.inv_order_id IS NOT NULL AND i0.invoice_id IS NULL AND DATE(i.inv_date) >= :start_date AND DATE(i.inv_date) <= :end_date ORDER BY i.inv_order_id ASC', cardinality::COLUMN, [':start_date' => $start_date->format('Y-m-d'), ':end_date' => $end_date->format('Y-m-d')])) {
			foreach ($orders_ids as $orders_id) {
				$ck_order = new ck_sales_order($orders_id);
				$ck_customer = $ck_order->get_customer();
				if ($ck_customer->get_header('customer_type') == 1 || !$ck_customer->is('newsletter_subscribed') || !empty($ck_order->get_header('ca_order_id')) || in_array($ck_customer->id(), ['96285', '127213'])) continue;

				$order = [
					'email' => $ck_order->get_header('customers_email_address'),
					'customer_name' => htmlspecialchars($ck_order->get_header('customers_name')),
					'order_id' => $ck_order->id(),
					'order_date' => $ck_order->get_header('date_purchased')->format('Y-m-d'),
					'currency_iso' => 'USD',
					'products' => [],
				];

				foreach ($ck_order->get_products() as $product) {
					if ($product['final_price'] <= 0) continue;

					$order['products'][$product['products_id']] = [
						'name' => $product['name'],
						'url' => 'https://'.PRODUCTION_FQDN.$product['listing']->get_url(),
						'image' => DIR_WS_IMAGES.$product['listing']->get_image('products_image_lrg'),
						'desription' => strip_tags($product['listing']->get_header('products_head_desc_tag')),
						'price' => CK\text::demonetize($product['final_price']),
					];
				}

				if (empty($order['products'])) continue;

				$req['orders'][] = $order;
			}
		}

		$config =  service_locator::get_config_service();

		if (!$config->is_production()) {
			var_dump($start_date, $end_date, $req, self::uri('orders'));
			return;
		}

		if (!empty($req['orders'])) {
			$res = $this->request(self::uri('orders'), $req);

			var_dump($req, $res, self::uri('orders'), $this->rh->debug_data(), $this->rh->debug_opts());

			$this->rh->debug(TRUE);
		}
	}

	// not used, so I'm not going to make it work in the object oriented context, just a straight copy from the old functions file
	/*public function getYotpoInlineSeo($product_id) {
		$pd = Ck_Product_Description::getById($product_id);

		if (strtotime($pd->yotpo_last_updated) > time() - 24*60*60) {
			$result = array(
				'main_widget' => $pd->yotpo_reviews_markup,
				'bottom_line' => $pd->yotpo_stars_markup
			);

			return $result;
		}

		//adapted from https://support.yotpo.com/hc/en-us/articles/204415043-In-Line-SEO
		$data = array('methods' => '[{"method":"main_widget","params":{"pid":"'.$product_id.'"}}, {"method":"bottomline","params":{"pid":"'.$product_id.'","link":"","skip_average_score":false}}]','app_key' => 'RS5sKge5HAa1Oe1gbgC5XkKMu2IATDFERi5Cypav');

		$url = 'http://staticw2.yotpo.com/batch';
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$result = curl_exec($ch);
		curl_close($ch);

		// Parsing the response
		$response = json_decode($result, true);
		$main_widget = $response[0]["result"];
		$bottom_line = $response[1]["result"];

		$result = array(
			'main_widget' => $main_widget,
			'bottom_line' => $bottom_line
		);

		$pd->yotpo_reviews_markup = $main_widget;
		$pd->yotpo_stars_markup = $bottom_line;
		$pd->yotpo_last_updated = date('Y-m-d H:i:s');
		$pd->save();

		return $result;
	}*/
}

class CKYotpoException extends Exception {
}
?>
