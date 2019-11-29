<?php
class ot_coupon {

	var $title, $output;

	function __construct() {
		$this->code = 'ot_coupon';
		$this->header = MODULE_ORDER_TOTAL_COUPON_HEADER;
		$this->title = MODULE_ORDER_TOTAL_COUPON_TITLE;
		$this->description = MODULE_ORDER_TOTAL_COUPON_DESCRIPTION;
		$this->user_prompt = '';
		$this->enabled = MODULE_ORDER_TOTAL_COUPON_STATUS;
		$this->sort_order = MODULE_ORDER_TOTAL_COUPON_SORT_ORDER;
		$this->include_shipping = MODULE_ORDER_TOTAL_COUPON_INC_SHIPPING;
		$this->include_tax = MODULE_ORDER_TOTAL_COUPON_INC_TAX;
		$this->calculate_tax = MODULE_ORDER_TOTAL_COUPON_CALC_TAX;
		$this->tax_class = MODULE_ORDER_TOTAL_COUPON_TAX_CLASS;
		$this->credit_class = true;
		$this->output = array();
	}

	function process() {
		global $order;
		$order_total=$this->get_order_total();
		$od_amount = $this->calculate_credit($order_total);
		//changes for ICW 510b
		$tod_amount = 0.0; //Fred
		$this->deduction = $od_amount;

		if ($od_amount > 0) {
			$order->info['total'] = $order->info['total'] - $od_amount;
			$this->output[] = array('title' => $this->title.':'.$this->coupon_code .':','text' => '<b>-'.CK\text::monetize($od_amount).'</b>', 'value' => $od_amount); //Fred added hyphen
		}
	}
	//end change 510b

	function selection_test() {
		return false;
	}

	function pre_confirmation_check($order_total) {
		return $this->calculate_credit($order_total);
	}

	function use_credit_amount() {
		return @$output_string; // no idea what is *supposed* to be happening here, so just suppress the notice and move on
	}

