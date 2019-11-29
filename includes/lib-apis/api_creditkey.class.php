<?php
require(__DIR__.'/creditkey-php-master/init.php');

class api_creditkey extends ck_master_api {
	private static $access = [
		\CreditKey\Api::STAGING => [
			'public_key' => 'cablesandkits_b1476e910d0c4715a47125e5df9c802e',
			'shared_secret' => '8891f083ce2e4b5bab2acb35f75ebe6a',
		],
		\CreditKey\Api::PRODUCTION => [
			'public_key' => 'cableskits_33b5005f1008447eb61c997f2ab7d8f3',
			'shared_secret' => '146df3682db049d598c9c5021716b0dd',
		],
	];

	private static $configured = FALSE;

	private static $return_url = 'checkout_confirmation.php?action=complete-credit-key&status=success&credit_key_order_id=%CKKEY%';
	private static $cancel_url = 'checkout_confirmation.php?action=complete-credit-key&status=cancel';

	private static $context = \CreditKey\Api::STAGING;

	private $cart;
	private $order;

	private $credit_key_order_id;

	private $cart_items = [];

	private $billing_address;
	private $shipping_address;
	private $charges;

	public function __construct() {
		self::setup();
	}

	public static function setup() {
		if (!self::$configured) {
			self::$return_url = '//'.FQDN.'/'.self::$return_url;
			self::$cancel_url = '//'.FQDN.'/'.self::$cancel_url;

			if (self::is_production()) self::$context = \CreditKey\Api::PRODUCTION;

			\CreditKey\Api::configure(self::$context, self::$access[self::$context]['public_key'], self::$access[self::$context]['shared_secret']);

			if (!\CreditKey\Authentication::authenticate()) throw new CKCreditKeyApiException('Could not authenticate CreditKey.');

			self::$configured = TRUE;
		}
	}

	private function reset() {
		$this->cart = NULL;
		$this->order = NULL;
		$this->credit_key_order_id = NULL;
		$this->cart_items = [];
		$this->billing_address = NULL;
		$this->shipping_address = NULL;
		$this->charges = NULL;
	}

	public function build_cart(ck_cart $cart) {
		$this->reset();

		$this->cart = $cart;

		$shipment = $cart->get_shipments('active');
		$payments = $cart->get_payments($shipment['cart_shipment_id']);
		$payment = $payments[0];

		$this->credit_key_order_id = $payment['credit_key_order_id'];

		$products = $cart->get_products();
		$quotes = $cart->get_quotes();

		if (!empty($products)) {
			foreach ($products as $product) {
				$this->cart_items[] = new \CreditKey\Models\CartItem($product['cart_product_id'], $product['listing']->get_header('products_name'), $product['unit_price'] * $product['quantity'], $product['listing']->get_header('products_model'), $product['quantity'], NULL, NULL);
			}
		}

		if (!empty($quotes)) {
			foreach ($quotes as $quote) {
				foreach ($quote['quote']->get_products() as $product) {
					$this->cart_items[] = new \CreditKey\Models\CartItem('q'.$product['customer_quote_product_id'], $product['listing']->get_header('products_name'), $product['price'] * $product['quantity'], $product['listing']->get_header('products_model'), $product['quantity'], NULL, NULL);
				}
			}
		}

		$billing = $cart->get_billing_address();
		$shipping = $cart->get_shipping_address();

		$this->billing_address = new \CreditKey\Models\Address($billing->get_header('first_name'), $billing->get_header('last_name'), $billing->get_company_name(), $cart->get_email_address(), $billing->get_header('address1'), $billing->get_header('address2'), $billing->get_header('city'), $billing->get_state(), $billing->get_header('postcode'), $billing->get_header('telephone'));

		$this->shipping_address = new \CreditKey\Models\Address($shipping->get_header('first_name'), $shipping->get_header('last_name'), $shipping->get_company_name(), $cart->get_email_address(), $shipping->get_header('address1'), $shipping->get_header('address2'), $shipping->get_header('city'), $shipping->get_state(), $shipping->get_header('postcode'), $shipping->get_header('telephone'));

		$totals = $cart->get_simple_totals();

		$this->charges = new \CreditKey\Models\Charges($cart->get_total(), @$totals['shipping'], @$totals['tax'], @$totals['coupon'], $totals['total']);
	}

