<?php
class forecast {

	public $stock_id;
	private $products_id;

	public $vendor_id;
	public $select_selected_vendor = FALSE;
	public $show_discontinued = FALSE;
	public $show_all_ipns = FALSE;

	public $ipns = array();
	public $vendorlist = array();

	const min_qty_factor = 1;
	const target_qty_factor = 1; // we don't give any margin of safety on the target qty
	const max_qty_factor = 1; // we don't give any margin of safety on the maximum qty

	public function __construct($stock_id=NULL, $products_id=NULL) {
		if (is_numeric($stock_id)) $this->stock_id = $stock_id;
		if (is_numeric($products_id)) $this->products_id = $products_id;

		if (!empty($_GET['vendor_id']) && is_numeric($_GET['vendor_id'])) $this->vendor_id = $_GET['vendor_id'];
		if (CK\fn::check_flag(@$_GET['select_selected_vendor'])) $this->select_selected_vendor = TRUE;
		if (CK\fn::check_flag(@$_GET['show_discontinued'])) $this->show_discontinued = TRUE;
		if (!empty($this->vendor_id) && strtolower(@$_GET['show_all_ipns']) == 'on') $this->show_all_ipns = TRUE;
	}

	public function daily_qty($data) {
		empty($data['3060_runrate'])?$data['3060_runrate']=@$data['p3060']/30:NULL;
		empty($data['30_runrate'])?$data['30_runrate']=@$data['to30']/30:NULL;
		if (!empty($data['30_runrate']) && !empty($data['3060_runrate'])) $data['060_runrate']=$data['30_runrate']+$data['3060_runrate'];
		else $data['060_runrate'] = NULL;
		// if we only have sales in the last 180 days, not in the past 60 days, then don't use zero as our median
		// if (is_null($data['3060_runrate']) && is_null($data['30_runrate'])) return $data['180_runrate'];
		// else return CK\math::median($data['180_runrate'], $data['3060_runrate'], $data['30_runrate']);

		// we are just hard coding these efforts for now. It might become a little more dynamic in the future. Everything above this was what was being used before
		// if the ipn is a rackkit then we are going to return the 0-60 day runrate otherwise we are returning 30 day
		if (!empty($data['060_runrate']) && !empty($data['products_stock_control_category_id']) && $data['products_stock_control_category_id'] == 47) return $data['060_runrate'];
		return $data['30_runrate']; // we are defaulting to 30 for now
	}

	public function min_qty_formula($data) {
		return max(0, ceil(($this->daily_qty($data) * $data['lead_factor']) * self::min_qty_factor));
	}

	public function target_qty_formula($data) {
		return max(0, ceil(($this->daily_qty($data) * $data['target_inventory_level']) * self::target_qty_factor));
	}

	public function max_qty_formula($data) {
		return max(0, ceil(($this->daily_qty($data) * $data['max_inventory_level']) * self::max_qty_factor));
	}

	public function reorder_qty_formula($data) {
		$max_reorder_adjust = $data['available_quantity']<0?abs($data['available_quantity']):0;
		return min((($data['target_qty'] + ceil($data['lead_time'] * $this->daily_qty($data)) - $data['on_order']) - $data['available_quantity']) - $data['quarantine_available_qty'], $data['target_qty'] + $max_reorder_adjust);
	}

	public function days_indicator_color($ipn, $days_supply) {

		$days_indicator = '0f0';
		$lower_warning_threshold = ($ipn['target_inventory_level'] - ($ipn['target_inventory_level'] - $ipn['min_inventory_level']) * .75);
		$upper_warning_threshold = ($ipn['max_inventory_level'] - ($ipn['max_inventory_level'] - $ipn['target_inventory_level']) * .25);

		if ($days_supply < $ipn['min_inventory_level'] || $days_supply > $ipn['max_inventory_level']) {
			$days_indicator = 'f33';
		}
		else if ($days_supply < $lower_warning_threshold ||
			$days_supply > $upper_warning_threshold) {
			$days_indicator = 'ee0';
		}
		return $days_indicator;
	}

