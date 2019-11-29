<?php
class shipping {

	var $modules;

	// class constructor
	function __construct($module = '') {
		// BOF: WebMakers.com Added: Downloads Controller
		global $PHP_SELF;
		// EOF: WebMakers.com Added: Downloads Controller

		if (defined('MODULE_SHIPPING_INSTALLED') && tep_not_null(MODULE_SHIPPING_INSTALLED)) {
			$this->modules = explode(';', MODULE_SHIPPING_INSTALLED);

			$include_modules = array();

			if (tep_not_null($module) && in_array(substr($module['id'], 0, strpos($module['id'], '_')).'.'.substr($PHP_SELF, (strrpos($PHP_SELF, '.')+1)), $this->modules)) {
				$include_modules[] = array('class' => substr($module['id'], 0, strpos($module['id'], '_')), 'file' => substr($module['id'], 0, strpos($module['id'], '_')).'.'.substr($PHP_SELF, (strrpos($PHP_SELF, '.')+1)));
			}
			else {
				// All Other Shipping Modules
                foreach($this->modules as $value) {
					$class = substr($value, 0, strrpos($value, '.'));
					// Don't show Free Shipping Module
					if ($class != 'freeshipper') {
						$include_modules[] = array('class' => $class, 'file' => $value);
					}
				}
			}

			foreach ($include_modules as $im) {
				if (is_file(DIR_FS_CATALOG.'/'.DIR_WS_LANGUAGES.$_SESSION['language'].'/modules/shipping/'.$im['file'])) include_once(DIR_FS_CATALOG.'/'.DIR_WS_LANGUAGES.$_SESSION['language'].'/modules/shipping/'.$im['file']);
				include_once(DIR_FS_CATALOG.'/'.DIR_WS_MODULES.'shipping/'.$im['file']);

				$GLOBALS[$im['class']] = new $im['class'];
			}
		}

		self::build_packages();
	}

	public static function build_packages() {
		global $total_weight, $shipping_weight, $shipping_quoted, $shipping_num_boxes;

		$shipping_quoted = '';
		$shipping_num_boxes = 1;
		$shipping_weight = $total_weight;

		if (SHIPPING_BOX_WEIGHT >= $shipping_weight*SHIPPING_BOX_PADDING/100) {
			$shipping_weight = $shipping_weight+SHIPPING_BOX_WEIGHT;
		}
		else {
			$shipping_weight = $shipping_weight + ($shipping_weight*SHIPPING_BOX_PADDING/100);
		}

		if ($shipping_weight > SHIPPING_MAX_WEIGHT) { // Split into many boxes
			$shipping_num_boxes = ceil($shipping_weight/SHIPPING_MAX_WEIGHT);
			$shipping_weight = $shipping_weight/$shipping_num_boxes;
		}
	}

	public function modified_quote($shipping_method_id) {
		$module = '';
		$method = '';

		$shipping_methods = prepared_query::fetch('SELECT * FROM shipping_methods WHERE shipping_code = :shipping_method_id', cardinality::ROW, [':shipping_method_id' => $shipping_method_id]);

		$method = $shipping_methods['original_code'];

		switch ($shipping_method_id) {
			case 48:
				$module = 'ckstandard';
				$method = 'Standard Shipping'; // ignored
				break;
			case 47:
				$module = 'spu';
				$method = 'Customer Pickup'; // ignored
				break;
			case 50:
				$module = 'fedexfreight';
				$method = 'FedEx Freight'; // ignored
				break;
			// UPS
			case 17:
				$module = 'iux';
				$method = 'UPS Next Day Air Early A.M.';
				break;
			case 18:
				$module = 'iux';
				$method = 'UPS Next Day Air';
				break;
			case 19:
				$module = 'iux';
				$method = 'UPS Next Day Air Saver';
				break;
			case 20:
				$module = 'iux';
				$method = 'UPS 2nd Day Air AM';
				break;
			case 21:
				$module = 'iux';
				$method = 'UPS 2nd Day Air';
				break;
			case 22:
				$module = 'iux';
				$method = 'UPS 3 Day Select';
				break;
			case 23:
				$module = 'iux';
				$method = 'UPS Ground';
				break;
			case 25:
				$module = 'iux';
				$method = 'UPS Worldwide Express Plus';
				break;
			case 26:
				$module = 'iux';
				$method = 'UPS Worldwide Express';
				break;
			case 27:
				$module = 'iux';
				$method = 'UPS Express Saver';
				break;
			case 28:
				$module = 'iux';
				$method = 'UPS Worldwide Expedited';
				break;
			case 29:
				$module = 'iux';
				$method = 'UPS Standard';
				break;
			case 64:
				$module = 'iux';
				$method = 'UPS Saturday Delivery (Next Day Air)';
				break;
			case 65:
				$module = 'iux';
				$method = 'UPS Saturday Delivery (2nd Day Air)';
				break;
			// FedEx
			case 2:
			case 3:
			case 4:
			case 5:
			case 7:
			case 8:
			case 9: // ground - was fedexwebservices
			case 13:
			case 14:
				$module = 'fedexnonsaturday';
				break;
			/*case 9:
				$module = 'fedexwebservices';
				break;*/
			/* these are not available on the front end
			case 31:// USPS
			case 32:// USPS
			case 33:// USPS
			case 34:// USPS
			case 38:// USPS
			case 41:// USPS
			case 43:// USPS
			case 1: fedex
			case 12: fedex
			case 15: fedex
			case 49: fedex saturday
			case 51: flat
			case 52: apo/fpo
			case 53: freight
			case 54: freight
			case 55: freight
			case 56: freight
			case 57: freight
			case 58: freight
			case 59: freight
			case 60: freight
			case 61: ups freight
			case 62: freight
			case 63: dhl
				break;*/
		}

		return $this->quote($method, $module);
	}