	function collect_posts() {
		global $order;
		if (is_numeric($_SESSION['customer_id'])) {
			$customer_type = prepared_query::fetch("SELECT customer_type FROM customers WHERE customers_id = ?", cardinality::SINGLE, array($_SESSION['customer_id']));
		}
		else {
			$customer_type = 0;
		}
		if (!empty($_POST['gv_redeem_code'])) {
			// get some info from the coupon table ICW change 5.10b
			$coupon_result = prepared_query::fetch("select coupon_id, coupon_amount, coupon_type, coupon_minimum_order,uses_per_coupon, uses_per_user, restrict_to_products,restrict_to_categories, customer_type from coupons where coupon_code=:coupon_code and coupon_active='Y'", cardinality::ROW, [':coupon_code' => $_POST['gv_redeem_code']]);

			if (empty($coupon_result) || $coupon_result['coupon_type'] != 'G') {
				if (empty($coupon_result)) {
					CK\fn::redirect_and_exit('/checkout_payment.php?error_message='.urlencode(ERROR_NO_INVALID_REDEEM_COUPON));
				}

				// below line changed for ICW 5.10b
				$date_query = prepared_query::fetch("select coupon_start_date from coupons where coupon_start_date <= now() and coupon_code=:coupon_code", cardinality::SET, [':coupon_code' => $_POST['gv_redeem_code']]);
				if (count($date_query) == 0) {
					CK\fn::redirect_and_exit('/checkout_payment.php?error_message='.urlencode(ERROR_INVALID_STARTDATE_COUPON));
				}

				// below line changed for ICW 5.10b
				$date_query = prepared_query::fetch("select coupon_expire_date from coupons where coupon_expire_date >= now() and coupon_code=:coupon_code", cardinality::SET, [':coupon_code' => $_POST['gv_redeem_code']]);
				if (count($date_query) == 0) {
					CK\fn::redirect_and_exit('/checkout_payment.php?error_message='.urlencode(ERROR_INVALID_FINISDATE_COUPON));
				}

				//below two lines changed for ICW 5.10b
				$coupon_count = prepared_query::fetch("select coupon_id from coupon_redeem_track where coupon_id = :coupon_id", cardinality::SET, [':coupon_id' => $coupon_result['coupon_id']]);
				$coupon_count_customer = prepared_query::fetch("select coupon_id from coupon_redeem_track where coupon_id = :coupon_id and customer_id = :customer_id", cardinality::SET, [':coupon_id' => $coupon_result['coupon_id'], ':customer_id' => $_SESSION['customer_id']]);

				if (count($coupon_count) >= $coupon_result['uses_per_coupon'] && $coupon_result['uses_per_coupon'] > 0) {

					CK\fn::redirect_and_exit('/checkout_payment.php?error_message='.urlencode(ERROR_INVALID_USES_COUPON.$coupon_result['uses_per_coupon'].TIMES));
				}

				if (count($coupon_count_customer) >= $coupon_result['uses_per_user'] && $coupon_result['uses_per_user'] > 0) {
					CK\fn::redirect_and_exit('/checkout_payment.php?error_message='.urlencode(ERROR_INVALID_USES_USER_COUPON.$coupon_result['uses_per_user'].TIMES));
				}

				if ($coupon_result['customer_type'] >= 0 && $coupon_result['customer_type'] != $customer_type) {
					if ($coupon_result['customer_type'] == 1) {		
						CK\fn::redirect_and_exit('/checkout_payment.php?error_message=The+coupon+code+you+entered+is+only+valid+for+Dealer+accounts.+If+you+have+questions,+please+call+'.$_SESSION['cart']->get_contact_phone());
					}
					else {
						CK\fn::redirect_and_exit('/checkout_payment.php?error_message=The+coupon+code+you+entered+is+not+valid+for+Dealer+accounts.+If+you+have+questions,+please+call+'.$_SESSION['cart']->get_contact_phone());
					}
				}

				if ($coupon_result['coupon_type'] == 'S') {
					//MMD - for 6/8/09 promotion
					if (strpos($order->info['shipping_method'], 'FedEx Ground') !== FALSE) {
						$coupon_amount = $order->info['shipping_cost'];
					}
					else {
						$coupon_amount = 0;
					}
				}
				else {
					$coupon_amount = CK\text::monetize($coupon_result['coupon_amount']).' ';
				}

				if ($coupon_result['coupon_type'] == 'P') $coupon_amount = $coupon_result['coupon_amount'].'% ';
				if ($coupon_result['coupon_minimum_order'] > 0) $coupon_amount .= 'on orders greater than '.$coupon_result['coupon_minimum_order'];
				$_SESSION['cc_id'] = $coupon_result['coupon_id']; //Fred ADDED, set the global and session variable
				// $_SESSION['cc_id'] = $coupon_result['coupon_id']; //Fred commented out, do not use $_SESSION[] due to backward comp. Reference the global var instead.
			}

			if (@$_POST['submit_redeem_coupon_x'] && !$_POST['gv_redeem_code']) {
				CK\fn::redirect_and_exit('/checkout_payment.php?error_message='.urlencode('You did not enter a redeem code.'));
			}
		}
	}

