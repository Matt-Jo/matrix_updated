<?php
/*
 $Id: ot_total.php,v 1.1.1.1 2004/03/04 23:41:16 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2003 osCommerce

 Released under the GNU General Public License
*/

 class ot_total {
	var $title, $output;

	function __construct() {
	$this->code = 'ot_total';
	$this->title = MODULE_ORDER_TOTAL_TOTAL_TITLE;
	$this->description = MODULE_ORDER_TOTAL_TOTAL_DESCRIPTION;
	$this->enabled = ((MODULE_ORDER_TOTAL_TOTAL_STATUS == 'true') ? true : false);
	$this->sort_order = MODULE_ORDER_TOTAL_TOTAL_SORT_ORDER;

	$this->output = array();
	}

	function process() {
	global $order;

	$this->output[] = array('title' => $this->title.':',
							'text' => '<b>'.CK\text::monetize($order->info['total']).'</b>',
							'value' => $order->info['total']);
	}

	function check() {
	if (!isset($this->_check)) {
		$check_query = prepared_query::fetch("select configuration_value from configuration where configuration_key = 'MODULE_ORDER_TOTAL_TOTAL_STATUS'");
		$this->_check = count($check_query);
	}

	return $this->_check;
	}

	function keys() {
	return array('MODULE_ORDER_TOTAL_TOTAL_STATUS', 'MODULE_ORDER_TOTAL_TOTAL_SORT_ORDER');
	}

	function install() {
	}

	function remove() {
	prepared_query::execute("delete from configuration where configuration_key in ('".implode("', '", $this->keys())."')");
	}
 }
?>
