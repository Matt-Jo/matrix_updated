<?php
class ck_family_unit extends ck_archetype implements ck_merchandising_unit_interface {
	protected static $skeleton_type = 'ck_family_unit_type';

	protected static $queries = [
		'family_header' => [
			'qry' => 'SELECT family_unit_id, generic_model_number, name, description, homogeneous, active, date_created FROM ck_merchandising_family_units WHERE family_unit_id = :family_unit_id',
			'cardinality' => cardinality::ROW,
		],

		'variances' => [
			'qry' => 'SELECT fv.family_unit_variance_id, fv.field_name, fv.attribute_id, ak.attribute_key, fv.name, fv.descriptor, fv.group_on, fv.sort_order, fv.active, fv.date_created FROM ck_merchandising_family_unit_variances fv LEFT JOIN ck_attribute_keys ak ON fv.attribute_id = ak.attribute_key_id WHERE fv.family_unit_id = :family_unit_id ORDER BY fv.sort_order ASC, fv.group_on DESC',
			'cardinality' => cardinality::SET,
		],

		'siblings' => [
			'qry' => 'SELECT fs.family_unit_sibling_id, fs.stock_id, fs.model_number, fs.name, fs.description, fs.active, fs.date_created, IFNULL(pv.products_id, MIN(ipv.products_id)) as products_id FROM ck_merchandising_family_unit_siblings fs JOIN products_stock_control psc ON fs.stock_id = psc.stock_id LEFT JOIN ckv_product_viewable pv ON fs.products_id = pv.products_id AND pv.viewable_state = 2 LEFT JOIN ckv_product_viewable ipv ON fs.stock_id = ipv.stock_id AND ipv.viewable_state = 2 WHERE fs.family_unit_id = :family_unit_id GROUP BY fs.family_unit_sibling_id ORDER BY psc.stock_name ASC',
			'cardinality' => cardinality::SET,
		],

		'sibling_attributes' => [
			'qry' => 'SELECT DISTINCT a.stock_id, ak.description as key_name, ak.attribute_key_id, a.attribute_key, a.value, ak.itemprop FROM ck_merchandising_family_unit_siblings fs JOIN ck_attributes a ON fs.stock_id = a.stock_id JOIN products p ON fs.products_id = p.products_id AND p.products_status = 1 JOIN ck_attribute_keys ak ON a.attribute_key_id = ak.attribute_key_id JOIN ck_merchandising_family_unit_variances fv ON fs.family_unit_id = fv.family_unit_id AND ak.attribute_key_id = fv.attribute_id WHERE a.internal = 0 AND fs.family_unit_id = :family_unit_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'containers' => [
			'qry' => 'SELECT family_container_id FROM ck_merchandising_family_containers WHERE family_unit_id = :family_unit_id',
			'cardinality' => cardinality::COLUMN,
		],

		'active_families' => [
			'qry' => 'SELECT family_unit_id FROM ck_merchandising_family_units WHERE active = 1 ORDER BY name ASC',
			'cardinality' => cardinality::COLUMN,
		]
	];