	function calculate_credit($amount) {
		global $order;
		//$_SESSION['cc_id'] = $_SESSION['cc_id']; //Fred commented out, do not use $_SESSION[] due to backward comp. Reference the global var instead.
		$od_amount = 0;

		if (isset($_SESSION['cc_id'])) {
			$coupon_result = prepared_query::fetch("select coupon_code from coupons where coupon_id = :coupon_id", cardinality::ROW, [':coupon_id' => $_SESSION['cc_id']]);
			if (!empty($coupon_result)) {
				$this->coupon_code = $coupon_result['coupon_code'];
				$get_result = prepared_query::fetch("select coupon_amount, coupon_minimum_order, restrict_to_products, restrict_to_categories, coupon_type from coupons where coupon_code = :coupon_code", cardinality::ROW, [':coupon_code' => $coupon_result['coupon_code']]);
				$c_deduct = $get_result['coupon_amount'];

				if ($get_result['coupon_type'] == 'S') {
					//MMD - for 6/8/09 promotion
					if (strpos($order->info['shipping_method'], 'FedEx Ground') !== FALSE) {
						$c_deduct = $order->info['shipping_cost'];
					}
					else {
						$c_deduct = 0;
					}
				}

				if ($get_result['coupon_minimum_order'] <= $this->get_order_total()) {
					if ($get_result['restrict_to_products'] || $get_result['restrict_to_categories']) {
						foreach ($order->products as $i => $product) {
							if (!empty($get_result['restrict_to_products'])) {
								$pr_ids = preg_split("/\s*,\s*/", $get_result['restrict_to_products']);
								foreach ($pr_ids as $ii => $pr_id) {
									if ($pr_id == $product['id']) {
										if ($get_result['coupon_type'] == 'P') {
											$pr_c = $this->single_product_price($product['id']);
											$pod_amount = round($pr_c*10)/10*$c_deduct/100;
											$qty = $_SESSION['cart']->get_total_product_quantity($product['id']);
											$od_amount = $od_amount + ($pod_amount*$qty);
										}
										else {
											$od_amount = $c_deduct;
										}
									}
								}
							}
							else {
								$cat_ids = preg_split("/\s*,\s*/", $get_result['restrict_to_categories']);
								$ckp = new ck_product_listing($product['id']);
								$my_path = $ckp->get_category_cpath();
								$sub_cat_ids = preg_split("/_/", $my_path);
								foreach ($sub_cat_ids as $iii => $sub_cat_id) {
									foreach ($cat_ids as $ii => $cat_id) {
										if ($sub_cat_id == $cat_id) {
											if ($get_result['coupon_type'] == 'P') {
												$pr_c = $this->single_product_price($product['id']);

												$pod_amount = round($pr_c*10)/10*$c_deduct/100;
												$qty = $_SESSION['cart']->get_total_product_quantity($product['id']);

												$od_amount = $od_amount + ($pod_amount*$qty);
											}
											else {
												$od_amount = $c_deduct;
											}
										}
									}
								}
							}
						}
					}
					else {
						if ($get_result['coupon_type'] !='P') {
							$od_amount = $c_deduct;
						}
						else {
							$od_amount = $amount * $get_result['coupon_amount'] / 100;
						}
					}
				}
			}
			if ($od_amount>$amount) $od_amount = $amount;
		}
		return $od_amount;
	}

	function get_order_total() {
		global $order;
		$order_total = $order->info['total'];

		if ($this->include_tax == 'false') $order_total = $order_total - $order->info['tax'];
		if ($this->include_shipping == 'false') $order_total = $order_total - $order->info['shipping_cost'];
		$coupon_result = prepared_query::fetch('SELECT coupon_code FROM coupons WHERE coupon_id = ?', cardinality::ROW, @$_SESSION['cc_id']);

		if (!empty($coupon_result)) {
			$get_result = prepared_query::fetch('SELECT coupon_amount, coupon_minimum_order, restrict_to_products, restrict_to_categories, coupon_type FROM coupons WHERE coupon_code = ?', cardinality::ROW, $coupon_result['coupon_code']);
			$in_cat = true;
			if (!empty($get_result['restrict_to_categories'])) {
				$cat_ids = preg_split("/\s*,\s*/", $get_result['restrict_to_categories']);
				$in_cat = false;
				foreach ($cat_ids as $i => $cat_id) {
					if (!empty($this->contents)) {
						foreach ($this->contents as $products_id => $contents) {
							$cat_query = prepared_query::fetch("select products_id from products_to_categories where products_id = :products_id and categories_id = :categories_id", cardinality::SET, [':products_id' => $products_id, ':categories_id' => $cat_id]);
							if (count($cat_query) != 0) {
								$in_cat = true;
								$total_price += $this->get_product_price($products_id);
							}
						}
					}
				}
			}
			$in_cart = true;
			if (!empty($get_result['restrict_to_products'])) {
				$pr_ids = preg_split("/\s*,\s*/", $get_result['restrict_to_products']);

				$in_cart=false;
				$products_array = $_SESSION['cart']->get_products();

				foreach ($pr_ids as $i => $pr_id) {
					foreach ($products_array as $ii => $product) {
						if ($product['id'] == $pr_id) {
							$in_cart = true;
							$total_price += $this->get_product_price($product['id']);
						}
					}
				}
				$order_total = $total_price;
			}
		}
		return $order_total;
	}