	public function days_indicator_color_new($ipn, $days_supply) {

		$days_indicator = '0f0';
		$lower_warning_threshold = ($ipn['target_inventory_level'] - ($ipn['target_inventory_level'] - $ipn['min_inventory_level']) * .75);
		$upper_warning_threshold = ($ipn['max_inventory_level'] - ($ipn['max_inventory_level'] - $ipn['target_inventory_level']) * .25);
		if ($days_supply > $ipn['max_inventory_level']) {
			$days_indicator = '3366ff';
		}
		else if ($days_supply < $ipn['min_inventory_level']) {
			$days_indicator = 'f33';
		}
		else if ($days_supply > $upper_warning_threshold) {
			$days_indicator = '00ffff';
		}
		else if ($days_supply < $lower_warning_threshold) {
			$days_indicator = 'ee0';
		}
		return $days_indicator;
	}

	public function is_sufficient($sources, $obligations=1, $data=[]) {
		$source_qty = 0;
		if (is_numeric($sources)) $source_qty = $sources;
		elseif (is_array($sources) || $sources = [$sources]) {
			foreach ($sources as $source) {
				$source_qty += $data[$source];
			}
		}

		$obligation_qty = 0;
		if (is_numeric($obligations)) $obligation_qty = $obligations;
		elseif (is_array($obligations) || $obligations = [$obligations]) {
			foreach ($obligations as $obligation) {
				$obligation_qty += $data[$obligation];
			}
		}

		return $source_qty >= $obligation_qty;
	}

