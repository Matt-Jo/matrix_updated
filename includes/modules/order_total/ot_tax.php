<?php
/*
 $Id: ot_tax.php,v 1.1.1.1 2004/03/04 23:41:16 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2003 osCommerce

 Released under the GNU General Public License
*/

 class ot_tax {
	var $title, $output;

	function __construct() {
	$this->code = 'ot_tax';
	$this->title = MODULE_ORDER_TOTAL_TAX_TITLE;
	$this->description = MODULE_ORDER_TOTAL_TAX_DESCRIPTION;
	$this->enabled = ((MODULE_ORDER_TOTAL_TAX_STATUS == 'true') ? true : false);
	$this->sort_order = MODULE_ORDER_TOTAL_TAX_SORT_ORDER;

	$this->output = array();
	}

	function process() {
		global $order;

		if (array_key_exists('tax', $order->info)) $tax = $order->info['tax'];
		else {
			require_once(dirname(__FILE__).'/../../functions/avatax.php');

			$tax = avatax_get_tax($order->products, $order->info['shipping_cost']);
		}

		//MMD - TODO - take into account logic from below

		if ($tax > 0) {
			$this->output[] = array('title' => 'Tax: ',
				'text' => CK\text::monetize($tax),
				'value' => $tax);
		}
	}

	function check() {
	if (!isset($this->_check)) {
		$check_query = prepared_query::fetch("select configuration_value from configuration where configuration_key = 'MODULE_ORDER_TOTAL_TAX_STATUS'");
		$this->_check = count($check_query);
	}

	return $this->_check;
	}

	function keys() {
	return array('MODULE_ORDER_TOTAL_TAX_STATUS', 'MODULE_ORDER_TOTAL_TAX_SORT_ORDER');
	}

	function install() {
	}

	function remove() {
	prepared_query::execute("delete from configuration where configuration_key in ('".implode("', '", $this->keys())."')");
	}
 }
?>