	function get_product_price($product_id) {
		global $order;
		$products_id = $product_id;
		// products price
		$qty = $_SESSION['cart']->get_universal_products($product_id)['quantity'];
		$product = prepared_query::fetch("select p.products_id, p.products_price, psc.dealer_price, p.products_tax_class_id, p.products_weight from products as p, products_stock_control as psc where p.products_id = :products_id AND p.stock_id = psc.stock_id", cardinality::ROW, [':products_id' => $product_id]);
		if (!empty($product)) {
			$prid = $product['products_id'];
			if ($_SESSION['customer_is_dealer'] == '1') {
				$products_price = $product['dealer_price'];
			}
			else {
				$products_price = $product['products_price'];
			}
			$specials_query = prepared_query::fetch("select specials_new_products_price from specials where products_id = :products_id and status = '1'", cardinality::SET, [':products_id' => $prid]);

			if (count($specials_query)) {
				$specials = $specials_query[0];
				$products_price = $specials['specials_new_products_price'];
			}

			$total_price += $products_price * $qty;
		}
		if ($this->include_shipping == 'true') {
			$total_price += $order->info['shipping_cost'];
		}
		return $total_price;
	}

	function get_single_product_price($product_id) {
		global $order;
		$products_id = $product_id;
		// products price
		$qty = $_SESSION['cart']->get_universal_products($product_id)['quantity'];
		$product = prepared_query::fetch("select p.products_id, p.products_price, psc.dealer_price, p.products_tax_class_id, p.products_weight from products as p, products_stock_control as psc where p.products_id = :products_id AND p.stock_id = psc.stock_id", cardinality::ROW, [':products_id' => $product_id]);

		$total_price = 0;

		if (!empty($product)) {
			$prid = $product['products_id'];
			if ($_SESSION['customer_is_dealer'] == '1') {
				$products_price = $product['dealer_price'];
			}
			else {
				$products_price = $product['products_price'];
			}
			$specials_query = prepared_query::fetch("select specials_new_products_price from specials where products_id = :products_id and status = '1'", cardinality::SET, [':products_id' => $prid]);
			if (count($specials_query)) {
				$specials = $specials_query[0];
				$products_price = $specials['specials_new_products_price'];
			}
			$total_price += $products_price;
		}
		if ($this->include_shipping == 'true') {
			$total_price += $order->info['shipping_cost'];
		}
		return $total_price;
	}
	//Added by Fred -- BOF -----------------------------------------------------
	//JUST RETURN THE PRODUCT PRICE (INCL ATTRIBUTE PRICES) WITH OR WITHOUT TAX
	function product_price($product_id) {
		$total_price = $this->get_product_price($product_id);
		if ($this->include_shipping == 'true') $total_price -= $order->info['shipping_cost'];
		return $total_price;
	}
	function single_product_price($product_id) {
		$total_price = $this->get_single_product_price($product_id);
		if ($this->include_shipping == 'true') $total_price -= $order->info['shipping_cost'];
		return $total_price;
	}
	//Added by Fred -- EOF -----------------------------------------------------

	function check() {
		if (!isset($this->check)) {
			$check_query = prepared_query::fetch("select configuration_value from configuration where configuration_key = 'MODULE_ORDER_TOTAL_COUPON_STATUS'");
			$this->check = count($check_query);
		}

		return $this->check;
	}

	function keys() {
		return array('MODULE_ORDER_TOTAL_COUPON_STATUS', 'MODULE_ORDER_TOTAL_COUPON_SORT_ORDER', 'MODULE_ORDER_TOTAL_COUPON_INC_SHIPPING', 'MODULE_ORDER_TOTAL_COUPON_INC_TAX', 'MODULE_ORDER_TOTAL_COUPON_CALC_TAX', 'MODULE_ORDER_TOTAL_COUPON_TAX_CLASS');
	}

	function install() {
	}

	function remove() {
		$keys_array = $this->keys();
		prepared_query::execute("delete from configuration where configuration_key in ('".implode("', '", $keys_array)."')");
	}
}
?>
