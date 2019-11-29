<?php
class ck_cart_flyout_view extends ck_view {

	protected $url = '/cart-flyout';

	public function process_response() {
		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if (!empty($_REQUEST['action'])) $this->psuedo_controller();
		$this->respond();
	}

	private function psuedo_controller() {
		$page = NULL;

		$__FLAG = request_flags::instance();

		switch ($_REQUEST['action']) {
			case 'update-product-quantity':
				$_SESSION['cart']->process_page('update_product');
				break;
			case 'remove-product':
				$_SESSION['cart']->process_page('delete_cart_item');
				break;
			case 'add-product':
				$_SESSION['cart']->process_page('add_product');
				break;
			case 'erp-add-to-cart':
				$cart = ck_cart::instance();

				foreach ($_POST['quantity'] as $stock_id => $qty) {
					$qty = (int) $qty;
					if ($qty <= 0) continue;

					$product = new ck_product_listing($_POST['products_id'][$stock_id]);
					$price_level = $_POST['price_level'][$stock_id];

					$quote = NULL;
					if ($product->get_price('reason') != $price_level) {
						$quote = [];
						if ($price_level == 'quote') {
							$quote_price = CK\text::demonetize($_POST['quote_price'][$stock_id]);
							if ($quote_price > 0) $quote['price'] = $quote_price;
						}
						if (!isset($quote['price'])) $quote['price'] = $product->get_price($price_level);
						$quote['reason'] = $price_level;
					}

					$cart->update_product($product, $qty, $is_total=FALSE, $parent_products_id=NULL, $option_type=NULL, $cart_shipment_id=NULL, $allow_discontinued=TRUE, $quote);
				}
				break;
			default:
				break;
		}

		if (!empty($page)) CK\fn::redirect_and_exit($page);
	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}


	private function ajax_response() {
		$results = self::get_cart_data();
		if (!empty($results)) echo json_encode($results);
		else echo json_encode(['success' => false]);
		exit();
	}

	private function http_response() {
		CK\fn::redirect_and_exit('/shopping_cart.php');
	}

	public static function get_cart_data () {
		$results = [];
		if ($_SESSION['cart']->has_products()) {
			$cart_content = [];
			$results['free_shipping_eligible'] = TRUE;
			foreach ($_SESSION['cart']->get_products() as $cart_product) {
				if ($cart_product['option_type'] == 3) continue; // if the option type is included then we don't actually display the product like we do the rest
				$product = $cart_product['listing'];

				$temp = [
					'cart_product_id' => $cart_product['cart_product_id'],
					'products_id' => $product->id(),
					'media' => '//media.cablesandkits.com',
					'products_image' => $product->get_image('products_image'),
					'product_url' => $product->get_url(),
					'products_model' => $product->get_header('products_model'),
					'products_name' => $product->get_header('products_name'),
					'product_on_special' => $product->has_special(),
					'line_subtotal' => CK\text::monetize($cart_product['unit_price'] * $cart_product['quantity']),
					'products_price_original' => CK\text::monetize($product->get_price('original')),
					'products_price_display' => CK\text::monetize($cart_product['unit_price']),
					'parent_products_id' => $cart_product['parent_products_id'],
					'option_type' => $cart_product['option_type'],
					'products_quantity' => $cart_product['quantity']
				];
				// make sure the shipment is eligible for free shipping
				if ($product->free_shipping() == 0) {
					$results['free_shipping_eligible'] = FALSE;
					$temp['not_eligible_for_free_shipping'] = 1;
				}

				if ($product->has_options('included')) {
					$temp['has_included_options'] = $product->has_options('included');
					$temp['included_options'] = $product->get_options('included');
				}
				// if the product is an option of another product then we want to add it to that products array
				if ($cart_product['option_type'] > 0) {
					$cart_content[$cart_product['parent_products_id']]['product_options'][] = $temp;
				}
				else $cart_content[$product->id()] = $temp; //otherwise it gets it's own array
			}
			$results['cart_content'] = array_values($cart_content);
			$results['has_products'] = 1;
		}
		else $results['no_products'] = 1;

		$results['success'] = TRUE;
		$results['cart_totals'] = ['display' => CK\text::monetize($_SESSION['cart']->get_product_total()), 'raw_total' => number_format($_SESSION['cart']->get_product_total(), 2, '.', '')];
		return $results;
	}
}
?>
