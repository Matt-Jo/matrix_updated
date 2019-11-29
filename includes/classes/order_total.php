<?php
class order_total {

	var $modules;

	// class constructor
	function __construct() {
		if (defined('MODULE_ORDER_TOTAL_INSTALLED') && tep_not_null(MODULE_ORDER_TOTAL_INSTALLED)) {
			$this->modules = explode(';', preg_replace('/ot_subtotal.php;/', '', MODULE_ORDER_TOTAL_INSTALLED));

			foreach ($this->modules as $module) {
				include_once(DIR_WS_LANGUAGES.$_SESSION['language'].'/modules/order_total/'.$module);
				include_once(DIR_WS_MODULES.'order_total/'.$module);

				$class = substr($module, 0, strrpos($module, '.'));
				$GLOBALS[$class] = new $class;
			}
		}
	}

	function process() {
		$order_total_array = array();
		if (is_array($this->modules)) {
			foreach ($this->modules as $module) {
				$class = substr($module, 0, strrpos($module, '.'));
				if ($GLOBALS[$class]->enabled) {
					$GLOBALS[$class]->process();

					foreach ($GLOBALS[$class]->output as $i => $output) {
						if (!empty($output['title']) && !empty($output['text'])) {
							$order_total_array[] = array(
								'code' => $GLOBALS[$class]->code,
								'title' => $output['title'],
								'text' => $output['text'],
								'value' => $output['value'],
								'external_id' => (!empty($output['external_id'])?$output['external_id']:NULL), # 334
								'sort_order' => $GLOBALS[$class]->sort_order
							);
						}
					}
				}
			}
		}

		return $order_total_array;
	}

	function collect_posts() {
		if (MODULE_ORDER_TOTAL_INSTALLED) {
			foreach ($this->modules as $module) {
				$class = substr($module, 0, strrpos($module, '.'));
				if ($GLOBALS[$class]->enabled && !empty($GLOBALS[$class]->credit_class)) {
					$post_var = 'c'.$GLOBALS[$class]->code;
					if (!empty($_POST[$post_var])) {
						$_SESSION['post_var'] = $_POST[$post_var];
					}
					$GLOBALS[$class]->collect_posts();
				}
			}
		}
	}

	function pre_confirmation_check() {
		global $order;
		if (MODULE_ORDER_TOTAL_INSTALLED) {
			$total_deductions = 0;
			$order_total = $order->info['total'];
			foreach ($this->modules as $module) {
				$class = substr($module, 0, strrpos($module, '.'));
				if ($GLOBALS[$class]->enabled && !empty($GLOBALS[$class]->credit_class)) {
					$deduct = $GLOBALS[$class]->pre_confirmation_check($order_total);
					$total_deductions += $deduct;
					$order_total -= $deduct;
				}
			}
		}
	}
}
?>
