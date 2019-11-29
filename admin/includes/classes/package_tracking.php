<?php
class package_tracking {

	protected static $smArr = [];

	public function pt_get_order_packages($oid) {
		$smArr = [];
		if ($oid > 0) {
			if ($packages = prepared_query::fetch('SELECT orders_packages.*, package_type.*, orders.orders_status, orders_total.external_id FROM orders_packages LEFT JOIN package_type USING (package_type_id) LEFT JOIN orders USING (orders_id) LEFT JOIN orders_total USING (orders_id) WHERE orders_id = :orders_id AND orders_total.class = :shipping AND orders_total.external_id > 0', cardinality::SET, [':orders_id' => $oid, ':shipping' => 'ot_shipping'])) {
				foreach ($packages as $package) {
					$smArr[] = [
						'orders_packages_id' => $package['orders_packages_id'],
						'orders_id' => $package['orders_id'],
						'package_type_id' => $package['package_type_id'],
						'order_package_length' => $package['order_package_length'],
						'order_package_width' => $package['order_package_width'],
						'order_package_height' => $package['order_package_height'],
						'scale_weight' => $package['scale_weight'],
						'package_name' => ($package['custom_dimension'] ? ("Custom ".$package['order_package_length']." x ".$package['order_package_width']." x ".$package['order_package_height'].($package['logoed'] ? " Logo" : "")) : $package['package_name']),
						'package_length' => $package['package_length'],
						'package_width' => $package['package_width'],
						'package_height' => $package['package_height'],
						'custom_dimension' => $package['custom_dimension'],
						'logoed' => $package['logoed'],
						'orders_status' => $package['orders_status'],
						'default_shipping_method_id' => $package['external_id'],
						'void' => $package['void'],
						'date_time_created' => $package['date_time_created'],
						'date_time_voided' => $package['date_time_voided']
					];
				}
			}
		}
		return $smArr;
	}

	public function pt_get_order_tracking($orders_packages_id, $active_only=FALSE) {
		$smArr = [];
		if (!empty($orders_packages_id)) {
			$void = !empty($active_only)?1:NULL;
			if ($tracking_numbers = prepared_query::fetch('SELECT * FROM orders_tracking WHERE orders_packages_id = :orders_packages_id AND (:void IS NULL OR void != 1) ORDER BY void, date_time_voided', cardinality::SET, [':orders_packages_id' => $orders_packages_id, ':void' => $void])) {
				foreach ($tracking_numbers as $tn) {
					$smArr[] = [
						'orders_packages_id' => $tn['orders_packages_id'],
						'orders_tracking_id' => $tn['orders_tracking_id'],
						'tracking_num' => $tn['tracking_num'],
						'shipping_method_id' => $tn['shipping_method_id'],
						'cost' => $tn['cost'],
						'invoiced_cost' => $tn['invoiced_cost'],
						'invoiced_weight' => $tn['invoiced_weight'],
						'date_time_created' => $tn['date_time_created'],
						'date_time_voided' => $tn['date_time_voided'],
						'void' => $tn['void']
					];
				}
			}
		}
		return $smArr;
	}

	public function pt_get_order_status($oid) {
		$tmpArr = [];
		$os = 0;
		if ($oid > 0) {
			$os = prepared_query::fetch('SELECT orders_status FROM orders WHERE orders_id = :orders_id', cardinality::SINGLE, [':orders_id' => $oid]);
		}
		return $os;
	}

	public function pt_pckg_type($pckgArr) {
		$pckgName = "";
		if (is_array($pckgArr)) {
			$pckg_length = $pckgArr['package_length'] ? $pckgArr['package_length'] : 0;
			$pckg_width = $pckgArr['package_width'] ? $pckgArr['package_width'] : 0;
			$pckg_height = $pckgArr['package_height'] ? $pckgArr['package_height'] : 0;

			if ($pckg_length + $pckg_width + $pckg_height === 0) {
				$pckgName = "Custom Package";
			}
			else {
				$pckgName = "$pckg_length x $pckg_width x $pckg_height";
			}
		}
		return $pckgName;
	}

