<?php
class ckstandard {
	public $code = 'ckstandard';
	public $title = 'Standard Shipping';
	public $description = 'Standard Shipping';
	public $sort_order = 10;
	public $icon = '/images/icons/ckheader2_logo_small.gif';
	public $tax_class = 0;

	private $allow_standard_shipping = TRUE;
	private $allow_reduced_rate = FALSE;
	private $allow_below_cost = FALSE;

	public $enabled = TRUE;
	public $free = TRUE;

	private $reduced_rate_enabled = FALSE;
	private $reduced_rate = 0.7;

	private $cached_rates;

	private $destination;

	public function __construct() {
		$this->enabled = $this->allow_standard_shipping;

		try {
			if (!empty($GLOBALS['order']) && !empty($GLOBALS['order']->delivery)) {
				$destination = $GLOBALS['order']->delivery;
				$destination['state_code'] = prepared_query::fetch('SELECT UPPER(zone_code) FROM zones WHERE zone_name = :state', cardinality::SINGLE, [':state' => $destination['state']]);

				$address_type = new ck_address_type();
				$address_type->load('header', [
					'address1' => $destination['street_address'],
					'address2' => !empty($destination['suburb'])?$destination['suburb']:NULL,
					'postcode' => $destination['postcode'],
					'city' => $destination['city'],
					'zone_id' => @$destination['zone_id'],
					'state' => $destination['state_code'],
					'countries_id' => $destination['country']['id'],
					'countries_iso_code_2' => $destination['country']['iso_code_2'],
				]);
				$this->destination = new ck_address2(NULL, $address_type);
			}
		}
		catch (Exception $e) {
			// default to address already attached to cart
		}

		if (empty($_SESSION['cart'])) $this->enabled = FALSE;
		else {
			if (!$_SESSION['cart']->is_freeship_eligible($this->destination, FALSE)) $this->enabled = FALSE;
			elseif ($_SESSION['cart']->get_estimated_shipped_weight() <= .9) $this->reduced_rate_enabled = $this->allow_reduced_rate;

			if (!$_SESSION['cart']->is_freeship_eligible($this->destination)) $this->free = FALSE;
			//@$order->info['subtotal'] >= $GLOBALS['ck_keys']->product['freeship_threshold']
		}

		// If we're logged in as admin, we get extra permissions
		if (!empty($_SESSION['admin']) && !empty($_GET['oid'])) {
			$order = new ck_sales_order($_GET['oid']);
			$customer = $order->get_customer();
			$address = $order->get_shipping_address();
			$state = $address->get_header('countries_iso_code_2')=='US'?$address->get_state():NULL;
			$country = $address->get_header('countries_iso_code_2');

			$sub_total = 0;

			$products = $order->get_products();

			foreach ($products as $product) {
				$sub_total += $product['final_price'] * $product['quantity'];
			}

			if ($country == 'US' && !in_array($state, ['AK', 'HI'])) {
				$this->enabled = TRUE;
				$order->info['subtotal'] = $sub_total;
			}
			if ($sub_total < $GLOBALS['ck_keys']->product['freeship_threshold']) $this->free = FALSE;
		}

		if ($this->enabled && !$this->free && !$this->reduced_rate_enabled) $this->enabled = FALSE;

		if ($this->enabled && !$this->free && !$this->allow_below_cost) {
			$this->rate_quote();
			$reduced_rate = $this->get_reduced_rate();

			$list_rate = $this->cached_rates[\Ups\Entity\Service::S_GROUND]['list'];

			if ($list_rate <= $reduced_rate) $this->enabled = FALSE;
		}

		if ($this->free) {
			$this->title = 'CK Free '.$this->title;
			$this->description = 'CK Free '.$this->description;
		}
		else {
			$this->title = 'CK '.$this->title;
			$this->description = 'CK '.$this->description;
		}
	}

	public function quote($method='') {
		$this->quotes = [
			'id' => $this->code,
			'module' => '<hr><strong color="#0F4B96" size="2">'.$this->title.'</strong>',
			'methods' => [
				[
					'id' => $this->code,
					'display_title' => $this->title,
					'title' => 48,
					'shipping_method_id' => 48,
					'cost' => 0,
					'negotiated_rate' => 0
				]
			]
		];

		if ($this->reduced_rate_enabled) {
			$this->quotes['methods'][0]['cost'] = $this->get_reduced_rate();
			$this->quotes['methods'][0]['negotiated_rate'] = $this->rate_quote('negotiated');
		}

		if (!empty($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title);

		return $this->quotes;
	}

	private function get_reduced_rate() {
		if ($this->free) return 0;
		elseif ($this->reduced_rate_enabled) return 3.99;
		elseif (!$this->reduced_rate_enabled) return NULL;

		$this->rate_quote();

		$list = $this->cached_rates[\Ups\Entity\Service::S_GROUND]['list'];
		$negotiated = $this->cached_rates[\Ups\Entity\Service::S_GROUND]['negotiated'];

		$reduced = $list * $this->reduced_rate;

		if (!$this->allow_below_cost) $reduced = max($reduced, $negotiated);

		return $reduced;
	}

	private function clear_cached_rates() {
		$this->cached_rates = NULL;
	}

	private function rate_quote($context='list') {
		if ($this->free) return 0;
		elseif (!$this->reduced_rate_enabled) return NULL;

		if (!empty($this->cached_rates)) return $this->cached_rates[\Ups\Entity\Service::S_GROUND][$context];

		if (empty($GLOBALS['shipping_num_boxes'])) shipping::build_packages();

		try {
			$packages = [];
			for ($i=0; $i<$GLOBALS['shipping_num_boxes']; $i++) {
				$packages[] = ['weight' => $GLOBALS['shipping_weight']];
			}

			$this->cached_rates = api_ups::quote_rates($packages, $this->destination);

			return $this->cached_rates[\Ups\Entity\Service::S_GROUND][$context];
		}
		catch (Exception $e) {
			return NULL;
		}
	}

	public function check() {
		return 1;
	}
}
?>