	public function is_available() {
		if (empty($this->cart)) return FALSE;
		if ($this->cart->get_customer()->has_credit() && $this->cart->get_customer()->has_terms()) return FALSE;

		return \CreditKey\Checkout::isDisplayedInCheckout($this->cart_items, $this->cart->get_header('customers_id'));
	}

	public function get_checkout_url() {
		if (!$this->is_available()) return NULL;

		return \CreditKey\Checkout::beginCheckout($this->cart_items, $this->billing_address, $this->shipping_address, $this->charges, $this->cart->id(), $this->cart->get_header('customers_id'), self::$return_url, self::$cancel_url);
	}

	public function capture_payment() {
		if (!$this->is_available()) return NULL;

		//$this->update_cart();

		return \CreditKey\Checkout::completeCheckout($this->credit_key_order_id);
	}

	/*public function update_cart() {
		if (empty($this->cart)) throw new CKCreditKeyApiException('No cart to update.');

		if (empty($this->credit_key_order_id)) return NULL;

		$order = \CreditKey\Orders::update($this->credit_key_order_id, 'PENDING', NULL, $this->cart_items, $this->charges, $this->shipping_address);

		return $order;
	}*/

	public function build_order(ck_sales_order $order) {
		$this->reset();

		$this->order = $order;

		$this->credit_key_order_id = $order->get_header('credit_key_order_id');

		foreach ($order->get_products() as $product) {
			$this->cart_items[] = new \CreditKey\Models\CartItem($product['orders_products_id'], $product['listing']->get_header('products_name'), $product['final_price'] * $product['quantity'], $product['listing']->get_header('products_model'), $product['quantity'], NULL, NULL);
		}

		$billing = $order->get_bill_address();
		$shipping = $order->get_ship_address();

		$this->billing_address = new \CreditKey\Models\Address($billing->get_header('first_name'), $billing->get_header('last_name'), $billing->get_company_name(), $order->get_prime_contact('email'), $billing->get_header('address1'), $billing->get_header('address2'), $billing->get_header('city'), $billing->get_state(), $billing->get_header('postcode'), $billing->get_header('telephone'));

		$this->shipping_address = new \CreditKey\Models\Address($shipping->get_header('first_name'), $shipping->get_header('last_name'), $shipping->get_company_name(), $order->get_prime_contact('email'), $shipping->get_header('address1'), $shipping->get_header('address2'), $shipping->get_header('city'), $shipping->get_state(), $shipping->get_header('postcode'), $shipping->get_header('telephone'));

		$totals = $order->get_simple_totals();

		$this->charges = new \CreditKey\Models\Charges($order->get_product_subtotal(), @$totals['shipping'], @$totals['tax'], @$totals['coupon'], $totals['total']);
	}

	public function update_order() {
		if (empty($this->order)) throw new CKCreditKeyApiException('No order to update.');

		if (empty($this->credit_key_order_id)) return NULL;

		$order = \CreditKey\Orders::update($this->credit_key_order_id, $this->order->get_header('orders_status'), $this->order->id(), $this->cart_items, $this->charges, $this->shipping_address);

		return $order;
	}

	public function ship_order() {
		if (empty($this->order)) throw new CKCreditKeyApiException('No order to ship.');

		if (empty($this->credit_key_order_id)) return NULL;

		$order = \CreditKey\Orders::confirm($this->credit_key_order_id, $this->order->id(), $this->order->get_header('orders_status'), $this->cart_items, $this->charges);

		return $order;
	}

	public function get_order_status() {
		if (empty($this->order)) throw new CKCreditKeyApiException('No order to find status for.');

		if (empty($this->credit_key_order_id)) return NULL;

		$order = \CreditKey\Orders::find($this->credit_key_order_id);

		return $order;
	}

	public function cancel_order() {
		if (empty($this->order)) throw new CKCreditKeyApiException('No order to cancel.');

		if (empty($this->credit_key_order_id)) return NULL;

		$order = \CreditKey\Orders::cancel($this->credit_key_order_id);

		return $order;
	}

	public function refund_order($amount) {
		if (empty($this->order)) throw new CKCreditKeyApiException('No order to refund.');

		if (empty($this->credit_key_order_id)) return NULL;

		$order = \CreditKey\Orders::refund($this->credit_key_order_id, $amount);

		return $order;
	}
}

class CKCreditKeyApiException extends CKApiException {
}
?>
