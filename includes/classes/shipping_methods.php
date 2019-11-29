<?php
class shipping_methods {

	protected static $smArr = array();

	public function shipping_methods() {
		return isset($this)?$this->__construct():self::__construct();
	}

	public function __construct() {
		$tmpArr = array();
		$sms = prepared_query::fetch('SELECT * FROM shipping_methods');
		foreach ($sms as $tmpArr) {
			self::$smArr[$tmpArr['shipping_code']] = array(
				'carrier' => $tmpArr['carrier'],
				'name' => $tmpArr['name'],
				'description' => $tmpArr['description'],
				'original_code' => $tmpArr['original_code'],
				'shortdescription' => trim($tmpArr['carrier'].' '.$tmpArr['name']),
				'fulldescription' => trim($tmpArr['carrier'].' '.$tmpArr['name']).($tmpArr['description'] ? ' ('.$tmpArr['description'].')' : ''),
				'length' => strlen(trim($tmpArr['carrier'].' '.$tmpArr['name']))
			);
		}
	}

	public function getShippingMethods() {
		return self::$smArr;
	}

	public function sm_details ($idx,$nm) {
		return is_array(self::$smArr)&&!empty(self::$smArr[$idx])?self::$smArr[$idx][$nm]:'';
	}

	public function sm_description ($idx) {
		return is_array(self::$smArr)&&!empty(self::$smArr[$idx])?self::$smArr[$idx]['description']:'';
	}

	public function sm_short_description ($idx) {
		return is_array(self::$smArr)&&!empty(self::$smArr[$idx])?self::$smArr[$idx]['shortdescription']:'';
	}

	public function sm_max_length () {
		$len = 0;
		foreach (self::$smArr as $k => $arr) {
			$len = max($len, strlen($arr['fulldescription']));
		}
		return $len;
	}
}
?>