	public function pt_pckg_weight($pckgArr) {
		$pckgWeight = "";
		if (is_array($pckgArr)) {
			$pckgWeight = $pckgArr['scale_weight'] ? $pckgArr['scale_weight'] : '';
		}
		return $pckgWeight;
	}

	public function updateOrderPackage($orderid, $packagesid, $updateArr, $act) {
		if ($orderid > 0 && is_array($updateArr) && count($updateArr) > 0 && $act) {

			$package_type_id = $updateArr[$packagesid]['package_type_id'] ? $updateArr[$packagesid]['package_type_id'] : 1;
			$order_package_length = $updateArr[$packagesid]['order_package_length'] > 0 ? $updateArr[$packagesid]['order_package_length'] : 0;
			$order_package_width = $updateArr[$packagesid]['order_package_width'] > 0 ? $updateArr[$packagesid]['order_package_width'] : 0;
			$order_package_height = $updateArr[$packagesid]['order_package_height'] > 0 ? $updateArr[$packagesid]['order_package_height'] : 0;
			$scale_weight = $updateArr[$packagesid]['scale_weight'] > 0 ? $updateArr[$packagesid]['scale_weight'] : 0;

			switch ($act) {
				case 'void_package':
					prepared_query::execute('UPDATE orders_packages SET void = 1, date_time_voided = NOW() WHERE orders_packages_id = :orders_packages_id', [':orders_packages_id' => $packagesid]);

					//remove the order product
					prepared_query::execute('DELETE op FROM orders_products op JOIN orders_packages opkg ON op.orders_products_id = opkg.order_product_id WHERE opkg.orders_packages_id = :orders_packages_id', [':orders_packages_id' => $packagesid]);
					break;
				case 'quick_add_package':
				case 'add_package':
					if (!($stock_id = prepared_query::fetch('SELECT pt.ipn_id FROM package_type pt WHERE pt.package_type_id = :package_type_id', cardinality::SINGLE, [':package_type_id' => $package_type_id]))) {
						$stock_id = 0;
					}

					$ipn = new ck_ipn2($stock_id);
					if ($ipn->found()) {
						$products = $ipn->get_listings();
						$product = $products[0];
				
						$opArray = [
							':orders_id' => $orderid,
							':products_id' => $product->id(),
							':products_model' => $product->get_header('products_model'),
							':products_name' => $product->get_header('products_name'),
							':products_price' => '0.00',
							':final_price' => '0.00',
							':display_price' => '0.00',
							':price_reason' => '0',
							':products_tax' => '0.00',
							':products_quantity' => '1',
							':cost' => $ipn->get_header('average_cost')
						];

						$order_product_id = prepared_query::insert('INSERT INTO orders_products (orders_id, products_id, products_model, products_name, products_price, final_price, display_price, price_reason, products_tax, products_quantity, cost) VALUES (:orders_id, :products_id, :products_model, :products_name, :products_price, :final_price, :display_price, :price_reason, :products_tax, :products_quantity, :cost)', $opArray);
					}
					
					$packageArray = [
						':orders_id' => $orderid,
						':order_product_id' => !empty($order_product_id)?$order_product_id:0,
						':package_type_id' => $package_type_id,
						':order_package_length' => $order_package_length,
						':order_package_width' => $order_package_width,
						':order_package_height' => $order_package_height,
						':scale_weight' => $scale_weight,
						':void' => 0,
					];

					$packagesid = prepared_query::insert('INSERT INTO orders_packages (orders_id, order_product_id, package_type_id, order_package_length, order_package_width, order_package_height, scale_weight, void, date_time_created) VALUES (:orders_id, :order_product_id, :package_type_id, :order_package_length, :order_package_width, :order_package_height, :scale_weight, :void, NOW())', $packageArray);

					break;
				case 'update_package':
					if (!empty($packagesid)) {
						$package = prepared_query::fetch('SELECT package_type_id, order_product_id as orders_products_id FROM orders_packages WHERE orders_packages_id = :orders_packages_id', cardinality::ROW, [':orders_packages_id' => $packagesid]);
						$packageArray = [];

						if ($package_type_id != 0 && $package_type_id != $package['package_type_id']) {
							prepared_query::execute('DELETE FROM orders_products WHERE orders_products_id = :orders_products_id', [':orders_products_id' => $package['orders_products_id']]);
							$ipn_id = prepared_query::fetch("select pt.ipn_id from package_type pt where pt.package_type_id = :package_type_id", cardinality::SINGLE, [':package_type_id' => $package_type_id]);
							$ipn = new ck_ipn2($ipn_id);
							$product = $ipn->get_default_listing();
							
							$opArray = [
								':orders_id' => $orderid,
								':products_id' => $product->id(),
								':products_model' => $product->get_header('products_model'),
								':products_name' => $product->get_header('products_name'),
								':products_price' => '0.00',
								':final_price' => '0.00',
								':display_price' => '0.00',
								':price_reason' => '0',
								':products_tax' => '0.00',
								':products_quantity' => '1',
								':cost' => $ipn->get_avg_cost()
							];
							$order_product_id = prepared_query::insert('INSERT INTO orders_products (orders_id, products_id, products_model, products_name, products_price, final_price, display_price, price_reason, products_tax, products_quantity, cost) VALUES (:orders_id, :products_id, :products_model, :products_name, :products_price, :final_price, :display_price, :price_reason, :products_tax, :products_quantity, :cost)', $opArray);

							$packageArray['order_product_id'] = $order_product_id;
							$packageArray['package_type_id'] = $package_type_id;
						}
						if ($order_package_length >= 0) {
							$packageArray['order_package_length'] = $order_package_length;
						}
						if ($order_package_width >= 0) {
							$packageArray['order_package_width'] = $order_package_width;
						}
						if ($order_package_height >= 0) {
							$packageArray['order_package_height'] = $order_package_height;
						}
						if ($scale_weight >= 0) {
							$packageArray['scale_weight'] = $scale_weight;
						}

						if (count($packageArray) > 0) {
							$updates = new prepared_fields($packageArray, prepared_fields::UPDATE_QUERY);
							$id = new prepared_fields(['orders_packages_id' => $packagesid]);
							prepared_query::execute('UPDATE orders_packages SET '.$updates->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($updates, $id));
						}
					}
					else {
						$packageArray = [
							':orders_id' => $orderid,
							':package_type_id' => $package_type_id,
							':order_package_length' => $order_package_length,
							':order_package_width' => $order_package_width,
							':order_package_height' => $order_package_height,
							':scale_weight' => $scale_weight,
							':void' => 0,
						];

						$packagesid = prepared_query::insert('INSERT INTO orders_packages (orders_id, package_type_id, order_package_length, order_package_width, order_package_height, scale_weight, void, date_time_created) VALUES (:orders_id, :package_type_id, :order_package_length, :order_package_width, :order_package_height, :scale_weight, :void, NOW())', $packageArray);
					}
					break;
				case 'delete_packages':
					//remove the order product
					prepared_query::execute('DELETE op, opkg, ot FROM orders_packages opkg LEFT JOIN orders_products op ON op.orders_products_id = opkg.order_product_id LEFT JOIN orders_tracking ot ON opkg.orders_packages_id = ot.orders_packages_id WHERE opkg.orders_packages_id = :orders_packages_id', [':orders_packages_id' => $packagesid]);
					break;
				default:
					break;
			}
			$this->updateOrderTracking($packagesid, $updateArr, $act);
		}
		return true;
	}