	public function build_report($all=NULL, $include_dropship=FALSE, $legacy=FALSE) {
		//debug_tools::mark('Overall Start');
		// since this is only run once, we can set show_all_ipns
		!is_null($all)?$this->show_all_ipns=$all:NULL;

		if ($this->stock_id) $ipns = [new ck_ipn2($this->stock_id)];
		else $ipns = ck_ipn2::get_ipns_for_purchase_management($this->show_all_ipns, $include_dropship);

		$today = new DateTime();

		$d30 = new DateTime();
		$d30->sub(new DateInterval('P30D'));

		$d60 = new DateTime();
		$d60->sub(new DateInterval('P60D'));

		$d180 = new DateTime();
		$d180->sub(new DateInterval('P180D'));

		$vendorlist = [];
		$number_of_parents = 0;
		foreach ($ipns as $idx => $ipn_raw) {
			$usage = ['0-30' => 0, '30-60' => 0, '0-180' => 0];
            if ($this->stock_id) $sales_history = $ipn_raw->get_sales_history_range($d180, $today);
			else $sales_history = $ipn_raw->get_sales_history();
			if (!empty($sales_history)) {
				foreach ($sales_history as $transaction) {
					if (!empty($transaction['exclude_forecast'])) continue;
					if (in_array($transaction['orders_status_id'], [6, 9])) continue;
					if ($transaction['date_purchased']->format('Y-m-d') < $d180->format('Y-m-d')) continue;

					$usage['0-180'] += $transaction['products_quantity'];

					if ($transaction['date_purchased']->format('Y-m-d') < $d60->format('Y-m-d')) continue;

					if ($transaction['date_purchased']->format('Y-m-d') < $d30->format('Y-m-d')) $usage['30-60'] += $transaction['products_quantity'];
					else $usage['0-30'] += $transaction['products_quantity'];
				}
			}

			if ($change_history = $ipn_raw->get_change_history()) {
				$conversions = ['gains' => [], 'losses' => []];
				foreach ($change_history as $transaction) {
					if (!in_array($transaction['change_code'], [41, 42])) continue;
					if ($transaction['change_date']->format('Y-m-d') < $d180->format('Y-m-d')) continue;
					if ($transaction['change_code'] == 42) {
						if (!isset($conversions['gains'][$transaction['change_date']->format('c')])) {
							$conversions['gains'][$transaction['change_date']->format('c')] = 0;
						}
						$conversions['gains'][$transaction['change_date']->format('c')] += abs($transaction['new_value'] - $transaction['old_value']);
					}
					elseif ($transaction['change_code'] == 41) {
						if (!isset($conversions['losses'][$transaction['change_date']->format('c')])) {
							$conversions['losses'][$transaction['change_date']->format('c')] = 0;
						}
						$conversions['losses'][$transaction['change_date']->format('c')] += abs($transaction['new_value'] - $transaction['old_value']);
					}
				}

				foreach ($conversions['losses'] as $date => $losses) {
					if (isset($conversions['gains'][$date]) && $losses == $conversions['gains'][$date]) continue;

					$usage['0-180'] += $losses;
					if ($date < $d60->format('c')) continue;

					if ($date < $d30->format('c')) $usage['30-60'] += $losses;
					else  $usage['0-30'] += $losses;
				}
			}

			// if we haven't sold it in the past 6 months, we don't want it
			if (!$this->show_all_ipns && !$this->is_sufficient($usage['0-180'])) continue;

			$header = $ipn_raw->get_header();
			$inventory = $ipn_raw->get_inventory();

			$ipn = array_merge($header, [
				'discontinued' => $ipn_raw->is('discontinued'),
				'to60' => $usage['0-30'] + $usage['30-60'],
				'to180' => $usage['0-180'],
				'display180' => ceil($usage['0-180']/6),
				'p3060' => $usage['30-60'],
				'to30' => $usage['0-30'],
				'last_special' => $ipn_raw->get_last_specials_date(),
				'180_runrate' => $usage['0-180']/180,
				'3060_runrate' => $usage['30-60']/30,
				'30_runrate' => $usage['0-30']/30,
				'060_runrate' => ($usage['0-30'] + $usage['30-60'])/60,
				// used to be max(min inventory level, lead time), now min inventory level + lead time // JMS 2018-02-08
				'lead_factor' => $legacy?max($header['min_inventory_level'], $header['lead_time']):$header['min_inventory_level'] + $header['lead_time'],
				'available_quantity' => $inventory['available'],
				'quarantine_qty' => $inventory['on_hold'] - $inventory['in_conditioning'],
				'quarantine_available_qty' => $inventory['in_conditioning'],
				'on_order' => 0,
				'parent_products_qty' => 0,
				'products_stock_control_category_id' => $header['products_stock_control_category_id']
			]);

			if (empty($ipn['target_inventory_level'])) $ipn['target_inventory_level'] = $ipn['max_inventory_level'];

			$ipn['display_last_special'] = !empty($ipn['last_special'])?$ipn['last_special']->format("M 'y"):'';

			if ($requiring_ipns = $ipn_raw->get_requiring_ipns()) {
				foreach ($requiring_ipns as $requiring_ipn) {
					$ipn['parent_products_qty'] += max($requiring_ipn->get_inventory('available'), 0);
				}
			}

			$ipn['adjusted_available_qty'] = $ipn['available_quantity'] - $ipn['parent_products_qty'];

			$ipn['target_min_qty'] = $this->min_qty_formula($ipn);
			$ipn['initial_target_qty'] = $this->target_qty_formula($ipn);
			$ipn['target_qty'] = $this->target_qty_formula($ipn);
			$ipn['target_max_qty'] = $this->max_qty_formula($ipn);

			$single_day = $this->daily_qty($ipn);
			$days_supply = empty($ipn['available_quantity'])?0:(empty($single_day)?999999:ceil($ipn['available_quantity']/$single_day));

			$dtarget = new DateTime();
			// there are a few products that "legitimately" do not have a target or max inventory level
			// need to use abs() because it's apparently possible to hit -0
			//if (!empty($single_day) && $days_supply >= 0 && $days_supply < $ipn['target_inventory_level']) $dtarget->add(new DateInterval('P'.abs($days_supply).'D'));
			/*else*/ // we rolled this change back because it was creating more of a disruption than help
			if (!empty($ipn['target_inventory_level'])) $dtarget->add(new DateInterval('P'.$ipn['target_inventory_level'].'D'));

			// do we have enough without on order qtys?
			if (!$this->show_all_ipns && $this->is_sufficient(['available_quantity', 'quarantine_available_qty'], 'target_min_qty', $ipn)) continue;

			// how much do we need to order without accounting for on order?
			$draft_reorder_qty = $this->reorder_qty_formula($ipn);

			if ($purchase_history = $ipn_raw->get_purchase_history()) {
				foreach ($purchase_history as $transaction) {
					if (!in_array($transaction['status_id'], [1, 2])) continue; // skip it if the PO isn't open or only partially received
					if ($transaction['quantity'] <= $transaction['quantity_received']) continue; // skip it if we've already received the entire line
					if ($transaction['expected_date']->format('Y-m-d') > $dtarget->format('Y-m-d')) continue; // if our order date is further out than the date we're targeting to, then we don't count it

					// this order date makes these qtys relevant
					$outstanding_qty = $transaction['quantity'] - $transaction['quantity_received'] - $transaction['allocated_quantity'];

					if ($outstanding_qty >= $draft_reorder_qty) {
						// this qty is sufficient to satisfy the entire needed amount, so we just need to cover until we receive this order
						if ($transaction['expected_date']->format('Y-m-d') <= $today->format('Y-m-d')) $new_target_inventory_level = 0;
						else $new_target_inventory_level = $today->diff($transaction['expected_date'])->format('%a');

						if ($new_target_inventory_level > $ipn['lead_factor']) {
							$ipn['target_inventory_level'] = $new_target_inventory_level;
							$ipn['target_qty'] = $this->target_qty_formula($ipn);
						}
						else $ipn['on_order'] += $outstanding_qty;
					}
					else $ipn['on_order'] += $outstanding_qty;

					$draft_reorder_qty = $this->reorder_qty_formula($ipn);
				}
			}

			// do we have enough *with* on order qtys?
			if (!$this->show_all_ipns && $this->is_sufficient(['available_quantity', 'on_order', 'quarantine_available_qty'], 'target_min_qty', $ipn)) continue;

			// by this point, this is an accurate number fully accounting for on order qtys
			$ipn['reorder_qty'] = $draft_reorder_qty;

			// if we're below our minimum, we should always have a reorder qty, but this is a double check
			if (!$this->show_all_ipns && !$this->is_sufficient($ipn['reorder_qty'])) continue; // if we aren't going to reorder any, we don't want it

			if ($this->is_sufficient(['available_quantity', 'on_order', 'quarantine_available_qty'], 'target_min_qty', $ipn) || !$this->is_sufficient($ipn['reorder_qty'])) $ipn['show_only_with_all'] = TRUE;
			else $ipn['show_only_with_all'] = FALSE;

			$ipn['export_vendor_name'] = preg_replace('/\s*,\s*/', ' ', $header['vendors_company_name']);
			$ipn['display_target_buy_price'] = money_format('%n', $header['target_buy_price']);

			// implement stock_id check or products_id check... products_id will require new data to be added to the query
			$ipn['display'] = TRUE; // default to showing it
			$ipn['checked'] = FALSE; // default to not checking it

			// we've selected a particular vendor, but this one doesn't match
			if ($this->vendor_id && $ipn['vendors_id'] != $this->vendor_id) {
				// if we're showing all IPNs and the vendor doesn't match, get rid of it, it'll be too much data for the page otherwise
				if ($this->show_all_ipns) continue;
				// otherwise, just hide it
				else $ipn['display'] = FALSE;
			}
			// we've selected a particular vendor, this one matches, and we want to auto select the checkboxes
			elseif ($this->vendor_id && $this->select_selected_vendor) $ipn['checked'] = TRUE;

			// we've not elected to show discontinued items, and this one is discontinued
			if (!$this->show_discontinued && $ipn['discontinued']) $ipn['display'] = FALSE;

			$this->vendorlist[$ipn['vendors_id']] = $ipn['vendors_company_name'];
			$this->ipns[] = $ipn;

			// if we're looping through more than a single IPN, clear out the memory
			if ($idx > 0) ck_ipn2::destroy_record($ipn['stock_id']); // manage memory
		}

		//debug_tools::mark('Overall End');

		return $this->ipns;
	}