	public function __construct($family_unit_id, ck_family_unit_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($family_unit_id);

		if (!$this->skeleton->built('family_unit_id')) $this->skeleton->load('family_unit_id', $family_unit_id);

		self::register($family_unit_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('family_unit_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$header = self::fetch('family_header', [':family_unit_id' => $this->id()]);
		}
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		$header['homogeneous'] = CK\fn::check_flag($header['homogeneous']);
		$header['active'] = CK\fn::check_flag($header['active']);
		$header['date_created'] = self::DateTime($header['date_created']);

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$this->skeleton->load('header', self::fetch('family_header', [':family_unit_id' => $this->id()]));
		$this->normalize_header();
	}

	private function build_variances() {
		$variances = self::fetch('variances', [':family_unit_id' => $this->id()]);

		$variances = array_map(function($var) {
			$var['target'] = !empty($var['attribute_id'])?'attribute':'field';
			$var['key'] = !empty($var['attribute_key'])?$var['attribute_key']:$var['field_name'];
			$var['group_on'] = CK\fn::check_flag($var['group_on']);
			$var['active'] = CK\fn::check_flag($var['active']);
			$var['date_created'] = self::DateTime($var['date_created']);

			if (empty($var['name'])) {
				$var['name_display'] = ucwords($var['key']);
				$var['name_key'] = preg_replace('/\s+/', '', $var['key']);
			}
			else {
				$var['name_display'] = ucwords($var['name']);
				$var['name_key'] = preg_replace('/\s+/', '', $var['name']);
			}

			return $var;
		}, $variances);

		$this->skeleton->load('variances', $variances);
	}

	private function build_siblings() {
		$siblings = self::fetch('siblings', [':family_unit_id' => $this->id()]);

		//ck_product_listing::preload(['headers'], array_filter(array_map(function($sib) { return $sib['products_id']; }, $siblings)));

		$siblings = array_map(function($sib) {
			$sib['active'] = CK\fn::check_flag($sib['active']);
			$sib['date_created'] = self::DateTime($sib['date_created']);
			return $sib;
		}, $siblings);

		$this->skeleton->load('siblings', $siblings);
	}

	private function build_sibling_attributes() {
		$sibattrs = self::fetch('sibling_attributes', [':family_unit_id' => $this->id()]);

		$sibling_attributes = [];

		foreach ($sibattrs as $attr) {
			if (empty($sibling_attributes[$attr['stock_id']])) $sibling_attributes[$attr['stock_id']] = [];
			$sibling_attributes[$attr['stock_id']][$attr['attribute_key_id']] = $attr;
		}

		$this->skeleton->load('sibling_attributes', $sibling_attributes);
	}

	private static function build_listing_attributes($unit_id, prepared_fields $products) {
		$a_crit = $products->parameters();
		array_unshift($a_crit, $unit_id);

		$attributes = prepared_query::fetch('SELECT DISTINCT fv.family_unit_variance_id, fv.name, a.products_id, a.stock_id, ak.description as key_name, ak.attribute_key_id, a.attribute_key, a.value, ak.itemprop FROM ck_attributes a JOIN ck_attribute_keys ak ON a.attribute_key_id = ak.attribute_key_id JOIN ck_merchandising_family_unit_variances fv ON ? = fv.family_unit_id AND ak.attribute_key_id = fv.attribute_id WHERE a.internal = 0 AND a.products_id IN ('.$products->select_values().')', cardinality::SET, $a_crit);

		$attrs = [];

		foreach ($attributes as $attribute) {
			if (empty($attrs[$attribute['products_id']])) $attrs[$attribute['products_id']] = [];
			$attrs[$attribute['products_id']][$attribute['attribute_key_id']] = $attribute;
		}

		return $attrs;
	}

	private static function build_listing_specials(prepared_fields $products) {
		$specials = prepared_query::fetch('SELECT s.products_id, s.specials_new_products_price FROM specials s LEFT JOIN specials s0 ON s.products_id = s0.products_id AND s.specials_id < s0.specials_id WHERE s0.specials_id IS NULL AND s.status = 1 AND s.products_id IN ('.$products->select_values().')', cardinality::SET, $products->parameters());

		$specs = [];

		foreach ($specials as $special) {
			$specs[$special['products_id']] = $special;
		}

		return $specs;
	}

	private static function build_listing_customer_prices(prepared_fields $products) {
		$cps = [];

		if (!empty($_SESSION['cart']) && $_SESSION['cart']->has_customer()) {
			$c_crit = $products->parameters();
			$c_crit[] = $_SESSION['cart']->get_customer()->id();

			$customer_prices = prepared_query::fetch('SELECT p.products_id, p.stock_id, itc.price FROM products p JOIN ipn_to_customers itc ON itc.stock_id = p.stock_id WHERE p.products_id IN ('.$products->select_values().') AND itc.customers_id = ?', cardinality::SET, $c_crit);

			foreach ($customer_prices as $price) {
				$cps[$price['products_id']] = $price;
			}
		}

		return $cps;
	}

	private static function build_listing_included_products(prepared_fields $products) {
		$includeds = prepared_query::fetch('SELECT pa.product_id as parent_products_id, pa.product_addon_id as products_id, pa.bundle_quantity, psc.is_bundle, psc.stock_price, psc.dealer_price, psc.wholesale_high_price, psc.wholesale_low_price FROM product_addons pa JOIN products p ON pa.product_addon_id = p.products_id JOIN products_description pd ON pa.product_addon_id = pd.products_id LEFT JOIN product_addon_data pad ON pa.product_addon_id = pad.product_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE pa.product_id IN ('.$products->select_values().') AND pa.included = 1 ORDER BY pa.product_id ASC', cardinality::SET, $products->parameters());

		return $includeds;

		/*$next_set = [];

		$ips = [];

		foreach ($includeds as $included) {
			$next_set[$included['products_id']] = $product_threads[$included['parent_products_id']];
			$ips[$included['products_id']] = $included;
		}

		$product_threads->append($next_set);

		return $ips;*/
	}

	/*private static function build_listing_inventory(prepared_fields $products) {
		$inventory = prepared_query::fetch('SELECT p.products_id, psc.stock_id, CASE WHEN psc.serialized = 1 THEN IFNULL(s.quantity, 0) ELSE psc.stock_quantity END as on_hand, psc.ca_allocated_quantity as ca_allocated, op.allocated - IFNULL(po.po_allocated, 0) as local_allocated, IFNULL(op.allocated, 0) + IFNULL(psc.ca_allocated_quantity, 0) - IFNULL(po.po_allocated, 0) as allocated, IFNULL(po.po_allocated, 0) as po_allocated, IFNULL(ih.on_hold, 0) as on_hold, CASE WHEN psc.serialized = 1 THEN IFNULL(s.quantity, 0) ELSE psc.stock_quantity END - (IFNULL(op.allocated, 0) + IFNULL(psc.ca_allocated_quantity, 0) - IFNULL(po.po_allocated, 0)) - IFNULL(ih.on_hold, 0) as available, CASE WHEN psc.serialized = 1 THEN IFNULL(s.quantity, 0) ELSE psc.stock_quantity END - IFNULL(ih.on_hold, 0) as salable, IFNULL(ic.in_conditioning, 0) as in_conditioning, psc.max_displayed_quantity, psc.on_order, psc.on_order - IFNULL(po.po_allocated, 0) as adjusted_on_order, NULL as adjusted_available_quantity FROM products_stock_control psc JOIN products p ON psc.stock_id = p.stock_id LEFT JOIN (SELECT ipn as stock_id, COUNT(id) as quantity FROM serials WHERE status IN (2, 3, 6) GROUP BY ipn) s ON psc.stock_id = s.stock_id LEFT JOIN (SELECT p.stock_id, SUM(op.products_quantity) as allocated FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id WHERE o.orders_status NOT IN (3, 6, 9) GROUP BY p.stock_id) op ON psc.stock_id = op.stock_id LEFT JOIN (SELECT p.stock_id, SUM(potoa.quantity) as po_allocated FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id JOIN purchase_order_to_order_allocations potoa ON op.orders_products_id = potoa.order_product_id AND potoa.purchase_order_product_id > 0 WHERE o.orders_status NOT IN (3, 6, 9) GROUP BY p.stock_id) po ON psc.stock_id = po.stock_id LEFT JOIN (SELECT ih.stock_id, SUM(ih.quantity) as on_hold FROM inventory_hold ih LEFT JOIN serials s ON ih.serial_id = s.id WHERE s.id IS NULL OR s.status = 6 GROUP BY ih.stock_id) ih ON psc.stock_id = ih.stock_id LEFT JOIN (SELECT stock_id, SUM(quantity) as in_conditioning FROM inventory_hold WHERE reason_id IN (4, 8, 11, 12) GROUP BY stock_id) ic ON psc.stock_id = ic.stock_id JOIN ck_merchandising_family_unit_siblings fs ON psc.stock_id = fs.stock_id WHERE p.products_id IN ('.$products->select_values().')', cardinality::SET, $products->parameters());

		$invs = [];

		foreach ($inventory as $inv) {
			$invs[$inv['products_id']] = $inv;
		}

		return $invs;
	}*/

	private static function build_listing_headers(prepared_fields $products, $preloop=FALSE) {
		$headers = prepared_query::fetch('SELECT p.products_id, p.stock_id, IFNULL(psc.image_reference, psc.stock_id) as image_reference_stock_id, pd.products_name, pd.products_head_desc_tag, p.products_model, psc.stock_price as retail_price, psc.dealer_price as reseller_price, psc.wholesale_high_price, psc.wholesale_low_price, psc.is_bundle, psc.bundle_price_flows_from_included_products, psc.bundle_price_modifier, psc.bundle_price_signum, vtsi.always_avail as always_available, vtsi.lead_time, psc.max_displayed_quantity, psc.conditions, c.conditions_name, psc.discontinued, psci.image as products_image, psci.image_med as products_image_med, psci.image_lrg as products_image_lrg, psci.image_sm_1 as products_image_sm_1, psci.image_xl_1 as products_image_xl_1, psci.image_sm_2 as products_image_sm_2, psci.image_xl_2 as products_image_xl_2, psci.image_sm_3 as products_image_sm_3, psci.image_xl_3 as products_image_xl_3, psci.image_sm_4 as products_image_sm_4, psci.image_xl_4 as products_image_xl_4, psci.image_sm_5 as products_image_sm_5, psci.image_xl_5 as products_image_xl_5, psci.image_sm_6 as products_image_sm_6, psci.image_xl_6 as products_image_xl_6, m.manufacturers_name FROM products_description pd JOIN products p ON pd.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN manufacturers m ON p.manufacturers_id = m.manufacturers_id LEFT JOIN vendors_to_stock_item vtsi ON vtsi.id = psc.vendor_to_stock_item_id LEFT JOIN conditions c ON psc.conditions = c.conditions_id LEFT JOIN products_stock_control_images psci ON psci.stock_id = IFNULL(psc.image_reference, psc.stock_id) WHERE p.products_id IN ('.$products->select_values().') AND pd.language_id = 1', cardinality::SET, $products->parameters());

		if ($preloop) {
			$hdrs = [];

			foreach ($headers as $header) {
				$hdrs[$header['products_id']] = ck_ipn2::normalize_pricing($header);
			}

			$headers = $hdrs;
		}

		return $headers;
	}

	private function build_listing_details() {
		$siblings = $this->get_siblings();

		$products_ids = array_filter(array_map(function($sib) { return $sib['products_id']; }, $siblings));

		$sel = new prepared_fields($products_ids, prepared_fields::SELECT_QUERY);

		$details = self::build_listing_headers($sel, TRUE);

		$included_details = [];
		$lookup_products_ids = array_map(function($prod) { return $prod['products_id']; }, array_filter($details, function($prod) { return $prod['is_bundle'] == 1; }));

		if (!empty($lookup_products_ids)) {
			$product_threads = new ck_product_generations($lookup_products_ids);

			array_map(function($pn) use ($details) {
				$pn->set_prop('bundle_price_method', $details[$pn->id()]['bundle_price_flows_from_included_products']);
				$pn->set_prop('bundle_price_modifier', $details[$pn->id()]['bundle_price_modifier']);
				$pn->set_prop('bundle_price_signum', $details[$pn->id()]['bundle_price_signum']);

				if ($details[$pn->id()]['bundle_price_flows_from_included_products'] >= 1) {
					$pn->set_prop('price_rollup', 0);
					$pn->set_prop('dealer_rollup', 0);
					$pn->set_prop('wholesale_high_rollup', 0);
					$pn->set_prop('wholesale_low_rollup', 0);
					$pn->set_prop('figure_bundle_pricing', TRUE);
				}
				else {
					$pn->set_prop('price_rollup', $details[$pn->id()]['retail_price']);
					$pn->set_prop('dealer_rollup', $details[$pn->id()]['reseller_price']);
					$pn->set_prop('wholesale_high_rollup', $details[$pn->id()]['wholesale_high_price']);
					$pn->set_prop('wholesale_low_rollup', $details[$pn->id()]['wholesale_low_price']);
				}
			}, $product_threads->get_generation(0));

			$next_set = TRUE;
		}

		while (!empty($next_set)) {
			$next_set = [];
			$level = $product_threads->get_current_generation();

			$lookup_products_ids = array_keys($level);
			$csel = new prepared_fields($lookup_products_ids, prepared_fields::SELECT_QUERY);

			if ($includeds = self::build_listing_included_products($csel)) {
				$child_products_ids = array_filter(array_map(function($pa) { return $pa['products_id']; }, $includeds));
				$csel = new prepared_fields($child_products_ids, prepared_fields::SELECT_QUERY);

				$cdetails = self::build_listing_headers($csel, TRUE);
				//$cinvs = self::build_listing_inventory($csel);

				foreach ($includeds as $included) {
					$products_id = $included['products_id'];
					$parent_node = $level[$included['parent_products_id']];

					$node = new ck_product_node($products_id, $parent_node);

					$inv = ck_ipn2::get_inventory_direct($cdetails[$included['products_id']]['stock_id']);

					// do we include it in the next set
					if ($included['is_bundle'] == 1) {
						$node->set_prop('bundle_qty_point', TRUE);
						$node->set_prop('bundle_multiplier', $included['bundle_quantity']);
						$next_set[$products_id] = $node;
					}
					else {
						// handle available quantity, if this isn't a bundle
						$bundle_quantity = floor($inv['available'] / $included['bundle_quantity']);

						if ($parent_node->has_prop_tree('bundle_qty_point')) {
							// we have a midpoint to roll up to
							$pn = $parent_node->get_next_prop_node('bundle_qty_point');
							if (!$pn->has_prop('bundle_qty_total')) $max_available = $bundle_quantity;
							else $max_available = $pn->get_prop('bundle_qty_total');

							$pn->set_prop('bundle_qty_total', min($max_available, $bundle_quantity));
						}
						else {
							// we're rolling all the way up to the progenitor
							$progenitor = $parent_node->progenitor();
							if (!$progenitor->has_prop('max_available_quantity_including_accessories')) $max_available = $bundle_quantity;
							else $max_available = $progenitor->get_prop('max_available_quantity_including_accessories');

							$progenitor->set_prop('max_available_quantity_including_accessories', min($max_available, $bundle_quantity));
						}
					}

					// if the first generation flows pricing from its children, and we haven't already finalized the price for this branch of the tree, figure pricing
					if ($parent_node->has_prop_tree('figure_bundle_pricing') && !$parent_node->has_prop_tree('price_halt')) {
						if ($included['is_bundle'] == 1 && $cdetails[$products_id]['bundle_price_flows_from_included_products'] >= 1) {
							// if this is a bundle and pricing flows from its children, just flag it with the necessary details and move on
							$node->set_prop('bundle_price_point', TRUE);
							$node->set_prop('bundle_price_method', $cdetails[$products_id]['bundle_price_flows_from_included_products']);
							$node->set_prop('bundle_price_modifier', $cdetails[$products_id]['bundle_price_modifier']);
							$node->set_prop('bundle_price_signum', $cdetails[$products_id]['bundle_price_signum']);
							//$node->set_prop('bundle_multiplier', $included['bundle_quantity']); // already set above
							$node->set_prop('bundle_price_base', 0); // the unmodified unit price
							$node->set_prop('bundle_price_base_dealer', 0); // the unmodified unit dealer price
							$node->set_prop('bundle_price_base_wholesale_high', 0); // the unmodified unit wholesale high price
							$node->set_prop('bundle_price_base_wholesale_low', 0); // the unmodified unit wholesale low price
						}
						else {
							// this is not a bundle, or the price is set on it directly, we don't need to figure pricing below this point
							$node->set_prop('price_halt', TRUE);

							if ($parent_node->has_prop_tree('bundle_price_point')) {
								// we have a midpoint to roll up to
								$pn = $parent_node->get_next_prop_node('bundle_price_point');
								$base_price = $pn->get_prop('bundle_price_base');
								$base_price = bcadd($base_price, bcmul($cdetails[$products_id]['retail_price'], $included['bundle_quantity'], 6), 6);
								$pn->set_prop('bundle_price_base', $base_price);

								$base_price_dealer = $pn->get_prop('bundle_price_base_dealer');
								$base_price_dealer = bcadd($base_price_dealer, bcmul($cdetails[$products_id]['reseller_price'], $included['bundle_quantity'], 6), 6);
								$pn->set_prop('bundle_price_base_dealer', $base_price_dealer);

								$base_price_wholesale_high = $pn->get_prop('bundle_price_base_wholesale_high');
								$base_price_wholesale_high = bcadd($base_price_wholesale_high, bcmul($cdetails[$products_id]['wholesale_high_price'], $included['bundle_quantity'], 6), 6);
								$pn->set_prop('bundle_price_base_wholesale_high', $base_price_wholesale_high);

								$base_price_wholesale_low = $pn->get_prop('bundle_price_base_wholesale_low');
								$base_price_wholesale_low = bcadd($base_price_wholesale_low, bcmul($cdetails[$products_id]['wholesale_low_price'], $included['bundle_quantity'], 6), 6);
								$pn->set_prop('bundle_price_base_wholesale_low', $base_price_wholesale_low);
							}
							else {
								// we're rolling all the way up to the progenitor
								$progenitor = $node->progenitor();
								$price_rollup = $progenitor->get_prop('price_rollup');
								$dealer_rollup = $progenitor->get_prop('dealer_rollup');
								$wholesale_high_rollup = $progenitor->get_prop('wholesale_high_rollup');
								$wholesale_low_rollup = $progenitor->get_prop('wholesale_low_rollup');

								$price_rollup = bcadd($price_rollup, bcmul($cdetails[$products_id]['retail_price'], $included['bundle_quantity'], 6), 6);
								$dealer_rollup = bcadd($dealer_rollup, bcmul($cdetails[$products_id]['reseller_price'], $included['bundle_quantity'], 6), 6);
								$wholesale_high_rollup = bcadd($wholesale_high_rollup, bcmul($cdetails[$products_id]['wholesale_high_price'], $included['bundle_quantity'], 6), 6);
								$wholesale_low_rollup = bcadd($wholesale_low_rollup, bcmul($cdetails[$products_id]['wholesale_low_price'], $included['bundle_quantity'], 6), 6);

								$progenitor->set_prop('price_rollup', $price_rollup);
								$progenitor->set_prop('dealer_rollup', $dealer_rollup);
								$progenitor->set_prop('wholesale_high_rollup', $wholesale_high_rollup);
								$progenitor->set_prop('wholesale_low_rollup', $wholesale_low_rollup);
							}
						}
					}
				}

				$product_threads->append_generation($next_set);
			}
		}

		if (!empty($product_threads)) {
			foreach ($product_threads->get_generation(0) as $bundle_node) {
				foreach ($bundle_node->get_family($reverse=TRUE) as $child_nodes) {
					foreach ($child_nodes as $child_node) {
						if ($child_node->is_progenitor()) break;

						$props = $child_node->get_props();

						if (!$child_node->has_prop('bundle_qty_point')) continue; // nothing of interest stored here - due to the nature of what we're doing, they'll all be qty stop points

						// we figure qty for every bundle stop point
						$available_bundle_qty = $props['bundle_qty_total'] / $props['bundle_multiplier'];

						// figure qty
						if ($child_node->previous()->has_prop_tree('bundle_qty_point')) {
							// we have a midpoint to roll up to
							$pn = $child_node->previous()->get_next_prop_node('bundle_qty_point');
							if (!$pn->has_prop('bundle_qty_total')) $max_available = $bundle_quantity;
							else $max_available = $pn->get_prop('bundle_qty_total');

							$pn->set_prop('bundle_qty_total', min($max_available, $bundle_quantity));
						}
						else {
							// we're rolling all the way up to the progenitor
							if (!$bundle_node->has_prop('max_available_quantity_including_accessories')) $max_available = $available_bundle_qty;
							else $max_available = $bundle_node->get_prop('max_available_quantity_including_accessories');

							$bundle_node->set_prop('max_available_quantity_including_accessories', min($max_available, $available_bundle_qty));
						}

						// figure price only for specifically marked bundle stop points
						if ($child_node->has_prop('bundle_price_point')) {
							$base_price = $props['bundle_price_base'];
							$base_dealer_price = $props['bundle_price_base_dealer'];
							$base_wholesale_high_price = $props['bundle_price_base_wholesale_high'];
							$base_wholesale_low_price = $props['bundle_price_base_wholesale_low'];

							$unit_price = ck_product_listing::modify_bundle_price($base_price, $props['bundle_price_method'], $props['bundle_price_modifier'], $props['bundle_price_signum']);
							$unit_dealer_price = ck_product_listing::modify_bundle_price($base_dealer_price, $props['bundle_price_method'], $props['bundle_price_modifier'], $props['bundle_price_signum']);
							$unit_wholesale_high_price = ck_product_listing::modify_bundle_price($base_wholesale_high_price, $props['bundle_price_method'], $props['bundle_price_modifier'], $props['bundle_price_signum']);
							$unit_wholesale_low_price = ck_product_listing::modify_bundle_price($base_wholesale_low_price, $props['bundle_price_method'], $props['bundle_price_modifier'], $props['bundle_price_signum']);

							$total_price = bcmul($unit_price, $props['bundle_multiplier'], 6);
							$total_dealer_price = bcmul($unit_dealer_price, $props['bundle_multiplier'], 6);
							$total_wholesale_high_price = bcmul($unit_wholesale_high_price, $props['bundle_multiplier'], 6);
							$total_wholesale_low_price = bcmul($unit_wholesale_low_price, $props['bundle_multiplier'], 6);

							if ($child_node->previous()->has_prop_tree('bundle_price_point')) {
								// we have a midpoint to roll up to
								$pn = $child_node->previous()->get_next_prop_node('bundle_price_point');
								$base_price = $pn->get_prop('bundle_price_base');
								$base_price_dealer = $pn->get_prop('bundle_price_base_dealer');
								$base_price_wholesale_high = $pn->get_prop('bundle_price_base_wholesale_high');
								$base_price_wholesale_low = $pn->get_prop('bundle_price_base_wholesale_low');

								$base_price = bcadd($base_price, $total_price, 6);
								$base_price_dealer = bcadd($base_price_dealer, $total_dealer_price, 6);
								$base_price_wholesale_high = bcadd($base_price_wholesale_high, $total_wholesale_high_price, 6);
								$base_price_wholesale_low = bcadd($base_price_wholesale_low, $total_wholesale_low_price, 6);

								$pn->set_prop('bundle_price_base', $base_price);
								$pn->set_prop('bundle_price_base_dealer', $base_price_dealer);
								$pn->set_prop('bundle_price_base_wholesale_high', $base_price_wholesale_high);
								$pn->set_prop('bundle_price_base_wholesale_low', $base_price_wholesale_low);
							}
							else {
								// we're rolling all the way up to the progenitor
								$progenitor = $node->progenitor();
								$price_rollup = $progenitor->get_prop('price_rollup');
								$dealer_rollup = $progenitor->get_prop('dealer_rollup');
								$wholesale_high_rollup = $progenitor->get_prop('wholesale_high_rollup');
								$wholesale_low_rollup = $progenitor->get_prop('wholesale_low_rollup');

								$price_rollup = bcadd($price_rollup, $total_price, 6);
								$dealer_rollup = bcadd($dealer_rollup, $total_dealer_price, 6);
								$wholesale_high_rollup = bcadd($wholesale_high_rollup, $total_wholesale_high_price, 6);
								$wholesale_low_rollup = bcadd($wholesale_low_rollup, $total_wholesale_low_price, 6);

								$progenitor->set_prop('price_rollup', $price_rollup);
								$progenitor->set_prop('dealer_rollup', $dealer_rollup);
								$progenitor->set_prop('wholesale_high_rollup', $wholesale_high_rollup);
								$progenitor->set_prop('wholesale_low_rollup', $wholesale_low_rollup);
							}
						}
					}
				}
			}

			$bundle_rollups = array_map(function($b) {
				$props = $b->get_props();
				return [
					'undiscounted_price' => $props['price_rollup'],
					'undiscounted_dealer' => $props['dealer_rollup'],
					'undiscounted_wholesale_high' => $props['wholesale_high_rollup'],
					'undiscounted_wholesale_low' => $props['wholesale_low_rollup'],

					'price' => ck_product_listing::modify_bundle_price($props['price_rollup'], $props['bundle_price_method'], $props['bundle_price_modifier'], $props['bundle_price_signum']),
					'dealer' => ck_product_listing::modify_bundle_price($props['dealer_rollup'], $props['bundle_price_method'], $props['bundle_price_modifier'], $props['bundle_price_signum']),
					'wholesale_high' => ck_product_listing::modify_bundle_price($props['wholesale_high_rollup'], $props['bundle_price_method'], $props['bundle_price_modifier'], $props['bundle_price_signum']),
					'wholesale_low' => ck_product_listing::modify_bundle_price($props['wholesale_low_rollup'], $props['bundle_price_method'], $props['bundle_price_modifier'], $props['bundle_price_signum']),

					'available' => $props['max_available_quantity_including_accessories'],
				];
			}, $product_threads->get_generation(0));
		}

		$attrs = self::build_listing_attributes($this->id(), $sel);
		$specs = self::build_listing_specials($sel);
		$cps = self::build_listing_customer_prices($sel);
		//$invs = self::build_listing_inventory($sel);

		$listing_details = [];

		foreach ($siblings as $sibling) {
			if (empty($sibling['products_id']) || empty($details[$sibling['products_id']])) continue;
			$detail = $details[$sibling['products_id']];
			$listing = [
				'family_unit_sibling_id' => $sibling['family_unit_sibling_id'],
				'stock_id' => $sibling['stock_id'],
				'products_id' => $sibling['products_id'],
				'products_model' => $detail['products_model'],
				'products_name' => $detail['products_name'],
				'products_head_desc_tag' => $detail['products_head_desc_tag'],
				'always_available' => $detail['always_available'],
				'lead_time' => $detail['lead_time'],
				'conditions' => $detail['conditions'],
				'conditions_name' => $detail['conditions_name'],
				'discontinued' => $detail['discontinued'],
				'images' => [
					'products_image' => $detail['products_image'],
					'products_image_med' => $detail['products_image_med'],
					'products_image_lrg' => $detail['products_image_lrg'],
					'products_image_sm_1' => $detail['products_image_sm_1'],
					'products_image_xl_1' => $detail['products_image_xl_1'],
					'products_image_sm_2' => $detail['products_image_sm_2'],
					'products_image_xl_2' => $detail['products_image_xl_2'],
					'products_image_sm_3' => $detail['products_image_sm_3'],
					'products_image_xl_3' => $detail['products_image_xl_3'],
					'products_image_sm_4' => $detail['products_image_sm_4'],
					'products_image_xl_4' => $detail['products_image_xl_4'],
					'products_image_sm_5' => $detail['products_image_sm_5'],
					'products_image_xl_5' => $detail['products_image_xl_5'],
					'products_image_sm_6' => $detail['products_image_sm_6'],
					'products_image_xl_6' => $detail['products_image_xl_6'],
				],
				'prices' => [],
				'inventory' => [],
				'has_special' => FALSE,
			];

			$prices = [
				'original' => $detail['retail_price'],
				'dealer' => $detail['reseller_price'],
				'wholesale_high' => $detail['wholesale_high_price'],
				'wholesale_low' => $detail['wholesale_low_price'],
				'special' => NULL,
				'customer' => NULL,
				'bundle_rollup' => 0,
			];

			if (!empty($specs[$detail['products_id']])) {
				$prices['special'] = $specs[$detail['products_id']]['specials_new_products_price'];
				$listing['has_special'] = TRUE;
			}

			if (!empty($cps[$detail['products_id']])) {
				$prices['customer'] = $cps[$detail['products_id']]['price'];
			}

			if ($detail['is_bundle'] == 1 && $detail['bundle_price_flows_from_included_products'] >= 1) {
				$prices['bundle_rollup'] = 1;

				$prices['bundle_original'] = $bundle_rollups[$detail['products_id']]['undiscounted_price'];
				$prices['bundle_dealer'] = $bundle_rollups[$detail['products_id']]['undiscounted_dealer'];
				$prices['bundle_wholesale_high'] = $bundle_rollups[$detail['products_id']]['undiscounted_wholesale_high'];
				$prices['bundle_wholesale_low'] = $bundle_rollups[$detail['products_id']]['undiscounted_wholesale_low'];

				$prices['original'] = $bundle_rollups[$detail['products_id']]['price'];
				$prices['dealer'] = $bundle_rollups[$detail['products_id']]['dealer'];
				$prices['wholesale_high'] = $bundle_rollups[$detail['products_id']]['wholesale_high'];
				$prices['wholesale_low'] = $bundle_rollups[$detail['products_id']]['wholesale_low'];
			}

			ck_product_listing::select_price($prices);

			$listing['prices'] = $prices;

			$inv = ck_ipn2::get_inventory_direct($detail['stock_id']);

			if ($detail['is_bundle'] == 1) $inv['available'] = $bundle_rollups[$detail['products_id']]['available'];

			$inventory = [
				'on_hand' => $inv['on_hand'],
				'allocated' => $inv['allocated'],
				'on_hold' => $inv['on_hold'],
				'available' => $inv['available'],
				'on_order' => $inv['on_order'],
				'adjusted_on_order' => $inv['adjusted_on_order'],
			];

			if ($inv['max_displayed_quantity'] > 0 && $inv['max_displayed_quantity'] < $inv['available']) {
				$inventory['display_available_num'] = $inv['max_displayed_quantity'];
				$inventory['display_available'] = number_format($inv['max_displayed_quantity']).'+';
			}
			else {
				$inventory['display_available_num'] = $inv['available'];
				$inventory['display_available'] = number_format($inv['available']);
			}

			$listing['inventory'] = $inventory;

			$listing['attributes'] = $attrs[$detail['products_id']];

			$listing['schema'] = [
				'mpn' => $listing['products_model'], // model #
				'name' => htmlspecialchars($listing['products_name']),
				'sku' => $listing['products_id'], // product ID
				'url' => NULL,
				'image' => $listing['images']['products_image_lrg'],
				'brand' => $detail['manufacturers_name'],
				'price' => $listing['prices']['display'],
				'price_currency' => 'USD',
				'availability' => $listing['inventory']['display_available_num']>0?'InStock':'OutOfStock',
				'condition' => ck_ipn2::get_condition_name($listing['conditions'], 'meta'),
				'inventory_level' => $listing['inventory']['display_available_num'],
				'description' => '',
				'warranty' => '100 years',
			];

			$listing_details[] = $listing;
		}

		$this->skeleton->load('listing_details', $listing_details);
	}

	private function build_listing_variant_options() {
		$lvos = prepared_query::fetch('SELECT fv.family_unit_variance_id, fv.field_name, fv.name, fs.family_unit_sibling_id, fs.stock_id, IFNULL(pv.products_id, MIN(ipv.products_id)) as sibling_products_id, ak.attribute_key, ak.description as key_name, ak.attribute_key_id, a.attribute_key, a.value, ak.itemprop, a.products_id as attribute_products_id FROM ck_merchandising_family_unit_variances fv JOIN ck_attribute_keys ak ON fv.attribute_id = ak.attribute_key_id JOIN ck_attributes a ON ak.attribute_key_id = a.attribute_key_id JOIN ck_merchandising_family_unit_siblings fs ON fv.family_unit_id = fs.family_unit_id AND a.stock_id = fs.stock_id LEFT JOIN ckv_product_viewable pv ON fs.products_id = pv.products_id AND pv.viewable_state = 2 LEFT JOIN ckv_product_viewable ipv ON fs.stock_id = ipv.stock_id AND ipv.viewable_state = 2 WHERE fv.family_unit_id = :family_unit_id AND fv.active = 1 GROUP BY fs.family_unit_sibling_id, fv.family_unit_variance_id HAVING sibling_products_id = attribute_products_id', cardinality::SET, [':family_unit_id' => $this->id()]);

		$options = [];

		foreach ($lvos as $lvo) {
			if (empty($options[$lvo['family_unit_variance_id']])) $options[$lvo['family_unit_variance_id']] = [];
			if (empty($options[$lvo['family_unit_variance_id']][$lvo['value']])) $options[$lvo['family_unit_variance_id']][$lvo['value']] = [];

			$options[$lvo['family_unit_variance_id']][$lvo['value']][] = $lvo;
		}

		// we know this one is going to be condition, so go ahead and grab that value with it
		$lvos = prepared_query::fetch('SELECT fv.family_unit_variance_id, fv.field_name, fv.name, fs.family_unit_sibling_id, fs.stock_id, IFNULL(pv.products_id, MIN(ipv.products_id)) as sibling_products_id, psc.conditions as condition_id FROM ck_merchandising_family_unit_variances fv JOIN ck_merchandising_family_unit_siblings fs ON fv.family_unit_id = fs.family_unit_id JOIN products_stock_control psc ON fs.stock_id = psc.stock_id LEFT JOIN ckv_product_viewable pv ON fs.products_id = pv.products_id AND pv.viewable_state = 2 LEFT JOIN ckv_product_viewable ipv ON fs.stock_id = ipv.stock_id AND ipv.viewable_state = 2 WHERE fv.family_unit_id = :family_unit_id AND fv.attribute_id IS NULL AND fv.active = 1 GROUP BY fs.family_unit_sibling_id, fv.family_unit_variance_id HAVING sibling_products_id IS NOT NULL', cardinality::SET, [':family_unit_id' => $this->id()]);

		foreach ($lvos as $lvo) {
			if (empty($options[$lvo['family_unit_variance_id']])) $options[$lvo['family_unit_variance_id']] = [];
			if (empty($options[$lvo['family_unit_variance_id']][$lvo['condition_id']])) $options[$lvo['family_unit_variance_id']][$lvo['condition_id']] = [];

			$options[$lvo['family_unit_variance_id']][$lvo['condition_id']][] = $lvo;
		}

		$this->skeleton->load('listing_variant_options', $options);
	}

	private function build_containers() {
		$container_ids = self::fetch('containers', [':family_unit_id' => $this->id()]);

		$containers = [];

		foreach ($container_ids as $container_id) {
			$containers[] = new ck_family_container($container_id);
		}

		$this->skeleton->load('containers', $containers);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	public function has_variances($key='active') {
		if (!$this->skeleton->built('variances')) $this->build_variances();
		if (empty($key)) return $this->skeleton->has('variances');
		else {
			if ($key == 'active') {
				foreach ($this->skeleton->get('variances') as $variance) {
					if ($variance['active']) return TRUE;
				}
				return FALSE;
			}
		}
	}

	public function get_variances($key='active') {
		if (!$this->has_variances($key)) return [];
		if (empty($key)) return $this->skeleton->get('variances');
		else {
			if ($key == 'active') {
				$variances = [];
				foreach ($this->skeleton->get('variances') as $variance) {
					if ($variance['active']) $variances[] = $variance;
				}
				return $variances;
			}
		}
	}

	public function has_grouped_variance() {
		foreach ($this->get_variances() as $variance) {
			if ($variance['group_on']) return TRUE;
		}
		return FALSE;
	}

	public function has_siblings($key='active') {
		if (!$this->skeleton->built('siblings')) $this->build_siblings();
		if (empty($key)) return $this->skeleton->has('siblings');
		else {
			if ($key == 'active') {
				foreach ($this->skeleton->get('siblings') as $siblings) {
					if ($siblings['active']) return TRUE;
				}
				return FALSE;
			}
			elseif (is_numeric($key)) {
				foreach ($this->skeleton->get('siblings') as $sibling) {
					if ($sibling['family_unit_sibling_id'] == $key) return TRUE;
				}
				return FALSE;
			}
		}
	}

	public function get_siblings($key='active') {
		if (!$this->has_siblings($key)) return [];
		if (empty($key)) return $this->skeleton->get('siblings');
		else {
			if ($key == 'active') {
				return array_filter($this->skeleton->get('siblings'), function($sib) { return $sib['active']; });
			}
			elseif (is_numeric($key)) {
				foreach ($this->skeleton->get('siblings') as $sibling) {
					if ($sibling['family_unit_sibling_id'] == $key) return $sibling;
				}
			}
		}
	}

	public function get_sibling_attributes($stock_id=NULL) {
		if (!$this->skeleton->built('sibling_attributes')) $this->build_sibling_attributes();
		if (empty($stock_id)) return $this->skeleton->get('sibling_attributes');
		else return $this->skeleton->get('sibling_attributes', $stock_id);
	}

	public function get_listing_details() {
		if (!$this->skeleton->built('listing_details')) $this->build_listing_details();
		return $this->skeleton->get('listing_details');
	}

	public function get_keyed_listing_details($key='family_unit_sibling_id') {
		$lds = [];
		
		foreach ($this->get_listing_details() as $ld) {
			$lds[$ld[$key]] = $ld;
		}

		return $lds;
	}

	public function get_listing_variant_options() {
		if (!$this->skeleton->built('listing_variant_options')) $this->build_listing_variant_options();
		return $this->skeleton->get('listing_variant_options');
	}

	public function has_containers($key='active') {
		if (!$this->skeleton->built('containers')) $this->build_containers();
		if (empty($key)) return $this->skeleton->has('containers');
		else {
			if ($key == 'active') {
				foreach ($this->skeleton->get('containers') as $container) {
					if ($container->is_active()) return TRUE;
				}
				return FALSE;
			}
			elseif (is_numeric($key)) {
				foreach ($this->skeleton->get('containers') as $container) {
					if ($container->id() == $key) return TRUE;
				}
				return FALSE;
			}
		}
	}

	public function get_containers($key='active') {
		if (!$this->has_containers($key)) return [];
		if (empty($key)) return $this->skeleton->get('container');
		else {
			if ($key == 'active') {
				$containers = [];
				foreach ($this->skeleton->get('containers') as $container) {
					if ($container->is_active()) $containers[] = $container;
				}
				return $containers;
			}
			elseif (is_numeric($key)) {
				foreach ($this->skeleton->get('containers') as $container) {
					if ($container->id() == $key) return $container;
				}
			}
		}
	}

	public function is_active() {
		if (!$this->is('active')) return FALSE;

		// we're gonna take a more optimized approach to checking for active siblings, since we may not need to load them all just for this
		if ($this->skeleton->built('siblings')) return $this->has_siblings('active');

		// run custom query for any active siblings
		return !empty(self::query_fetch('SELECT family_unit_sibling_id FROM ck_merchandising_family_unit_siblings WHERE family_unit_id = :family_unit_id AND active = 1', cardinality::SINGLE, [':family_unit_id' => $this->id()]));
	}

	public static function get_active_families() {
		if ($family_unit_ids = self::fetch('active_families')) {
			$families = [];
			foreach ($family_unit_ids as $family_unit_id) {
				$families[] = new self($family_unit_id);
			}
			return $families;
		}
		else return NULL;
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public static function create(Array $data) {
		$savepoint = self::transaction_begin();

		try {
			if (!isset($data['header']['generic_model_number'])) $data['header']['generic_model_number'] = NULL;
			if (!isset($data['header']['description'])) $data['header']['description'] = NULL;
			$header = CK\fn::parameterize($data['header']);
			self::query_execute('INSERT INTO ck_merchandising_family_units (generic_model_number, name, description, homogeneous) VALUES (:generic_model_number, :name, :description, :homogeneous)', cardinality::NONE, $header);
			$family_unit_id = self::fetch_insert_id();

			$family = new self($family_unit_id);

			if (!empty($data['variances'])) {
				foreach ($data['variances'] as $variance) {
					$family->create_variance($variance);
				}
			}

			if (!empty($data['siblings'])) {
				foreach ($data['siblings'] as $sibling) {
					$family->create_sibling($sibling);
				}
			}

			self::transaction_commit($savepoint);
			return $family;
		}
		catch (CKFamilyUnitException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyUnitException('Failed to create family: '.$e->getMessage());
		}
	}

	public function create_variance(Array $data) {
		$savepoint = self::transaction_begin();

		try {
			$params = new ezparams($data);
			self::query_execute('INSERT INTO ck_merchandising_family_unit_variances ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, $params->query_vals(NULL, TRUE));
			$family_unit_variance_id = self::fetch_insert_id();

			$this->skeleton->rebuild('variances');

			self::transaction_commit($savepoint);
			return $family_unit_variance_id;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyUnitException('Failed to create variance: '.$e->getMessage());
		}
	}

	public function create_sibling(Array $data) {
		$savepoint = self::transaction_begin();

		try {
			$ipn = new ck_ipn2($data['stock_id']);
			if ($ipn->has_listings() && (!empty($data['products_id']) || $listing = $ipn->get_default_listing())) {
				if (empty($data['products_id'])) $data['products_id'] = $listing->id();
				$params = new ezparams($data);
				self::query_execute('INSERT INTO ck_merchandising_family_unit_siblings ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, $params->query_vals(NULL, TRUE));
				$family_unit_sibling_id = self::fetch_insert_id();

				$this->skeleton->rebuild('siblings');
			}
			else $family_unit_sibling_id = NULL;

			self::transaction_commit($savepoint);
			return $family_unit_sibling_id;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyUnitException('Failed to create sibling: '.$e->getMessage());
		}
	}

	public function edit(Array $data) {
		$savepoint = self::transaction_begin();

		try {
			$params = new ezparams($data);
			self::query_execute('UPDATE ck_merchandising_family_units SET '.$params->update_cols(TRUE).' WHERE family_unit_id = :family_unit_id', cardinality::NONE, $params->query_vals(['family_unit_id' => $this->id()], TRUE));

			$this->skeleton->rebuild('header');

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyUnitException('Failed to edit family: '.$e->getMessage());
		}
	}

	public function edit_variance($family_unit_variance_id, Array $data) {
		$savepoint = self::transaction_begin();

		try {
			$params = new ezparams($data);
			self::query_execute('UPDATE ck_merchandising_family_unit_variances SET '.$params->update_cols(TRUE).' WHERE family_unit_variance_id = :family_unit_variance_id', cardinality::NONE, $params->query_vals(['family_unit_variance_id' => $family_unit_variance_id], TRUE));

			$this->skeleton->rebuild('variances');

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyUnitException('Failed to edit variance: '.$e->getMessage());
		}
	}

	public function edit_sibling($family_unit_sibling_id, Array $data) {
		$savepoint = self::transaction_begin();

		try {
			$params = new ezparams($data);
			self::query_execute('UPDATE ck_merchandising_family_unit_siblings SET '.$params->update_cols(TRUE).' WHERE family_unit_sibling_id = :family_unit_sibling_id', cardinality::NONE, $params->query_vals(['family_unit_sibling_id' => $family_unit_sibling_id], TRUE));

			$this->skeleton->rebuild('siblings');

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyUnitException('Failed to edit sibling: '.$e->getMessage());
		}
	}

	public function remove_variance($family_unit_variance_id) {
		$savepoint = self::transaction_begin();

		try {
			self::query_execute('DELETE FROM ck_merchandising_family_unit_variances WHERE family_unit_variance_id = :family_unit_variance_id', cardinality::NONE, [':family_unit_variance_id' => $family_unit_variance_id]);

			$this->skeleton->rebuild('variances');

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyUnitException('Failed to remove variance: '.$e->getMessage());
		}
	}

	public function remove_sibling($family_unit_sibling_id) {
		$savepoint = self::transaction_begin();

		try {
			self::query_execute('DELETE FROM ck_merchandising_family_unit_siblings WHERE family_unit_sibling_id = :family_unit_sibling_id', cardinality::NONE, [':family_unit_sibling_id' => $family_unit_sibling_id]);

			$this->skeleton->rebuild('siblings');

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyUnitException('Failed to remove sibling: '.$e->getMessage());
		}
	}

	public function change_product_listing(Array $data) {
		$savepoint = self::transaction_begin();
		try {
			self::query_execute('UPDATE ck_merchandising_family_unit_siblings SET products_id = :products_id WHERE stock_id = :stock_id', cardinality::NONE, [':products_id' => $data['products_id'], ':stock_id' => $data['stock_id']]);

			//self::query_execute('INSERT INTO ck_merchandising_primary_containers (stock_id, products_id, container_type_id, container_id, redirect, date_created) VALUES (:stock_id, :products_id, 1, :container_id, :redirect, NOW())', cardinality::NONE, [':stock_id' => $data['stock_id'], ':products_id' => $data['products_id'], ':container_id' => $this->id(), ':redirect' => 1]);

			$this->skeleton->rebuild('siblings');
			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyUnitException('Failed to remove sibling: '.$e->getMessage());
		}
	}
}

class CKFamilyUnitException extends CKMasterArchetypeException {
}
?>
