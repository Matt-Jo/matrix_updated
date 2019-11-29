<?php
class ot_shipping {
	var $title, $output;

	function __construct() {
		$this->code = 'ot_shipping';
		$this->title = MODULE_ORDER_TOTAL_SHIPPING_TITLE;
		$this->description = MODULE_ORDER_TOTAL_SHIPPING_DESCRIPTION;
		$this->enabled = ((MODULE_ORDER_TOTAL_SHIPPING_STATUS == 'true') ? true : false);
		$this->sort_order = MODULE_ORDER_TOTAL_SHIPPING_SORT_ORDER;

		$this->output = array();
	}

	function process() {
		global $order;

		$cart = $_SESSION['cart'];
		$shipment = $cart->get_shipments('active');

		if (!empty($order->info['shipping_method'])) {
			// manage freight extras
			if ($order->info['shipping_method'] == 50) { // freight
				$GLOBALS['FREIGHT'] = TRUE; // so we can handle other processing related to freight
				$shipping_cost = $order->info['shipping_cost'];
				$order->info['liftgate_cost'] = $shipment['residential']?0:70;
				$order->info['inside_cost'] = 100;
				$order->info['limitaccess_cost'] = 100;
				$shipment['freight_needs_liftgate']?$shipping_cost += $order->info['liftgate_cost']:NULL;
				$shipment['freight_needs_inside_delivery']?$shipping_cost += $order->info['inside_cost']:NULL;
				$shipment['freight_needs_limited_access']?$shipping_cost += $order->info['limitaccess_cost']:NULL;
			}
			else {
				$shipping_cost = $order->info['shipping_cost'];
				$order->info['liftgate_cost'] = 0;
				$order->info['inside_cost'] = 0;
				$order->info['limitaccess_cost'] = 0;
			}

			if ($order->info['shipping_method'] != 50) {
				$this->output[] = array(
					'title' => $order->info['shipping_method'],
					'external_id' => $order->info['shipping_method'], # 334
					'text' => CK\text::monetize($order->info['shipping_cost']),
					'value' => $order->info['shipping_cost']
				);
			}
			else {
				$this->output[] = array(
					'title' => 'Oversize/Best Fit Shipping:',
					'external_id' => $order->info['shipping_method'], # 334
					'text' => CK\text::monetize($order->info['shipping_cost']),
					'value' => $order->info['shipping_cost']
				);
				if ($shipment['freight_needs_liftgate']) {
					$this->output[] = array(
						'title' => 'Liftgate:',
						'external_id' => NULL,
						'text' => $shipment['residential']?'Included':CK\text::monetize($order->info['liftgate_cost']),
						'value' => $order->info['liftgate_cost']
					);
					$order->info['total'] += $order->info['liftgate_cost'];
				}
				if ($shipment['freight_needs_inside_delivery']) {
					$this->output[] = array(
						'title' => 'Inside Delivery:',
						'external_id' => NULL,
						'text' => CK\text::monetize($order->info['inside_cost']),
						'value' => $order->info['inside_cost']
					);
					$order->info['total'] += $order->info['inside_cost'];
				}
				if ($shipment['freight_needs_limited_access']) {
					$this->output[] = array(
						'title' => 'Limited Access:',
						'external_id' => NULL,
						'text' => CK\text::monetize($order->info['limitaccess_cost']),
						'value' => $order->info['limitaccess_cost']
					);
					$order->info['total'] += $order->info['limitaccess_cost'];
				}
			}
		}
	}

	function check() {
		if (!isset($this->_check)) {
			$check_query = prepared_query::fetch("select configuration_value from configuration where configuration_key = 'MODULE_ORDER_TOTAL_SHIPPING_STATUS'");
			$this->_check = count($check_query);
		}

		return $this->_check;
	}

	function keys() {
		return array('MODULE_ORDER_TOTAL_SHIPPING_STATUS', 'MODULE_ORDER_TOTAL_SHIPPING_SORT_ORDER', 'MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING', 'MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER', 'MODULE_ORDER_TOTAL_SHIPPING_DESTINATION');
	}

	function remove() {
		prepared_query::execute("delete from configuration where configuration_key in ('".implode("', '", $this->keys())."')");
	}
 }
?>
