<?php
 class spu {
	public $code = 'spu';
	public $title = 'Customer Pickup';
	public $description = 'In-store pickup during regular business hours.';
	//public $icon = DIR_WS_ICONS.'shipping_fedex_express_old.gif';
	public $enabled = TRUE;
	public $sort_order;

	// class constructor
	public function __construct() {
		$this->sort_order = 11;
	}

	// class methods

	public function quote($method='') {
		$this->quotes = [
			'id' => $this->code,
			'module' => '<hr><font color="#000000" size="2"><b>'.$this->title.'</b></font>',
			'icon' => tep_image(DIR_WS_IMAGES.'customerpickup.gif', $this->title, '', '', 'align="absmiddle"'),
			'methods' => [
				[
					'id' => $this->code,
					'title' => 47,
					'shipping_method_id' => 47,
					'cost' => '0.00'
				]
			]
		];

		return $this->quotes;
	}

	public function check() {
		if (!isset($this->_check)) {
			$check_query = prepared_query::fetch("select configuration_value from configuration where configuration_key = 'MODULE_SHIPPING_SPU_STATUS'");
			$this->_check = count($check_query);
		}
		return $this->_check;
	}

	function install() {
	}


	function remove() {
		$keys = '';
		$keys_array = $this->keys();
		for ($i=0; $i<sizeof($keys_array); $i++) {
			$keys .= "'".$keys_array[$i]."',";
		}
		$keys = substr($keys, 0, -1);

		prepared_query::execute("delete from configuration where configuration_key in (".$keys.")");
	}

	function keys() {
		return ['MODULE_SHIPPING_SPU_STATUS', 'MODULE_SHIPPING_SPU_COST', 'MODULE_SHIPPING_SPU_SORT_ORDER', 'MODULE_SHIPPING_SPU_ZONE'];
	}
 }
?>