	public function updateOrderTracking($packagesid, $updateArr, $act) {
		$trackArr = self::pt_get_order_tracking($packagesid, " AND void <> 1");
		$orders_tracking_id = !empty($updateArr[$packagesid]['orders_tracking_id'])?$updateArr[$packagesid]['orders_tracking_id']:NULL;
		$tracking_num = !empty($updateArr[$packagesid]['tracking_num'])?$updateArr[$packagesid]['tracking_num']:NULL;
		$shipping_method_id = !empty($updateArr[$packagesid]['shipping_method_id'])?$updateArr[$packagesid]['shipping_method_id']:NULL;
		$cost = !empty($updateArr[$packagesid]['cost'])?$updateArr[$packagesid]['cost']:NULL;

		if ($act === 'delete_packages' && $orders_tracking_id) prepared_query::execute('DELETE FROM orders_tracking WHERE orders_tracking_id = :orders_tracking_id', [':orders_tracking_id' => $orders_tracking_id]);
		elseif ($act === 'void_package' && $orders_tracking_id) {
			if (!empty($tracking_num)) {
				# void
				prepared_query::execute('UPDATE orders_tracking SET void = 1, date_time_voided = NOW() WHERE orders_tracking_id = :orders_tracking_id', [':orders_tracking_id' => $orders_tracking_id]);
			}
			else prepared_query::execute('DELETE FROM orders_tracking WHERE orders_tracking_id = :orders_tracking_id', [':orders_tracking_id' => $orders_tracking_id]);
		}
		elseif ($act === 'void_shipment' && $orders_tracking_id) {
			prepared_query::execute('UPDATE orders_tracking SET void = 1, date_time_voided = NOW() WHERE orders_tracking_id = :orders_tracking_id', [':orders_tracking_id' => $orders_tracking_id]);
			# create another default tracking record when voiding
			$insert = [
				':orders_packages_id' => $packagesid,
				':tracking_num' => '',
				':shipping_method_id' => 0,
				':cost' => 0,
				':void' => 0,
			];
			prepared_query::insert('INSERT INTO orders_tracking (orders_packages_id, tracking_num, shipping_method_id, cost, void, date_time_created) VALUES (:orders_packages_id, :tracking_num, :shipping_method_id, :cost, :void, NOW())', $insert);
		}
		elseif (count($trackArr) >= 1 && $orders_tracking_id) {
			# update
			$update = [
				':orders_packages_id' => $packagesid,
				':tracking_num' => $tracking_num,
				':shipping_method_id' => $shipping_method_id,
				':cost' => $cost,
				':orders_tracking_id' => $orders_tracking_id
			];

			prepared_query::execute('UPDATE orders_tracking SET orders_packages_id = :orders_packages_id, tracking_num = :tracking_num, shipping_method_id = :shipping_method_id, cost = :cost WHERE orders_tracking_id = :orders_tracking_id', $update);
		}
		else {
			# insert
			$insert = [
				'orders_packages_id' => $packagesid,
				'void' => 0,
			];

			if (!is_null($tracking_num)) $insert['tracking_num'] = $tracking_num;
			if (!is_null($shipping_method_id)) $insert['shipping_method_id'] = $shipping_method_id;
			if (!is_null($cost)) $insert['cost'] = $cost;

			$insert = new prepared_fields($insert, prepared_fields::INSERT_QUERY);
			prepared_query::execute('INSERT INTO orders_tracking ('.$insert->insert_fields().', date_time_created) VALUES ('.$insert->insert_values().', NOW())', $insert->insert_parameters());
		}

		return true;
	}

