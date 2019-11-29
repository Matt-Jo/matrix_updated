<?php
require('includes/application_top.php');

$page_controller = new ck_product_finder_page($_GET['focus']);

$page_details = new ck_page_details;

switch ($page_controller->major_category) {
	case 'ethernet':
		// the page template for this major category
		$page_details->templates[] = 'partial-product_finder-qo-ethernet.mustache.html';

		// the map from the refinement to its customer-friendly name
		$page_details->options_map = [ // $map_friendly_selection
			'Shielded:No' => 'Non-Shielded',
			'Shielded:Yes' => 'Shielded',
			'Crossover:No' => 'Straight through',
			'Crossover:Yes' => 'Crossover'
		];

		$page_details->key_to_attribute_map = [
			'subcategories' => 'Category'
		];

		// which refinement selections are defaulted on page load
		$page_details->options_defaults = ['Shielded:No', 'Crossover:No', 'Packagequantity:1'];

		// the list of attributes (and potentially their values) that we want to deal with in the product finder - ignore all other attributes
		$page_details->attributes = [
			'Subcategory' => 0,
			'Color' => [
				// I don't typically make all of these things aligned because it's just extra whitespace management that I don't want to deal with,
				// but occasionally the aesthetic benefit seems worth it - in this case because it makes the few options with an extra attribute obvious
				'Blue'		=> ['hex' => '#0072bc', 'color' => 'Blue'],
				'Black'		=> ['hex' => '#000',	'color' => 'Black'],
				'Gray'		=> ['hex' => '#acacac', 'color' => 'Gray'],
				'Green'		=> ['hex' => '#049548', 'color' => 'Green'],
				'Red'		=> ['hex' => '#f30319', 'color' => 'Red'],
				'Yellow'	=> ['hex' => '#fee905', 'color' => 'Yellow', 'selhex' => 'black'],
				'White'		=> ['hex' => '#fff',	'color' => 'White', 'selhex' => 'black'],
				'Orange'	=> ['hex' => '#f26522', 'color' => 'Orange'],
				'Purple'	=> ['hex' => '#88026d', 'color' => 'Purple'],
				'Pink'		=> ['hex' => '#f26d7d', 'color' => 'Pink']
			],
			'Length' => 0,
			'Shielded' => 0,
			'Crossover' => 0,
			'Boottype' => ['EASYboot', 'Half-Moon', 'Slim Run', 'Non-Booted'],
			'Packagequantity' => 0
		];

		$page_details->show_additional = TRUE;

		// the option control method is specific to each category
		$page_details->build_enabled_attributes = function($available_attributes) use ($page_controller, $page_details) {
			$enabled_attributes = [];

			// for each of our attribute types, send back which attribute options are enabled
			foreach ($page_details->attributes as $attribute => $preselected_values) {

				$key = strtolower(CK\fn::pluralize($attribute)); // used to interact with our attributes from nextopia

				$original_attribute = !empty($page_details->key_to_attribute_map[$key])?$page_details->key_to_attribute_map[$key]:$attribute;

				if (!empty($available_attributes[$key])) {
					foreach ($available_attributes[$key] as $idx => $details) {
						// if we've got preselected values and this value is enabled, add it
						if (!empty($preselected_values) && !empty($details['enabled?'])) $enabled_attributes[] = ['type' => $original_attribute, 'value' => $idx];
						// if we *don't* have preselected values and it's in this list it's enabled, add it
						elseif (empty($preselected_values)) $enabled_attributes[] = ['type' => $original_attribute, 'value' => $details];
					}
				}
			}

			foreach ($_GET['refinement_data'] as $val) {
				$parts = explode(':', $val);

				if (!isset($page_details->already_plural_attributes[$parts[0]])) $key = strtolower(CK\fn::pluralize($parts[0]));
				else $key = strtolower($parts[0]);

				if (isset($available_attributes[$key])) continue;

				$enabled_attributes[] = ['type' => $parts[0], 'value' => $parts[1]];
			}

			return $enabled_attributes;
		};

		// the display method, like the template it's building, is specific to each category
		// this only covers the quick order section - the other parts of the page are generic
		$page_details->build_quick_order = function(ck_listing_category $category, $available_attributes, ck_content $content_map) use ($page_controller, $page_details) {


			// main_category/specific_category: if product_finder_title, else category_name
			$content_map->finder = ['main_category' => ucwords($page_controller->major_category)]; //, 'specific_category' => $page_controller->specific_category];

			$content_map->finder['categories'] = [];
			foreach ($category->get_progenitor()->get_finder_subcategories()['shop_by'] as $qocat) {
				// create the quick order category entry
				$cat = ['id' => $qocat->get_header('categories_id'), 'name' => $qocat->get_header('categories_name')];
				if ($category->get_header('categories_id') == $qocat->get_header('categories_id')) $cat['selected?'] = 1;
				$content_map->finder['categories'][] = $cat;
			}

			$content_map->finder['colors'] = [];
			// dereference to get rid of the named key
			if (!empty($available_attributes['colors'])) {
				foreach ($available_attributes['colors'] as $color) {
					$content_map->finder['colors'][] = $color;
				}
			}

			$content_map->finder['lengths'] = [];
			if (!empty($available_attributes['lengths'])) {
				foreach ($available_attributes['lengths'] as $idx => $length) {
					if ($length == 'Unset') continue;
					$len = ['length' => $length, 'display_length' => preg_replace('/[^0-9.]/', '', $length)];
					if ($idx <= 6) $len['top?'] = 1;
					if ($idx%7 == 0) $len['left?'] = 1;
					$content_map->finder['lengths'][] = $len;
				}
			}

			$content_map->finder['boottypes'] = [];
			if (!empty($available_attributes['boottypes'])) {
				foreach ($available_attributes['boottypes'] as $boot) {
					$content_map->finder['boottypes'][] = $boot;
				}
			}

			$content_map->finder['packagequantities'] = [];
			if (!empty($available_attributes['packagequantities'])) {
				foreach ($available_attributes['packagequantities'] as $quantity) {
					$package_quantity = [];
					if ($quantity == 1) $package_quantity['default'] = TRUE;
					$package_quantity['quantity'] = $quantity;

					$content_map->finder['packagequantities'][] = $package_quantity;
				}
			}

			$content_map->finder['selections'] = implode(' - ', array_filter(array_map(function($v) use ($page_details, $category) {
				// if we've got a friendly name for this option, return it
				if (!empty($page_details->options_map[$v])) return $page_details->options_map[$v];
				return NULL;
			}, array_values($_GET['refinement_data']))));

			// we don't need to do anything, $content_map is modified by reference
		};
		break;
	case 'fiber':
		// the page template for this major category
		$page_details->templates[] = 'partial-product_finder-qo-fiber.mustache.html';

		// the map from the refinement to its customer-friendly name
		$page_details->options_map = [ // $map_friendly_selection
			'Polishtype:PC (Physical Contact)' => 'PC (Physical Contact) Polish',
			'Polishtype:APC (Angled Physical Contact)' => 'APC (Angled Physical Contact) Polish',
			'Polishtype:UPC (Ultra Physical Contact)' => 'UPC (Ultra Physical Contact) Polish',
			'Polishtype:APC / UPC (Angled Physical Contact / Ultra Physical Contact)' => 'APC/UPC (Angled Physical Contact / Ultra Physical Contact) Polish',
			'Jacket:OFNP' => 'OFNP Jacket',
			'Jacket:OFNR' => 'OFNR Jacket',
			//'Jacket:PVC' => 'PVC Jacket',
			//'Jacket:Plenum' => 'Plenum Jacket',
			//'Jacket:Rugged' => 'Rugged Jacket'
		];

		// which refinement selections are defaulted on page load
		$page_details->options_defaults = [];
		// 'Polishtype:PC (Physical Contact)', 'Jacket:OFNR', 'Brand:CablesAndKits'
		// the list of attributes (and potentially their values) that we want to deal with in the product finder - ignore all other attributes
		$page_details->attributes = [
			'Connectors' => 0 /*[
				'LC' => ['connector' => 'LC'],
				'SC' => ['connector' => 'SC'],
				'ST' => ['connector' => 'ST'],
				'MPO' => ['connector' => 'MPO'],
				'Tail' => ['connector' => 'Tail']
			]*/,
			'Connectorpairs' => 0,
			'Cabletype' => [
				// I don't typically make all of these things aligned because it's just extra whitespace management that I don't want to deal with,
				// but occasionally the aesthetic benefit seems worth it - in this case because it makes the few options with an extra attribute obvious
				'OM1'		=> ['hex' => '#f26522', 'cabletype' => 'OM1', 'description' => 'OM1 (62.5/125)'],
				'OM2'		=> ['hex' => '#f26522',	'cabletype' => 'OM2', 'description' => 'OM2 (50/125)'],
				'OM3'		=> ['hex' => '#00ffff', 'cabletype' => 'OM3', 'description' => 'OM3 (50/125)', 'selhex' => 'black'],
				'OM4'		=> ['hex' => '#00ffff', 'cabletype' => 'OM4', 'description' => 'OM4 (50/125)', 'selhex' => 'black', 'pair' => '#ff63ff'],
				'OM5'		=> ['hex' => '#c4de5f', 'cabletype' => 'OM5', 'description' => 'OM5 (50/125)', 'selhex' => 'black'],
				'OS2'		=> ['hex' => '#fee905', 'cabletype' => 'OS2', 'description' => 'OS2 (9/125)', 'selhex' => 'black']
			],
			/*'Lengthinmeters' => 0,
			'Lengthinfeet' => 0,*/
			'Length' => 0,
			'Polishtype' => 0,
			'Jacket' => 0,
			'Brand' => ['CablesAndKits', 'Generic'],
			'Connectortypes' => 0,
			'Corematerial' => 0
		];

		// simple pluralizing is easy, but recognizing an existing plural is much harder
		$page_details->already_plural_attributes = ['Connectors' => 1, 'Connectorpairs' => 1, 'Connectortypes' => 1];

		if (!$page_controller->is_top_level()) {
			$page_details->build_query = function() {
				$_GET['refinement_data']['Stub:Stub'] = 'Stub:Stub';
			};
			$page_details->attribute_adjustments['Stub'] = 'Connectors';
		}

		// we can directly modify the refinement data here
		$connectors = [NULL, NULL];

		if (!empty($_GET['refinement_data'])) {
			// we want to make a selection for connector 1 be symmetric with a selection for connector 2
			// e.g. if they select LC on connector 1, it should find anything with LC in connector 1 *or* connector 2
			// if they select connector 1 LC connector 2 SC, it should find LC-SC *and* SC-LC

			foreach ($_GET['refinement_data'] as $key => $val) {
				$parts = explode(':', $val);
				if ($parts[0] == 'Connector1') $connectors[0] = $parts[1];
				elseif ($parts[0] == 'Connector2') $connectors[1] = $parts[1];
				else continue; // skip anything else, so when we kill the connector entries, we're not killing anything else

				unset($_GET['refinement_data'][$key]);
			}

			if (!empty($connectors[0]) && !empty($connectors[1])) {
				// we've already limited both sides, so we can just duplicate it the other way 'round and be done
				$connector_pair = $connectors[0].' - '.$connectors[1];
				$_GET['refinement_data']['Connectorpairs:'.$connector_pair] = 'Connectorpairs:'.$connector_pair;
			}
			elseif (!empty($connectors[0])) {
				// if we've only selected connector 1, we've got a problem: if we duplicate it to connector 2 it becomes an "and" search rather
				// than an "or" search - "LC" becomes "LC-LC *only*" rather than "LC- or -LC"
				// we can only remedy this by running the query twice, once with connector1:LC & connector2 blank and once
				// with connector2:LC & connector1 blank

				// refinement_data2 will cause the nextopia class to run a 2nd time and merge results
				$_GET['refinement_data']['Connectors:'.$connectors[0]] = 'Connectors:'.$connectors[0];
			}
			elseif (!empty($connectors[1])) {
				// same as only connector1 above
				$_GET['refinement_data']['Connectors:'.$connectors[1]] = 'Connectors:'.$connectors[1];
			}
			// otherwise we don't have any connectors defined, we can ignore it
		}

		// the option control method is specific to each category
		$page_details->build_enabled_attributes = function($available_attributes) use ($page_controller, $page_details, $connectors) {
			$enabled_attributes = [];

			$fill_connector1 = TRUE;
			$fill_connector2 = TRUE;

			if (isset($available_attributes['connectorpairs'])) {
				foreach ($available_attributes['connectorpairs'] as $pair) {
					$pair = explode(' - ', $pair);

					if (!empty($connectors[0]) && !empty($connectors[1])) {
						if ($pair[0] == $connectors[0]) {
							$enabled_attributes[] = ['type' => 'Connector1', 'value' => $pair[0]];
							$enabled_attributes[] = ['type' => 'Connector2', 'value' => $pair[1]];
						}
						elseif ($pair[0] == $connectors[1]) {
							$enabled_attributes[] = ['type' => 'Connector1', 'value' => $pair[1]];
							$enabled_attributes[] = ['type' => 'Connector2', 'value' => $pair[0]];
						}

						if ($pair[1] == $connectors[1]) {
							$enabled_attributes[] = ['type' => 'Connector1', 'value' => $pair[0]];
							$enabled_attributes[] = ['type' => 'Connector2', 'value' => $pair[1]];
						}
						elseif ($pair[1] == $connectors[0]) {
							$enabled_attributes[] = ['type' => 'Connector1', 'value' => $pair[1]];
							$enabled_attributes[] = ['type' => 'Connector2', 'value' => $pair[0]];
						}

						$fill_connector1 = FALSE;
						$fill_connector2 = FALSE;
					}
					elseif (!empty($connectors[0])) {
						if ($pair[0] == $connectors[0]) $enabled_attributes[] = ['type' => 'Connector2', 'value' => $pair[1]];
						elseif ($pair[1] == $connectors[0]) $enabled_attributes[] = ['type' => 'Connector2', 'value' => $pair[0]];

						$fill_connector2 = FALSE;
					}
					elseif (!empty($connectors[1])) {
						if ($pair[0] == $connectors[1]) $enabled_attributes[] = ['type' => 'Connector1', 'value' => $pair[1]];
						elseif ($pair[1] == $connectors[1]) $enabled_attributes[] = ['type' => 'Connector1', 'value' => $pair[0]];

						$fill_connector1 = FALSE;
					}
				}
			}

			// for each of our attribute types, send back which attribute options are enabled
			foreach ($page_details->attributes as $attribute => $preselected_values) {
				if ($attribute == 'Connectorpairs') continue;

				if (!isset($page_details->already_plural_attributes[$attribute])) $key = strtolower(CK\fn::pluralize($attribute));
				else $key = strtolower($attribute);
				if ($key == 'connectors') {
					if (!empty($available_attributes[$key])) {
						foreach ($available_attributes[$key] as $idx => $details) {
							// determine if we've already set enabled attributes above for the particular column
							if ($fill_connector1) $enabled_attributes[] = ['type' => 'Connector1', 'value' => $details];
							if ($fill_connector2) $enabled_attributes[] = ['type' => 'Connector2', 'value' => $details];
						}
					}
				}
				else {
					if (!empty($available_attributes[$key])) {
						foreach ($available_attributes[$key] as $idx => $details) {
							// if we've got preselected values and this value is enabled, add it
							if (!empty($preselected_values) && !empty($details['enabled?'])) $enabled_attributes[] = ['type' => $attribute, 'value' => $idx];
							// if we *don't* have preselected values and it's in this list it's enabled, add it
							elseif (empty($preselected_values)) $enabled_attributes[] = ['type' => $attribute, 'value' => $details];
						}
					}
				}
			}

			foreach ($_GET['refinement_data'] as $val) {
				$parts = explode(':', $val);

				if (!isset($page_details->already_plural_attributes[$parts[0]])) $key = strtolower(CK\fn::pluralize($parts[0]));
				else $key = strtolower($parts[0]);

				if (isset($available_attributes[$key])) continue;

				$enabled_attributes[] = ['type' => $parts[0], 'value' => $parts[1]];
			}

			return $enabled_attributes;
		};

		// the display method, like the template it's building, is specific to each category
		// this only covers the quick order section - the other parts of the page are generic
		$page_details->build_quick_order = function(ck_listing_category $category, $available_attributes, ck_content $content_map) use ($page_controller, $page_details) {

			$content_map->{'sbccat?'} = 'Optical Mode';

			// main_category/specific_category: if product_finder_title, else category_name
			$content_map->finder = ['main_category' => ucwords($page_controller->major_category)]; //, 'specific_category' => $page_controller->specific_category];

			if (!$page_controller->is_top_level()) {
				$endopts = explode(' to ', strtolower($page_controller->specific_category));
				$end1_selected = $end2_selected = FALSE;
			}

			// dereference to get rid of the named key
			$content_map->finder['connectors_1'] = [];
			if (!empty($available_attributes['connectors'])) {
				foreach ($available_attributes['connectors'] as $idx => $connector) {
					if ($connector == 'Unset') continue;
					//if (empty($connector['enabled?'])) continue;
					$connector = ['connector' => $connector];
					if (!$page_controller->is_top_level() && !$end1_selected && ($endidx = array_search(strtolower($connector['connector']), $endopts)) !== FALSE) {
						$end1_selected = $connector['selected?'] = 1;
						unset($endopts[$endidx]);
					}
					$content_map->finder['connectors_1'][] = $connector;
				}
			}

			$content_map->finder['connectors_2'] = [];
			if (!empty($available_attributes['connectors'])) {
				foreach ($available_attributes['connectors'] as $idx => $connector) {
					if ($connector == 'Unset') continue;
					//if (empty($connector['enabled?'])) continue;
					$connector = ['connector' => $connector];
					if (!$page_controller->is_top_level() && !$end2_selected && ($endidx = array_search(strtolower($connector['connector']), $endopts)) !== FALSE) {
						$end2_selected = $connector['selected?'] = 1;
						unset($endopts[$endidx]);
					}
					$content_map->finder['connectors_2'][] = $connector;
				}
			}

			$content_map->finder['cable_types'] = [];
			if (!empty($available_attributes['cabletypes'])) {
				foreach ($available_attributes['cabletypes'] as $cable_type) {
					$content_map->finder['cable_types'][] = $cable_type;
				}
			}

			$content_map->finder['lengths'] = [];
			if (!empty($available_attributes['lengths'])) {
				foreach ($available_attributes['lengths'] as $idx => $length) {
					if ($length == 'Unset') continue;
					$len = ['length' => $length, 'display_length' => preg_replace('/[^0-9.]/', '', $length)];
					$len['length_in_feet'] = round($len['display_length'] * (39.370/12), 2);
					if ($idx <= 6) $len['top?'] = 1;
					if ($idx%7 == 0) $len['left?'] = 1;
					$content_map->finder['lengths'][] = $len;
				}
			}

			$content_map->finder['polish_types'] = [];
			if (!empty($available_attributes['polishtypes'])) {
				$content_map->finder['polish_types?'] = 1;
				// we just want to make sure this one is first, so the index is hardcoded
				$content_map->finder['polish_types'][0] = ['polish' => 'All', 'polish_short_name' => 'All', 'selected?' => 1];
				foreach ($available_attributes['polishtypes'] as $idx => $polish) {
					$selected = FALSE;
					foreach ($_GET['refinement_data'] as $val) {
						$val = explode(':', $val);
						if ($val[0] == 'Polishtype' && $val[1] == $polish) {
							unset($content_map->finder['polish_types'][0]['selected?']);
							$selected = TRUE;
							break;
						}
					}
					$pol = ['polish' => $polish, 'polish_short_name' => trim(preg_replace("/\([^)]+\)/", "", $polish))];
					if ($selected) $pol['selected?'] = 1;
					$content_map->finder['polish_types'][] = $pol;
				}
			}

			$content_map->finder['jackets'] = [];
			if (!empty($available_attributes['jackets'])) {
				$content_map->finder['jackets?'] = 1;
				// we just want to make sure this one is first, so the index is hardcoded
				$content_map->finder['jackets'][0] = ['jacket' => 'All', 'selected?' => 1];
				foreach ($available_attributes['jackets'] as $idx => $jacket) {
					$selected = FALSE;
					foreach ($_GET['refinement_data'] as $val) {
						$val = explode(':', $val);
						if ($val[0] == 'Jacket' && $val[1] == $jacket) {
							unset($content_map->finder['jackets'][0]['selected?']);
							$selected = TRUE;
							break;
						}
					}
					$jac = ['jacket' => $jacket];
					if ($selected) $jac['selected?'] = 1;
					$content_map->finder['jackets'][] = $jac;
				}

			}

			$content_map->finder['brands'] = [];
			if (!empty($available_attributes['brands'])) {
				$content_map->finder['brands?'] = 1;
				$content_map->finder['brands'][0] = ['brand' => 'All', 'selected?' => 1];
				foreach ($available_attributes['brands'] as $idx => $brand) {
					$selected = FALSE;
					foreach ($_GET['refinement_data'] as $val) {
						$val = explode(':', $val);
						if ($val[0] == 'Brand' && $val[1] == $brand) {
							unset($content_map->finder['brands'][0]['selected?']);
							$selected = TRUE;
							break;
						}
					}
					$brand_result = ['brand' => $brand];
					if ($selected) $brand_result['selected?'] = 1;
					$content_map->finder['brands'][] = $brand_result;
				}
			}

			$content_map->finder['connectortypes'] = [];
			if (!empty($available_attributes['connectortypes'])) {
				$content_map->finder['connectortypes?'] = 1;
				$content_map->finder['connectortypes'][0] = ['connectortype' => 'All', 'selected?' => 1];
				foreach ($available_attributes['connectortypes'] as $idx => $connector) {
					$selected = FALSE;
					foreach ($_GET['refinement_data'] as $val) {
						$val = explode(':', $val);
						if ($val[0] == 'Connectortypes' && $val[1] == $connector) {
							unset($content_map->finder['connectortypes'][0]['selected?']);
							$selected = TRUE;
							break;
						}
					}
					$connector_result = ['connectortype' => $connector];
					if ($selected) $connector_result['selected?'] = 1;
					$content_map->finder['connectortypes'][] = $connector_result;
				}
			}

			$content_map->finder['corematerials'] = [];
			if (!empty($available_attributes['corematerials'])) {
				$content_map->finder['corematerials?'] = 1;
				$content_map->finder['corematerials'][0] = ['corematerial' => 'All', 'selected?' => 1];
				foreach ($available_attributes['corematerials'] as $idx => $core) {
					$selected = FALSE;
					foreach ($_GET['refinement_data'] as $val) {
						$val = explode(':', $val);
						if ($val[0] == 'Corematerials' && $val[1] == $core) {
							unset($content_map->finder['corematerials'][0]['selected?']);
							$selected = TRUE;
							break;
						}
					}
					$core_result = ['corematerial' => $core];
					if ($selected) $core_result['selected?'] = 1;
					$content_map->finder['corematerials'][] = $core_result;
				}
			}

			$content_map->finder['selections'] = implode(' - ', array_filter(array_map(function($v) use ($page_details, $category) {
				// if we've got a friendly name for this option, return it
				if (!empty($page_details->options_map[$v])) return $page_details->options_map[$v];
				return NULL;
			}, array_values($_GET['refinement_data']))));

			if (!$page_controller->is_top_level()) $content_map->finder['run_immediately?'] = 1;

			// we don't need to do anything, $content_map is modified by reference
		};

		break;
	default:
		// we need to show a 404 of some kind
		break;
}

$page_controller->control($page_details);

require_once(__DIR__.'/templates/Pixame_v1/main_page.tpl.php');
?>