	function quote($method = '', $module = '') {
		$quotes_array = array();

		if (is_array($this->modules)) {
			$include_quotes = array();

			foreach ($this->modules as $value) {
				$class = substr($value, 0, strrpos($value, '.'));
				if (tep_not_null($module)) {
					if (($module == $class) && ($GLOBALS[$class]->enabled)) {
						$include_quotes[] = $class;
					}
				}
				elseif ($GLOBALS[$class]->enabled) {
					// we moved this section to the actual quote phase to handle failover duties, if the module is exclusive but fails, then show everything else
					/*if (isset($GLOBALS[$class]->exclusive) && $GLOBALS[$class]->exclusive) {
						$include_quotes = array($class);
						break;
					}*/
					$include_quotes[] = $class;
				}
			}

			$GLOBALS['SHIPPING_FAILOVER'] = array();
			foreach ($include_quotes as $i => $class) {
				// if the chosen module is a failover module, and it's not included as one of the modules which has failed, then skip it
				if (isset($GLOBALS[$class]->failover) && $GLOBALS[$class]->failover && !in_array($class, $GLOBALS['SHIPPING_FAILOVER'])) continue;
				// run the quote
				$quotes = $GLOBALS[$class]->quote($method);
				// if we got a valid response...
				if (is_array($quotes) && $quotes) {
					// if this module is exclusive, overwrite earlier results and break out of the cycle so we're not processing later modules
					if (isset($GLOBALS[$class]->exclusive) && $GLOBALS[$class]->exclusive) {
						$quotes_array = array($quotes);
						break; // remove earlier ones and break out of later ones
					}
					// otherwise, just log the response
					else $quotes_array[] = $quotes;
				}
			}
			/*if (!empty($GLOBALS['SHIPPING_FAILOVER'])) {
				mail('jason.shinn, mike', 'SHIPPING MODULE FAILURE!!!', 'The following shipping failover modules were required: '."\n".implode("\n", $GLOBALS['SHIPPING_FAILOVER']), "From: alert@cablesandkits.com\r\n");
			}*/
		}

		return $quotes_array;
	}

	function cheapest() {
		if (is_array($this->modules)) {
			$rates = array();

            foreach($this->modules as $value) {
				$class = substr($value, 0, strrpos($value, '.'));
				if ($GLOBALS[$class]->enabled) {
					$quotes = isset($GLOBALS[$class]->quotes)?$GLOBALS[$class]->quotes:array('methods' => array());
					for ($i=0, $n=sizeof($quotes['methods']); $i<$n; $i++) {
						if (isset($quotes['methods'][$i]['cost']) && tep_not_null($quotes['methods'][$i]['cost'])) {
							$rates[] = array('id' => $quotes['id'].'_'.$quotes['methods'][$i]['id'], 'title' => $quotes['module'].' ('.$quotes['methods'][$i]['title'].')', 'cost' => $quotes['methods'][$i]['cost']);
							// echo $quotes['id'].'_'.$quotes['methods'][$i]['id'].'<p>';
						}
					}
				}
			}

			$cheapest = false;
			$ups_choice = false;
			for ($i=0, $n=sizeof($rates); $i<$n; $i++) {
				if (is_array($cheapest)) {
					if ($rates[$i]['id'] == 'upsxml_UPS Ground') {
						//if ($rates[$i]['cost'] < $cheapest['cost']) {
							$cheapest = $rates[$i];
							$ups_choice = true;

						//}
					}
				}
				else {
					$cheapest = $rates[$i];
				}
			}

			if ($ups_choice == false) {
				for ($i=0, $n=sizeof($rates); $i<$n; $i++) {
					if (is_array($cheapest)) {
						if ($rates[$i]['cost'] < $cheapest['cost']) {
							$cheapest = $rates[$i];
						}
					}
					else {
						$cheapest = $rates[$i];
					}
				}
			}

			return $cheapest;
		}
	}

	// BOF DEFAULT_SHIPPING_METHOD
	function shipping_default($title) {
		$shipping_default = NULL;
		if (is_array($this->modules)) {
			$monModule = array();
			foreach ($this->modules as $value) {
				$class = substr($value, 0, strrpos($value, '.'));
				if ($class == $title) {
					if ($GLOBALS[$class]->enabled) {
						$quotes = $GLOBALS[$class]->quotes;
						$monModule_cost = "";

						for ($i=0, $n=sizeof($quotes['methods']); $i<$n; $i++) {
							$monModule[] = array('id' => $quotes['id'].'_'.$quotes['methods'][$i]['id'], 'title' => $quotes['module'].' ('.$quotes['methods'][$i]['title'].')', 'cost' => $quotes['methods'][$i]['cost']);
							if (($monModule[$i]['cost'] < $monModule_cost ) || ($monModule_cost == '')) {
								$monModule_cost = $monModule[$i]['cost'];
								$shipping_default = $monModule[$i];
							}
						}
					}
				}
			}
		}
		return $shipping_default;
	}
	// EOF DEFAULT_SHIPPING_METHOD

}
?>