	public function build_history($onlyvendors=FALSE, $params=NULL) {
		if (empty($onlyvendors)) {
			$last_180 = 'SELECT p.stock_id, SUM(op.products_quantity) as qty, MAX(op.products_quantity) as high FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id AND op.exclude_forecast = 0 JOIN products p ON op.products_id = p.products_id WHERE o.orders_status NOT IN (6, 9) AND TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 180';
			if ($this->stock_id) $last_180 .= ' AND p.stock_id = '.$this->stock_id;
			if ($params) $last_180 .= $params;
			$last_180 .= ' GROUP BY p.stock_id';
			$last_180_result = prepared_query::fetch($last_180);

			$to180_conversion = 'SELECT stock_id, ABS(SUM(new_value - old_value)) as quantity FROM products_stock_control_change_history WHERE type_id IN (41) AND TO_DAYS(change_date) >= TO_DAYS(NOW()) - 180';
			if ($this->stock_id) $to180_conversion .= ' AND stock_id = '.$this->stock_id;
			$to180_conversion .= ' GROUP BY stock_id';
			$to180_conversion_result = prepared_query::fetch($to180_conversion);

			$p30_60 = 'SELECT p.stock_id, SUM(op.products_quantity) as qty, MAX(op.products_quantity) as high FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id AND op.exclude_forecast = 0 JOIN products p ON op.products_id = p.products_id WHERE o.orders_status NOT IN (6, 9) AND TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 60 AND TO_DAYS(o.date_purchased) < TO_DAYS(NOW()) - 30';
			if ($this->stock_id) $p30_60 .= ' AND p.stock_id = '.$this->stock_id;
			if ($params) $p30_60 .= $params;
			$p30_60 .= ' GROUP BY p.stock_id';
			$p30_60_result = prepared_query::fetch($p30_60);

			$p30_60_conversion = 'SELECT stock_id, ABS(SUM(new_value - old_value)) as quantity FROM products_stock_control_change_history WHERE type_id IN (41) AND TO_DAYS(change_date) >= TO_DAYS(NOW()) - 60 AND TO_DAYS(change_date) < TO_DAYS(NOW()) - 30';
			if ($this->stock_id) $p30_60_conversion .= ' AND stock_id = '.$this->stock_id;
			$p30_60_conversion .= ' GROUP BY stock_id';
			$p30_60_conversion_result = prepared_query::fetch($p30_60_conversion);

			$last_30 = 'SELECT p.stock_id, SUM(op.products_quantity) as qty, MAX(op.products_quantity) as high FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id AND op.exclude_forecast = 0 JOIN products p ON op.products_id = p.products_id WHERE o.orders_status NOT IN (6, 9) AND TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 30';
			if ($this->stock_id) $last_30 .= ' AND p.stock_id = '.$this->stock_id;
			if ($params) $last_30 .= $params;
			$last_30 .= ' GROUP BY p.stock_id';
			$last_30_result = prepared_query::fetch($last_30);

			$to30_conversion = 'SELECT stock_id, ABS(SUM(new_value - old_value)) as quantity FROM products_stock_control_change_history WHERE type_id IN (41) AND TO_DAYS(change_date) >= TO_DAYS(NOW()) - 30';
			if ($this->stock_id) $to30_conversion .= ' AND stock_id = '.$this->stock_id;
			$to30_conversion .= ' GROUP BY stock_id';
			$to30_conversion_result = prepared_query::fetch($to30_conversion);

			$last_special = 'SELECT p.stock_id, MAX(UNIX_TIMESTAMP(o.date_purchased)) as last_special_date FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id WHERE op.price_reason = 2 AND o.orders_status NOT IN (6, 9) AND TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 180';
			if ($this->stock_id) $last_special .= ' AND p.stock_id = '.$this->stock_id;
			if ($params) $last_special .= $params;
			$last_special .= ' GROUP BY p.stock_id';
			$last_special_result = prepared_query::fetch($last_special);

			$ipn_history = array();

			// we don't have to be very comprehensive with how we set the values here, if an item hasn't sold in the last 180 days
			// it won't have sold in the last 30 or the previous 30 days either.
			foreach ($last_180_result as $ipn) {
				$ipn_history[$ipn['stock_id']] = array('to180' => $ipn['qty'], 'highto180' => $ipn['high'], /*'highdays180' => array_slice($high_days_180, 0, 6), 'lowdays180' => array_slice($high_days_180, -6, 6),*/ 'p3060' => 0, 'highp3060' => 0, /*'highdays3060' => array(), 'lowdays3060' => array(),*/ 'to30' => 0, 'highto30' => 0, /*'highdays30' => 0, 'lowdays30' => 0,*/ 'last_special' => NULL);
			}
			foreach ($to180_conversion_result as $ipn) {
				if (empty($ipn_history[$ipn['stock_id']])) $ipn_history[$ipn['stock_id']] = array('to180' => $ipn['quantity'], 'highto180' => 0, /*'highdays180' => array_slice($high_days_180, 0, 6), 'lowdays180' => array_slice($high_days_180, -6, 6),*/ 'p3060' => 0, 'highp3060' => 0, /*'highdays3060' => array(), 'lowdays3060' => array(),*/ 'to30' => 0, 'highto30' => 0, /*'highdays30' => 0, 'lowdays30' => 0,*/ 'last_special' => NULL);
				else $ipn_history[$ipn['stock_id']]['to180'] += $ipn['quantity'];
			}
			foreach ($p30_60_result as $ipn) {
				$ipn_history[$ipn['stock_id']]['p3060'] = $ipn['qty'];
				$ipn_history[$ipn['stock_id']]['highp3060'] = $ipn['high'];
				/*$ipn_history[$ipn['stock_id']]['highdays3060'] = $high_days_3060[0];
				$ipn_history[$ipn['stock_id']]['lowdays3060'] = end($high_days_3060);*/
			}
			foreach ($p30_60_conversion_result as $ipn) {
				$ipn_history[$ipn['stock_id']]['p3060'] += $ipn['quantity'];
			}
			foreach ($last_30_result as $ipn) {
				$ipn_history[$ipn['stock_id']]['to30'] = $ipn['qty'];
				$ipn_history[$ipn['stock_id']]['highto30'] = $ipn['high'];
				/*$ipn_history[$ipn['stock_id']]['highdays30'] = $high_days_30[0];
				$ipn_history[$ipn['stock_id']]['lowdays30'] = end($high_days_30);*/
			}
			foreach ($to30_conversion_result as $ipn) {
				$ipn_history[$ipn['stock_id']]['to30'] += $ipn['quantity'];
			}
			foreach ($last_special_result as $ipn) {
				$ipn_history[$ipn['stock_id']]['last_special'] = $ipn['last_special_date'];
			}

			return $ipn_history;
		}
		else {
			if ($params === TRUE) {
				// we actually want *all* the vendors
				// ideally we'd be using the Zend DB class all over, for now we'll leave the other ones as they are
				$vendors = prepared_query::fetch('SELECT DISTINCT v.vendors_id, v.vendors_company_name FROM vendors v ORDER BY v.vendors_company_name ASC', cardinality::SET);

				foreach ($vendors as $vendor) {
					$this->vendorlist[$vendor['vendors_id']] = $vendor['vendors_company_name'];
				}
			}
			else {
				$last_180 = 'SELECT DISTINCT v.vendors_id, v.vendors_company_name FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id JOIN vendors_to_stock_item vtsi ON psc.vendor_to_stock_item_id = vtsi.id JOIN vendors v ON vtsi.vendors_id = v.vendors_id WHERE o.orders_status NOT IN (6, 9) AND TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 180 ORDER BY v.vendors_company_name ASC';
				$last_180_result = prepared_query::fetch($last_180);

				foreach ($last_180_result as $vendor) {
					$this->vendorlist[$vendor['vendors_id']] = $vendor['vendors_company_name'];
				}
			}
		}
		return TRUE;
	}
}

function column_sort($a, $b) {
	if (is_numeric($a) && is_numeric($b)) {
		if ($a == $b) {
			return 0;
		}
		return ($a < $b) ? -1 : 1;
	}
	elseif (is_numeric($a)) {
		return -1;
	}
	elseif (is_numeric($b)) {
		return 1;
	}
	else {
		return 0;
	}
}
?>