	public function updateTracking($packageArr, $trackingArr) {
		$msg;
		$trackArr = [];
		$trackArr = self::pt_get_order_tracking($packageArr['orders_packages_id']," AND void <> 1");

		$trackingId = ($trackArr[0]['orders_tracking_id'] > 0) ? $trackArr[0]['orders_tracking_id'] : $packageArr['orders_tracking_id'];

		$trackingArr['shipping_method_id'] = $trackingArr['shipping_method_id'] > 0 ? $trackingArr['shipping_method_id'] : 0;
		$trackingArr['cost'] = $trackingArr['cost'] > 0 ? $trackingArr['cost'] : 0;

		if ($trackingId > 0) { # count($trackArr[0]) >= 1 &&
			# update
			$sql = "UPDATE orders_tracking SET tracking_num = '{$trackingArr['tracking_num']}', shipping_method_id = {$trackingArr['shipping_method_id']}, cost = {$trackingArr['cost']} WHERE orders_tracking_id = $trackingId";
		}
		else {
			# insert tracking
			$sql = "INSERT INTO orders_tracking (orders_tracking_id,orders_packages_id,tracking_num,shipping_method_id,cost,void,date_time_created) VALUES (NULL,{$packageArr['orders_packages_id']},'{$trackingArr['tracking_num']}',{$trackingArr['shipping_method_id']},{$trackingArr['cost']},0,'".date("Y-m-d H:i:s")."')";
		}

		if (!empty($sql)) prepared_query::execute($sql);

		return true;
	}

	public function getPackagesArr() {
		$package_types = prepared_query::fetch('SELECT DISTINCT pt.package_type_id, pt.package_name, pt.promote FROM package_type pt LEFT JOIN products_stock_control psc ON pt.ipn_id = psc.stock_id LEFT JOIN products p ON p.stock_id = psc.stock_id WHERE psc.products_stock_control_category_id = 90 AND psc.stock_quantity > 0 AND p.products_id IS NOT NULL');

		usort($package_types, function($a, $b) {
			$aphrases = preg_split('/\s/', $a['package_name']);
			$bphrases = preg_split('/\s/', $b['package_name']);

			if ($aphrases[0] == $bphrases[0]) return $a['package_name']==$b['package_name']?0:($a['package_name']<$b['package_name']?-1:1);

			return $aphrases[0]<$bphrases[0]?-1:1;
		});

		return $package_types;
	}

	public function pt_get_order_shipping_method($oid) {
		$shipping_method = prepared_query::fetch("select external_id from orders_total where orders_total.orders_id = :orders_id and class = 'ot_shipping' ORDER BY external_id DESC limit 1", cardinality::SINGLE, [':orders_id' => $oid]);
		return $shipping_method;
	}

	public function pt_get_original_shipper_code($shipping_method_id) {
		$original_code = prepared_query::fetch("select original_code from shipping_methods where shipping_code = :shipping_method_id limit 1", cardinality::SINGLE, [':shipping_method_id' => $shipping_method_id]);
		return $original_code;
	}

	public function pt_get_order_pack_count($oid, $includevoid=FALSE) {
		$ordercount = prepared_query::fetch('SELECT COUNT(op.orders_packages_id) FROM orders_packages op LEFT JOIN orders_tracking ot ON op.orders_packages_id = ot.orders_packages_id AND ot.void != 1 AND NULLIF(ot.tracking_num, :empty_string) IS NULL WHERE orders_id = :orders_id AND op.void != 1', cardinality::SINGLE, [':orders_id' => $oid, ':empty_string' => '']);

		if (empty($ordercount)) $ordercount = 0;

		return $ordercount;
	}

	public function pt_get_from_order($oid, $fld) {
		$fldval = "";
		$fld = (!$fld) ? "*" : $fld;
		if (!empty($oid)) {
			$fldval = prepared_query::fetch("select $fld from orders where orders.orders_id = :orders_id limit 1", $fld!='*'?cardinality::SINGLE:cardinality::ROW, [':orders_id' => $oid]);
		}
		return $fldval;
	}

	public function cancelFedexShipment($oid, $trackingid) {
		$tmpArr = $smArr = [];

		if (!empty($trackingid)) {
			$trackingnum = prepared_query::fetch("select tracking_num from orders_tracking where orders_tracking_id = :orders_tracking_id limit 1", cardinality::SINGLE, [':orders_tracking_id' => $trackingid]);
		}

		/*if (($ship_type['shipping_type'] == 90) or ($ship_type['shipping_type'] == 92)) {
			$ship_type = 'GROUND';
		}
		else {*/
			$ship_type = 'EXPRESS';
		/*}*/

		require_once(DIR_FS_CATALOG.DIR_WS_FUNCTIONS.'fedex_webservices.php');
		fws_cancel_shipping_label($trackingnum, $ship_type);

		return true;
	}

	public function debugLog($str) {
		$debug = 0;
		if (!empty($debug)) {
			$logfile = $_ENV["TMP"]."/debug.log";
			$debugfile = fopen($logfile,"a");
			fwrite($debugfile,"$str\n");
			fclose($debugfile);
		}
		return true;
	}
}
